<?php

// Zabránění přímému přístupu
if (!defined('ABSPATH')) {
    exit;
}


// Vytvoření tabulek při aktivaci pluginu
function pdf_settings_db_init() {
    global $wpdb;
    $pdf_settings_table = $wpdb->prefix . 'small_business_suite_pdf_settings';
    $charset_collate = $wpdb->get_charset_collate();

    $sql_pdf_settings = "CREATE TABLE IF NOT EXISTS $pdf_settings_table (
        setting_key varchar(50) NOT NULL,
        setting_value text NOT NULL,
        PRIMARY KEY  (setting_key)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql_pdf_settings);
}


// Administrace pluginu - stránka nastavení
function get_pdf_settings() {
    global $wpdb;
    $settings_table = $wpdb->prefix . 'small_business_suite_pdf_settings';
    
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
    
    return $settings;
}

// Uložení nastavení PDF
function save_pdf_settings() {
    if (!current_user_can('manage_options')) {
        wp_die('Nemáte oprávnění k této akci.');
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'small_business_suite_pdf_settings';

    // Pole s povolenými klíči nastavení
    $allowed_settings = array(
        'background_image',
        'discount_y',
        'discount_color',
        'discount_align',
        'code_y',
        'code_color',
        'code_align',
        'date_y',
        'date_color',
        'date_align'
    );

    // Zpracování nahrání obrázku
    if (!empty($_FILES['background_image']['name'])) {
        $upload = wp_handle_upload($_FILES['background_image'], array('test_form' => false));
        if (!isset($upload['error'])) {
            $wpdb->replace(
                $table_name,
                array(
                    'setting_key' => 'background_image',
                    'setting_value' => $upload['url']
                ),
                array('%s', '%s')
            );
        }
    }

    // Zpracování ostatních nastavení
    foreach ($allowed_settings as $key) {
        if (isset($_POST[$key]) && $key !== 'background_image') {
            $value = sanitize_text_field($_POST[$key]);
            $wpdb->replace(
                $table_name,
                array(
                    'setting_key' => $key,
                    'setting_value' => $value
                ),
                array('%s', '%s')
            );
        }
    }

    // Přidání zprávy o úspěchu
    add_settings_error('pdf_settings', 'settings_updated', 'Nastavení bylo úspěšně uloženo.', 'updated');
} 