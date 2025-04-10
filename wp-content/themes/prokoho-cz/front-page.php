<?php
/**
 * The template for displaying the static homepage
 *
 * @package Prokoho_CZ
 */

get_header(); ?>

	<section id="primary" class="content-area">
		<main id="main" class="site-main" role="main">

		<?php
		if ( have_posts() ) :

			while ( have_posts() ) : the_post();

				?>
				<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
					<div class="entry-content clearfix">
						<?php the_content(); ?>
					</div>
				</article>
				<?php

			endwhile;

		endif; ?>

		</main><!-- #main -->
	</section><!-- #primary -->

	<?php get_sidebar(); ?>

<?php get_footer(); ?> 