<?php

if(!defined('ABSPATH')){exit;}

use Timber\Site;

class StarterSite extends Site {
    public function __construct() {
        add_filter( 'timber/context', array( $this, 'add_to_context' ) );
        add_filter( 'timber/twig', array( $this, 'add_to_twig' ) );
        parent::__construct();
    }

    /**
     * This is where you add some context
     *
     * @param string $context context['this'] Being the Twig's {{ this }}.
     */
    public function add_to_context( $context ) {
        $context['site'] = $this;
        $context['assets'] = ASSETS_VERSION;
        $context['site_language'] = get_bloginfo('language');
        $context['svg_sprite'] = SVG_SPRITE_URL;
        $context['general_fields'] = cache_general_fields();
        $context['TEXTDOMAIN'] = TEXTDOMAIN;
        /**
         * Active languages for the switcher — an empty array on single-language
         * sites and when WP-LOC is not installed, so templates only need
         * {% if languages %}. WP-LOC's own Timber integration additionally
         * provides `current_language` and the `wp_loc_*` Twig functions.
         */
        $context['languages'] = theme_languages();
        return $context;
    }

    function add_to_twig( $twig ) {
        /* this is where you can add your own functions to twig */
        $twig->addExtension( new \Twig\Extension\StringLoaderExtension() );
        $twig->addFilter( new \Twig\TwigFilter( 'pr', 'pr' ) );
        $twig->addFilter( new \Twig\TwigFilter( 'log', 'write_log' ) );

        /**
         * Escaping note — do NOT re-register the WordPress escapers here.
         *
         * Timber v2 already ships |esc_url, |esc_attr, |esc_html, |esc_js,
         * |wp_kses and |wp_kses_post; adding them again makes Twig throw
         * "Filter … is already registered" and takes down every page.
         *
         * They matter because Timber ships with Twig autoescaping OFF, so every
         * {{ … }} in this theme prints raw. That is deliberate (menus, wp_head
         * and block HTML all have to pass through), but it means anything
         * landing in an HTML attribute has to be escaped by hand: |esc_url in
         * href/src, |esc_attr in any other attribute, |esc_html for text that
         * must not carry markup.
         */

        /**
         * Guard for values interpolated into an inline <style> block (see
         * block-base.twig). Lets through colours, keywords, numbers with units
         * and var() references; drops anything containing ";", "}" or "<",
         * which could close the declaration — or the <style> element itself —
         * and inject arbitrary rules or markup.
         */
        $twig->addFilter( new \Twig\TwigFilter( 'css_value', function($value) {
            $value = trim((string) $value);
            return preg_match('/^[a-zA-Z0-9#%().,\s_-]*$/', $value) ? $value : '';
        }));
        $twig->addFunction( new \Twig\TwigFunction('get_pattern', 'get_pattern'));
        $placeholder = '';
        $twig->addFilter( new \Twig\TwigFilter( 'picture', function($picture, $size = 'full') use ($placeholder) {
            if (empty($picture)) return $placeholder;
            return render_picture_tag($picture, $size);
        }));
        $twig->addFilter( new \Twig\TwigFilter( 'picture_eager', function($picture, $size = 'full') use ($placeholder) {
            if (empty($picture)) return $placeholder;
            return render_picture_tag($picture, $size, 'eager');
        }));
        $twig->addFilter( new \Twig\TwigFilter( 'picture_src', function($picture, $size = 'full') {
            if (empty($picture)) return '';
            return render_picture_src($picture, $size);
        }));
        $twig->addFilter( new \Twig\TwigFilter( 'svg', function($svg, $attributes = []) {
            if (empty($svg)) return '';
            return render_svg_tag($svg, $attributes);
        }));
        $twig->addFilter( new \Twig\TwigFilter( 'ceil', function($number) {
            return ceil($number);
        }));
        $twig->addFunction( new \Twig\TwigFunction('picture', function($picture, $size = 'full') use ($placeholder) {
            if (empty($picture)) return $placeholder;
            return render_picture_tag($picture, $size);
        }));
        $twig->addFunction( new \Twig\TwigFunction('get_option', 'get_option'));
        $twig->addFunction( new \Twig\TwigFunction('wp_editor', 'wp_editor'));
        $twig->addFunction( new \Twig\TwigFunction('checked', 'checked'));
        $twig->addFunction( new \Twig\TwigFunction('get_user_ip', 'get_user_ip'));
        $twig->addFunction( new \Twig\TwigFunction('get_session_info', 'get_session_info'));
        $twig->addFunction( new \Twig\TwigFunction('fix_phone_format', 'fix_phone_format'));
        $twig->addFunction( new \Twig\TwigFunction('nice_phone_format', 'nice_phone_format'));
        $twig->addFunction( new \Twig\TwigFunction('icon', 'render_managed_icon', array('is_safe' => array('html'))));
        $twig->addFunction( new \Twig\TwigFunction('managed_icon', 'render_managed_icon', array('is_safe' => array('html'))));
        return $twig;
    }
}

Timber\Timber::init();
Timber::$dirname = TIMBER_VIEWS;
new StarterSite();
