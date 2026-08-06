<?php

if(!defined('ABSPATH')){exit;}

// Додаємо пункт меню в секцію інструментів
add_action('admin_menu', 'rp_generator_menu');

function rp_generator_menu() {
    add_management_page(
        __('Generate Posts', TEXTDOMAIN), // title в браузері
        __('Generate Posts', TEXTDOMAIN), // пункт меню
        'manage_options', // права доступу
        'random-posts-generator', // slug
        'rp_generator_page' // функція відображення сторінки
    );
}

// Функція для генерації заголовку
function rp_generate_title_from_content($content) {
    $clean_content = strip_tags($content);
    $words = preg_split('/\s+/', $clean_content);
    $words = array_filter($words, function($word) {
        return strlen($word) > 3 && !preg_match('/[.,!?;:]/', $word);
    });
    shuffle($words);
    $word_count = rand(3, 5);
    $selected_words = array_slice($words, 0, $word_count);
    $title = implode(' ', $selected_words);
    $title = mb_strtolower($title);
    $title = mb_strtoupper(mb_substr($title, 0, 1)) . mb_substr($title, 1);
    return $title;
}

// Завантаження зовнішнього ресурсу через WP HTTP API (таймаут + обробка помилок,
// на відміну від file_get_contents, який вішає запит і мовчки падає без allow_url_fopen)
function rp_fetch_remote_body($url) {
    $response = wp_remote_get($url, array('timeout' => 15));

    if(is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200){
        return false;
    }

    $body = wp_remote_retrieve_body($response);

    return $body !== '' ? $body : false;
}

// Функція генерації постів
function generate_random_posts($count, $paragraphs, $with_image) {
    $generated = 0;
    $errors = array();

    for ($i = 1; $i <= $count; $i++) {
        try {
            $lorem_ipsum = rp_fetch_remote_body("https://loripsum.net/api/{$paragraphs}");

            if($lorem_ipsum === false){
                $errors[] = sprintf(__('Error generating post %d: could not reach loripsum.net', TEXTDOMAIN), $i);
                continue;
            }

            $lorem_ipsum_image = false;
            if($with_image) {
                $lorem_ipsum_image = rp_fetch_remote_body('https://picsum.photos/1024/768');
            }

            $post_title = rp_generate_title_from_content($lorem_ipsum);

            $new_post = array(
                'post_type' => 'post',
                'post_title' => $post_title,
                'post_content' => $lorem_ipsum,
                'post_status' => 'publish',
                'post_author' => get_current_user_id()
            );

            $new_posted_item_id = wp_insert_post($new_post);

            if($with_image && $lorem_ipsum_image) {
                $fileName = 'lorem_ipsum_' . $i . '_' . wp_rand() . '.jpg';
                $upload_file = wp_upload_bits($fileName, null, $lorem_ipsum_image);
                $wp_filetype = wp_check_filetype($fileName, null);

                $attachment = array(
                    'post_mime_type' => $wp_filetype['type'],
                    'post_parent' => $new_posted_item_id,
                    'post_title' => preg_replace('/\.[^.]+$/', '', $fileName),
                    'post_content' => '',
                    'post_status' => 'inherit'
                );

                $attachment_id = wp_insert_attachment($attachment, $upload_file['file'], $new_posted_item_id);

                if (!is_wp_error($attachment_id)) {
                    require_once(ABSPATH . "wp-admin" . '/includes/image.php');
                    $attachment_data = wp_generate_attachment_metadata($attachment_id, $upload_file['file']);
                    wp_update_attachment_metadata($attachment_id, $attachment_data);
                    set_post_thumbnail($new_posted_item_id, $attachment_id);
                }
            }

            $generated++;
        } catch (Exception $e) {
            $errors[] = sprintf(
                __('Error generating post %d: %s', TEXTDOMAIN),
                $i,
                $e->getMessage()
            );
        }
    }

    return array(
        'generated' => $generated,
        'errors' => $errors
    );
}

// Сторінка генератора
function rp_generator_page() {
    $message = '';

    if(isset($_POST['generate_posts'])) {
        // Без цих двох перевірок сторінку можна викликати міжсайтовим POST-запитом
        check_admin_referer('rp_generate_posts', 'rp_generator_nonce');

        if(!current_user_can('manage_options')){
            wp_die(__('Permission denied', TEXTDOMAIN));
        }

        // Обмеження max= в розмітці — лише підказка браузеру; справжній ліміт тут,
        // інакше один POST із count=100000 кладе сайт на сотні зовнішніх запитів
        $count = min(100, max(0, intval($_POST['post_count'] ?? 0)));
        $paragraphs = min(50, max(0, intval($_POST['paragraphs'] ?? 0)));
        $with_image = isset($_POST['with_image']) ? true : false;

        if($count > 0 && $paragraphs > 0) {
            $result = generate_random_posts($count, $paragraphs, $with_image);
            $message = sprintf(
                '<div class="notice notice-success"><p>%s</p>',
                sprintf(
                    __('Successfully generated %d posts!', TEXTDOMAIN),
                    $result['generated']
                )
            );
            if(!empty($result['errors'])) {
                $message .= sprintf(
                    '<p>%s<br>%s</p>',
                    __('Errors occurred:', TEXTDOMAIN),
                    implode("<br>", array_map('esc_html', $result['errors']))
                );
            }
            $message .= '</div>';
        }
    }

    ?>
    <div class="wrap">
        <h1><?php _e('Generate Random Posts', TEXTDOMAIN); ?></h1>
        <?php echo $message; ?>

        <form method="post" class="form-table">
            <?php wp_nonce_field('rp_generate_posts', 'rp_generator_nonce'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="post_count"><?php _e('Number of posts to generate', TEXTDOMAIN); ?></label>
                    </th>
                    <td>
                        <input type="number" id="post_count" name="post_count" min="1" max="100" value="1" required>
                        <p class="description"><?php _e('How many posts should be generated (max 100)', TEXTDOMAIN); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="paragraphs"><?php _e('Paragraphs per post', TEXTDOMAIN); ?></label>
                    </th>
                    <td>
                        <input type="number" id="paragraphs" name="paragraphs" min="1" max="50" value="5" required>
                        <p class="description"><?php _e('How many paragraphs should be in each post (max 50)', TEXTDOMAIN); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="with_image"><?php _e('Include featured image', TEXTDOMAIN); ?></label>
                    </th>
                    <td>
                        <input type="checkbox" id="with_image" name="with_image" value="1" checked>
                        <p class="description"><?php _e('Generate and attach a random featured image for each post', TEXTDOMAIN); ?></p>
                    </td>
                </tr>
            </table>

            <?php submit_button(__('Generate Posts', TEXTDOMAIN), 'primary', 'generate_posts'); ?>
        </form>
    </div>
    <?php
}
