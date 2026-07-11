<?php

if(!defined('ABSPATH')){exit;}

/**
 * Disable the default WordPress "post_tag" taxonomy on posts; the blog uses
 * categories only. Detaching it removes the Tags meta box, admin submenu,
 * post-list column and tag archives while leaving the built-in taxonomy itself.
 */
if(get_option('disable_blog_tags')){
    add_action('init', function(){
        unregister_taxonomy_for_object_type('post_tag', 'post');
    }, 100);
}
