<?php

// Zabránění přímému přístupu
if (!defined('ABSPATH')) {
    exit;
}


// Vytvoření tabulek při aktivaci pluginu
function orders_db_init() {
    global $wpdb;
    $orders_table = $wpdb->prefix . 'small_business_suite_orders';
    $charset_collate = $wpdb->get_charset_collate();

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
    dbDelta($sql_orders);    
}



// Administrace pluginu - objednávky
function get_orders() {
    global $wpdb;
    $orders_table = $wpdb->prefix . 'small_business_suite_orders';
    
    // Získání všech objednávek
    $orders = $wpdb->get_results("SELECT * FROM $orders_table ORDER BY created_at DESC");
    
    return $orders;
}

// Funkce pro odeslání potvrzovacího e-mailu
function send_order_confirmation_email($order_data) {
    $to = $order_data['email'];
    $subject = 'Potvrzení objednávky kurzu - ' . get_bloginfo('name');
    
    $message = "Dobrý den,\n\n";
    $message .= "děkujeme za Vaši objednávku kurzu " . $order_data['course'] . ".\n\n";
    $message .= "Detaily objednávky:\n";
    $message .= "Jméno: " . $order_data['name'] . "\n";
    $message .= "E-mail: " . $order_data['email'] . "\n";
    $message .= "Telefon: " . $order_data['phone'] . "\n";
    $message .= "Kurz: " . $order_data['course'] . "\n";
    $message .= "Počet osob: " . $order_data['number_of_persons'] . "\n";
    if (!empty($order_data['voucher'])) {
        $message .= "Slevový kód: " . $order_data['voucher'] . "\n";
    }
    $message .= "\nV nejbližší době Vás budeme kontaktovat s dalšími informacemi.\n\n";
    $message .= "S pozdravem,\n";
    $message .= get_bloginfo('name');

    $headers = array('Content-Type: text/plain; charset=UTF-8');
    
    return wp_mail($to, $subject, $message, $headers);
}


