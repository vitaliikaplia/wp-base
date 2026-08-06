<?php

if(!defined('ABSPATH')){exit;}

function get_managed_icons_sprite_path(){
    return trailingslashit(WP_CONTENT_DIR) . 'uploads' . DS . 'icons' . DS . 'sprite-managed.svg';
}

function get_managed_icons_sprite_url(){
    return content_url('uploads/icons/sprite-managed.svg') . '?ver=' . get_option('assets_version', ASSETS_VERSION);
}

// The theme ships a read-only default sprite; the manager only reads it, never writes to it.
function get_theme_sprite_path(){
    return THEME_PATH . 'assets' . DS . 'svg' . DS . 'sprite.svg';
}

function prepare_managed_icons_sprite_dir(){

    $dir = dirname(get_managed_icons_sprite_path());

    if(!is_dir($dir)){
        wp_mkdir_p($dir);
    }

    return is_dir($dir) && is_writable($dir);

}

// User-managed icons live in the uploads sprite under this internal prefix, so they never
// collide with the theme's default (clean-id) icons and icon() can resolve which sprite to
// render from. The prefix is hidden everywhere in the UI.
function get_managed_icon_id_prefix(){
    return 'managed-icon-';
}

// Bump the shared asset version so a changed sprite invalidates browser caches.
function increment_assets_version(){

    if(!defined('ASSETS_VERSION')){
        return;
    }

    $next_assets_version = round((float) ASSETS_VERSION + 0.01, 2);
    update_option('assets_version', $next_assets_version);

}

function get_empty_managed_icons_dom(){

    $dom = new DOMDocument();
    $dom->preserveWhiteSpace = false;
    $dom->formatOutput = true;
    $dom->loadXML('<svg style="display:none !important;" xmlns="http://www.w3.org/2000/svg"></svg>');

    return $dom;

}

function get_managed_icons_dom(){

    $sprite_path = get_managed_icons_sprite_path();

    if(!file_exists($sprite_path)){
        return get_empty_managed_icons_dom();
    }

    $dom = new DOMDocument();
    $dom->preserveWhiteSpace = false;
    $dom->formatOutput = true;

    libxml_use_internal_errors(true);
    $loaded = $dom->load($sprite_path, LIBXML_NONET);
    libxml_clear_errors();

    if(!$loaded){
        return new WP_Error('invalid_sprite', __('SVG sprite could not be read.', TEXTDOMAIN));
    }

    return $dom;

}

// Read the theme's default (read-only) icons from the bundled sprite.
function get_theme_sprite_icons(){

    $path = get_theme_sprite_path();
    $icons = array();

    if(!file_exists($path)){
        return $icons;
    }

    $dom = new DOMDocument();

    libxml_use_internal_errors(true);
    $loaded = $dom->load($path, LIBXML_NONET);
    libxml_clear_errors();

    if(!$loaded){
        return $icons;
    }

    foreach($dom->getElementsByTagName('symbol') as $symbol){
        $id = $symbol->getAttribute('id');

        if(!$id){
            continue;
        }

        $icons[] = array(
            'id' => $id,
            'viewBox' => $symbol->getAttribute('viewBox'),
            'locked' => true,
            'sprite_url' => SVG_SPRITE_URL,
        );
    }

    return $icons;

}

// Read the user-managed icons from the uploads sprite (fully editable).
function get_managed_sprite_icons(){

    $dom = get_managed_icons_dom();

    if(is_wp_error($dom)){
        return array();
    }

    $icons = array();
    $sprite_url = get_managed_icons_sprite_url();

    foreach($dom->getElementsByTagName('symbol') as $symbol){
        $id = $symbol->getAttribute('id');

        if(!$id){
            continue;
        }

        $icons[] = array(
            'id' => $id,
            'viewBox' => $symbol->getAttribute('viewBox'),
            'locked' => false,
            'sprite_url' => $sprite_url,
        );
    }

    return $icons;

}

// The full icon list the manager/picker shows: theme defaults (read-only) + managed icons.
function get_managed_icons(){

    $icons = array_merge(get_theme_sprite_icons(), get_managed_sprite_icons());

    usort($icons, function($a, $b){
        // locked (theme default) icons float to the top, then natural order by id
        if($a['locked'] !== $b['locked']){
            return $a['locked'] ? -1 : 1;
        }

        return strnatcasecmp($a['id'], $b['id']);
    });

    return $icons;

}

function load_svg_content($svg_content){

    $svg_content = trim((string) $svg_content);

    if(!$svg_content){
        return new WP_Error('empty_svg', __('SVG file is empty.', TEXTDOMAIN));
    }

    if(strlen($svg_content) > 5 * MB_IN_BYTES){
        return new WP_Error('svg_too_large', __('SVG file is too large.', TEXTDOMAIN));
    }

    $svg_content = preg_replace('/<!DOCTYPE[^>]*>/i', '', $svg_content);

    $dom = new DOMDocument();
    $dom->preserveWhiteSpace = false;
    $dom->formatOutput = true;

    libxml_use_internal_errors(true);
    $loaded = $dom->loadXML($svg_content, LIBXML_NONET);
    libxml_clear_errors();

    if(!$loaded || !$dom->documentElement || strtolower($dom->documentElement->tagName) !== 'svg'){
        return new WP_Error('invalid_svg', __('Only valid SVG files are allowed.', TEXTDOMAIN));
    }

    return $dom;

}

// IDs that exist in the writable (uploads) sprite — used for import conflict detection.
function get_managed_icon_ids(){

    $ids = array();

    foreach(get_managed_sprite_icons() as $icon){
        $ids[] = $icon['id'];
    }

    return $ids;

}

