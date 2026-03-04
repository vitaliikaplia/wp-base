<?php

if(!defined('ABSPATH')){exit;}

add_action( 'phpmailer_init', 'smtp_fix_phpmailer_init' );

function smtp_fix_phpmailer_init( $phpmailer ) {

    if ( ! get_option('enable_custom_smtp_server') ) {
        return;
    }

    $host       = get_option('smtp_host');
    $port       = (int) get_option('smtp_port');
    $user       = get_option('smtp_username');
    $pass       = get_option('smtp_password');
    $from_email = get_option('smtp_from_email');
    $from_name  = get_option('smtp_from_name');
    $secure     = get_option('smtp_secure');

    if ( ! $host || ! $port ) {
        return;
    }

    $phpmailer->isSMTP();
    $phpmailer->Host = $host;
    $phpmailer->Port = $port;

    // Аутентифікація тільки якщо є логін і пароль
    if ( $user && $pass ) {
        $phpmailer->SMTPAuth = true;
        $phpmailer->Username = $user;
        $phpmailer->Password = $pass;
    } else {
        $phpmailer->SMTPAuth = false;
    }

    // Сумісність зі старим чекбоксом: '1' → визначаємо по порту
    if ( $secure === '1' ) {
        $secure = ( $port === 465 ) ? 'ssl' : 'tls';
    }

    // Шифрування: за вибором в опціях (ssl / tls / none)
    if ( in_array( $secure, array( 'ssl', 'tls' ), true ) ) {
        $phpmailer->SMTPSecure = $secure;
        $phpmailer->SMTPAutoTLS = false;
    } else {
        $phpmailer->SMTPSecure = '';
        $phpmailer->SMTPAutoTLS = false;
    }

    // Для локальних серверів вимикаємо верифікацію сертифікатів
    if ( in_array( $host, array( '127.0.0.1', 'localhost', '0.0.0.0' ), true ) ) {
        $phpmailer->SMTPOptions = array(
            'ssl' => array(
                'verify_peer'       => false,
                'verify_peer_name'  => false,
                'allow_self_signed' => true,
            ),
        );
    }

    // Відправник: smtp_from_email > smtp_username > default
    $sender = $from_email ? $from_email : ( $user ? $user : '' );
    if ( $sender ) {
        $phpmailer->setFrom( $sender, $from_name ? $from_name : '' );
        $phpmailer->Sender = $sender;
    }

    // Логування тільки в режимі дебагу
    if ( defined('WP_DEBUG') && WP_DEBUG ) {
        $phpmailer->SMTPDebug = 2;
        $phpmailer->Debugoutput = function($str, $level) {
            error_log( "SMTP DEBUG: " . trim($str) );
        };
    }
}

// Фільтри відправника
add_filter( 'wp_mail_from', function( $original_email ) {
    if ( get_option('enable_custom_smtp_server') ) {
        $from_email = get_option('smtp_from_email');
        return $from_email ? $from_email : ( get_option('smtp_username') ? get_option('smtp_username') : $original_email );
    }
    return $original_email;
} );

add_filter( 'wp_mail_from_name', function( $original_name ) {
    if ( get_option('enable_custom_smtp_server') ) {
        $from_name = get_option('smtp_from_name');
        return $from_name ? $from_name : $original_name;
    }
    return $original_name;
} );
