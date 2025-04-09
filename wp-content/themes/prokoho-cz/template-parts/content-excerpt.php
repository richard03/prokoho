<?php
/**
 * The template for displaying articles in the loop with post excerpts and featured image
 *
 * @package Prokoho_CZ
 */

?>

<div class="custom-box custom-box-h25 custom-box-first">
   <div class="custom-box-content">
        <?php if (has_post_thumbnail()) : ?>
            <div class="post-thumbnail">
                <a href="<?php the_permalink(); ?>">
                    <?php the_post_thumbnail('medium', array('style' => 'max-width: 100%; height: auto;')); ?>
                </a>
            </div>
        <?php endif; ?>
        <h3 class="custom-box-title">
            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
        </h3> 
        <?php the_excerpt(); ?>
    </div>
    
    <div class="custom-box-footer">
        <a href="<?php the_permalink(); ?>">Více o kurzu…</a>
    </div>
</div> 