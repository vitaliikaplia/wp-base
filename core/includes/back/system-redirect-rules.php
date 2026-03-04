<?php

if(!defined('ABSPATH')){exit;}

function add_redirect_rules_metabox() {
    // Replace default Publish box with simplified version
    remove_meta_box('submitdiv', 'redirect-rules', 'side');
    add_meta_box(
        'redirect_rules_submitdiv',
        __('Save', TEXTDOMAIN),
        'render_redirect_rules_submit_metabox',
        'redirect-rules',
        'side',
        'high'
    );

    add_meta_box(
        'redirect_rules_metabox',
        __('Redirect Rules Options', TEXTDOMAIN),
        'render_redirect_rules_metabox',
        'redirect-rules',
        'normal',
        'default'
    );
}
add_action('add_meta_boxes', 'add_redirect_rules_metabox');

add_filter('post_updated_messages', function($messages) {
    $messages['redirect-rules'] = [
        0  => '',
        1  => __('Rule updated.', TEXTDOMAIN),
        2  => __('Rule updated.', TEXTDOMAIN),
        3  => __('Rule deleted.', TEXTDOMAIN),
        4  => __('Rule updated.', TEXTDOMAIN),
        5  => false,
        6  => __('Rule added.', TEXTDOMAIN),
        7  => __('Rule saved.', TEXTDOMAIN),
        8  => __('Rule submitted.', TEXTDOMAIN),
        9  => __('Rule scheduled.', TEXTDOMAIN),
        10 => __('Rule updated.', TEXTDOMAIN),
    ];
    return $messages;
});

function render_redirect_rules_submit_metabox($post) {
    $is_new = $post->post_status === 'auto-draft';
    ?>
    <div class="submitbox" id="submitpost">
        <div style="padding: 10px 0; display: flex; align-items: center; justify-content: space-between;">
            <?php if (!$is_new) : ?>
                <a class="submitdelete deletion" href="<?php echo get_delete_post_link($post->ID); ?>">
                    <?php _e('Move to Trash', TEXTDOMAIN); ?>
                </a>
            <?php else : ?>
                <span></span>
            <?php endif; ?>
            <input type="submit" name="<?php echo $is_new ? 'publish' : 'save'; ?>" class="button button-primary button-large" value="<?php echo esc_attr($is_new ? __('Add Rule', TEXTDOMAIN) : __('Update', TEXTDOMAIN)); ?>" />
        </div>
    </div>
    <?php
}

function render_redirect_rules_metabox($post) {
    wp_nonce_field('redirect_rules_metabox', 'redirect_rules_nonce');
    $context = Timber::context();
    $context = array_merge($context, array(
        'old_url' => esc_url(get_post_meta($post->ID, 'old_url', true)),
        'new_url' => esc_url(get_post_meta($post->ID, 'new_url', true)),
        'code' => get_post_meta($post->ID, 'code', true) ?: '301',
        'protocol' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http',
        'BLOGINFO_JUST_DOMAIN' => BLOGINFO_JUST_DOMAIN,
        'home_url' => trailingslashit(home_url()),
    ));
    Timber::render( 'dashboard/redirects-meta.twig', $context);
}

function clear_redirect_rules_cache() {
    delete_transient('redirect_rules' . LANG_SUFFIX);
}

