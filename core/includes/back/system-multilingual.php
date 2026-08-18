<?php

if(!defined('ABSPATH')){exit;}

/**
 * Multilingual layer — WP-LOC.
 *
 * https://github.com/vitaliikaplia/wp-loc
 *
 * WP-LOC is the multilingual plugin this theme targets. Everything the theme
 * needs from it goes through the helpers below, for two reasons:
 *
 *  1. The theme is a starter — most projects are single-language. Every helper
 *     degrades to a sensible single-language answer when WP-LOC is not active,
 *     so no call site needs its own function_exists() guard.
 *  2. WP-LOC ships an `icl_*` / `wpml_*` compatibility layer, but the theme does
 *     not use it. Those shims exist for third-party code that predates WP-LOC;
 *     theme code calls the native API.
 *
 * Language identifiers: WP-LOC separates the URL slug from the database/API
 * language code — Ukrainian is slug `ua`, code `uk`, locale `uk`. The helpers
 * here return the **slug**, which is what the theme uses for cache keys and
 * template logic. Localized options are handled by WP-LOC itself, which reads
 * both suffix forms.
 */

/** Is a multilingual plugin actually running? */
function theme_is_multilingual(){
    return function_exists('wp_loc_get_current_lang') && class_exists('WP_LOC');
}

/**
 * Current language slug (`ua`, `en`, …).
 * Falls back to the WordPress locale so single-language installs still get a
 * stable, non-empty value for cache keys.
 */
function theme_current_language(){

    if(function_exists('wp_loc_get_current_lang')){
        $language = wp_loc_get_current_lang();

        if($language){
            return $language;
        }
    }

    return get_locale();

}

/** Default (no URL prefix) language slug. */
function theme_default_language(){

    if(class_exists('WP_LOC_Languages')){
        $language = WP_LOC_Languages::get_default_language();

        if($language){
            return $language;
        }
    }

    return get_locale();

}

/** Current language as a WordPress locale (`uk`, `en_US`, …). */
function theme_current_locale(){
    return function_exists('wp_loc_get_current_locale') ? wp_loc_get_current_locale() : get_locale();
}

/**
 * The language whatever we are rendering belongs to: the admin-selected one
 * behind wp-admin, the frontend one everywhere else. Use this when filtering
 * pickers and admin lists — the visitor's language is meaningless there.
 */
function theme_context_language(){

    if(is_admin() && !wp_doing_ajax() && function_exists('wp_loc_get_admin_lang')){
        $language = wp_loc_get_admin_lang();

        if($language){
            return $language;
        }
    }

    return theme_current_language();

}

/**
 * The language slug a term belongs to, or '' when it is not registered.
 * WP-LOC keys terms by term_taxonomy_id; WP_LOC_Terms handles that lookup.
 */
function theme_term_language($term_id, $taxonomy){

    $term_id = (int) $term_id;

    if(!$term_id || !class_exists('WP_LOC_Terms')){
        return '';
    }

    $language = WP_LOC_Terms::get_term_language($term_id, $taxonomy);

    return $language ? (string) $language : '';

}

/**
 * The active languages, ready for a switcher.
 * Each entry: code, locale, name, flag, url, active. Empty array when the site
 * is single-language or WP-LOC is not active.
 */
function theme_languages(){

    if(!function_exists('wp_loc_get_lang_switcher')){
        return array();
    }

    $languages = wp_loc_get_lang_switcher();

    if(!is_array($languages) || count($languages) < 2){
        return array();
    }

    return $languages;

}

/**
 * Translate a post/attachment/term ID into another language.
 *
 * @param int         $element_id   Post, attachment or term_taxonomy_id.
 * @param string      $element_type WP-LOC element type, e.g. `post_page`, `post_attachment`, `tax_category`.
 * @param string|null $language     Target language slug; defaults to the current one.
 * @return int The translated ID, or the original when there is no translation.
 */
function theme_translated_id($element_id, $element_type, $language = null){

    $element_id = (int) $element_id;

    if(!$element_id || !theme_is_multilingual()){
        return $element_id;
    }

    $language = $language ? $language : theme_current_language();
    $translated_id = WP_LOC::instance()->db->get_element_translation($element_id, $element_type, $language);

    return $translated_id ? (int) $translated_id : $element_id;

}

/** Translate a post ID, resolving its element type from the post itself. */
function theme_translated_post_id($post_id, $language = null){

    $post_id = (int) $post_id;

    if(!$post_id || !theme_is_multilingual()){
        return $post_id;
    }

    $post_type = get_post_type($post_id);

    if(!$post_type){
        return $post_id;
    }

    return theme_translated_id($post_id, WP_LOC_DB::post_element_type($post_type), $language);

}

