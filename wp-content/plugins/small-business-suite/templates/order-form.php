<?php
if (!defined('ABSPATH')) {
    exit;
}

// Zobrazení chybových zpráv
if (isset($_SESSION['sbs_order_message'])) {
    $message = $_SESSION['sbs_order_message'];
    $class = $message['type'] === 'error' ? 'error-message' : 'success-message';
    echo '<div class="' . esc_attr($class) . '">' . esc_html($message['text']) . '</div>';
    unset($_SESSION['sbs_order_message']);
}
?>

<div class="sbs-order-form">
    <form method="post" action="<?php echo esc_url(home_url('order-submit')); ?>">
        <?php wp_nonce_field('submit_order', 'order_nonce'); ?>
        
        <div class="form-group">
            <label for="name">Jméno a příjmení *</label>
            <input type="text" name="name" id="name" value="<?php echo isset($_POST['name']) ? esc_attr($_POST['name']) : ''; ?>" required>
        </div>

        <div class="form-group">
            <label for="email">E-mail *</label>
            <input type="email" name="email" id="email" value="<?php echo isset($_POST['email']) ? esc_attr($_POST['email']) : ''; ?>" required>
        </div>

        <div class="form-group">
            <label for="phone">Telefon *</label>
            <input type="tel" name="phone" id="phone" value="<?php echo isset($_POST['phone']) ? esc_attr($_POST['phone']) : ''; ?>" required>
        </div>

        <div class="form-group">
            <label for="course">Kurz *</label>
            <select name="course" id="course" required>
                <option value="">Vyberte kurz</option>
                <option value="Sebeobrana proti podvodům" <?php selected(isset($_POST['course']) && $_POST['course'] === 'Sebeobrana proti podvodům' || isset($_GET['course']) && $_GET['course'] === 'Sebeobrana proti podvodům'); ?>>Sebeobrana proti podvodům</option>
                <option value="Nastartujte své myšlení" <?php selected(isset($_POST['course']) && $_POST['course'] === 'Nastartujte své myšlení' || isset($_GET['course']) && $_GET['course'] === 'Nastartujte své myšlení'); ?>>Nastartujte své myšlení</option>
                <option value="Sebeobrana proti dezinformacím" <?php selected(isset($_POST['course']) && $_POST['course'] === 'Sebeobrana proti dezinformacím' || isset($_GET['course']) && $_GET['course'] === 'Sebeobrana proti dezinformacím'); ?>>Sebeobrana proti dezinformacím</option>
            </select>
        </div>

        <div class="form-group">
            <label for="number_of_persons">Počet osob *</label>
            <input type="number" name="number_of_persons" id="number_of_persons" min="1" value="<?php echo isset($_POST['number_of_persons']) ? intval($_POST['number_of_persons']) : 1; ?>" required>
        </div>

        <div class="form-group">
            <label for="voucher">Slevový kód</label>
            <input type="text" name="voucher" id="voucher" value="<?php echo isset($_POST['voucher']) ? esc_attr($_POST['voucher']) : ''; ?>" placeholder="Zadejte slevový kód">
        </div>

        <div class="form-group">
            <button type="submit" name="submit_order" class="button button-primary">Odeslat objednávku</button>
        </div>
    </form>
</div>

<style>
.sbs-order-form {
    max-width: 600px;
    margin: 0 auto;
    padding: 20px;
}

.sbs-order-form .form-group {
    margin-bottom: 15px;
}

.sbs-order-form label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
}

.sbs-order-form input[type="text"],
.sbs-order-form input[type="email"],
.sbs-order-form input[type="tel"],
.sbs-order-form input[type="number"] {
    width: 100%;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.sbs-order-form button {
    padding: 10px 20px;
    background-color: #0073aa;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
}

.sbs-order-form button:hover {
    background-color: #005177;
}

.error-message {
    background-color: #f8d7da;
    color: #721c24;
    padding: 10px;
    margin-bottom: 20px;
    border: 1px solid #f5c6cb;
    border-radius: 4px;
}

.success-message {
    background-color: #d4edda;
    color: #155724;
    padding: 10px;
    margin-bottom: 20px;
    border: 1px solid #c3e6cb;
    border-radius: 4px;
}
</style> 