add_action('save_post', 'save_redirect_rules');
function save_redirect_rules($post_id) {
    if (!isset($_POST['redirect_rules_nonce']) || !wp_verify_nonce($_POST['redirect_rules_nonce'], 'redirect_rules_metabox')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $old_url = esc_url_raw($_POST['old_url']);
    // fix current protocol in old_url link
    $old_url = str_replace(['https://', 'http://'], $protocol.'://', $old_url);
    $new_url = esc_url_raw($_POST['new_url']);
    // fix current protocol in new_url link
    $new_url_fixed = str_replace(['https://', 'http://'], $protocol.'://', $new_url);
    $code = absint($_POST['code']);

    if (!$old_url || !$new_url || $old_url === $new_url_fixed) {
        if (get_post($post_id)) {
            wp_delete_post($post_id, true);
        }
        wp_die(__('Invalid redirect rule: Old URL and New URL must be different and not empty.', TEXTDOMAIN));
    }

    $existing_rules = new WP_Query([
        'post_type' => 'redirect-rules',
        'post__not_in' => [$post_id],
        'suppress_filters' => false, // Allow WPML to filter by current language
        'meta_query' => [
            [
                'key' => 'old_url',
                'value' => $old_url,
                'compare' => '='
            ]
        ]
    ]);

    if ($existing_rules->found_posts > 0) {
        if (get_post($post_id)) {
            wp_delete_post($post_id, true);
        }
        wp_die(__('Conflict detected: A redirect rule with this Old URL already exists.', TEXTDOMAIN));
    }

    // check if old url contains current wordpress domain, if not - wp_die
    if (strpos($old_url, BLOGINFO_JUST_DOMAIN) === false) {
        if (get_post($post_id)) {
            wp_delete_post($post_id, true);
        }
        wp_die(__('Invalid redirect rule: Old URL must contain the current WordPress domain.', TEXTDOMAIN));
    }

    update_post_meta($post_id, 'old_url', $old_url);
    update_post_meta($post_id, 'new_url', $new_url);
    update_post_meta($post_id, 'code', $code);

    // Тимчасово відключаємо хук, щоб уникнути рекурсії
    remove_action('save_post', 'save_redirect_rules');

    $old_url_display = urldecode(str_replace($protocol.'://', '', $old_url));
    $new_url_display = urldecode(str_replace($protocol.'://', '', $new_url));

    $post_update = array(
        'ID'         => $post_id,
        'post_title' => $old_url_display . ' -> ' . $new_url_display
    );
    wp_update_post( $post_update );

    // Знову підключаємо хук
    add_action('save_post', 'save_redirect_rules');

    // Очищаємо кеш
    clear_redirect_rules_cache();

}

// Очищення кешу при зміні статусу поста
add_action('transition_post_status', 'clear_redirect_rules_cache_on_status_change', 10, 3);
function clear_redirect_rules_cache_on_status_change($new_status, $old_status, $post) {
    if ($post->post_type === 'redirect-rules') {
        clear_redirect_rules_cache();
    }
}

// Очищення кешу при видаленні поста в кошик
add_action('wp_trash_post', 'clear_redirect_rules_cache', 10, 1);

// Очищення кешу при відновленні поста з кошика
add_action('untrash_post', 'clear_redirect_rules_cache', 10, 1);

// Очищення кешу при видаленні поста назавжди
add_action('before_delete_post', 'clear_redirect_rules_cache', 10, 1);

function get_redirect_rules(){
    if($redirect_rules = get_transient( 'redirect_rules'.LANG_SUFFIX )){
        return $redirect_rules;
    } else {
        $redirect_posts = get_posts([
            'post_type' => 'redirect-rules',
            'numberposts' => -1,
            'suppress_filters' => false // Allow WPML to filter by current language
        ]);
        $redirect_rules = [];
        if(!empty($redirect_posts)){
            foreach ($redirect_posts as $post) {
                $redirect_rule['old_url'] = get_post_meta($post->ID, 'old_url', true);
                $redirect_rule['new_url'] = get_post_meta($post->ID, 'new_url', true);
                $redirect_rule['code'] = get_post_meta($post->ID, 'code', true);
                $redirect_rules[] = $redirect_rule;
            }
        } else {
            $redirect_rules = ['empty' => true];
        }
        set_transient( 'redirect_rules'.LANG_SUFFIX, $redirect_rules, TRANSIENTS_TIME );
        return $redirect_rules;
    }
}

add_action('template_redirect', function () {
    $redirect_rules = get_redirect_rules();
    if (!empty($redirect_rules) && !isset($redirect_rules['empty'])) {
        $REQUEST_URI = isset($_SERVER['REQUEST_URI']) ? rtrim($_SERVER['REQUEST_URI'], '/') . '/' : '/';
        // Normalize URL: decode and lowercase for case-insensitive comparison
        $REQUEST_URI_NORMALIZED = strtolower(urldecode($REQUEST_URI));

        foreach ($redirect_rules as $rule) {
            if (!$rule['old_url'] || !$rule['new_url'] || !$rule['code']) {
                continue; // Пропускаємо цей запис, якщо якесь з полів відсутнє
            }
            // Видаляємо протокол і домен з old_url
            $old = str_replace(["https://", "http://", parse_url(get_bloginfo('url'), PHP_URL_HOST)], "", rtrim($rule['old_url'], '/') . '/');
            // Normalize old URL: decode and lowercase for case-insensitive comparison
            $old_normalized = strtolower(urldecode($old));

            if ($REQUEST_URI_NORMALIZED === $old_normalized) {
                wp_redirect($rule['new_url'], $rule['code']);
                exit;
            }
        }
    }
});

// Decode URLs in post title for admin display
add_filter('the_title', 'decode_redirect_title_in_admin', 10, 2);
function decode_redirect_title_in_admin($title, $post_id) {
    if (is_admin() && get_post_type($post_id) === 'redirect-rules') {
        return urldecode($title);
    }
    return $title;
}

/** Custom columns for redirect rules list table */
add_filter('manage_edit-redirect-rules_columns', 'redirect_rules_columns');
function redirect_rules_columns($columns) {
    $new = array();
    $new['cb'] = $columns['cb'];
    $new['redirect_actions'] = __('Actions', TEXTDOMAIN);
    $new['redirect_from'] = __('Redirect from', TEXTDOMAIN);
    $new['redirect_to']   = __('Redirect to', TEXTDOMAIN);
    $new['redirect_code']  = __('Code', TEXTDOMAIN);

    // keep WPML and other columns
    foreach ($columns as $key => $val) {
        if ( ! in_array($key, array('cb', 'title', 'date'), true) ) {
            $new[$key] = $val;
        }
    }

    $new['date'] = $columns['date'];
    return $new;
}

add_action('manage_redirect-rules_posts_custom_column', 'redirect_rules_column_content', 10, 2);
function redirect_rules_column_content($column, $post_id) {
    if ($column === 'redirect_actions') {
        $post_status = get_post_status($post_id);
        echo '<div class="redirect-rules-actions">';
        if ($post_status === 'trash') {
            $restore_url = wp_nonce_url(admin_url('post.php?action=untrash&post=' . $post_id), 'untrash-post_' . $post_id);
            $force_delete_url = get_delete_post_link($post_id, '', true);
            echo '<a href="' . esc_url($restore_url) . '" class="redirect-rules-action-btn" title="' . esc_attr__('Restore', TEXTDOMAIN) . '"><span class="dashicons dashicons-undo"></span></a>';
            echo '<a href="' . esc_url($force_delete_url) . '" class="redirect-rules-action-btn redirect-rules-action-delete" title="' . esc_attr__('Delete Permanently', TEXTDOMAIN) . '"><span class="dashicons dashicons-trash"></span></a>';
        } else {
            $edit_url = get_edit_post_link($post_id);
            $delete_url = get_delete_post_link($post_id);
            echo '<a href="' . esc_url($edit_url) . '" class="redirect-rules-action-btn" title="' . esc_attr__('Edit', TEXTDOMAIN) . '"><span class="dashicons dashicons-edit"></span></a>';
            echo '<a href="' . esc_url($delete_url) . '" class="redirect-rules-action-btn redirect-rules-action-delete" title="' . esc_attr__('Trash', TEXTDOMAIN) . '"><span class="dashicons dashicons-trash"></span></a>';
        }
        echo '</div>';
    }

    if ($column === 'redirect_from') {
        $old_url = get_post_meta($post_id, 'old_url', true);
        $display = $old_url ? urldecode(preg_replace('#^https?://#', '', rtrim($old_url, '/'))) : '—';
        if ($old_url) {
            echo '<a href="' . esc_url($old_url) . '" target="_blank">' . esc_html($display) . ' <span class="dashicons dashicons-external"></span></a>';
        } else {
            echo '—';
        }
    }

    if ($column === 'redirect_to') {
        $new_url = get_post_meta($post_id, 'new_url', true);
        $display = $new_url ? urldecode(preg_replace('#^https?://#', '', rtrim($new_url, '/'))) : '—';
        if ($new_url) {
            echo '<a href="' . esc_url($new_url) . '" target="_blank">' . esc_html($display) . ' <span class="dashicons dashicons-external"></span></a>';
        } else {
            echo '—';
        }
    }

    if ($column === 'redirect_code') {
        $code = get_post_meta($post_id, 'code', true);
        if ($code == '301') {
            echo '<span class="redirect-rules-code-badge redirect-rules-code-301">' . esc_html($code) . '</span>';
        } elseif ($code == '302') {
            echo '<span class="redirect-rules-code-badge redirect-rules-code-302">' . esc_html($code) . '</span>';
        } else {
            echo esc_html($code ?: '—');
        }
    }
}

/** Redirect Rules dashboard widget */
add_action('wp_dashboard_setup', 'add_redirect_rules_dashboard_widget');
function add_redirect_rules_dashboard_widget() {
    wp_add_dashboard_widget(
        'redirect_rules_widget',
        __('Redirect Rules', TEXTDOMAIN),
        'render_redirect_rules_dashboard_widget'
    );
}

function render_redirect_rules_dashboard_widget() {
    $posts = get_posts([
        'post_type'        => 'redirect-rules',
        'numberposts'      => 10,
        'orderby'          => 'date',
        'order'            => 'DESC',
        'suppress_filters' => false,
    ]);

    $rules = [];
    foreach ($posts as $post) {
        $old_url = get_post_meta($post->ID, 'old_url', true);
        $new_url = get_post_meta($post->ID, 'new_url', true);
        $code    = get_post_meta($post->ID, 'code', true);

        $code_class = '';
        if ($code == '301') $code_class = 'redirect-rules-code-301';
        elseif ($code == '302') $code_class = 'redirect-rules-code-302';

        $rules[] = [
            'edit_url'    => get_edit_post_link($post->ID),
            'old_display' => $old_url ? urldecode(rtrim(parse_url($old_url, PHP_URL_PATH), '/')) ?: '/' : '—',
            'new_display' => $new_url ? urldecode(rtrim(parse_url($new_url, PHP_URL_PATH), '/')) ?: '/' : '—',
            'code'        => $code,
            'code_class'  => $code_class,
        ];
    }

    $context = Timber::context();
    $context['rules']        = $rules;
    $context['view_all_url'] = admin_url('edit.php?post_type=redirect-rules');
    $context['add_new_url']  = admin_url('post-new.php?post_type=redirect-rules');

    Timber::render('dashboard/redirects-widget.twig', $context);
}
