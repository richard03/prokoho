<?php
/**
 * Plugin Name: Small Business Suite
 * Description: Plugin pro správu slevových kódů a objednávek
 * Version: 1.0
 * Author: Prokoho.cz
 */

// Zabránění přímému přístupu
if (!defined('ABSPATH')) {
    exit;
}

include plugin_dir_path(__FILE__) . 'functions/orders.php';
include plugin_dir_path(__FILE__) . 'functions/vouchers.php';
include plugin_dir_path(__FILE__) . 'functions/pdf-settings.php';


// Vytvoření tabulek při aktivaci pluginu
function small_business_suite_activate() {
    global $wpdb;
    orders_db_init();
    vouchers_db_init();
    pdf_settings_db_init();
}
register_activation_hook(__FILE__, 'small_business_suite_activate');



// Přidání položek do menu
function small_business_suite_menu() {
    add_menu_page(
        'Small Business Suite',
        'Small Business Suite',
        'manage_options',
        'small-business-suite',
        'small_business_suite_page',
        'dashicons-tickets-alt',
        30
    );

    add_submenu_page(
        'small-business-suite',
        'Objednávky',
        'Objednávky',
        'manage_options',
        'small-business-suite-orders',
        'small_business_suite_orders_page'
    );

    add_submenu_page(
        'small-business-suite',
        'Slevové poukazy',
        'Slevové poukazy',
        'manage_options',
        'small-business-suite-vouchers',
        'small_business_suite_vouchers_page'
    );
    
    add_submenu_page(
        'small-business-suite',
        'Nastavení PDF',
        'Nastavení PDF',
        'manage_options',
        'small-business-suite-pdf-settings',
        'small_business_suite_pdf_settings_page'
    );

}
add_action('admin_menu', 'small_business_suite_menu');



// Administrace pluginu - hlavní stránka
function small_business_suite_page() {
    // Zpracování resetu dat
    if (isset($_POST['reset_data']) && check_admin_referer('reset_plugin_data')) {
        global $wpdb;
        
        // Smazání tabulek
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}small_business_suite_vouchers");
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}small_business_suite_orders");
        $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}small_business_suite_settings");
        
        // Vytvoření nových tabulek
        small_business_suite_activate();
        
        // Přidání zprávy o úspěchu
        add_settings_error(
            'small_business_suite_messages',
            'reset_success',
            'Data byla úspěšně smazána a tabulky byly znovu vytvořeny.',
            'updated'
        );
    }
    
    // Zobrazení chybových zpráv
    settings_errors('small_business_suite_messages');
    
    // Načtení šablony
    include plugin_dir_path(__FILE__) . 'templates/admin.php';
}

function small_business_suite_orders_page() {
    $orders = get_orders();
    include plugin_dir_path(__FILE__) . 'templates/admin-orders.php';
}

// Administrace pluginu - stránka nastavení
function small_business_suite_pdf_settings_page() {
    // Zpracování formuláře
    if (isset($_POST['save_settings'])) {
        if (check_admin_referer('small_business_suite_pdf_settings')) {
            save_pdf_settings();
        } else {
            add_settings_error('pdf_settings', 'settings_error', 'Došlo k chybě při ukládání nastavení.', 'error');
        }
    }

    $settings = get_pdf_settings();
    include plugin_dir_path(__FILE__) . 'templates/admin-pdf-settings.php';
}

// Administrace pluginu - slevové kupóny
function small_business_suite_vouchers_page() {
    
    // Zpracování formuláře pro přidání nového slevového kupónu
    if (isset($_POST['add_voucher']) && check_admin_referer('add_voucher')) {
        $discount = sanitize_text_field($_POST['discount']);
        $code = generate_discount_code();
        $months = intval($_POST['valid_months']);
        $valid_to = date('Y-m-d', strtotime("+$months months"));
        $status = 'active';

        global $wpdb;
        $table_name = $wpdb->prefix . 'small_business_suite_vouchers';

        $result = $wpdb->insert(
            $table_name,
            array(
                'discount' => $discount,
                'code' => $code,
                'valid_to' => $valid_to,
                'status' => $status
            ),
            array('%s', '%s', '%s', '%s')
        );
        if ($result) {
            add_settings_error(
                'small_business_suite_messages',
                'voucher_added',
                'Kupón byl úspěšně přidán.',
                'updated'
            );
        } else {
            add_settings_error(
                'small_business_suite_messages',
                'voucher_error',
                'Nepodařilo se přidat kupón. Zkuste to prosím znovu.',
                'error'
            );
        }
    }

    $vouchers = get_vouchers();
    include plugin_dir_path(__FILE__) . 'templates/admin-vouchers.php';
}





// Zpracování požadavku na PDF
function pdf_template_redirect() {
    $pdf_id = get_query_var('discount_voucher_pdf');
    error_log('PDF endpoint: Checking template redirect');
    $pdf_id = get_query_var('discount_voucher_pdf');
    if ($pdf_id) {
        error_log('PDF endpoint: Found PDF ID: ' . $pdf_id);
        global $wpdb;
        $table_name = $wpdb->prefix . 'small_business_suite_vouchers';
        $voucher = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d",
            $pdf_id
        ));
 
        if ($voucher) {
            error_log('PDF endpoint: Found voucher: ' . $voucher->code);
            $pdf_content = generate_voucher_pdf($voucher->discount, $voucher->code, $voucher->valid_to);
            // Nastavení hlaviček pro stažení PDF
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="slevovy_kupon_' . $voucher->code . '.pdf"');
            header('Content-Length: ' . strlen($pdf_content));
            header('Cache-Control: no-cache, no-store, must-revalidate');
            header('Pragma: no-cache');
            header('Expires: 0');
            
            echo $pdf_content;
            exit;
        } else {
            error_log('PDF endpoint: Voucher not found for ID: ' . $pdf_id);
        }
    }
}



add_action('init', 'pdf_add_endpoint');
add_filter('query_vars', 'pdf_register_query_var');
add_action('template_redirect', 'pdf_template_redirect');








// Shortcode pro objednávkový formulář
function small_business_suite_order_form_shortcode() {
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
        $voucher = sanitize_text_field($_POST['voucher']);

        if (empty($name) || empty($email) || empty($phone) || empty($course) || $number_of_persons < 1) {
            $error = 'Prosím vyplňte všechna povinná pole';
        } else {
            global $wpdb;
            $table_name = $wpdb->prefix . 'small_business_suite_orders';

            // Kontrola slevového kódu
            $voucher_table = $wpdb->prefix . 'small_business_suite_vouchers';
            $voucher_data = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $voucher_table WHERE voucher = %s AND status = 'active'",
                $voucher
            ));

            if (!empty($voucher) && !$voucher_data) {
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
                        'voucher' => $voucher,
                        'order_date' => current_time('mysql')
                    ),
                    array('%s', '%s', '%s', '%s', '%d', '%s', '%s')
                );

                // Aktualizace stavu slevového kódu
                if ($voucher_data) {
                    $wpdb->update(
                        $voucher_table,
                        array('status' => 'redeemed'),
                        array('id' => $voucher_data->id)
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
add_shortcode('small_business_suite_order_form', 'small_business_suite_order_form_shortcode');

