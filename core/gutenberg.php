<?php

if(!defined('ABSPATH')){exit;}

if(get_option('disable_gutenberg') !== 'everywhere'){

    /** custom blocks categories */
    function custom_block_categories( $categories, $post ) {
        return array_merge(
            $categories,
            array(
                array(
                    'slug' => 'main',
                    'title' => __( 'Main blocks', TEXTDOMAIN ),
                ),
                array(
                    'slug' => 'logical',
                    'title' => __( 'Logical blocks', TEXTDOMAIN ),
                ),
            )
        );
    }
    add_filter( 'block_categories_all', 'custom_block_categories', 10, 2);

    /** custom gutenberg blocks */
    function get_custom_gutenberg_blocks_array(){
        return array(
            array(
                "name" => "hero",
                "label" => __( "Hero", TEXTDOMAIN ),
                "category" => "main",
                'defaults' => array(
                    'field_5es3eaf348ca151aff27' => array('desktop_tablet','mobile')
                )
            ),
            array(
                "name" => "text",
                "label" => __( "Text", TEXTDOMAIN ),
                "category" => "main",
                'defaults' => array(
                    'field_5es3eaf348ca151aff27' => array('desktop_tablet','mobile')
                )
            ),
            array(
                "name" => "pattern",
                "label" => __( "Pattern", TEXTDOMAIN ),
                "category" => "logical",
                'defaults' => array(
                    'field_5es3eaf348ca151aff27' => array('desktop_tablet','mobile')
                )
            ),
        );
    }

    /** parse arrays of custom blocks */
    function parse_custom_gutenberg_blocks(){

        $to_return = array();

        foreach(get_custom_gutenberg_blocks_array() as $block){

            $to_return['global_allowed_blocks'][] = 'acf/' . $block['category'] . '-' . $block['name'];

            $to_return['custom_gutenberg_blocks'][] = array(
                'name'            => $block['category'] . '-' . $block['name'],
                'title'           => $block['label'],
                'render_callback' => 'block_render_callback',
                'style'           => $block['category'] . '-' . $block['name'],
                'mode' 			  => 'preview',
                'category'        => $block['category'],
                'keywords'        => array( $block['label'] ),
                'data'            => $block['defaults'],
                'supports'        => array(
                    'align' => false,
                    'mode' => false,
                    'customClassName' => false,
                    'jsx' => true
                ),
                'example'  => [
                    'attributes' => [
                        'mode' => 'preview',
                        'data' => array(
                            'is_example'   => true
                        )
                    ]
                ]
            );

        }

        return $to_return;

    }

    /** the callback that renders the blocks */
    function block_render_callback( $block, $content = '', $is_preview = false, $post_id = 0 ) {

        $context = Timber::context();

        // Store block values
        $context['block'] = $block;

        $context['block_name'] = str_replace('acf/','', $block['name']);
        $context['block_class'] = $context['block_name'];
        $no_category_block_name = str_replace('acf/' . $block['category'] . '-','', $block['name']);

        //Store $is_preview value.
        global $is_preview_global;
        if($is_preview_global){
            $context['is_preview'] = true;
        } else {
            $context['is_preview'] = $is_preview;
        }

        // Store field values
        $context['fields'] = get_fields();

        $context['site_theme_uri'] = TEMPLATE_DIRECTORY_URL;
        $context['is_admin'] = is_admin();
        $context['is_example'] = get_field('is_example');
        if($context['is_example']){
            $context['block_example'] = TEMPLATE_DIRECTORY_URL . 'assets/block-preview/' . $block['category'] . '/' . $no_category_block_name . '.png?ver=' . ASSETS_VERSION;
        }

        // Render the block
        Timber::render('blocks' . DS . $context['block']['category'] . DS . $no_category_block_name . '.twig', $context );
    }

    /** init custom blocks */
    function init_custom_gutenberg_blocks() {
        $parsed = parse_custom_gutenberg_blocks();
        foreach ($parsed['custom_gutenberg_blocks'] as $block) {
            acf_register_block_type( $block );
        }
    }
    add_action( 'acf/init', 'init_custom_gutenberg_blocks' );

    /** allow only custom blocks */
    add_filter( 'allowed_block_types_all', 'custom_allowed_block_types' );
    function custom_allowed_block_types() {
        $parsed = parse_custom_gutenberg_blocks();
        return $parsed['global_allowed_blocks'];
    }

    /** remove default block patterns from gutenberg editor */
    remove_theme_support( 'core-block-patterns' );

    /**
     * Enqueue WordPress theme styles within Gutenberg.
     */
    function organic_origin_gutenberg_styles() {
        wp_enqueue_style( 'organic-origin-gutenberg', TEMPLATE_DIRECTORY_URL . 'assets/css/gutenberg.min.css', false, ASSETS_VERSION, 'all' );
    }
    add_action( 'enqueue_block_editor_assets', 'organic_origin_gutenberg_styles' );

    /** adding block styles as css files on front-end */
    function add_blocks_styles_to_frontend_css_action() {
        if(!is_admin() && !is_login()){
            global $post;
            if (!$post) {
                return;
            }
            foreach (get_custom_gutenberg_blocks_array() as $block) {
                $block_name = 'acf/' . $block['category'] . '-' . $block['name'];

                if (has_block($block_name, $post->post_content)) {
                    $style_name = $block['category'] . '-' . $block['name'];
                    $style_url = TEMPLATE_DIRECTORY_URL . 'assets/css/blocks/' . $block['category'] . '/' . $block['name'] . '.min.css';
                    wp_enqueue_style($style_name, $style_url, '', ASSETS_VERSION);
                }
            }
        }
    }
    add_action('wp_enqueue_scripts', 'add_blocks_styles_to_frontend_css_action');

    /** adding block styles as css files in back-end */
    function add_locks_styles_to_editor_css_action() {
        if(is_admin()){
            foreach(get_custom_gutenberg_blocks_array() as $block){
                $style_name = $block['category'] . '-' . $block['name'];
                $style_url = TEMPLATE_DIRECTORY_URL . 'assets/css/blocks/' . $block['category'] . '/' . $block['name'] . '.min.css';
                wp_register_style($style_name, $style_url, '', ASSETS_VERSION);
            }
        }
    }
    add_action('init', 'add_locks_styles_to_editor_css_action');

    /** adding custom block patterns categories */
    function custom_block_pattern_categories_array() {
        $categories = get_terms('pattern_categories', array('hide_empty' => false));
        $pattern_categories = array();
        foreach ($categories as $category) {
            $pattern_categories[] = array(
                $category->slug,
                array('label' => $category->name)
            );
        }
        return $pattern_categories;
    }

    /** adding custom block patterns */
    function custom_block_patterns_array() {
        $args = array(
            'post_type' => 'patterns',
            'posts_per_page' => -1,
        );
        $posts = get_posts($args);
        $custom_block_patterns = array();
        foreach ($posts as $post) {
            $terms = wp_get_post_terms($post->ID, 'pattern_categories');
            $categories_slugs = array_map(function($term) { return $term->slug; }, $terms);
            $custom_block_patterns[] = array(
                'pattern-' . $post->ID,
                array(
                    'title'       => $post->post_title,
                    'description' => $post->post_excerpt,
                    'categories'  => $categories_slugs,
                    'content'     => $post->post_content
                )
            );
        }
        return $custom_block_patterns;
    }

    /** registering custom block patterns categories */
    function custom_block_pattern_category() {
        $pattern_categories = custom_block_pattern_categories_array();
        if(!empty($pattern_categories)){
            foreach ($pattern_categories as $category){
                register_block_pattern_category(
                    $category[0],
                    $category[1]
                );
            }
        }
    }
    add_action( 'init', 'custom_block_pattern_category' );

    /** registering custom block patterns */
    function register_custom_patterns() {
        $custom_block_patterns_array = custom_block_patterns_array();
        if(!empty($custom_block_patterns_array)){
            foreach ($custom_block_patterns_array as $pattern){
                register_block_pattern(
                    $pattern[0],
                    $pattern[1]
                );
            }
        }
    }
    add_action( 'init', 'register_custom_patterns' );

    // This code registers custom block patterns and categories for pages in WordPress.
    // It retrieves all published pages, extracts their titles and content,
    // and creates block patterns based on the blocks found within each page's content.
    // The patterns are categorized by the page ID, allowing for easy organization and retrieval in the Gutenberg editor.
    if(!empty(get_option('parse_all_pages_blocks_as_gutenberg_patterns'))){
        function page_block_pattern_categories_array() {
            $pages = get_posts(array(
                'post_type' => 'page',
                'posts_per_page' => -1,
                'post_status' => 'publish',
                'suppress_filters' => false
            ));

            $pattern_categories = array();
            foreach ($pages as $page) {
                $pattern_categories[] = array(
                    'page-' . $page->ID,
                    array('label' => $page->post_title)
                );
            }

            return $pattern_categories;
        }

        function page_block_patterns_array() {
            $pages = get_posts(array(
                'post_type' => 'page',
                'posts_per_page' => -1,
                'post_status' => 'publish',
                'suppress_filters' => false
            ));

            $custom_block_patterns = array();

            foreach ($pages as $page) {
                $blocks = parse_blocks($page->post_content);
                $block_counter = 1;

                foreach ($blocks as $block) {
                    if (!empty($block['blockName'])) {
                        $custom_block_patterns[] = array(
                            'page-' . $page->ID . '-block-' . $block_counter,
                            array(
                                'title' => $page->post_title . ' - '.__('Block', TEXTDOMAIN).' ' . $block_counter,
                                'description' => __('Block from', TEXTDOMAIN).' ' . $page->post_title,
                                'categories' => array('page-' . $page->ID),
                                'content' => serialize_block($block)
                            )
                        );
                        $block_counter++;
                    }
                }
            }

            return $custom_block_patterns;
        }

        function register_page_block_pattern_categories() {
            $categories = get_transient('page_block_pattern_categories_array' . LANG_SUFFIX);
            if (!$categories) {
                $categories = page_block_pattern_categories_array();
                set_transient('page_block_pattern_categories_array' . LANG_SUFFIX, $categories, TRANSIENTS_TIME);
            }
            foreach ($categories as $category) {
                register_block_pattern_category($category[0], $category[1]);
            }
        }
        add_action('init', 'register_page_block_pattern_categories');

        function register_page_block_patterns() {
            $patterns = get_transient('page_block_patterns_array' . LANG_SUFFIX);
            if (!$patterns) {
                $patterns = page_block_patterns_array();
                set_transient('page_block_patterns_array' . LANG_SUFFIX, $patterns, TRANSIENTS_TIME);
            }
            foreach ($patterns as $pattern) {
                register_block_pattern($pattern[0], $pattern[1]);
            }
        }
        add_action('init', 'register_page_block_patterns');
    }

}
