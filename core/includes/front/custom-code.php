<?php

if(!defined('ABSPATH')){exit;}

/** header custom code */
function frontend_custom_header(){
	if ($custom_header_code = get_option('header_custom_code')) {
		echo $custom_header_code . "\n";
	}
}
add_action('wp_head', 'frontend_custom_header');

/** footer custom code */
function frontend_custom_footer(){
	if ($custom_footer_code = get_option('footer_custom_code')) {
		echo $custom_footer_code . "\n";
	}
}
add_action('wp_footer', 'frontend_custom_footer',99);
