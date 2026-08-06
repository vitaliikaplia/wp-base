<?php

if(!defined('ABSPATH')){exit;}

// Remove the "Update" button from the post edit screen for custom post types
function remove_update_post_widget_cpt() {
    $custom_post_types = array('mail-log', 'sms-log');
    global $post_type;
    if(in_array($post_type, $custom_post_types)) {
        remove_meta_box('submitdiv', $post_type, 'side');
    }
}
add_action('do_meta_boxes', 'remove_update_post_widget_cpt');

// Set single column layout for custom post types
function set_single_column_layout_cpt($columns) {
    $columns['mail-log'] = 1; // for your custom post type
    $columns['sms-log'] = 1;
    return $columns;
}
add_filter('screen_layout_columns', 'set_single_column_layout_cpt');
function set_screen_layout_cpt($selected) {
    return 1; // Set the number of columns
}
add_filter('get_user_option_screen_layout_mail-log', 'set_screen_layout_cpt');
add_filter('get_user_option_screen_layout_sms-log', 'set_screen_layout_cpt');

// This function allows administrators to preview the content of a mail log entry
function mail_log_preview(){

    if(is_user_logged_in() && current_user_can('manage_options') && !empty($_GET['secret-mail-log-preview']) && $_GET['secret-mail-log-preview'] && !empty($_GET['mail-log-id']) && $_GET['mail-log-id']){

        $mail_log_id = intval($_GET['mail-log-id']);

        // check if $mail_log_id is id of post type mail-log
        $post = get_post($mail_log_id);
        if(!$post || $post->post_type !== 'mail-log'){
            wp_die(__('Invalid mail log ID', TEXTDOMAIN));
        }

        // A logged email body can contain whatever a visitor typed into a form,
        // so it is untrusted markup rendered inside an authenticated session.
        // Serve it as an inert document: no scripts, no sniffing, no framing
        // by anyone but us. The meta box additionally loads it sandboxed.
        header('Content-Type: text/html; charset=' . get_bloginfo('charset'));
        header('X-Content-Type-Options: nosniff');
        header("Content-Security-Policy: sandbox; default-src 'none'; img-src * data:; style-src 'unsafe-inline'; font-src *");
        header('X-Robots-Tag: noindex, nofollow');

        echo $post->post_content;

        exit;

    }

}
add_action('init', 'mail_log_preview');

// Add custom meta boxes for the mail log post type
function add_custom_email_log_meta_box() {
    add_meta_box(
        'custom-email-log-information-fields-meta-box',
        __('Email log information', TEXTDOMAIN),
        'email_log_render_custom_fields',
        'mail-log',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'add_custom_email_log_meta_box');

// Render custom fields for the mail log meta box
function email_log_render_custom_fields($post) {
    $context = Timber::context();
    $context['log_id'] = $post->ID;
    $context['recipient'] = get_post_meta($post->ID, 'recipient', true);
    Timber::render( 'dashboard/email-log-meta.twig', $context );
}

// Add a second meta box for email preview
function add_custom_email_log_meta_box_2() {
    add_meta_box(
        'custom-email-log-information-fields-meta-box-2',
        __('Email preview', TEXTDOMAIN),
        'email_log_preview_custom',
        'mail-log',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'add_custom_email_log_meta_box_2');

// Render the email preview in an iframe.
// sandbox="" (empty value = every restriction on) keeps the logged markup from
// running scripts or touching the admin session it is displayed in.
function email_log_preview_custom($post) {
    $preview_url = home_url('/?secret-mail-log-preview=true&mail-log-id=' . (int) $post->ID);
    echo '<iframe class="mailPreview" style="width: 100%;" sandbox="" referrerpolicy="no-referrer" src="' . esc_url($preview_url) . '"></iframe>';
}

// Add custom columns to the mail log post type in the admin dashboard
add_filter('manage_mail-log_posts_columns', 'add_mail_log_custom_columns');
function add_mail_log_custom_columns($columns) {
    // Вставимо наші колонки після заголовку
    $new_columns = [];

    foreach ($columns as $key => $value) {
        $new_columns[$key] = $value;

        if ($key === 'title') {
            $new_columns['recipient'] = __('Recipient', TEXTDOMAIN);
        }
    }

    return $new_columns;
}

// Render custom columns for the mail log post type
add_action('manage_mail-log_posts_custom_column', 'render_mail_log_custom_columns', 10, 2);
function render_mail_log_custom_columns($column, $post_id) {
    switch ($column) {
        case 'recipient':
            echo esc_html(get_post_meta($post_id, 'recipient', true));
            break;
    }
}
