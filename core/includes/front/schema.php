<?php

if(!defined('ABSPATH')){exit;}

add_action('wp_head', 'render_schema_json_ld', 1);

function render_schema_json_ld(){
    if(is_admin() || !get_option('enable_schema_json_ld')){
        return;
    }

    $graph = array();
    $organization = get_schema_organization();

    if($organization){
        $graph[] = $organization;
    }

    $website = get_schema_website();
    if($website){
        $graph[] = $website;
    }

    $webpage = get_schema_webpage();
    if($webpage){
        $graph[] = $webpage;
    }

    if(is_singular('post')){
        $article = get_schema_article();
        if($article){
            $graph[] = $article;
        }
    }

    if(empty($graph)){
        return;
    }

    $schema = array(
        '@context' => 'https://schema.org',
        '@graph'   => $graph,
    );

    echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . '</script>' . "\n";
}

function get_schema_organization(){
    $general_fields = function_exists('cache_general_fields') ? cache_general_fields() : array();
    $general_fields = is_array($general_fields) ? $general_fields : array();
    $site_name = get_bloginfo('name');
    $site_description = get_bloginfo('description');
    $name = schema_get_string_option('schema_org_name', $site_name);

    if(!$name){
        return null;
    }

    $org = array(
        '@type' => schema_get_organization_type(),
        '@id'   => home_url('/#organization'),
        'name'  => $name,
        'url'   => home_url('/'),
    );

    $description = schema_get_string_option('schema_org_description', $site_description);
    if($description){
        $org['description'] = $description;
    }

    $logo_url = schema_get_logo_url($general_fields);
    if($logo_url){
        $org['logo'] = array(
            '@type' => 'ImageObject',
            '@id'   => home_url('/#logo'),
            'url'   => $logo_url,
        );
        $org['image'] = array('@id' => home_url('/#logo'));
    }

    if(!empty($general_fields['footer_email'])){
        $org['email'] = sanitize_email($general_fields['footer_email']);
    }

    if(!empty($general_fields['footer_phone'])){
        $org['telephone'] = sanitize_text_field($general_fields['footer_phone']);
    }

    $address = get_schema_address($general_fields);
    if($address){
        $org['address'] = $address;
    }

    if(!empty($general_fields['opening_hours']) && is_array($general_fields['opening_hours'])){
        $hours = schema_format_working_hours($general_fields['opening_hours']);
        if($hours){
            $org['openingHoursSpecification'] = $hours;
        }
    }

    $same_as = get_schema_social_links($general_fields);
    if($same_as){
        $org['sameAs'] = $same_as;
    }

    return schema_clean_array($org);
}

function get_schema_website(){
    return array(
        '@type'           => 'WebSite',
        '@id'             => home_url('/#website'),
        'name'            => get_bloginfo('name'),
        'url'             => home_url('/'),
        'inLanguage'      => get_bloginfo('language'),
        'publisher'       => array('@id' => home_url('/#organization')),
        'potentialAction' => array(
            '@type'       => 'SearchAction',
            'target'      => array(
                '@type'       => 'EntryPoint',
                'urlTemplate' => home_url('/?s={search_term_string}'),
            ),
            'query-input' => 'required name=search_term_string',
        ),
    );
}

function get_schema_webpage(){
    $url = schema_get_current_url();
    $page = array(
        '@type'      => 'WebPage',
        '@id'        => $url . '#webpage',
        'url'        => $url,
        'name'       => wp_get_document_title(),
        'isPartOf'   => array('@id' => home_url('/#website')),
        'about'      => array('@id' => home_url('/#organization')),
        'inLanguage' => get_bloginfo('language'),
    );

    if(is_singular()){
        $modified = get_the_modified_date('c');
        if($modified){
            $page['dateModified'] = $modified;
        }
    }

    return $page;
}

function get_schema_article(){
    $post_id = get_the_ID();

    if(!$post_id){
        return null;
    }

    $url = get_permalink($post_id);
    $article = array(
        '@type'            => 'Article',
        '@id'              => $url . '#article',
        'headline'         => get_the_title($post_id),
        'url'              => $url,
        'datePublished'    => get_the_date('c', $post_id),
        'dateModified'     => get_the_modified_date('c', $post_id),
        'author'           => array('@id' => home_url('/#organization')),
        'publisher'        => array('@id' => home_url('/#organization')),
        'mainEntityOfPage' => array('@id' => $url . '#webpage'),
        'inLanguage'       => get_bloginfo('language'),
    );

    $description = get_post_meta($post_id, '_yoast_wpseo_metadesc', true);
    if(!$description){
        $description = get_the_excerpt($post_id);
    }
    if($description){
        $article['description'] = wp_strip_all_tags($description);
    }

    $thumbnail_id = get_post_thumbnail_id($post_id);
    if($thumbnail_id){
        $image = wp_get_attachment_image_src($thumbnail_id, 'full');
        if($image){
            $article['image'] = array(
                '@type'  => 'ImageObject',
                'url'    => $image[0],
                'width'  => $image[1],
                'height' => $image[2],
            );
        }
    }

    return schema_clean_array($article);
}

