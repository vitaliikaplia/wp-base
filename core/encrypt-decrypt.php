<?php

if(!defined('ABSPATH')){exit;}

/**
 * Encrypt and decrypt with openssl.
 *
 * Used for connector secrets stored in wp_options. AES-256-CBC with a random IV
 * per message and an encrypt-then-MAC signature, so identical plaintexts no
 * longer produce identical ciphertexts and a tampered value is rejected instead
 * of silently decrypting into attacker-influenced bytes.
 *
 * Format: "v2:" . base64( hmac-sha256 (32B) | iv (16B) | ciphertext )
 *
 * Values written by the previous implementation (static IV derived from
 * NONCE_SALT, no MAC) still decrypt — see custom_decrypt_legacy(). They move to
 * the new format the next time they are re-encrypted and saved.
 */

define('CUSTOM_ENCRYPT_METHOD', 'AES-256-CBC');
define('CUSTOM_ENCRYPT_PREFIX', 'v2:');

/** Keys derive from the site salts, so ciphertext is not portable between installs. */
function custom_encrypt_keys(){
    $salt = defined('NONCE_SALT') ? NONCE_SALT : '';
    $auth = defined('AUTH_SALT') ? AUTH_SALT : $salt;

    return array(
        'cipher' => hash('sha256', 'wp-base-cipher|' . $salt, true),
        'mac'    => hash('sha256', 'wp-base-mac|' . $auth, true),
    );
}

function custom_encrypt($string) {

    $string = (string) $string;

    if($string === ''){
        return '';
    }

    $keys = custom_encrypt_keys();
    $iv = random_bytes(openssl_cipher_iv_length(CUSTOM_ENCRYPT_METHOD));
    $ciphertext = openssl_encrypt($string, CUSTOM_ENCRYPT_METHOD, $keys['cipher'], OPENSSL_RAW_DATA, $iv);

    if($ciphertext === false){
        return false;
    }

    $payload = $iv . $ciphertext;
    $mac = hash_hmac('sha256', $payload, $keys['mac'], true);

    return CUSTOM_ENCRYPT_PREFIX . base64_encode($mac . $payload);

}

function custom_decrypt($string) {

    $string = (string) $string;

    if($string === ''){
        return '';
    }

    if(strpos($string, CUSTOM_ENCRYPT_PREFIX) !== 0){
        return custom_decrypt_legacy($string);
    }

    $decoded = base64_decode(substr($string, strlen(CUSTOM_ENCRYPT_PREFIX)), true);

    if($decoded === false){
        return false;
    }

    $iv_length = openssl_cipher_iv_length(CUSTOM_ENCRYPT_METHOD);

    if(strlen($decoded) <= 32 + $iv_length){
        return false;
    }

    $keys = custom_encrypt_keys();
    $mac = substr($decoded, 0, 32);
    $payload = substr($decoded, 32);

    // Verify before decrypting: a modified ciphertext must fail loudly.
    if(!hash_equals(hash_hmac('sha256', $payload, $keys['mac'], true), $mac)){
        return false;
    }

    return openssl_decrypt(
        substr($payload, $iv_length),
        CUSTOM_ENCRYPT_METHOD,
        $keys['cipher'],
        OPENSSL_RAW_DATA,
        substr($payload, 0, $iv_length)
    );

}

/** Read values written before the v2 format (static IV, no MAC). */
function custom_decrypt_legacy($string) {

    if(!defined('NONCE_SALT')){
        return false;
    }

    $key = hash('sha256', mb_strcut(NONCE_SALT, 0, 32, "UTF-8"));
    $iv = substr(hash('sha256', mb_strcut(NONCE_SALT, 0, 16, "UTF-8")), 0, 16);

    return openssl_decrypt(base64_decode($string), CUSTOM_ENCRYPT_METHOD, $key, 0, $iv);

}

/**
 * Kept for call sites that pass the action as an argument.
 * New code should call custom_encrypt() / custom_decrypt() directly.
 */
function custom_encrypt_decrypt($action, $string){
    return $action === 'encrypt' ? custom_encrypt($string) : custom_decrypt($string);
}
