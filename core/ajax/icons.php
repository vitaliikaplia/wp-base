<?php

if(!defined('ABSPATH')){exit;}

function ajax_rename_managed_icon(){

    if(!current_user_can('manage_options')){
        wp_send_json_error(array(
            'message' => __('You are not allowed to manage icons.', TEXTDOMAIN),
        ), 403);
    }

    check_ajax_referer('icons', 'nonce');

    $old_id = isset($_POST['old_id']) ? wp_unslash($_POST['old_id']) : '';
    $new_id = isset($_POST['new_id']) ? wp_unslash($_POST['new_id']) : '';
    $options = array(
        'fit_viewbox' => !empty($_POST['fit_viewbox']),
        'current_color' => !empty($_POST['current_color']),
        'viewBox' => isset($_POST['viewBox']) ? wp_unslash($_POST['viewBox']) : '',
    );
    $result = update_managed_icon($old_id, $new_id, $options);

    if(is_wp_error($result)){
        wp_send_json_error(array(
            'message' => $result->get_error_message(),
        ), 400);
    }

    $new_id = sanitize_icon_id($new_id);

    wp_send_json_success(array(
        'id' => $new_id,
        'viewBox' => is_array($result) && isset($result['viewBox']) ? $result['viewBox'] : '',
        'spriteUrl' => get_managed_icons_sprite_url(),
        'message' => __('Icon updated.', TEXTDOMAIN),
    ));

}
add_action('wp_ajax_rename_managed_icon', 'ajax_rename_managed_icon');

function ajax_delete_managed_icon(){

    if(!current_user_can('manage_options')){
        wp_send_json_error(array(
            'message' => __('You are not allowed to manage icons.', TEXTDOMAIN),
        ), 403);
    }

    check_ajax_referer('icons', 'nonce');

    $icon_id = isset($_POST['icon_id']) ? wp_unslash($_POST['icon_id']) : '';
    $result = delete_managed_icon($icon_id);

    if(is_wp_error($result)){
        wp_send_json_error(array(
            'message' => $result->get_error_message(),
        ), 400);
    }

    wp_send_json_success(array(
        'spriteUrl' => get_managed_icons_sprite_url(),
        'message' => __('Icon deleted.', TEXTDOMAIN),
    ));

}
add_action('wp_ajax_delete_managed_icon', 'ajax_delete_managed_icon');

function ajax_delete_managed_icons(){

    if(!current_user_can('manage_options')){
        wp_send_json_error(array(
            'message' => __('You are not allowed to manage icons.', TEXTDOMAIN),
        ), 403);
    }

    check_ajax_referer('icons', 'nonce');

    $icon_ids = isset($_POST['icon_ids']) ? wp_unslash($_POST['icon_ids']) : array();

    if(!is_array($icon_ids)){
        $decoded_icon_ids = json_decode($icon_ids, true);
        $icon_ids = is_array($decoded_icon_ids) ? $decoded_icon_ids : array();
    }

    $result = delete_managed_icons($icon_ids);

    if(is_wp_error($result)){
        wp_send_json_error(array(
            'message' => $result->get_error_message(),
        ), 400);
    }

    $deleted = (int) $result['deleted'];

    wp_send_json_success(array(
        'deletedIds' => $result['deleted_ids'],
        'spriteUrl' => get_managed_icons_sprite_url(),
        'message' => sprintf(
            _n('%s icon deleted.', '%s icons deleted.', $deleted, TEXTDOMAIN),
            number_format_i18n($deleted)
        ),
    ));

}
add_action('wp_ajax_delete_managed_icons', 'ajax_delete_managed_icons');

function ajax_analyze_managed_icons_import(){

    if(!current_user_can('manage_options')){
        wp_send_json_error(array(
            'message' => __('You are not allowed to manage icons.', TEXTDOMAIN),
        ), 403);
    }

    check_ajax_referer('icons', 'nonce');

    $svg_content = isset($_POST['svg_content']) ? wp_unslash($_POST['svg_content']) : '';
    $source_name = isset($_POST['source_name']) ? wp_unslash($_POST['source_name']) : '';
    $analysis = analyze_managed_icons_import($svg_content, $source_name);

    if(is_wp_error($analysis)){
        wp_send_json_error(array(
            'message' => $analysis->get_error_message(),
        ), 400);
    }

    wp_send_json_success($analysis);

}
add_action('wp_ajax_analyze_managed_icons_import', 'ajax_analyze_managed_icons_import');

function ajax_import_managed_icons_sprite(){

    if(!current_user_can('manage_options')){
        wp_send_json_error(array(
            'message' => __('You are not allowed to manage icons.', TEXTDOMAIN),
        ), 403);
    }

    check_ajax_referer('icons', 'nonce');

    $svg_content = isset($_POST['svg_content']) ? wp_unslash($_POST['svg_content']) : '';
    $source_name = isset($_POST['source_name']) ? wp_unslash($_POST['source_name']) : '';
    $import_options = array();

    if(isset($_POST['import_options'])){
        $decoded_options = json_decode(wp_unslash($_POST['import_options']), true);

        if(is_array($decoded_options)){
            $import_options = $decoded_options;
        }
    }

    $result = import_managed_icons($svg_content, $source_name, $import_options);

    if(is_wp_error($result)){
        wp_send_json_error(array(
            'message' => $result->get_error_message(),
        ), 400);
    }

    wp_send_json_success(array(
        'icons' => $result['icons'],
        'stats' => $result['stats'],
        'spriteUrl' => get_managed_icons_sprite_url(),
        'message' => $result['stats']['total'] === 1 ? __('SVG icon imported.', TEXTDOMAIN) : __('SVG sprite imported.', TEXTDOMAIN),
    ));

}
add_action('wp_ajax_import_managed_icons_sprite', 'ajax_import_managed_icons_sprite');
