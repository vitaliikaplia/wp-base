<?php

if(!defined('ABSPATH')){exit;}

/**
 * SVG uploads.
 *
 * Off by default — an SVG is a script-capable document, so allowing it into the
 * media library hands anyone with upload rights a stored-XSS primitive. Enable
 * the "Allow SVG uploads" option only when the editors are trusted.
 *
 * Even with the option on, every uploaded SVG is rewritten through
 * sanitize_svg_file() before WordPress stores it: script, event handlers,
 * foreign objects and javascript: URIs never reach the uploads directory.
 */
if(get_option('allow_svg_upload')){

    add_action("init", function() {

        add_filter('upload_mimes', function ($mimes) {
            $mimes['svg'] = 'image/svg+xml';
            return $mimes;
        });

        add_filter("wp_handle_upload_prefilter", function ($upload) {

            if(empty($upload["type"]) || $upload["type"] !== "image/svg+xml"){
                return $upload;
            }

            if(empty($upload["tmp_name"]) || !is_readable($upload["tmp_name"])){
                $upload['error'] = __('The SVG file could not be read.', TEXTDOMAIN);
                return $upload;
            }

            // Refuse rather than silently store something we could not clean.
            if(!sanitize_svg_file($upload["tmp_name"])){
                $upload['error'] = __('This SVG could not be sanitized and was rejected. Re-export it as a plain SVG, without scripts or embedded documents.', TEXTDOMAIN);
                return $upload;
            }

            // WordPress rejects SVG uploads that have no XML declaration.
            $contents = file_get_contents($upload["tmp_name"]);
            if($contents !== false && strpos($contents, "<?xml") === false){
                file_put_contents($upload["tmp_name"], '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . $contents);
            }

            return $upload;

        }, 10, 1);

    });

    /**
     * WordPress cannot measure an SVG with getimagesize(), so the media library
     * would show a 0x0 box. Read the real viewBox where possible instead of
     * falling back to a hardcoded square.
     */
    function add_svg_dimensions($metadata, $attachment_id) {

        if('image/svg+xml' !== get_post_mime_type($attachment_id)){
            return $metadata;
        }

        $metadata['width'] = 100;
        $metadata['height'] = 100;

        $attachment_path = get_attached_file($attachment_id);

        if($attachment_path && is_readable($attachment_path)){
            $dom = new DOMDocument();
            libxml_use_internal_errors(true);
            $loaded = $dom->loadXML(file_get_contents($attachment_path), LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
            libxml_clear_errors();

            if($loaded && $dom->documentElement){
                $view_box = preg_split('/[\s,]+/', trim((string) $dom->documentElement->getAttribute('viewBox')));

                if(is_array($view_box) && count($view_box) === 4 && (float) $view_box[2] > 0 && (float) $view_box[3] > 0){
                    $metadata['width'] = (int) round((float) $view_box[2]);
                    $metadata['height'] = (int) round((float) $view_box[3]);
                }
            }
        }

        return $metadata;

    }
    add_filter('wp_generate_attachment_metadata', 'add_svg_dimensions', 10, 2);

}
