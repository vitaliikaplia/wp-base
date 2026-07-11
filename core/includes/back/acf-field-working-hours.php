<?php

if(!defined('ABSPATH')){exit;}

function register_acf_working_hours_field(){
    if(!class_exists('acf_field') || class_exists('ACF_Field_Working_Hours')){
        return;
    }

    /**
     * ACF field for selecting weekly working hours.
     */
    class ACF_Field_Working_Hours extends acf_field {

        private $days = array(
            'Mo' => 'Mon',
            'Tu' => 'Tue',
            'We' => 'Wed',
            'Th' => 'Thu',
            'Fr' => 'Fri',
            'Sa' => 'Sat',
            'Su' => 'Sun',
        );

        public function __construct(){
            $this->name     = 'working_hours';
            $this->label    = __('Working Hours', TEXTDOMAIN);
            $this->category = 'choice';
            $this->defaults = array();

            parent::__construct();
        }

        private function get_day_labels(){
            return array(
                'Mo' => __('Mon', TEXTDOMAIN),
                'Tu' => __('Tue', TEXTDOMAIN),
                'We' => __('Wed', TEXTDOMAIN),
                'Th' => __('Thu', TEXTDOMAIN),
                'Fr' => __('Fri', TEXTDOMAIN),
                'Sa' => __('Sat', TEXTDOMAIN),
                'Su' => __('Sun', TEXTDOMAIN),
            );
        }

        private function get_time_options(){
            $times = array();

            for($hour = 0; $hour < 24; $hour++){
                for($minute = 0; $minute < 60; $minute += 30){
                    $times[] = sprintf('%02d:%02d', $hour, $minute);
                }
            }

            return $times;
        }

        public function render_field($field){
            $labels = $this->get_day_labels();
            $times = $this->get_time_options();
            $value = isset($field['value']) && is_array($field['value']) ? $field['value'] : array();
            $name = esc_attr($field['name']);
            ?>
            <div class="acf-working-hours-field">
                <?php foreach($this->days as $key => $default_label) :
                    $day = isset($value[$key]) && is_array($value[$key]) ? $value[$key] : array();
                    $enabled = !empty($day['enabled']);
                    $opens = isset($day['opens']) ? $day['opens'] : '09:00';
                    $closes = isset($day['closes']) ? $day['closes'] : '18:00';
                    $disabled_attr = $enabled ? '' : ' disabled="disabled"';
                    $row_class = $enabled ? '' : ' disabled';
                    ?>
                    <div class="acf-wh-row<?php echo esc_attr($row_class); ?>">
                        <input type="hidden" name="<?php echo $name; ?>[<?php echo esc_attr($key); ?>][enabled]" value="0" />
                        <input type="checkbox" name="<?php echo $name; ?>[<?php echo esc_attr($key); ?>][enabled]" value="1" class="acf-wh-checkbox" <?php checked($enabled); ?> />
                        <button type="button" class="acf-wh-badge<?php echo $enabled ? ' active' : ''; ?>">
                            <?php echo esc_html($labels[$key] ?? $default_label); ?>
                        </button>
                        <select name="<?php echo $name; ?>[<?php echo esc_attr($key); ?>][opens]" class="acf-wh-select"<?php echo $disabled_attr; ?>>
                            <?php foreach($times as $time) : ?>
                                <option value="<?php echo esc_attr($time); ?>" <?php selected($opens, $time); ?>><?php echo esc_html($time); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <span class="acf-wh-separator">&ndash;</span>
                        <select name="<?php echo $name; ?>[<?php echo esc_attr($key); ?>][closes]" class="acf-wh-select"<?php echo $disabled_attr; ?>>
                            <?php foreach($times as $time) : ?>
                                <option value="<?php echo esc_attr($time); ?>" <?php selected($closes, $time); ?>><?php echo esc_html($time); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endforeach; ?>
            </div>
            <style>
                .acf-working-hours-field {
                    display: flex;
                    flex-direction: column;
                    gap: 6px;
                    max-width: 460px;
                }
                .acf-wh-row {
                    display: flex;
                    align-items: center;
                    gap: 10px;
                    padding: 6px 0;
                }
                .acf-wh-row .acf-wh-checkbox {
                    position: absolute;
                    opacity: 0;
                    width: 0;
                    height: 0;
                    pointer-events: none;
                }
                .acf-wh-badge {
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    width: 44px;
                    height: 34px;
                    border-radius: 8px;
                    border: none;
                    font-size: 13px;
                    font-weight: 600;
                    cursor: pointer;
                    transition: background-color 0.2s, color 0.2s;
                    background-color: var(--wp-admin-theme-color, #2271b1);
                    color: #fff;
                    flex-shrink: 0;
                    line-height: 1;
                    padding: 0;
                }
                .acf-wh-badge:hover {
                    background-color: var(--wp-admin-theme-color-darker-10, #135e96);
                }
                .acf-wh-row.disabled .acf-wh-badge,
                .acf-wh-badge:not(.active) {
                    background-color: #dcdcde;
                    color: #787c82;
                }
                .acf-wh-select {
                    flex: 1;
                    min-width: 0;
                    height: 34px;
                    border: 1px solid #d0d5dd;
                    border-radius: 8px;
                    padding: 0 8px;
                    font-size: 14px;
                    background: #fff;
                    cursor: pointer;
                    transition: opacity 0.2s;
                }
                .acf-wh-row.disabled .acf-wh-select {
                    opacity: 0.4;
                    pointer-events: none;
                }
                .acf-wh-separator {
                    color: #98a2b3;
                    font-size: 16px;
                    flex-shrink: 0;
                    transition: opacity 0.2s;
                }
                .acf-wh-row.disabled .acf-wh-separator {
                    opacity: 0.4;
                }
            </style>
            <script type="text/javascript">
                (function($) {
                    function initWorkingHours($field) {
                        var $wrap = $field.find('.acf-working-hours-field');
                        if($wrap.data('initialized')) return;
                        $wrap.data('initialized', true);

                        $wrap.on('click', '.acf-wh-badge', function(e) {
                            e.preventDefault();

                            var $row = $(this).closest('.acf-wh-row');
                            var $checkbox = $row.find('.acf-wh-checkbox');
                            var isChecked = $checkbox.is(':checked');

                            $checkbox.prop('checked', !isChecked);
                            $(this).toggleClass('active', !isChecked);
                            $row.toggleClass('disabled', isChecked);
                            $row.find('.acf-wh-select').prop('disabled', isChecked);
                        });
                    }

                    if(window.acf){
                        acf.add_action('ready_field/type=working_hours', initWorkingHours);
                        acf.add_action('append_field/type=working_hours', initWorkingHours);
                    }
                })(jQuery);
            </script>
            <?php
        }

        public function format_value($value, $post_id, $field){
            if(empty($value) || !is_array($value)){
                return false;
            }

            return $value;
        }

        public function update_value($value, $post_id, $field){
            if(!is_array($value)){
                return array();
            }

            $sanitized = array();

            foreach(array_keys($this->days) as $day){
                if(!isset($value[$day]) || !is_array($value[$day])){
                    $sanitized[$day] = array(
                        'enabled' => false,
                        'opens'   => '09:00',
                        'closes'  => '18:00',
                    );
                    continue;
                }

                $sanitized[$day] = array(
                    'enabled' => !empty($value[$day]['enabled']),
                    'opens'   => $this->sanitize_time($value[$day]['opens'] ?? '09:00'),
                    'closes'  => $this->sanitize_time($value[$day]['closes'] ?? '18:00'),
                );
            }

            return $sanitized;
        }

        private function sanitize_time($time){
            if(preg_match('/^\d{1,2}:\d{2}$/', $time)){
                $parts = explode(':', $time);
                $hour = min(23, max(0, (int)$parts[0]));
                $minute = min(59, max(0, (int)$parts[1]));

                return sprintf('%02d:%02d', $hour, $minute);
            }

            return '09:00';
        }
    }

    new ACF_Field_Working_Hours();
}

add_action('acf/include_field_types', 'register_acf_working_hours_field');
register_acf_working_hours_field();
