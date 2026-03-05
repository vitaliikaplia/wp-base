<?php

if(!defined('ABSPATH')){exit;}

/** hide default widgets */
if(get_option('hide_dashboard_widgets')){
	function remove_dashboard_widgets() {
		global $wp_meta_boxes;
		unset($wp_meta_boxes['dashboard']['normal']['core']['dashboard_activity']);
		unset($wp_meta_boxes['dashboard']['normal']['core']['dashboard_right_now']);
		unset($wp_meta_boxes['dashboard']['side']['core']['dashboard_quick_press']);
		unset($wp_meta_boxes['dashboard']['normal']['core']['dashboard_incoming_links']);
		unset($wp_meta_boxes['dashboard']['normal']['core']['dashboard_plugins']);
		unset($wp_meta_boxes['dashboard']['normal']['core']['dashboard_recent_drafts']);
		unset($wp_meta_boxes['dashboard']['normal']['core']['dashboard_recent_comments']);
		unset($wp_meta_boxes['dashboard']['side']['core']['dashboard_primary']);
		unset($wp_meta_boxes['dashboard']['side']['core']['dashboard_secondary']);
		unset($wp_meta_boxes['dashboard']['normal']['core']['dashboard_site_health']);
		unset($wp_meta_boxes['dashboard']['normal']['core']['dashboard_php_nag']);
	}
	add_action('wp_dashboard_setup', 'remove_dashboard_widgets' );
	remove_action( 'try_gutenberg_panel', 'wp_try_gutenberg_panel' );
	add_action('wp_dashboard_setup', 'remove_wpseo_dashboard_overview' );
	function remove_wpseo_dashboard_overview() {
		remove_meta_box( 'wpseo-dashboard-overview', 'dashboard', 'side' );
	}
    function remove_php_nag() {
        remove_meta_box( 'dashboard_php_nag', 'dashboard', 'normal' );
    }
    add_action( 'wp_dashboard_setup', 'remove_php_nag' );
}
