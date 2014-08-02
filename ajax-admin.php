<?php

global $wpdb;

require_once("class.ezfc_backend.php");
$ezfc = new Ezfc_backend();

parse_str($_REQUEST["data"], $data);
$action = $data["action"];
$id 	= $data["id"];

if ($action == "form_duplicate" || $action == "form_save_template" || $action == "form_template_delete") {
	send_ajax(array(
		"error" => "Only available in the premium version."
	));

	die();
}

/**
	forms
**/
if ($action == "form_add") {
	$new_form_id = $ezfc->form_add($id);

	// error
	if (is_array($new_form_id)) {
		send_ajax($new_form_id);
		die();
	}

	$ret = array(
		"elements" => $ezfc->form_elements_get($new_form_id),
		"form"     => $ezfc->form_get($new_form_id),
		"options"  => $ezfc->form_get_options($new_form_id)
	);

	send_ajax($ret);
}

if ($action == "form_delete") {
	send_ajax($ezfc->form_delete($id));
}

if ($action == "form_file_delete") {
	send_ajax($ezfc->form_file_delete($id));
}

if ($action == "form_duplicate") {
	// send from form class due to delayed insert
	send_ajax($ezfc->form_duplicate($id));
}

if ($action == "form_get") {
	$ret = array(
		"elements" => $ezfc->form_elements_get($id),
		"form"     => $ezfc->form_get($id),
		"options"  => $ezfc->form_get_options($id)
	);

	send_ajax($ret);
}

if ($action == "form_get_submissions") {
	$ret = array(
		"files"       => array(),
		"submissions" => $ezfc->form_get_submissions($id)
	);

	send_ajax($ret);
}

if ($action == "form_preview") {
	require_once("class.ezfc_frontend.php");
	$tmp_frontend = new Ezfc_frontend();

	//send_ajax($tmp_frontend->get_output($id));
	send_ajax($tmp_frontend->get_output(-1, $data["elements"]));
}

if ($action == "form_save_template") {
	send_ajax($ezfc->form_save_template($id));
}


if ($action == "form_save") {
	// update form info
	$ezfc->form_update($id, $data["ezfc-form-name"]);

	// update form elements
	$res = $ezfc->form_elements_save($id, $data["elements"]);
	if ($res !== 1) {
		send_ajax($res);
		die();
	}

	send_ajax($res);
}

if ($action == "form_submission_delete") {
	send_ajax($ezfc->form_submission_delete($id));
}

if ($action == "form_template_delete") {
	send_ajax($ezfc->form_template_delete($id));
}

// update options
if ($action == "form_update_options") {	
	send_ajax($ezfc->form_update_options($id, $data["opt"]));
}

/**
	elements
**/
if ($action == "elements_get") {
	$ret = array(
		"elements" => $ezfc->elements_get()
	);

	send_ajax($ret);
}

if ($action == "element_get") {
	$ret = array(
		"element" => $ezfc->element_get($id)
	);

	send_ajax($ret);
}

if ($action == "form_element_add") {
	$f_id = (int) $data["f_id"];
	$e_id = (int) $data["e_id"];
	$type = mysql_real_escape_string($data["type"]);

	$new_element_id = $ezfc->form_element_add($f_id, $e_id, $type);
	send_ajax($ezfc->form_element_get($new_element_id));
}

if ($action == "form_element_delete") {
	send_ajax($ezfc->form_element_delete($id));
}

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