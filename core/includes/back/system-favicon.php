<?php

if(!defined('ABSPATH')){exit;}

/** Generate icons when site icon is updated */
add_action('update_option_site_icon', 'handle_site_icon_update', 10, 2);
add_action('add_option_site_icon', 'handle_site_icon_add', 10, 2);

function handle_site_icon_update($old_value, $new_value) {
    // error_log('Site icon update: old=' . $old_value . ', new=' . $new_value);

    if (empty($new_value)) {
        // Іконка видалена
        delete_pwa_icons();

        // Встановлюємо transient для показу нотифікації про видалення
        set_transient('pwa_icon_deleted_notice', true, 60);
    } else {
        // Іконка оновлена або додана
        generate_pwa_icons($old_value, $new_value);

        // Встановлюємо transient для показу нотифікації
        set_transient('pwa_icon_updated_notice', true, 60);
    }
}

function handle_site_icon_add($old_value, $new_value) {
    // error_log('Site icon added: ' . $new_value);
    if (!empty($new_value)) {
        generate_pwa_icons($old_value, $new_value);

        // Встановлюємо transient для показу нотифікації
        set_transient('pwa_icon_updated_notice', true, 60);
    }
}

/** Show admin notice after icon update */
add_action('admin_notices', function() {
    if (get_transient('pwa_icon_updated_notice')) {
        delete_transient('pwa_icon_updated_notice');

        $svg_path = ABSPATH . 'icon.svg';
        ?>
        <div class="notice notice-info is-dismissible">
            <p>
                <strong><?php echo __('Icons Generated!', TEXTDOMAIN); ?></strong><br>
                <?php echo __("Don't forget to manually update your SVG favicon at:", TEXTDOMAIN); ?><br>
                <code><?php echo esc_html($svg_path); ?></code>
            </p>
        </div>
        <?php
    }

    if (get_transient('pwa_icon_deleted_notice')) {
        delete_transient('pwa_icon_deleted_notice');
        ?>
        <div class="notice notice-warning is-dismissible">
            <p>
                <strong><?php echo __('Icons Deleted!', TEXTDOMAIN); ?></strong><br>
                <?php echo __('All PWA icons and manifest have been removed from the site root.', TEXTDOMAIN); ?>
            </p>
        </div>
        <?php
    }
});

function generate_pwa_icons($old_value, $icon_id) {
    if (!$icon_id || !extension_loaded('imagick')) {
        return;
    }

    $icon_file = get_attached_file($icon_id);
    if (!$icon_file || !file_exists($icon_file)) {
        return;
    }

    $root_dir = ABSPATH;

    $sizes = [
        ['64', '64', 'favicon2x.png'],
        ['48', '48', 'favicon.ico'],
        ['114', '114', 'favicon-114.png'],
        ['144', '144', 'favicon-144.png'],
        ['180', '180', 'apple-touch-icon.png'],
        ['192', '192', 'icon-192.png'],
        ['256', '256', 'icon-256.png'],
        ['384', '384', 'icon-384.png'],
        ['512', '512', 'icon-512.png'],
    ];

    try {
        foreach ($sizes as [$width, $height, $filename]) {
            $image = new Imagick($icon_file);

            // ВИПРАВЛЕННЯ 1: Встановлюємо прозорий фон ПЕРЕД resize
            $image->setImageBackgroundColor(new ImagickPixel('transparent'));
            $image->setBackgroundColor(new ImagickPixel('transparent'));

            $image->resizeImage($width, $height, Imagick::FILTER_LANCZOS, 1);
            $image->setGravity(Imagick::GRAVITY_CENTER);

            // Використовуємо transparentPaintImage замість extentImage для збереження прозорості
            $image->extentImage($width, $height, 0, 0);

            $image->setImageAlphaChannel(Imagick::ALPHACHANNEL_ACTIVATE);
            $image->stripImage();
            $image->setImageInterlaceScheme(Imagick::INTERLACE_PLANE);
            $image->setImageCompressionQuality(85);
            $image->setFormat('png');
            $image->writeImage($root_dir . $filename);
            $image->destroy();
        }

        // ВИПРАВЛЕННЯ 3: Правильне центрування для maskable icon
        $image = new Imagick($icon_file);

        // Встановлюємо прозорий фон
        $image->setImageBackgroundColor(new ImagickPixel('transparent'));
        $image->setBackgroundColor(new ImagickPixel('transparent'));

        // Resize до 384x384 (залишаємо 20% safe zone = 512 - 384 = 128px margin)
        $image->resizeImage(384, 384, Imagick::FILTER_LANCZOS, 1, true);

        // Створюємо canvas 512x512 з прозорим фоном
        $canvas = new Imagick();
        $canvas->newImage(512, 512, new ImagickPixel('transparent'));
        $canvas->setImageFormat('png');

        // Центруємо зображення на canvas
        $canvas->compositeImage($image, Imagick::COMPOSITE_OVER, 64, 64);

        $canvas->setImageAlphaChannel(Imagick::ALPHACHANNEL_ACTIVATE);
        $canvas->stripImage();
        $canvas->setImageInterlaceScheme(Imagick::INTERLACE_PLANE);
        $canvas->setImageCompressionQuality(85);
        $canvas->writeImage($root_dir . 'icon-maskable.png');

        $image->destroy();
        $canvas->destroy();

        generate_manifest_json(true);
    } catch (Exception $e) {
        // error_log('PWA Icons Error: ' . $e->getMessage());
    }
}

