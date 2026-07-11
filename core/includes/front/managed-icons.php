<?php

if(!defined('ABSPATH')){exit;}

// Internal prefix that marks a user-managed (uploads) icon, so icon() can tell it apart
// from a theme default and render from the correct sprite.
function managed_icon_prefix(){
    return 'managed-icon-';
}

// Theme default sprite (bundled with the theme, read-only).
function theme_sprite_file(){
    return THEME_PATH . 'assets' . DS . 'svg' . DS . 'sprite.svg';
}

// User-managed sprite (in uploads, written by the icon manager).
function uploads_sprite_file(){
    return trailingslashit(WP_CONTENT_DIR) . 'uploads' . DS . 'icons' . DS . 'sprite-managed.svg';
}

// Normalize a stored/typed icon ID. Managed icons keep their prefix; theme IDs stay clean.
function normalize_icon_id($icon_id){
    return sanitize_title((string) $icon_id);
}

// Is this ID a user-managed (uploads) icon?
function is_managed_icon_id($icon_id){
    $prefix = managed_icon_prefix();

    return $prefix && 0 === strpos((string) $icon_id, $prefix);
}

// Read and cache the symbol IDs of a given sprite file.
function read_sprite_symbol_ids($sprite_path){

    static $cache = array();

    if(isset($cache[$sprite_path])){
        return $cache[$sprite_path];
    }

    $ids = array();

    if(!file_exists($sprite_path)){
        return $cache[$sprite_path] = $ids;
    }

    $dom = new DOMDocument();

    libxml_use_internal_errors(true);
    $loaded = $dom->load($sprite_path, LIBXML_NONET);
    libxml_clear_errors();

    if($loaded){
        foreach($dom->getElementsByTagName('symbol') as $symbol){
            $id = $symbol->getAttribute('id');

            if($id){
                $ids[] = $id;
            }
        }
    }

    return $cache[$sprite_path] = array_values(array_unique($ids));

}

// Read and cache id → viewBox for a given sprite file.
function read_sprite_symbol_viewboxes($sprite_path){

    static $cache = array();

    if(isset($cache[$sprite_path])){
        return $cache[$sprite_path];
    }

    $map = array();

    if(!file_exists($sprite_path)){
        return $cache[$sprite_path] = $map;
    }

    $dom = new DOMDocument();

    libxml_use_internal_errors(true);
    $loaded = $dom->load($sprite_path, LIBXML_NONET);
    libxml_clear_errors();

    if($loaded){
        foreach($dom->getElementsByTagName('symbol') as $symbol){
            $id = $symbol->getAttribute('id');

            if($id){
                $map[$id] = $symbol->getAttribute('viewBox');
            }
        }
    }

    return $cache[$sprite_path] = $map;

}

// The viewBox of a managed/theme icon symbol (empty string if unknown). Lets markup
// give the <svg> the glyph's real aspect ratio so the box can hug non-square icons.
function get_managed_icon_viewbox($icon_id){

    $icon_id = normalize_icon_id($icon_id);

    if(!$icon_id){
        return '';
    }

    $sprite_path = is_managed_icon_id($icon_id) ? uploads_sprite_file() : theme_sprite_file();
    $map = read_sprite_symbol_viewboxes($sprite_path);

    return isset($map[$icon_id]) ? $map[$icon_id] : '';

}

// Strip the x/y offset from a viewBox, keeping only "0 0 W H". When an icon is shown
// via <use> (which already applies the symbol's own viewBox, offset included), the outer
// <svg> must frame the SIZE only — otherwise the offset is applied twice and the art clips.
function icon_size_only_viewbox($view_box){

    $parts = preg_split('/[\s,]+/', trim((string) $view_box));

    if(is_array($parts) && count($parts) === 4 && (float) $parts[2] > 0 && (float) $parts[3] > 0){
        return '0 0 ' . $parts[2] . ' ' . $parts[3];
    }

    return (string) $view_box;

}

// The public sprite URL an icon ID renders from (managed → uploads, otherwise → theme).
function managed_icon_sprite_url($icon_id){
    return is_managed_icon_id($icon_id) ? MANAGED_SVG_SPRITE_URL : SVG_SPRITE_URL;
}

// Whether a stored icon ID still exists in its sprite (so deleted icons show a placeholder).
function managed_icon_exists($icon_id){

    $icon_id = normalize_icon_id($icon_id);

    if(!$icon_id){
        return false;
    }

    $sprite_path = is_managed_icon_id($icon_id) ? uploads_sprite_file() : theme_sprite_file();

    return in_array($icon_id, read_sprite_symbol_ids($sprite_path), true);

}

// Build HTML attributes for generated icon markup.
function render_managed_icon_attributes($attributes){

    $output = '';

    foreach((array) $attributes as $name => $value){
        if(null === $value || false === $value){
            continue;
        }

        $name = sanitize_key($name);

        if('' === $name){
            continue;
        }

        if(true === $value){
            $output .= ' ' . esc_attr($name);
            continue;
        }

        $output .= ' ' . esc_attr($name) . '="' . esc_attr((string) $value) . '"';
    }

    return $output;

}

// Render a managed SVG icon from Twig/PHP, with an explicit missing-icon state for deleted symbols.
function render_managed_icon($icon_id, $attributes = array()){

    $icon_id = normalize_icon_id($icon_id);
    $attributes = is_array($attributes) ? $attributes : array();
    $class = !empty($attributes['class']) ? $attributes['class'] : 'icon';

    if(!$icon_id || !managed_icon_exists($icon_id)){
        $attributes['class'] = trim($class . ' icon--missing');
        $attributes['data-missing-icon'] = $icon_id;
        $attributes['role'] = !empty($attributes['role']) ? $attributes['role'] : 'img';
        $attributes['aria-label'] = !empty($attributes['aria-label']) ? $attributes['aria-label'] : sprintf(__('Missing icon: %s', TEXTDOMAIN), $icon_id ? $icon_id : __('unknown', TEXTDOMAIN));

        return '<svg' . render_managed_icon_attributes($attributes) . ' viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" focusable="false"><path d="M12 9v4"/><path d="M12 17h.01"/><path d="M10.3 3.9 2.8 17a2 2 0 0 0 1.7 3h15a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"/></svg>';
    }

    $attributes['class'] = trim($class);

    if(empty($attributes['aria-hidden']) && empty($attributes['aria-label']) && empty($attributes['role'])){
        $attributes['aria-hidden'] = 'true';
    }

    if(empty($attributes['focusable'])){
        $attributes['focusable'] = 'false';
    }

    return '<svg' . render_managed_icon_attributes($attributes) . '><use href="' . esc_url(managed_icon_sprite_url($icon_id) . '#' . $icon_id) . '"></use></svg>';

}
