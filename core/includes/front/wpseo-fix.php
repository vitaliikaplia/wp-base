<?php

if(!defined('ABSPATH')){exit;}

/**
 * Yoast SEO fixes (generic, reusable subset).
 *
 * All hooks below are no-ops unless Yoast SEO is active. They only fix the
 * places where Yoast stores a single value across every language (breadcrumb
 * labels, OG locale) or leaves a template string in the admin language.
 * Project-specific pieces (reserved-slug URL rewriting, team-author sitemap)
 * intentionally stay in the downstream projects.
 */

/**
 * Localized breadcrumb labels. Yoast keeps the Home crumb text and the
 * archive/search/404 prefixes as one value in its options, so a multilingual
 * site shows them in a single language — re-translate them at render time.
 */
add_filter('wpseo_breadcrumb_links', function($links){
    if(empty($links) || !is_array($links)){
        return $links;
    }

    // Home crumb: matches the first crumb only when it points at the site root.
    if(isset($links[0]['url'])){
        $crumb_home = untrailingslashit(strtok((string) $links[0]['url'], '?'));
        $site_home  = untrailingslashit(strtok((string) home_url('/'), '?'));
        if($crumb_home !== '' && $crumb_home === $site_home){
            $links[0]['text'] = __('Home', TEXTDOMAIN);
        }
    }

    // Yoast prefixes (breadcrumbs-archiveprefix / -searchprefix / -404crumb)
    // are stored as one English value — translate them on the fly.
    $prefix_map = array(
        'Error 404: Page not found' => __('Error 404: Page not found', TEXTDOMAIN),
        'Archives for'              => __('Archives for', TEXTDOMAIN),
        'You searched for'          => __('You searched for', TEXTDOMAIN),
    );
    foreach($links as &$link){
        if(isset($link['text']) && is_string($link['text'])){
            $link['text'] = strtr($link['text'], $prefix_map);
        }
    }
    unset($link);

    return $links;
});

/**
 * Open Graph locale for the current language: Yoast returns a raw code ("uk"),
 * while the OG format needs a full locale ("uk_UA"). Reads the language from
 * WP-LOC when it is active, otherwise from the WordPress locale.
 *
 * theme_current_locale() already returns a locale, so the map only has to cover
 * the short forms WP-LOC uses as slugs (`ua`) and codes (`uk`).
 */
add_filter('wpseo_og_locale', function($locale){
    $map = array(
        'uk' => 'uk_UA',
        'ua' => 'uk_UA',
        'ru' => 'ru_RU',
        'en' => 'en_US',
    );

    $language = function_exists('theme_current_locale') ? theme_current_locale() : get_locale();

    if(isset($map[$language])){
        return $map[$language];
    }

    // A full locale ("uk_UA", "en_US") can be used as-is.
    if(strpos($language, '_') !== false){
        return $language;
    }

    return $map[substr((string) $language, 0, 2)] ?? $locale;
}, 999);

/**
 * Clean, translatable Yoast titles for the utility pages (search / 404) —
 * Yoast otherwise leaves the template in the admin language. Separator and
 * site name come from Yoast's own settings via %%sep%% / %%sitename%%.
 */
add_filter('wpseo_title', function($title){
    if(is_search()){
        $sep  = wpseo_replace_vars('%%sep%%', array());
        $site = wpseo_replace_vars('%%sitename%%', array());
        return sprintf(__('Search results for: %s', TEXTDOMAIN), get_search_query()) . " {$sep} " . $site;
    }

    if(is_404()){
        $sep  = wpseo_replace_vars('%%sep%%', array());
        $site = wpseo_replace_vars('%%sitename%%', array());
        return __('Page not found', TEXTDOMAIN) . " {$sep} " . $site;
    }

    return $title;
});

/** Strip the localized "Archives" word from category page titles. */
add_filter('wpseo_title', function($title){
    if(is_category()){
        $title = str_replace(array(' Archives ', ' ' . __('Archives', TEXTDOMAIN) . ' '), ' ', $title);
    }

    return $title;
});
