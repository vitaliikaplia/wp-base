<?php

if(!defined('ABSPATH')){exit;}

/**
 * Simple MCP — what the theme has to know about the plugin.
 *
 * https://github.com/vitaliikaplia/simple-mcp
 *
 * The theme's tools for AI agents are plain WordPress abilities (see
 * system-abilities.php) and need nothing from the plugin. This file only keeps
 * the plugin's own doors open while the site is in maintenance mode.
 */

/**
 * Is this request for Simple MCP: its endpoint, its OAuth doors or the OAuth
 * discovery documents?
 *
 * Maintenance mode answers every anonymous request with wp_die() as soon as the
 * theme loads. MCP calls carry a Bearer key or an OAuth token, not a login
 * cookie, so without this exception an agent could switch maintenance mode on
 * and never reach the site again to switch it off. Simple MCP itself still
 * authenticates every call — this only lets the request get that far.
 *
 * For these paths init.php does not die at once: it moves the maintenance
 * check to do_parse_request at priority 1. Simple MCP answers there at
 * priority 0 and exits, so whatever it does not take (OAuth switched off, a
 * GET without a key) still gets the maintenance page, not the site.
 *
 * Paths mirror the plugin's router: `<endpoint>`, `<endpoint>/oauth/{register,
 * token,authorize}`, `/.well-known/oauth-*`, `/.well-known/openid-configuration`
 * and the default doors (POST /register, POST /token, GET /authorize?client_id=…)
 * for clients that skip the discovery metadata.
 */
function is_simple_mcp_request(){

    if(!class_exists('Simple_MCP')){
        return false;
    }

    $options  = get_option('simple_mcp_options');
    $endpoint = trim((string) (is_array($options) && isset($options['path']) ? $options['path'] : 'simple-mcp'), '/');

    // порожній шлях збігся б із головною сторінкою
    if($endpoint === ''){
        return false;
    }

    $uri  = isset($_SERVER['REQUEST_URI']) ? wp_parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) : '';
    $path = trim(is_string($uri) ? $uri : '', '/');
    $home = wp_parse_url(home_url(), PHP_URL_PATH);
    $home = is_string($home) ? trim($home, '/') : '';

    // сайт у підкаталозі: лише цілий сегмент, як у самого плагіна
    if($home !== ''){
        if($path === $home){
            $path = '';
        } elseif(strpos($path, $home . '/') === 0){
            $path = trim(substr($path, strlen($home) + 1), '/');
        }
    }

    if($path === $endpoint || strpos($path, $endpoint . '/oauth/') === 0){
        return true;
    }

    if(strpos($path, '.well-known/oauth-') === 0 || $path === '.well-known/openid-configuration'){
        return true;
    }

    $method = isset($_SERVER['REQUEST_METHOD']) ? strtoupper((string) $_SERVER['REQUEST_METHOD']) : 'GET';

    if(in_array($path, array('register', 'token'), true) && $method === 'POST'){
        return true;
    }

    return $path === 'authorize' && $method === 'GET' && isset($_GET['client_id'], $_GET['response_type']);

}
