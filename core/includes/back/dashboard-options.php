<?php

if(!defined('ABSPATH')){exit;}

function get_custom_options(){

    return array(
        'images'   =>  Array(
            'label' => __('Images', TEXTDOMAIN),
            'title' => __('Resize and optimize media while upload', TEXTDOMAIN),
            'description' => __('In this section, you can enable resizing and optimization of images while uploading them to the media library. You can specify the formats that will be resized, set the width and height of the resized images, and adjust the quality of the resized images. Additionally, you can enable the conversion of images to the WEBP format, which is a modern image format that provides better compression and quality compared to other formats.', TEXTDOMAIN),
            'fields' => Array(
                array (
                    'type'          => 'tab_start',
                    'name'          => 'resizing_at_upload',
                    'label'         => __("Resizing at upload", TEXTDOMAIN),
                ),
                array (
                    'type'          => 'checkbox',
                    'name'          => 'enable_resize_at_upload',
                    'label'         => __("Enable", TEXTDOMAIN),
                    'description'   => __("Enable resizing media while upload", TEXTDOMAIN)
                ),
                array (
                    'type'          => 'select-multiple',
                    'options'       => array (
                        'image/gif' => 'GIF',
                        'image/png' => 'PNG',
                        'image/jpeg' => 'JPEG',
                        'image/jpg' => 'JPG',
                        'image/webp' => 'WEBP',
                    ),
                    'name'         => 'resize_at_upload_formats',
                    'label'         => __("Formats", TEXTDOMAIN),
                    'description'   => __("Resize at upload formats", TEXTDOMAIN),
                    'conditional_logic' => array(
                        'action' => 'show',
                        'rules' => array(
                            array(
                                'field' => 'enable_resize_at_upload',
                                'operator' => '==',
                                'value' => '1',
                            ),
                        ),
                    ),
                ),
                array (
                    'type'          => 'range',
                    'name'          => 'resize_upload_width',
                    'tweaks'        => array(
                        'min' => '0',
                        'max' => '4096',
                        'step' => '2',
                        'suffix' => 'px',
                    ),
                    'label'         => __("Width", TEXTDOMAIN),
                    'description'   => __("Resize upload width", TEXTDOMAIN),
                    'conditional_logic' => array(
                        'action' => 'show',
                        'rules' => array(
                            array(
                                'field' => 'enable_resize_at_upload',
                                'operator' => '==',
                                'value' => '1',
                            ),
                        ),
                    ),
                ),
                array (
                    'type'          => 'range',
                    'name'          => 'resize_upload_height',
                    'tweaks'        => array(
                        'min' => '0',
                        'max' => '4096',
                        'step' => '2',
                        'suffix' => 'px',
                    ),
                    'label'         => __("Height", TEXTDOMAIN),
                    'description'   => __("Resize upload height", TEXTDOMAIN),
                    'conditional_logic' => array(
                        'action' => 'show',
                        'rules' => array(
                            array(
                                'field' => 'enable_resize_at_upload',
                                'operator' => '==',
                                'value' => '1',
                            ),
                        ),
                    ),
                ),
                array (
                    'type'          => 'range',
                    'name'          => 'resize_upload_quality',
                    'tweaks'        => array(
                        'min' => '2',
                        'max' => '100',
                        'step' => '2',
                        'suffix' => '%',
                    ),
                    'label'         => __("Quality", TEXTDOMAIN),
                    'description'   => __("Resize upload quality", TEXTDOMAIN),
                    'conditional_logic' => array(
                        'action' => 'show',
                        'rules' => array(
                            array(
                                'field' => 'enable_resize_at_upload',
                                'operator' => '==',
                                'value' => '1',
                            ),
                        ),
                    ),
                ),
                array (
                    'type'          => 'tab_end',
                ),
                array (
                    'type'          => 'tab_start',
                    'name'          => 'webp_convert',
                    'label'         => __("WEBP convert", TEXTDOMAIN),
                ),
                array (
                    'type'          => 'checkbox',
                    'name'         => 'enable_webp_convert',
                    'label'         => __("Enable", TEXTDOMAIN),
                    'description'   => __("Enable WEBP convert", TEXTDOMAIN)
                ),
                array (
                    'type'          => 'range',
                    'name'          => 'webp_convert_quality',
                    'tweaks'        => array(
                        'min' => '2',
                        'max' => '100',
                        'step' => '2',
                        'suffix' => '%',
                    ),
                    'label'         => __("Webp convert quality", TEXTDOMAIN),
                    'description'   => __("Webp convert quality", TEXTDOMAIN),
                    'conditional_logic' => array(
                        'action' => 'show',
                        'rules' => array(
                            array(
                                'field' => 'enable_webp_convert',
                                'operator' => '==',
                                'value' => '1',
                            ),
                        ),
                    ),
                ),
                array (
                    'type'          => 'tab_end',
                ),
            ),
        ),
        'smtp'   =>  Array(
            'label' => __('SMTP', TEXTDOMAIN),
            'title' => __('Configure custom SMTP server', TEXTDOMAIN),
            'description' => __('In this section, you can configure a custom SMTP server to send emails from your website. You can specify the SMTP host, port, username, password, and from name. Additionally, you can enable a secure SMTP connection using SSL.', TEXTDOMAIN),
            'fields' => Array(
                array (
                    'type'          => 'checkbox',
                    'name'          => 'enable_custom_smtp_server',
                    'label'         => __("Enable", TEXTDOMAIN),
                    'description'   => __("Enable custom SMTP server", TEXTDOMAIN),
                ),
                array (
                    'type'              => 'text',
                    'name'              => 'smtp_host',
                    'label'             => __("SMTP host", TEXTDOMAIN),
                    'conditional_logic' => array(
                        'action' => 'show',
                        'rules' => array(
                            array(
                                'field' => 'enable_custom_smtp_server',
                                'operator' => '==',
                                'value' => '1',
                            ),
                        ),
                    ),
                ),
                array (
                    'type'              => 'number',
                    'name'              => 'smtp_port',
                    'label'             => __("SMTP port", TEXTDOMAIN),
                    'conditional_logic' => array(
                        'action' => 'show',
                        'rules' => array(
                            array(
                                'field' => 'enable_custom_smtp_server',
                                'operator' => '==',
                                'value' => '1',
                            ),
                        ),
                    ),
                ),
                array (
                    'type'              => 'text',
                    'name'              => 'smtp_username',
                    'label'             => __("SMTP username", TEXTDOMAIN),
                    'conditional_logic' => array(
                        'action' => 'show',
                        'rules' => array(
                            array(
                                'field' => 'enable_custom_smtp_server',
                                'operator' => '==',
                                'value' => '1',
                            ),
                        ),
                    ),
                ),
                array (
                    'type'              => 'password',
                    'name'              => 'smtp_password',
                    'label'             => __("SMTP password", TEXTDOMAIN),
                    'conditional_logic' => array(
                        'action' => 'show',
                        'rules' => array(
                            array(
                                'field' => 'enable_custom_smtp_server',
                                'operator' => '==',
                                'value' => '1',
                            ),
                        ),
                    ),
                ),
                array (
                    'type'              => 'text',
                    'name'              => 'smtp_from_name',
                    'label'             => __("SMTP from name", TEXTDOMAIN),
                    'conditional_logic' => array(
                        'action' => 'show',
                        'rules' => array(
                            array(
                                'field' => 'enable_custom_smtp_server',
                                'operator' => '==',
                                'value' => '1',
                            ),
                        ),
                    ),
                ),
                array (
                    'type'          => 'text',
                    'name'          => 'smtp_from_email',
                    'label'         => __("SMTP from Email", TEXTDOMAIN),
                    'description'   => __("Email address that will be used as sender", TEXTDOMAIN),
                    'conditional_logic' => array(
                        'action' => 'show',
                        'rules' => array(
                            array(
                                'field' => 'enable_custom_smtp_server',
                                'operator' => '==',
                                'value' => '1',
                            ),
                        ),
                    ),
                ),
                array (
                    'type'              => 'select',
                    'name'              => 'smtp_secure',
                    'label'             => __("Encryption", TEXTDOMAIN),
                    'description'       => __("Select encryption type for SMTP connection", TEXTDOMAIN),
                    'options'           => array(
                        ''    => __("None", TEXTDOMAIN),
                        'tls' => 'TLS',
                        'ssl' => 'SSL',
                    ),
                    'conditional_logic' => array(
                        'action' => 'show',
                        'rules' => array(
                            array(
                                'field' => 'enable_custom_smtp_server',
                                'operator' => '==',
                                'value' => '1',
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'custom_code'   =>  Array(
            'label' => __('Custom code', TEXTDOMAIN),
            'title' => __('Custom HTML code for header and footer', TEXTDOMAIN),
            'description' => __('In this section, you can add custom HTML code to the header and footer of your website. The custom code will be placed inside the header tag and before the end of the body tag. You can use this feature to add custom scripts, styles, meta tags, and other elements to your website.', TEXTDOMAIN),
            'fields' => Array(
                array (
                    'type'          => 'code',
                    'name'          => 'header_custom_code',
                    'label'         => __("Header custom code", TEXTDOMAIN),
                    'description'   => __("The custom code will be placed inside the header tag", TEXTDOMAIN)
                ),
                array (
                    'type'          => 'code',
                    'name'          => 'after_body_custom_code',
                    'label'         => __("After &#x3C;body&#x3E; custom code", TEXTDOMAIN),
                    'description'   => __("The special code will be placed after the start of the body tag", TEXTDOMAIN)
                ),
                array (
                    'type'          => 'code',
                    'name'          => 'footer_custom_code',
                    'label'         => __("Footer custom code", TEXTDOMAIN),
                    'description'   => __("The special code will be placed before the end of the body tag", TEXTDOMAIN)
                ),
            ),
        ),
        'maintenance'   =>  Array(
            'label' => __('Maintenance', TEXTDOMAIN),
            'title' => __('Maintenance mode for anonymous users', TEXTDOMAIN),
            'description' => __('In this section, you can enable maintenance mode for anonymous users, customize the title and text that will be displayed on the maintenance page.', TEXTDOMAIN),
            'fields' => Array(
                array (
                    'type'          => 'checkbox',
                    'name'          => 'enable_maintenance_mode',
                    'label'         => __('Enable', TEXTDOMAIN),
                    'description'   => __('Enable maintenance mode for anonymous users', TEXTDOMAIN),
                ),
                array (
                    'type'          => 'text',
                    'name'          => 'maintenance_mode_title',
                    'label'         => __('Title', TEXTDOMAIN),
                    'description'   => __('Maintenance mode title for anonymous users', TEXTDOMAIN),
                    'conditional_logic' => array(
                        'action' => 'show',
                        'rules' => array(
                            array(
                                'field' => 'enable_maintenance_mode',
                                'operator' => '==',
                                'value' => '1',
                            ),
                        ),
                    ),
                ),
                array (
                    'type'          => 'mce',
                    'name'          => 'maintenance_mode_text',
                    'label'         => __('Text', TEXTDOMAIN),
                    'description'   => __('Maintenance mode text for anonymous users', TEXTDOMAIN),
                    'conditional_logic' => array(
                        'action' => 'show',
                        'rules' => array(
                            array(
                                'field' => 'enable_maintenance_mode',
                                'operator' => '==',
                                'value' => '1',
                            ),
                        ),
                    ),
                ),
            ),
        ),
        'integrations'   =>  Array(
            'label' => __('Integrations', TEXTDOMAIN),
            'title' => __('Integrations with third-party services options', TEXTDOMAIN),
            'fields' => Array(
                array (
                    'type'          => 'password',
                    'name'          => 'google_maps_api_key',
                    'label'         => __("Google Maps API key", TEXTDOMAIN),
                    'description'   => '<a href="https://console.cloud.google.com/apis/credentials" target="_blank">'.__('Google Cloud Console', TEXTDOMAIN).'</a>',
                ),
            ),
        ),
        'various'   =>  Array(
            'label' => __('Other options', TEXTDOMAIN),
            'title' => __('All other various options', TEXTDOMAIN),
            'description' => '',
            'fields' => Array(
                array (
                    'type'          => 'checkbox',
                    'name'          => 'disable_all_updates',
                    'label'         => __("Disable all updates", TEXTDOMAIN),
                    'description'   => __("Disable plugins and WordPress core updates", TEXTDOMAIN),
                ),
                array (
                    'type'          => 'select',
                    'options'       => array (
                        'disabled' => __('Disabled', TEXTDOMAIN),
                        'basic'    => __('Basic — remove indentation and empty lines', TEXTDOMAIN),
                        'full'     => __('Full — complete HTML minification', TEXTDOMAIN),
                    ),
                    'name'          => 'minify_mode',
                    'label'         => __("HTML minification", TEXTDOMAIN),
                    'description'   => __("Minify HTML output on frontend", TEXTDOMAIN),
                ),
                array (
                    'type'          => 'checkbox',
                    'name'          => 'minify_show_comment',
                    'label'         => __("Show minification comment", TEXTDOMAIN),
                    'description'   => __("Display HTML comment with compression stats at the end of the page", TEXTDOMAIN),
                    'conditional_logic' => array(
                        'action' => 'show',
                        'rules' => array(
                            array(
                                'field' => 'minify_mode',
                                'operator' => '==',
                                'value' => 'full',
                            ),
                        ),
                    ),
                ),
                array (
                    'type'          => 'checkbox',
                    'name'          => 'disable_customizer',
                    'label'         => __("Disable customizer", TEXTDOMAIN),
                    'description'   => __("Disable WordPress customizer", TEXTDOMAIN),
                ),
                array (
                    'type'          => 'checkbox',
                    'name'          => 'disable_src_set',
                    'label'         => __("Disable src set", TEXTDOMAIN),
                    'description'   => __("Disable src set for images", TEXTDOMAIN),
                ),
                array (
                    'type'          => 'checkbox',
                    'name'          => 'remove_default_image_sizes',
                    'label'         => __("Remove default image sizes", TEXTDOMAIN),
                    'description'   => __("Remove default image sizes in WordPress", TEXTDOMAIN),
                ),
                array (
                    'type'          => 'checkbox',
                    'name'          => 'disable_core_privacy_tools',
                    'label'         => __("Disable core privacy tools", TEXTDOMAIN),
                    'description'   => __("Disable default WordPress core privacy tools", TEXTDOMAIN),
                ),
                array (
                    'type'          => 'checkbox',
                    'name'          => 'enable_cyr3lat',
                    'label'         => __("Enable CYR3LAT", TEXTDOMAIN),
                    'description'   => __("Enable CYR3LAT transliteration", TEXTDOMAIN),
                ),
                array (
                    'type'          => 'checkbox',
                    'name'          => 'disable_dns_prefetch',
                    'label'         => __("Disable DNS prefetch", TEXTDOMAIN),
                    'description'   => __("Disable DNS prefetch for external resources", TEXTDOMAIN),
                ),
                array (
                    'type'          => 'checkbox',
                    'name'          => 'disable_rest_api',
                    'label'         => __("Disable Rest API", TEXTDOMAIN),
                    'description'   => __("Disable Rest API for anonymous users", TEXTDOMAIN),
                ),
                array (
                    'type'          => 'checkbox',
                    'name'          => 'disable_emojis',
                    'label'         => __("Disable Emojis", TEXTDOMAIN),
                    'description'   => __("Disable default WordPress Emojis", TEXTDOMAIN),
                ),
                array (
                    'type'          => 'checkbox',
                    'name'          => 'disable_embeds',
                    'label'         => __("Disable Embeds", TEXTDOMAIN),
                    'description'   => __("Disable default WordPress Embeds", TEXTDOMAIN),
                ),
                array (
                    'type'          => 'checkbox',
                    'name'          => 'hide_dashboard_widgets',
                    'label'         => __("Disable dashboard widgets", TEXTDOMAIN),
                    'description'   => __("Disable default WordPress dashboard widgets", TEXTDOMAIN),
                ),
                array (
                    'type'          => 'checkbox',
                    'name'          => 'hide_admin_top_bar',
                    'label'         => __("Hide admin top bar", TEXTDOMAIN),
                    'description'   => __("Hide admin top bar for all users on front-end", TEXTDOMAIN),
                ),
                array (
                    'type'          => 'checkbox',
                    'name'          => 'disable_admin_email_verification',
                    'label'         => __("Disable admin email verification", TEXTDOMAIN),
                    'description'   => __("Disable default WordPress admin email verification", TEXTDOMAIN),
                ),
                array (
                    'type'          => 'checkbox',
                    'name'          => 'disable_comments',
                    'label'         => __("Disable comments", TEXTDOMAIN),
                    'description'   => __("Disable comments on all posts and pages", TEXTDOMAIN),
                ),
                array (
                    'type'          => 'checkbox',
                    'name'          => 'delete_child_media',
                    'label'         => __("Delete child media", TEXTDOMAIN),
                    'description'   => __("Delete child media when parent post is deleted", TEXTDOMAIN),
                ),
                array (
                    'type'          => 'checkbox',
                    'name'          => 'enable_html_cache',
                    'label'         => __("Enable HTML cache", TEXTDOMAIN),
                    'description'   => __("Enable HTML page cache for anonymous users", TEXTDOMAIN),
                ),
                array (
                    'type'          => 'checkbox',
                    'name'          => 'hide_acf',
                    'label'         => __("Hide ACF", TEXTDOMAIN),
                    'description'   => __("Hide Advanced Custom Fields from Dashboard", TEXTDOMAIN)
                ),
                array (
                    'type'          => 'checkbox',
                    'name'          => 'disable_application_passwords',
                    'label'         => __("Disable application passwords", TEXTDOMAIN),
                    'description'   => __("Disable WordPress application passwords used for REST API authentication and third-party integrations", TEXTDOMAIN),
                ),
                array (
                    'type'    => 'select',
                    'name'    => 'disable_gutenberg',
                    'label'   => __("Disable Gutenberg", TEXTDOMAIN),
                    'description'   => __("Disable Gutenberg editor for posts and pages", TEXTDOMAIN),
                    'options' => array(
                        ''           => '—',
                        'blog'       => __("For Blog", TEXTDOMAIN),
                        'everywhere' => __("Everywhere", TEXTDOMAIN),
                    ),
                ),
                array (
                    'type'          => 'checkbox',
                    'name'          => 'parse_all_pages_blocks_as_gutenberg_patterns',
                    'label'         => __("Parse blocks", TEXTDOMAIN),
                    'description'         => __("Parse all pages blocks as Gutenberg patterns", TEXTDOMAIN),
                    'conditional_logic' => array(
                        'action' => 'show',
                        'rules' => array(
                            array(
                                'field' => 'disable_gutenberg',
                                'operator' => '!=',
                                'value' => 'everywhere',
                            ),
                        ),
                    ),
                ),
                array (
                    'type'          => 'color',
                    'name'          => 'theme_color',
                    'label'         => __("Theme color", TEXTDOMAIN),
                    'description'   => __("Set theme color for browsers that support it", TEXTDOMAIN),
                ),
            ),
        ),
    );
}

// Add options pages
add_action('admin_menu', function() {
    foreach (get_custom_options() as $key=>$value) {
        add_submenu_page(
            'options-general.php', // вказуємо null, щоб сторінка не зʼявлялась у підменю
            $value['label'],
            $value['label'],
            'manage_options',
            $key,
            function() use ($value, $key) {
                echo '<div class="wrap">';
                echo '<h1>' . (!empty($value['title']) ? $value['title'] : $value['label']).'</h1>';
                echo '<form method="post" action="options.php" class="custom-options-form">';
                if(!empty($value['description'])){
                    echo '<p>'.$value['description'].'</p>';
                }
                settings_fields($key.'_settings');
                $context = Timber::context();
                $context['options'] = $value['fields'];
                Timber::render( 'dashboard/options.twig', $context);
                submit_button();
                echo '</form>';
                echo '</div>';
            }
        );
    }
});

// Register settings
add_action('admin_init', function() {
    foreach (get_custom_options() as $key=>$value) {
        foreach ($value['fields'] as $field) {
            if($field['type'] == 'tab_start' || $field['type'] == 'tab_end'){
                continue;
            }
            register_setting($key.'_settings', $field['name']);
        }
    }
});

// WPML integration for localized options
if( defined('ICL_LANGUAGE_CODE' ) ){
    add_action( 'init', function() {
        foreach (get_custom_options() as $key=>$value) {
            foreach ($value['fields'] as $field) {
                if (isset($field['localize']) && $field['localize']) {
                    do_action( 'wpml_multilingual_options', $field['name'] );
                }
            }
        }
    });
    do_action( 'wpml_multilingual_options', 'blogname' );
    do_action( 'wpml_multilingual_options', 'blogdescription' );
    add_filter('pre_option', function($pre_option, $option, $default) {
        if ((defined('REST_REQUEST') && REST_REQUEST) || is_admin() || $pre_option !== false) {
            return $pre_option;
        }

        global $sitepress, $wpdb;

        if (!$sitepress) {
            return $pre_option;
        }

        $current_lang = $sitepress->get_current_language();
        $default_lang = $sitepress->get_default_language();

        if ($current_lang !== $default_lang) {
            $localized_option = $option . '_' . $current_lang;
            $localized_value = $wpdb->get_var($wpdb->prepare(
                "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s LIMIT 1",
                $localized_option
            ));

            if ($localized_value !== null) {
                return maybe_unserialize($localized_value);
            }
        }

        return $pre_option;
    }, 10, 3);
}

/** options assets */
function custom_options_assets(){
    global $pagenow;
    if($pagenow == "options-general.php" && !empty($_GET['page'])){
        $custom_pages = array_keys(get_custom_options());
        if(in_array($_GET['page'], $custom_pages)){
            wp_register_script( 'custom-options', TEMPLATE_DIRECTORY_URL . 'assets/js/custom-options.min.js', '', ASSETS_VERSION, true);
            wp_enqueue_script( 'custom-options' );
            wp_enqueue_script('wplink');
            wp_enqueue_style( 'editor-buttons' );
            wp_enqueue_style( 'wp-color-picker' );
            wp_enqueue_script( 'wp-color-picker' );
            wp_register_style( 'custom-options', TEMPLATE_DIRECTORY_URL . 'assets/css/custom-options.min.css', array(), ASSETS_VERSION );
            wp_enqueue_style( 'custom-options' );
        }
    }
}
add_action( 'admin_enqueue_scripts', 'custom_options_assets' );

/** wplink dialog */
function custom_options_wplink_dialog(){
    global $pagenow;
    if($pagenow == "options-general.php" && !empty($_GET['page'])){
        $custom_pages = array_keys(get_custom_options());
        if(in_array($_GET['page'], $custom_pages)){
            if(!class_exists('_WP_Editors', false)){
                require_once ABSPATH . 'wp-includes/class-wp-editor.php';
            }
            _WP_Editors::wp_link_dialog();
        }
    }
}
add_action( 'admin_footer', 'custom_options_wplink_dialog' );