// IDs of theme-default (locked) icons — these can never be renamed or deleted.
function get_locked_managed_icon_ids(){

    $ids = array();

    foreach(get_theme_sprite_icons() as $icon){
        $ids[] = $icon['id'];
    }

    return $ids;

}

function get_svg_viewbox($svg){

    $viewbox = $svg->getAttribute('viewBox');

    if($viewbox){
        return $viewbox;
    }

    $width = (float) $svg->getAttribute('width');
    $height = (float) $svg->getAttribute('height');

    if($width > 0 && $height > 0){
        return '0 0 ' . rtrim(rtrim((string) $width, '0'), '.') . ' ' . rtrim(rtrim((string) $height, '0'), '.');
    }

    return '0 0 24 24';

}

function get_single_svg_source_id($svg, $source_name = ''){

    $source_id = $source_name ? pathinfo(sanitize_file_name($source_name), PATHINFO_FILENAME) : '';

    if(!$source_id){
        $source_id = $svg->getAttribute('id');
    }

    return $source_id ? $source_id : 'icon';

}

function get_managed_icons_import_options($options = array()){

    if(!is_array($options)){
        $options = array();
    }

    $fit_viewbox = isset($options['fit_viewbox']) && $options['fit_viewbox'];
    $current_color = isset($options['current_color']) && $options['current_color'];
    $viewbox = '';
    $crop_x = 0;
    $crop_y = 0;

    if($fit_viewbox && !empty($options['viewBox'])){
        $parts = preg_split('/[\s,]+/', trim((string) $options['viewBox']));

        if(count($parts) === 4){
            $parts = array_map('floatval', $parts);

            if($parts[2] > 0 && $parts[3] > 0){
                $crop_x = $parts[0];
                $crop_y = $parts[1];
                $viewbox = '0 0 ' . format_svg_number($parts[2]) . ' ' . format_svg_number($parts[3]);
            }
        }
    }

    return array(
        'fit_viewbox' => $fit_viewbox,
        'current_color' => $current_color,
        'viewBox' => $viewbox,
        'crop_x' => $crop_x,
        'crop_y' => $crop_y,
    );

}

function format_svg_number($number){

    $number = round((float) $number, 3);
    $number = rtrim(rtrim(sprintf('%.3F', $number), '0'), '.');

    return $number === '-0' ? '0' : $number;

}

function analyze_single_managed_icon_import($dom, $source_name = ''){

    $svg = $dom->documentElement;
    $source_id = get_single_svg_source_id($svg, $source_name);
    $managed_id = sanitize_icon_id($source_id);
    $existing_ids = get_managed_icon_ids();
    $status = 'add';
    $stats = array(
        'total' => 1,
        'add' => 0,
        'conflict' => 0,
        'invalid' => 0,
    );

    if(!$managed_id){
        $status = 'invalid';
        $stats['invalid'] = 1;
    } elseif(!svg_node_has_visible_artwork($svg)){
        $status = 'invalid';
        $stats['invalid'] = 1;
    } elseif(in_array($managed_id, $existing_ids, true)){
        $status = 'conflict';
        $stats['conflict'] = 1;
    } else {
        $stats['add'] = 1;
    }

    return array(
        'type' => 'icon',
        'stats' => $stats,
        'icons' => array(
            array(
                'source_id' => $source_id,
                'id' => $managed_id,
                'display_id' => $managed_id ? get_managed_icon_display_id($managed_id) : $source_id,
                'viewBox' => get_svg_viewbox($svg),
                'status' => $status,
            ),
        ),
    );

}

function analyze_managed_icons_import($svg_content, $source_name = ''){

    $dom = load_svg_content($svg_content);

    if(is_wp_error($dom)){
        return $dom;
    }

    $symbols = $dom->getElementsByTagName('symbol');

    if(!$symbols->length){
        return analyze_single_managed_icon_import($dom, $source_name);
    }

    $existing_ids = get_managed_icon_ids();
    $reserved_ids = $existing_ids;
    $icons = array();
    $stats = array(
        'total' => 0,
        'add' => 0,
        'conflict' => 0,
        'invalid' => 0,
    );

    foreach($symbols as $symbol){
        $stats['total']++;

        $source_id = $symbol->getAttribute('id');
        $managed_id = sanitize_icon_id($source_id);
        $status = 'add';

        if(!$managed_id){
            $status = 'invalid';
            $stats['invalid']++;
        } elseif(!svg_node_has_visible_artwork($symbol)){
            $status = 'invalid';
            $stats['invalid']++;
        } elseif(in_array($managed_id, $reserved_ids, true)){
            $status = 'conflict';
            $stats['conflict']++;
        } else {
            $stats['add']++;
            $reserved_ids[] = $managed_id;
        }

        $icons[] = array(
            'source_id' => $source_id,
            'id' => $managed_id,
            'display_id' => $managed_id ? get_managed_icon_display_id($managed_id) : $source_id,
            'viewBox' => $symbol->getAttribute('viewBox'),
            'status' => $status,
        );
    }

    return array(
        'type' => 'sprite',
        'stats' => $stats,
        'icons' => $icons,
    );

}

