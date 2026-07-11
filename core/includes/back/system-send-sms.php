<?php

if(!defined('ABSPATH')){exit;}

/**
 * Send an SMS through the provider configured in the dashboard options
 * (sms_service_provider: sms_fly | turbo_sms). The recipient is normalized to
 * E164 via fix_phone_format(). Every attempt is logged as an sms-log post.
 * Returns the raw provider response, or false when not configured / invalid.
 */
function send_sms($recipient, $message){

    if($recipient && $message && ($sms_service_provider = get_option('sms_service_provider')) ){

        $recipient = fix_phone_format(trim(htmlspecialchars($recipient, ENT_QUOTES, 'UTF-8')));
        if(!$recipient){
            return false;
        }

        $response = '';
        $sms_alpha_name = '';

        if ( $sms_service_provider == 'sms_fly' && ($sms_username = get_option('sms_fly_username')) && ($sms_password = get_option('sms_fly_password')) && ($sms_alpha_name = get_option('sms_fly_alpha_name')) ){

            $text = iconv('utf-8', 'utf-8', htmlspecialchars($message));
            $description = iconv('utf-8', 'utf-8', htmlspecialchars('Website notifications'));
            $start_time = 'AUTO';
            $end_time = 'AUTO';
            $rate = 1;
            $lifetime = 4;

            $myXML 	 = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n";
            $myXML 	.= "<request>";
            $myXML 	.= "<operation>SENDSMS</operation>";
            $myXML 	.= '		<message start_time="'.$start_time.'" end_time="'.$end_time.'" lifetime="'.$lifetime.'" rate="'.$rate.'" desc="'.$description.'" source="'.$sms_alpha_name.'">'."\n";
            $myXML 	.= "		<body>".$text."</body>";
            $myXML 	.= "		<recipient>".$recipient."</recipient>";
            $myXML 	.=  "</message>";
            $myXML 	.= "</request>";

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_USERPWD , $sms_username.':'.$sms_password);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_URL, 'http://sms-fly.com/api/api.php');
            curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: text/xml", "Accept: text/xml"));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $myXML);
            $response = curl_exec($ch);
            curl_close($ch);

        } elseif ( $sms_service_provider == 'turbo_sms' && ($turbo_sms_token = get_option('turbo_sms_token')) && ($sms_alpha_name = get_option('turbo_sms_alpha_name')) ){

            $url = 'https://api.turbosms.ua/message/send.json';

            $data = [
                'recipients' => [$recipient],
                'sms' => [
                    'sender' => $sms_alpha_name,
                    'text' => $message
                ]
            ];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $turbo_sms_token
            ]);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

            $response = curl_exec($ch);

            curl_close($ch);

        }

        $sms_post = array(
            'post_type' => 'sms-log',
            'post_title' => $message,
            'post_content' => '',
            'post_status' => 'publish'
        );
        $log_id = wp_insert_post( $sms_post );
        update_post_meta( $log_id, 'recipient', $recipient);
        update_post_meta( $log_id, 'sent_with', $sms_service_provider);
        update_post_meta( $log_id, 'sms_alpha_name', $sms_alpha_name);
        update_post_meta( $log_id, 'response', $response);

        return $response;

    }

    return false;

}
