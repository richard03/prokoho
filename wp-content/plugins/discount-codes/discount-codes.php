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

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
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

    // Zpracování změny stavu
    if (isset($_POST['change_status']) && check_admin_referer('change_status')) {
        $id = intval($_POST['id']);
        $status = sanitize_text_field($_POST['status']);
        
        $wpdb->update(
            $table_name,
            array('status' => $status),
            array('id' => $id),
            array('%s'),
            array('%d')
        );
    }

    // Získání všech kódů
    $codes = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC");
    ?>
    <div class="wrap">
        <h1>Slevové kupóny</h1>

        <h2>Přidat nový kupón</h2>
        <form method="post" action="">
            <?php wp_nonce_field('add_discount_code'); ?>
            <table>
                <tr>
                    <th scope="col" class="textleft"><label for="discount">Sleva (Kč)</label></th>
                    <th scope="col" class="textleft"><label for="valid_months">Platnost</label></th>
                    <th>

                </tr>
                <tr>
                    <td>
                        <input type="text" name="discount" value="500" id="discount" class="regular-text" required>
                    </td>
                    <td>
                        <select name="valid_months" id="valid_months" required>
                            <option value="1">1 měsíc</option>
                            <option value="3">3 měsíce</option>
                            <option value="6">6 měsíců</option>
                            <option value="12" selected>12 měsíců</option>
                        </select>
                    </td>
                    <td>
                        <input type="submit" name="add_discount_code" class="button button-primary" value="Přidat kupón">
                    </td>
                </tr>
            </table>
        </form>

        <h2>Seznam kupónů</h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Sleva</th>
                    <th>Kód</th>
                    <th>Platný do</th>
                    <th>Stav</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($codes as $code) : ?>
                    <tr>
                        <td><?php echo esc_html($code->id); ?></td>
                        <td><?php echo esc_html($code->discount); ?></td>
                        <td><?php echo esc_html($code->code); ?></td>
                        <td><?php echo esc_html($code->valid_to); ?></td>
                        <td><?php echo esc_html($code->status); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
} 