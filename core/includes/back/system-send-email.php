<?php

if(!defined('ABSPATH')){exit;}

/**
 * Send a templated email and log it as a `mail-log` post.
 *
 * Pass `'language' => 'en'` to render the template and its translated strings in
 * a specific language — useful when the recipient's language differs from the
 * request's (cron, webhooks, admin-triggered notifications). Requires WP-LOC;
 * without it the parameter is simply ignored.
 */
function send_email($params = array()){
    if(!empty($params) && !empty($params['email']) && !empty($params['template'])){
        $previous_language = !empty($params['language']) ? theme_switch_language($params['language']) : null;
        $no_reply_mail = "no-reply@".BLOGINFO_JUST_DOMAIN;
        $subject = stripslashes($params['subject'] ?? '');
        $context = Timber::context();
        $context['TEXTDOMAIN'] = TEXTDOMAIN;
        $context['BLOGINFO_NAME'] = BLOGINFO_NAME;
        $context['BLOGINFO_URL'] = BLOGINFO_URL;
        $context['subject'] = $subject;
        $context['fields'] = $params['fields'] ?? array();
        $mail_html = Timber::compile( 'email/'.$params['template'].'.twig', $context);
        $headers  = "Content-type: text/html; charset=utf-8 \r\n";
        $headers .= "From: ".BLOGINFO_NAME." <".$no_reply_mail.">\r\n";
        $response = wp_mail($params['email'], $subject, $mail_html, $headers);
        $mail_post = array(
            'post_type' => 'mail-log',
            'post_title' => $subject,
            'post_content' => $mail_html,
            'post_status' => 'publish'
        );
        $log_id = wp_insert_post( $mail_post );

        if($log_id && !is_wp_error($log_id)){
            update_post_meta( $log_id, 'recipient', $params['email']);
        }

        if($previous_language !== null){
            theme_restore_language($previous_language);
        }

        return $response;
    }

    return false;
}

/**
 * The old `?send_test_email=1` trigger was removed: it had no nonce (any admin
 * could be made to fire it with a crafted link) and it hardcoded the theme
 * author's mailbox, so every fork mailed a stranger. Use the "Send test"
 * dashboard widget instead — see dashboard-widget-send-test.php, which is
 * capability- and nonce-checked and lets you pick the recipient.
 */
