<?php

if(!defined('ABSPATH')){exit;}

if (class_exists('ACF')) {

    function allow_svg_in_nav($tags, $context) {
        $tags['figure'] = array('class' => true);
        $tags['svg'] = array();
        $tags['use'] = array('href' => true, 'xlink:href' => true);
        $tags['span'] = array('class' => true);
        return $tags;
    }

    /**
     * Custom Walker that doesn't escape HTML in menu item titles
     */
    class Walker_Nav_Menu_No_Escape extends Walker_Nav_Menu {
        public function start_el( &$output, $data_object, $depth = 0, $args = null, $current_object_id = 0 ) {
            // Get the original title before WordPress escapes it
            $original_title = $data_object->title;

            // Temporarily replace title with a placeholder
            $placeholder = '___TITLE_PLACEHOLDER_' . md5($original_title) . '___';
            $data_object->title = $placeholder;

            // Call parent to build the menu item HTML
            parent::start_el( $output, $data_object, $depth, $args, $current_object_id );

            // Restore original title
            $data_object->title = $original_title;

            // Replace the escaped placeholder with the original HTML title
            $output = str_replace( esc_html($placeholder), $original_title, $output );
        }
    }

    /**
     * ACF_Field_Nav_Menu_V5 Class
     *
     * This class contains all the custom workings for the Nav Menu Field for ACF v5
     */
    class ACF_Field_Nav_Menu_V5 extends acf_field {

        /**
         * Sets up some default values and delegats work to the parent constructor.
         */
        public function __construct() {
            $this->name     = 'nav_menu';
            $this->label    = __( 'Nav Menu', TEXTDOMAIN );
            $this->category = 'relational';
            $this->defaults = array(
                'save_format' => 'id',
                'allow_null'  => 0,
                'container'   => 'div',
            );

            parent::__construct();
        }

        /**
         * Renders the Nav Menu Field options seen when editing a Nav Menu Field.
         *
         * @param array $field The array representation of the current Nav Menu Field.
         */
        public function render_field_settings( $field ) {
            // Register the Return Value format setting
            acf_render_field_setting( $field, array(
                'label'        => __( 'Return Value', TEXTDOMAIN ),
                'instructions' => __( 'Specify the returned value on front end', TEXTDOMAIN ),
                'type'         => 'radio',
                'name'         => 'save_format',
                'layout'       => 'horizontal',
                'choices'      => array(
                    'object' => __( 'Nav Menu Object', TEXTDOMAIN ),
                    'menu'   => __( 'Nav Menu HTML', TEXTDOMAIN ),
                    'id'     => __( 'Nav Menu ID', TEXTDOMAIN ),
                    'all'     => __( 'All', TEXTDOMAIN ),
                ),
            ) );

            // Register the Menu Container setting
            acf_render_field_setting( $field, array(
                'label'        => __( 'Menu Container', TEXTDOMAIN ),
                'instructions' => __( "What to wrap the Menu's ul with (when returning HTML only)", TEXTDOMAIN ),
                'type'         => 'select',
                'name'         => 'container',
                'choices'      => $this->get_allowed_nav_container_tags(),
            ) );

            // Register the Allow Null setting
            acf_render_field_setting( $field, array(
                'label'        => __( 'Allow Null?', TEXTDOMAIN ),
                'type'         => 'radio',
                'name'         => 'allow_null',
                'layout'       => 'horizontal',
                'choices'      => array(
                    1 => __( 'Yes', TEXTDOMAIN ),
                    0 => __( 'No', TEXTDOMAIN ),
                ),
            ) );
        }

        /**
         * Get the allowed wrapper tags for use with wp_nav_menu().
         *
         * @return array An array of allowed wrapper tags.
         */
        private function get_allowed_nav_container_tags() {
            $tags           = apply_filters( 'wp_nav_menu_container_allowedtags', array( 'div', 'nav' ) );
            $formatted_tags = array(
                '0' => __( 'None', TEXTDOMAIN ),
            );

            foreach ( $tags as $tag ) {
                $formatted_tags[$tag] = ucfirst( $tag );
            }

            return $formatted_tags;
        }

        /**
         * Renders the Nav Menu Field.
         *
         * @param array $field The array representation of the current Nav Menu Field.
         */
        public function render_field( $field ) {
            $allow_null = $field['allow_null'];
            $nav_menus  = $this->get_nav_menus( $allow_null );

            if ( empty( $nav_menus ) ) {
                return;
            }
            ?>
            <select id="<?php esc_attr( $field['id'] ); ?>" class="<?php echo esc_attr( $field['class'] ); ?>" name="<?php echo esc_attr( $field['name'] ); ?>">
                <?php foreach( $nav_menus as $nav_menu_id => $nav_menu_name ) : ?>
                    <option value="<?php echo esc_attr( $nav_menu_id ); ?>" <?php selected( $field['value'], $nav_menu_id ); ?>>
                        <?php echo esc_html( $nav_menu_name ); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php
        }

        /**
         * Gets a list of Nav Menus indexed by their Nav Menu IDs.
         *
         * @param bool $allow_null If true, prepends the null option.
         *
         * @return array An array of Nav Menus indexed by their Nav Menu IDs.
         */
        private function get_nav_menus( $allow_null = false ) {

            $navs = get_terms( 'nav_menu', array( 'hide_empty' => false ) );

            $nav_menus = array();

            if ( $allow_null ) {
                $nav_menus[''] = ' - ' . __("Choose one", TEXTDOMAIN) . ' - ';
            }

            // On a multilingual site show only the menus belonging to the language
            // being edited. WP-LOC's own acf/load_field/type=nav_menu filter works on
            // $field['choices'], which this field does not use, so the list is
            // filtered here. Menus with no registered language (created before the
            // plugin, or on a single-language site) always stay visible.
            $context_language = theme_is_multilingual() ? theme_context_language() : '';

            foreach ( $navs as $nav ) {

                if ( $context_language ) {
                    $menu_language = theme_term_language( (int) $nav->term_id, 'nav_menu' );

                    if ( $menu_language && $menu_language !== $context_language ) {
                        continue;
                    }
                }

                $nav_menus[ $nav->term_id ] = $nav->name;

            }

            return $nav_menus;
        }

        /**
         * Renders the Nav Menu Field.
         *
         * @param int   $value   The Nav Menu ID selected for this Nav Menu Field.
         * @param int   $post_id The Post ID this $value is associated with.
         * @param array $field   The array representation of the current Nav Menu Field.
         *
         * @return mixed The Nav Menu ID, or the Nav Menu HTML, or the Nav Menu Object, or false.
         */
        public function format_value( $value, $post_id, $field ) {
            // bail early if no value
            if ( empty( $value ) ) {
                return false;
            }

            // check format
            if ( 'object' == $field['save_format'] ) {
                $wp_menu_object = wp_get_nav_menu_object( $value );

                if( empty( $wp_menu_object ) ) {
                    return false;
                }

                $menu_object = new stdClass;

                $menu_object->ID    = $wp_menu_object->term_id;
                $menu_object->name  = $wp_menu_object->name;
                $menu_object->slug  = $wp_menu_object->slug;
                $menu_object->count = $wp_menu_object->count;

                return $menu_object;
            } elseif ( 'menu' == $field['save_format'] ) {
                $menu_args = array(
                        'menu' => $value,
                        'container' => $field['container'],
                        'menu_class'=> false,
                        'menu_id'=> false,
                        'echo' => false,
                        'link_before' => '',
                        'link_after' => '',
                        'items_wrap' => '%3$s', // видаляємо обгортку ul
                        'walker' => new Walker_Nav_Menu_No_Escape() // використовуємо кастомний walker
                );

                $menu_html = wp_nav_menu($menu_args);

                // Видаляємо класи current
                $menu_html = str_replace(array('current-menu-item','current_page_item','current-menu-ancestor','current_page_parent'), '', $menu_html);

                return $menu_html;
            } elseif ( 'all' == $field['save_format'] ) {
                $menu_html = wp_nav_menu( array(
                        'menu' => $value,
                        'container' => $field['container'],
                        'menu_class'=> false,
                        'menu_id'=> false,
                        'echo' => false,
                        'link_before' => '',
                        'link_after' => '',
                        'walker' => new Walker_Nav_Menu_No_Escape() // використовуємо кастомний walker
                ) );

                // Видаляємо зайві класи та обгортки
                $menu_html = preg_replace( array( '#^<ul[^>]*>#', '#</ul>$#' ), '', $menu_html);
                $menu_html = str_replace(array('current-menu-item','current_page_item','current-menu-ancestor','current_page_parent'), '', $menu_html);
                $menu_html = preg_replace( array( '#^<div[^>]*>#', '#</div>$#' ), '', $menu_html);

                $letsSaveArr['html'] = $menu_html;
                $letsSaveArr['id'] = $value;
                $letsSaveArr['object'] = wp_get_nav_menu_object( $value );
                return $letsSaveArr;
            }

            // Just return the Nav Menu ID
            return $value;
        }
    }

    new ACF_Field_Nav_Menu_V5();

}
