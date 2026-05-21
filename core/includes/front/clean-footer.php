<?php

if(!defined('ABSPATH')){exit;}

if(get_option('disable_wp_speculation_rules')){
    add_filter('wp_speculation_rules_configuration', '__return_null');
}

if(get_option('disable_wp_core_block_supports')){
    add_action('wp_footer', function () {
        wp_dequeue_style('core-block-supports');
    });
}
