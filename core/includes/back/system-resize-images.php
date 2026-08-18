<?php

if(!defined('ABSPATH')){exit;}

/**
 * Wrap inline content images in a <picture> ladder.
 *
 * The rungs are ordered the same way as in picture-tag.twig: the browser takes
 * the FIRST type it understands and stops looking. AVIF goes first because it
 * is the lightest, WEBP catches Safari before 16.4, and the plain <img> stays
 * for everything else.
 */
function add_picture_tag_to_images($content) {
    if (is_single()) {

        // Ranges that are already a <picture> are off limits. Without this an
        // <img> that Timber has itself wrapped gets wrapped a second time and
        // the markup becomes <picture><source><picture><source><img> — invalid
        // nesting where the outer <source> rungs mean nothing.
        $skip_ranges = array();
        if (preg_match_all('/<picture\b[^>]*>.*?<\/picture>/is', $content, $existing, PREG_OFFSET_CAPTURE)) {
            foreach ($existing[0] as $match) {
                $skip_ranges[] = array($match[1], $match[1] + strlen($match[0]));
            }
        }

        if (preg_match_all('/<img[^>]+>/', $content, $matches, PREG_OFFSET_CAPTURE)) {
            foreach ($matches[0] as $match) {

                $img_tag = $match[0];
                $img_offset = $match[1];

                $inside_picture = false;
                foreach ($skip_ranges as $range) {
                    if ($img_offset > $range[0] && $img_offset < $range[1]) {
                        $inside_picture = true;
                        break;
                    }
                }
                if ($inside_picture) {
                    continue;
                }

                preg_match('/src="([^"]+)"/', $img_tag, $src_match);
                $src_url = $src_match[1] ?? '';

                preg_match('/wp-image-([0-9]+)/', $img_tag, $id_match);
                $image_id = $id_match[1] ?? null;

                if (!$image_id && $src_url) {
                    $image_id = attachment_url_to_postid($src_url);
                }

                if (!$image_id) {
                    continue;
                }

                // theme_attachment_meta() rather than a raw read: on a
                // multilingual site the record being rendered may be the
                // translation, and the shadow meta belongs to the file.
                $avif_url = theme_attachment_meta($image_id, 'avif_url');
                $webp_url = theme_attachment_meta($image_id, 'webp_url');

                // Nothing to offer — leave the <img> alone rather than wrapping
                // it in an empty <picture>.
                if (!$avif_url && !$webp_url) {
                    continue;
                }

                $picture_tag = '<picture>';
                if ($avif_url) {
                    $picture_tag .= '<source srcset="' . esc_url($avif_url) . '" type="image/avif">';
                }
                if ($webp_url) {
                    $picture_tag .= '<source srcset="' . esc_url($webp_url) . '" type="image/webp">';
                }
                $picture_tag .= $img_tag;
                $picture_tag .= '</picture>';

                $content = str_replace($img_tag, $picture_tag, $content);
            }
        }
    }
    return $content;
}
add_filter('the_content', 'add_picture_tag_to_images', 99999);

if(get_option('remove_default_image_sizes')){
    function remove_default_image_sizes( $sizes) {
        unset( $sizes['large']);
        unset( $sizes['thumbnail']);
        unset( $sizes['medium']);
        unset( $sizes['medium_large']);
        unset( $sizes['1536x1536']);
        unset( $sizes['2048x2048']);
        return $sizes;
    }
    add_filter('intermediate_image_sizes_advanced', 'remove_default_image_sizes');
}

function resize_images_at_upload($image_data){

    if(get_option('enable_resize_at_upload') && in_array($image_data['type'], get_option('resize_at_upload_formats'))) {
        $max_width  = get_option('resize_upload_width');
        $max_height = get_option('resize_upload_height');
        $resize_quality = get_option('resize_upload_quality');
        $image_editor = wp_get_image_editor($image_data['file']);
        $image_editor->resize($max_width, $max_height, false);
        $image_editor->set_quality($resize_quality);
        $image_editor->save($image_data['file']);
    }

    return $image_data;

}
add_action('wp_handle_upload', 'resize_images_at_upload');

/** Source types worth converting. SVG is vector; WEBP and AVIF are the targets. */
function shadow_image_source_mimes(){
    return array('image/gif', 'image/png', 'image/jpeg', 'image/jpg');
}

/**
 * Set the quality so the encoder actually hears it.
 *
 * Two setters, and that is not belt-and-braces: they feed DIFFERENT encoders.
 * WEBP listens to setImageCompressionQuality() and ignores setCompressionQuality();
 * AVIF is the other way round. With only the former, every AVIF quality from 40
 * to 80 produces a byte-identical file — the slider does nothing and the encoder
 * writes its own default. Call both; each format takes the one it reads.
 */
function set_image_encoder_quality($imagick, $quality){
    $imagick->setCompressionQuality($quality);
    $imagick->setImageCompressionQuality($quality);
}

