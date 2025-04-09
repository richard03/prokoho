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