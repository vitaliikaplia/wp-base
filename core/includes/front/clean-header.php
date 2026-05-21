<?php

if(!defined('ABSPATH')){exit;}

// clean inline gallery CSS
add_filter( 'use_default_gallery_style', '__return_false' );

// clean other CSS and JS on header
if (!is_admin()) {
    function my_init_method(){
        wp_deregister_script( 'l10n' );
    }
    add_action('init', 'my_init_method');
}
function remheadlink(){
    remove_action( 'wp_head', 'feed_links_extra', 3 );
    remove_action( 'wp_head', 'feed_links', 2 );
    remove_action( 'wp_head', 'rsd_link' );
    remove_action( 'wp_head', 'wlwmanifest_link' );
    remove_action( 'wp_head', 'index_rel_link' );
    remove_action( 'wp_head', 'parent_post_rel_link', 10, 0 );
    remove_action( 'wp_head', 'start_post_rel_link', 10, 0 );
    remove_action( 'wp_head', 'adjacent_posts_rel_link', 10, 0 );
    remove_action( 'wp_head', 'wp_generator');

    if(get_option('disable_wp_font_library_output') || get_option('disable_wp_font_library_manager')){
        // Use this only when theme-generated font CSS replaces WordPress native Font Library output.
        remove_action( 'wp_head', 'wp_print_font_faces', 50 );
    }

    if(get_option('disable_wp_image_auto_sizes')){
        // Keep WordPress from printing the WP 7 auto-sizes helper CSS in the frontend head.
        remove_action( 'wp_head', 'wp_enqueue_img_auto_sizes_contain_css_fix', 0 );
        remove_action( 'wp_head', 'wp_print_auto_sizes_contain_css_fix', 1 );
    }

    if(get_option('disable_wp_global_styles')){
        // Disable Gutenberg preset variables/classes and stored style output on the frontend.
        remove_action( 'wp_enqueue_scripts', 'wp_enqueue_global_styles' );
        remove_action( 'wp_footer', 'wp_enqueue_global_styles', 1 );
        remove_action( 'wp_enqueue_scripts', 'wp_enqueue_stored_styles' );
        remove_action( 'wp_footer', 'wp_enqueue_stored_styles', 1 );
    }
}
add_action('init', 'remheadlink');
add_filter( 'wpseo_json_ld_output', '__return_false' );

if(get_option('disable_wp_image_auto_sizes')){
    add_filter( 'wp_img_tag_add_auto_sizes', '__return_false' );
}

add_action( 'wp_print_styles', 'wps_deregister_styles', 100 );
function wps_deregister_styles() {
    if(get_option('disable_wp_image_auto_sizes')){
        wp_dequeue_style( 'wp-img-auto-sizes-contain' );
    }

    if(get_option('disable_wp_block_library_styles')){
        wp_dequeue_style( 'wp-block-library' );
        wp_dequeue_style( 'wp-block-library-theme' );
    }

    if(get_option('disable_wp_global_styles')){
        wp_dequeue_style( 'global-styles' );
        wp_dequeue_style( 'classic-theme-styles' );
    }
}
