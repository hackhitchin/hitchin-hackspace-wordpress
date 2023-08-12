<?php get_header(); ?>
<div id="main_content">
<!--  (single.php line 3)-->
<?php //the_content(); ?>
	
    <!--  (single.php line 5)-->
	<?php if (have_posts()) : while (have_posts()) : the_post(); ?>
	
    <!--  (single.php line 9)-->
		<div class="post" id="post-<?php the_ID(); ?>">
                <!--  (single.php line 11)-->
				<div class="post_header">
					<h2><a href="<?php the_permalink() ?>" rel="bookmark" title="Permanent Link to <?php the_title_attribute(); ?>"><?php the_title(); ?></a></h2>
					<p><?php the_time('jS M Y') ?> <!-- by <?php the_author() ?> --></p>
				</div>

            <!--  (single.php line 16)-->
			<?php the_content('<p>Read the rest of this entry &raquo;</p>'); ?>

			<?php wp_link_pages(array('before' => '<p><strong>Pages:</strong> ', 'after' => '</p>', 'next_or_number' => 'number')); ?>
            <!--  (single.php line 20)-->
			<ul class="post_meta">
				<li>Posted to <?php the_category(', ') ?> on <?php the_time('l, F jS, Y') ?> at <?php the_time() ?></li>
				<?php the_tags( '<li>Related Catagories: ', ', ', '</li>'); ?>
				
                <!--  (single.php line 25)-->
				<li>You can follow any responses to this entry through the <?php post_comments_feed_link('RSS 2.0'); ?> feed</li>

				<?php if (('open' == $post-> comment_status) && ('open' == $post->ping_status)) {
					// Both Comments and Pings are open ?>
					You can <a href="#respond">leave a response</a>, or <a href="<?php trackback_url(); ?>" rel="trackback">trackback</a> from your own site.

				<?php } elseif (!('open' == $post-> comment_status) && ('open' == $post->ping_status)) {
					// Only Pings are Open ?>
					Responses are currently closed, but you can <a href="<?php trackback_url(); ?> " rel="trackback">trackback</a> from your own site.

				<?php } elseif (('open' == $post-> comment_status) && !('open' == $post->ping_status)) {
					// Comments are open, Pings are not ?>
					You can skip to the end and leave a response. Pinging is currently not allowed.

				<?php } elseif (!('open' == $post-> comment_status) && !('open' == $post->ping_status)) {
					// Neither Comments, nor Pings are open ?>
					Both comments and pings are currently closed.

				<?php } edit_post_link('Edit this entry','','.'); ?>
			</ul>

		</div>
    <!--  (single.php line 48)-->
	<?php comments_template(); ?>

		<ul class="prevnext">
			<li class="prev"><?php previous_post('%', '', 'yes') ?></li>
			<li class="next"><?php next_post('%', '', 'yes') ?></li>
		</ul>

	<?php endwhile; else: ?>

		<p>Sorry, no posts matched your criteria.</p>

<?php endif; ?>

</div>
<?php get_sidebar(); ?>
<?php get_footer(); ?>