function svg_node_has_visible_artwork($node){

    if(!$node || $node->nodeType !== XML_ELEMENT_NODE){
        return false;
    }

    $name = strtolower($node->localName ? $node->localName : $node->nodeName);
    $non_rendering_nodes = array(
        'defs',
        'filter',
        'lineargradient',
        'radialgradient',
        'clippath',
        'mask',
        'marker',
        'pattern',
        'style',
        'script',
        'metadata',
        'title',
        'desc',
    );

    if(in_array($name, $non_rendering_nodes, true)){
        return false;
    }

    if($node->hasAttribute('display') && strtolower(trim($node->getAttribute('display'))) === 'none'){
        return false;
    }

    if($node->hasAttribute('visibility') && strtolower(trim($node->getAttribute('visibility'))) === 'hidden'){
        return false;
    }

    if($node->hasAttribute('opacity') && (float) $node->getAttribute('opacity') === 0.0){
        return false;
    }

    $drawable_nodes = array(
        'path',
        'circle',
        'ellipse',
        'rect',
        'line',
        'polyline',
        'polygon',
        'text',
        'tspan',
        'image',
        'use',
    );

    if(in_array($name, $drawable_nodes, true)){
        $fill = $node->hasAttribute('fill') ? strtolower(trim($node->getAttribute('fill'))) : '';
        $stroke = $node->hasAttribute('stroke') ? strtolower(trim($node->getAttribute('stroke'))) : '';

        if($fill === 'none' && $stroke === 'none'){
            return false;
        }

        return true;
    }

    foreach($node->childNodes as $child){
        if(svg_node_has_visible_artwork($child)){
            return true;
        }
    }

    return false;

}

function import_single_managed_icon($source_dom, $target_dom, $icon, $options = array()){

    $source_svg = $source_dom->documentElement;
    $symbol = $target_dom->createElementNS('http://www.w3.org/2000/svg', 'symbol');
    $symbol->setAttribute('id', $icon['id']);
    $symbol->setAttribute('viewBox', !empty($options['viewBox']) ? $options['viewBox'] : $icon['viewBox']);
    $presentation_attributes = array(
        'fill',
        'stroke',
        'stroke-width',
        'stroke-linecap',
        'stroke-linejoin',
        'stroke-miterlimit',
        'fill-rule',
        'clip-rule',
        'opacity',
        'fill-opacity',
        'stroke-opacity',
        'color',
        'preserveAspectRatio',
    );

    foreach($presentation_attributes as $attribute){
        if($source_svg->hasAttribute($attribute)){
            $symbol->setAttribute($attribute, $source_svg->getAttribute($attribute));
        }
    }

    foreach(iterator_to_array($source_svg->childNodes) as $child){
        if($child->nodeType === XML_TEXT_NODE && !trim($child->nodeValue)){
            continue;
        }

        $symbol->appendChild($target_dom->importNode($child, true));
    }

    inline_svg_styles($symbol);
    sanitize_imported_svg_node($symbol);
    optimize_imported_svg_node($symbol);

    if(!empty($options['viewBox']) && ((float) $options['crop_x'] !== 0.0 || (float) $options['crop_y'] !== 0.0)){
        translate_svg_geometry($symbol, -1 * (float) $options['crop_x'], -1 * (float) $options['crop_y']);
    }

    if(!empty($options['current_color'])){
        apply_current_color_to_icon($symbol);
    }

    $target_dom->documentElement->appendChild($symbol);

}

function translate_svg_geometry($node, $dx, $dy){

    foreach(iterator_to_array($node->childNodes) as $child){
        if($child->nodeType !== XML_ELEMENT_NODE){
            continue;
        }

        if($child->hasAttribute('d')){
            $child->setAttribute('d', translate_svg_path_data($child->getAttribute('d'), $dx, $dy));
        }

        foreach(array('x', 'x1', 'x2', 'cx') as $attribute){
            translate_svg_number_attribute($child, $attribute, $dx);
        }

        foreach(array('y', 'y1', 'y2', 'cy') as $attribute){
            translate_svg_number_attribute($child, $attribute, $dy);
        }

        if($child->hasAttribute('points')){
            $child->setAttribute('points', translate_svg_points($child->getAttribute('points'), $dx, $dy));
        }

        if($child->hasAttribute('transform')){
            $child->setAttribute('transform', translate_svg_transform($child->getAttribute('transform'), $dx, $dy));
        }

        translate_svg_geometry($child, $dx, $dy);
    }

}

function translate_svg_number_attribute($node, $attribute, $delta){

    if(!$node->hasAttribute($attribute)){
        return;
    }

    $value = trim($node->getAttribute($attribute));

    if(!is_numeric($value)){
        return;
    }

    $node->setAttribute($attribute, format_svg_number((float) $value + $delta));

}

function translate_svg_points($points, $dx, $dy){

    $numbers = preg_split('/[\s,]+/', trim($points));
    $translated = array();

    for($i = 0; $i < count($numbers); $i += 2){
        if(!isset($numbers[$i + 1]) || !is_numeric($numbers[$i]) || !is_numeric($numbers[$i + 1])){
            continue;
        }

        $translated[] = format_svg_number((float) $numbers[$i] + $dx) . ',' . format_svg_number((float) $numbers[$i + 1] + $dy);
    }

    return implode(' ', $translated);

}

function translate_svg_transform($transform, $dx, $dy){

    return preg_replace_callback('/translate\(\s*([-+]?\d*\.?\d+(?:e[-+]?\d+)?)\s*(?:,|\s)?\s*([-+]?\d*\.?\d+(?:e[-+]?\d+)?)?\s*\)/i', function($matches) use ($dx, $dy){
        $x = (float) $matches[1] + $dx;
        $y = isset($matches[2]) && $matches[2] !== '' ? (float) $matches[2] + $dy : $dy;

        return 'translate(' . format_svg_number($x) . ' ' . format_svg_number($y) . ')';
    }, $transform);

}

