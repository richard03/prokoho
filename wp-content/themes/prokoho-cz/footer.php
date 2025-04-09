<?php
/**
 * The template for displaying the footer.
 *
 * Contains all content after the main content area and sidebar
 *
 * @package Chronus
 */

?>

	</div><!-- #content -->

	<?php do_action( 'chronus_before_footer' ); ?>

	<div id="footer" class="footer-wrap">

		<?php if ( is_active_sidebar( 'header-widget' ) ) : ?>
			<div id="footer-widget-area" class="widget-area">
				<?php dynamic_sidebar( 'footer-widget' ); ?>
			</div>
		<?php endif; ?>
		
		<footer id="colophon" class="site-footer container clearfix" role="contentinfo">
			<div id="footer-text" class="site-info">
				<?php do_action( 'chronus_footer_text' ); ?>
				<?php chronus_credit_link(); ?>
			</div><!-- .site-info -->

		</footer><!-- #colophon -->

	</div>

</div><!-- #page -->

<?php wp_footer(); ?>

</body>
</html>
