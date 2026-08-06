<?php

if(!defined('ABSPATH')){exit;}

/** debug log */
function write_log( $data ) {
	if ( true === WP_DEBUG ) {
		if ( is_array( $data ) || is_object( $data ) ) {
			error_log( print_r( $data, true ) );
		} else {
			error_log( $data );
		}
	}
}

/**
 * Custom print_r function.
 *
 * Debug-only: it is also exposed to Twig as the |pr filter, so without this
 * guard a stray {{ something|pr }} left in a template would dump internals —
 * potentially including option values and user data — onto a live page.
 * The dumped value is escaped so it cannot close the textarea and inject markup.
 */
function pr($var){
    if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
        return;
    }
    echo "<textarea style='position: fixed; border: none; padding: 10px; opacity: 1; bottom:0; left:0; z-index:999999999; display: block; width: 100%;height: 20%;overflow: auto; resize: none; background-color:#4b4b4b; color: #fff; border-top: solid 2px black;' onclick='$(this).select(); console.clear(); console.log($(this).val())'>";
    echo esc_html( print_r( $var, true ) );
    echo "</textarea>";
}

// Showing all hooks
//$debug_tags = array();
//add_action( 'all', function ( $tag ) {
//    global $debug_tags;
//    if ( in_array( $tag, $debug_tags ) ) {
//        return;
//    }
//    echo "<pre>" . $tag . "</pre>";
//    $debug_tags[] = $tag;
//} );
