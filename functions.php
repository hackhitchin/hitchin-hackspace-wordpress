<?php

add_action('wp_enqueue_scripts', function() {
   wp_enqueue_style('style', get_stylesheet_uri(), [], wp_get_theme()->Version);
});

register_sidebar([
   'before_widget' => '<li id="%1$s" class="widget %2$s">',
   'after_widget' => '</li>',
   'before_title' => '<h2 class="widgettitle">',
   'after_title' => '</h2>',
]);

register_nav_menus([
   'primary' => 'Top Navigation'
]);

// Force all menu items to be focusable (even if they're not linked anywhere, such as menu items with children)
add_filter('nav_menu_link_attributes', function($atts) {
   $atts['tabindex'] = 0;
   return $atts;
});

add_shortcode('hh_login_redirect_here', function($attrs) {
   $redirect_to = get_permalink() ?: '';

   $link = wp_login_url($redirect_to);

   return "<a class=\"login-link\" href=\"$link\">Log In</a>";
});

// Fetch automatic updates from github
add_filter('update_themes_github.com', function($update, $theme_data, $theme_stylesheet, $locales) {
   // Fetch style.css - it contains the theme version number.

   // Work out where the raw style.css file would be.
   $repoURI = $theme_data['UpdateURI'];
   $rawURI = str_replace('https://github.com', 'https://raw.githubusercontent.com', $repoURI) . '/master';
   $styleURI = "$rawURI/style.css";

   // Fetch it.
   $response = wp_remote_get($styleURI, [
      'timeout' => 10,
      'headers' => [
          'Accept' => 'application/json',
          'Cache-Control' => 'no-cache, max-age=0'
      ]
   ]);

   // Network failure? Bail.
   if (is_wp_error($response))
      return $update;

   // ... or HTTP error?
   if (wp_remote_retrieve_response_code($response) != 200)
      return $update;

   // ... or something unexpected?
   $body = wp_remote_retrieve_body($response);
   if (empty($body))
      return $update;

   // Get the relevant bits from the plugin header.

   // get_file_data wants a filename, but we don't have one. Yet.
   // Let's give it a data: URL.
   $file = "data:text/css;charset=utf-8," . $body;

   $update = get_file_data($file, [
      'theme' => 'Theme Name',
      'url' => 'Theme URI',
      'version' => 'Version',
   ]);

   // Tell WordPress where to get the archive, if it wants.
   $update['package'] = "$repoURI/archive/refs/heads/master.zip";
   
   return $update;
}, 10, 4);

function add_img_tabindex($html) {
   return str_replace('<img', '<img tabindex="0"', $html);
}

// Make post images focusable.
add_filter('render_block_core/image', function($html) {
   return add_img_tabindex($html);
});

function add_lightbox_legacy($content) {
   // Find links that contain only a single image.
   // MT Ugh.
   $regex = '/<a\s+href="([^"]*)"[^>]*>\s*(<img[^>]*>)\s*<\/a>/';

   return preg_replace_callback($regex, function($matches) {
      // Add a lightbox style attribute to them.
      $imageURL = $matches[1];
      $imageTag = $matches[2];

      // Make the image focusable.
      $imageTag = add_img_tabindex($imageTag);

      ob_start();

      ?>
      <div class="has-lightbox">
         <?= $imageTag ?>
         <div class="lightbox-container">
            <img src="<?= $imageURL ?>" />
         </div>
      </div>
      <?php

      return ob_get_clean();
   }, $content);
}

add_filter('the_content', function($content) { return add_lightbox_legacy($content); }, 90);