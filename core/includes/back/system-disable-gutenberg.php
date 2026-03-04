<?php

if(!defined('ABSPATH')){exit;}

$disable_gutenberg = get_option('disable_gutenberg');

/** disable gutenberg everywhere */
if($disable_gutenberg === 'everywhere'){
	add_filter( 'use_block_editor_for_post_type', '__return_false', 100 );
	remove_action( 'wp_enqueue_scripts', 'wp_common_block_scripts_and_styles' );
	add_action( 'admin_init', function(){
		remove_action( 'admin_notices', [ 'WP_Privacy_Policy_Content', 'notice' ] );
		add_action( 'edit_form_after_title', [ 'WP_Privacy_Policy_Content', 'notice' ] );
	} );
}

/** disable gutenberg for blog archive and single pages by default */
if($disable_gutenberg === 'blog'){
    add_filter('use_block_editor_for_post_type', 'disable_gutenberg_for_blog', 10, 2);
    function disable_gutenberg_for_blog($current_status, $post_type){
        global $post;
        if (isset($post->ID) && $post->ID == get_option('page_for_posts')) {
            return false;
        }
        if (isset($post->ID) && get_post_type($post->ID) == 'post') {
            return false;
        }
        return $current_status;
    }
}
