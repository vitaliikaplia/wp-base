<?php

if(!defined('ABSPATH')){exit;}

function get_custom_options(){

    return array(
        'images'   =>  Array(
            'label' => __('Images', TEXTDOMAIN),
            'title' => __('Resize and optimize media while upload', TEXTDOMAIN),
            'description' => __('In this section, you can enable resizing and optimization of images while uploading them to the media library. You can specify the formats that will be resized, set the width and height of the resized images, and adjust the quality of the resized images. Additionally, you can enable the conversion of images to the WEBP and AVIF formats, which are modern image formats that provide better compression and quality compared to other formats. Both are optional and independent of each other; AVIF is offered to the browser first and WEBP second.', TEXTDOMAIN),
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
                    'default'       => '90',
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
                    'type'          => 'regenerate_images',
                    'name'          => 'regenerate_webp',
                    'label'         => __("Regenerate WEBP", TEXTDOMAIN),
                    'description'   => __("Rebuilds the WEBP copy of every original image with the quality set above. Existing files are overwritten.", TEXTDOMAIN),
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
                array (
                    'type'          => 'tab_start',
                    'name'          => 'avif_convert',
                    'label'         => __("AVIF convert", TEXTDOMAIN),
                ),
                array (
                    'type'          => 'checkbox',
                    'name'          => 'enable_avif_convert',
                    'label'         => __("Enable", TEXTDOMAIN),
                    'description'   => __("Enable AVIF convert. AVIF is offered before WEBP; browsers that do not support it fall back automatically.", TEXTDOMAIN)
                ),
                // Two qualities rather than one: at the same visual fidelity flat
                // graphics and screenshots compress about twice as hard as
                // photographs, so a shared number would have to be set for the
                // worst case and would give away the difference on everything else.
                array (
                    'type'          => 'range',
                    'name'          => 'avif_convert_quality_photo',
                    'default'       => '60',
                    'tweaks'        => array(
                        'min' => '2',
                        'max' => '100',
                        'step' => '2',
                        'suffix' => '%',
                    ),
                    'label'         => __("AVIF quality for photos (JPEG)", TEXTDOMAIN),
                    'description'   => __("Photographs need a higher setting to stay below the WEBP of the same image. Defaults to 60 when left empty.", TEXTDOMAIN),
                    'conditional_logic' => array(
                        'action' => 'show',
                        'rules' => array(
                            array(
                                'field' => 'enable_avif_convert',
                                'operator' => '==',
                                'value' => '1',
                            ),
                        ),
                    ),
                ),
                array (
                    'type'          => 'range',
                    'name'          => 'avif_convert_quality_graphics',
                    'default'       => '56',
                    'tweaks'        => array(
                        'min' => '2',
                        'max' => '100',
                        'step' => '2',
                        'suffix' => '%',
                    ),
                    'label'         => __("AVIF quality for graphics (PNG, GIF)", TEXTDOMAIN),
                    'description'   => __("Screenshots and flat graphics stay sharp at a lower setting. Defaults to 56 when left empty.", TEXTDOMAIN),
                    'conditional_logic' => array(
                        'action' => 'show',
                        'rules' => array(
                            array(
                                'field' => 'enable_avif_convert',
                                'operator' => '==',
                                'value' => '1',
                            ),
                        ),
                    ),
                ),
                array (
                    'type'          => 'regenerate_images',
                    'name'          => 'regenerate_avif',
                    'label'         => __("Regenerate AVIF", TEXTDOMAIN),
                    'description'   => __("Rebuilds the AVIF copy of every original image with the qualities set above. Existing files are overwritten.", TEXTDOMAIN),
                    'conditional_logic' => array(
                        'action' => 'show',
                        'rules' => array(
                            array(
                                'field' => 'enable_avif_convert',
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
                    'type'          => 'tab_start',
                    'name'          => 'google',
                    'label'         => __("Google Maps", TEXTDOMAIN),
                ),
                array (
                    'type'          => 'password',
                    'name'          => 'google_maps_api_key',
                    'label'         => __("Google Maps API key", TEXTDOMAIN),
                    'description'   => '<a href="https://console.cloud.google.com/apis/credentials" target="_blank">'.__('Google Cloud Console', TEXTDOMAIN).'</a>',
                ),
                array (
                    'type'          => 'tab_end',
                ),
                array (
                    'type'          => 'tab_start',
                    'name'          => 'telegram_bot',
                    'label'         => __("Telegram Bot", TEXTDOMAIN),
                ),
                array (
                    'type'          => 'password',
                    'name'          => 'telegram_token',
                    'label'         => __("Telegram token", TEXTDOMAIN),
                    'description'   => __("Telegram token to integrate with Telegram bot", TEXTDOMAIN) . ', <a href="https://core.telegram.org/bots#6-botfather" target="_blank">'.__('link', TEXTDOMAIN).'</a>',
                ),
                array (
                    'type'          => 'text',
                    'name'          => 'telegram_chat_id',
                    'label'         => __("Telegram chat ID", TEXTDOMAIN),
                    'description'   => __("Telegram chat ID to integrate with Telegram bot", TEXTDOMAIN),
                ),
                array (
                    'type'          => 'text',
                    'name'          => 'telegram_chat_thread_id',
                    'label'         => __("Telegram chat thread ID", TEXTDOMAIN),
                    'description'   => __("Optional message thread (topic) ID for forum-style group chats", TEXTDOMAIN),
                ),
                array (
                    'type'          => 'tab_end',
                ),
                array (
                    'type'          => 'tab_start',
                    'name'          => 'sms',
                    'label'         => __("SMS Services", TEXTDOMAIN),
                ),
                array (
                    'type'          => 'select',
                    'options'       => array (
                        'sms_fly' => __('SMS-Fly', TEXTDOMAIN),
                        'turbo_sms' => __('Turbo SMS', TEXTDOMAIN)
                    ),
                    'name'         => 'sms_service_provider',
                    'label'         => __("SMS service provider", TEXTDOMAIN),
                    'description'   => __("Select SMS service provider to integrate with SMS service", TEXTDOMAIN)
                ),
                array (
                    'type'          => 'password',
                    'name'          => 'sms_fly_api_key',
                    'label'         => __("SMS-Fly API key", TEXTDOMAIN),
                    'description'   => __("SMS-Fly REST API v2 key. Generate it in your SMS-Fly account — the old username/password XML gateway is no longer used", TEXTDOMAIN) . ', <a href="https://sms-fly.ua/integration/api/" target="_blank">'.__('link', TEXTDOMAIN).'</a>',
                    'conditional_logic' => array(
                        'action' => 'show',
                        'rules' => array(
                            array(
                                'field' => 'sms_service_provider',
                                'operator' => '==',
                                'value' => 'sms_fly',
                            ),
                        ),
                    ),
                ),
                array (
                    'type'          => 'text',
                    'name'          => 'sms_fly_alpha_name',
                    'label'         => __("SMS-Fly alpha name", TEXTDOMAIN),
                    'description'   => __("SMS-Fly alpha name what will be used as SMS sender", TEXTDOMAIN),
                    'conditional_logic' => array(
                        'action' => 'show',
                        'rules' => array(
                            array(
                                'field' => 'sms_service_provider',
                                'operator' => '==',
                                'value' => 'sms_fly',
                            ),
                        ),
                    ),
                ),
                array (
                    'type'          => 'password',
                    'name'          => 'turbo_sms_token',
                    'label'         => __("Turbo SMS token", TEXTDOMAIN),
                    'description'   => __("Turbo SMS token to integrate with Turbo SMS service", TEXTDOMAIN) . ', <a href="https://turbosms.ua/ua/route.html" target="_blank">'.__('link', TEXTDOMAIN).'</a>, <a href="https://turbosms.ua/ua/api.html" target="_blank">'.__('link', TEXTDOMAIN).'</a>',
                    'conditional_logic' => array(
                        'action' => 'show',
                        'rules' => array(
                            array(
                                'field' => 'sms_service_provider',
                                'operator' => '==',
                                'value' => 'turbo_sms',
                            ),
                        ),
                    ),
                ),
                array (
                    'type'          => 'text',
                    'name'          => 'turbo_sms_alpha_name',
                    'label'         => __("Turbo SMS alpha name", TEXTDOMAIN),
                    'description'   => __("Turbo SMS alpha name what will be used as SMS sender", TEXTDOMAIN),
                    'conditional_logic' => array(
                        'action' => 'show',
                        'rules' => array(
                            array(
                                'field' => 'sms_service_provider',
                                'operator' => '==',
                                'value' => 'turbo_sms',
                            ),
                        ),
                    ),
                ),
                array (
                    'type'          => 'tab_end',
                ),
                array (
                    'type'          => 'tab_start',
                    'name'          => 'schema_org',
                    'label'         => __("Schema.org (JSON-LD)", TEXTDOMAIN),
                ),
                array (
                    'type'          => 'checkbox',
                    'name'          => 'enable_schema_json_ld',
                    'label'         => __("Enable JSON-LD", TEXTDOMAIN),
                    'description'   => __("Output Organization / WebSite / WebPage / Article structured data in the head", TEXTDOMAIN),
                ),
                array (
                    'type'          => 'text',
                    'name'          => 'schema_org_name',
                    'label'         => __("Organization name", TEXTDOMAIN),
                    'description'   => __("Defaults to the site title", TEXTDOMAIN),
                ),
                array (
                    'type'          => 'textarea',
                    'name'          => 'schema_org_description',
                    'label'         => __("Organization description", TEXTDOMAIN),
                ),
                array (
                    'type'          => 'select',
                    'options'       => array(
                        'Organization'        => __('Organization', TEXTDOMAIN),
                        'LocalBusiness'       => __('Local Business', TEXTDOMAIN),
                        'ProfessionalService' => __('Professional Service', TEXTDOMAIN),
                    ),
                    'name'          => 'schema_org_type',
                    'label'         => __("Organization type", TEXTDOMAIN),
                ),
                array (
                    'type'          => 'text',
                    'name'          => 'schema_org_email',
                    'label'         => __("Organization email", TEXTDOMAIN),
                ),
                array (
                    'type'          => 'text',
                    'name'          => 'schema_org_phone',
                    'label'         => __("Organization phone", TEXTDOMAIN),
                ),
                array (
                    'type'          => 'text',
                    'name'          => 'schema_org_street',
                    'label'         => __("Street address", TEXTDOMAIN),
                ),
                array (
                    'type'          => 'text',
                    'name'          => 'schema_org_city',
                    'label'         => __("City", TEXTDOMAIN),
                ),
                array (
                    'type'          => 'text',
                    'name'          => 'schema_org_postal_code',
                    'label'         => __("Postal code", TEXTDOMAIN),
                ),
                array (
                    'type'          => 'text',
                    'name'          => 'schema_org_country',
                    'label'         => __("Country code (e.g. UA)", TEXTDOMAIN),
                ),
                array (
                    'type'          => 'tab_end',
                ),
            ),
        ),
        'cookie_popup'   =>  Array(
            'label' => __('Cookie popup', TEXTDOMAIN),
            'title' => __('Cookie consent popup', TEXTDOMAIN),
            'description' => __('In this section, you can configure the cookie consent popup. The popup is displayed only when the title, text and button are all filled in.', TEXTDOMAIN),
            'fields' => Array(
                array (
                    'type'          => 'text',
                    'name'          => 'cookie_popup_title',
                    'label'         => __('Title', TEXTDOMAIN),
                    'description'   => __('Cookie popup title', TEXTDOMAIN),
                    'localize'      => true,
                ),
                array (
                    'type'          => 'mce',
                    'name'          => 'cookie_popup_text',
                    'label'         => __('Text', TEXTDOMAIN),
                    'description'   => __('Cookie popup text', TEXTDOMAIN),
                    'localize'      => true,
                ),
                array (
                    'type'          => 'text',
                    'name'          => 'cookie_popup_button_title',
                    'label'         => __('Button', TEXTDOMAIN),
                    'description'   => __('Cookie popup button label', TEXTDOMAIN),
                    'localize'      => true,
                ),
            ),
        ),
        'various'   =>  Array(
            'label' => __('Other options', TEXTDOMAIN),
            'title' => __('All other various options', TEXTDOMAIN),
            'description' => '',
            'fields' => Array(
                array (
                    'type'          => 'tab_start',
                    'name'          => 'tab_performance',
                    'label'         => __("Performance", TEXTDOMAIN),
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
                    'name'          => 'enable_html_cache',
                    'label'         => __("Enable HTML cache", TEXTDOMAIN),
                    'description'   => __("Enable HTML page cache for anonymous users", TEXTDOMAIN),
                ),
                array (
                    'type'          => 'tab_end',
                ),
                array (
                    'type'          => 'tab_start',
                    'name'          => 'tab_media',
                    'label'         => __("Media", TEXTDOMAIN),
                ),
                array (
                    'type'          => 'checkbox',
                    'name'          => 'allow_m3u_ts_upload',
                    'label'         => __("Allow .m3u / .m3u8 / .ts uploads", TEXTDOMAIN),
                    'description'   => __("Permit HLS playlist and MPEG-TS segment uploads in the media library", TEXTDOMAIN),
                ),
                array (
                    'type'          => 'checkbox',
                    'name'          => 'remove_default_image_sizes',
                    'label'         => __("Remove default image sizes", TEXTDOMAIN),
                    'description'   => __("Remove default image sizes in WordPress", TEXTDOMAIN),
                ),
                array (
                    'type'          => 'checkbox',
                    'name'          => 'disable_src_set',
                    'label'         => __("Disable src set", TEXTDOMAIN),
                    'description'   => __("Disable src set for images", TEXTDOMAIN),
                ),
                array (
                    'type'          => 'checkbox',
                    'name'          => 'delete_child_media',
                    'label'         => __("Delete child media", TEXTDOMAIN),
                    'description'   => __("Delete child media when parent post is deleted", TEXTDOMAIN),
                ),
                array (
                    'type'          => 'tab_end',
                ),
                array (
                    'type'          => 'tab_start',
                    'name'          => 'tab_security',
                    'label'         => __("Security", TEXTDOMAIN),
                ),
                array (
                    'type'          => 'checkbox',
                    'name'          => 'disable_all_updates',
                    'label'         => __("Disable all updates", TEXTDOMAIN),
                    'description'   => __("Disable plugins and WordPress core updates", TEXTDOMAIN),
                ),
                array (
                    'type'          => 'checkbox',
                    'name'          => 'disable_rest_api',
                    'label'         => __("Disable Rest API", TEXTDOMAIN),
                    'description'   => __("Disable Rest API for anonymous users", TEXTDOMAIN),
                ),
                array (
                    'type'          => 'checkbox',
                    'name'          => 'disable_application_passwords',
                    'label'         => __("Disable application passwords", TEXTDOMAIN),
                    'description'   => __("Disable WordPress application passwords used for REST API authentication and third-party integrations", TEXTDOMAIN),
                ),
                array (
                    'type'          => 'checkbox',
                    'name'          => 'disable_core_privacy_tools',
                    'label'         => __("Disable core privacy tools", TEXTDOMAIN),
                    'description'   => __("Disable default WordPress core privacy tools", TEXTDOMAIN),
                ),
                array (
                    'type'          => 'checkbox',
                    'name'          => 'disable_connectors_page',
                    'label'         => __("Disable Connectors admin page", TEXTDOMAIN),
                    'description'   => __("Hide and block the wp-admin Connectors settings page", TEXTDOMAIN),
                ),
                array (
                    'type'          => 'checkbox',
                    'name'          => 'allow_svg_upload',
                    'label'         => __("Allow SVG uploads", TEXTDOMAIN),
                    'description'   => __("An SVG is a script-capable document. Every upload is sanitized (scripts, event handlers and javascript: URIs are stripped) and files that cannot be cleaned are rejected — but only enable this when the editors are trusted.", TEXTDOMAIN),
                ),
                array (
                    'type'          => 'checkbox',
                    'name'          => 'trust_proxy_headers',
                    'label'         => __("Trust proxy IP headers", TEXTDOMAIN),
                    'description'   => __("Read the visitor IP from CF-Connecting-IP / X-Forwarded-For. Enable only behind Cloudflare or another reverse proxy — otherwise any visitor can spoof their IP in logs and geolocation.", TEXTDOMAIN),
                ),
                array (
                    'type'          => 'tab_end',
                ),
                array (
                    'type'          => 'tab_start',
                    'name'          => 'tab_admin',
                    'label'         => __("Admin", TEXTDOMAIN),
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
                    'name'          => 'hide_acf',
                    'label'         => __("Hide ACF", TEXTDOMAIN),
                    'description'   => __("Hide Advanced Custom Fields from Dashboard", TEXTDOMAIN)
                ),
                array (
                    'type'          => 'checkbox',
                    'name'          => 'disable_admin_email_verification',
                    'label'         => __("Disable admin email verification", TEXTDOMAIN),
                    'description'   => __("Disable default WordPress admin email verification", TEXTDOMAIN),
                ),
                array (
                    'type'          => 'checkbox',
                    'name'          => 'disable_customizer',
                    'label'         => __("Disable customizer", TEXTDOMAIN),
                    'description'   => __("Disable WordPress customizer", TEXTDOMAIN),
                ),
                array (
                    'type'          => 'tab_end',
                ),
                array (
                    'type'          => 'tab_start',
                    'name'          => 'tab_content',
                    'label'         => __("Content", TEXTDOMAIN),
                ),
                array (
                    'type'          => 'checkbox',
                    'name'          => 'disable_comments',
                    'label'         => __("Disable comments", TEXTDOMAIN),
                    'description'   => __("Disable comments on all posts and pages", TEXTDOMAIN),
                ),
                array (
                    'type'          => 'checkbox',
                    'name'          => 'disable_blog_tags',
                    'label'         => __("Disable blog tags", TEXTDOMAIN),
                    'description'   => __("Detach the post_tag taxonomy from posts (categories only)", TEXTDOMAIN),
                ),
                array (
                    'type'          => 'checkbox',
                    'name'          => 'enable_cyr3lat',
                    'label'         => __("Enable CYR3LAT", TEXTDOMAIN),
                    'description'   => __("Enable CYR3LAT transliteration", TEXTDOMAIN),
                ),
                array (
                    'type'          => 'tab_end',
                ),
                array (
                    'type'          => 'tab_start',
                    'name'          => 'tab_frontend',
                    'label'         => __("Frontend", TEXTDOMAIN),
                ),
                array (
                    'type'          => 'checkbox',
                    'name'          => 'enable_external_link_page',
                    'label'         => __("External link interstitial", TEXTDOMAIN),
                    'description'   => __("Route outbound links through a \"you are leaving\" countdown page", TEXTDOMAIN),
                ),
                array (
                    'type'          => 'checkbox',
                    'name'          => 'disable_dns_prefetch',
                    'label'         => __("Disable DNS prefetch", TEXTDOMAIN),
                    'description'   => __("Disable DNS prefetch for external resources", TEXTDOMAIN),
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
                    'type'          => 'tab_end',
                ),
                array (
                    'type'          => 'tab_start',
                    'name'          => 'tab_wp7',
                    'label'         => __("WordPress 7", TEXTDOMAIN),
                ),
                array (
                    'type'          => 'checkbox',
                    'name'          => 'disable_wp_block_library_styles',
                    'label'         => __("Disable WordPress block library styles", TEXTDOMAIN),
                    'description'   => __("Remove default Gutenberg block library CSS from the frontend when the theme provides all required block styling.", TEXTDOMAIN),
                ),
                array (
                    'type'          => 'checkbox',
                    'name'          => 'disable_wp_global_styles',
                    'label'         => __("Disable WordPress global styles", TEXTDOMAIN),
                    'description'   => __("Remove Gutenberg global, stored and classic theme styles from the frontend.", TEXTDOMAIN),
                ),
                array (
                    'type'          => 'checkbox',
                    'name'          => 'disable_wp_core_block_supports',
                    'label'         => __("Disable WordPress block support styles", TEXTDOMAIN),
                    'description'   => __("Remove late core block support styles generated for layout, spacing and duotone support.", TEXTDOMAIN),
                ),
                array (
                    'type'          => 'checkbox',
                    'name'          => 'disable_wp_font_library_output',
                    'label'         => __("Disable WordPress Font Library output", TEXTDOMAIN),
                    'description'   => __("Remove native frontend Font Library @font-face output when custom theme font CSS replaces it.", TEXTDOMAIN),
                ),
                array (
                    'type'          => 'checkbox',
                    'name'          => 'disable_wp_font_library_manager',
                    'label'         => __("Disable WordPress Font Library manager", TEXTDOMAIN),
                    'description'   => __("Hide and block the WordPress Font Library manager in admin, editor UI and REST API. This also disables native Font Library frontend output.", TEXTDOMAIN),
                ),
                array (
                    'type'          => 'checkbox',
                    'name'          => 'disable_wp_image_auto_sizes',
                    'label'         => __("Disable WordPress image auto sizes", TEXTDOMAIN),
                    'description'   => __("Remove WordPress image auto-sizes markup and helper CSS from the frontend.", TEXTDOMAIN),
                ),
                array (
                    'type'          => 'checkbox',
                    'name'          => 'disable_wp_speculation_rules',
                    'label'         => __("Disable WordPress speculation rules", TEXTDOMAIN),
                    'description'   => __("Disable WordPress frontend prefetch/prerender speculation rules.", TEXTDOMAIN),
                ),
                array (
                    'type'          => 'tab_end',
                ),
                array (
                    'type'          => 'tab_start',
                    'name'          => 'tab_editor',
                    'label'         => __("Editor", TEXTDOMAIN),
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
                    'type'          => 'tab_end',
                ),
                array (
                    'type'          => 'tab_start',
                    'name'          => 'tab_manifest',
                    'label'         => __("Manifest", TEXTDOMAIN),
                ),
                array (
                    'type'          => 'color',
                    'name'          => 'theme_color',
                    'label'         => __("Theme color", TEXTDOMAIN),
                    'description'   => __("Set theme color for browsers that support it", TEXTDOMAIN),
                ),
                array (
                    'type'          => 'color',
                    'name'          => 'manifest_background_color',
                    'label'         => __("Web manifest background color", TEXTDOMAIN),
                    'description'   => __("Splash-screen background for the installed PWA", TEXTDOMAIN),
                ),
                array (
                    'type'          => 'text',
                    'name'          => 'manifest_short_name',
                    'label'         => __("Web manifest short name", TEXTDOMAIN),
                    'description'   => __("Defaults to the first 12 characters of the site title", TEXTDOMAIN),
                ),
                array (
                    'type'          => 'text',
                    'name'          => 'manifest_description',
                    'label'         => __("Web manifest description", TEXTDOMAIN),
                    'description'   => __("Defaults to the site tagline", TEXTDOMAIN),
                ),
                array (
                    'type'          => 'tab_end',
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

/**
 * Per-type sanitize callback for a custom option.
 *
 * `code` and `mce` are deliberately left raw — those fields exist to hold
 * custom HTML/JS (header/footer snippets, maintenance and cookie copy) and
 * filtering them would defeat the feature. Everything else is normalised, so a
 * stray tag in an alpha-name or a schema field cannot reach the page unescaped.
 */
function custom_option_sanitize_callback($field){

    switch($field['type']){

        case 'code':
        case 'mce':
            return null; // stored verbatim

        case 'checkbox':
            return function($value){ return empty($value) ? '' : '1'; };

        case 'number':
        case 'range':
            return function($value){
                return is_numeric($value) ? $value + 0 : '';
            };

        case 'select-multiple':
            return function($value){
                return is_array($value) ? array_map('sanitize_text_field', $value) : array();
            };

        case 'link':
            return function($value){
                if(!is_array($value)){
                    return array();
                }

                return array(
                    'url'    => isset($value['url']) ? esc_url_raw($value['url']) : '',
                    'title'  => isset($value['title']) ? sanitize_text_field($value['title']) : '',
                    'target' => (isset($value['target']) && $value['target'] === '_blank') ? '_blank' : '',
                );
            };

        case 'textarea':
            return 'sanitize_textarea_field';

        default:
            return 'sanitize_text_field';

    }

}

// Register settings
add_action('admin_init', function() {
    foreach (get_custom_options() as $key=>$value) {
        foreach ($value['fields'] as $field) {
            // Layout markers and the regeneration button are not settings —
            // registering them would create empty options that nothing reads.
            if(in_array($field['type'], array('tab_start', 'tab_end', 'regenerate_images'), true)){
                continue;
            }

            $args = array();
            $sanitize_callback = custom_option_sanitize_callback($field);

            if($sanitize_callback !== null){
                $args['sanitize_callback'] = $sanitize_callback;
            }

            register_setting($key.'_settings', $field['name'], $args);
        }
    }
});

/**
 * Localized options are handled by WP-LOC.
 *
 * Fields flagged `'localize' => true` are registered as multilingual in
 * system-multilingual.php; WP-LOC then stores and reads them per language
 * through its own `pre_option_*` filters, on the frontend, in frontend AJAX and
 * on custom settings pages alike.
 *
 * The theme used to run its own `pre_option` filter here, querying wp_options
 * directly via $sitepress and $wpdb. That is now dead weight — it duplicated
 * WP-LOC's option layer, ran an uncached query on every option read, and only
 * ever resolved one of the two suffix forms WP-LOC supports (`_uk` vs `_ua`).
 */

/** options assets */
function custom_options_assets(){
    global $pagenow;
    if($pagenow == "options-general.php" && !empty($_GET['page'])){
        $custom_pages = array_keys(get_custom_options());
        if(in_array($_GET['page'], $custom_pages)){
            wp_register_script( 'custom-options', TEMPLATE_DIRECTORY_URL . 'assets/js/custom-options.min.js', '', ASSETS_VERSION, true);
            // Shadow-image regeneration: its own nonce and its own strings —
            // the tool is a plain script, not a template, so nothing else can
            // translate them for it.
            wp_localize_script('custom-options', 'regenerateImages', array(
                'ajaxUrl' => ADMIN_AJAX_URL,
                'nonce'   => wp_create_nonce('regenerate_images'),
                'i18n'    => array(
                    'confirm'   => __('This will overwrite existing files. Continue?', TEXTDOMAIN),
                    'converted' => __('converted', TEXTDOMAIN),
                    'skipped'   => __('skipped', TEXTDOMAIN),
                    'done'      => __('Done', TEXTDOMAIN),
                    'failed'    => __('Regeneration failed.', TEXTDOMAIN),
                ),
            ));
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
