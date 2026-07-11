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
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        $result = curl_exec($ch);
        curl_close($ch);
        return json_decode($result, true);
    }
    return false;
}
