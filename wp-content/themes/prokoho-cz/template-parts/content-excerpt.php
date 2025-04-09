<?php
/**
 * The template for displaying posts in the loop with custom box format
 *
 * @package Prokoho_CZ
 */

?>

<div class="custom-box custom-box-h25 custom-box-first">
    <h3 class="custom-box-title">
        <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
    </h3>
    <div class="custom-box-content">
        <?php the_excerpt(); ?>
    </div>
    <div class="custom-box-footer">
        <a href="<?php the_permalink(); ?>">Více o kurzu…</a>
    </div>
</div> 