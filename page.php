<?php get_header(); ?>

<article>
	<div class="inner">
		<?php while (have_posts()) {
			the_post(); ?>
			<div class="page" id="post-<?php the_ID(); ?>">
				<?php the_content('<p class="serif">Read the rest of this page &raquo;</p>'); ?>
				<?php wp_link_pages(array('before' => '<p><strong>Pages:</strong> ', 'after' => '</p>', 'next_or_number' => 'number')); ?>
			</div>
		<?php } ?>
	</div>
</article>

<?php get_sidebar(); ?>
<?php get_footer(); ?>