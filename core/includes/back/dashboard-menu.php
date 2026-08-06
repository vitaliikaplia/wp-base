<?php

if(!defined('ABSPATH')){exit;}

/** hide some dashboard pages */
if(is_admin()){
	function remove_menus(){

//		remove_menu_page( 'tools.php' );                  //Tools
//		remove_menu_page( 'index.php' );                  //Dashboard
//		remove_menu_page( 'edit.php' );                   //Posts
//		remove_menu_page( 'upload.php' );                 //Media
//		remove_menu_page( 'edit.php?post_type=page' );    //Pages
//		remove_menu_page( 'themes.php' );                 //Appearance
//		remove_menu_page( 'users.php' );                  //Users
//        remove_submenu_page( 'tools.php', 'site-health.php' );
//        remove_menu_page( 'edit-comments.php' );          //Comments
//		remove_menu_page( 'plugins.php' );                //Plugins
//		remove_menu_page( 'options-general.php' );        //Settings
//		remove_submenu_page( 'tools.php', 'site-health.php' );
//        remove_submenu_page('options-general.php', 'options-media.php');
//        remove_submenu_page('options-general.php', 'options-writing.php');
//        remove_submenu_page( 'tools.php', 'site-health.php' );

        remove_submenu_page( 'themes.php', 'site-editor.php?path=/patterns' );
        remove_submenu_page( 'themes.php', 'site-editor.php?p=/pattern' );

        global $submenu;
        $redirect_rules_position = null;
        $target_position = 60;
        foreach ($submenu as $index => $item) {
            if ($index == 'options-general.php') {
                foreach ($item as $i => $ii){
                    if($ii[2] == 'edit.php?post_type=redirect-rules'){
                        $redirect_rules_position = $i;
                    }
                }
            }
        }
        if ($redirect_rules_position && $target_position) {
            $tmp = $submenu['options-general.php'][$redirect_rules_position];
            unset($submenu['options-general.php'][$redirect_rules_position]);
            $submenu['options-general.php'][$target_position] = $tmp;
        }

	}
	add_action( 'admin_menu', 'remove_menus', 999 );
}

//function redirect_from_disabled_admin_pages() {
//    global $pagenow;
//    $disabled_pages = array('options-media.php', 'options-writing.php', 'options-discussion.php', 'site-health.php', 'options-privacy.php');
//    if (in_array($pagenow, $disabled_pages)) {
//        wp_redirect(admin_url('options-general.php'));
//        exit;
//    }
//}
//add_action('admin_init', 'redirect_from_disabled_admin_pages', 1);

function highlight_admin_menu_options_for_custom_pages( $parent_file ) {
    global $typenow;
    if ( (isset( $_GET['post_type'] ) && 'redirect-rules' === $_GET['post_type']) || $typenow == 'redirect-rules' ) {
        return 'options-general.php';
    }
    return $parent_file;
}
add_filter( 'parent_file', 'highlight_admin_menu_options_for_custom_pages' );

function highlight_admin_submenu_for_custom_pages( $submenu_file ) {
    if ( isset( $_GET['post_type'] ) && 'redirect-rules' === $_GET['post_type'] ) {
        $submenu_file = 'edit.php?post_type=redirect-rules';
    }
    return $submenu_file;
}
add_filter( 'submenu_file', 'highlight_admin_submenu_for_custom_pages' );

/** add dashboard menu separators */
function add_admin_menu_separator($position) {
    global $menu;
    $menu[$position] = array(
        0	=>	'',
        1	=>	'read',
        2	=>	'separator' . $position,
        3	=>	'',
        4	=>	'wp-menu-separator'
    );
}
function set_admin_menu_separator() {
    add_admin_menu_separator(25);
    add_admin_menu_separator(85);
    add_admin_menu_separator(87);
}
add_action('admin_menu', 'set_admin_menu_separator');
