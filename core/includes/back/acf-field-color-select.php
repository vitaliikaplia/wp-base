<?php

if(!defined('ABSPATH')){exit;}

function register_acf_color_select_field(){
    if(!class_exists('acf_field') || class_exists('ACF_Field_Color_Select')){
        return;
    }

    /**
     * ACF field for selecting a color from theme presets.
     *
     * IMPORTANT — why the picker JS lives in a static admin_footer print (not inline in
     * render_field, and not via acf.add_action/ready_field):
     *   In the Gutenberg editor ACF injects a block's inspector fields DYNAMICALLY via
     *   innerHTML. A <script> printed inside render_field's HTML NEVER executes there, so
     *   the old `acf.add_action('append_field/type=color_select')` registration was never
     *   reached — the swatches rendered but were dead (styled-but-unclickable). We instead
     *   print ONE static <script> in the admin footer and bind the click via DOCUMENT-LEVEL
     *   DELEGATION, which catches clicks no matter when/how the field is injected and needs
     *   neither the acf-input bundle nor any acf.* action. See docs/agent/blocks.md.
     */
    class ACF_Field_Color_Select extends acf_field {

        public function __construct(){
            $this->name     = 'color_select';
            $this->label    = __('Color Select', TEXTDOMAIN);
            $this->category = 'choice';
            $this->defaults = array();

            parent::__construct();
        }

        private function get_colors(){
            if(function_exists('get_system_color_presets')){
                return get_system_color_presets();
            }

            return array();
        }

        public function render_field($field){
            $colors = $this->get_colors();
            $current_value = strtolower((string)$field['value']);
            $field_name = esc_attr($field['name']);
            $field_key = esc_attr($field['key']);
            ?>
            <div class="acf-color-select-field" data-key="<?php echo $field_key; ?>">
                <input type="hidden" name="<?php echo $field_name; ?>" value="<?php echo esc_attr($current_value); ?>" class="acf-color-select-value" />
                <div class="acf-color-select-swatches">
                    <?php foreach($colors as $color) :
                        $color = strtolower($color);
                        $is_active = ($current_value === $color) ? ' active' : '';
                        $is_light = $this->is_light_color($color);
                        ?>
                        <button type="button"
                                class="acf-color-swatch<?php echo esc_attr($is_active); ?>"
                                data-color="<?php echo esc_attr($color); ?>"
                                style="background-color: <?php echo esc_attr($color); ?>"
                                title="<?php echo esc_attr($color); ?>">
                            <svg class="acf-color-swatch-check" viewBox="0 0 24 24" fill="none" stroke="<?php echo $is_light ? '#333' : '#fff'; ?>" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="20 6 9 17 4 12"></polyline>
                            </svg>
                        </button>
                    <?php endforeach; ?>
                    <?php if($current_value) : ?>
                        <button type="button" class="acf-color-swatch-clear" title="<?php esc_attr_e('Clear', TEXTDOMAIN); ?>">&times;</button>
                    <?php endif; ?>
                </div>
            </div>
            <?php
        }

        private function is_light_color($hex){
            $hex = ltrim($hex, '#');

            if(strlen($hex) === 3){
                $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
            }

            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 2, 2));
            $b = hexdec(substr($hex, 4, 2));
            $luminance = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;

            return $luminance > 0.6;
        }

        public function format_value($value, $post_id, $field){
            if(empty($value)){
                return false;
            }

            return $value;
        }

        public function update_value($value, $post_id, $field){
            return sanitize_hex_color($value);
        }
    }

    new ACF_Field_Color_Select();
}

add_action('acf/include_field_types', 'register_acf_color_select_field');

/**
 * Print the color_select CSS + a document-delegated click handler ONCE, in the admin
 * footer of any block-editor / post-edit screen. Static footer markup always runs,
 * unlike a <script> injected with the field's HTML in the Gutenberg inspector.
 */