/**
 * Every language sibling of an element, including the one passed in.
 *
 * WP-LOC gives each language its own record joined by a `trid`, so a value that
 * belongs to the FILE rather than to the translation — the theme's `webp_url` /
 * `avif_url` shadows, which are one physical file shared by every language —
 * has to be written to all of them at once. Reading falls back to the default
 * language (theme_attachment_meta()), but writing cannot rely on that: the
 * fallback only covers the sibling that happens to be the original.
 *
 * Returns just the given ID when the site is single-language or the element is
 * not registered, so callers can always foreach over the result.
 *
 * @param int    $element_id
 * @param string $element_type WP-LOC element type, e.g. `post_attachment`.
 * @return int[]
 */
function theme_translated_ids($element_id, $element_type){

    $element_id = (int) $element_id;

    if(!$element_id){
        return array();
    }

    if(!theme_is_multilingual()){
        return array($element_id);
    }

    $db = WP_LOC::instance()->db;
    $trid = $db->get_trid($element_id, $element_type);

    if(!$trid){
        return array($element_id);
    }

    $ids = array($element_id);

    foreach($db->get_element_translations($trid, $element_type) as $translation){
        if(!empty($translation->element_id)){
            $ids[] = (int) $translation->element_id;
        }
    }

    return array_values(array_unique($ids));

}

/** Every language sibling of an attachment, including the one passed in. */
function theme_attachment_ids($attachment_id){

    $attachment_id = (int) $attachment_id;

    if(!$attachment_id){
        return array();
    }

    if(!theme_is_multilingual()){
        return array($attachment_id);
    }

    return theme_translated_ids($attachment_id, WP_LOC_DB::post_element_type('attachment'));

}

/** The language a given post belongs to, or '' when it is not registered. */
function theme_post_language($post_id){

    $post_id = (int) $post_id;

    if(!$post_id || !theme_is_multilingual()){
        return '';
    }

    $post_type = get_post_type($post_id);

    if(!$post_type){
        return '';
    }

    $language = WP_LOC::instance()->db->get_element_language($post_id, WP_LOC_DB::post_element_type($post_type));

    return $language ? (string) $language : '';

}

/**
 * Temporarily switch the active language (and the WordPress locale with it).
 *
 * For background work that has to render in someone else's language rather than
 * the current request's — transactional email, cron notifications, webhooks.
 * Always pair it with theme_restore_language().
 *
 *   $previous = theme_switch_language('en');
 *   … render …
 *   theme_restore_language($previous);
 *
 * @return string The language that was active before, for restoring.
 */
function theme_switch_language($language){

    $previous = theme_current_language();

    if($language && theme_is_multilingual() && class_exists('WP_LOC_Routing')){
        WP_LOC_Routing::switch_language($language);
    }

    return $previous;

}

/** Restore the language captured by theme_switch_language(). */
function theme_restore_language($language){

    if($language && theme_is_multilingual() && class_exists('WP_LOC_Routing')){
        WP_LOC_Routing::switch_language($language);
    }

}

/**
 * Read attachment meta, falling back to the default-language sibling.
 *
 * WP-LOC gives every language its own attachment record sharing one physical
 * file, so per-language values (alt text) live on the record being rendered —
 * but meta written once at upload time (the theme's `webp_url`) only exists on
 * the record that was uploaded. Read the current one first, then fall back.
 */
function theme_attachment_meta($attachment_id, $key){

    $attachment_id = (int) $attachment_id;

    if(!$attachment_id){
        return '';
    }

    $value = get_post_meta($attachment_id, $key, true);

    if($value || !theme_is_multilingual()){
        return $value;
    }

    $source_id = theme_translated_id($attachment_id, WP_LOC_DB::post_element_type('attachment'), theme_default_language());

    return $source_id !== $attachment_id ? get_post_meta($source_id, $key, true) : $value;

}

/**
 * Register every dashboard option flagged `'localize' => true` as multilingual,
 * so WP-LOC stores and reads it per language.
 *
 * `blogname`, `blogdescription`, `page_on_front` and `page_for_posts` are
 * registered by WP-LOC itself — no need to repeat them here.
 */
add_action('init', function(){

    if(!theme_is_multilingual() || !function_exists('get_custom_options')){
        return;
    }

    foreach(get_custom_options() as $section){

        if(empty($section['fields']) || !is_array($section['fields'])){
            continue;
        }

        foreach($section['fields'] as $field){
            if(!empty($field['localize']) && !empty($field['name'])){
                do_action('wp_loc_multilingual_options', $field['name']);
            }
        }

    }

}, 5);
