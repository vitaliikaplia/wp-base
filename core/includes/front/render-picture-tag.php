<?php

if(!defined('ABSPATH')){exit;}

use Timber\ImageHelper;

/** html filter to render picture tag from timber image object */
function render_picture_tag($picture, $size = 'full', $loading = 'lazy', $alt = null) {
    if(is_array($picture)) {
        $picture_id = $picture['ID'];
        $original_id = apply_filters('wpml_object_id', $picture_id, 'attachment', true, apply_filters('wpml_default_language', null));
        $webp_url = get_post_meta($original_id, 'webp_url', true);
        $picture_url = wp_get_attachment_image_url($original_id, 'full');
        $picture_alt = get_post_meta($original_id, '_wp_attachment_image_alt', true);
        $picture_w = $picture['width'];
        $picture_h = $picture['height'];
        $mime_type = get_post_mime_type($original_id);
    } elseif(is_object($picture)) {
        $picture_id = $picture->id;
        $original_id = apply_filters('wpml_object_id', $picture_id, 'attachment', true, apply_filters('wpml_default_language', null));
        $picture_w = $picture->width;
        $picture_h = $picture->height;
        $webp_url = get_post_meta($original_id, 'webp_url', true);
        $picture_url = wp_get_attachment_image_url($original_id, 'full');
        $picture_alt = get_post_meta($original_id, '_wp_attachment_image_alt', true);
        $mime_type = get_post_mime_type($original_id);
    }
    if($alt !== null){
        $picture_alt = $alt;
    }
    if (in_array($size, array("full", "large", "medium", "thumbnail"))) {
        $sizer_map_array = array(
            'full' => 'full',
            'large' => 1024,
            'medium' => 768,
            'thumbnail' => 480
        );
        $size_px = $sizer_map_array[$size];
    } else {
        $size_px = 'full';
    }

    if( (!empty($picture_w) && !empty($picture_h) && pathinfo($picture_url, PATHINFO_EXTENSION) != 'svg') or pathinfo($picture_url, PATHINFO_EXTENSION) == 'svg' ) {
        return Timber::compile('overall/picture-tag.twig', array(
            'webp_url' => $webp_url,
            'picture_url' => $picture_url,
            'picture_w' => $picture_w,
            'picture_h' => $picture_h,
            'mime_type' => $mime_type,
            'picture_alt' => $picture_alt,
            'size' => $size_px,
            'ext' => pathinfo($picture_url, PATHINFO_EXTENSION),
            'loading' => $loading
        ));
    }
}

function render_picture_src($picture, $size = 'full'){

    if (in_array($size, array("full", "large", "medium", "thumbnail"))) {
        $sizer_map_array = array(
            'full' => 'full',
            'large' => 1024,
            'medium' => 768,
            'thumbnail' => 480
        );
        $size_px = $sizer_map_array[$size];
    } else {
        $size_px = 'full';
    }

    if(is_array($picture)){
        $webp_url = get_post_meta($picture['ID'], 'webp_url', true);
        $picture_url = $picture['url'];
    } elseif(is_object($picture)){
        $picture_id = $picture->id;
        $webp_url = get_post_meta($picture_id, 'webp_url', true);
        $picture_url = wp_get_attachment_image_url($picture_id, 'full');
    }

    if(!empty($webp_url)){
        $picture_url = $webp_url;
    }

    if($size_px == 'full'){
        return $picture_url;
    } else {
        return ImageHelper::resize($picture_url, $size_px);
    }

}
