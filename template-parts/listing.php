<?php 

/**
 * Template Name: Listings page
 */


get_header();  
echo do_shortcode(('[elementor-template id="93246"]'));
the_content();
echo '<div class="" style="padding:1em;"><div class="container">'.do_shortcode(get_field('listings_shortcode')).'</div></div>';
get_footer(); ?>