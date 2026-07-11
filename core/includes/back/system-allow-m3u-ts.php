<?php

if(!defined('ABSPATH')){exit;}

if(get_option('allow_m3u_ts_upload')){

    function m3u_ts_mime_types($mimes) {
        $mimes['ts'] = 'video/mp2t';
        $mimes['m3u8'] = 'text/plain';
        $mimes['m3u'] = 'text/plain';
        return $mimes;
    }
    add_filter('upload_mimes', 'm3u_ts_mime_types');

    /*
     * upload_mimes alone isn't enough: since WP 5.0.1 the uploader also sniffs the real
     * file content (finfo) and, if it doesn't match the extension's declared mime, blanks
     * ext/type and rejects the upload ("you are not allowed to upload this file type").
     * libmagic doesn't recognise HLS playlists / MPEG-TS segments, so it returns an empty
     * or mismatching mime and these get blocked. Re-assert ext/type by extension here so
     * the sniff can't veto them. 5th arg ($real_mime) exists since WP 5.1 — default-guard it.
     */
    function m3u_ts_check_filetype($info, $file, $filename, $mimes, $real_mime = '') {
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if ($ext === 'm3u8' || $ext === 'm3u') {
            $info['ext']  = $ext;
            $info['type'] = 'text/plain';
        } elseif ($ext === 'ts') {
            $info['ext']  = 'ts';
            $info['type'] = 'video/mp2t';
        }
        return $info;
    }
    add_filter('wp_check_filetype_and_ext', 'm3u_ts_check_filetype', 10, 5);

}
