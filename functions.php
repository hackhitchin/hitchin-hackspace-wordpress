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