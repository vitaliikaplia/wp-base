<?php

if(!defined('ABSPATH')){exit;}

/**
 * Encrypt and decrypt with openssl
 */
function custom_encrypt_decrypt($action, $string){
    $output = false;
    $encrypt_method = "AES-256-CBC";
    $secret_key = mb_strcut(NONCE_SALT, 0, 32, "UTF-8");
    $secret_iv = mb_strcut(NONCE_SALT, 0, 16, "UTF-8");
    // hash
    $key = hash('sha256', $secret_key);
    // iv - encrypt method AES-256-CBC expects 16 bytes
    $iv = substr(hash('sha256', $secret_iv), 0, 16);
    if ( $action == 'encrypt' ) {
        $output = openssl_encrypt($string, $encrypt_method, $key, 0, $iv);
        $output = base64_encode($output);
    } else if( $action == 'decrypt' ) {
        $output = openssl_decrypt(base64_decode($string), $encrypt_method, $key, 0, $iv);
    }
    return $output;
}

function custom_encrypt($string) {
    return custom_encrypt_decrypt('encrypt', $string);
}

function custom_decrypt($string) {
    return custom_encrypt_decrypt('decrypt', $string);
}
