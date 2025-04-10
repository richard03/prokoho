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

// Generování PDF
function generate_discount_pdf($discount, $code, $valid_to) {
    require_once(ABSPATH . 'wp-content/plugins/discount-codes/fpdf/fpdf.php');

    // Vytvoření nového PDF dokumentu
    $pdf = new FPDF();
    $pdf->AddPage();
    
    // Nastavení barev
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFillColor(255, 255, 255);
    
    // Nastavení fontu Barlow
    $pdf->AddFont('Barlow', '', 'Barlow-Regular.php');
    $pdf->AddFont('Barlow', 'B', 'Barlow-Bold.php');
    
    // Sleva
    $pdf->SetFont('Barlow', 'B', 36);
    $discount_cz = mb_convert_encoding($discount . ' CZK', 'windows-1252', 'UTF-8');
    $pdf->Cell(0, 30, $discount_cz, 0, 1, 'C');
    $pdf->Ln(10);
    
    // Kód
    $pdf->SetFont('Barlow', 'B', 20);
    $pdf->Cell(0, 20, $code, 0, 1, 'C');
    $pdf->Ln(10);
    
    // Platnost
    $pdf->SetFont('Barlow', '', 14);
    $pdf->Cell(0, 10, date('d. m. Y', strtotime($valid_to)), 0, 1, 'C');
    
    
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
                    <th scope="col"> </th>
                </tr>
                <tr>
                    <td><input type="text" value="500" name="discount" id="discount" class="regular-text" required></td>
                    <td>
                        <select name="valid_months" id="valid_months" required>
                            <option value="1">1 měsíc</option>
                            <option value="3">3 měsíce</option>
                            <option value="6">6 měsíců</option>
                            <option value="12" selected>12 měsíců</option>
                        </select>
                    </td>
                    <td class="submit">
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
                    <th>Akce</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($codes as $code) : ?>
                    <tr>
                        <td><?php echo esc_html($code->id); ?></td>
                        <td><?php echo esc_html($code->discount); ?></td>
                        <td><?php echo esc_html($code->code); ?></td>
                        <td><?php echo date('d. m. Y', strtotime($code->valid_to)); ?></td>
                        <td><?php echo esc_html($code->status); ?></td>
                        <td>
                            <a href="<?php echo esc_url(home_url('/discount-codes/pdf/' . $code->id . '/')); ?>" 
                               class="button button-secondary" target="_blank">
                                Stáhnout PDF
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
} 