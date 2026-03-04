<?php

if(!defined('ABSPATH')){exit;}

// Render pattern content for iframe preview
function pattern_preview(){

    if(is_user_logged_in() && current_user_can('edit_others_posts') && !empty($_GET['pattern-preview-id']) && $_GET['pattern-preview-id']){

        $pattern_id = intval($_GET['pattern-preview-id']);

        $post = get_post($pattern_id);
        if(!$post || $post->post_type !== 'patterns'){
            wp_die(__('Invalid pattern ID', TEXTDOMAIN));
        }

        // Enqueue block styles via get_pattern
        $content = get_pattern($pattern_id);

        ?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <link rel="stylesheet" href="<?php echo TEMPLATE_DIRECTORY_URL . 'assets/css/style.min.css?ver=' . ASSETS_VERSION; ?>">
    <title><?php echo esc_html(get_the_title($pattern_id)); ?></title>
    <?php
    // Print enqueued block styles
    wp_print_styles();
    ?>
</head>
<body class="no-header block-preview">
    <main>
        <?php echo $content; ?>
    </main>
    <?php wp_footer(); ?>
    <script src="<?php echo TEMPLATE_DIRECTORY_URL . 'assets/js/jquery.min.js?ver=3.4.1'; ?>"></script>
    <script src="<?php echo TEMPLATE_DIRECTORY_URL . 'assets/js/plugins.min.js?ver=' . ASSETS_VERSION; ?>"></script>
    <script src="<?php echo TEMPLATE_DIRECTORY_URL . 'assets/js/custom.min.js?ver=' . ASSETS_VERSION; ?>"></script>

</body>
</html><?php

        exit;
    }

}
add_action('init', 'pattern_preview');

// Add "Preview" row action to patterns list
function patterns_row_actions($actions, $post){
    if($post->post_type === 'patterns' && current_user_can('edit_others_posts')){
        $preview_url = home_url('/?pattern-preview-id=' . $post->ID);
        $actions['pattern_preview'] = '<a href="#" class="pattern-preview-link" data-url="' . esc_url($preview_url) . '">' . __('Preview', TEXTDOMAIN) . '</a>';
    }
    return $actions;
}
add_filter('post_row_actions', 'patterns_row_actions', 10, 2);