function color_select_admin_assets(){
    if(!function_exists('get_current_screen')){
        return;
    }
    $screen = get_current_screen();
    if(!$screen){
        return;
    }
    $is_editor = (method_exists($screen, 'is_block_editor') && $screen->is_block_editor())
        || in_array($screen->base, array('post', 'widgets', 'site-editor'), true);
    if(!$is_editor){
        return;
    }
    $clear_title = __('Clear', TEXTDOMAIN);
    ?>
    <style id="color-select-css">
        .acf-color-select-field .acf-color-select-swatches {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            align-items: center;
        }
        .acf-color-select-field .acf-color-swatch {
            width: 32px;
            height: 32px;
            border-radius: 6px;
            border: 2px solid rgba(0,0,0,0.1);
            cursor: pointer;
            position: relative;
            padding: 0;
            transition: transform 0.15s ease, border-color 0.15s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .acf-color-select-field .acf-color-swatch:hover {
            transform: scale(1.15);
            border-color: rgba(0,0,0,0.3);
        }
        .acf-color-select-field .acf-color-swatch.active {
            border-color: #0073aa;
            box-shadow: 0 0 0 2px #0073aa;
            transform: scale(1.1);
        }
        .acf-color-select-field .acf-color-swatch-check {
            width: 16px;
            height: 16px;
            display: none;
        }
        .acf-color-select-field .acf-color-swatch.active .acf-color-swatch-check {
            display: block;
        }
        .acf-color-select-field .acf-color-swatch-clear {
            width: 32px;
            height: 32px;
            border-radius: 6px;
            border: 2px dashed rgba(0,0,0,0.2);
            background: transparent;
            cursor: pointer;
            font-size: 18px;
            line-height: 1;
            color: #999;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0;
            transition: all 0.15s ease;
        }
        .acf-color-select-field .acf-color-swatch-clear:hover {
            border-color: #c00;
            color: #c00;
            transform: scale(1.15);
        }
    </style>
    <script id="color-select-js">
        (function(){
            if(window.colorSelectBound){ return; }
            window.colorSelectBound = true;

            var CLEAR_TITLE = <?php echo wp_json_encode($clear_title); ?>;

            function triggerChange(input){
                if(window.jQuery){
                    window.jQuery(input).trigger('change');
                } else {
                    input.dispatchEvent(new Event('change', {bubbles: true}));
                    input.dispatchEvent(new Event('input', {bubbles: true}));
                }
            }

            // document-level delegation: works for dynamically-injected ACF block fields
            document.addEventListener('click', function(e){
                if(!e.target.closest){ return; }

                var swatch = e.target.closest('.acf-color-swatch');
                if(swatch){
                    e.preventDefault();
                    var wrap = swatch.closest('.acf-color-select-field');
                    if(!wrap){ return; }

                    wrap.querySelectorAll('.acf-color-swatch').forEach(function(b){ b.classList.remove('active'); });
                    swatch.classList.add('active');

                    var input = wrap.querySelector('.acf-color-select-value');
                    if(input){
                        input.value = swatch.getAttribute('data-color');
                        triggerChange(input);
                    }

                    var swatches = wrap.querySelector('.acf-color-select-swatches');
                    if(swatches && !swatches.querySelector('.acf-color-swatch-clear')){
                        var clr = document.createElement('button');
                        clr.type = 'button';
                        clr.className = 'acf-color-swatch-clear';
                        clr.title = CLEAR_TITLE;
                        clr.innerHTML = '&times;';
                        swatches.appendChild(clr);
                    }
                    return;
                }

                var clear = e.target.closest('.acf-color-swatch-clear');
                if(clear){
                    e.preventDefault();
                    var wrap2 = clear.closest('.acf-color-select-field');
                    if(!wrap2){ return; }

                    wrap2.querySelectorAll('.acf-color-swatch').forEach(function(b){ b.classList.remove('active'); });
                    var input2 = wrap2.querySelector('.acf-color-select-value');
                    if(input2){
                        input2.value = '';
                        triggerChange(input2);
                    }
                    clear.remove();
                }
            }, false);
        })();
    </script>
    <?php
}
add_action('admin_footer', 'color_select_admin_assets');
