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
