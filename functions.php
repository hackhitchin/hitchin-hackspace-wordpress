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

function get_slack_archive_content($path) {
   // Get the archive an admin has uploaded.
   $archive_id = get_option('hh_slack_archives')['archive_id'] ?? 0;

   // Is there one?
   if (!$archive_id)
      throw new Exception('Sorry, the Slack archives are not available at this time.');

   // Try opening it.
   // Open the archive and retrieve the page the user's asked for.
   $archive = new ZipArchive();
   if ($archive->open(get_attached_file($archive_id)) !== true)
      throw new Exception('There was a problem opening the Slack archive file.');

   $content = $archive->getFromName($path);
   if ($content === false)
      throw new Exception('The Slack archive does not contain the requested file.');

   return $content;
}

add_shortcode('hh_slack_archives', function($attrs) {
   try {
      // Work out which page the user wants.
      $path = get_query_var('path') ?: 'relative menu.html';

      // ... then fetch it, if we can.
      $content = get_slack_archive_content($path);

      // Rewrite the links to be relative to the current page.
      $content = rewrite_slack_html($content);
   }
   catch (Exception $e) {
      error_log(print_r($e, true));
      $content = $e->getMessage();
   }

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

function setting_media($page, $section, $option_group, $key, $name, $description) {
   $field_id = $option_group . '_' . $key;

   add_settings_field($field_id, $name, function() use ($option_group, $key, $description) {
      $options = get_option($option_group);
      $media_id = $options[$key] ?: 0;

      ?>
         <div class="select-media-container">
            <input type="hidden" name="<?= $option_group ?>[<?= $key ?>]" value="<?= $media_id ?>">
            <span><?= $media_id ? basename(get_attached_file($media_id)) : 'None Selected' ?></span>
            <button class="select-media" onclick="selectMedia(event);">Select File</button>
         </div>
         <p><?= $description ?></p>
      <?php
   }, $page, $section);
}

add_action('admin_init', function() {
   register_setting('general', 'hh_slack_archives', [
       'default' => [
           'archive_id' => 0
       ]
   ]);

   add_settings_section('hh_slack_archives', 'Slack Archive Viewer', function($args) {
      wp_enqueue_media();
      ?>
         <script>
            
            function selectMedia(ev) {
               ev.preventDefault();

               const el = ev.target;
               const container = el.closest('.select-media-container');
               const idInput = container.querySelector('input[type="hidden"]');

               let mediaPicker = wp.media({
                  title: 'Select or Upload a Slack HTML archive (.zip)',
                  button: {
                     text: 'Use this file'
                  },
                  multiple: false
               });

               mediaPicker.on('select', function() {
                  const selected = mediaPicker.state().get('selection').first().toJSON();

                  idInput.value = selected.id;
                  container.querySelector('span').innerText = selected.filename;
               });

               mediaPicker.open();
            }
         </script>
      <?php
   }, 'general');

   setting_media('general', 'hh_slack_archives', 'hh_slack_archives', 'archive_id', 'Slack Archive file', 'ZIP file containing the Slack archive in HTML format.');
});

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