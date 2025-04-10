<?php
/*This file is part of prokoho-cz, chronus child theme.

All functions of this file will be loaded before of parent theme functions.
Learn more at https://codex.wordpress.org/Child_Themes.

Note: this function loads the parent stylesheet before, then child theme stylesheet
(leave it in place unless you know what you are doing.)
*/

function prokoho_cz_enqueue_child_styles() {
$parent_style = 'parent-style'; 
	wp_enqueue_style($parent_style, get_template_directory_uri() . '/style.css' );
	wp_enqueue_style( 
		'child-style', 
		get_stylesheet_directory_uri() . '/style.css',
		array( $parent_style ),
		wp_get_theme()->get('Version') );
	}
add_action( 'wp_enqueue_scripts', 'prokoho_cz_enqueue_child_styles' );

/*Write here your own functions */

/**
 * Register widget areas and custom widgets.
 *
 * @link http://codex.wordpress.org/Function_Reference/register_sidebar
 */
function custom_widgets_init() {
	register_sidebar( array(
		'name' => 'Header Widget',
		'id' => 'header-widget',
		'before_widget' => '<div class="header-widget">',
		'after_widget' => '</div>',
		'before_title' => '<h2 class="header-widget-title">',
		'after_title' => '</h2>',
	) );

	register_sidebar( array(
		'name' => 'Footer Widget',
		'id' => 'footer-widget',
		'before_widget' => '<div class="footer-widget">',
		'after_widget' => '</div>'
	) );
}
add_action( 'widgets_init', 'custom_widgets_init' );

/**
 * Add custom meta box for detail image
 */
function add_detail_image_meta_box() {
    add_meta_box(
        'detail_image_meta_box',
        'Detailní obrázek',
        'render_detail_image_meta_box',
        'post',
        'side',
        'high'
    );
}
add_action('add_meta_boxes', 'add_detail_image_meta_box');

function render_detail_image_meta_box($post) {
    $detail_image_id = get_post_meta($post->ID, 'detail_image_id', true);
    $detail_image_url = wp_get_attachment_url($detail_image_id);
    ?>
    <div class="detail-image-container">
        <input type="hidden" name="detail_image_id" id="detail_image_id" value="<?php echo esc_attr($detail_image_id); ?>" />
        <div id="detail_image_preview" style="margin-bottom: 10px;">
            <?php if ($detail_image_url) : ?>
                <img src="<?php echo esc_url($detail_image_url); ?>" style="max-width: 100%; height: auto;" />
            <?php endif; ?>
        </div>
        <input type="button" class="button" id="upload_detail_image_button" value="Vybrat obrázek" />
        <input type="button" class="button" id="remove_detail_image_button" value="Odstranit obrázek" <?php if (!$detail_image_id) echo 'style="display:none;"'; ?> />
    </div>
    <script>
    jQuery(document).ready(function($) {
        var mediaUploader;
        
        $('#upload_detail_image_button').click(function(e) {
            e.preventDefault();
            
            if (mediaUploader) {
                mediaUploader.open();
                return;
            }
            
            mediaUploader = wp.media({
                title: 'Vyberte detailní obrázek',
                button: {
                    text: 'Použít tento obrázek'
                },
                multiple: false
            });
            
            mediaUploader.on('select', function() {
                var attachment = mediaUploader.state().get('selection').first().toJSON();
                $('#detail_image_id').val(attachment.id);
                $('#detail_image_preview').html('<img src="' + attachment.url + '" style="max-width: 100%; height: auto;" />');
                $('#remove_detail_image_button').show();
            });
            
            mediaUploader.open();
        });
        
        $('#remove_detail_image_button').click(function(e) {
            e.preventDefault();
            $('#detail_image_id').val('');
            $('#detail_image_preview').html('');
            $(this).hide();
        });
    });
    </script>
    <?php
}

function save_detail_image_meta($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;
    
    if (isset($_POST['detail_image_id'])) {
        update_post_meta($post_id, 'detail_image_id', sanitize_text_field($_POST['detail_image_id']));
    }
}
add_action('save_post', 'save_detail_image_meta');

/**
 * Shortcode for displaying posts
 * Usage: [display_posts category="category-slug" posts_per_page="5"]
 */
function display_posts_shortcode($atts) {
    // Default attributes
    $atts = shortcode_atts(array(
        'category' => '',
        'posts_per_page' => 5,
        'orderby' => 'date',
        'order' => 'DESC'
    ), $atts);

    // Query arguments
    $args = array(
        'post_type' => 'post',
        'posts_per_page' => $atts['posts_per_page'],
        'orderby' => $atts['orderby'],
        'order' => $atts['order']
    );

    // Add category if specified
    if (!empty($atts['category'])) {
        $args['category_name'] = $atts['category'];
    }

    // Start output buffering
    ob_start();

    // Create new query
    $query = new WP_Query($args);

    if ($query->have_posts()) :
        echo '<div class="custom-posts-container">';
        while ($query->have_posts()) : $query->the_post();
            ?>
            <div class="custom-box custom-box-h25 custom-box-first">
                <h3 class="custom-box-title" style="height: 34px;">
                    <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                </h3>
                <div class="custom-box-content" style="height: 323px;">
                    <?php if (has_post_thumbnail()) : ?>
                        <div class="post-thumbnail" style="margin-bottom: 1rem;">
                            <a href="<?php the_permalink(); ?>">
                                <?php the_post_thumbnail('medium', array('style' => 'max-width: 100%; height: auto;')); ?>
                            </a>
                        </div>
                    <?php endif; ?>
                    <?php the_excerpt(); ?>
                </div>
                <div class="custom-box-footer" style="height: 34px;">
                    <a href="<?php the_permalink(); ?>">Více o kurzu…</a>
                </div>
            </div>
            <?php
        endwhile;
        echo '</div>';
    endif;

    // Reset post data
    wp_reset_postdata();

    // Return the buffered content
    return ob_get_clean();
}
add_shortcode('display_posts', 'display_posts_shortcode');