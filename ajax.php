<?php

global $wpdb;

require_once("class.ezfc_frontend.php");

$ezfc = new Ezfc_frontend();

parse_str($_POST["data"], $data);
$id = (int) $data["id"];

$check_input_result = $ezfc->check_input($id, $data["ezfc_element"], $data["ref_id"]);

if ($check_input_result["error"]) {
	send_ajax($check_input_result);
	die();
}

// check if recaptcha is present in the current form
$elements      = $ezfc->elements_get();
$form_elements = $ezfc->form_elements_get($id);
$has_recaptcha = false;
$has_upload    = false;

// everything's fine for now
send_ajax($ezfc->insert($id, $data["ezfc_element"], $data["ref_id"]));

die();

function send_ajax($msg) {
	// check for errors in array
	if (is_array($msg)) {
		foreach ($msg as $m) {
			if (is_array($m) && $m["error"]) {
				echo json_encode($m);

				return;
			}
		}
	}

	echo json_encode($msg);
}

?>