/** convert to webp */
function convert_to_webp($image_path, $destination_path, $quality) {
    $imagick = new \Imagick(realpath($image_path));
    $imagick->setImageFormat('webp');
    set_image_encoder_quality($imagick, $quality);
    $imagick->stripImage();
    $imagick->writeImage($destination_path);
    $imagick->clear();
    $imagick->destroy();
}

/**
 * convert to avif
 *
 * Same engine as WEBP — ImageMagick writes AVIF through libheif. `magick -list
 * format` showing `AVIF rw+` is the check that matters; the delegate banner only
 * mentions heic, which is misleading — one delegate serves both formats.
 */
function convert_to_avif($image_path, $destination_path, $quality) {
    $imagick = new \Imagick(realpath($image_path));
    $imagick->setImageFormat('avif');
    set_image_encoder_quality($imagick, $quality);
    $imagick->stripImage();
    $imagick->writeImage($destination_path);
    $imagick->clear();
    $imagick->destroy();
}

/**
 * AVIF quality depends on what the source is: flat graphics compress far harder
 * than photographs, so a single number would have to be set for the worst case
 * and would waste bytes on everything else. Hence two options, and defaults that
 * keep AVIF below the WEBP of the same image while avoiding the banding the
 * encoder default (~q50) leaves on gradients.
 */
function avif_quality_for($mime_type){

    $is_graphics = in_array($mime_type, array('image/png', 'image/gif'), true);
    $option = $is_graphics ? 'avif_convert_quality_graphics' : 'avif_convert_quality_photo';
    $quality = (int) get_option($option);

    if($quality > 0){
        return $quality;
    }

    return $is_graphics ? 55 : 60;

}

/** WEBP quality from the options, with the historic default. */
function webp_quality(){
    $quality = (int) get_option('webp_convert_quality');
    return $quality > 0 ? $quality : 90;
}

/** Quality for either shadow format, so one code path serves both. */
function shadow_image_quality($format, $mime_type){
    return $format === 'avif' ? avif_quality_for($mime_type) : webp_quality();
}

/**
 * Path and URL of a shadow file, in the naming scheme the theme has always used
 * for WEBP: `name-ext.webp` next to the original, so `photo.png` and `photo.jpg`
 * never collide on one shadow.
 */
function shadow_image_paths($attachment_id, $extension){

    $attached_file = get_attached_file($attachment_id);
    $origin_url = wp_get_attachment_url($attachment_id);

    if(!$attached_file || !$origin_url){
        return null;
    }

    $path_parts = pathinfo($attached_file);
    $url_info = pathinfo($origin_url);

    return array(
        'path' => $path_parts['dirname'] . DS . $path_parts['filename'] . '-' . $path_parts['extension'] . '.' . $extension,
        'url'  => $url_info['dirname'] . '/' . $url_info['filename'] . '-' . $url_info['extension'] . '.' . $extension,
    );

}

/** The same path, derived from a file rather than from an attachment record. */
function shadow_image_path_for_file($file, $extension){
    $path_parts = pathinfo($file);
    return $path_parts['dirname'] . DS . $path_parts['filename'] . '-' . $path_parts['extension'] . '.' . $extension;
}

/**
 * Write the shadow meta onto EVERY language record of this attachment.
 *
 * WP-LOC translates media at the record level while the physical file stays
 * single, so `webp_path` / `avif_url` describe the file, not the translation.
 * Writing only the uploaded record leaves every duplicate without the meta, and
 * the other languages then serve the heavy original — the shadow file is on
 * disk, nothing points at it.
 *
 * update_post_meta(), not add_post_meta(): regeneration has to overwrite, and
 * add_post_meta() with $unique would silently keep the stale value.
 */
function set_shadow_image_meta($attachment_id, $extension, $path, $url){

    $ids = theme_attachment_ids($attachment_id);

    foreach($ids as $id){
        update_post_meta($id, $extension . '_path', $path);
        update_post_meta($id, $extension . '_url', $url);
    }

    return $ids;

}

/**
 * Convert one attachment into one shadow format and record it.
 * Shared by the upload hooks and the regeneration tool so the two can never
 * drift apart. Returns the written size in bytes, or a WP_Error.
 */
