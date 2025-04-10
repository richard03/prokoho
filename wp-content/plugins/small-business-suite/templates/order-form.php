<?php
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="discount-codes-order-form">
    <?php if (isset($error)): ?>
        <div class="error-message"><?php echo esc_html($error); ?></div>
    <?php endif; ?>

    <?php if (isset($success)): ?>
        <div class="success-message"><?php echo esc_html($success); ?></div>
    <?php endif; ?>

    <form method="post" action="">
        <?php wp_nonce_field('submit_order', 'order_nonce'); ?>

        <div class="form-group">
            <label for="name">Jméno a příjmení *</label>
            <input type="text" name="name" id="name" required>
        </div>

        <div class="form-group">
            <label for="email">E-mail *</label>
            <input type="email" name="email" id="email" required>
        </div>

        <div class="form-group">
            <label for="phone">Telefon *</label>
            <input type="tel" name="phone" id="phone" required>
        </div>

        <div class="form-group">
            <label for="course">Kurz *</label>
            <select name="course" id="course" required>
                <option value="">Vyberte kurz</option>
                <option value="Sebeobrana proti podvodům">Sebeobrana proti podvodům</option>
                <option value="Nastartujte své myšlení">Nastartujte své myšlení</option>
                <option value="Sebeobrana proti dezinformacím">Sebeobrana proti dezinformacím</option>
            </select>
        </div>

        <div class="form-group">
            <label for="number_of_persons">Počet osob *</label>
            <input type="number" name="number_of_persons" id="number_of_persons" min="1" value="1" required>
        </div>

        <div class="form-group">
            <label for="discount_code">Slevový kód (volitelné)</label>
            <input type="text" name="discount_code" id="discount_code">
        </div>

        <button type="submit" class="submit-button">Odeslat objednávku</button>
    </form>
</div>

<style>
.discount-codes-order-form {
    max-width: 600px;
    margin: 0 auto;
    padding: 20px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
}

.form-group input,
.form-group select {
    width: 100%;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.error-message {
    color: #dc3545;
    margin-bottom: 20px;
    padding: 10px;
    background-color: #f8d7da;
    border: 1px solid #f5c6cb;
    border-radius: 4px;
}

.success-message {
    color: #28a745;
    margin-bottom: 20px;
    padding: 10px;
    background-color: #d4edda;
    border: 1px solid #c3e6cb;
    border-radius: 4px;
}

.submit-button {
    background-color: #007bff;
    color: white;
    padding: 10px 20px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 16px;
}

.submit-button:hover {
    background-color: #0056b3;
}
</style> 