<?php
/*
Plugin Name: ez Form Calculator Free
Plugin URI: http://www.mials.de/mials/ezfc/
Description: With ez Form Calculator, you can simply create a form calculator for both yourself and your customers. Easily add basic form elements like checkboxes, dropdown menus, radio buttons etc. with only a few clicks. Each form element can be assigned a value which will automatically be calculated. Get the premium version at <a href="http://codecanyon.net/item/ez-form-calculator-wordpress-plugin/7595334?ref=keksdieb">CodeCanyon</a>.
Version: 1.3
Author: Michael Schuppenies
Author URI: http://www.mials.de/
*/

defined( 'ABSPATH' ) OR exit;

// actions
add_action("admin_menu", "ezfc_setup", 999);
add_action("init", "ezfc_load_language");
add_action("wp_head", "ezfc_wp_head");
add_action("wp_ajax_ezfc_backend", "ezfc_ajax");
add_action("wp_ajax_ezfc_frontend", "ezfc_ajax_frontend");
add_action("wp_ajax_nopriv_ezfc_frontend", "ezfc_ajax_frontend");

// hooks
register_activation_hook(__FILE__, "ezfc_register");
register_uninstall_hook(__FILE__, "ezfc_uninstall");

/**
	setup
**/
// version
function ezfc_get_version() {
	return "1.3";
}
// register plugin
function ezfc_register() {
	require_once("ezfc-register.php");
}
// uninstall plugin
function ezfc_uninstall() {
	require_once("ezfc-uninstall.php");
}
// language
function ezfc_load_language() {
	load_plugin_textdomain('ezfc', false, dirname(plugin_basename(__FILE__)) . '/');
}

/**
	admin pages
**/
function ezfc_setup() {
	$role = "edit_pages"; // todo: change settings

	add_menu_page("ezfc", __("ez Form Calculator Free", "ezfc"), $role, "ezfc", "ezfc_page_main");
	add_submenu_page("ezfc", __("Global settings", "ezfc"), __("Global settings", "ezfc"), $role, "ezfc-options", "ezfc_page_settings");
	add_submenu_page("ezfc", __("Premium version", "ezfc"), __("Premium version", "ezfc"), $role, "ezfc-premium", "ezfc_page_premium");
}

function ezfc_page_main() {
	ezfc_load_scripts("backend");
	require_once("ezfc-page-main.php");
}

function ezfc_page_settings() {
	ezfc_load_scripts("backend");
	require_once("ezfc-page-settings.php");
}

function ezfc_page_premium() {
	ezfc_load_scripts("backend");
	require_once("ezfc-page-premium.php");
}


