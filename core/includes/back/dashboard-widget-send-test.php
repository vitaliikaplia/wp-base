<?php
/**
 * Dashboard widget — send-test panel.
 *
 * Lets an admin fire a real Email / SMS / Telegram message through the theme's
 * own senders (send_email() / send_sms() / telegram_bot()) straight from the
 * dashboard, to verify each channel is configured and working. The three forms
 * live in separate native WP tabs and submit over AJAX (admin-ajax.php), so the
 * page never reloads or jumps to the top — the result (and raw provider
 * response) is rendered in place. A no-JS fallback posts to admin-post.php.
 */

if(!defined('ABSPATH')){exit;}

/** register the widget — admins only */
add_action('wp_dashboard_setup', function(){
    if(!current_user_can('manage_options')){ return; }
    wp_add_dashboard_widget(
        'send_test',
        __('Send test — Email / SMS / Telegram', TEXTDOMAIN),
        'send_test_widget_render'
    );
});

/** render the widget */
function send_test_widget_render(){

    $uid    = get_current_user_id();
    $result = get_transient('send_test_result_' . $uid); // only set by the no-JS fallback
    if($result){ delete_transient('send_test_result_' . $uid); }

    $action    = esc_url(admin_url('admin-post.php'));
    $email_to  = esc_attr(get_option('admin_email'));
    $sms_ready = (bool) get_option('sms_service_provider');
    $tg_ready  = get_option('telegram_token') && get_option('telegram_chat_id');
    $nonce     = wp_nonce_field('send_test', '_wpnonce', false, false);

    $tabs = array(
        'email'    => esc_html__('Email', TEXTDOMAIN),
        'sms'      => esc_html__('SMS', TEXTDOMAIN),
        'telegram' => esc_html__('Telegram', TEXTDOMAIN),
    );
    $active = (!empty($result['channel']) && isset($tabs[$result['channel']])) ? $result['channel'] : 'email';

    // fit the native nav-tabs neatly inside the widget box (tabs, not links → no underline)
    echo '<style>
        #send_test .nav-tab-wrapper{ margin:0 0 16px; padding:0; border-bottom:1px solid #c3c4c7; }
        #send_test .nav-tab{ margin:0 4px -1px 0; padding:7px 12px; font-size:13px; line-height:1.4; text-decoration:none; box-shadow:none; }
        #send_test .nav-tab:hover, #send_test .nav-tab:focus{ text-decoration:none; box-shadow:none; }
        #send_test .st-panel input{ margin-bottom:0; }
    </style>';

    // ---- result area (filled by AJAX, or pre-filled by the no-JS fallback) ----
    echo '<div id="st-result">';
    if($result){ echo send_test_result_html($result); }
    echo '</div>';

    // ---- tab nav ----
    echo '<h2 class="nav-tab-wrapper">';
    foreach($tabs as $key => $label){
        echo '<a href="#" class="nav-tab' . ($key === $active ? ' nav-tab-active' : '') . '" data-st-tab="' . esc_attr($key) . '">' . $label . '</a>';
    }
    echo '</h2>';

    // ---- Email panel ----
    echo '<div class="st-panel" data-st-panel="email"' . ($active === 'email' ? '' : ' style="display:none;"') . '>';
    echo '<form method="post" action="' . $action . '">' . $nonce;
    echo '<input type="hidden" name="action" value="send_test"><input type="hidden" name="channel" value="email">';
    echo '<p style="margin:0 0 10px;"><input type="email" name="email" value="' . $email_to . '" class="widefat" placeholder="' . esc_attr__('Recipient email', TEXTDOMAIN) . '" required></p>';
    echo '<p style="margin:0;"><button type="submit" class="button button-primary">' . esc_html__('Send test email', TEXTDOMAIN) . '</button></p>';
    echo '</form></div>';

    // ---- SMS panel ----
    echo '<div class="st-panel" data-st-panel="sms"' . ($active === 'sms' ? '' : ' style="display:none;"') . '>';
    echo '<form method="post" action="' . $action . '">' . $nonce;
    echo '<input type="hidden" name="action" value="send_test"><input type="hidden" name="channel" value="sms">';
    if(!$sms_ready){ echo '<p style="margin:0 0 10px;color:#b32d2e;">' . esc_html__('No SMS provider is configured.', TEXTDOMAIN) . '</p>'; }
    echo '<p style="margin:0 0 10px;"><input type="text" name="phone" value="" class="widefat" placeholder="+380 __ ___-__-__" required></p>';
    echo '<p style="margin:0 0 10px;"><input type="text" name="message" value="' . esc_attr__('Test SMS from the website', TEXTDOMAIN) . '" class="widefat" required></p>';
    echo '<p style="margin:0;"><button type="submit" class="button button-primary"' . ($sms_ready ? '' : ' disabled') . '>' . esc_html__('Send test SMS', TEXTDOMAIN) . '</button></p>';
    echo '</form></div>';

    // ---- Telegram panel ----
    echo '<div class="st-panel" data-st-panel="telegram"' . ($active === 'telegram' ? '' : ' style="display:none;"') . '>';
    echo '<form method="post" action="' . $action . '">' . $nonce;
    echo '<input type="hidden" name="action" value="send_test"><input type="hidden" name="channel" value="telegram">';
    if(!$tg_ready){ echo '<p style="margin:0 0 10px;color:#b32d2e;">' . esc_html__('Telegram bot is not configured.', TEXTDOMAIN) . '</p>'; }
    echo '<p style="margin:0 0 10px;"><input type="text" name="message" value="' . esc_attr__('Test message from the website', TEXTDOMAIN) . '" class="widefat" required></p>';
    echo '<p style="margin:0;"><button type="submit" class="button button-primary"' . ($tg_ready ? '' : ' disabled') . '>' . esc_html__('Send test Telegram', TEXTDOMAIN) . '</button></p>';
    echo '</form></div>';

    // ---- tab switching + AJAX submit (no reload, no page jump) ----
    $fail = esc_js(__('Could not reach the server.', TEXTDOMAIN));
    echo '<script>(function(){
        var w = document.getElementById("send_test");
        if(!w){ return; }

        var tabs = w.querySelectorAll(".nav-tab-wrapper .nav-tab");
        tabs.forEach(function(tab){
            tab.addEventListener("click", function(e){
                e.preventDefault();
                var key = this.getAttribute("data-st-tab");
                tabs.forEach(function(t){ t.classList.remove("nav-tab-active"); });
                this.classList.add("nav-tab-active");
                w.querySelectorAll(".st-panel").forEach(function(p){
                    p.style.display = (p.getAttribute("data-st-panel") === key) ? "" : "none";
                });
            });
        });

        var box = w.querySelector("#st-result");
        function esc(s){ var d = document.createElement("div"); d.textContent = (s == null ? "" : String(s)); return d.innerHTML; }
        function render(r){
            if(!r){ box.innerHTML = ""; return; }
            var cls = r.ok ? "notice-success" : "notice-error";
            var lead = r.label ? "<strong>" + esc(r.label) + ":</strong> " : "";
            var h = "<div class=\"notice " + cls + " inline\" style=\"margin:0 0 14px;padding:8px 12px;\">";
            h += "<p style=\"margin:0;\">" + lead + esc(r.message) + "</p>";
            if(r.response){ h += "<pre style=\"margin:6px 0 0;max-height:120px;overflow:auto;white-space:pre-wrap;font-size:11px;background:#f6f7f7;padding:6px;border-radius:4px;\">" + esc(r.response) + "</pre>"; }
            h += "</div>";
            box.innerHTML = h;
        }

        w.querySelectorAll(".st-panel form").forEach(function(form){
            form.addEventListener("submit", function(e){
                e.preventDefault();
                var btn = form.querySelector("button[type=submit]");
                if(btn && btn.disabled){ return; }
                var orig = btn ? btn.innerHTML : "";
                if(btn){ btn.disabled = true; btn.style.opacity = ".6"; }
                fetch(window.ajaxurl, { method:"POST", credentials:"same-origin", body:new FormData(form) })
                    .then(function(res){ return res.json(); })
                    .then(function(j){ render(j && j.data ? j.data : j); })
                    .catch(function(){ render({ ok:false, message:"' . $fail . '" }); })
                    .then(function(){ if(btn){ btn.disabled = false; btn.style.opacity = ""; btn.innerHTML = orig; } });
            });
        });
    })();</script>';
}

