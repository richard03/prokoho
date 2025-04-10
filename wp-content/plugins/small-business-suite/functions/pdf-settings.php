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
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        setting_key varchar(50) NOT NULL,
        setting_value text NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql_pdf_settings);
}


// Administrace pluginu - stránka nastavení
function get_pdf_settings() {
    global $wpdb;
    $settings_table = $wpdb->prefix . 'small_business_suite_pdf_settings';
    
    // Zpracování formuláře
    if (isset($_POST['save_settings']) && check_admin_referer('save_pdf_settings')) {
        // Uložení pozadí
        if (isset($_FILES['background_image']) && $_FILES['background_image']['error'] == 0) {
            $upload_dir = wp_upload_dir();
            $target_dir = $upload_dir['basedir'] . '/small-business-suite/';
            
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
                            'setting_value' => $upload_dir['baseurl'] . '/small-business-suite/' . $file_name
                        ),
                        array('%s', '%s')
                    );
                }
            } else {
                add_settings_error(
                    'small_business_suite_settings',
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
    
    return $settings;
}

