<?php
/**
 * Template for the orders page
 */
?>
<div class="wrap">
    <h1>Objednávky</h1>
    
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Jméno</th>
                <th>Email</th>
                <th>Telefon</th>
                <th>Kurz</th>
                <th>Počet osob</th>
                <th>Slevový kód</th>
                <th>Datum objednávky</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($orders as $order) : ?>
                <tr>
                    <td><?php echo esc_html($order->id); ?></td>
                    <td><?php echo esc_html($order->name); ?></td>
                    <td><?php echo esc_html($order->email); ?></td>
                    <td><?php echo esc_html($order->phone); ?></td>
                    <td><?php echo esc_html($order->course); ?></td>
                    <td><?php echo esc_html($order->number_of_persons); ?></td>
                    <td><?php echo esc_html($order->discount_code); ?></td>
                    <td><?php echo esc_html($order->created_at); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div> 