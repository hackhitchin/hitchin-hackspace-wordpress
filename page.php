<?php get_header(); ?>
<div id="main_content">
	<!--  (page.php line 3)-->
	<?php if (have_posts()) : while (have_posts()) : the_post(); ?>
	<div class="page" id="post-<?php the_ID(); ?>">
		<?php the_content('<p class="serif">Read the rest of this page &raquo;</p>'); ?>
		<?php wp_link_pages(array('before' => '<p><strong>Pages:</strong> ', 'after' => '</p>', 'next_or_number' => 'number')); ?>
	</div>
	<?php endwhile; endif; ?>
	<!--  (page.php line 9)-->
</div>
<?php get_sidebar(); ?>
<?php get_footer(); ?>