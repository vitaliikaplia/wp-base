<?php

if(!defined('ABSPATH')){exit;}

/**
 * Every suffix the theme's per-language transients can carry.
 *
 * LANG_SUFFIX only names the language of the current request. A change made
 * from another language — an AI agent through MCP, WP-CLI, cron, an admin whose
 * screen language differs from the content's — would clear the wrong copy and
 * leave the right one stale for TRANSIENTS_TIME. Clearing walks all of them.
 */
function theme_transient_suffixes(){

    $suffixes = array(LANG_SUFFIX, '_' . get_locale());

    foreach(theme_language_slugs() as $slug){
        $suffixes[] = '_' . $slug;
    }

    return array_values(array_unique($suffixes));

}

/** Delete one theme transient in every language. */
function delete_theme_transient_all_languages($key){

    foreach(theme_transient_suffixes() as $suffix){
        delete_transient($key . $suffix);
    }

}

/** clear transients cache */
function my_acf_update_value( $value, $post_id, $field  ) {

    // Усі мови: опції чи сторінку могли змінити з іншої мови (агент через MCP, WP-CLI)
    if (strpos((string) $post_id, 'options') !== false) {
        delete_theme_transient_all_languages( 'general_fields' );
    } elseif ($post_id && get_post_type( $post_id ) == "page"){
        delete_theme_transient_all_languages( 'custom_page_' . $post_id . '_fields' );
    }

    delete_transient( 'header_css' );
    delete_transient( 'footer_js' );

	return $value;
}
add_filter('acf/update_value', 'my_acf_update_value', 10, 3);

/** clear menus cache */
function clear_nav_menus_cache(){
    delete_theme_transient_all_languages( 'general_fields' );
}
add_action('wp_update_nav_menu', 'clear_nav_menus_cache');
