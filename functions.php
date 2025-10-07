<?php
/**
 * Theme functions and definitions
 *
 * @package Ni
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

include_once('inc/old-functions.php');
include_once('inc/customcode.php');
include_once('inc/shortcode.php');

add_action('wp_enqueue_scripts', function () {
    wp_enqueue_script('your-custom-js', get_template_directory_uri() . '/path-to/your-js-file.js', [], null, true);
    wp_localize_script('your-custom-js', 'navigator_ajax_filter', [
        'ajax_url' => admin_url('admin-ajax.php'),
    ]);
});
