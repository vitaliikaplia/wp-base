<?php

if(!defined('ABSPATH')){exit;}

/** hide top menu */
function sl_dashboard_tweaks_render() {
	global $wp_admin_bar;
	$wp_admin_bar->remove_menu('wp-logo');
	$wp_admin_bar->remove_menu('new-content');
	$wp_admin_bar->remove_menu('about');
	$wp_admin_bar->remove_menu('wporg');
	$wp_admin_bar->remove_menu('documentation');
	$wp_admin_bar->remove_menu('support-forums');
	$wp_admin_bar->remove_menu('feedback');
	$wp_admin_bar->remove_menu('view-site');
	$wp_admin_bar->remove_menu('comments'); // optional, delete comments as many websites don't even have those enabled.
}
add_action( 'wp_before_admin_bar_render', 'sl_dashboard_tweaks_render' );

/** hide admin top bar */
if(get_option('hide_admin_top_bar')){
	add_action( 'admin_print_styles-profile.php', 'global_profile_hide_admin_bar' );
	add_action( 'admin_print_styles-user-edit.php', 'global_profile_hide_admin_bar' );
	function global_profile_hide_admin_bar() {
		echo '<style>.show-admin-bar { display: none !important; }</style>';
		return;
	}
	add_filter( 'show_admin_bar', '__return_false' );
}
