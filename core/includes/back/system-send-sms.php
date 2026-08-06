<?php

if(!defined('ABSPATH')){exit;}

/**
 * Send an SMS through the provider configured in the dashboard options
 * (sms_service_provider: sms_fly | turbo_sms). The recipient is normalized to
 * E164 via fix_phone_format(). Every attempt is logged as an sms-log post.
 * Returns the raw provider response, or false when not configured / invalid.
 *
 * Both providers go over HTTPS through the WP HTTP API, with certificate
 * verification on and a bounded timeout — a stalled SMS gateway must not hold
 * the page request open.
 */
function send_sms($recipient, $message){

    if(!$recipient || !$message || !($sms_service_provider = get_option('sms_service_provider'))){
        return false;
    }

    $recipient = fix_phone_format(trim(htmlspecialchars($recipient, ENT_QUOTES, 'UTF-8')));

    if(!$recipient){
        return false;
    }

    $response = '';
    $sms_alpha_name = '';

    if($sms_service_provider == 'sms_fly'){

        $sms_alpha_name = get_option('sms_fly_alpha_name');
        $sms_fly_api_key = get_option('sms_fly_api_key');

        if($sms_fly_api_key && $sms_alpha_name){
            $response = send_sms_request(
                'https://sms-fly.ua/api/v2/api.php',
                array(
                    'auth'   => array('key' => $sms_fly_api_key),
                    'action' => 'SENDMESSAGE',
                    'data'   => array(
                        'recipient' => $recipient,
                        'channels'  => array('sms'),
                        'sms'       => array(
                            'source' => $sms_alpha_name,
                            'ttl'    => 60,
                            'text'   => $message,
                        ),
                    ),
                )
            );
        }

    } elseif($sms_service_provider == 'turbo_sms'){

        $sms_alpha_name = get_option('turbo_sms_alpha_name');
        $turbo_sms_token = get_option('turbo_sms_token');

        if($turbo_sms_token && $sms_alpha_name){
            $response = send_sms_request(
                'https://api.turbosms.ua/message/send.json',
                array(
                    'recipients' => array($recipient),
                    'sms' => array(
                        'sender' => $sms_alpha_name,
                        'text'   => $message,
                    ),
                ),
                array('Authorization' => 'Bearer ' . $turbo_sms_token)
            );
        }

    }

    $sms_post = array(
        'post_type' => 'sms-log',
        'post_title' => $message,
        'post_content' => '',
        'post_status' => 'publish'
    );
    $log_id = wp_insert_post( $sms_post );

    if($log_id && !is_wp_error($log_id)){
        update_post_meta( $log_id, 'recipient', $recipient);
        update_post_meta( $log_id, 'sent_with', $sms_service_provider);
        update_post_meta( $log_id, 'sms_alpha_name', $sms_alpha_name);
        update_post_meta( $log_id, 'response', $response);
    }

    return $response;

}

/**
 * POST a JSON body to an SMS gateway and return the raw response body.
 * Returns a readable error string instead of throwing, so it can be logged
 * and shown in the "Send test" widget.
 */
function send_sms_request($url, $body, $headers = array()){

    $response = wp_remote_post($url, array(
        'timeout'     => 15,
        'sslverify'   => true,
        'headers'     => array_merge(array('Content-Type' => 'application/json'), $headers),
        'body'        => wp_json_encode($body),
        'data_format' => 'body',
    ));

    if(is_wp_error($response)){
        return $response->get_error_message();
    }

    return wp_remote_retrieve_body($response);

}