/**
	scripts
**/
function ezfc_load_scripts($end="frontend") {
	wp_enqueue_script("jquery");

	if ($end == "backend") {
		wp_enqueue_media();
		
		wp_enqueue_style("jquery-ui", plugins_url("assets/css/jquery-ui.theme.min.css", __FILE__));
		wp_enqueue_style("jquerytimepicker-css", plugins_url("assets/css/jquery.timepicker.css", __FILE__));
        wp_enqueue_style("thickbox");
		wp_enqueue_style("ezfc-css-frontend", plugins_url("style-frontend.css", __FILE__));
		wp_enqueue_style("ezfc-css-backend", plugins_url("style-backend.css", __FILE__));
		wp_enqueue_style("ezfc-css-grid", plugins_url("assets/css/ezfc-grid.min.css", __FILE__));
		wp_enqueue_style("font-awesome", plugins_url("assets/css/font-awesome.min.css", __FILE__));

		wp_enqueue_script("jquery-ui-core");
		wp_enqueue_script("jquery-ui-mouse");
		wp_enqueue_script("jquery-ui-widget");
		wp_enqueue_script("jquery-ui-dialog");
		wp_enqueue_script("jquery-ui-draggable");
		wp_enqueue_script("jquery-ui-droppable");
		wp_enqueue_script("jquery-ui-selectable");
		wp_enqueue_script("jquery-ui-sortable");
		wp_enqueue_script("jquerytimepicker", plugins_url("assets/js/jquery.timepicker.min.js", __FILE__));
		wp_enqueue_script("thickbox");
		//wp_enqueue_script("ezfc-backend", plugins_url("backend.min.js", __FILE__));
		wp_enqueue_script("ezfc-backend", plugins_url("backend.js", __FILE__));

		wp_localize_script("ezfc-backend", "ezfc_vars", array(
			"delete" => __("Delete", "ezfc"),
			"delete_form" => __("Really delete the selected form?", "ezfc"),
			"delete_element" => __("Really delete the selected element?", "ezfc"),
			"form_changed" => __("You have changed the form without having saved. Really leave the current form unsaved?"),
			"yes_no" => array(
				"yes" => __("Yes", "ezfc"),
				"no"  => __("No", "ezfc")
			)
		));
	}

	if ($end == "frontend") {
		remove_filter( 'the_content', 'wpautop' );
		remove_filter( 'the_excerpt', 'wpautop' );
		
		if (get_option("ezfc_jquery_ui") == 1) {
			wp_enqueue_style("jquery-ui", plugins_url("assets/css/jquery-ui.theme.min.css", __FILE__));
		}
		wp_enqueue_style("opentip", plugins_url("assets/css/opentip.css", __FILE__));
		wp_enqueue_style("ezfc-css-frontend", plugins_url("style-frontend.css", __FILE__));

		wp_enqueue_script("jquery");
		wp_enqueue_script("jquery-ui-core");
		wp_enqueue_script("jquery-ui-datepicker");
		wp_enqueue_script("jquery-ui-progressbar");
		wp_enqueue_script("jquery-ui-widget");
		wp_enqueue_script("jquery-opentip", plugins_url("assets/js/opentip-jquery.min.js", __FILE__));
		// todo!
		//wp_enqueue_script("numeraljs-languages", plugins_url("assets/js/languages.min.js", __FILE__));
		wp_enqueue_script("numeraljs", plugins_url("assets/js/numeral.min.js", __FILE__));
		wp_enqueue_script("jquery-file-upload", plugins_url("assets/js/jquery.fileupload.min.js", __FILE__));
		wp_enqueue_script("jquery-iframe-transport", plugins_url("assets/js/jquery.iframe-transport.min.js", __FILE__));
		wp_enqueue_script("ezfc-frontend", plugins_url("frontend.min.js", __FILE__));	

		wp_localize_script("ezfc-frontend", "ezfc_vars", array(
			"noid"           => __("No form with the requested ID found.", "ezfc"),
			"price_format"   => get_option("ezfc_price_format"),
			"uploading"      => __("Uploading...", "ezfc"),
			"upload_success" => __("File upload successful.", "ezfc"),
			"yes_no" => array(
				"yes" => __("Yes", "ezfc"),
				"no"  => __("No", "ezfc")
			)
		));
	}
}

/**
	ajax
**/
function ezfc_ajax_frontend() {
	require_once(plugin_dir_path(__FILE__)."ajax.php");
	exit;
}

// backend
function ezfc_ajax() {
	require_once("ajax-admin.php");
}

function ezfc_wp_head() {
	?>
		<script type="text/javascript">
		ezfc_ajaxurl = "<?php echo admin_url( 'admin-ajax.php' ); ?>";
		ezfc_ajaxurl_fileupload = "<?php echo plugins_url('ajax-fileupload.php', __FILE__); ?>";
		</script>
	<?php
}


/**
	shortcode
**/
function ezfc_shortcode($atts, $content = null) {
	extract(shortcode_atts(array(
		"id" => null
	), $atts));

	$id = (int) $id;

	wp_register_style('ezfc-css-frontend', plugins_url("style-frontend.css", __FILE__) );
	ezfc_load_scripts("frontend");

	require_once(plugin_dir_path(__FILE__)."class.ezfc_frontend.php");
	$ezfc = new ezfc_frontend();

	return $ezfc->get_output($id);
}
add_shortcode("ezfc", "ezfc_shortcode");