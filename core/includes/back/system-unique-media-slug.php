<?php

if(!defined('ABSPATH')){exit;}

add_filter( 'wp_unique_post_slug_is_bad_attachment_slug', '__return_true' );
