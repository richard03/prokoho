<?php
/**
 * The header for our theme.
 *
 * Displays all of the <head> section and everything up till <div id="content">
 *
 * @package Chronus
 */

?><!DOCTYPE html>
<html <?php language_attributes(); ?>>

<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="profile" href="http://gmpg.org/xfn/11">

	<?php wp_head(); ?>

	<link href="https://fonts.googleapis.com/css?family=Barlow&display=swap" rel="stylesheet">
	
	<link rel="stylesheet" type="text/css" href="<?=get_stylesheet_directory_uri() ?>/custom.css" media="screen"/>
	<script defer src="<?=get_stylesheet_directory_uri() ?>/custom.js"></script>
</head>

<body <?php body_class(); ?>>

	<a class="skip-link screen-reader-text" href="#content"><?php esc_html_e( 'Skip to content', 'chronus' ); ?></a>

	<?php do_action( 'chronus_header_bar' ); ?>

	<?php chronus_header_image(); ?>

	<div id="page" class="hfeed site">

		<header id="masthead" class="site-header clearfix" role="banner">

			<div class="header-main container clearfix">

				<div id="logo" class="site-branding clearfix">
					
					<?php /* chronus_site_logo(); */ ?>
					<a href="/" class="custom-logo-link" rel="home" aria-current="page">
						<img width="190" height="190" src="<?=get_stylesheet_directory_uri() ?>/site-logo.gif" class="custom-logo" alt="" decoding="async">
					</a>
					<?php chronus_site_title(); ?>
					<?php chronus_site_description(); ?>
					<?php if ( is_active_sidebar( 'header-widget' ) ) : ?>
						<div id="header-widget-area" class="header-widget widget-area" role="complementary">
							<?php dynamic_sidebar( 'header-widget' ); ?>
						</div>
					<?php endif; ?>
				</div><!-- .site-branding -->

			</div><!-- .header-main -->

			<?php get_template_part( 'template-parts/header/navigation', 'main' ); ?>

		</header><!-- #masthead -->

		<?php chronus_featured_content(); ?>

		<?php chronus_breadcrumbs(); ?>

		<div id="content" class="site-content container clearfix">
