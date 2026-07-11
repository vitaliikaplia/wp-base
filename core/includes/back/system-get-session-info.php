<?php

if(!defined('ABSPATH')){exit;}

function get_user_ip()
{
    // Get real visitor IP behind CloudFlare network
    if (isset($_SERVER["HTTP_CF_CONNECTING_IP"])) {
        $_SERVER['REMOTE_ADDR'] = $_SERVER["HTTP_CF_CONNECTING_IP"];
        $_SERVER['HTTP_CLIENT_IP'] = $_SERVER["HTTP_CF_CONNECTING_IP"];
    }
    $client  = @$_SERVER['HTTP_CLIENT_IP'];
    $forward = @$_SERVER['HTTP_X_FORWARDED_FOR'];
    $remote  = !empty($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';

    if(filter_var($client, FILTER_VALIDATE_IP))
    {
        $ip = $client;
    }
    elseif($forward)
    {
        $forwarded_ips = explode(',', $forward);
        $forwarded_ip = trim($forwarded_ips[0]);
        $ip = filter_var($forwarded_ip, FILTER_VALIDATE_IP) ? $forwarded_ip : $remote;
    }
    else
    {
        $ip = $remote;
    }

    return $ip;
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
