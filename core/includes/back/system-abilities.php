<?php

if(!defined('ABSPATH')){exit;}

/**
 * Theme abilities for AI agents — WordPress Abilities API (core 6.9+).
 *
 * Every ability lives in the fixed `wp-base/…` namespace, so each site built on
 * this theme offers the same tools under the same names. They are marked for
 * MCP with `meta.mcp.public`: Simple MCP (and the official WordPress MCP
 * Adapter) lists them to the agent as `wp-base-…` tools. Without an MCP plugin
 * they just sit in the core registry and change nothing.
 *
 * Only what the theme alone knows lives here. Posts, blocks, ACF fields and
 * options pages, media, terms, translations and the page/object cache already
 * are Simple MCP tools — do not duplicate them.
 *
 * Each ability goes through the same code as its dashboard counterpart
 * (redirect validation, the Send test widget, the regeneration batch, the icon
 * manager, the options sanitizer), so an agent cannot do what the admin screen
 * would refuse. Permissions are native capabilities: the call runs as the user
 * the MCP key or OAuth connection belongs to.
 *
 * Labels are interface strings (Settings → Simple MCP lists them) and go
 * through translation; descriptions are instructions for the agent and stay in
 * English.
 */

if(!function_exists('wp_register_ability')){
    return;
}

/** ------------------------------------------------------------------------
 *  Registration helpers
 *  --------------------------------------------------------------------- */

add_action('wp_abilities_api_categories_init', function(){
    wp_register_ability_category('wp-base', array(
        'label'       => __('Site theme', TEXTDOMAIN),
        'description' => __('Settings, caches, mail and SMS logs, redirects, icons and maintenance mode of the site theme.', TEXTDOMAIN),
    ));
});

/** Register one theme ability: shared category, MCP flag and behaviour hints. */
function register_theme_ability($name, $args, $annotations){

    $args['category'] = 'wp-base';
    $args['meta'] = array(
        'mcp'         => array('public' => true),
        'annotations' => $annotations,
    );

    wp_register_ability('wp-base/' . $name, $args);

}

/** The usual gate for site-wide settings and logs. */
function theme_ability_can_manage(){
    return current_user_can('manage_options');
}

/** An object input schema that refuses unknown keys. */
function theme_ability_schema($properties, $required = array()){

    $schema = array(
        'type'                 => 'object',
        'properties'           => $properties,
        'additionalProperties' => false,
    );

    if($required){
        $schema['required'] = $required;
    }

    return $schema;

}

/** The `language` input property, offered only on multilingual sites. */
function theme_ability_language_property($description){

    if(!theme_is_multilingual()){
        return array();
    }

    return array('language' => array(
        'type'        => 'string',
        'enum'        => theme_language_slugs(),
        'description' => $description . ' Default language: ' . theme_default_language() . '.',
    ));

}

/** Check a requested language against the active ones; '' means the default. */
function theme_ability_language($input){

    $language = isset($input['language']) ? (string) $input['language'] : '';

    if($language === '' || !theme_is_multilingual()){
        return '';
    }

    if(!in_array($language, theme_language_slugs(), true)){
        return new WP_Error('unknown_language', sprintf(__('Unknown language: %s.', TEXTDOMAIN), $language));
    }

    return $language === theme_default_language() ? '' : $language;

}

/** ------------------------------------------------------------------------
 *  Dashboard options — read and write through the options framework
 *  --------------------------------------------------------------------- */

/**
 * Every real option of the dashboard framework, keyed by name, with the
 * section and tab it sits in. Layout markers and action buttons are left out.
 */
function theme_option_fields(){

    $fields = array();

    foreach(get_custom_options() as $section_slug => $section){

        $tab = '';

        foreach($section['fields'] as $field){

            if($field['type'] === 'tab_start'){
                $tab = isset($field['label']) ? $field['label'] : '';
                continue;
            }

            if($field['type'] === 'tab_end'){
                $tab = '';
                continue;
            }

            if($field['type'] === 'regenerate_images' || empty($field['name'])){
                continue;
            }

            $field['section'] = $section_slug;
            $field['tab'] = $tab;
            $fields[$field['name']] = $field;

        }

    }

    return $fields;

}

/** Is this field stored per language for this (non-default) language? */
function theme_option_is_localized($field, $language){
    return $language !== '' && !empty($field['localize']) && theme_is_multilingual();
}

/** Stored value of one option; [exists, value] for a localized one in a non-default language. */
function theme_option_read($field, $language = ''){

    if(theme_option_is_localized($field, $language)){
        return theme_get_localized_option($field['name'], $language);
    }

    $value = get_option($field['name'], null);

    return array($value !== null, $value);

}

