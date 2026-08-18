<?php

if(!defined('ABSPATH')){exit;}

/**
 * Regenerate the shadow images (webp / avif) from the originals.
 *
 * Both qualities live in the options, but changing a slider on its own does
 * nothing — the files are already on disk, written with the previous settings.
 * This tool re-encodes the whole library so the new numbers can be seen, without
 * dropping to the console.
 *
 * It works in batches: the browser sends one request per batch and gets an
 * offset back. That keeps each request well inside max_execution_time and gives
 * an honest progress bar instead of a page that appears to hang.
 */

/** How many attachments one request converts. */
function regenerate_images_batch_size(){
    return (int) apply_filters('regenerate_images_batch_size', 10);
}

/**
 * Total number of convertible attachments — needed only for the progress bar.
 * It counts records, not files, so on a multilingual site it includes the
 * language duplicates the batch below skips; they show up as "skipped".
 */
function regenerate_images_total(){

    global $wpdb;

    $mimes = shadow_image_source_mimes();
    $placeholders = implode(',', array_fill(0, count($mimes), '%s'));

    return (int) $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->posts}
         WHERE post_type = 'attachment' AND post_mime_type IN ({$placeholders})",
        $mimes
    ));

}

/**
 * One pass: convert a batch and report what happened.
 *
 * @param string $format 'webp' or 'avif'
 * @param int    $offset attachment to resume from
 */
function regenerate_images_batch($format, $offset){

    global $wpdb;

    $mimes = shadow_image_source_mimes();
    $placeholders = implode(',', array_fill(0, count($mimes), '%s'));
    $params = array_merge($mimes, array(regenerate_images_batch_size(), $offset));

    $rows = $wpdb->get_results($wpdb->prepare(
        "SELECT ID FROM {$wpdb->posts}
         WHERE post_type = 'attachment' AND post_mime_type IN ({$placeholders})
         ORDER BY ID ASC LIMIT %d OFFSET %d",
        $params
    ));

    $stat = array(
        'processed' => 0,
        'converted' => 0,
        'skipped'   => 0,
        'bytes'     => 0,
        'errors'    => array(),
    );

    foreach($rows as $row){

        $stat['processed']++;

        // On a multilingual site every language has its own attachment record
        // over ONE physical file, so converting each of them would re-encode the
        // same image N times — and AVIF encoding is the slow part of this pass.
        // Only the default-language record does the work; set_shadow_image_meta()
        // then writes the result onto all the siblings anyway.
        $original_id = theme_translated_id((int) $row->ID, 'post_attachment', theme_default_language());

        if($original_id !== (int) $row->ID){
            $stat['skipped']++;
            continue;
        }

        $result = generate_shadow_image((int) $row->ID, $format);

        if(is_wp_error($result)){

            // A missing source is an empty slot in the library, not a failure —
            // it is counted but does not go into the error list, which is for
            // things the user can act on.
            if($result->get_error_code() === 'missing_source'){
                $stat['skipped']++;
                continue;
            }

            // One broken attachment must not stop the pass over the library:
            // collect the error and carry on, the report will show it.
            $stat['errors'][] = basename((string) get_attached_file($row->ID)) . ': ' . $result->get_error_message();
            continue;
        }

        $stat['converted']++;
        $stat['bytes'] += $result;

    }

    return $stat;

}

/** AJAX: one regeneration batch. */
function ajax_regenerate_images(){

    if(!current_user_can('manage_options')){
        wp_send_json_error(array(
            'message' => __('You are not allowed to regenerate images.', TEXTDOMAIN),
        ), 403);
    }

    check_ajax_referer('regenerate_images', 'nonce');

    $format = isset($_POST['format']) ? sanitize_key($_POST['format']) : '';

    if(!in_array($format, array('webp', 'avif'), true)){
        wp_send_json_error(array(
            'message' => __('Unknown image format.', TEXTDOMAIN),
        ), 400);
    }

    // Regenerating a format that is switched off would write files nothing ever
    // reads, so the tool follows the same toggles as the upload hooks.
    $enabled = $format === 'avif' ? get_option('enable_avif_convert') : get_option('enable_webp_convert');

    if(!$enabled){
        wp_send_json_error(array(
            'message' => __('This image format is disabled in the settings.', TEXTDOMAIN),
        ), 400);
    }

    if(!class_exists('Imagick')){
        wp_send_json_error(array(
            'message' => __('Imagick is not available on this server.', TEXTDOMAIN),
        ), 500);
    }

    $offset = isset($_POST['offset']) ? max(0, (int) $_POST['offset']) : 0;
    $total  = regenerate_images_total();

    $stat = regenerate_images_batch($format, $offset);
    $next = $offset + $stat['processed'];

    wp_send_json_success(array(
        'offset'    => $next,
        'total'     => $total,
        'processed' => $stat['processed'],
        'converted' => $stat['converted'],
        'skipped'   => $stat['skipped'],
        'bytes'     => $stat['bytes'],
        'errors'    => $stat['errors'],
        'done'      => ($stat['processed'] === 0 || $next >= $total),
    ));

}
add_action('wp_ajax_regenerate_images', 'ajax_regenerate_images');
