<?php

if(!defined('ABSPATH')){exit;}

/**
 * System color presets, parsed from the CSS custom properties in
 * assets/scss/_variables.scss (the single source of truth for the palette).
 *
 * The parsed palette feeds the WordPress editor color palette, the ACF color
 * picker, the custom color-select field and the TinyMCE text-color grid, so
 * every colour control offers the same theme colours. Replaces the old
 * ACF "favorite colors" repeater.
 */

/**
 * Flat list of hex colours for pickers that only need values.
 *
 * @return array
 */
function get_system_color_presets(){
    return array_column(get_system_color_palette(), 'color');
}

/**
 * Full palette (name/slug/color), parsed from _variables.scss and cached.
 *
 * @return array
 */
function get_system_color_palette(){
    $cached_palette = get_transient('system_color_palette');

    if(is_array($cached_palette)){
        return $cached_palette;
    }

    $palette = array();
    $scss_file_path = get_theme_file_path('/assets/scss/_variables.scss');

    if(file_exists($scss_file_path)){
        $scss_content = file_get_contents($scss_file_path);

        preg_match_all('/--([a-z0-9-]+)\s*:\s*(#[0-9a-f]{3,8})\s*;/i', $scss_content, $matches, PREG_SET_ORDER);

        foreach($matches as $match){
            $property_name = strtolower($match[1]);
            $color = normalize_system_color($match[2]);

            if(!$color || str_ends_with($property_name, '-rgb')){
                continue;
            }

            if(system_color_exists($palette, $color)){
                continue;
            }

            $palette[] = array(
                'name'  => format_system_color_name($property_name),
                'slug'  => sanitize_title($property_name),
                'color' => $color,
            );
        }
    }

    $cache_time = defined('TRANSIENTS_TIME') ? TRANSIENTS_TIME : (defined('DAY_IN_SECONDS') ? DAY_IN_SECONDS : 86400);

    set_transient('system_color_palette', $palette, $cache_time);

    return $palette;
}

function normalize_system_color($color){
    $color = strtolower(trim($color));

    if(!preg_match('/^#([0-9a-f]{3}|[0-9a-f]{6}|[0-9a-f]{8})$/', $color)){
        return false;
    }

    if(strlen($color) === 4){
        $color = '#' . $color[1] . $color[1] . $color[2] . $color[2] . $color[3] . $color[3];
    }

    if(strlen($color) === 9){
        $color = substr($color, 0, 7);
    }

    return $color;
}

function system_color_exists($palette, $color){
    foreach($palette as $item){
        if(!empty($item['color']) && strtolower($item['color']) === strtolower($color)){
            return true;
        }
    }

    return false;
}

function format_system_color_name($property_name){
    $name = preg_replace('/^(color|theme)-/', '', $property_name);
    $name = str_replace('-', ' ', $name);

    return ucwords($name);
}

/**
 * Register the palette for Gutenberg / block editor colour controls.
 */
function register_system_color_palette(){
    $palette = get_system_color_palette();

    if(!empty($palette)){
        add_theme_support('editor-color-palette', $palette);
    }
}
add_action('after_setup_theme', 'register_system_color_palette');

function add_system_color_palette_to_block_editor($settings){
    $palette = get_system_color_palette();

    if(!empty($palette)){
        $settings['colors'] = $palette;
    }

    return $settings;
}
add_filter('block_editor_settings_all', 'add_system_color_palette_to_block_editor');

if(is_admin() && is_user_logged_in()){

    // ACF color picker palette.
    function change_acf_color_picker() {
        echo Timber::compile( 'dashboard/acf-colors.twig', array(
            'colors' => wp_json_encode(get_system_color_presets())
        ));
    }
    add_action( 'acf/input/admin_head', 'change_acf_color_picker' );

    // TinyMCE color picker palette.
    function my_tiny_mce_custom_colors($mceInit) {
        $colors_for_tiny_mce = "";

        foreach(get_system_color_presets() as $k => $color){
            $colors_for_tiny_mce .= '"' . str_replace('#', '', $color) . '", "Custom color ' . ($k + 1) . '", ';
        }

        $mceInit['textcolor_map'] = '[' . $colors_for_tiny_mce . ']';
        $mceInit['textcolor_rows'] = 6;

        return $mceInit;
    }
    add_filter('tiny_mce_before_init', 'my_tiny_mce_custom_colors');

}
