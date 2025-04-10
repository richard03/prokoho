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


