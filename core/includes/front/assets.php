<?php

if(!defined('ABSPATH')){exit;}

/** custom jquery and other js */
function load_personal_resources() {

    if( !is_admin()){
		wp_deregister_script('jquery');
	}

    wp_register_script('jquery', TEMPLATE_DIRECTORY_URL . 'assets/js/jquery.min.js', '', JQUERY_VERSION, true);
    wp_enqueue_script('jquery');
    wp_register_script('plugins', TEMPLATE_DIRECTORY_URL.'assets/js/plugins.min.js', '', ASSETS_VERSION, true);
    wp_enqueue_script('plugins');
    wp_register_script('custom', TEMPLATE_DIRECTORY_URL.'assets/js/custom.min.js', '', ASSETS_VERSION, true);
    wp_enqueue_script('custom');

    wp_register_style('main', TEMPLATE_DIRECTORY_URL . 'assets/css/style.min.css', array(), ASSETS_VERSION, 'all');
    wp_enqueue_style('main');

}
add_action('wp_enqueue_scripts', 'load_personal_resources');

/** footer Google Maps init */
function footer_google_maps_js_init(){
    if($google_maps_api_key = get_option('google_maps_api_key')){
        ?>
        <script>
            (g=>{var h,a,k,p="The Google Maps JavaScript API",c="google",l="importLibrary",q="__ib__",m=document,b=window;b=b[c]||(b[c]={});var d=b.maps||(b.maps={}),r=new Set,e=new URLSearchParams,u=()=>h||(h=new Promise(async(f,n)=>{await (a=m.createElement("script"));e.set("libraries",[...r]+"");for(k in g)e.set(k.replace(/[A-Z]/g,t=>"_"+t[0].toLowerCase()),g[k]);e.set("callback",c+".maps."+q);a.src=`https://maps.${c}apis.com/maps/api/js?`+e; a.async=true;d[q]=f;a.onerror=()=>h=n(Error(p+" could not load."));a.nonce=m.querySelector("script[nonce]")?.nonce||"";m.head.append(a)}));d[l]?console.warn(p+" only loads once. Ignoring:",g):d[l]=(f,...n)=>r.add(f)&&u().then(()=>d[l](f,...n))})({
                key: "<?php echo esc_js($google_maps_api_key); ?>",
                v: "weekly"
            });
        </script>
        <?php
    }
}
add_action('wp_footer', 'footer_google_maps_js_init',10);