/** One option as the agent sees it: metadata, choices, limits and the value — never a secret. */
function theme_option_export($field, $language = ''){

    list($exists, $value) = theme_option_read($field, $language);

    $out = array(
        'name'        => $field['name'],
        'type'        => $field['type'],
        'label'       => isset($field['label']) ? wp_strip_all_tags($field['label']) : '',
        'description' => isset($field['description']) ? wp_strip_all_tags($field['description']) : '',
        'section'     => $field['section'],
    );

    if($field['tab'] !== ''){
        $out['tab'] = wp_strip_all_tags($field['tab']);
    }

    if(!empty($field['localize'])){
        $out['localized'] = true;

        if(theme_option_is_localized($field, $language)){
            $out['translated'] = $exists;
        }
    }

    if($field['type'] === 'password'){
        $out['is_set'] = $exists && $value !== '' && $value !== false;
    } elseif($field['type'] === 'checkbox'){
        $out['value'] = !empty($value);
    } elseif(in_array($field['type'], array('number', 'range'), true)){
        $out['value'] = is_numeric($value) ? $value + 0 : null;
    } else {
        $out['value'] = $exists && $value !== false ? $value : null;
    }

    if(!empty($field['options']) && is_array($field['options'])){
        $out['choices'] = array_map('wp_strip_all_tags', $field['options']);
    }

    if(!empty($field['tweaks']) && is_array($field['tweaks'])){
        foreach(array('min', 'max', 'step', 'suffix') as $key){
            if(isset($field['tweaks'][$key]) && $field['tweaks'][$key] !== ''){
                $out[$key] = is_numeric($field['tweaks'][$key]) ? $field['tweaks'][$key] + 0 : $field['tweaks'][$key];
            }
        }
    }

    if(array_key_exists('default', $field)){
        $out['default'] = in_array($field['type'], array('number', 'range'), true) && is_numeric($field['default']) ? $field['default'] + 0 : $field['default'];
    }

    return $out;

}

/**
 * Check one incoming value against its field and sanitize it the way the
 * settings form does (custom_option_sanitize_callback()). The form cannot send
 * an out-of-range slider or an unknown select choice — the agent can, so those
 * are refused here instead of being stored.
 *
 * Raw HTML/JS fields (`code`, `mce`) need unfiltered_html, like any other place
 * in WordPress that stores markup unfiltered.
 *
 * @return mixed|WP_Error The value to store.
 */
function theme_option_prepare($field, $value){

    switch($field['type']){

        case 'checkbox':
            if(!is_bool($value) && !in_array($value, array(0, 1, '0', '1', ''), true)){
                return new WP_Error('invalid_value', __('Must be true or false.', TEXTDOMAIN));
            }
            return $value ? '1' : '';

        case 'number':
        case 'range':
            if(!is_numeric($value)){
                return new WP_Error('invalid_value', __('Must be a number.', TEXTDOMAIN));
            }

            $value = $value + 0;
            $tweaks = isset($field['tweaks']) && is_array($field['tweaks']) ? $field['tweaks'] : array();
            $min = isset($tweaks['min']) && is_numeric($tweaks['min']) ? $tweaks['min'] + 0 : null;
            $max = isset($tweaks['max']) && is_numeric($tweaks['max']) ? $tweaks['max'] + 0 : null;
            $step = isset($tweaks['step']) && is_numeric($tweaks['step']) ? $tweaks['step'] + 0 : null;

            if(($min !== null && $value < $min) || ($max !== null && $value > $max)){
                return new WP_Error('invalid_value', sprintf(__('Must be between %1$s and %2$s.', TEXTDOMAIN), $min, $max));
            }

            // повзунок дає лише значення на кроці — так само й тут
            if($step && $step > 0){
                $steps = ($value - ($min !== null ? $min : 0)) / $step;

                if(abs($steps - round($steps)) > 1e-9){
                    return new WP_Error('invalid_value', sprintf(__('Must be a multiple of %s.', TEXTDOMAIN), $step));
                }
            }

            return $value;

        case 'select':
            $choices = isset($field['options']) && is_array($field['options']) ? array_map('strval', array_keys($field['options'])) : array();

            if(!is_scalar($value) || !in_array((string) $value, $choices, true)){
                return new WP_Error('invalid_value', sprintf(__('Must be one of: %s.', TEXTDOMAIN), implode(', ', $choices)));
            }
            return sanitize_text_field((string) $value);

        case 'select-multiple':
            $choices = isset($field['options']) && is_array($field['options']) ? array_map('strval', array_keys($field['options'])) : array();

            if(!is_array($value) || array_diff(array_map('strval', $value), $choices)){
                return new WP_Error('invalid_value', sprintf(__('Must be a list of: %s.', TEXTDOMAIN), implode(', ', $choices)));
            }
            return array_values(array_map('sanitize_text_field', array_map('strval', $value)));

        case 'link':
            if(!is_array($value)){
                return new WP_Error('invalid_value', __('Must be an object with url, title and target.', TEXTDOMAIN));
            }
            break;

        case 'code':
            if(!current_user_can('unfiltered_html')){
                return new WP_Error('invalid_value', __('Storing raw HTML or JavaScript needs the unfiltered_html capability.', TEXTDOMAIN));
            }
            if(!is_string($value)){
                return new WP_Error('invalid_value', __('Must be text.', TEXTDOMAIN));
            }
            return $value;

        case 'mce':
            if(!is_string($value)){
                return new WP_Error('invalid_value', __('Must be text.', TEXTDOMAIN));
            }
            return current_user_can('unfiltered_html') ? $value : wp_kses_post($value);

        default:
            if(!is_scalar($value)){
                return new WP_Error('invalid_value', __('Must be text.', TEXTDOMAIN));
            }
            $value = (string) $value;

    }

    $sanitize = custom_option_sanitize_callback($field);

    return $sanitize ? call_user_func($sanitize, $value) : $value;

}

/** What the agent is told an option changed from/to — secrets stay hidden. */
function theme_option_report_value($field, $value){

    if($field['type'] === 'password'){
        return ($value === '' || $value === false || $value === null) ? '' : '••••••';
    }

    if($field['type'] === 'checkbox'){
        return !empty($value);
    }

    return $value;

}

