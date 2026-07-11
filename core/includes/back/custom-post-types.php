<?php

if(!defined('ABSPATH')){exit;}

register_post_type('redirect-rules', array(
        'label' => __('Redirect rules', TEXTDOMAIN),
        'description' => '',
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => '/options-general.php',
        'capability_type' => 'post',
        'map_meta_cap' => true,
        'hierarchical' => false,
        'rewrite' => array('slug' => false, 'with_front' => false),
        'query_var' => true,
        'has_archive' => false,
        'supports' => array('author'),
        'labels' => array (
            'name' => __('Redirect rules', TEXTDOMAIN),
            'singular_name' => __('Redirect rule', TEXTDOMAIN),
            'menu_name' => __('Redirect rules', TEXTDOMAIN),
            'add_new' => __('Add redirect rule', TEXTDOMAIN),
            'add_new_item' => __('Add new redirect rule', TEXTDOMAIN),
            'edit' => __('Edit', TEXTDOMAIN),
            'edit_item' => __('Edit redirect rule', TEXTDOMAIN),
            'new_item' => __('New redirect rule', TEXTDOMAIN),
            'view' => __('View', TEXTDOMAIN),
            'view_item' => __('View redirect rule', TEXTDOMAIN),
            'search_items' => __('Search for redirect rules', TEXTDOMAIN),
            'not_found' => __('No redirect rules found', TEXTDOMAIN),
            'not_found_in_trash' => __('No redirect rules found in trash', TEXTDOMAIN),
            'parent' => __('Parent redirect rule', TEXTDOMAIN)
        )
    )
);

register_post_type('patterns', array(
        'label' => __('Patterns', TEXTDOMAIN),
        'description' => '',
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => true,
        'show_in_nav_menus' => false,
        'capability_type' => 'post',
        'map_meta_cap' => true,
        'hierarchical' => false,
        'rewrite' => array('slug' => false, 'with_front' => false),
        'query_var' => true,
        'has_archive' => false,
        'menu_position' => 86,
        'menu_icon' => 'dashicons-block-default',
        'supports' => array('title','editor','revisions','author'),
        'show_in_rest' => true,
        'taxonomies' => array('pattern_categories'),
        'labels' => array (
            'name' => __('Patterns', TEXTDOMAIN),
            'singular_name' => __('Pattern', TEXTDOMAIN),
            'menu_name' => __('Patterns', TEXTDOMAIN),
            'add_new' => __('Add item', TEXTDOMAIN),
            'add_new_item' => __('Add new item', TEXTDOMAIN),
            'edit' => __('Edit', TEXTDOMAIN),
            'edit_item' => __('Edit item', TEXTDOMAIN),
            'new_item' => __('New item', TEXTDOMAIN),
            'view' => __('View', TEXTDOMAIN),
            'view_item' => __('View item', TEXTDOMAIN),
            'search_items' => __('Search for items', TEXTDOMAIN),
            'not_found' => __('No items found', TEXTDOMAIN),
            'not_found_in_trash' => __('No items found in trash', TEXTDOMAIN),
            'parent' => __('Parent item', TEXTDOMAIN)
        )
    )
);

register_post_type('mail-log', array(
        'label' => __('Mail log', TEXTDOMAIN),
        'description' => '',
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => true,
        'show_in_nav_menus' => false,
        'capability_type' => 'post',
        'map_meta_cap' => true,
        'hierarchical' => false,
        'rewrite' => array('slug' => '', 'with_front' => false),
        'query_var' => true,
        'has_archive' => false,
        'exclude_from_search' => true,
        'menu_position' => 88,
        'menu_icon' => 'dashicons-email-alt',
        'supports' => array('title'),
        'capabilities' => array(
            'create_posts' => false
        ),
        'labels' => array (
            'name' => __('Mail log', TEXTDOMAIN),
            'singular_name' => __('Mail log', TEXTDOMAIN),
            'menu_name' => __('Mail log', TEXTDOMAIN),
            'add_new' => __('Add', TEXTDOMAIN),
            'add_new_item' => __('Add', TEXTDOMAIN),
            'edit' => __('Edit', TEXTDOMAIN),
            'edit_item' => __('Edit', TEXTDOMAIN),
            'new_item' => __('New', TEXTDOMAIN),
            'view' => __('View', TEXTDOMAIN),
            'view_item' => __('View', TEXTDOMAIN),
            'search_items' => __('Search for mail logs', TEXTDOMAIN),
            'not_found' => __('No mail logs found', TEXTDOMAIN),
            'not_found_in_trash' => __('No mail logs found in trash', TEXTDOMAIN),
            'parent' => __('Parent', TEXTDOMAIN),
        )
    )
);

register_post_type('sms-log', array(
        'label' => __('SMS log', TEXTDOMAIN),
        'description' => '',
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => true,
        'show_in_nav_menus' => false,
        'capability_type' => 'post',
        'map_meta_cap' => true,
        'hierarchical' => false,
        'rewrite' => array('slug' => '', 'with_front' => false),
        'query_var' => true,
        'has_archive' => false,
        'exclude_from_search' => true,
        'menu_position' => 89,
        'menu_icon' => 'dashicons-format-status',
        'supports' => false,
        'capabilities' => array(
            'create_posts' => false
        ),
        'labels' => array (
            'name' => __('SMS log', TEXTDOMAIN),
            'singular_name' => __('SMS log', TEXTDOMAIN),
            'menu_name' => __('SMS log', TEXTDOMAIN),
            'add_new' => __('Add', TEXTDOMAIN),
            'add_new_item' => __('Add', TEXTDOMAIN),
            'edit' => __('Edit', TEXTDOMAIN),
            'edit_item' => __('Edit', TEXTDOMAIN),
            'new_item' => __('New', TEXTDOMAIN),
            'view' => __('View', TEXTDOMAIN),
            'view_item' => __('View', TEXTDOMAIN),
            'search_items' => __('Search for SMS logs', TEXTDOMAIN),
            'not_found' => __('No SMS logs found', TEXTDOMAIN),
            'not_found_in_trash' => __('No SMS logs found in trash', TEXTDOMAIN),
            'parent' => __('Parent', TEXTDOMAIN),
        )
    )
);
