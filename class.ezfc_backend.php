<?php

class Ezfc_backend {
	function __construct() {
		global $wpdb;
		$this->wpdb = $wpdb;

		$this->tables = array(
			"elements"       => "{$this->wpdb->prefix}ezfcf_elements",
			"files"          => "{$this->wpdb->prefix}ezfcf_files",
			"forms"			 => "{$this->wpdb->prefix}ezfcf_forms",
			"forms_elements" => "{$this->wpdb->prefix}ezfcf_forms_elements",
			"forms_options"  => "{$this->wpdb->prefix}ezfcf_forms_options",
			"options"        => "{$this->wpdb->prefix}ezfcf_options",
			"submissions"    => "{$this->wpdb->prefix}ezfcf_submissions",
			"templates"      => "{$this->wpdb->prefix}ezfcf_templates",
			"tmp"            => "{$this->wpdb->prefix}ezfcf_tmptable"
		);
	}

	function form_get_count() {
		$res = $this->wpdb->get_var("SELECT count(*) FROM {$this->tables["forms"]}");

		return $res;
	}

	/**
		forms
	**/
	function form_add($template_id=0) {
		if ($this->form_get_count() >= 1) return $this->send_message("error", __("Only 1 form per site allowed in the free version", "ezfc"));

		$res = $this->wpdb->insert(
			$this->tables["forms"],
			array("name" => __("New Form", "ezfc")),
			array("%s")
		);

		$insert_id = $this->wpdb->insert_id;

		// add template elements
		$template_id = (int) $template_id;
		if ($template_id != 0) {
			$template = $this->form_template_get($template_id);
			$template_elements = json_decode($template->data);

			foreach ($template_elements as $element) {
				$el_res = $this->form_element_add($insert_id, $element->e_id, $element->data);

				if (!$el_res) return $this->send_message("error", __("Could not insert element data from template.", "ezfc"));
			}
		}

		// add default options
		$default_options = $this->wpdb->query($this->wpdb->prepare("
			INSERT INTO {$this->tables["forms_options"]} (f_id, o_id, value)
			(
				SELECT %d, id, value FROM {$this->tables["options"]}
			)
		", $insert_id));

		return $insert_id;
	}

	function form_delete($id) {
		$id = (int) $id;

		$res = $this->wpdb->delete(
			$this->tables["forms"],
			array("id" => $id),
			array("%d")
		);

		if ($res === false) return $this->send_message("error", __("Could not delete form: {$wpdb->last_error}"));

		$res = $this->wpdb->delete(
			$this->tables["forms_elements"],
			array("f_id" => $id),
			array("%d")
		);

		if ($res === false) return $this->send_message("error", __("Could not delete form elements: {$wpdb->last_error}"));

		$res = $this->wpdb->delete(
			$this->tables["forms_options"],
			array("f_id" => $id),
			array("%d")
		);

		if ($res === false) return $this->send_message("error", __("Could not delete form options: {$wpdb->last_error}"));

		return $this->send_message("success", __("Form deleted.", "ezfc"));
	}

	function forms_get() {
		$res = $this->wpdb->get_results("SELECT * FROM {$this->tables["forms"]}");

		return $res;
	}

	function form_get($id) {
		if (!$id) return $this->send_message("error", __("No ID", "ezfc"));

		$res = $this->wpdb->get_row($this->wpdb->prepare(
			"SELECT * FROM {$this->tables["forms"]} WHERE id=%d",
			$id
		));

		return $res;
	}

	function form_get_options($id) {
		if (!$id) return $this->send_message("error", __("No ID", "ezfc"));

		$res = $this->wpdb->get_results($this->wpdb->prepare(
			"SELECT fo.f_id, fo.o_id, fo.value, o.name, o.type
			FROM {$this->tables["forms_options"]} AS fo
			JOIN {$this->tables["options"]} AS o
				ON fo.o_id=o.id
			WHERE fo.f_id=%d;",
			$id
		));

		return $res;
	}

	/**
		submissions
	**/
	function form_get_submissions($id) {
		if (!$id) return $this->send_message("error", __("No ID", "ezfc"));

		$res = $this->wpdb->get_results($this->wpdb->prepare(
			"SELECT * FROM {$this->tables["submissions"]} WHERE f_id=%d ORDER BY id DESC",
			$id
		));

		return $res;
	}

	function form_submission_delete($id) {
		$id = (int) $id;

		$res = $this->wpdb->delete(
			$this->tables["submissions"],
			array("id" => $id),
			array("%d")
		);

		if ($res === false) return $this->send_message("error", __("Could not delete form: {$wpdb->last_error}"));

		return $this->send_message("success", __("Submission deleted.", "ezfc"));
	}

	function form_update($id, $name) {
		$id = (int) $id;

		$res = $this->wpdb->update(
			$this->tables["forms"],
			array("name" => $name),
			array("id" => $id),
			array("%s"),
			array("%d")
		);

		return $res;
	}

	function form_update_options($id, $options) {	
		if (count($options) < 1) return $this->send_message("success", __("Settings updated", "ezfc"));

		foreach ($options as $o_id=>$value) {
			$res = $this->wpdb->replace(
				$this->tables["forms_options"],
				array(
					"f_id"  => $id,
					"o_id"  => $o_id,
					"value" => $value
				),
				array(
					"%d",
					"%d",
					"%s"
				)
			);

			if ($res === false) {
				return $this->send_message("error", $this->wpdb->last_error);
			}
		}

		return $this->send_message("success", __("Settings updated", "ezfc"));
	}

	/**
		elements
	**/
	function elements_get() {
		$res = $this->wpdb->get_results("SELECT * FROM {$this->tables["elements"]} ORDER BY id ASC");

		return $res;
	}

	function element_get($id) {
		if (!$id) return $this->send_message("error", __("No ID.", "ezfc"));

		$res = $this->wpdb->get_row($this->wpdb->prepare(
			"SELECT * FROM {$this->tables["elements"]} WHERE id=%d",
			$id
		));

		return $res;
	}

	/**
		form elements
	**/
	function form_elements_get($id) {
		if (!$id) return $this->send_message("error", __("No ID given.", "ezfc"));

		$res = $this->wpdb->get_results($this->wpdb->prepare(
			"SELECT * FROM {$this->tables["forms_elements"]} WHERE f_id=%d ORDER BY position DESC",
			$id
		));

		return $res;
	}

	function form_element_get($id) {
		if (!$id) return $this->send_message("error", __("No ID given.", "ezfc"));

		$res = $this->wpdb->get_row($this->wpdb->prepare(
			"SELECT * FROM {$this->tables["forms_elements"]} WHERE id=%d",
			$id
		));

		return $res;
	}

	function form_element_add($f_id, $e_id, $data=null) {
		$form_elements = $this->form_elements_get($f_id);
		if (count($form_elements) >= 5) return $this->send_message("error", __("Only 5 elements allowed in the free version."));
		
		// default data
		if (!$data) {
			$default_data = $this->wpdb->get_var($this->wpdb->prepare(
				"SELECT data FROM {$this->tables["elements"]} WHERE id=%d",
				$e_id
			));
		}
		// template data
		else {
			$default_data = $data;
		}

		$res = $this->wpdb->insert(
			$this->tables["forms_elements"],
			array(
				"f_id" => $f_id,
				"e_id" => $e_id,
				"data" => $default_data
			),
			array(
				"%d",
				"%d",
				"%s"
			)
		);

		if (!$res) return $this->send_message("error", __("Could not insert element to form", "ezfc") . ": " . $this->wpdb->last_error);

		return $this->wpdb->insert_id;
	}

	function form_element_delete($id) {
		$id = (int) $id;

		$res = $this->wpdb->delete(
			$this->tables["forms_elements"],
			array("id" => $id),
			array("%d")
		);

		return $this->send_message("success", __("Element deleted.", "ezfc"));
	}

	function form_elements_save($id, $data) {
		if (!$id) return $this->send_message("error", __("No ID.", "ezfc"));

		// no elements present --> save complete
		if (count($data) < 1 ) return 1;

		$max = count($data);
		foreach ($data as $id=>$element) {
			$element_data = json_encode($element);

			$res = $this->wpdb->update(
				$this->tables["forms_elements"],
				array(
					"data"     => $element_data,
					"position" => $max--
				),
				array("id" => $id),
				array(
					"%s",
					"%d"
				),
				array("%d")
			);

			if (!$res && $res!=0) return $this->send_message("error", __("Form elements update error: ", "ezfc") . $this->wpdb->last_error);
		}

		return 1;
	}


	/**
		form templates
	**/
	function form_templates_get() {
		$res = $this->wpdb->get_results("SELECT * FROM {$this->tables["templates"]}");

		return $res;
	}

	function form_template_get($id) {
		if (!$id) return $this->send_message("error", __("No ID given.", "ezfc"));

		$res = $this->wpdb->get_row($this->wpdb->prepare(
			"SELECT * FROM {$this->tables["templates"]} WHERE id=%d",
			$id
		));

		return $res;
	}

	/**
		settings
	**/
	function get_settings() {
		$res = $this->wpdb->get_results("SELECT * FROM {$this->tables["options"]}");
		return $res;
	}
	

	function update_options($settings, $overwrite=0, $upgrade=0) {
		foreach ($settings as $o_id=>$value) {
			$res = $this->wpdb->update(
				$this->tables["options"],
				array("value" => $value),
				array("id" => $o_id),
				array("%s"),
				array("%d")
			);

			if ($res === false) {
				return $this->send_message("error", $this->wpdb->last_error);
			}

			// overwrite settings, skip on name
			if ($overwrite == 0 || $name == "name") continue;

			$res = $this->wpdb->update(
				$this->tables["forms_options"],
				array("value" => $value),
				array("o_id" => $o_id),
				array("%s"),
				array("%d")
			);
		}

		if ($upgrade == 1) {
			$this->upgrade();
		}

		return $this->send_message("success", __("Settings updated", "ezfc"));
	}

	function upgrade() {
		$current_version = (float) ezfc_get_version();
		$plugin_version  = (float) get_option("ezfc_version", -1);

		$elements = $this->array_index_key($this->elements_get(), "id");
		$forms    = $this->forms_get();

		foreach ($forms as $fi=>$form) {
			// insert missing options
			$query = "SELECT 
				o.id, o.value
			FROM
				{$this->tables["options"]} as o
				left join {$this->tables["forms_options"]} as fo on (o.id = fo.o_id AND fo.f_id = {$form->id})
			WHERE
				fo.o_id is NULL";

			$missing_options = $this->wpdb->get_results($query);

			foreach ($missing_options as $mo) {
				$mo_res = $this->wpdb->insert(
					$this->tables["forms_options"],
					array(
						"f_id"  => $form->id,
						"o_id"  => $mo->id,
						"value" => $mo->value
					),
					array(
						"%d",
						"%d",
						"%s"
					)
				);

				if ($mo_res === false) {
					return $this->send_message("error", $this->wpdb->last_error);
				}
			}

			// insert missing element options
			$form_elements_data = $this->wpdb->get_results($this->wpdb->prepare("SELECT id, e_id, data FROM {$this->tables["forms_elements"]} WHERE f_id=%d", $form->id));

			foreach ($form_elements_data as $element_data) {
				$data_array    = json_decode($elements[$element_data->e_id]->data, true);
				$data_el_array = json_decode($element_data->data, true);
				
				// merge global element data with form element data
				$data_merged   = array_merge($data_array, $data_el_array);
				$data_new      = json_encode($data_merged);

				$res = $this->wpdb->update(
					$this->tables["forms_elements"],
					array("data" => $data_new),
					array("id" => $element_data->id),
					array("%s"),
					array("%d")
				);

				if ($res === false) {
					return $this->send_message("error", $this->wpdb->last_error);
				}
			}
		}

		// version specific upgrades
		$query_upgrade = array();
		if ($old_version <= 1.0) {
			$query_upgrade[] = "ALTER IGNORE TABLE `{$this->tables["submissions"]}` ADD COLUMN `ref_id` VARCHAR(16) NOT NULL;";
		}

		foreach ($query_upgrade as $q) {
			$res = $this->wpdb->query($q);
		}

		// all upgrades finished, update version
		update_option("ezfc_version", ezfc_get_version());
	}


	function send_message($type, $msg, $id=0) {
		return array(
			$type 	=> $msg,
			"id"	=> $id
		);
	}

	private function array_index_key($array, $key) {
		$ret_array = array();

		foreach ($array as $v) {
			if (is_object($v)) {
				$ret_array[$v->$key] = $v;
			}
			if (is_array($v)) {
				$ret_array[$v[$key]] = $v;
			}
		}

		return $ret_array;
	}
}