/**
 * Validate everything first, then write — all or nothing.
 *
 * Localized options in a non-default language go to the row WP-LOC reads for
 * that language; WP-LOC only routes such saves itself on admin screens. The
 * Timber HTML cache is dropped afterwards, since most options change markup.
 *
 * @return array|WP_Error ['changed' => [name => [old, new]], 'unchanged' => [names]]
 */
function theme_update_options($values, $language = ''){

    if(!is_array($values) || !$values){
        return new WP_Error('no_options', __('No settings to change.', TEXTDOMAIN));
    }

    $fields = theme_option_fields();
    $prepared = array();
    $errors = array();

    foreach($values as $name => $value){

        if(!isset($fields[$name])){
            $errors[] = sprintf(__('Unknown option: %s.', TEXTDOMAIN), $name);
            continue;
        }

        $clean = theme_option_prepare($fields[$name], $value);

        if(is_wp_error($clean)){
            $errors[] = $name . ': ' . $clean->get_error_message();
            continue;
        }

        $prepared[$name] = $clean;

    }

    if($errors){
        return new WP_Error('invalid_options', implode(' ', $errors));
    }

    $changed = array();
    $unchanged = array();

    foreach($prepared as $name => $clean){

        $field = $fields[$name];
        list(, $old) = theme_option_read($field, $language);

        if(theme_option_is_localized($field, $language)){
            theme_update_localized_option($name, $language, $clean);
        } else {
            update_option($name, $clean);
        }

        if($old == $clean){
            $unchanged[] = $name;
        } else {
            $changed[$name] = array(
                'old' => theme_option_report_value($field, $old),
                'new' => theme_option_report_value($field, $clean),
            );
        }

    }

    if($changed){
        delete_transients_by_prefix('timberloader_');
    }

    return array('changed' => $changed, 'unchanged' => $unchanged);

}

/** ------------------------------------------------------------------------
 *  Mail and SMS log
 *  --------------------------------------------------------------------- */

/** Plain text of a logged email: block ends become line breaks, styles and scripts go. */
function mail_log_plain_text($html){

    $html = preg_replace('#<(br|/p|/div|/tr|/li|/h[1-6]|/table)\b[^>]*>#i', "$0\n", (string) $html);
    $text = html_entity_decode(wp_strip_all_tags($html), ENT_QUOTES, 'UTF-8');
    $text = preg_replace("/[ \t\x{00A0}]+/u", ' ', $text);
    $text = preg_replace("/ *\n\s*/", "\n", $text);

    return trim($text);

}

/** One log entry; $full adds the email text or the whole provider response. */
function message_log_entry($post, $full = false, $html = false){

    $entry = array(
        'id'        => $post->ID,
        'date'      => get_post_time('c', true, $post),
        'recipient' => (string) get_post_meta($post->ID, 'recipient', true),
    );

    if($post->post_type === 'sms-log'){

        $response = (string) get_post_meta($post->ID, 'response', true);

        $entry['message']  = $post->post_title;
        $entry['provider'] = (string) get_post_meta($post->ID, 'sent_with', true);
        $entry['sender']   = (string) get_post_meta($post->ID, 'sms_alpha_name', true);
        $entry['response'] = $full ? $response : wp_html_excerpt($response, 300, '…');

        return $entry;

    }

    $entry['subject'] = $post->post_title;

    if($full){
        $entry['text'] = mail_log_plain_text($post->post_content);

        if($html){
            $entry['html'] = $post->post_content;
        }
    }

    return $entry;

}

/** ------------------------------------------------------------------------
 *  Redirect rules
 *  --------------------------------------------------------------------- */

/** A site path ("/old-page/") becomes a full URL on this site; full URLs pass as they are. */
function redirect_rule_absolute_url($url){

    $url = trim((string) $url);

    return strpos($url, '/') === 0 && strpos($url, '//') !== 0 ? home_url($url) : $url;

}

/** One rule as the agent sees it. */
function redirect_rule_export($post){

    $rule = array(
        'id'       => $post->ID,
        'status'   => $post->post_status,
        'old_url'  => (string) get_post_meta($post->ID, 'old_url', true),
        'new_url'  => (string) get_post_meta($post->ID, 'new_url', true),
        'code'     => (int) get_post_meta($post->ID, 'code', true),
        'edit_url' => admin_url('post.php?post=' . $post->ID . '&action=edit'),
    );

    if(theme_is_multilingual()){
        $rule['language'] = theme_post_language($post->ID);
    }

    return $rule;

}

/** Capability of the redirect-rules post type, by its generic name. */
function redirect_rules_cap($cap){

    $type = get_post_type_object('redirect-rules');

    return $type && isset($type->cap->$cap) ? $type->cap->$cap : 'manage_options';

}

/** ------------------------------------------------------------------------
 *  The abilities
 *  --------------------------------------------------------------------- */

