<?php
/**
 * Template for the main admin page
 */
?>
<div class="wrap">
    <h1>Small Business Suite</h1>

    <div class="card">
        <h2>Reset dat</h2>
        <p>Toto tlačítko smaže všechna data pluginu a vytvoří nové prázdné tabulky.</p>
        <form method="post" action="">
            <?php wp_nonce_field('reset_plugin_data'); ?>
            <input type="submit" name="reset_data" class="button button-secondary" value="Smazat data" 
                   onclick="return confirm('Opravdu chcete smazat všechna data? Tato akce je nevratná!');">
        </form>
    </div>
</div>

<style>
.wrap {
    max-width: 1200px;
    margin: 20px auto;
}

.card {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
    margin-top: 20px;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

.card h2 {
    margin-top: 0;
    padding-bottom: 12px;
    border-bottom: 1px solid #eee;
}
</style>