function translate_svg_path_data($path_data, $dx, $dy){

    preg_match_all('/[a-zA-Z]|[-+]?(?:\d*\.\d+|\d+\.?)(?:[eE][-+]?\d+)?/', $path_data, $matches);
    $tokens = $matches[0];
    $argument_counts = array(
        'M' => 2,
        'L' => 2,
        'H' => 1,
        'V' => 1,
        'C' => 6,
        'S' => 4,
        'Q' => 4,
        'T' => 2,
        'A' => 7,
        'Z' => 0,
    );
    $output = '';
    $command = '';
    $arguments = array();

    foreach($tokens as $token){
        if(preg_match('/^[a-zA-Z]$/', $token)){
            $output .= translate_svg_path_arguments($command, $arguments, $dx, $dy);
            $arguments = array();
            $command = $token;
            $output .= $token;
            continue;
        }

        if($command === ''){
            continue;
        }

        $arguments[] = $token;
        $upper_command = strtoupper($command);
        $argument_count = isset($argument_counts[$upper_command]) ? $argument_counts[$upper_command] : 0;

        if($argument_count && count($arguments) === $argument_count){
            $output .= translate_svg_path_arguments($command, $arguments, $dx, $dy);
            $arguments = array();
        }
    }

    $output .= translate_svg_path_arguments($command, $arguments, $dx, $dy);

    return $output;

}

function translate_svg_path_arguments($command, $arguments, $dx, $dy){

    if(!$command || !$arguments){
        return '';
    }

    $upper_command = strtoupper($command);
    $is_relative = $command !== $upper_command;
    $translated = array();

    foreach($arguments as $index => $argument){
        $value = (float) $argument;

        if(!$is_relative){
            if(in_array($upper_command, array('M', 'L', 'T'), true)){
                $value += $index % 2 === 0 ? $dx : $dy;
            } elseif($upper_command === 'H'){
                $value += $dx;
            } elseif($upper_command === 'V'){
                $value += $dy;
            } elseif(in_array($upper_command, array('C', 'S', 'Q'), true)){
                $value += $index % 2 === 0 ? $dx : $dy;
            } elseif($upper_command === 'A' && ($index === 5 || $index === 6)){
                $value += $index === 5 ? $dx : $dy;
            }
        }

        $translated[] = format_svg_number($value);
    }

    return ' ' . implode(' ', $translated);

}

function inline_svg_styles($node){

    $rules = extract_svg_style_rules($node);

    foreach(iterator_to_array($node->getElementsByTagName('*')) as $element){
        apply_svg_style_rules($element, $rules);
    }

}

function extract_svg_style_rules($node){

    $rules = array();

    foreach(iterator_to_array($node->getElementsByTagName('style')) as $style){
        $css = $style->textContent;

        if(!preg_match_all('/([^{}]+)\{([^{}]+)\}/', $css, $matches, PREG_SET_ORDER)){
            continue;
        }

        foreach($matches as $match){
            $selectors = array_map('trim', explode(',', $match[1]));
            $properties = parse_svg_css_properties($match[2]);

            foreach($selectors as $selector){
                if(!preg_match('/^\.[a-zA-Z0-9_-]+$/', $selector)){
                    continue;
                }

                $class_name = substr($selector, 1);

                if(!isset($rules[$class_name])){
                    $rules[$class_name] = array();
                }

                $rules[$class_name] = array_merge($rules[$class_name], $properties);
            }
        }
    }

    return $rules;

}

function parse_svg_css_properties($css){

    $properties = array();

    foreach(explode(';', $css) as $declaration){
        if(strpos($declaration, ':') === false){
            continue;
        }

        list($name, $value) = array_map('trim', explode(':', $declaration, 2));

        if($name && $value){
            $properties[$name] = $value;
        }
    }

    return $properties;

}

function apply_svg_style_rules($element, $rules){

    $inline_properties = array();

    if($element->hasAttribute('class')){
        foreach(preg_split('/\s+/', trim($element->getAttribute('class'))) as $class_name){
            if(isset($rules[$class_name])){
                $inline_properties = array_merge($inline_properties, $rules[$class_name]);
            }
        }
    }

    if($element->hasAttribute('style')){
        $inline_properties = array_merge($inline_properties, parse_svg_css_properties($element->getAttribute('style')));
        $element->removeAttribute('style');
    }

    foreach($inline_properties as $name => $value){
        if(!$element->hasAttribute($name)){
            $element->setAttribute($name, $value);
        }
    }

    $element->removeAttribute('class');

}

function is_svg_paint_element($node){
    return $node->nodeType === XML_ELEMENT_NODE && in_array(strtolower($node->nodeName), array('path', 'circle', 'ellipse', 'rect', 'polygon', 'polyline', 'text', 'tspan'), true);
}

function svg_attribute_or_style($node, $name){

    if($node->hasAttribute($name)){
        return trim($node->getAttribute($name));
    }

    if(!$node->hasAttribute('style')){
        return '';
    }

    if(preg_match('/(?:^|;)\s*' . preg_quote($name, '/') . '\s*:\s*([^;]+)/i', $node->getAttribute('style'), $matches)){
        return trim($matches[1]);
    }

    return '';

}

function svg_node_has_stroke($node){

    $stroke = svg_attribute_or_style($node, 'stroke');

    return $stroke && strtolower($stroke) !== 'none' && $stroke !== 'transparent';

}

function svg_resolved_attribute_or_style($node, $name, $inherited_value = ''){

    $value = svg_attribute_or_style($node, $name);

    return $value !== '' ? $value : $inherited_value;

}

function svg_value_has_stroke($stroke){
    return $stroke && strtolower($stroke) !== 'none' && $stroke !== 'transparent';
}

function svg_value_has_fill($fill){
    return strtolower($fill) !== 'none' && strtolower($fill) !== 'transparent';
}

function svg_node_has_fill($node, $inherited_fill = ''){

    $fill = svg_resolved_attribute_or_style($node, 'fill', $inherited_fill);

    return svg_value_has_fill($fill);

}

