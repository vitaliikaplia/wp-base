<?php

if(!defined('ABSPATH')){exit;}

/**
 * Raw-HTML ACF fields (currently the iFrame block's `html_code`).
 *
 * The block renders this value with Twig's |raw, so whatever is stored here is
 * executed verbatim in every visitor's browser. On a stock single-site install
 * that is fine — editors and admins hold `unfiltered_html` and can already put
 * script into post content. On multisite, or wherever a role plugin has taken
 * that capability away, it is a stored-XSS hole.
 *
 * So: users with `unfiltered_html` keep saving raw markup, everyone else gets
 * their input filtered down to embed-safe markup — <iframe> and friends stay,
 * <script>, event handlers and javascript: URIs do not.
 */

/** Field keys whose value is rendered unescaped. */
function raw_html_field_keys(){
    return apply_filters('wp_base_raw_html_field_keys', array(
        'field_6905b9f89f003', // Блок: iFrame → html_code
    ));
}

/** wp_kses whitelist: post markup plus the embed tags the iFrame block exists for. */
function raw_html_allowed_tags(){

    $allowed = wp_kses_allowed_html('post');

    $frame_attributes = array(
        'src'             => true,
        'width'           => true,
        'height'          => true,
        'title'           => true,
        'class'           => true,
        'id'              => true,
        'style'           => true,
        'name'            => true,
        'loading'         => true,
        'frameborder'     => true,
        'scrolling'       => true,
        'marginwidth'     => true,
        'marginheight'    => true,
        'allow'           => true,
        'allowfullscreen' => true,
        'referrerpolicy'  => true,
        'sandbox'         => true,
        'data-*'          => true,
        'aria-*'          => true,
    );

    $allowed['iframe'] = $frame_attributes;
    $allowed['video']  = $frame_attributes + array('controls' => true, 'autoplay' => true, 'muted' => true, 'loop' => true, 'poster' => true, 'playsinline' => true, 'preload' => true);
    $allowed['audio']  = $frame_attributes + array('controls' => true, 'autoplay' => true, 'muted' => true, 'loop' => true, 'preload' => true);
    $allowed['source'] = array('src' => true, 'type' => true, 'srcset' => true, 'media' => true);
    $allowed['picture'] = array('class' => true, 'id' => true, 'style' => true);

    return apply_filters('wp_base_raw_html_allowed_tags', $allowed);

}

/**
 * Filter a raw-HTML field on save unless the current user is trusted with
 * unfiltered markup. Runs before ACF writes the value to the database.
 */
add_filter('acf/update_value', function($value, $post_id, $field){

    if(empty($field['key']) || !in_array($field['key'], raw_html_field_keys(), true)){
        return $value;
    }

    if(!is_string($value) || $value === ''){
        return $value;
    }

    if(current_user_can('unfiltered_html')){
        return $value;
    }

    // ACF hands us slashed content; wp_kses must see the real characters.
    return wp_slash(wp_kses(wp_unslash($value), raw_html_allowed_tags()));

}, 10, 3);
