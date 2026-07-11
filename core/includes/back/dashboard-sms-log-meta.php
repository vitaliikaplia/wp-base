<?php

if(!defined('ABSPATH')){exit;}

function add_custom_sms_log_meta_box() {
    add_meta_box(
        'custom-sms-log-information-fields-meta-box',
        __('SMS log information', TEXTDOMAIN),
        'sms_log_render_custom_fields',
        'sms-log',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'add_custom_sms_log_meta_box');

function sms_log_render_custom_fields($post) {
    $sms_providers = array(
        'sms_fly' => __('SMS Fly', TEXTDOMAIN),
        'turbo_sms' => __('Turbo SMS', TEXTDOMAIN),
    );
    $context = Timber::context();
    $context['log_id'] = $post->ID;
    $context['recipient'] = get_post_meta($post->ID, 'recipient', true);
    $sent_with = get_post_meta($post->ID, 'sent_with', true);
    $context['sent_with'] = $sms_providers[$sent_with] ?? $sent_with;
    $context['sms_alpha_name'] = get_post_meta($post->ID, 'sms_alpha_name', true);
    $context['message'] = get_post($post->ID)->post_title;
    Timber::render( 'dashboard/sms-log-meta.twig', $context );
}

function add_custom_sms_log_meta_box_2() {
    add_meta_box(
        'custom-sms-log-information-fields-meta-box-2',
        __('SMS log information response', TEXTDOMAIN),
        'sms_log_response_render_custom',
        'sms-log',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'add_custom_sms_log_meta_box_2');

function sms_log_response_render_custom($post) {
    $sent_with = get_post_meta($post->ID, 'sent_with', true);
    $response = get_post_meta($post->ID, 'response', true);
    $json = '{}';

    if($sent_with == 'sms_fly'){
        $xml = $response ? simplexml_load_string($response) : false;
        $json = $xml ? json_encode($xml, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : wp_json_encode(array('response' => $response), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    } elseif ($sent_with == 'turbo_sms') {
        json_decode($response);
        $json = json_last_error() === JSON_ERROR_NONE ? $response : wp_json_encode(array('response' => $response), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    } elseif($response) {
        $json = wp_json_encode(array('response' => $response), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
    echo '<pre class="sms-log-response" style="white-space:pre-wrap;word-break:break-word;background:#f6f7f7;border:1px solid #dcdcde;border-radius:4px;padding:12px;margin:0;max-height:400px;overflow:auto;">' . esc_html($json) . '</pre>';
}

add_filter('manage_sms-log_posts_columns', 'add_sms_log_custom_columns');
function add_sms_log_custom_columns($columns) {
    $new_columns = [];

    foreach ($columns as $key => $value) {
        $new_columns[$key] = $value;

        if ($key === 'title') {
            $new_columns['recipient'] = __('Recipient', TEXTDOMAIN);
            $new_columns['sent_with'] = __('Sent with', TEXTDOMAIN);
            $new_columns['sms_alpha_name'] = __('SMS Alpha Name', TEXTDOMAIN);
        }
    }

    return $new_columns;
}

add_action('manage_sms-log_posts_custom_column', 'render_sms_log_custom_columns', 10, 2);
function render_sms_log_custom_columns($column, $post_id) {
    switch ($column) {
        case 'recipient':
            echo nice_phone_format(esc_html(get_post_meta($post_id, 'recipient', true)));
            break;

        case 'sent_with':
            $sent_with_key = get_post_meta($post_id, 'sent_with', true);
            $sms_providers = array(
                'sms_fly' => __('SMS Fly', TEXTDOMAIN),
                'turbo_sms' => __('Turbo SMS', TEXTDOMAIN),
            );
            echo esc_html($sms_providers[$sent_with_key] ?? $sent_with_key);
            break;

        case 'sms_alpha_name':
            echo esc_html(get_post_meta($post_id, 'sms_alpha_name', true));
            break;
    }
}
