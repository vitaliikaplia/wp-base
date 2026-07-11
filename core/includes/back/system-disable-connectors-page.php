<?php

if(!defined('ABSPATH')){exit;}

if(get_option('disable_connectors_page')){

    /**
     * Disable the WordPress Connectors admin page.
     */
    function hide_connectors_admin_page(){
        remove_menu_page('options-connectors.php');
        remove_submenu_page('options-general.php', 'options-connectors.php');

        global $menu, $submenu;

        if(is_array($menu)){
            foreach($menu as $index => $item){
                if(isset($item[2]) && $item[2] === 'options-connectors.php'){
                    unset($menu[$index]);
                }
            }
        }

        if(is_array($submenu)){
            foreach($submenu as $parent => $items){
                foreach($items as $index => $item){
                    if(isset($item[2]) && $item[2] === 'options-connectors.php'){
                        unset($submenu[$parent][$index]);
                    }
                }
            }
        }
    }
    add_action('admin_menu', 'hide_connectors_admin_page', 999);
    add_action('network_admin_menu', 'hide_connectors_admin_page', 999);

    /**
     * Block direct access to /wp-admin/options-connectors.php.
     */
    function disable_connectors_admin_page(){
        global $pagenow;

        if($pagenow === 'options-connectors.php'){
            wp_die(
                esc_html__('This admin page is disabled.', TEXTDOMAIN),
                esc_html__('Disabled page', TEXTDOMAIN),
                array('response' => 403)
            );
        }
    }
    add_action('admin_init', 'disable_connectors_admin_page');
    add_action('network_admin_edit_options-connectors', 'disable_connectors_admin_page');

}
