<?php

if(!defined('ABSPATH')){exit;}

/**
 * Shared SVG sanitizer.
 *
 * SVG is an XML document, not an image: it can carry <script>, event handlers,
 * javascript: URIs and animation that rewrites an href at runtime. Anything that
 * ends up inline in a page (icon sprite, ACF svg field, media-library upload)
 * goes through here first.
 *
 * Auto-loaded with the rest of core/includes/. Every file there is required
 * before any hook fires, so these functions are available to admin and frontend
 * code alike regardless of load order.
 *
 * Used by:
 *  - system-allow-svg.php     — cleans the temp file before WordPress stores it
 *  - render-svg-tag.php       — cleans an attachment before it is echoed inline
 *  - dashboard-icons.php      — cleans imported sprite symbols
 */

/** Elements that exist only to execute or embed something — always removed. */
function svg_blocked_elements(){
    return array(
        'script',
        'foreignobject',
        'iframe',
        'object',
        'embed',
        'handler',
        'listener',
        'audio',
        'video',
        'source',
    );
}

/** SMIL elements: harmless for geometry, dangerous when they retarget a link. */
function svg_animation_elements(){
    return array('animate', 'animatetransform', 'animatemotion', 'set', 'animatecolor');
}

/** Attributes that hold a URI and therefore need a scheme check. */
function svg_uri_attributes(){
    return array('href', 'xlink:href', 'src', 'action', 'formaction', 'xlink:role', 'xlink:arcrole', 'from', 'to', 'values', 'by');
}

/** URI schemes an SVG is allowed to point at. Anything else is dropped. */
function svg_allowed_uri_schemes(){
    return array('http', 'https', 'mailto', 'tel');
}

/**
 * Normalize an attribute value before testing it for a scheme: decode entities,
 * strip control characters and whitespace. "java\tscript:" and "&#106;avascript:"
 * both have to collapse to "javascript:" or the check is decorative.
 */
function svg_normalize_uri_value($value){
    $value = (string) $value;
    $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $value = preg_replace('/[\x00-\x20\x7F]/u', '', $value);

    return strtolower(trim((string) $value));
}

/** Is this attribute value a URI the document may keep? */
function svg_is_safe_uri_value($value){
    $normalized = svg_normalize_uri_value($value);

    if($normalized === ''){
        return true;
    }

    // Fragment ("#icon-id") and relative paths carry no scheme — nothing to abuse.
    if(strpos($normalized, '#') === 0 || !preg_match('/^([a-z0-9.+-]+):/', $normalized, $scheme_match)){
        return true;
    }

    $scheme = $scheme_match[1];

    // Inline raster images are fine; data:image/svg+xml and data:text/html are not,
    // because they nest a new document with its own script context.
    if($scheme === 'data'){
        return (bool) preg_match('#^data:image/(png|jpe?g|gif|webp);base64,#', $normalized);
    }

    return in_array($scheme, svg_allowed_uri_schemes(), true);
}

/** Does this inline style / <style> body try to execute something? */
function svg_style_is_dangerous($css){
    $normalized = svg_normalize_uri_value($css);

    return strpos($normalized, 'javascript:') !== false
        || strpos($normalized, 'expression(') !== false
        || strpos($normalized, '@import') !== false
        || strpos($normalized, 'behavior:') !== false;
}

/**
 * Recursively strip everything executable from a DOM node.
 * Operates in place; safe to call on a <symbol> being imported into a sprite.
 */
