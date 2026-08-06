<?php

if(!defined('ABSPATH')){exit;}

/** assets theme version */

if(is_admin()){
	function dashboard_header_add_theme_update_button() {
		global $wp_admin_bar;
		$wp_admin_bar->add_menu( array(
			'id'    => 'update_assets_version',
			'title' => ASSETS_VERSION,
			'href'  => '#update_assets_version',
			'meta'  => array(
				'class' => 'update_assets_version',
				'title' => __("Update theme version", TEXTDOMAIN),
			),
		) );
	}
	add_action( 'wp_before_admin_bar_render', 'dashboard_header_add_theme_update_button' );
	function update_assets_version_header() {
		if(!current_user_can('manage_options')){
			return;
		}
		?>
		<style>
			.update_assets_version a:before{
				content: "\f185";
			}
			.update_assets_version a.working:before{
				content: "\f463";
			}
		</style>
		<script>
            (function ($) {
                $(document).ready(function () {
                    $(".update_assets_version a").click(function(){
                        var thisbutton = $(this);
                        $.ajax({
                            type: "POST",
                            url: ajaxurl,
                            dataType: "json",
                            cache: false,
                            data: {
                                action: "update_assets_version",
                                nonce: "<?php echo esc_js(wp_create_nonce('update_assets_version')); ?>"
                            },
                            beforeSend: function() {
                                thisbutton.addClass("working");
                            },
                            success : function (out) {
                                thisbutton.text(out.new_assets_version);
                                thisbutton.blur();
                                thisbutton.removeClass("working");
                            }
                        });
                        return false;
                    });
                });
            })(jQuery);
		</script>
	<?php }
	add_action('admin_head', 'update_assets_version_header');

	/**
	 * Bump the cache-busting asset version.
	 *
	 * Capability + nonce are mandatory: this writes a site option and resets the
	 * object cache and OPcache, so leaving it open to any logged-in user is both
	 * a CSRF target and a cheap denial-of-service lever.
	 */
	function update_assets_version_function() {

		if(!current_user_can('manage_options')){
			wp_send_json_error(array('message' => __('Permission denied', TEXTDOMAIN)), 403);
		}

		check_ajax_referer('update_assets_version', 'nonce');

		$new_assets_version = round((float) ASSETS_VERSION + 0.01, 2);
		update_option('assets_version', $new_assets_version);

        if (function_exists('wp_cache_flush')) { wp_cache_flush(); }
        if (function_exists('opcache_reset')) { opcache_reset(); }

		wp_send_json(array('new_assets_version' => $new_assets_version));

	}
	add_action( 'wp_ajax_update_assets_version', 'update_assets_version_function' );

}