function generate_shadow_image($attachment_id, $format){

    if(!in_array($format, array('webp', 'avif'), true)){
        return new WP_Error('unknown_format', __('Unknown image format.', TEXTDOMAIN));
    }

    if(!class_exists('Imagick')){
        return new WP_Error('no_imagick', __('Imagick is not available on this server.', TEXTDOMAIN));
    }

    $file = get_attached_file($attachment_id);

    if(!$file || !file_exists($file)){
        return new WP_Error('missing_source', __('Source file is missing.', TEXTDOMAIN));
    }

    $paths = shadow_image_paths($attachment_id, $format);

    if(!$paths){
        return new WP_Error('missing_source', __('Source file is missing.', TEXTDOMAIN));
    }

    $quality = shadow_image_quality($format, get_post_mime_type($attachment_id));

    try {
        if($format === 'avif'){
            convert_to_avif($file, $paths['path'], $quality);
        } else {
            convert_to_webp($file, $paths['path'], $quality);
        }
    } catch(\Throwable $e){
        return new WP_Error('convert_failed', $e->getMessage());
    }

    // Record the meta only once the file is really there and is not a stub —
    // a truncated write would otherwise be advertised to every browser.
    if(!file_exists($paths['path']) || filesize($paths['path']) < 100){
        return new WP_Error('empty_result', __('Empty result.', TEXTDOMAIN));
    }

    set_shadow_image_meta($attachment_id, $format, $paths['path'], $paths['url']);

    return filesize($paths['path']);

}

if(get_option('enable_webp_convert')) {

    /** convert action */
    function optimize_images_at_upload($image_data){
        if(in_array($image_data['type'], shadow_image_source_mimes())){
            convert_to_webp($image_data['file'], shadow_image_path_for_file($image_data['file'], 'webp'), webp_quality());
        }
        return $image_data;
    }
    add_action('wp_handle_upload', 'optimize_images_at_upload');

    /**
     * Runs at priority 100000, i.e. after WP-LOC (priority 10) has created the
     * language duplicates and registered them, so the sibling lookup finds them
     * all. The duplicates trigger this hook too, before their `_wp_attached_file`
     * is written — shadow_image_paths() returns null there and we simply skip,
     * because the original's own pass covers every language anyway.
     */
    function add_custom_attachment_meta( $attachment_id ) {
        $paths = shadow_image_paths($attachment_id, 'webp');
        if($paths && file_exists($paths['path'])){
            set_shadow_image_meta($attachment_id, 'webp', $paths['path'], $paths['url']);
        }
    }
    add_action( 'add_attachment', 'add_custom_attachment_meta', 100000 );

    /** delete webp on original file deletion */
    function delete_webp_shadow_image($post_id) {
        $webp_path = get_post_meta($post_id, 'webp_path', true);
        if ($webp_path && file_exists($webp_path)) {
            unlink($webp_path);
        }
    }
    add_action('delete_attachment', 'delete_webp_shadow_image');

}

if(get_option('enable_avif_convert')) {

    /**
     * AVIF shadow at upload time.
     *
     * A hook of its own rather than a branch inside the WEBP converter: the two
     * formats are enabled independently and neither may become a precondition
     * for the other. The <source> ladder works out on its own what exists.
     */
    function convert_uploaded_image_to_avif($image_data){
        if(in_array($image_data['type'], shadow_image_source_mimes())){
            convert_to_avif($image_data['file'], shadow_image_path_for_file($image_data['file'], 'avif'), avif_quality_for($image_data['type']));
        }
        return $image_data;
    }
    add_action('wp_handle_upload', 'convert_uploaded_image_to_avif');

    /** Same timing note as add_custom_attachment_meta(). */
    function add_avif_attachment_meta($attachment_id) {
        $paths = shadow_image_paths($attachment_id, 'avif');
        if($paths && file_exists($paths['path'])){
            set_shadow_image_meta($attachment_id, 'avif', $paths['path'], $paths['url']);
        }
    }
    add_action('add_attachment', 'add_avif_attachment_meta', 100000);

    /**
     * Apply the theme's AVIF quality to AVIF written by WordPress itself.
     *
     * Not belt-and-braces — without it the sliders govern only the shadow file,
     * while every resized derivative Timber cuts from it is re-encoded at
     * WordPress's own default of 82. That single number decides whether the
     * ladder helps or hurts: measured on the same 768px cut, AVIF is 2 920 B at
     * q55 against WEBP's 3 224 B, but 4 829 B at q82 against WEBP's 4 312 B.
     * Since AVIF is the FIRST rung, the q82 case means every modern browser
     * downloads the heavier of the two files — the ladder backwards.
     *
     * The filter is only handed the OUTPUT mime, so it cannot tell whether the
     * original was a photo or flat graphics; it uses the photo setting, which is
     * the higher of the two and therefore never under-qualities graphics.
     *
     * WEBP is deliberately left on WordPress's default: its quality has always
     * come from there on existing projects, and this feature is no reason to
     * silently re-cut every derivative they already have.
     */
    function set_avif_editor_quality($quality, $mime_type){
        return $mime_type === 'image/avif' ? avif_quality_for('image/jpeg') : $quality;
    }
    add_filter('wp_editor_set_quality', 'set_avif_editor_quality', 10, 2);

    /** delete avif on original file deletion */
    function delete_avif_shadow_image($post_id) {
        $avif_path = get_post_meta($post_id, 'avif_path', true);
        if ($avif_path && file_exists($avif_path)) {
            unlink($avif_path);
        }
    }
    add_action('delete_attachment', 'delete_avif_shadow_image');

}
