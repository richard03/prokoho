<?php
/**
 * The template for displaying single posts
 *
 * @package Prokoho_CZ
 */

?>

<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

	<?php 
	$detail_image_id = get_post_meta(get_the_ID(), 'detail_image_id', true);
	if ($detail_image_id) :
		$detail_image = wp_get_attachment_image($detail_image_id, 'full', false, array('class' => 'detail-image'));
		if ($detail_image) :
	?>
		<div class="custom-article-image">
			<?php echo $detail_image; ?>
		</div>
	<?php 
		endif;
	endif;
	?>

	<header class="entry-header">

		<?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>

		<?php chronus_entry_meta(); ?>

	</header><!-- .entry-header -->

	<div class="entry-content clearfix">

		<?php the_content(); ?>


		<h2 class="wp-block-heading"><strong>Cena pro jednu osobu: 2 800 Kč</strong></h2>

		<a class="custom-btn" href="/objednavka/?course=<?php echo urlencode(get_the_title()); ?>">Objednat</a>


		<?php wp_link_pages( array(
			'before' => '<div class="page-links">' . esc_html__( 'Pages:', 'chronus' ),
			'after'  => '</div>',
		) ); ?>

	</div><!-- .entry-content -->

	<footer class="entry-footer">

			

		<?php chronus_entry_tags(); ?>
		<?php do_action( 'chronus_author_bio' ); ?>

	</footer><!-- .entry-footer -->

</article> 