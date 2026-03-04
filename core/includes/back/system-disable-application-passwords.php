<?php

if(!defined('ABSPATH')){exit;}

/** disable application passwords */
if(get_option('disable_application_passwords')){
    add_filter('wp_is_application_passwords_available', '__return_false');
}