function svg_group_has_strokes($node, $inherited_stroke = ''){

    $node_stroke = svg_resolved_attribute_or_style($node, 'stroke', $inherited_stroke);

    foreach($node->childNodes as $child){
        if($child->nodeType !== XML_ELEMENT_NODE){
            continue;
        }

        $child_stroke = svg_resolved_attribute_or_style($child, 'stroke', $node_stroke);

        if(is_svg_paint_element($child) && svg_value_has_stroke($child_stroke)){
            return true;
        }

        if(svg_group_has_strokes($child, $child_stroke)){
            return true;
        }
    }

    return false;

}

function svg_group_has_fills($node, $inherited_fill = ''){

    $node_fill = svg_resolved_attribute_or_style($node, 'fill', $inherited_fill);

    foreach($node->childNodes as $child){
        if($child->nodeType !== XML_ELEMENT_NODE){
            continue;
        }

        $child_fill = svg_resolved_attribute_or_style($child, 'fill', $node_fill);

        if(is_svg_paint_element($child) && svg_value_has_fill($child_fill)){
            return true;
        }

        if(svg_group_has_fills($child, $child_fill)){
            return true;
        }
    }

    return false;

}

function set_svg_fill_current_color($node){

    $node->setAttribute('fill', 'currentColor');

    if($node->hasAttribute('style')){
        $style = preg_replace('/(?:^|;)\s*fill\s*:\s*[^;]+;?/i', '', $node->getAttribute('style'));
        $style = trim($style, " \t\n\r\0\x0B;");

        if($style){
            $node->setAttribute('style', $style);
        } else {
            $node->removeAttribute('style');
        }
    }

}

function remove_svg_fill_from_descendants($node){

    foreach(iterator_to_array($node->childNodes) as $child){
        if($child->nodeType !== XML_ELEMENT_NODE){
            continue;
        }

        if(is_svg_paint_element($child)){
            $child->removeAttribute('fill');

            if($child->hasAttribute('style')){
                $style = preg_replace('/(?:^|;)\s*fill\s*:\s*[^;]+;?/i', '', $child->getAttribute('style'));
                $style = trim($style, " \t\n\r\0\x0B;");

                if($style){
                    $child->setAttribute('style', $style);
                } else {
                    $child->removeAttribute('style');
                }
            }
        }

        remove_svg_fill_from_descendants($child);
    }

}

function apply_current_color_to_icon($node, $inherited_fill = '', $inherited_stroke = ''){

    $node_fill = svg_resolved_attribute_or_style($node, 'fill', $inherited_fill);
    $node_stroke = svg_resolved_attribute_or_style($node, 'stroke', $inherited_stroke);

    foreach(iterator_to_array($node->childNodes) as $child){
        if($child->nodeType !== XML_ELEMENT_NODE){
            continue;
        }

        $name = strtolower($child->nodeName);
        $child_fill = svg_resolved_attribute_or_style($child, 'fill', $node_fill);
        $child_stroke = svg_resolved_attribute_or_style($child, 'stroke', $node_stroke);

        if($name === 'g' || $name === 'symbol'){
            $has_fills = svg_group_has_fills($child, $child_fill);
            $has_strokes = svg_group_has_strokes($child, $child_stroke);

            if($has_fills && !$has_strokes){
                set_svg_fill_current_color($child);
                remove_svg_fill_from_descendants($child);
                continue;
            }
        }

        if(is_svg_paint_element($child) && svg_value_has_fill($child_fill)){
            set_svg_fill_current_color($child);
            continue;
        }

        apply_current_color_to_icon($child, $child_fill, $child_stroke);
    }

}

/**
 * Clean a node that is about to be written into the managed sprite.
 *
 * Delegates to the shared sanitizer in core/includes/back/svg-sanitizer.php so imported
 * symbols, uploaded SVGs and inline-rendered attachments all enforce the same
 * rules (blocked elements, event handlers, URI schemes, SMIL href retargeting).
 * The node itself is checked here; sanitize_svg_node() handles its subtree.
 */
function sanitize_imported_svg_node($node){

    if($node->nodeType === XML_ELEMENT_NODE){

        $tag = strtolower($node->localName ? $node->localName : $node->nodeName);

        if(in_array($tag, svg_blocked_elements(), true) && $node->parentNode){
            $node->parentNode->removeChild($node);
            return;
        }

        foreach(iterator_to_array($node->attributes) as $attribute){
            $name = strtolower($attribute->nodeName);
            $value = $attribute->nodeValue;

            if(strpos($name, 'on') === 0){
                $node->removeAttributeNode($attribute);
                continue;
            }

            if($name === 'style' && svg_style_is_dangerous($value)){
                $node->removeAttributeNode($attribute);
                continue;
            }

            if(in_array($name, svg_uri_attributes(), true) && !svg_is_safe_uri_value($value)){
                $node->removeAttributeNode($attribute);
            }
        }

    }

    sanitize_svg_node($node);

}

function optimize_imported_svg_node($node){

    foreach(iterator_to_array($node->childNodes) as $child){
        if($child->nodeType === XML_COMMENT_NODE || ($child->nodeType === XML_TEXT_NODE && !trim($child->nodeValue))){
            $node->removeChild($child);
            continue;
        }

        if($child->nodeType === XML_ELEMENT_NODE){
            if(in_array(strtolower($child->nodeName), array('metadata', 'title', 'desc', 'defs', 'style'), true)){
                $node->removeChild($child);
                continue;
            }

            $child->removeAttribute('isolation');
            $child->removeAttribute('id');
            optimize_imported_svg_node($child);

            if(strtolower($child->nodeName) === 'g' && !$child->hasAttributes()){
                foreach(iterator_to_array($child->childNodes) as $grandchild){
                    $node->insertBefore($grandchild, $child);
                }

                $node->removeChild($child);
            }
        }
    }

}

