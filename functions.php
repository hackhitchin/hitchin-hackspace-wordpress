<?php

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

function convert_slack_url($url) {
   // Is it a relative URL?
   $parsed = parse_url($url);
   if (array_key_exists('host', $parsed))
      return $url; // No.
      
   return '?path=' . urlencode(str_replace('\\', '/', $url));
}

function rewrite_slack_html($content) {
   /* 
   
   Turns out, what we get isn't valid HTML.

   --

   $doc = new DOMDocument();
   $doc->loadHTML($content);

   // Iterate through all the links.
   foreach ($doc->getElementsByTagName('a') as $link) {
      $href = $link->attributes['href']->nodeValue;

      // Is it a relative URL?
      $url = parse_url($href);
      if (array_key_exists('host', $url))
         continue; // No.
         
      $link->attributes['href']->nodeValue = '?path=' . urlencode(str_replace('\\', '/', $href));
   }
   */

   // Okay, let's try something more naive
   return preg_replace_callback('/<a href="([^"]*)" target="iframe_main">/', function($matches) {
      $url = convert_slack_url($matches[1]);

      return "<a href=\"$url\" target=\"iframe_main\">";
   }, $content);
}

add_shortcode('hh_slack_archives', function($attrs) {
   // Open the archive and retrieve the page the user's asked for.
   $archive = new ZipArchive();
   $archive->open(WP_CONTENT_DIR . '/uploads/slack html archive.zip');
   $path = get_query_var('path') ?: 'relative menu.html';

   $content = $archive->getFromName($path);

   // Rewrite the links to be relative to the current page.
   $content = rewrite_slack_html($content);
   
   ob_start();
   ?>
      <div class="slack-archives">
         <?= $content ?>
      </div>
   <?php

   return ob_get_clean();
});

add_filter('query_vars', function($query_vars) {
   $query_vars[] = 'path';

   return $query_vars;
});