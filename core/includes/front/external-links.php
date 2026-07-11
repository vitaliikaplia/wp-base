<?php

if(!defined('ABSPATH')){exit;}

/**
 * Outbound-link interstitial (opt-in via the "external link page" option).
 *
 * When enabled, every external <a> in the rendered HTML is rewritten to point
 * at home_url('/?go=<encoded url>'). Hitting that URL shows a localized
 * "you are leaving our website" countdown page, then redirects. External links
 * also get target="_blank" and rel="noopener nofollow noindex".
 */
if(get_option('enable_external_link_page')){

    /** How many seconds the interstitial counts down before redirecting. */
    if(!defined('EXTERNAL_LINK_REDIRECT_SECONDS')){
        define('EXTERNAL_LINK_REDIRECT_SECONDS', 5);
    }

    /**
     * Is the link a DIRECT video link (playable in a lightbox)? Channels,
     * playlists and profiles are not — they go through the interstitial like
     * any other external link.
     */
    function is_direct_video_link($url){
        $host  = strtolower((string) wp_parse_url($url, PHP_URL_HOST));
        $host  = preg_replace('/^www\./', '', $host);
        $path  = (string) wp_parse_url($url, PHP_URL_PATH);
        $query = (string) wp_parse_url($url, PHP_URL_QUERY);

        if(!$host){
            return false;
        }

        // Direct video files (playable in a lightbox directly)
        if(preg_match('/\.(mp4|webm|ogv|mov|m4v)$/i', $path)){
            return true;
        }

        // YouTube: short links youtu.be/<id>
        if($host === 'youtu.be'){
            return (bool) preg_match('#^/[A-Za-z0-9_-]{6,}/?$#', $path);
        }

        // YouTube: watch?v=<id>, /shorts/<id>, /embed/<id>, /live/<id>
        if(in_array($host, array('youtube.com', 'm.youtube.com', 'youtube-nocookie.com'), true)){
            if($path === '/watch'){
                parse_str($query, $params);
                return !empty($params['v']);
            }
            return (bool) preg_match('#^/(embed|shorts|live)/[A-Za-z0-9_-]{6,}#', $path);
        }

        // Vimeo: numeric video id (optionally with a private hash)
        if($host === 'vimeo.com'){
            return (bool) preg_match('#^/\d+(/[a-f0-9]+)?/?$#', $path);
        }
        if($host === 'player.vimeo.com'){
            return (bool) preg_match('#^/video/\d+#', $path);
        }

        return false;
    }

    /**
     * Handle the ?go= redirect page — render the countdown interstitial.
     */
    add_action('template_redirect', function(){
        if(!isset($_GET['go']) || empty($_GET['go'])){
            return;
        }

        $url = esc_url_raw(urldecode($_GET['go']));

        // Validate the target URL
        if(!filter_var($url, FILTER_VALIDATE_URL)){
            wp_safe_redirect(home_url());
            exit;
        }

        // Never interstitial our own host — redirect straight through
        $site_host = wp_parse_url(home_url(), PHP_URL_HOST);
        $link_host = wp_parse_url($url, PHP_URL_HOST);
        if($link_host === $site_host){
            wp_redirect($url);
            exit;
        }

        $context = Timber::context();
        $context['redirect_url']     = esc_url($url);
        $context['redirect_host']    = $link_host;
        $context['redirect_seconds'] = EXTERNAL_LINK_REDIRECT_SECONDS;
        // marker class so the template can be centred between header and footer
        $context['body_class']       = trim(implode(' ', get_body_class()) . ' external-redirect');

        Timber::render('external-link.twig', $context, false);
        exit;
    });

    /**
     * Rewrite external links in the HTML output to pass through the interstitial.
     */
    if(
        !is_admin()
        && !wp_is_json_request()
        && !wp_doing_ajax()
        && !(defined('DOING_CRON') && DOING_CRON)
        && !(defined('XMLRPC_REQUEST') && XMLRPC_REQUEST)
        && !(defined('WP_CLI') && WP_CLI)
        && !(defined('REST_REQUEST') && REST_REQUEST)
    ){
        add_action('init', function(){
            ob_start('rewrite_external_links_callback');
        }, 0);
    }

    function rewrite_external_links_callback($html){
        if(empty($html)){
            return $html;
        }

        // Skip non-HTML responses (file downloads, streams, etc.)
        foreach(headers_list() as $header){
            if(stripos($header, 'content-type:') === 0 && stripos($header, 'text/html') === false){
                return $html;
            }
            if(stripos($header, 'content-disposition:') === 0){
                return $html;
            }
        }

        // Skip if content doesn't look like HTML
        $trimmed = ltrim($html);
        if(empty($trimmed) || $trimmed[0] !== '<'){
            return $html;
        }

        // Skip the interstitial page itself (avoid rewriting its own links)
        if(isset($_GET['go'])){
            return $html;
        }

        $site_host = wp_parse_url(home_url(), PHP_URL_HOST);

        // Match all <a> tags that carry an href
        $html = preg_replace_callback(
            '/<a\s([^>]*?)href=["\']([^"\']+)["\']([^>]*?)>/i',
            function($matches) use ($site_host){
                $before_href = $matches[1];
                $url         = $matches[2];
                $after_href  = $matches[3];

                $link_host   = wp_parse_url($url, PHP_URL_HOST);
                $link_scheme = strtolower((string) wp_parse_url($url, PHP_URL_SCHEME));

                // Only web links (http/https) go through the interstitial.
                // App deep-links (viber:, tg:, whatsapp:, mailto:, tel: ...) open
                // an app, not the site — leave them alone, since esc_url_raw on the
                // interstitial page strips protocols outside wp_allowed_protocols().
                if($link_scheme && !in_array($link_scheme, array('http', 'https'), true)){
                    return $matches[0];
                }

                // Skip internal links and anchors
                if(
                    !$link_host
                    || $link_host === $site_host
                    || strpos($url, '#') === 0
                ){
                    return $matches[0];
                }

                // Direct video links pass through untouched (so a lightbox can play
                // them); channels/playlists/profiles go through the interstitial
                $redirect_url = is_direct_video_link($url) ? $url : home_url('/?go=' . urlencode($url));

                // Reconstruct attributes, enforce target="_blank" and the rel set
                $attrs = $before_href . $after_href;

                if(stripos($attrs, 'target=') === false){
                    $attrs .= ' target="_blank"';
                }

                $required_rels = array('noopener', 'nofollow', 'noindex');
                if(preg_match('/rel=["\']([^"\']*)["\']/', $attrs, $rel_match)){
                    $rel_value = $rel_match[1];
                    foreach($required_rels as $rel_part){
                        if(stripos($rel_value, $rel_part) === false){
                            $rel_value .= ' ' . $rel_part;
                        }
                    }
                    $attrs = str_replace($rel_match[0], 'rel="' . trim($rel_value) . '"', $attrs);
                } else {
                    $attrs .= ' rel="' . implode(' ', $required_rels) . '"';
                }

                return '<a ' . trim($attrs) . ' href="' . esc_url($redirect_url) . '">';
            },
            $html
        );

        return $html;
    }

}
