<?php
/**
 * Template for the admin page
 */
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