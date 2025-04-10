<?php
/**
 * Plugin Name: Discount Codes
 * Description: Plugin pro správu slevových kódů
 * Version: 1.0
 * Author: Prokoho.cz
 */

// Zabránění přímému přístupu
if (!defined('ABSPATH')) {
    exit;
}

// Vytvoření tabulky při aktivaci pluginu
function discount_codes_activate() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'discount_codes';
    $settings_table = $wpdb->prefix . 'discount_codes_settings';
    $orders_table = $wpdb->prefix . 'discount_codes_orders';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        discount varchar(50) NOT NULL,
        code varchar(50) NOT NULL,
        valid_to date NOT NULL,
        status varchar(20) NOT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    $sql_settings = "CREATE TABLE IF NOT EXISTS $settings_table (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        setting_key varchar(50) NOT NULL,
        setting_value text NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    $sql_orders = "CREATE TABLE IF NOT EXISTS $orders_table (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        name varchar(100) NOT NULL,
        email varchar(100) NOT NULL,
        phone varchar(20) NOT NULL,
        course varchar(100) NOT NULL,
        number_of_persons int NOT NULL DEFAULT 1,
        discount_code varchar(20),
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    dbDelta($sql_settings);
    dbDelta($sql_orders);
}
register_activation_hook(__FILE__, 'discount_codes_activate');

// Přidání položky do menu
function discount_codes_menu() {
    add_menu_page(
        'Slevové kupóny',
        'Slevové kupóny',
        'manage_options',
        'discount-codes',
        'discount_codes_page',
        'dashicons-tickets-alt',
        30
    );
    
    add_submenu_page(
        'discount-codes',
        'Nastavení',
        'Nastavení',
        'manage_options',
        'discount-codes-settings',
        'discount_codes_settings_page'
    );

    add_submenu_page(
        'discount-codes',
        'Objednávky',
        'Objednávky',
        'manage_options',
        'discount-codes-orders',
        'discount_codes_orders_page'
    );
}
add_action('admin_menu', 'discount_codes_menu');

// Kontrola unikátnosti kódu
function is_discount_code_unique($code) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'discount_codes';
    $existing_code = $wpdb->get_var($wpdb->prepare(
        "SELECT code FROM $table_name WHERE code = %s",
        $code
    ));
    return empty($existing_code);
}

// Generování náhodného kódu
function generate_discount_code() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'discount_codes';
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

// Hlavní stránka administrace
function discount_codes_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'discount_codes';

    // Zpracování formuláře pro přidání nového kódu
    if (isset($_POST['add_discount_code']) && check_admin_referer('add_discount_code')) {
        $discount = sanitize_text_field($_POST['discount']);
        $code = generate_discount_code();
        $months = intval($_POST['valid_months']);
        $valid_to = date('Y-m-d', strtotime("+$months months"));
        $status = 'active';

        $wpdb->insert(
            $table_name,
            array(
                'discount' => $discount,
                'code' => $code,
                'valid_to' => $valid_to,
                'status' => $status
            ),
            array('%s', '%s', '%s', '%s')
        );
    }

    // Získání všech kódů
    $codes = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC");
    
    // Načtení šablony
    include plugin_dir_path(__FILE__) . 'templates/admin-vouchers.php';
}

