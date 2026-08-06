<?php

if(!defined('ABSPATH')){exit;}

/**
 * Send a message through the Telegram bot configured in the dashboard options
 * (telegram_token + telegram_chat_id, optional telegram_chat_thread_id).
 * Returns the decoded Telegram API response, or false when not configured.
 */
function telegram_bot($message, $thread_id = null, $chat_id = null, $parse_mode = 'HTML') {
    if (
        ($telegram_bot_token = get_option('telegram_token')) &&
        ($default_chat_id = get_option('telegram_chat_id'))
    ) {
        $chat_id = $chat_id ?: $default_chat_id;
        // thread_id is optional — use the option if set, otherwise omit it entirely
        $thread_id = $thread_id ?: get_option('telegram_chat_thread_id');
        $url = "https://api.telegram.org/bot{$telegram_bot_token}/sendMessage";
        $params = [
            'chat_id' => $chat_id,
            'text' => $message,
            'parse_mode' => $parse_mode
        ];
        if (!empty($thread_id)) {
            $params['message_thread_id'] = $thread_id;
        }

        // WP HTTP API rather than raw curl: bounded timeout (a stalled Telegram
        // API must not hold the page open) and certificate verification on.
        $response = wp_remote_post($url, array(
            'timeout'   => 15,
            'sslverify' => true,
            'body'      => $params,
        ));

        if (is_wp_error($response)) {
            return array(
                'ok' => false,
                'description' => $response->get_error_message(),
            );
        }

        return json_decode(wp_remote_retrieve_body($response), true);
    }
    return false;
}