function import_managed_icons($svg_content, $source_name = '', $options = array()){

    $analysis = analyze_managed_icons_import($svg_content, $source_name);
    $options = get_managed_icons_import_options($options);

    if(is_wp_error($analysis)){
        return $analysis;
    }

    if(empty($analysis['stats']['add'])){
        return new WP_Error('no_icons_to_import', __('No new icons can be imported from this SVG file.', TEXTDOMAIN));
    }

    if(!prepare_managed_icons_sprite_dir()){
        return new WP_Error('sprite_not_writable', __('SVG sprite is not writable.', TEXTDOMAIN));
    }

    $source_dom = load_svg_content($svg_content);
    $target_dom = get_managed_icons_dom();

    if(is_wp_error($source_dom)){
        return $source_dom;
    }

    if(is_wp_error($target_dom)){
        return $target_dom;
    }

    $target_root = $target_dom->documentElement;
    $imported_icons = array();
    $import_queue = array();

    if($analysis['type'] === 'icon'){
        $icon = $analysis['icons'][0];
        import_single_managed_icon($source_dom, $target_dom, $icon, $options);
        $imported_icons[] = array(
            'id' => $icon['id'],
            'display_id' => $icon['display_id'],
            'viewBox' => !empty($options['viewBox']) ? $options['viewBox'] : $icon['viewBox'],
        );
    }

    foreach($analysis['type'] === 'sprite' ? $analysis['icons'] : array() as $icon){
        if($icon['status'] !== 'add'){
            continue;
        }

        if(!isset($import_queue[$icon['source_id']])){
            $import_queue[$icon['source_id']] = array();
        }

        $import_queue[$icon['source_id']][] = $icon;
    }

    foreach($analysis['type'] === 'sprite' ? $source_dom->getElementsByTagName('symbol') : array() as $symbol){
        $source_id = $symbol->getAttribute('id');

        if(empty($import_queue[$source_id])){
            continue;
        }

        $icon = array_shift($import_queue[$source_id]);
        $imported_symbol = $target_dom->importNode($symbol, true);
        $imported_symbol->setAttribute('id', $icon['id']);
        sanitize_imported_svg_node($imported_symbol);
        $target_root->appendChild($imported_symbol);
        $imported_icons[] = array(
            'id' => $icon['id'],
            'display_id' => $icon['display_id'],
            'viewBox' => $icon['viewBox'],
        );
    }

    if(!save_managed_icons_sprite($target_dom, get_managed_icons_sprite_path())){
        return new WP_Error('sprite_save_failed', __('SVG sprite could not be saved.', TEXTDOMAIN));
    }

    increment_assets_version();

    return array(
        'icons' => $imported_icons,
        'stats' => $analysis['stats'],
    );

}

function import_managed_icons_sprite($svg_content, $source_name = '', $options = array()){
    return import_managed_icons($svg_content, $source_name, $options);
}

// Sanitize a user-typed icon NAME into a managed sprite ID (always prefixed). Used by the
// manager when creating/importing/renaming icons (which only ever live in the uploads sprite).
function sanitize_icon_id($icon_id){

    $prefix = get_managed_icon_id_prefix();
    $icon_id = sanitize_title((string) $icon_id);

    if(!$icon_id || $icon_id === rtrim($prefix, '-')){
        return '';
    }

    if(!$prefix || strpos($icon_id, $prefix) === 0){
        return $icon_id;
    }

    return $prefix . $icon_id;

}

// Strip the internal prefix for display. Theme default IDs have no prefix and pass through.
function get_managed_icon_display_id($icon_id){

    $prefix = get_managed_icon_id_prefix();

    if($prefix && strpos($icon_id, $prefix) === 0){
        return substr($icon_id, strlen($prefix));
    }

    return (string) $icon_id;

}

function get_managed_icons_config(){

    return array(
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('icons'),
        'spriteUrl' => get_managed_icons_sprite_url(),
        'themeSpriteUrl' => SVG_SPRITE_URL,
        'prefix' => get_managed_icon_id_prefix(),
        'messages' => array(
            'emptyIconId' => __('Icon name cannot be empty.', TEXTDOMAIN),
            'duplicateIconId' => __('Icon name already exists.', TEXTDOMAIN),
            'updateFailed' => __('Icon name was not updated.', TEXTDOMAIN),
            'iconNotFound' => __('Icon was not found in the sprite.', TEXTDOMAIN),
            'iconLocked' => __('Default icons are read-only and cannot be changed.', TEXTDOMAIN),
            'downloadFailed' => __('SVG icon could not be downloaded.', TEXTDOMAIN),
            'updated' => __('Icon updated.', TEXTDOMAIN),
            'empty' => __('No icons found in the sprite yet.', TEXTDOMAIN),
            'deleteFailed' => __('Icon was not deleted.', TEXTDOMAIN),
            'bulkDeleteFailed' => __('Selected icons were not deleted.', TEXTDOMAIN),
            'bulkDeleted' => __('Selected icons deleted.', TEXTDOMAIN),
            'deleteSelected' => __('Delete selected', TEXTDOMAIN),
            'deleteSelectedCount' => __('Delete selected (%s)', TEXTDOMAIN),
            'noIconsSelected' => __('No icons selected.', TEXTDOMAIN),
            'delete' => __('Delete', TEXTDOMAIN),
            'confirmDelete' => __('Confirm delete', TEXTDOMAIN),
            'svgOnly' => __('Only SVG files are allowed.', TEXTDOMAIN),
            'readFailed' => __('SVG file could not be read.', TEXTDOMAIN),
            'importFailed' => __('SVG file could not be imported.', TEXTDOMAIN),
            'imported' => __('SVG sprite imported.', TEXTDOMAIN),
            'importSummary' => __('This sprite contains %1$s icons. %2$s will be added and %3$s skipped because of name conflicts.', TEXTDOMAIN),
            'importIconSummary' => __('This SVG icon will be imported as "%s".', TEXTDOMAIN),
            'importIconConflict' => __('Icon "%s" already exists and will be skipped.', TEXTDOMAIN),
            'importIconInvalid' => __('This SVG icon does not contain visible artwork.', TEXTDOMAIN),
            'importInvalid' => __('%s icons have invalid names and will be skipped.', TEXTDOMAIN),
            'dismissNotice' => __('Dismiss this notice.', TEXTDOMAIN),
            'pickIconFirst' => __('Choose an icon first.', TEXTDOMAIN),
            'lockedBadge' => __('Default', TEXTDOMAIN),
            'readOnlyHint' => __('This is a default theme icon. It is read-only — you can download it, but not rename or delete it.', TEXTDOMAIN),
            'editTitle' => __('Edit icon', TEXTDOMAIN),
            'viewTitle' => __('Icon', TEXTDOMAIN),
        ),
    );

}

