<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * ACF Field Groups Order Column
 * Adds a sortable "Order" column to ACF Field Groups admin list
 */
// Add Order column
add_filter('manage_acf-field-group_posts_columns', function($columns) {
    // Debug: uncomment to see column keys
    // echo '<pre>'; print_r(array_keys($columns)); echo '</pre>';

    $new_columns = [];
    foreach ($columns as $key => $value) {
        $new_columns[$key] = $value;
        // Insert after title column (check both possible keys)
        if ($key === 'acf-fg-title' || $key === 'title') {
            $new_columns['acf_menu_order'] = __('Order', 'acf');
        }
    }

    // Fallback: if column wasn't added, add it at the end
    if (!isset($new_columns['acf_menu_order'])) {
        $new_columns['acf_menu_order'] = __('Order', 'acf');
    }

    return $new_columns;
}, 99);

// Render Order column content
add_action('manage_acf-field-group_posts_custom_column', function($column, $post_id) {
    if ($column === 'acf_menu_order') {
        $post = get_post($post_id);
        $order = $post->menu_order;
        ?>
        <span class="acf-order-value" data-post-id="<?php echo esc_attr($post_id); ?>">
            <span class="acf-order-display"><?php echo esc_html($order); ?></span>
            <input type="number"
                   class="acf-order-input"
                   value="<?php echo esc_attr($order); ?>"
                   min="0"
                   step="1"
                   style="display: none; width: 60px;">
        </span>
        <?php
    }
}, 10, 2);

// Make Order column sortable
add_filter('manage_edit-acf-field-group_sortable_columns', function($columns) {
    $columns['acf_menu_order'] = 'menu_order';
    return $columns;
});

// Enqueue scripts and styles
add_action('admin_enqueue_scripts', function($hook) {
    global $typenow;

    if ($hook !== 'edit.php' || $typenow !== 'acf-field-group') {
        return;
    }

    wp_enqueue_script(
        'acf-order-column',
        get_template_directory_uri() . '/assets/js/dashboard/acf-order-column.js',
        ['jquery'],
        '1.0.0',
        true
    );

    wp_localize_script('acf-order-column', 'acfOrderColumn', [
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('acf_order_column_nonce'),
    ]);

    wp_add_inline_style('wp-admin', '
        .acf-order-value {
            cursor: pointer;
        }
        .acf-order-display:hover {
            color: #2271b1;
            text-decoration: underline;
        }
        .acf-order-input {
            padding: 2px 5px;
        }
        .acf-order-saving {
            opacity: 0.5;
            pointer-events: none;
        }
    ');
});

// AJAX handler for updating menu order
add_action('wp_ajax_acf_update_menu_order', function() {
    check_ajax_referer('acf_order_column_nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Permission denied']);
    }

    $post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
    $order = isset($_POST['order']) ? absint($_POST['order']) : 0;

    if (!$post_id) {
        wp_send_json_error(['message' => 'Invalid post ID']);
    }

    // Verify it's an ACF field group
    if (get_post_type($post_id) !== 'acf-field-group') {
        wp_send_json_error(['message' => 'Invalid post type']);
    }

    $result = wp_update_post([
        'ID'         => $post_id,
        'menu_order' => $order,
    ]);

    if (is_wp_error($result)) {
        wp_send_json_error(['message' => $result->get_error_message()]);
    }

    // Update ACF JSON file to sync menu_order
    if (function_exists('acf_get_field_group')) {
        $field_group = acf_get_field_group($post_id);
        if ($field_group) {
            // Update menu_order in field group data
            $field_group['menu_order'] = $order;
            // This will trigger ACF's JSON save
            acf_update_field_group($field_group);
        }
    }

    wp_send_json_success(['order' => $order]);
});
