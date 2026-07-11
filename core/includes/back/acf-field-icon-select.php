<?php

if(!defined('ABSPATH')){exit;}

function register_acf_icon_select_field(){
    if(!class_exists('acf_field') || class_exists('ACF_Field_Icon_Select')){
        return;
    }

    /**
     * ACF field for selecting an icon from the managed theme sprite.
     */
    class ACF_Field_Icon_Select extends acf_field {

        public function __construct() {
            $this->name = 'icon_select';
            $this->label = __('Icon Select', TEXTDOMAIN);
            $this->category = 'choice';
            $this->defaults = array();

            parent::__construct();
        }

        private function get_icon($icon_id) {

            // a stored value may be a theme default (clean id) or a managed icon (prefixed),
            // so normalize without forcing the managed prefix
            $icon_id = normalize_icon_id($icon_id);

            if(!$icon_id){
                return false;
            }

            foreach(get_managed_icons() as $icon){
                if($icon['id'] === $icon_id){
                    $icon['display_id'] = get_managed_icon_display_id($icon['id']);
                    return $icon;
                }
            }

            return false;

        }

        public function render_field($field) {

            $current_value = is_scalar($field['value']) ? normalize_icon_id((string) $field['value']) : '';
            $current_icon = $this->get_icon($current_value);
            $has_missing_icon = $current_value && !$current_icon;
            $context = get_managed_icons_view_context();
            $sprite_url = $context['sprite_url'];
            $labels = $context['labels'];
            ?>
            <div
                class="acf-icon-select-field"
                data-selected-icon-id="<?php echo esc_attr($current_icon ? $current_icon['id'] : ''); ?>"
                data-pick-label="<?php echo esc_attr__('Pick an icon', TEXTDOMAIN); ?>"
                data-pick-another-label="<?php echo esc_attr__('Pick another icon', TEXTDOMAIN); ?>"
            >
                <input type="hidden" name="<?php echo esc_attr($field['name']); ?>" value="<?php echo esc_attr($current_value); ?>" class="acf-icon-select-value">

                <div class="acf-icon-select-control<?php echo $current_icon ? ' has-icon' : ''; ?><?php echo $has_missing_icon ? ' has-missing-icon' : ''; ?>">
                    <button type="button" class="button acf-icon-select-open">
                        <span class="acf-icon-select-preview" aria-hidden="true">
                            <?php if($current_icon) : ?>
                                <svg focusable="false">
                                    <use href="<?php echo esc_url($current_icon['sprite_url'] . '#' . $current_icon['id']); ?>"></use>
                                </svg>
                            <?php elseif($has_missing_icon) : ?>
                                <span class="dashicons dashicons-warning" aria-hidden="true"></span>
                            <?php endif; ?>
                        </span>
                        <span class="acf-icon-select-divider" aria-hidden="true"></span>
                        <span class="acf-icon-select-label">
                            <?php
                            echo esc_html(
                                $has_missing_icon
                                    ? sprintf(__('Missing icon: %s', TEXTDOMAIN), get_managed_icon_display_id($current_value))
                                    : ($current_icon ? __('Pick another icon', TEXTDOMAIN) : __('Pick an icon', TEXTDOMAIN))
                            );
                            ?>
                        </span>
                    </button>
                    <button type="button" class="button acf-icon-select-clear" aria-label="<?php echo esc_attr__('Remove selected icon', TEXTDOMAIN); ?>" title="<?php echo esc_attr__('Remove selected icon', TEXTDOMAIN); ?>">
                        <span class="dashicons dashicons-trash" aria-hidden="true"></span>
                    </button>
                </div>

                <div class="acf-icon-select-overlay" aria-hidden="true">
                    <div class="acf-icon-select-overlay__backdrop" data-acf-icon-select-close></div>
                    <div class="acf-icon-select-overlay__frame" role="dialog" aria-modal="true" aria-label="<?php echo esc_attr__('Select icon', TEXTDOMAIN); ?>">
                        <button type="button" class="acf-icon-select-overlay__close" data-acf-icon-select-close aria-label="<?php echo esc_attr($labels['close']); ?>">&times;</button>

                        <div class="icons-page icons-manager icons-picker" data-icons-picker="1" data-selected-icon-id="<?php echo esc_attr($current_icon ? $current_icon['id'] : ''); ?>">
                            <div class="icons-header">
                                <h1><?php echo esc_html($labels['title']); ?></h1>
                                <button type="button" class="page-title-action upload-icons"><?php echo esc_html($labels['upload']); ?></button>
                                <button type="button" class="page-title-action bulk-delete-icons"<?php echo empty($context['icons']) ? ' hidden' : ''; ?>><?php echo esc_html($labels['bulk_delete']); ?></button>
                                <input type="file" class="icons-file-input" accept=".svg,image/svg+xml" hidden>
                            </div>
                            <div class="icons-notices"></div>

                            <div class="icons-bulk-actions" hidden>
                                <button type="button" class="button button-link-delete delete-selected-icons" disabled><?php echo esc_html($labels['delete_selected']); ?></button>
                                <button type="button" class="button cancel-bulk-selection"><?php echo esc_html($labels['cancel_selection']); ?></button>
                            </div>

                            <?php if(empty($context['icons'])) : ?>
                                <div class="notice notice-warning inline icons-empty">
                                    <p><?php echo esc_html($labels['empty']); ?></p>
                                </div>
                            <?php endif; ?>

                            <div class="icons-grid"<?php echo empty($context['icons']) ? ' hidden' : ''; ?> data-sprite-url="<?php echo esc_attr($sprite_url); ?>">
                                <?php foreach($context['icons'] as $icon) : ?>
                                    <button
                                        type="button"
                                        class="icon-card<?php echo $current_icon && $current_icon['id'] === $icon['id'] ? ' is-selected' : ''; ?><?php echo !empty($icon['locked']) ? ' is-locked' : ''; ?>"
                                        data-icon-id="<?php echo esc_attr($icon['id']); ?>"
                                        data-icon-display-id="<?php echo esc_attr($icon['display_id']); ?>"
                                        data-icon-viewbox="<?php echo esc_attr($icon['viewBox']); ?>"
                                        <?php echo !empty($icon['locked']) ? 'data-icon-locked="1"' : ''; ?>
                                        aria-pressed="<?php echo $current_icon && $current_icon['id'] === $icon['id'] ? 'true' : 'false'; ?>"
                                    >
                                        <span class="icon-card__preview">
                                            <?php $card_view_box = icon_size_only_viewbox($icon['viewBox']); ?>
                                            <svg aria-hidden="true" focusable="false" preserveAspectRatio="xMidYMid meet"<?php echo $card_view_box ? ' viewBox="' . esc_attr($card_view_box) . '"' : ''; ?>>
                                                <use href="<?php echo esc_url($icon['sprite_url'] . '#' . $icon['id']); ?>"></use>
                                            </svg>
                                        </span>
                                        <span class="icon-card__title"><?php echo esc_html($icon['display_id']); ?></span>
                                        <?php if(!empty($icon['locked'])) : ?>
                                            <span class="icon-card__lock" title="<?php echo esc_attr($labels['read_only_hint']); ?>">
                                                <span class="dashicons dashicons-lock" aria-hidden="true"></span>
                                                <span class="screen-reader-text"><?php echo esc_html($labels['locked_badge']); ?></span>
                                            </span>
                                        <?php endif; ?>
                                        <span class="icon-card__check" aria-hidden="true">
                                            <span class="dashicons dashicons-yes"></span>
                                        </span>
                                    </button>
                                <?php endforeach; ?>
                            </div>

                            <div class="icons-picker-actions">
                                <button type="button" class="button edit-selected-icon" disabled><?php echo esc_html($labels['edit_selected']); ?></button>
                                <button type="button" class="button button-primary choose-selected-icon" disabled><?php echo esc_html($labels['choose_selected']); ?></button>
                            </div>

                            <div class="icons-drop-overlay">
                                <div>
                                    <span class="dashicons dashicons-upload" aria-hidden="true"></span>
                                    <strong><?php echo esc_html($labels['upload']); ?></strong>
                                </div>
                            </div>

                            <?php $this->render_icon_modals($labels); ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php

        }

        private function render_icon_modals($labels) {
            ?>
            <div class="icon-modal" aria-hidden="true">
                <div class="icon-modal__backdrop" data-icons-modal-close></div>
                <div class="icon-modal__panel" role="dialog" aria-modal="true">
                    <button type="button" class="icon-modal__close" data-icons-modal-close aria-label="<?php echo esc_attr($labels['close']); ?>">&times;</button>
                    <div class="icons-import-preview icon-modal__preview">
                        <span class="icons-import-preview__grid" aria-hidden="true"></span>
                        <span class="icons-import-preview__icon">
                            <svg aria-hidden="true" focusable="false"></svg>
                        </span>
                    </div>
                    <h2><?php echo esc_html($labels['edit']); ?></h2>
                    <label><?php echo esc_html($labels['icon_id']); ?></label>
                    <input type="text" class="regular-text icon-id" autocomplete="off">
                    <p class="description"><?php echo esc_html($labels['description']); ?></p>
                    <p class="icon-modal__readonly notice notice-info inline"><?php echo esc_html($labels['read_only_hint']); ?></p>
                    <div class="icons-import-options icon-modal__options">
                        <label>
                            <input type="checkbox" class="icon-edit-fit-viewbox">
                            <?php echo esc_html($labels['fit_viewbox']); ?>
                        </label>
                        <label>
                            <input type="checkbox" class="icon-edit-current-color">
                            <?php echo esc_html($labels['current_color']); ?>
                        </label>
                    </div>
                    <div class="icon-modal__actions">
                        <button type="button" class="button button-primary save-icon-id"><?php echo esc_html($labels['save']); ?></button>
                        <button type="button" class="button download-icon"><?php echo esc_html($labels['download']); ?></button>
                        <button type="button" class="button button-link-delete delete-icon" aria-label="<?php echo esc_attr($labels['delete']); ?>" title="<?php echo esc_attr($labels['delete']); ?>">
                            <span class="dashicons dashicons-trash" aria-hidden="true"></span>
                            <span class="screen-reader-text"><?php echo esc_html($labels['delete']); ?></span>
                        </button>
                    </div>
                    <p class="icon-modal__message" aria-live="polite"></p>
                </div>
            </div>

            <div class="icons-import-modal" aria-hidden="true">
                <div class="icon-modal__backdrop" data-icons-import-modal-close></div>
                <div class="icon-modal__panel" role="dialog" aria-modal="true">
                    <button type="button" class="icon-modal__close" data-icons-import-modal-close aria-label="<?php echo esc_attr($labels['close']); ?>">&times;</button>
                    <h2><?php echo esc_html($labels['import_title']); ?></h2>
                    <div class="icons-import-preview" hidden>
                        <span class="icons-import-preview__grid" aria-hidden="true"></span>
                        <span class="icons-import-preview__icon"></span>
                    </div>
                    <div class="icons-import-options" hidden>
                        <label>
                            <input type="checkbox" class="icon-fit-viewbox" checked>
                            <?php echo esc_html($labels['fit_viewbox']); ?>
                        </label>
                        <label>
                            <input type="checkbox" class="icon-current-color">
                            <?php echo esc_html($labels['current_color']); ?>
                        </label>
                    </div>
                    <p class="icons-import-summary"></p>
                    <p class="icons-import-invalid"></p>
                    <div class="icon-modal__actions">
                        <button type="button" class="button button-primary confirm-icons-import"><?php echo esc_html($labels['continue_import']); ?></button>
                        <button type="button" class="button" data-icons-import-modal-close><?php echo esc_html($labels['cancel']); ?></button>
                    </div>
                    <p class="icons-import-message icon-modal__message" aria-live="polite"></p>
                </div>
            </div>
            <?php
        }

        public function format_value($value, $post_id, $field) {
            $value = is_scalar($value) ? normalize_icon_id((string) $value) : '';

            return $value ? $value : false;
        }

        public function update_value($value, $post_id, $field) {
            return is_scalar($value) ? normalize_icon_id((string) $value) : '';
        }
    }

    new ACF_Field_Icon_Select();
}

add_action('acf/include_field_types', 'register_acf_icon_select_field');
register_acf_icon_select_field();
