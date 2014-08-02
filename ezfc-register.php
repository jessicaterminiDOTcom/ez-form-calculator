<?php

defined( 'ABSPATH' ) OR exit;

require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
global $wpdb;

$query = file_get_contents(dirname(__FILE__) . "/db.sql");
if (!$query) {
	die("Error opening file.");
}

$query_replaced = str_replace("__PREFIX__", $wpdb->prefix, $query);
execute_multiline_sql($query_replaced);

$current_version = ezfc_get_version();
$old_version     = get_option("ezfc_version", -1);
// upgrade from older version;
if ($old_version != $current_version && $old_version != -1) {
	require_once("class.ezfc_backend.php");
	$ezfc_backend = new Ezfc_backend();
	$ezfc_backend->upgrade($old_version, $current_version);
	update_option("ezfc_version", $current_version);
}

// default options
$ezfc_options = array(
	"css_form_label_width" => "",
    "captcha_public"       => "",
    "captcha_private"      => "",
    "jquery_ui"            => 1,
    "price_format"         => "0,0[.]00",
	"uninstall_keep_data"  => 0
);

// set default option values
foreach ($ezfc_options as $option=>$value) {
	if (!get_option("ezfc_" . $option)) {
		update_option("ezfc_" . $option, $value);
	}
}

function execute_multiline_sql($sql, $delim=";") {
    global $wpdb;
    
    $sqlParts = array_filter(explode($delim, $sql));
    foreach($sqlParts as $part) {
    	$part = trim($part);

    	// skip empty parts
    	if (empty($part)) continue;

        $wpdb->query($part);
    }

    return true;
}