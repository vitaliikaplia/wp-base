<?php

if(!defined('ABSPATH')){exit;}

/**
 * Initialize every ACF WYSIWYG editor immediately (delay = 0) so TinyMCE
 * renders on load instead of only after the field's tab/area is first clicked.
 * A global load_field filter avoids setting "delay": 0 on every field by hand.
 */
add_filter('acf/load_field/type=wysiwyg', function($field){
    $field['delay'] = 0;
    return $field;
});
