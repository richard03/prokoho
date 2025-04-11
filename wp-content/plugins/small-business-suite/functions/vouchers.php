<?php

// Zabránění přímému přístupu
if (!defined('ABSPATH')) {
    exit;
}


// Vytvoření tabulek při aktivaci pluginu
function vouchers_db_init() {
    global $wpdb;
    $vouchers_table = $wpdb->prefix . 'small_business_suite_vouchers';
    $charset_collate = $wpdb->get_charset_collate();

    $sql_vouchers = "CREATE TABLE IF NOT EXISTS $vouchers_table (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        discount varchar(50) NOT NULL,
        code varchar(50) NOT NULL,
        valid_to date NOT NULL,
        status varchar(20) NOT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql_vouchers);    
}


// Administrace pluginu - slevové kupóny
function get_vouchers() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'small_business_suite_vouchers';


    // Získání všech kuponů
    $vouchers = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC");
    
    return $vouchers;
}





// Kontrola unikátnosti kódu
function is_discount_code_unique($code) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'small_business_suite_vouchers';
    $existing_code = $wpdb->get_var($wpdb->prepare(
        "SELECT code FROM $table_name WHERE code = %s",
        $code
    ));
    return empty($existing_code);
}

// Generování náhodného kódu
function generate_discount_code() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'small_business_suite_vouchers';
    $max_attempts = 100; // Maximální počet pokusů pro generování unikátního kódu
    $attempts = 0;
    
    do {
        $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $code = '';
        for ($i = 0; $i < 3; $i++) {
            for ($j = 0; $j < 4; $j++) {
                $code .= $characters[rand(0, strlen($characters) - 1)];
            }
            if ($i < 2) {
                $code .= '-';
            }
        }
        $attempts++;
        
        // Pokud se nepodaří vygenerovat unikátní kód po 100 pokusech, vyvoláme chybu
        if ($attempts >= $max_attempts) {
            wp_die('Nepodařilo se vygenerovat unikátní slevový kód. Zkuste to prosím znovu.');
        }
    } while (!is_discount_code_unique($code));
    
    return $code;
}


// Převod barev z HEX na RGB
function hex2rgb($hex) {
    $hex = str_replace('#', '', $hex);
    if (strlen($hex) == 3) {
        $r = hexdec(substr($hex, 0, 1) . substr($hex, 0, 1));
        $g = hexdec(substr($hex, 1, 1) . substr($hex, 1, 1));
        $b = hexdec(substr($hex, 2, 1) . substr($hex, 2, 1));
    } else {
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
    }
    return array($r, $g, $b);
}




// Přidání endpointu pro generování PDF
function pdf_add_endpoint() {
    add_rewrite_rule(
        '^small-business-suite/pdf/([0-9]+)/?$',
        'index.php?discount_voucher_pdf=$matches[1]',
        'top'
    );
    flush_rewrite_rules();
}

// Registrace query var
function pdf_register_query_var($vars) {
    error_log('PDF endpoint: Registering query var');
    $vars[] = 'discount_voucher_pdf';
    return $vars;
}

// Generování PDF
function generate_voucher_pdf($discount, $code, $valid_to) {
    require_once(ABSPATH . 'wp-content/plugins/small-business-suite/fpdf/fpdf.php');

    // Vytvoření nového PDF dokumentu
    $pdf = new FPDF();
    
    // Načtení obrázku pozadí
    global $wpdb;
    $settings_table = $wpdb->prefix . 'small_business_suite_pdf_settings';
    $background_image = $wpdb->get_var($wpdb->prepare(
        "SELECT setting_value FROM $settings_table WHERE setting_key = %s",
        'background_image'
    ));
    
    if ($background_image) {
        // Získání cesty k obrázku
        $upload_dir = wp_upload_dir();
        $image_path = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $background_image);
        
        // Získání rozměrů obrázku
        list($width, $height) = getimagesize($image_path);
        
        // Určení orientace stránky na základě poměru stran obrázku
        $orientation = ($width > $height) ? 'L' : 'P';
        
        // Výpočet rozměrů stránky
        if ($orientation == 'L') {
            $page_width = ($width / $height) * 210;
            $page_height = 210;
        } else {
            $page_width = 210;
            $page_height = ($height / $width) * 210;
        }
        
        // Vytvoření stránky s odpovídající orientací a rozměry
        $pdf->AddPage($orientation, array($page_width, $page_height));
        
        // Umístění obrázku
        $pdf->Image($background_image, 0, 0, $page_width, $page_height);
    } else {
        $pdf->AddPage();
        $page_width = 210;
        $page_height = 297;
    }
    
    // Načtení nastavení textu
    $settings = array();
    $results = $wpdb->get_results("SELECT setting_key, setting_value FROM $settings_table");
    foreach ($results as $row) {
        $settings[$row->setting_key] = $row->setting_value;
    }
    
    // Výchozí hodnoty
    $defaults = array(
        'discount_y' => '50',
        'discount_color' => '#000000',
        'discount_align' => 'C',
        'code_y' => '70',
        'code_color' => '#000000',
        'code_align' => 'C',
        'date_y' => '90',
        'date_color' => '#000000',
        'date_align' => 'C'
    );
    
    $settings = wp_parse_args($settings, $defaults);
    
    // Nastavení fontu
    $pdf->AddFont('Barlow', '', 'Barlow-Regular.php');
    $pdf->AddFont('Barlow', 'B', 'Barlow-Bold.php');
    
    // Nastavení výchozího fontu
    $pdf->SetFont('Barlow', '', 12);

    // nastavení okrajů
    $margin_left = 20;
    $margin_right = 20;
    $margin_top = 20;
    $pdf->SetMargins($margin_left, $margin_top, $margin_right);

    $cell_width = $page_width - ($margin_left + $margin_right);

    // Sleva
    $text = $discount . ' CZK';
    $pdf->SetFont('Barlow', 'B', 60);
    list($r, $g, $b) = hex2rgb($settings['discount_color']);
    $pdf->SetTextColor($r, $g, $b);
    $pdf->SetXY($margin_left, ($page_height * $settings['discount_y']) / 100);
    $pdf->Cell($cell_width, 0, $text, 0, 1, $settings['discount_align']);
    
    // Kód
    $text = $code;
    $pdf->SetFont('Barlow', 'B', 20);
    list($r, $g, $b) = hex2rgb($settings['code_color']);
    $pdf->SetTextColor($r, $g, $b);
    $pdf->SetXY($margin_left, ($page_height * $settings['code_y']) / 100);
    $pdf->Cell($cell_width, 0, $text, 0, 1, $settings['code_align']);
    
    // Platnost
    $text ='do ' . date('d. m. Y', strtotime($valid_to));
    $pdf->SetFont('Barlow', 'B', 20);
    list($r, $g, $b) = hex2rgb($settings['date_color']);
    $pdf->SetTextColor($r, $g, $b);
    $pdf->SetXY($margin_left, ($page_height * $settings['date_y']) / 100);
    $pdf->Cell($cell_width, 0, $text, 0, 1, $settings['date_align']);

    // Vrácení PDF jako string
    return $pdf->Output('', 'S');
}
