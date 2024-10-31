<?php
/*
Template Name: VideoWhisper Full
*/
?>
<?php get_header(); ?>
<style type="text/css">
.post-title
{

}
.post-content-videowhisper
{

}
.featured-image{ display:none; }

</style>

			<?php
			if ( have_posts() ) :
				while ( have_posts() ) :
					the_post();
					?>

				<div class="post">

					<div class="post-header">

						<h1 class="post-title"><?php the_title(); ?></h1>

					</div> <!-- /post-header -->

					<div class="post-content-videowhisper">

						<?php the_content(); ?>

						<?php if ( current_user_can( 'manage_options' ) ) : ?>

						<?php endif; ?>

					</div> <!-- /post-content -->

					<?php comments_template( '', true ); ?>

				</div> <!-- /post -->

			<?php endwhile; else : ?>

				<p><?php _e( "We couldn't find any posts that matched your query. Please try again.", 'picture-gallery' ); ?></p>

			<?php endif; ?>

			<div class="clear"></div>

<?php get_footer(); ?>
