<?php

defined( 'ABSPATH' ) OR exit;
if (!current_user_can('activate_plugins')) return;

require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

global $wpdb;

$tables = array(
	"{$wpdb->prefix}ezfcf_elements",
	"{$wpdb->prefix}ezfcf_files",
	"{$wpdb->prefix}ezfcf_forms",
	"{$wpdb->prefix}ezfcf_forms_elements",
	"{$wpdb->prefix}ezfcf_forms_options",
	"{$wpdb->prefix}ezfcf_options",
	"{$wpdb->prefix}ezfcf_submissions",
	"{$wpdb->prefix}ezfcf_templates"
);

$options = array(
	"css_form_label_width",
	"captcha_public",
	"captcha_private",
	"jquery_ui",
	"price_format",
	"uninstall_keep_data",
	"version"
);

// do not delete data
if (get_option("ezfc_uninstall_keep_data") == 1) return;

foreach ($tables as $table) {
	$wpdb->query("DROP TABLE `{$table}`");
}

foreach ($options as $o) {
	delete_option("ezfc_{$o}");
}