// Stránka nastavení
function discount_codes_settings_page() {
    global $wpdb;
    $settings_table = $wpdb->prefix . 'discount_codes_settings';
    
    // Zpracování formuláře
    if (isset($_POST['save_settings']) && check_admin_referer('save_discount_codes_settings')) {
        // Uložení pozadí
        if (isset($_FILES['background_image']) && $_FILES['background_image']['error'] == 0) {
            $upload_dir = wp_upload_dir();
            $target_dir = $upload_dir['basedir'] . '/discount-codes/';
            
            if (!file_exists($target_dir)) {
                wp_mkdir_p($target_dir);
            }
            
            $file_name = basename($_FILES['background_image']['name']);
            $target_file = $target_dir . $file_name;
            
            $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
            if ($imageFileType == "jpg" || $imageFileType == "png" || $imageFileType == "jpeg") {
                if (move_uploaded_file($_FILES['background_image']['tmp_name'], $target_file)) {
                    $wpdb->replace(
                        $settings_table,
                        array(
                            'setting_key' => 'background_image',
                            'setting_value' => $upload_dir['baseurl'] . '/discount-codes/' . $file_name
                        ),
                        array('%s', '%s')
                    );
                }
            } else {
                add_settings_error(
                    'discount_codes_settings',
                    'invalid_file_type',
                    'Podporované formáty obrázků jsou pouze JPG a PNG.',
                    'error'
                );
            }
        }
        
        // Uložení nastavení textu
        $text_settings = array(
            'discount_y' => $_POST['discount_y'],
            'discount_color' => $_POST['discount_color'],
            'discount_align' => $_POST['discount_align'],
            'code_y' => $_POST['code_y'],
            'code_color' => $_POST['code_color'],
            'code_align' => $_POST['code_align'],
            'date_y' => $_POST['date_y'],
            'date_color' => $_POST['date_color'],
            'date_align' => $_POST['date_align']
        );
        
        foreach ($text_settings as $key => $value) {
            $wpdb->replace(
                $settings_table,
                array(
                    'setting_key' => $key,
                    'setting_value' => $value
                ),
                array('%s', '%s')
            );
        }
    }
    
    // Načtení aktuálních nastavení
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
    
    // Načtení šablony
    include plugin_dir_path(__FILE__) . 'templates/admin-settings.php';
}

// Generování PDF
function generate_discount_pdf($discount, $code, $valid_to) {
    require_once(ABSPATH . 'wp-content/plugins/discount-codes/fpdf/fpdf.php');

    // Vytvoření nového PDF dokumentu
    $pdf = new FPDF();
    
    // Načtení obrázku pozadí
    global $wpdb;
    $settings_table = $wpdb->prefix . 'discount_codes_settings';
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
    $pdf->SetDrawColor(50, 50, 50); 

    // Nastavení fontu
    $pdf->AddFont('Barlow', '', 'Barlow-Regular.php');
    $pdf->AddFont('Barlow', 'B', 'Barlow-Bold.php');

    // nastavení okrajů
    $margin_left = 20;
    $margin_right = 20;
    $margin_top = 20;
    $pdf->SetMargins($margin_left, $margin_top, $margin_right);

    $cell_width = 255; // magick0 číslo - nemám ponětí proč zrovna 255, ale funguje to

    // Sleva
    $text = $discount . ' CZK';
    $pdf->SetFont('Barlow', 'B', 60);
    $pdf->SetXY($margin_left, ($page_height * $settings['discount_y']) / 100);
    list($r, $g, $b) = hex2rgb($settings['discount_color']);
    $pdf->SetTextColor($r, $g, $b);
    $pdf->Cell(255, 0, $text, 0, 1, $settings['discount_align']);

    
    // Kód
    $text = $code;
    $pdf->SetFont('Barlow', 'B', 20);
    list($r, $g, $b) = hex2rgb($settings['code_color']);
    $pdf->SetTextColor($r, $g, $b);
    $pdf->SetXY($margin_left, ($page_height * $settings['code_y']) / 100);
    $pdf->Cell(255, 0, $text, 0, 1, $settings['code_align']);
    
    // Platnost
    $text ='do ' . date('d. m. Y', strtotime($valid_to));
    $pdf->SetFont('Barlow', 'B', 20);
    list($r, $g, $b) = hex2rgb($settings['date_color']);
    $pdf->SetTextColor($r, $g, $b);
    $pdf->SetXY($margin_left, ($page_height * $settings['date_y']) / 100);
    $pdf->Cell(255, 0, $text, 0, 1, $settings['date_align']);
    

    // Vrácení PDF jako string
    return $pdf->Output('', 'S');
}

