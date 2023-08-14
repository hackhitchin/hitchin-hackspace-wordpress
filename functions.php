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
