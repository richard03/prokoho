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

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    dbDelta($sql_settings);
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
    ?>
    <div class="wrap">
        <h1>Nastavení slevových kupónů</h1>
        
        <?php settings_errors('discount_codes_settings'); ?>
        
        <form method="post" enctype="multipart/form-data">
            <?php wp_nonce_field('save_discount_codes_settings'); ?>
            
            <h2>Pozadí PDF</h2>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="background_image">Obrázek pozadí</label></th>
                    <td>
                        <input type="file" name="background_image" id="background_image" accept="image/jpeg,image/png">
                        <p class="description">Podporované formáty: JPG, PNG</p>
                        <?php if (!empty($settings['background_image'])) : ?>
                            <p class="description">Aktuální obrázek:</p>
                            <img src="<?php echo esc_url($settings['background_image']); ?>" style="max-width: 300px; margin-top: 10px;">
                        <?php endif; ?>
                    </td>
                </tr>
            </table>
            
            <hr>
            <h3>Sleva - nastavení textu</h3>
            <table>
                <tr>
                    <th scope="col"><label for="discount_y">Y pozice (%)</label></th>
                    <th scope="col"><label for="discount_align">Zarovnání</label></th>
                    <th scope="col"><label for="discount_color">Barva</label></th>
                </tr>
                <tr>
                    <td><input type="number" name="discount_y" id="discount_y" value="<?php echo esc_attr($settings['discount_y']); ?>" min="0" max="100"></td>
                    <td>
                        <select name="discount_align" id="discount_align">
                            <option value="L" <?php selected($settings['discount_align'], 'L'); ?>>Vlevo</option>
                            <option value="C" <?php selected($settings['discount_align'], 'C'); ?>>Na střed</option>
                            <option value="R" <?php selected($settings['discount_align'], 'R'); ?>>Vpravo</option>
                        </select>
                    </td>
                    <td><input type="color" name="discount_color" id="discount_color" value="<?php echo esc_attr($settings['discount_color']); ?>"></td>
                </tr>
            </table>

            <hr>
            <h3>Kód - nastavení textu</h3>
            <table>    
                <tr>
                    <th scope="col"><label for="code_y">Y pozice (%)</label></th>
                    <th scope="col"><label for="code_align">Zarovnání</label></th>
                    <th scope="col"><label for="code_color">Barva</label></th>
                </tr>
                <tr>
                    <td><input type="number" name="code_y" id="code_y" value="<?php echo esc_attr($settings['code_y']); ?>" min="0" max="100"></td>    
                    <td>
                        <select name="code_align" id="code_align">
                            <option value="L" <?php selected($settings['code_align'], 'L'); ?>>Vlevo</option>
                            <option value="C" <?php selected($settings['code_align'], 'C'); ?>>Na střed</option>
                            <option value="R" <?php selected($settings['code_align'], 'R'); ?>>Vpravo</option>
                        </select>
                    </td>
                    <td><input type="color" name="code_color" id="code_color" value="<?php echo esc_attr($settings['code_color']); ?>"></td>
                </tr>
            </table>

            <hr>
            <h3>Datum platnosti - nastavení textu</h3>
            <table>    
                <tr>
                    <th scope="col"><label for="date_y">Y pozice (%)</label></th>
                    <th scope="col"><label for="date_align">Zarovnání</label></th>
                    <th scope="col"><label for="date_color">Barva</label></th>
                </tr>
                <tr>
                    <td><input type="number" name="date_y" id="date_y" value="<?php echo esc_attr($settings['date_y']); ?>" min="0" max="100"></td>
                    <td>
                        <select name="date_align" id="date_align">
                            <option value="L" <?php selected($settings['date_align'], 'L'); ?>>Vlevo</option>
                            <option value="C" <?php selected($settings['date_align'], 'C'); ?>>Na střed</option>
                            <option value="R" <?php selected($settings['date_align'], 'R'); ?>>Vpravo</option>
                        </select>
                    </td>
                    <td><input type="color" name="date_color" id="date_color" value="<?php echo esc_attr($settings['date_color']); ?>"></td>
                </tr>
            </table>

            <hr>
            <p class="submit">
                <input type="submit" name="save_settings" class="button button-primary" value="Uložit nastavení">
            </p>
        </form>
    </div>
    <?php
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