<?php

if(!defined('ABSPATH')){exit;}

use Timber\ImageHelper;

/** html filter to render picture tag from timber image object */
function render_picture_tag($picture, $size = 'full', $loading = 'lazy', $alt = null) {
    // The attachment is used as given: WP-LOC already resolves frontend image
    // lookups to the current-language record, and alt text is per-language on
    // purpose. Only the shadow URLs — `webp_url` / `avif_url`, which describe
    // the physical file rather than the translation — fall back to the
    // default-language sibling (see theme_attachment_meta()).
    if(is_array($picture)) {
        $picture_id = $picture['ID'];
        $picture_w = $picture['width'];
        $picture_h = $picture['height'];
    } elseif(is_object($picture)) {
        $picture_id = $picture->id;
        $picture_w = $picture->width;
        $picture_h = $picture->height;
    } else {
        return '';
    }

    $avif_url = theme_attachment_meta($picture_id, 'avif_url');
    $webp_url = theme_attachment_meta($picture_id, 'webp_url');
    $picture_url = wp_get_attachment_image_url($picture_id, 'full');
    $picture_alt = get_post_meta($picture_id, '_wp_attachment_image_alt', true);
    $mime_type = get_post_mime_type($picture_id);
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

    // The AVIF rung is resolved here rather than in the template because it
    // needs a guard the template cannot express.
    //
    // AVIF sits FIRST in the <picture> ladder, so whatever it points at is what
    // every modern browser downloads — nothing behind it is ever consulted. At a
    // requested size that means the URL has to be the resized file: Timber hands
    // the source back unchanged when it cannot process a format (see the tail of
    // ImageHelper::_operate()), and serving that would make the first rung the
    // FULL-SIZE image, quietly undoing the resize for everyone except Safari
    // before 16.4. Whether it can process AVIF depends on the server's Imagick
    // build, so this is a runtime check, not an assumption: if the resize did
    // not produce a new URL, the rung is dropped and the ladder falls through to
    // WEBP, which resizes reliably.
    $avif_src = '';
    if($avif_url){
        if($size_px === 'full'){
            $avif_src = $avif_url;
        } else {
            $resized_avif = ImageHelper::resize($avif_url, $size_px);
            $avif_src = ($resized_avif && $resized_avif !== $avif_url) ? $resized_avif : '';
        }
    }

    if( (!empty($picture_w) && !empty($picture_h) && pathinfo($picture_url, PATHINFO_EXTENSION) != 'svg') or pathinfo($picture_url, PATHINFO_EXTENSION) == 'svg' ) {
        return Timber::compile('overall/picture-tag.twig', array(
            'avif_src' => $avif_src,
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

/**
 * A bare URL for contexts that cannot carry a <picture> ladder — a CSS
 * background, an og:image, an `src` written by hand.
 *
 * Deliberately WEBP and never AVIF: there is no fallback rung here, so the
 * single URL has to be one every browser can decode. WEBP is universal; AVIF
 * would hand a broken image to Safari before 16.4. Use `| picture` whenever the
 * markup allows it — that path does offer AVIF, with a fallback behind it.
 */
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
        $webp_url = theme_attachment_meta($picture['ID'], 'webp_url');
        $picture_url = $picture['url'];
    } elseif(is_object($picture)){
        $picture_id = $picture->id;
        $webp_url = theme_attachment_meta($picture_id, 'webp_url');
        $picture_url = wp_get_attachment_image_url($picture_id, 'full');
    } else {
        return '';
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