add_action('wp_abilities_api_init', function(){

    $read_only = array('readonly' => true, 'destructive' => false, 'idempotent' => true);
    $sections = array_keys(get_custom_options());

    // ── Site status ─────────────────────────────────────────────────────

    register_theme_ability('site-status', array(
        'label'               => __('Site status', TEXTDOMAIN),
        'description'         => 'Overview of how the site theme is set up: theme and asset versions, maintenance mode, HTML cache and minification, WEBP/AVIF conversion and Imagick support, which message channels are configured (custom SMTP, SMS provider, Telegram bot), languages, and how many redirect rules, mail/SMS log entries and icons there are. Call it first to see which of the other wp-base tools make sense on this site. Never returns secrets.',
        'permission_callback' => 'theme_ability_can_manage',
        'execute_callback'    => function(){

            $theme = wp_get_theme(get_template());
            $imagick = class_exists('Imagick');
            $counts = array();

            foreach(array('redirect_rules' => 'redirect-rules', 'mail_log' => 'mail-log', 'sms_log' => 'sms-log') as $key => $type){
                $count = wp_count_posts($type);
                $counts[$key] = isset($count->publish) ? (int) $count->publish : 0;
            }

            $icons = function_exists('get_managed_icons') ? get_managed_icons() : array();
            $counts['icons'] = count($icons);

            return array(
                'theme'            => array(
                    'name'           => $theme->get('Name'),
                    'version'        => $theme->get('Version'),
                    'assets_version' => (string) get_option('assets_version'),
                ),
                'wordpress'        => get_bloginfo('version'),
                'php'              => PHP_VERSION,
                'maintenance_mode' => array(
                    'enabled'               => (bool) get_option('enable_maintenance_mode'),
                    'title'                 => (string) get_option('maintenance_mode_title'),
                    'mcp_endpoint_reachable' => class_exists('Simple_MCP'),
                ),
                'performance'      => array(
                    'html_cache'       => (bool) get_option('enable_html_cache'),
                    'html_cache_hours' => 48,
                    'minify_mode'      => (string) get_option('minify_mode'),
                ),
                'images'           => array(
                    'resize_at_upload' => (bool) get_option('enable_resize_at_upload'),
                    'webp'             => (bool) get_option('enable_webp_convert'),
                    'avif'             => (bool) get_option('enable_avif_convert'),
                    'imagick'          => $imagick,
                    'imagick_webp'     => $imagick && (bool) Imagick::queryFormats('WEBP'),
                    'imagick_avif'     => $imagick && (bool) Imagick::queryFormats('AVIF'),
                ),
                'channels'         => array(
                    'custom_smtp'  => (bool) get_option('enable_custom_smtp_server'),
                    'sms_provider' => (string) get_option('sms_service_provider'),
                    'telegram'     => (bool) (get_option('telegram_token') && get_option('telegram_chat_id')),
                ),
                'languages'        => array(
                    'multilingual' => theme_is_multilingual(),
                    'default'      => theme_default_language(),
                    'active'       => theme_language_slugs(),
                ),
                'counts'           => $counts,
            );

        },
    ), $read_only);

    // ── Settings ────────────────────────────────────────────────────────

    register_theme_ability('get-options', array(
        'label'               => __('Theme settings', TEXTDOMAIN),
        'description'         => 'Read the theme settings (Settings → Images, SMTP, Maintenance, Cookie popup, Other options and the rest): every option with its section, tab, type, label, description, current value, allowed choices and min/max/step. Password fields only report is_set — their values are never returned. Localized options (localized: true) hold one value per language; pass language to read a translation (translated: false means it is empty and visitors get the default-language value). Change values with wp-base-update-options.',
        'input_schema'        => theme_ability_schema(array(
            'section' => array(
                'type'        => 'string',
                'enum'        => $sections,
                'description' => 'Only this settings section; omit for all of them.',
            ),
        ) + theme_ability_language_property('Language for localized options.')),
        'permission_callback' => 'theme_ability_can_manage',
        'execute_callback'    => function($input){

            $language = theme_ability_language($input);

            if(is_wp_error($language)){
                return $language;
            }

            $section = isset($input['section']) ? $input['section'] : '';
            $out = array();

            foreach(theme_option_fields() as $field){
                if($section === '' || $field['section'] === $section){
                    $out[] = theme_option_export($field, $language);
                }
            }

            return array('language' => $language !== '' ? $language : theme_default_language(), 'options' => $out);

        },
    ), $read_only);

    register_theme_ability('update-options', array(
        'label'               => __('Update theme settings', TEXTDOMAIN),
        'description'         => 'Change theme settings by name, e.g. {"values": {"enable_html_cache": true, "minify_mode": "basic"}}. Read wp-base-get-options first for names, types and allowed values. Values are checked like the settings form does: checkboxes take true/false, select fields one of their choices, sliders a number within min/max on the step; code fields (raw HTML/JS) need the unfiltered_html capability. All or nothing: one invalid value rejects the whole call. Localized options are written for the given language (default language otherwise); other options ignore language. Settings can switch whole features on or off (REST API, comments, updates, SVG uploads), so change only what you were asked to. Returns the old and new value of each changed option (passwords masked).',
        'input_schema'        => theme_ability_schema(array(
            'values' => array(
                'type'                 => 'object',
                'description'          => 'Option name → new value.',
                'minProperties'        => 1,
                'additionalProperties' => true,
            ),
        ) + theme_ability_language_property('Language to write localized options in.'), array('values')),
        'permission_callback' => 'theme_ability_can_manage',
        'execute_callback'    => function($input){

            $language = theme_ability_language($input);

            return is_wp_error($language) ? $language : theme_update_options($input['values'], $language);

        },
    ), array('readonly' => false, 'destructive' => true, 'idempotent' => true));

    register_theme_ability('maintenance-mode', array(
        'label'               => __('Maintenance mode', TEXTDOMAIN),
        'description'         => 'Switch maintenance mode on or off, optionally with its title and text (HTML allowed). While it is on, visitors who are not logged in get only the maintenance page; logged-in users, wp-admin and the Simple MCP endpoint keep working, so you can switch it off again through this tool. The current state is in wp-base-site-status.',
        'input_schema'        => theme_ability_schema(array(
            'enabled' => array('type' => 'boolean', 'description' => 'true — show the maintenance page to visitors, false — open the site.'),
            'title'   => array('type' => 'string', 'description' => 'Heading of the maintenance page; omit to keep the current one.'),
            'text'    => array('type' => 'string', 'description' => 'Body of the maintenance page (HTML); omit to keep the current one.'),
        ), array('enabled')),
        'permission_callback' => 'theme_ability_can_manage',
        'execute_callback'    => function($input){

            $values = array('enable_maintenance_mode' => (bool) $input['enabled']);

            if(isset($input['title'])){
                $values['maintenance_mode_title'] = $input['title'];
            }

            if(isset($input['text'])){
                $values['maintenance_mode_text'] = $input['text'];
            }

            $result = theme_update_options($values);

            if(is_wp_error($result)){
                return $result;
            }

            return array(
                'enabled'                => (bool) get_option('enable_maintenance_mode'),
                'title'                  => (string) get_option('maintenance_mode_title'),
                'text'                   => (string) get_option('maintenance_mode_text'),
                'mcp_endpoint_reachable' => class_exists('Simple_MCP'),
                'changed'                => array_keys($result['changed']),
            );

        },
    ), array('readonly' => false, 'destructive' => true, 'idempotent' => true));

    // ── Caches and images ───────────────────────────────────────────────

    register_theme_ability('flush-cache', array(
        'label'               => __('Clear theme caches', TEXTDOMAIN),
        'description'         => 'Clear the theme\'s own caches in every language: cached ACF option and page fields, menus, redirect rules, block patterns, the colour palette, rendered SVG icons and the Timber HTML cache (on when html_cache is true in wp-base-site-status). With post_id only that page\'s cached fields and the HTML cache go. Call it when a change made outside the usual editors does not show on the site. It does not touch page-cache plugins or the object cache — that is cache_flush. bump_assets_version raises the asset version so browsers reload CSS, JS and the icon sprite.',
        'input_schema'        => theme_ability_schema(array(
            'post_id'             => array('type' => 'integer', 'minimum' => 1, 'description' => 'Page whose cached fields to clear; omit to clear everything.'),
            'bump_assets_version' => array('type' => 'boolean', 'default' => false, 'description' => 'Also raise the asset version (cache-busting for CSS, JS and the icon sprite).'),
        )),
        'permission_callback' => function($input){

            if(empty($input['post_id']) || !empty($input['bump_assets_version'])){
                return theme_ability_can_manage();
            }

            return current_user_can('edit_post', (int) $input['post_id']);

        },
        'execute_callback'    => function($input){

            $post_id = !empty($input['post_id']) ? (int) $input['post_id'] : 0;

            if($post_id && !get_post($post_id)){
                return new WP_Error('post_not_found', __('Post not found.', TEXTDOMAIN));
            }

            $result = flush_theme_caches($post_id);

            if(!empty($input['bump_assets_version'])){
                increment_assets_version();
                $result['assets_version'] = (string) get_option('assets_version');
            }

            if($result['failed']){
                $result['note'] = 'A persistent object cache without group flushing holds these; run cache_flush to clear them.';
            }

            return $result;

        },
    ), array('readonly' => false, 'destructive' => false, 'idempotent' => true));

    register_theme_ability('regenerate-images', array(
        'label'               => __('Regenerate WEBP / AVIF images', TEXTDOMAIN),
        'description'         => 'Re-encode the WEBP or AVIF copies the theme keeps next to every image, from the originals, with the current quality settings — the same as the Regenerate button in Settings → Images. Works in batches: start with offset 0 and call again with the returned offset until done is true. Only for a format that is enabled in wp-base-site-status, and only with Imagick.',
        'input_schema'        => theme_ability_schema(array(
            'format' => array('type' => 'string', 'enum' => array('webp', 'avif'), 'description' => 'Which copies to regenerate.'),
            'offset' => array('type' => 'integer', 'minimum' => 0, 'default' => 0, 'description' => 'Where to resume — the offset the previous call returned.'),
        ), array('format')),
        'permission_callback' => 'theme_ability_can_manage',
        'execute_callback'    => function($input){
            return regenerate_images_run($input['format'], isset($input['offset']) ? $input['offset'] : 0);
        },
    ), array('readonly' => false, 'destructive' => false, 'idempotent' => true));

    // ── Mail and SMS ────────────────────────────────────────────────────

    register_theme_ability('message-log', array(
        'label'               => __('Mail and SMS log', TEXTDOMAIN),
        'description'         => 'Read the log of emails (channel email) or SMS (channel sms) the site has sent, newest first. Without id: a page of entries — date (UTC), recipient, subject or message text, and for SMS the provider and the start of its response. search matches the recipient or the text. With id: one entry in full — the email as plain text (include_html adds its HTML) or the whole provider response of an SMS. Use it to check whether a form notification went out and what it said.',
        'input_schema'        => theme_ability_schema(array(
            'channel'      => array('type' => 'string', 'enum' => array('email', 'sms'), 'description' => 'Which log.'),
            'id'           => array('type' => 'integer', 'minimum' => 1, 'description' => 'Read this one entry in full.'),
            'search'       => array('type' => 'string', 'description' => 'Part of the recipient (email, phone) or of the text.'),
            'limit'        => array('type' => 'integer', 'minimum' => 1, 'maximum' => 50, 'default' => 10, 'description' => 'Entries per page.'),
            'offset'       => array('type' => 'integer', 'minimum' => 0, 'default' => 0, 'description' => 'Entries to skip.'),
            'include_html' => array('type' => 'boolean', 'default' => false, 'description' => 'With id and channel email: also return the HTML of the email.'),
        ), array('channel')),
        'permission_callback' => 'theme_ability_can_manage',
        'execute_callback'    => function($input){

            $post_type = $input['channel'] === 'sms' ? 'sms-log' : 'mail-log';

            if(!empty($input['id'])){
                $post = get_post((int) $input['id']);

                if(!$post || $post->post_type !== $post_type){
                    return new WP_Error('log_not_found', __('Log entry not found.', TEXTDOMAIN));
                }

                return message_log_entry($post, true, !empty($input['include_html']));
            }

            $limit = isset($input['limit']) ? (int) $input['limit'] : 10;
            $offset = isset($input['offset']) ? (int) $input['offset'] : 0;
            $search = isset($input['search']) ? trim($input['search']) : '';
            $query = array(
                'post_type'        => $post_type,
                'post_status'      => 'publish',
                'orderby'          => 'ID',
                'order'            => 'DESC',
                'fields'           => 'ids',
                'suppress_filters' => true,
            );

            if($search !== ''){
                // текст і отримувач — два запити: WP_Query не поєднує пошук і мету через OR
                $ids = array_unique(array_merge(
                    get_posts($query + array('posts_per_page' => -1, 's' => $search)),
                    get_posts($query + array('posts_per_page' => -1, 'meta_query' => array(array('key' => 'recipient', 'value' => $search, 'compare' => 'LIKE'))))
                ));
                rsort($ids);
                $total = count($ids);
                $ids = array_slice($ids, $offset, $limit);
            } else {
                $found = new WP_Query($query + array('posts_per_page' => $limit, 'offset' => $offset));
                $ids = $found->posts;
                $total = (int) $found->found_posts;
            }

            $entries = array();

            foreach($ids as $id){
                $entries[] = message_log_entry(get_post($id));
            }

            return array('total' => $total, 'offset' => $offset, 'entries' => $entries);

        },
    ), $read_only);

    register_theme_ability('send-test', array(
        'label'               => __('Send a test message', TEXTDOMAIN),
        'description'         => 'Send a real test message through the site\'s own channel, like the Send test widget on the dashboard: email (templated test email; recipient defaults to the site admin email), sms (needs phone; goes through the configured SMS provider and costs money) or telegram (to the configured bot chat). Use it only when asked to check that a channel works. The email and SMS land in wp-base-message-log; the result includes the provider\'s raw response.',
        'input_schema'        => theme_ability_schema(array(
            'channel' => array('type' => 'string', 'enum' => array('email', 'sms', 'telegram'), 'description' => 'Which channel to test.'),
            'email'   => array('type' => 'string', 'description' => 'Email channel: recipient; omit for the site admin email.'),
            'phone'   => array('type' => 'string', 'description' => 'SMS channel: recipient phone, e.g. +380501234567.'),
            'message' => array('type' => 'string', 'description' => 'SMS and Telegram: text to send; omit for the standard test text.'),
        ), array('channel')),
        'permission_callback' => 'theme_ability_can_manage',
        'execute_callback'    => function($input){

            $args = array('channel' => $input['channel']);

            if($input['channel'] === 'email'){
                $args['email'] = !empty($input['email']) ? $input['email'] : get_option('admin_email');
            } else {
                $args['phone'] = isset($input['phone']) ? $input['phone'] : '';
                $args['message'] = !empty($input['message']) ? $input['message'] : (
                    $input['channel'] === 'sms' ? __('Test SMS from the website', TEXTDOMAIN) : __('Test message from the website', TEXTDOMAIN)
                );
            }

            $result = send_test_run($args);

            if(empty($result['ok'])){
                return new WP_Error('send_test_failed', trim($result['message'] . ' ' . $result['response']));
            }

            return array(
                'channel'  => $result['channel'],
                'message'  => $result['message'],
                'response' => $result['response'],
            );

        },
    ), array('readonly' => false, 'destructive' => true, 'idempotent' => false, 'open_world' => true));

    // ── Redirects ───────────────────────────────────────────────────────

    register_theme_ability('list-redirects', array(
        'label'               => __('Redirect rules', TEXTDOMAIN),
        'description'         => 'List the 301/302 redirect rules the theme serves (Redirect rules in wp-admin), newest first, in every language. Each rule: id, status (publish = active; draft = parked after a failed check, not served), old_url, new_url, code. search matches either URL.',
        'input_schema'        => theme_ability_schema(array(
            'search' => array('type' => 'string', 'description' => 'Part of the old or the new URL.'),
            'status' => array('type' => 'string', 'enum' => array('any', 'publish', 'draft', 'trash'), 'default' => 'any', 'description' => 'any = active and drafts; trash = deleted rules.'),
            'limit'  => array('type' => 'integer', 'minimum' => 1, 'maximum' => 200, 'default' => 50, 'description' => 'Rules per page.'),
            'offset' => array('type' => 'integer', 'minimum' => 0, 'default' => 0, 'description' => 'Rules to skip.'),
        )),
        'permission_callback' => function(){
            return current_user_can(redirect_rules_cap('edit_posts'));
        },
        'execute_callback'    => function($input){

            $statuses = array(
                'any'     => array('publish', 'future', 'draft', 'pending', 'private'),
                'publish' => array('publish'),
                'draft'   => array('draft'),
                'trash'   => array('trash'),
            );
            $status = isset($input['status']) ? $input['status'] : 'any';
            $offset = isset($input['offset']) ? (int) $input['offset'] : 0;
            $query = array(
                'post_type'        => 'redirect-rules',
                'post_status'      => $statuses[$status],
                'posts_per_page'   => isset($input['limit']) ? (int) $input['limit'] : 50,
                'offset'           => $offset,
                'orderby'          => 'date',
                'order'            => 'DESC',
                'suppress_filters' => true, // усі мови
            );

            if(!empty($input['search'])){
                $query['meta_query'] = array(
                    'relation' => 'OR',
                    array('key' => 'old_url', 'value' => $input['search'], 'compare' => 'LIKE'),
                    array('key' => 'new_url', 'value' => $input['search'], 'compare' => 'LIKE'),
                );
            }

            $found = new WP_Query($query);

            return array(
                'total'  => (int) $found->found_posts,
                'offset' => $offset,
                'rules'  => array_map('redirect_rule_export', $found->posts),
            );

        },
    ), $read_only);

    register_theme_ability('save-redirect', array(
        'label'               => __('Save a redirect rule', TEXTDOMAIN),
        'description'         => 'Create a redirect rule, or change one by id, and make it active. old_url must be on this site (a path like /old-page/ is enough); new_url can be a path on this site or any full URL. The rule goes through the same checks as the edit screen: the URLs must differ, and no other rule may already redirect the same old_url — the error says which check failed and nothing is saved. Matching ignores the protocol, letter case and a trailing slash. Use 301 for a permanent move, 302 for a temporary one.',
        'input_schema'        => theme_ability_schema(array(
            'id'      => array('type' => 'integer', 'minimum' => 1, 'description' => 'Rule to change; omit to create a new one.'),
            'old_url' => array('type' => 'string', 'minLength' => 1, 'description' => 'Address to redirect from: a path on this site or its full URL.'),
            'new_url' => array('type' => 'string', 'minLength' => 1, 'description' => 'Where to send visitors: a path on this site or a full URL.'),
            'code'    => array('type' => 'integer', 'enum' => array(301, 302), 'default' => 301, 'description' => '301 permanent or 302 temporary.'),
        ), array('old_url', 'new_url')),
        'permission_callback' => function($input){

            if(!current_user_can(redirect_rules_cap('publish_posts'))){
                return false;
            }

            return !empty($input['id']) ? current_user_can('edit_post', (int) $input['id']) : current_user_can(redirect_rules_cap('create_posts'));

        },
        'execute_callback'    => function($input){

            $id = !empty($input['id']) ? (int) $input['id'] : 0;

            if($id){
                $post = get_post($id);

                if(!$post || $post->post_type !== 'redirect-rules' || $post->post_status === 'trash'){
                    return new WP_Error('redirect_not_found', __('Redirect rule not found.', TEXTDOMAIN));
                }
            }

            $rule = validate_redirect_rule(
                redirect_rule_absolute_url($input['old_url']),
                redirect_rule_absolute_url($input['new_url']),
                isset($input['code']) ? $input['code'] : 301,
                $id
            );

            if(is_wp_error($rule)){
                return new WP_Error($rule->get_error_code(), $rule->get_error_message());
            }

            if(!$id){
                $id = wp_insert_post(array(
                    'post_type'   => 'redirect-rules',
                    'post_status' => 'publish',
                    'post_title'  => $rule['old_url'],
                ), true);

                if(is_wp_error($id)){
                    return $id;
                }
            } elseif($post->post_status !== 'publish'){
                // правило, відкладене в чернетку після невдалої перевірки, знову діє
                wp_update_post(array('ID' => $id, 'post_status' => 'publish'));
            }

            apply_redirect_rule($id, $rule);

            return array('created' => empty($input['id'])) + redirect_rule_export(get_post($id));

        },
    ), array('readonly' => false, 'destructive' => false, 'idempotent' => false));

    register_theme_ability('delete-redirect', array(
        'label'               => __('Delete a redirect rule', TEXTDOMAIN),
        'description'         => 'Move a redirect rule to the trash: it stops working at once. It can be restored in wp-admin (Redirect rules → Trash).',
        'input_schema'        => theme_ability_schema(array(
            'id' => array('type' => 'integer', 'minimum' => 1, 'description' => 'Rule id from wp-base-list-redirects.'),
        ), array('id')),
        'permission_callback' => function($input){
            return current_user_can('delete_post', (int) $input['id']);
        },
        'execute_callback'    => function($input){

            $post = get_post((int) $input['id']);

            if(!$post || $post->post_type !== 'redirect-rules'){
                return new WP_Error('redirect_not_found', __('Redirect rule not found.', TEXTDOMAIN));
            }

            if($post->post_status === 'trash'){
                return new WP_Error('redirect_in_trash', __('The redirect rule is already in the trash.', TEXTDOMAIN));
            }

            if(!wp_trash_post($post->ID)){
                return new WP_Error('redirect_not_trashed', __('The redirect rule could not be moved to the trash.', TEXTDOMAIN));
            }

            return redirect_rule_export(get_post($post->ID));

        },
    ), array('readonly' => false, 'destructive' => true, 'idempotent' => true));

    // ── Icons ───────────────────────────────────────────────────────────

    register_theme_ability('list-icons', array(
        'label'               => __('Icons', TEXTDOMAIN),
        'description'         => 'List the SVG icons the site can show (Appearance → Icons): the theme\'s own icons (locked: true, read-only) and the ones added on this site. The id is the value icon fields in blocks and options store, and what Twig templates render with {{ icon(\'id\') }}. search matches the id.',
        'input_schema'        => theme_ability_schema(array(
            'search' => array('type' => 'string', 'description' => 'Part of the icon id.'),
        )),
        'permission_callback' => function(){
            return current_user_can('edit_posts');
        },
        'execute_callback'    => function($input){

            $search = isset($input['search']) ? strtolower(trim($input['search'])) : '';
            $icons = array();

            foreach(get_managed_icons() as $icon){
                if($search !== '' && strpos(strtolower($icon['id']), $search) === false){
                    continue;
                }

                $icons[] = array(
                    'id'      => $icon['id'],
                    'name'    => get_managed_icon_display_id($icon['id']),
                    'locked'  => $icon['locked'],
                    'viewBox' => $icon['viewBox'],
                );
            }

            return array('total' => count($icons), 'icons' => $icons);

        },
    ), $read_only);

    register_theme_ability('import-icons', array(
        'label'               => __('Import icons', TEXTDOMAIN),
        'description'         => 'Add icons from SVG markup, like the importer in Appearance → Icons: one <svg> icon (name gives its id) or a sprite of <symbol id="…"> elements (each symbol becomes an icon). The markup goes through the theme\'s SVG sanitizer: scripts, event handlers and unsafe links are removed. Icons whose id already exists, or that draw nothing, are skipped and reported — delete the old icon first if it should be replaced. For a single icon, current_color makes it follow the text colour (fill/stroke → currentColor) and viewbox crops it. The asset version is raised so browsers reload the sprite.',
        'input_schema'        => theme_ability_schema(array(
            'svg'           => array('type' => 'string', 'minLength' => 1, 'maxLength' => 5000000, 'description' => 'SVG markup: a single <svg> or a sprite with <symbol> elements.'),
            'name'          => array('type' => 'string', 'description' => 'Single icon: its name (becomes the id); omit to use the id of the <svg>.'),
            'current_color' => array('type' => 'boolean', 'default' => false, 'description' => 'Single icon: recolour fills and strokes to currentColor.'),
            'viewbox'       => array('type' => 'string', 'description' => 'Single icon: crop to this box, "x y width height".'),
        ), array('svg')),
        'permission_callback' => 'theme_ability_can_manage',
        'execute_callback'    => function($input){

            $svg = (string) $input['svg'];
            $name = isset($input['name']) ? (string) $input['name'] : '';
            $analysis = analyze_managed_icons_import($svg, $name);

            if(is_wp_error($analysis)){
                return $analysis;
            }

            $skipped = array();

            foreach($analysis['icons'] as $icon){
                if($icon['status'] !== 'add'){
                    $skipped[] = array('id' => $icon['id'] ? $icon['id'] : $icon['source_id'], 'reason' => $icon['status']);
                }
            }

            $result = import_managed_icons($svg, $name, array(
                'current_color' => !empty($input['current_color']),
                'fit_viewbox'   => !empty($input['viewbox']),
                'viewBox'       => isset($input['viewbox']) ? $input['viewbox'] : '',
            ));

            if(is_wp_error($result)){
                return $skipped ? new WP_Error($result->get_error_code(), $result->get_error_message() . ' ' . wp_json_encode($skipped)) : $result;
            }

            return array(
                'imported'       => $result['icons'],
                'skipped'        => $skipped,
                'assets_version' => (string) get_option('assets_version'),
            );

        },
    ), array('readonly' => false, 'destructive' => false, 'idempotent' => false));

    register_theme_ability('delete-icons', array(
        'label'               => __('Delete icons', TEXTDOMAIN),
        'description'         => 'Delete icons added on this site by id (wp-base-list-icons). The theme\'s own icons (locked: true) cannot be deleted. There is no trash: content that still uses a deleted id shows nothing in its place.',
        'input_schema'        => theme_ability_schema(array(
            'ids' => array('type' => 'array', 'minItems' => 1, 'items' => array('type' => 'string', 'minLength' => 1), 'description' => 'Icon ids to delete.'),
        ), array('ids')),
        'permission_callback' => 'theme_ability_can_manage',
        'execute_callback'    => function($input){

            $ids = array_values(array_unique(array_map('strval', $input['ids'])));
            $locked = array_values(array_intersect($ids, get_locked_managed_icon_ids()));

            if(count($locked) === count($ids)){
                return new WP_Error('icon_locked', __('Default icons are read-only and cannot be deleted.', TEXTDOMAIN));
            }

            $result = delete_managed_icons($ids);

            if(is_wp_error($result)){
                return $result;
            }

            return array(
                'deleted'        => $result['deleted_ids'],
                'locked'         => $locked,
                'not_found'      => array_values(array_diff($ids, $result['deleted_ids'], $locked)),
                'assets_version' => (string) get_option('assets_version'),
            );

        },
    ), array('readonly' => false, 'destructive' => true, 'idempotent' => true));

});