// Přidání endpointu pro generování PDF
function discount_codes_add_endpoint() {
    add_rewrite_rule(
        '^discount-codes/pdf/([0-9]+)/?$',
        'index.php?discount_code_pdf=$matches[1]',
        'top'
    );
}
add_action('init', 'discount_codes_add_endpoint');

// Registrace query var
function discount_codes_register_query_var($vars) {
    $vars[] = 'discount_code_pdf';
    return $vars;
}
add_filter('query_vars', 'discount_codes_register_query_var');

// Zpracování požadavku na PDF
function discount_codes_template_redirect() {
    $pdf_id = get_query_var('discount_code_pdf');
    if ($pdf_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'discount_codes';
        $code = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d",
            $pdf_id
        ));

        if ($code) {
            $pdf_content = generate_discount_pdf($code->discount, $code->code, $code->valid_to);
            
            // Nastavení hlaviček pro stažení PDF
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="slevovy_kupon_' . $code->code . '.pdf"');
            header('Content-Length: ' . strlen($pdf_content));
            header('Cache-Control: no-cache, no-store, must-revalidate');
            header('Pragma: no-cache');
            header('Expires: 0');
            
            echo $pdf_content;
            exit;
        }
    }
}
add_action('template_redirect', 'discount_codes_template_redirect');

// Stránka s objednávkami
function discount_codes_orders_page() {
    global $wpdb;
    $orders_table = $wpdb->prefix . 'discount_codes_orders';
    
    // Získání všech objednávek
    $orders = $wpdb->get_results("SELECT * FROM $orders_table ORDER BY created_at DESC");
    
    // Načtení šablony
    include plugin_dir_path(__FILE__) . 'templates/admin-orders.php';
}

// Shortcode pro objednávkový formulář
function discount_codes_order_form_shortcode() {
    ob_start();
    
    // Zpracování formuláře
    if (isset($_POST['submit_order'])) {
        if (!wp_verify_nonce($_POST['order_nonce'], 'submit_order')) {
            wp_die('Neplatný požadavek');
        }

        $name = sanitize_text_field($_POST['name']);
        $email = sanitize_email($_POST['email']);
        $phone = sanitize_text_field($_POST['phone']);
        $course = sanitize_text_field($_POST['course']);
        $number_of_persons = intval($_POST['number_of_persons']);
        $discount_code = sanitize_text_field($_POST['discount_code']);

        if (empty($name) || empty($email) || empty($phone) || empty($course) || $number_of_persons < 1) {
            $error = 'Prosím vyplňte všechna povinná pole';
        } else {
            global $wpdb;
            $table_name = $wpdb->prefix . 'discount_codes_orders';

            // Kontrola slevového kódu
            $code_table = $wpdb->prefix . 'discount_codes';
            $code = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $code_table WHERE code = %s AND status = 'active'",
                $discount_code
            ));

            if (!empty($discount_code) && !$code) {
                $error = 'Neplatný slevový kód';
            } else {
                // Uložení objednávky
                $wpdb->insert(
                    $table_name,
                    array(
                        'name' => $name,
                        'email' => $email,
                        'phone' => $phone,
                        'course' => $course,
                        'number_of_persons' => $number_of_persons,
                        'discount_code' => $discount_code,
                        'order_date' => current_time('mysql')
                    ),
                    array('%s', '%s', '%s', '%s', '%d', '%s', '%s')
                );

                // Aktualizace stavu slevového kódu
                if ($code) {
                    $wpdb->update(
                        $code_table,
                        array('status' => 'redeemed'),
                        array('id' => $code->id)
                    );
                }

                $success = 'Objednávka byla úspěšně odeslána';
            }
        }
    }
    
    // Načtení šablony
    include plugin_dir_path(__FILE__) . 'templates/order-form.php';
    
    return ob_get_clean();
}
add_shortcode('discount_codes_order_form', 'discount_codes_order_form_shortcode'); 