<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Renders an SVG file inline with sanitization, accessibility, and styling enhancements.
 *
 * @param array $svg The SVG data array from ACF/WordPress.
 * @param array $attributes Optional. An associative array of attributes to add to the <svg> tag (e.g., ['class' => 'my-icon']).
 *
 * @return string The processed SVG content or an empty string on failure.
 */
function render_svg_tag( $svg, $attributes = [] ) {

    // 1. Покращена валідація вхідних даних
    if ( empty( $svg['id'] ) || $svg['type'] !== 'image' || $svg['subtype'] !== 'svg+xml' ) {
        return '';
    }

    // 2. Створюємо унікальний ключ кешу, що враховує додаткові атрибути.
    //    Суфікс версії інвалідовує кеш, зібраний до появи санітайзера.
    $attributes_key = ! empty( $attributes ) ? '_' . md5( json_encode( $attributes ) ) : '';
    $cache_key      = 'svg2_' . $svg['id'] . $attributes_key;

    // 3. Перевіряємо кеш
    $cached_svg = get_transient( $cache_key );
    if ( $cached_svg !== false ) {
        return $cached_svg;
    }

    // 4. Отримуємо шлях до файлу
    $path = get_attached_file( $svg['id'] );
    if ( ! $path || ! file_exists( $path ) ) {
        return '';
    }

    // 5. Читаємо вміст файлу та повністю санітизуємо його.
    //    Файли, завантажені до появи санітайзера, теж проходять через цю перевірку,
    //    тому невідчищений SVG ніколи не потрапляє inline у сторінку.
    $svg_content = file_get_contents( $path );
    $svg_content = sanitize_svg_markup( $svg_content );

    if ( $svg_content === false ) {
        return '';
    }

    // 6. Додаємо атрибути до тегу <svg>
    if ( ! empty( $attributes ) ) {
        $attrs_string = '';
        foreach ( $attributes as $name => $value ) {
            $attrs_string .= ' ' . esc_attr( $name ) . '="' . esc_attr( $value ) . '"';
        }
        $svg_content = preg_replace( '/<svg/i', '<svg' . $attrs_string, $svg_content, 1 );
    }

    // 7. Додаємо тег <title> для доступності, використовуючи alt-текст зображення
    $alt_text = get_post_meta( $svg['id'], '_wp_attachment_image_alt', true );
    if ( ! empty( $alt_text ) ) {
        $title_tag   = '<title>' . esc_html( $alt_text ) . '</title>';
        $svg_content = preg_replace( '/(<svg[^>]*>)/i', '$1' . $title_tag, $svg_content, 1 );
    }

    // 8. Додаємо `fill="currentColor"` до елементів, у яких немає власного fill
    $svg_content = preg_replace_callback(
        '/<(path|circle|rect|ellipse|line|polyline|polygon|g)\s+([^>]*?)>/i',
        function ( $matches ) {
            $tag   = $matches[1];
            $attrs = $matches[2];

            $self_closing = '';
            // Перевіряємо, чи є тег самозакритим (наприклад, <path ... />)
            if ( substr( rtrim( $attrs ), -1 ) === '/' ) {
                // Видаляємо слеш з атрибутів
                $attrs        = rtrim( $attrs );
                $attrs        = substr( $attrs, 0, -1 );
                $self_closing = '/';
            }

            // Прибираємо зайві пробіли в кінці
            $attrs = rtrim( $attrs );

            // Додаємо fill="currentColor", якщо атрибут fill відсутній
            if ( ! preg_match( '/fill\s*=/i', $attrs ) ) {
                $attrs .= ' fill="currentColor"';
            }

            // Збираємо тег назад, зберігаючи самозакритий слеш
            return '<' . $tag . ' ' . $attrs . $self_closing . '>';
        },
        $svg_content
    );

    // 9. Зберігаємо оброблений SVG у кеш
    set_transient( $cache_key, $svg_content, DAY_IN_SECONDS );

    return $svg_content;
}
