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
        return $context;
    }

    function add_to_twig( $twig ) {
        /* this is where you can add your own functions to twig */
        $twig->addExtension( new \Twig\Extension\StringLoaderExtension() );
        $twig->addFilter( new \Twig\TwigFilter( 'pr', 'pr' ) );
        $twig->addFilter( new \Twig\TwigFilter( 'log', 'write_log' ) );
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
