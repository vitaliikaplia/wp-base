<?php

if(!defined('ABSPATH')){exit;}

function get_pattern($id){

    if(!$id){
        return '';
    }

    $post = get_post($id);

    // Bail before touching $post->post_content: a deleted or mistyped pattern ID
    // used to fatal here on PHP 8 ("attempt to read property on null"), because
    // the null check sat below the has_block() loop.
    if(!$post || $post->post_type !== 'patterns'){
        return '';
    }

    foreach (get_custom_gutenberg_blocks_array() as $block) {
        $block_name = 'acf/' . $block['category'] . '-' . $block['name'];
        if (has_block($block_name, $post->post_content)) {
            $style_name = $block['category'] . '-' . $block['name'];
            $style_url = TEMPLATE_DIRECTORY_URL . 'assets/css/blocks/' . $block['category'] . '/' . $block['name'] . '.min.css';
            wp_enqueue_style($style_name, $style_url, '', ASSETS_VERSION);
        }
    }

    return apply_filters('the_content', $post->post_content);

}