function manifest_get_string_option($name, $default = '')
{
    $value = get_option($name);

    if (is_string($value)) {
        $value = trim($value);
    }

    return $value !== false && $value !== '' ? $value : $default;
}

function manifest_short_name($site_name)
{
    if (function_exists('mb_substr')) {
        return mb_substr($site_name, 0, 12);
    }

    return substr($site_name, 0, 12);
}

function update_manifest_color_options($colors, $force = false)
{
    $GLOBALS['manifest_updating_options'] = true;

    if ($force || !get_option('theme_color')) {
        update_option('theme_color', $colors['theme']);
    }

    if ($force || !get_option('manifest_background_color')) {
        update_option('manifest_background_color', $colors['background']);
    }

    if ($force || !get_option('mask_icon_color')) {
        update_option('mask_icon_color', $colors['theme']);
    }

    unset($GLOBALS['manifest_updating_options']);
}

function generate_manifest_json($update_color_options = false)
{
    $colors = analyze_icon_colors();

    if ($update_color_options) {
        update_manifest_color_options($colors, true);
    }

    $site_name = get_bloginfo('name');
    $site_description = get_bloginfo('description');
    $theme_color = manifest_get_string_option('theme_color', $colors['theme']);
    $background_color = manifest_get_string_option('manifest_background_color', $colors['background']);

    $manifest = [
        'id' => home_url('/'),
        'name' => $site_name,
        'short_name' => manifest_get_string_option('manifest_short_name', manifest_short_name($site_name)),
        'description' => manifest_get_string_option('manifest_description', $site_description),
        'lang' => get_bloginfo('language'),
        'start_url' => home_url('/'),
        'scope' => home_url('/'),
        'display' => 'standalone',
        'display_override' => array('standalone', 'minimal-ui'),
        'background_color' => $background_color,
        'theme_color' => $theme_color,
        'orientation' => 'any',
        'prefer_related_applications' => false,
        'icons' => [
            ['src' => home_url('/icon-192.png'), 'sizes' => '192x192', 'type' => 'image/png'],
            ['src' => home_url('/icon-512.png'), 'sizes' => '512x512', 'type' => 'image/png'],
            ['src' => home_url('/icon-maskable.png'), 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable']
        ]
    ];

    file_put_contents(ABSPATH . 'manifest.webmanifest', json_encode($manifest, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
}

function manifest_option_names()
{
    return array(
        'theme_color',
        'mask_icon_color',
        'manifest_background_color',
        'manifest_short_name',
        'manifest_description',
    );
}

function regenerate_manifest_after_option_update($option)
{
    if (!empty($GLOBALS['manifest_updating_options'])) {
        return;
    }

    if (!in_array($option, manifest_option_names(), true)) {
        return;
    }

    if (!get_option('site_icon')) {
        return;
    }

    generate_manifest_json();
}

add_action('updated_option', function($option, $old_value, $value) {
    regenerate_manifest_after_option_update($option);
}, 10, 3);

add_action('added_option', function($option, $value) {
    regenerate_manifest_after_option_update($option);
}, 10, 2);

function analyze_icon_colors() {
    $icon_path = ABSPATH . 'icon-512.png';

    // Якщо файл не існує, повертаємо дефолтні кольори
    if (!file_exists($icon_path)) {
        return [
            'background' => '#1a1d2e',
            'theme' => '#F47622'
        ];
    }

    try {
        $image = new Imagick($icon_path);

        // Зменшуємо зображення для швидшого аналізу
        $image->resizeImage(100, 100, Imagick::FILTER_LANCZOS, 1);

        // Отримуємо палітру кольорів (кількість кольорів обмежуємо для швидкості)
        $image->quantizeImage(10, Imagick::COLORSPACE_RGB, 0, false, false);
        $image->uniqueImageColors();

        $colors = [];
        $pixels = $image->getImageHistogram();

        foreach ($pixels as $pixel) {
            $color = $pixel->getColor();

            // Пропускаємо прозорі та майже прозорі пікселі
            if (isset($color['a']) && $color['a'] < 0.5) {
                continue;
            }

            // Пропускаємо дуже темні (майже чорні) та дуже світлі (майже білі) кольори
            $brightness = ($color['r'] + $color['g'] + $color['b']) / 3;
            if ($brightness < 30 || $brightness > 225) {
                continue;
            }

            // Обчислюємо насиченість кольору
            $max = max($color['r'], $color['g'], $color['b']);
            $min = min($color['r'], $color['g'], $color['b']);
            $saturation = $max > 0 ? ($max - $min) / $max : 0;

            // Зберігаємо тільки насичені кольори
            if ($saturation > 0.3) {
                $hex = sprintf("#%02x%02x%02x", $color['r'], $color['g'], $color['b']);
                $count = $pixel->getColorCount();

                $colors[] = [
                    'hex' => $hex,
                    'count' => $count,
                    'saturation' => $saturation,
                    'brightness' => $brightness
                ];
            }
        }

        $image->destroy();

        // Якщо не знайдено достатньо кольорів, повертаємо дефолтні
        if (empty($colors)) {
            return [
                'background' => '#1a1d2e',
                'theme' => '#F47622'
            ];
        }

        // Сортуємо за кількістю пікселів та насиченістю
        usort($colors, function($a, $b) {
            $score_a = $a['count'] * $a['saturation'];
            $score_b = $b['count'] * $b['saturation'];
            return $score_b <=> $score_a;
        });

        // Беремо найдомінантніший колір для theme_color
        $theme_color = $colors[0]['hex'];

        // Для background_color беремо затемнену версію theme_color або другий домінантний колір
        if (count($colors) > 1 && $colors[1]['brightness'] < $colors[0]['brightness']) {
            $background_color = $colors[1]['hex'];
        } else {
            // Затемнюємо theme_color на 40%
            $background_color = darken_color($theme_color, 0.4);
        }

        // error_log("PWA Colors detected - Theme: $theme_color, Background: $background_color");

        return [
            'background' => $background_color,
            'theme' => $theme_color
        ];

    } catch (Exception $e) {
        // error_log('Color analysis error: ' . $e->getMessage());
        return [
            'background' => '#1a1d2e',
            'theme' => '#F47622'
        ];
    }
}

function darken_color($hex, $percent) {

    // Видаляємо # якщо є
    $hex = ltrim($hex, '#');

    // Конвертуємо hex в RGB
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));

    // Затемнюємо
    $r = max(0, min(255, $r * (1 - $percent)));
    $g = max(0, min(255, $g * (1 - $percent)));
    $b = max(0, min(255, $b * (1 - $percent)));

    // Повертаємо hex
    return sprintf("#%02x%02x%02x", $r, $g, $b);

}

// ВИПРАВЛЕННЯ 2: Правильне видалення файлів
function delete_pwa_icons($old_value = null) {
    $root_dir = ABSPATH;

    $files = [
        'favicon2x.png',
        'favicon.ico',
        'favicon-114.png',
        'favicon-144.png',
        'apple-touch-icon.png',
        'icon-192.png',
        'icon-256.png',
        'icon-384.png',
        'icon-512.png',
        'icon-maskable.png',
        'manifest.webmanifest'
    ];

    $deleted = [];
    $failed = [];

    foreach ($files as $file) {
        $path = $root_dir . $file;
        if (file_exists($path)) {
            if (is_writable($path)) {
                if (unlink($path)) {
                    $deleted[] = $file;
                } else {
                    $failed[] = $file . ' (unlink failed)';
                }
            } else {
                $failed[] = $file . ' (not writable)';
            }
        }
    }

    // Видаляємо автоматично згенеровані кольори при видаленні іконок
    delete_option('theme_color');
    delete_option('manifest_background_color');
    delete_option('mask_icon_color');

    if (!empty($deleted)) {
        // error_log('PWA Icons deleted: ' . implode(', ', $deleted));
    }
    if (!empty($failed)) {
        // error_log('PWA Icons deletion failed: ' . implode(', ', $failed));
    }
    if (empty($deleted) && empty($failed)) {
        // error_log('PWA Icons: no files found to delete');
    }
}

/** favicon for dashboard */
function favicon_for_admin() {
    $site_icon = get_option('site_icon');
    // Показуємо favicon тільки якщо встановлена Site Icon
    if ($site_icon) {
        $favicon_url = home_url('/favicon2x.png');
        echo '<link rel="shortcut icon" href="' . $favicon_url . '?' . ASSETS_VERSION . '" />';
    }
}
add_action('admin_head', 'favicon_for_admin');
add_action('login_head', 'favicon_for_admin');