function sanitize_svg_node($node){

    if(!$node instanceof DOMNode){
        return;
    }

    $blocked   = svg_blocked_elements();
    $animation = svg_animation_elements();
    $uri_attrs = svg_uri_attributes();

    // Iterate over a snapshot: removing children mutates the live DOMNodeList.
    foreach(iterator_to_array($node->childNodes ?: array()) as $child){

        if($child->nodeType === XML_COMMENT_NODE || $child->nodeType === XML_PI_NODE){
            $node->removeChild($child);
            continue;
        }

        if($child->nodeType !== XML_ELEMENT_NODE){
            continue;
        }

        $tag = strtolower($child->localName ? $child->localName : $child->nodeName);

        if(in_array($tag, $blocked, true)){
            $node->removeChild($child);
            continue;
        }

        // SMIL that animates a link target can turn a static icon into a live
        // javascript: navigation. Geometry animation is left alone.
        if(in_array($tag, $animation, true)){
            $target = strtolower($child->getAttribute('attributeName'));

            if($target === 'href' || $target === 'xlink:href'){
                $node->removeChild($child);
                continue;
            }
        }

        if($tag === 'style' && svg_style_is_dangerous($child->textContent)){
            $node->removeChild($child);
            continue;
        }

        foreach(iterator_to_array($child->attributes ?: array()) as $attribute){
            $name  = strtolower($attribute->nodeName);
            $value = $attribute->nodeValue;

            // Event handlers: onload, onclick, onbegin (SMIL), …
            if(strpos($name, 'on') === 0){
                $child->removeAttribute($attribute->nodeName);
                continue;
            }

            if($name === 'style' && svg_style_is_dangerous($value)){
                $child->removeAttribute($attribute->nodeName);
                continue;
            }

            if(in_array($name, $uri_attrs, true) && !svg_is_safe_uri_value($value)){
                $child->removeAttribute($attribute->nodeName);
            }
        }

        sanitize_svg_node($child);

    }

}

/**
 * Sanitize a full SVG document.
 *
 * @param string $markup Raw SVG source.
 * @return string|false Cleaned markup, or false when the input is not parseable
 *                      SVG or declares XML entities (billion-laughs / XXE bait).
 */
function sanitize_svg_markup($markup){

    $markup = (string) $markup;

    if(trim($markup) === ''){
        return false;
    }

    // Entity declarations have no legitimate use in an icon and are the entry
    // point for entity-expansion and external-entity attacks. Refuse outright.
    if(stripos($markup, '<!ENTITY') !== false){
        return false;
    }

    $dom = new DOMDocument();
    $dom->preserveWhiteSpace = false;

    libxml_use_internal_errors(true);
    // No LIBXML_NOENT: entity substitution stays off.
    $loaded = $dom->loadXML($markup, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
    libxml_clear_errors();

    if(!$loaded || !$dom->documentElement){
        return false;
    }

    if(strtolower($dom->documentElement->localName ?: $dom->documentElement->nodeName) !== 'svg'){
        return false;
    }

    // Drop any DOCTYPE that survived parsing.
    foreach(iterator_to_array($dom->childNodes) as $child){
        if($child->nodeType === XML_DOCUMENT_TYPE_NODE){
            $dom->removeChild($child);
        }
    }

    // The root <svg> needs the same attribute treatment as its children.
    $root = $dom->documentElement;
    foreach(iterator_to_array($root->attributes ?: array()) as $attribute){
        $name = strtolower($attribute->nodeName);

        if(strpos($name, 'on') === 0){
            $root->removeAttribute($attribute->nodeName);
            continue;
        }

        if($name === 'style' && svg_style_is_dangerous($attribute->nodeValue)){
            $root->removeAttribute($attribute->nodeName);
        }
    }

    sanitize_svg_node($root);

    $clean = $dom->saveXML($root);

    return $clean !== false ? $clean : false;

}

/**
 * Sanitize an SVG file in place. Returns false when the file could not be read
 * or the contents are not usable SVG (caller decides whether that is fatal).
 */
function sanitize_svg_file($path){

    if(!$path || !is_readable($path)){
        return false;
    }

    $contents = file_get_contents($path);

    if($contents === false){
        return false;
    }

    $clean = sanitize_svg_markup($contents);

    if($clean === false){
        return false;
    }

    if($clean === $contents){
        return true;
    }

    return file_put_contents($path, $clean) !== false;

}