function get_managed_icons_view_context(){

    $sprite_url = get_managed_icons_sprite_url();
    $icons = get_managed_icons();

    foreach($icons as $key => $icon){
        $icons[$key]['display_id'] = get_managed_icon_display_id($icon['id']);
    }

    return array(
        'icons' => $icons,
        'sprite_url' => $sprite_url,
        'labels' => array(
            'title' => __('Icons', TEXTDOMAIN),
            'empty' => __('No icons found in the sprite yet.', TEXTDOMAIN),
            'close' => __('Close', TEXTDOMAIN),
            'edit' => __('Edit icon', TEXTDOMAIN),
            'view' => __('Icon', TEXTDOMAIN),
            'icon_id' => __('Icon name', TEXTDOMAIN),
            'description' => __('This name is used as the icon identifier.', TEXTDOMAIN),
            'save' => __('Save', TEXTDOMAIN),
            'download' => __('Download SVG', TEXTDOMAIN),
            'delete' => __('Delete', TEXTDOMAIN),
            'bulk_delete' => __('Bulk delete', TEXTDOMAIN),
            'delete_selected' => __('Delete selected', TEXTDOMAIN),
            'cancel_selection' => __('Cancel selection', TEXTDOMAIN),
            'upload' => __('Upload', TEXTDOMAIN),
            'import_title' => __('Import SVG file', TEXTDOMAIN),
            'import_summary' => __('This sprite contains %1$s icons. %2$s will be added and %3$s skipped because of name conflicts.', TEXTDOMAIN),
            'import_invalid' => __('%s icons have invalid names and will be skipped.', TEXTDOMAIN),
            'fit_viewbox' => __('Fit artwork to viewBox bounds', TEXTDOMAIN),
            'current_color' => __('Convert fills to currentColor', TEXTDOMAIN),
            'continue_import' => __('Continue import', TEXTDOMAIN),
            'cancel' => __('Cancel', TEXTDOMAIN),
            'choose_selected' => __('Choose selected icon', TEXTDOMAIN),
            'edit_selected' => __('Edit selected icon', TEXTDOMAIN),
            'locked_badge' => __('Default', TEXTDOMAIN),
            'read_only_hint' => __('This is a default theme icon. It is read-only — you can download it, but not rename or delete it.', TEXTDOMAIN),
        ),
    );

}

function save_managed_icons_sprite($dom, $sprite_path){

    $symbols = $dom->getElementsByTagName('symbol');
    $symbol_blocks = array();

    foreach($symbols as $symbol){
        $symbol_xml = $dom->saveXML($symbol);

        if(false === $symbol_xml){
            continue;
        }

        $symbol_xml = trim($symbol_xml);
        $symbol_xml = preg_replace('/\R\s*\R+/', PHP_EOL, $symbol_xml);
        $symbol_xml = preg_replace_callback('/^( +)/m', function($matches){
            return str_repeat(' ', strlen($matches[1]) * 2);
        }, $symbol_xml);
        $symbol_xml = preg_replace('/^/m', '    ', $symbol_xml);
        $symbol_blocks[] = $symbol_xml;
    }

    $svg = '<svg style="display:none !important;" xmlns="http://www.w3.org/2000/svg">' . PHP_EOL . PHP_EOL;

    if($symbol_blocks){
        $svg .= implode(PHP_EOL . PHP_EOL, $symbol_blocks) . PHP_EOL . PHP_EOL;
    }

    $svg .= '</svg>' . PHP_EOL;

    return false !== file_put_contents($sprite_path, $svg);

}

function rename_managed_icon($old_id, $new_id){
    return update_managed_icon($old_id, $new_id);
}

