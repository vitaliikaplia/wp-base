<?php

if(!defined('ABSPATH')){exit;}

if(get_option('disable_wp_font_library_manager')){

    function wp_base_disable_font_library_admin_menu(){
        remove_submenu_page('themes.php', 'font-library.php');
    }
    add_action('admin_menu', 'wp_base_disable_font_library_admin_menu', 999);

    function wp_base_disable_font_library_direct_access(){
        global $pagenow;

        $font_library_pages = array('font-library', 'font-library-wp-admin');
        $requested_page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : '';

        if('font-library.php' === $pagenow || in_array($requested_page, $font_library_pages, true)){
            wp_die(
                '<h1>' . esc_html__('Font Library is disabled.', TEXTDOMAIN) . '</h1>' .
                '<p>' . esc_html__('The WordPress Font Library manager is disabled by the theme settings.', TEXTDOMAIN) . '</p>',
                esc_html__('Font Library disabled', TEXTDOMAIN),
                array('response' => 403)
            );
        }
    }
    add_action('admin_init', 'wp_base_disable_font_library_direct_access', 0);

    function wp_base_disable_font_library_output(){
        remove_action('wp_head', 'wp_print_font_faces', 50);
        remove_action('admin_print_styles', 'wp_print_font_faces', 50);
        remove_action('admin_print_styles', 'wp_print_font_faces_from_style_variations', 50);
    }
    add_action('init', 'wp_base_disable_font_library_output', 20);

    function wp_base_disable_font_library_editor_settings($editor_settings){
        $editor_settings['fontLibraryEnabled'] = false;

        return $editor_settings;
    }
    add_filter('block_editor_settings_all', 'wp_base_disable_font_library_editor_settings', 20);

    function wp_base_disable_font_library_rest_routes($result, $server, $request){
        $route = $request->get_route();

        if(preg_match('#^/wp/v2/(font-families|font-collections)(?:/|$)#', $route)){
            return new WP_Error(
                'rest_font_library_disabled',
                __('The WordPress Font Library is disabled by the theme settings.', TEXTDOMAIN),
                array('status' => 403)
            );
        }

        return $result;
    }
    add_filter('rest_pre_dispatch', 'wp_base_disable_font_library_rest_routes', 10, 3);

}
