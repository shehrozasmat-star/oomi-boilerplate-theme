<?php 
/**
 * Template Name: sso 
 * @package Ni
 */
 
session_start();
echo do_shortcode('[oomiSSO]');
get_header();
get_footer();