/** build the result notice markup (shared by the no-JS render and is mirrored in JS) */
function send_test_result_html($result){
    $cls  = !empty($result['ok']) ? 'notice-success' : 'notice-error';
    $lead = !empty($result['label']) ? '<strong>' . esc_html($result['label']) . ':</strong> ' : '';
    $html = '<div class="notice ' . $cls . ' inline" style="margin:0 0 14px;padding:8px 12px;">';
    $html .= '<p style="margin:0;">' . $lead . esc_html($result['message']) . '</p>';
    if(!empty($result['response'])){
        $html .= '<pre style="margin:6px 0 0;max-height:120px;overflow:auto;white-space:pre-wrap;font-size:11px;background:#f6f7f7;padding:6px;border-radius:4px;">' . esc_html($result['response']) . '</pre>';
    }
    return $html . '</div>';
}

/** shared: run the requested test send and return the result array (reads $_POST) */
function send_test_run(){

    $channel = isset($_POST['channel']) ? sanitize_key($_POST['channel']) : '';
    $result  = array('ok' => false, 'channel' => $channel, 'label' => '', 'message' => '', 'response' => '');

    if($channel === 'email'){

        $result['label'] = __('Email', TEXTDOMAIN);
        $email = isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '';

        if(!is_email($email)){
            $result['message'] = __('Invalid email address.', TEXTDOMAIN);
        } else {
            // catch a wp_mail failure if one fires while this test is sending
            $mail_error = null;
            $capture = function($wp_error) use (&$mail_error){ $mail_error = $wp_error->get_error_message(); };
            add_action('wp_mail_failed', $capture);

            send_email(array(
                'email'    => $email,
                'subject'  => __('Test', TEXTDOMAIN),
                'template' => 'test',
                'fields'   => array(
                    'test'    => BLOGINFO_NAME,
                    'session' => (function_exists('get_session_info') && function_exists('get_user_ip')) ? get_session_info(get_user_ip()) : '',
                ),
            ));

            remove_action('wp_mail_failed', $capture);

            if($mail_error === null){
                $result['ok']      = true;
                $result['message'] = sprintf(__('Sent to %s — see the Mail log.', TEXTDOMAIN), $email);
            } else {
                $result['message']  = __('wp_mail reported an error.', TEXTDOMAIN);
                $result['response'] = $mail_error;
            }
        }

    } elseif($channel === 'sms'){

        $result['label'] = __('SMS', TEXTDOMAIN);
        $phone   = isset($_POST['phone'])   ? sanitize_text_field(wp_unslash($_POST['phone']))   : '';
        $message = isset($_POST['message']) ? sanitize_text_field(wp_unslash($_POST['message'])) : '';

        if(!$phone || !$message){
            $result['message'] = __('Phone and message are required.', TEXTDOMAIN);
        } elseif(!get_option('sms_service_provider')){
            $result['message'] = __('No SMS provider is configured.', TEXTDOMAIN);
        } else {
            $response = send_sms($phone, $message);
            $result['ok']       = !empty($response);
            $result['message']  = $result['ok']
                ? sprintf(__('Request sent via %s — see the SMS log.', TEXTDOMAIN), get_option('sms_service_provider'))
                : __('The provider returned an empty response.', TEXTDOMAIN);
            $result['response'] = is_string($response) ? $response : wp_json_encode($response);
        }

    } elseif($channel === 'telegram'){

        $result['label'] = __('Telegram', TEXTDOMAIN);
        $message = isset($_POST['message']) ? sanitize_text_field(wp_unslash($_POST['message'])) : '';

        if(!$message){
            $result['message'] = __('Message is required.', TEXTDOMAIN);
        } else {
            $response = telegram_bot($message);
            if($response === false){
                $result['message'] = __('Telegram bot is not configured.', TEXTDOMAIN);
            } elseif(!empty($response['ok'])){
                $result['ok']      = true;
                $result['message'] = __('Message delivered to the configured chat.', TEXTDOMAIN);
            } else {
                $result['message']  = __('Telegram API rejected the message.', TEXTDOMAIN);
                $result['response'] = isset($response['description']) ? $response['description'] : wp_json_encode($response);
            }
        }

    } else {
        $result['message'] = __('Unknown channel.', TEXTDOMAIN);
    }

    return $result;
}

/** AJAX endpoint (primary path — keeps the page in place) */
add_action('wp_ajax_send_test', function(){
    if(!current_user_can('manage_options')){ wp_send_json_error(array('message' => __('Permission denied', TEXTDOMAIN)), 403); }
    check_ajax_referer('send_test');
    wp_send_json_success(send_test_run());
});

/** no-JS fallback — post/redirect/get back to the dashboard */
add_action('admin_post_send_test', function(){
    if(!current_user_can('manage_options')){ wp_die(__('Permission denied', TEXTDOMAIN)); }
    check_admin_referer('send_test');
    set_transient('send_test_result_' . get_current_user_id(), send_test_run(), 60);
    wp_safe_redirect(admin_url('index.php'));
    exit;
});
