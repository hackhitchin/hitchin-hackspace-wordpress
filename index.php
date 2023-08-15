<?php get_header(); ?>
<article>
	<div class="inner">
		<?php if (have_posts()) { ?>
			<?php while (have_posts()) {
				the_post(); ?>
				<div class="post" id="post-<?php the_ID(); ?>">
					<div class="post_header">
						<h2><a href="<?php the_permalink() ?>" rel="bookmark" title="Permanent Link to <?php the_title_attribute(); ?>"><?php the_title(); ?></a></h2>
						<p><?php the_time('jS M Y') ?> <!-- by <?php the_author() ?> --></p>
					</div>
					<?php the_content('Read the rest of this entry &raquo;'); ?>
				</div>
			<?php } ?>

			<ul class="prevnext">
				<li class="next"><?php previous_posts_link('Newer Entries') ?></li>
				<li class="prev"><?php next_posts_link('Older Entries') ?></li>
			</ul>

		<?php } else { ?>

			<h2>Not Found</h2>
			<?php include (TEMPLATEPATH . '/searchform.php'); ?>

		<?php } ?>
	</div>
</article>
<?php get_sidebar(); ?>

<?php get_footer(); ?>