function update_managed_icon($old_id, $new_id, $options = array()){

    $old_id = sanitize_text_field($old_id);
    $new_id = sanitize_icon_id($new_id);
    $options = get_managed_icons_import_options($options);

    if(!$old_id || !$new_id){
        return new WP_Error('empty_icon_id', __('Icon name cannot be empty.', TEXTDOMAIN));
    }

    if(in_array($old_id, get_locked_managed_icon_ids(), true)){
        return new WP_Error('icon_locked', __('Default icons are read-only and cannot be changed.', TEXTDOMAIN));
    }

    $sprite_path = get_managed_icons_sprite_path();

    if(!file_exists($sprite_path) || !is_writable($sprite_path)){
        return new WP_Error('sprite_not_writable', __('SVG sprite is not writable.', TEXTDOMAIN));
    }

    $dom = new DOMDocument();
    $dom->preserveWhiteSpace = false;
    $dom->formatOutput = true;

    libxml_use_internal_errors(true);
    $loaded = $dom->load($sprite_path);
    libxml_clear_errors();

    if(!$loaded){
        return new WP_Error('invalid_sprite', __('SVG sprite could not be read.', TEXTDOMAIN));
    }

    $symbols = $dom->getElementsByTagName('symbol');
    $target_symbol = null;

    foreach($symbols as $symbol){
        $symbol_id = $symbol->getAttribute('id');

        if($symbol_id === $new_id && $new_id !== $old_id){
            return new WP_Error('duplicate_icon_id', __('Icon name already exists.', TEXTDOMAIN));
        }

        if($symbol_id === $old_id){
            $target_symbol = $symbol;
        }
    }

    if(!$target_symbol){
        return new WP_Error('icon_not_found', __('Icon was not found in the sprite.', TEXTDOMAIN));
    }

    $has_changes = false;

    if($old_id !== $new_id){
        $target_symbol->setAttribute('id', $new_id);
        $has_changes = true;
    }

    if(!empty($options['viewBox'])){
        if((float) $options['crop_x'] !== 0.0 || (float) $options['crop_y'] !== 0.0){
            translate_svg_geometry($target_symbol, -1 * (float) $options['crop_x'], -1 * (float) $options['crop_y']);
        }

        $target_symbol->setAttribute('viewBox', $options['viewBox']);
        $has_changes = true;
    }

    if(!empty($options['current_color'])){
        apply_current_color_to_icon($target_symbol);
        $has_changes = true;
    }

    if(!$has_changes){
        return array(
            'id' => $new_id,
            'viewBox' => $target_symbol->getAttribute('viewBox'),
        );
    }

    if(!save_managed_icons_sprite($dom, $sprite_path)){
        return new WP_Error('sprite_save_failed', __('SVG sprite could not be saved.', TEXTDOMAIN));
    }

    increment_assets_version();

    return array(
        'id' => $new_id,
        'viewBox' => $target_symbol->getAttribute('viewBox'),
    );

}

function delete_managed_icon($icon_id){

    $icon_id = sanitize_text_field($icon_id);

    if(!$icon_id){
        return new WP_Error('empty_icon_id', __('Icon name cannot be empty.', TEXTDOMAIN));
    }

    if(in_array($icon_id, get_locked_managed_icon_ids(), true)){
        return new WP_Error('icon_locked', __('Default icons are read-only and cannot be deleted.', TEXTDOMAIN));
    }

    $result = delete_managed_icons(array($icon_id));

    if(is_wp_error($result)){
        return $result;
    }

    if(empty($result['deleted_ids'])){
        return new WP_Error('icon_not_found', __('Icon was not found in the sprite.', TEXTDOMAIN));
    }

    return true;

}

function delete_managed_icons($icon_ids){

    if(!is_array($icon_ids)){
        return new WP_Error('empty_icon_id', __('No icons selected.', TEXTDOMAIN));
    }

    $icon_ids = array_values(array_unique(array_filter(array_map(function($icon_id){
        return sanitize_text_field($icon_id);
    }, $icon_ids))));

    if(!$icon_ids){
        return new WP_Error('empty_icon_id', __('No icons selected.', TEXTDOMAIN));
    }

    // Never delete theme-default (locked) icons, even if the request asks for them.
    $locked_ids = get_locked_managed_icon_ids();
    $icon_ids = array_values(array_diff($icon_ids, $locked_ids));

    if(!$icon_ids){
        return new WP_Error('icon_locked', __('Default icons are read-only and cannot be deleted.', TEXTDOMAIN));
    }

    $sprite_path = get_managed_icons_sprite_path();

    if(!file_exists($sprite_path) || !is_writable($sprite_path)){
        return new WP_Error('sprite_not_writable', __('SVG sprite is not writable.', TEXTDOMAIN));
    }

    $dom = new DOMDocument();
    $dom->preserveWhiteSpace = false;
    $dom->formatOutput = true;

    libxml_use_internal_errors(true);
    $loaded = $dom->load($sprite_path);
    libxml_clear_errors();

    if(!$loaded){
        return new WP_Error('invalid_sprite', __('SVG sprite could not be read.', TEXTDOMAIN));
    }

    $deleted_ids = array();
    $symbols = iterator_to_array($dom->getElementsByTagName('symbol'));

    foreach($symbols as $symbol){
        $symbol_id = $symbol->getAttribute('id');

        if(in_array($symbol_id, $icon_ids, true)){
            $symbol->parentNode->removeChild($symbol);
            $deleted_ids[] = $symbol_id;
        }
    }

    if(!$deleted_ids){
        return new WP_Error('icons_not_found', __('Selected icons were not found in the sprite.', TEXTDOMAIN));
    }

    if(!save_managed_icons_sprite($dom, $sprite_path)){
        return new WP_Error('sprite_save_failed', __('SVG sprite could not be saved.', TEXTDOMAIN));
    }

    increment_assets_version();

    return array(
        'deleted_ids' => $deleted_ids,
        'deleted' => count($deleted_ids),
    );

}

function render_dashboard_icons_page(){

    $context = Timber::context();
    $context = array_merge($context, get_managed_icons_view_context());

    Timber::render('dashboard/icons.twig', $context);

}

function register_dashboard_icons_page(){
    add_submenu_page(
        'themes.php',
        __('Icons', TEXTDOMAIN),
        __('Icons', TEXTDOMAIN),
        'manage_options',
        'managed-icons',
        'render_dashboard_icons_page',
        61
    );
}
add_action('admin_menu', 'register_dashboard_icons_page', 20);
