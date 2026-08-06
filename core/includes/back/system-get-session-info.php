<?php

if(!defined('ABSPATH')){exit;}

/**
 * Чи можна довіряти проксі-заголовкам з IP відвідувача.
 *
 * Вмикається опцією "Trust proxy IP headers" або константою TRUST_PROXY_HEADERS
 * у wp-config.php (константа має пріоритет).
 */
function trust_proxy_headers()
{
    if (defined('TRUST_PROXY_HEADERS')) {
        return (bool) TRUST_PROXY_HEADERS;
    }

    return (bool) get_option('trust_proxy_headers');
}

/**
 * IP відвідувача.
 *
 * REMOTE_ADDR — єдине значення, яке клієнт не може підробити. CF-Connecting-IP,
 * X-Forwarded-For і Client-IP — звичайні заголовки запиту: за Cloudflare вони
 * достовірні, а без проксі їх задає сам відвідувач. Тому вони читаються лише
 * коли довіру до проксі увімкнено явно — інакше будь-хто підставляє собі
 * довільний IP у логах, геолокації та обмеженнях за IP.
 */
function get_user_ip()
{
    $remote = !empty($_SERVER['REMOTE_ADDR']) && filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP)
        ? $_SERVER['REMOTE_ADDR']
        : '';

    if (!trust_proxy_headers()) {
        return $remote;
    }

    // Cloudflare перезаписує цей заголовок на кожному запиті — тож він головний
    if (!empty($_SERVER['HTTP_CF_CONNECTING_IP']) && filter_var($_SERVER['HTTP_CF_CONNECTING_IP'], FILTER_VALIDATE_IP)) {
        return $_SERVER['HTTP_CF_CONNECTING_IP'];
    }

    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        foreach (explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']) as $forwarded_ip) {
            $forwarded_ip = trim($forwarded_ip);

            if (filter_var($forwarded_ip, FILTER_VALIDATE_IP)) {
                return $forwarded_ip;
            }
        }
    }

    if (!empty($_SERVER['HTTP_CLIENT_IP']) && filter_var($_SERVER['HTTP_CLIENT_IP'], FILTER_VALIDATE_IP)) {
        return $_SERVER['HTTP_CLIENT_IP'];
    }

    return $remote;
}

function get_platform_info() {
    $u_agent = !empty($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
    $platform = 'Unknown';

    if (preg_match('/linux/i', $u_agent)) {
        $platform = 'linux';
    } elseif (preg_match('/macintosh|mac os x/i', $u_agent)) {
        $platform = 'mac';
    } elseif (preg_match('/windows|win32/i', $u_agent)) {
        $platform = 'windows';
    }

    return ucfirst($platform);
}

function get_browser_info() {
    $u_agent = !empty($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
    $bname = 'Unknown';

    if (preg_match('/MSIE/i', $u_agent) && !preg_match('/Opera/i', $u_agent)) {
        $bname = 'Internet Explorer';
    } elseif (preg_match('/Firefox/i', $u_agent)) {
        $bname = 'Mozilla Firefox';
    } elseif (preg_match('/Chrome/i', $u_agent)) {
        $bname = 'Google Chrome';
    } elseif (preg_match('/Safari/i', $u_agent)) {
        $bname = 'Apple Safari';
    } elseif (preg_match('/Opera/i', $u_agent)) {
        $bname = 'Opera';
    } elseif (preg_match('/Netscape/i', $u_agent)) {
        $bname = 'Netscape';
    }

    return ucfirst($bname);
}

function get_ip_info($ipAddress = false){

    if($ipAddress){

        if(!filter_var($ipAddress, FILTER_VALIDATE_IP)){
            return $ipAddress;
        }

        $is_public_ip = filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);

        if(!$is_public_ip){
            return 'Localhost ('.$ipAddress.')';
        } else {
            try {
                // Initialize the reader for country
                $countryReader = new GeoIp2\Database\Reader(CORE_PATH . DS . 'geo' . DS . 'country.mmdb');
                $countryRecord = $countryReader->country($ipAddress);

                // Initialize the reader for city
                $cityReader = new GeoIp2\Database\Reader(CORE_PATH . DS . 'geo' . DS . 'city.mmdb');
                $cityRecord = $cityReader->city($ipAddress);

                return $countryRecord->country->name . ', ' . $cityRecord->mostSpecificSubdivision->name . ', ' . $cityRecord->city->name . ' ('.$ipAddress.')';
            } catch (\Throwable $e) {
                return $ipAddress;
            }
        }

    }

}

function get_session_info($ip){
    return get_ip_info($ip) . ', ' . get_platform_info() . ', ' . get_browser_info();
}
