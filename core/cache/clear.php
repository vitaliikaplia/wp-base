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

/**
 * Delete every transient whose key starts with $prefix.
 *
 * In the database that is one LIKE query. A persistent object cache cannot
 * list its keys, so there the whole `transient` group is flushed instead (once
 * per request) — transients are disposable by contract.
 *
 * @return bool Whether the transients are gone.
 */
function delete_transients_by_prefix($prefix){

    global $wpdb;
    static $group_flushed = null;

    if(wp_using_ext_object_cache()){

        if($group_flushed === null){
            $group_flushed = function_exists('wp_cache_supports') && wp_cache_supports('flush_group') && wp_cache_flush_group('transient');
        }

        return $group_flushed;

    }

    $where = $wpdb->prepare(
        "option_name LIKE %s OR option_name LIKE %s",
        $wpdb->esc_like('_transient_' . $prefix) . '%',
        $wpdb->esc_like('_transient_timeout_' . $prefix) . '%'
    );
    $names = $wpdb->get_col("SELECT option_name FROM {$wpdb->options} WHERE {$where}");

    if(!$names){
        return true;
    }

    if(false === $wpdb->query("DELETE FROM {$wpdb->options} WHERE {$where}")){
        return false;
    }

    // рядки зникли повз delete_option(), тож і кеш опцій цього запиту скидаємо вручну
    foreach($names as $name){
        wp_cache_delete($name, 'options');
    }
    wp_cache_delete('alloptions', 'options');

    return true;

}

/**
 * Clear the theme's own caches.
 *
 * With a post ID: that page's cached ACF fields and the Timber HTML cache.
 * Without: everything the theme caches — options, menus, page fields, redirect
 * rules, block patterns, the colour palette, rendered SVG markup and Timber HTML.
 * Page-cache plugins and the object cache are not the theme's: Simple MCP's
 * cache_flush handles those.
 *
 * @return array What was cleared, and what could not be (persistent object
 *               cache without group flushing).
 */
function flush_theme_caches($post_id = 0){

    $post_id = (int) $post_id;
    $cleared = array();
    $failed  = array();

    if($post_id){
        delete_theme_transient_all_languages('custom_page_' . $post_id . '_fields');
        $cleared[] = 'page_fields';
    } else {
        foreach(array('general_fields', 'redirect_rules', 'page_block_pattern_categories_array', 'page_block_patterns_array') as $key){
            delete_theme_transient_all_languages($key);
            $cleared[] = $key;
        }

        foreach(array('system_color_palette', 'header_css', 'footer_js') as $key){
            delete_transient($key);
            $cleared[] = $key;
        }

        // ключі з ID чи хешем перелічити не можна — чистимо за префіксом
        foreach(array('custom_page_' => 'page_fields', 'svg2_' => 'svg_markup') as $prefix => $label){
            if(delete_transients_by_prefix($prefix)){
                $cleared[] = $label;
            } else {
                $failed[] = $label;
            }
        }
    }

    // HTML-кеш Timber: ключ — хеш шаблону й контексту, тож окрему сторінку не вибрати
    if(delete_transients_by_prefix('timberloader_')){
        $cleared[] = 'timber_html';
    } else {
        $failed[] = 'timber_html';
    }

    return array(
        'cleared' => array_values(array_unique($cleared)),
        'failed'  => array_values(array_unique($failed)),
    );

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