function schema_get_organization_type(){
    $type = schema_get_string_option('schema_org_type', 'LocalBusiness');
    $allowed = array('Organization', 'LocalBusiness', 'ProfessionalService');

    return in_array($type, $allowed, true) ? $type : 'LocalBusiness';
}

function schema_get_logo_url($general_fields){
    $logo_url = '';

    if(!empty($general_fields['logo_svg'])){
        $logo_url = schema_get_image_url($general_fields['logo_svg']);
    }

    if(!$logo_url && !empty($general_fields['logo_png'])){
        $logo_url = schema_get_image_url($general_fields['logo_png']);
    }

    $site_icon = get_site_icon_url(512);

    if(!$logo_url && $site_icon){
        $logo_url = $site_icon;
    }

    return $logo_url ? esc_url_raw($logo_url) : '';
}

function schema_get_image_url($image){
    if(is_array($image) && !empty($image['url'])){
        return $image['url'];
    }

    if(is_numeric($image)){
        $url = wp_get_attachment_image_url((int)$image, 'full');
        return $url ? $url : '';
    }

    return is_string($image) ? $image : '';
}

function get_schema_address($general_fields){
    $address = array('@type' => 'PostalAddress');

    $street = schema_get_string_option('schema_org_street');
    $city = schema_get_string_option('schema_org_city');
    $postal_code = schema_get_string_option('schema_org_postal_code');
    $country = schema_get_string_option('schema_org_country');

    if($street){
        $address['streetAddress'] = $street;
    } elseif(!empty($general_fields['footer_address']) && is_array($general_fields['footer_address']) && !empty($general_fields['footer_address']['title'])){
        $address['streetAddress'] = wp_strip_all_tags($general_fields['footer_address']['title']);
    } elseif(!empty($general_fields['footer_address']) && is_string($general_fields['footer_address'])){
        $address['streetAddress'] = wp_strip_all_tags($general_fields['footer_address']);
    }

    if($city){
        $address['addressLocality'] = $city;
    }

    if($postal_code){
        $address['postalCode'] = $postal_code;
    }

    if(!$country && count($address) > 1){
        $country = 'UA';
    }

    if($country){
        $address['addressCountry'] = strtoupper(sanitize_text_field($country));
    }

    return count($address) > 1 ? $address : null;
}

function get_schema_social_links($general_fields){
    if(empty($general_fields['footer_socials']) || !is_array($general_fields['footer_socials'])){
        return array();
    }

    $links = array();
    foreach($general_fields['footer_socials'] as $item){
        if(!empty($item['url'])){
            $links[] = esc_url_raw($item['url']);
        }
    }

    return array_values(array_unique(array_filter($links)));
}

function schema_format_working_hours($data){
    $day_map = array(
        'Mo' => 'Monday',
        'Tu' => 'Tuesday',
        'We' => 'Wednesday',
        'Th' => 'Thursday',
        'Fr' => 'Friday',
        'Sa' => 'Saturday',
        'Su' => 'Sunday',
    );

    $specs = array();
    foreach($data as $day_key => $day_data){
        if(empty($day_map[$day_key]) || empty($day_data['enabled']) || empty($day_data['opens']) || empty($day_data['closes'])){
            continue;
        }

        $specs[] = array(
            '@type'     => 'OpeningHoursSpecification',
            'dayOfWeek' => $day_map[$day_key],
            'opens'     => sanitize_text_field($day_data['opens']),
            'closes'    => sanitize_text_field($day_data['closes']),
        );
    }

    return $specs;
}

function schema_get_current_url(){
    if(is_front_page()){
        return home_url('/');
    }

    global $wp;
    $request = isset($wp->request) ? $wp->request : '';

    return $request ? trailingslashit(home_url($request)) : home_url('/');
}

function schema_get_string_option($name, $default = ''){
    $value = get_option($name, $default);

    if(is_string($value)){
        $value = trim(wp_strip_all_tags($value));
    }

    return $value !== false && $value !== '' ? $value : $default;
}

function schema_clean_array($value){
    if(!is_array($value)){
        return $value;
    }

    foreach($value as $key => $item){
        if(is_array($item)){
            $item = schema_clean_array($item);
        }

        if($item === '' || $item === null || $item === array()){
            unset($value[$key]);
            continue;
        }

        $value[$key] = $item;
    }

    return $value;
}
