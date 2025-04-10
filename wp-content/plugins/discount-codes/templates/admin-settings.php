<?php
/**
 * Template for the settings page
 */
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