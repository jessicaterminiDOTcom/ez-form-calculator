<?php

class Ezfc_frontend {
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
			"settings"       => "{$this->wpdb->prefix}ezfcf_settings",
			"submissions"    => "{$this->wpdb->prefix}ezfcf_submissions"
		);
	}

	function form_get($id) {
		if (!$id) return $this->send_message("error", __("No ID.", "ezfc"));

		$res = $this->wpdb->get_row($this->wpdb->prepare(
			"SELECT * FROM {$this->tables["forms"]} WHERE id=%d",
			$id
		));

		return $res;
	}

	function form_get_count() {
		$res = $this->wpdb->get_var("SELECT count(*) FROM {$this->tables["forms"]}");

		return $res;
	}

	function form_get_options($id) {
		if (!$id) return $this->send_message("error", __("No ID", "ezfc"));

		$res = $this->wpdb->get_results($this->wpdb->prepare(
			"SELECT o.name, fo.value
			FROM {$this->tables["forms_options"]} AS fo
			JOIN {$this->tables["options"]} AS o
				ON fo.o_id=o.id
			WHERE fo.f_id=%d;",
			$id
		));

		return $res;
	}

	/**
		elements
	**/
	function elements_get() {
		$res = $this->wpdb->get_results("SELECT * FROM {$this->tables["elements"]} ORDER BY id ASC");

		$elements_indexed = array();
		foreach ($res as $element) {
			$elements_indexed[$element->id] = $element;
		}

		return $elements_indexed;
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

	/**
		misc functions
	**/
	function check_input($id, $data, $ref_id) {
		if (!$id || !$data) return $this->send_message("error", __("No ID or no data.", "ezfc"));

		$elements      = $this->array_index_key($this->elements_get(), "id");
		$form_elements = $this->array_index_key($this->form_elements_get($id), "id");
		$options       = $this->array_index_key($this->form_get_options($id), "name");

		if ($options["submission_enabled"]->value == 0) {
			return $this->send_message("error", __("Submission not allowed.", "ezfc"));
		}

		//foreach ($data as $element_id=>$input_value) {
		foreach ($form_elements as $fe_id=>$form_element) {
			// checkbox
			if (is_array($data[$fe_id])) {
				if (count($data[$fe_id]) < 1) {
					return $this->send_message("error", __("This field is required.", "ezfc"), $fe_id);
				}
			}
			else {
				$input_value = trim($data[$fe_id]);
				$el_reference = $elements[$form_element->e_id];

				$element_data = json_decode($form_element->data);
				// required field is empty
				$empty = ((!is_string($input_value) || $input_value == "") && $el_reference->type != "fileupload") ? true : false;

				if ($element_data->required == 1 && $empty) {
					return $this->send_message("error", __("This field is required.", "ezfc"), $fe_id);
				}

				if (!$empty) {
					switch ($el_reference->type) {
						case "email":
							if (!filter_var($input_value, FILTER_VALIDATE_EMAIL)) {
								return $this->send_message("error", __("Please enter a valid email address.", "ezfc"), $fe_id);
							}
						break;

						case "numbers":
							if (!filter_var($input_value, FILTER_VALIDATE_INT)) {
								return $this->send_message("error", __("Please enter a valid number.", "ezfc"), $fe_id);
							}

							// min / max values
							if (!empty($element_data->min)) {
								if ($input_value < $element_data->min) return $this->send_message("error", __("Minimum value: ", "ezfc") . $element_data->min , $fe_id);
							}
							if (!empty($element_data->max)) {
								if ($input_value > $element_data->max) return $this->send_message("error", __("Maximum value: ", "ezfc") . $element_data->max , $fe_id);
							}
						break;
					}
				}
			}
		}

		return $this->send_message("success", "");
	}

	/**
		insert submission
	**/
	function insert($id, $data, $ref_id) {
		if (!$id || !$data) return $this->send_message("error", __("No ID or no data.", "ezfc"));
		if ($this->form_get_count() > 1) return $this->send_message("error", __("Maximum forms exceeded."));

		$options = $this->array_index_key($this->form_get_options($id), "name");

		// spam protection
		$spam_time = $options["spam_time"]->value;
		$spam = $this->wpdb->get_row($this->wpdb->prepare(
			"SELECT 1 FROM {$this->tables["submissions"]} WHERE ip='%s' AND date>=DATE_ADD(NOW(), INTERVAL -{$spam_time} SECOND)",
			$_SERVER["REMOTE_ADDR"]
		));

		if ($spam == 1) {
			return $this->send_message("error", __("Spam protection: you need to wait {$spam_time} seconds before you can submit anything.", "ezfc"));
		}

		$elements      = $this->array_index_key($this->elements_get(), "id");
		$form_elements = $this->array_index_key($this->form_elements_get($id), "id");

		// mail output
		$currency = $options["currency"]->value;
		$total = 0;
		$out   = array();
		$out[] = "
		<html>
		<head>
			<meta charset='utf-8' />
			<style type='text/css'>
			table { width: 100%; max-width: 800px; border-collapse: collapse; }
			tr, td { padding: 10px 5px; vertical-align: top; }
			</style>
		</head>
		<body>";
		$out[] = "<h2>" . __("You have received a new submission", "ezfc") . "</h2>";
		$out[] = "<table>";

		$i = 0;
		foreach ($data as $fe_id=>$value) {
			$element = json_decode($form_elements[$fe_id]->data);

			$value_valid = true;
			$value_out = array();
			switch ($elements[$element->e_id]->type) {
				case "email":
					$bcc_mail = $value;
					$value_out[] = "<a href='mailto:{$value}'>{$value}</a>";
				break;

				// no action
				case "hr":
				case "html":
				case "recaptcha":
				break;

				default:
					$value_out[] = $value;
				break;
			}

			$total_out = array();
			if ($element->calculate_enabled) {
				if (!is_array($value)) $value_array = array($value);
				else                   $value_array = $value;

				foreach ($value_array as $input_value) {
					$tmp_total = (float) $input_value;
					$tmp_total_out = "";

					// calculate value * factor
					if ($element->factor) {
						if ($value) {
							$tmp_total = (float) $input_value * $element->factor;
							$tmp_total_out = "{$value} * {$currency} " . number_format($element->factor, 2);
						}
					}
					// calculate user-defined operator
					if ($element->calculate && $element->calculate->operator != 0 && $element->calculate->target != 0) {
						// check if target element exists
						if (!$form_elements[$element->calculate->target]) {
							$tmp_total = 0;
							$tmp_total_out = __("No target found: ", "ezfc") . $element->calculate->target;
						}
						else if (!$form_elements[$element->calculate->target]->factor) {
							$tmp_total = 0;
							$tmp_total_out = __("No target factor found: ", "ezfc") . $element->calculate->target;
						}
						else {
							$form_factor = number_format($form_elements[$element->calculate->target]->factor, 2);

							switch ($element->operator) {
								case "add":
									$tmp_total = (float) $value + $form_elements[$element->calculate->target]->factor;
									$tmp_total_out = "{$value} + {$currency} {$form_factor}";
								break;

								case "subtract":
									$tmp_total = (float) $value - $form_elements[$element->calculate->target]->factor;
									$tmp_total_out = "{$value} - {$currency} {$form_factor}";
								break;

								case "multiply":
									$tmp_total = (float) $value * $form_elements[$element->calculate->target]->factor;
									$tmp_total_out = "{$value} * {$currency} {$form_factor}";
								break;

								case "divide":
									if ($form_elements[$element->calculate->target]->factor == 0) {
										$tmp_total = 0;
										$tmp_total_out = __("Cannot divide by target factor 0", "ezfc");
									}
									else {
										$tmp_total = (float) $value / $form_elements[$element->calculate->target]->factor;
										$tmp_total_out = "{$value} / {$currency} {$form_factor}";
									}
								break;
							}
						}
					}

					// add element value to total value
					$total += $tmp_total;

					// build string for output
					$value_out_str = !$tmp_total ? "-" : "$currency " . number_format($tmp_total, 2);
					if ($tmp_total_out) {
						$value_out_str = "{$tmp_total_out}<br>= {$value_out_str}";
					}

					$total_out[] = $value_out_str;
				}
			}

			$tr_bg = $i%2==0 ? "#fff" : "#efefef";

			$out[] = "<tr style='margin: 5px 0; background-color: {$tr_bg};'>";
			$out[] = "	<td style='vertical-align: top;'>{$element->label}</td>";
			$out[] = "	<td style='vertical-align: top;'>" . implode("<hr style='border: 0; border-bottom: #ccc 1px solid;' />", $value_out) . "</td>";
			$out[] = "	<td style='vertical-align: top; text-align: right;'>" . implode("<hr style='border: 0; border-bottom: #ccc 1px solid;' />", $total_out) . "</td>";
			$out[] = "</tr>";

			$i++;
		}

		$out[] = "<tr style='margin: 5px 0; background-color: #eee; border-top: #aaa 1px solid; font-weight: bold;'>";
		$out[] = "	<td colspan='2'>" . __("Total", "ezfc") . "</td>";
		$out[] = "	<td style='text-align: right;'>{$currency} " .  number_format($total, 2) . "</td>";
		$out[] = "</tr>";

		$out[] = "<tr><td colspan='3'>Powered by <a href='http://codecanyon.net/item/ez-form-calculator-wordpress-plugin/7595334?ref=keksdieb'>EZ Form Calculator</a></td></tr>";

		$out[] = "</table>";
		$out[] = "</body></html>";

		// insert into db
		$res = $this->wpdb->insert(
			$this->tables["submissions"],
			array(
				"f_id"    => $id,
				"data"    => json_encode($data),
				"content" => implode("\n", $out),
				"ip"      => $_SERVER["REMOTE_ADDR"],
				"ref_id"  => $ref_id
			),
			array(
				"%d",
				"%s",
				"%s",
				"%s",
				"%s"
			)
		);

		if (!$res) return $this->send_message("error", __("Submission failed.", "ezfc"));

		$admin_mail = trim($options["email_recipient"]->value);
		if (empty($admin_mail)) {
			return $this->send_message("success", __($options["success_text"]->value, "ezfc"));
		}

		// send mail
		$mail_content   = implode("\n", $out);
		$mail_headers   = array();
		$mail_headers[] = "Content-type: text/html";
		$mail_headers[] = "From: \"" . get_bloginfo("name") . "\"";
		
		if ($bcc_mail) $mail_headers[] = "Bcc: " . $bcc_mail;

		wp_mail(
			$admin_mail,
			__("Submission received", "ezfc"),
			$mail_content,
			$mail_headers
		);

		return $this->send_message("success", __($options["success_text"]->value, "ezfc"));
	}


	/**
		output
	**/
	function get_output($id, $raw=false) {
		$form = $this->form_get($id);
		if (!$form) return __("No form found (ID: {$id}).", "ezfc");

		$elements = $this->elements_get();
		$options  = $this->array_index_key($this->form_get_options($id), "name");

		require_once(plugin_dir_path(__FILE__)."lib/recaptcha-php-1.11/recaptchalib.php");
		$publickey = get_option("ezfc_captcha_public");

		// frontend output
		if (!$raw) {
			$form_elements = $this->form_elements_get($form->id);
		}
		// backend preview
		else {
			$form_elements = $raw;
		}

		// additional styles
		$css_label_width = get_option("ezfc_css_form_label_width");
		$css_label_width = empty($css_label_width) ? "" : "style='width: {$css_label_width}'";

		$form_class = $options["form_class"]->value;
		// reference id for file uploads
		$ref_id = uniqid();

		$html  = "<div class='ezfc-wrapper {$form_class}'>";
		$html .= "<div class='ezfc-message'></div>";
		$html .= "<form class='{$form_class} ezfc-form' name='ezfc-form[{$form->id}]' action='' data-id='{$form->id}' data-currency='{$options["currency"]->value}'>";

		// reference
		$html .= "<input type='hidden' name='id' value='{$id}'>";
		$html .= "<input type='hidden' name='ref_id' value='{$ref_id}'>";

		// price
		if ($options["show_price_position"]->value == 2 ||
			$options["show_price_position"]->value == 3) {
			$html .= "<div class='ezfc-element'>";
			$html .= "	<label {$css_label_width}>{$options["price_label"]->value}</label>";
			$html .= "	<div class='ezfc-price-wrapper'>";
			$html .= "		<span class='ezfc-price'>-</span>";
			$html .= "	</div>";
			$html .= "</div>";
		}

		$el_pre = "<div class='ezfc-element'>";
		$el_suf = "</div>";

		foreach ($form_elements as $i=>$element) {
			if (!$raw) {
				$data    = json_decode($element->data);
				$el_id   = "ezfc_element-{$element->id}";
				$el_name = "ezfc_element[{$element->id}]";
			}
			else {
				$element = json_decode(json_encode($element), FALSE);
				$data    = $element;
				$el_id   = "ezfc_element-{$i}";
				$el_name = "ezfc_element[{$i}]";
			}

			$el_label      = "";
			$el_text       = "";
			$required      = "";
			$required_char = "";
			if ($data->required == 1) {
				$required = "required";

				if ($options["show_required_char"]->value != 0) {
					$required_char = " <span class='ezfc-required-char'>*</span>";
				}
			}

			// trim labels
			$data->label = trim($data->label);

			// calculate values
			$calc_enabled = $data->calculate_enabled ? 1 : 0;
			$data_calculate = "data-calculate_operator='{$data->calculate->operator}' data-calculate_target='{$data->calculate->target}' data-calculate_enabled='{$calc_enabled}'";

			// element price
			$show_price = "";

			switch ($elements[$element->e_id]->type) {
				case "input":
				case "email":
				case "numbers":
					if ($options["show_element_price"]->value == 1 && $data->factor) {
						$show_price = " <span class='ezfc-element-price'>({$options["currency"]->value}{$data->factor})</span>";
					}

					$el_label .= "<label class='ezfc-label' for='{$el_id}' {$css_label_width}>" . __($data->label, "ezfc") . "{$required_char}</label>";
					$el_text .= "<input	class='{$data->class} ezfc-element ezfc-element-input' data-element='{$elements[$element->e_id]->type}' data-factor='{$data->factor}' {$data_calculate}	id='{$el_id}'name='{$el_name}' placeholder='{$data->placeholder}' type='text' value='{$data->value}' {$required} />{$show_price}";
				break;

				case "hidden":
					$el_text .= "<input class='{$data->class} ezfc-element ezfc-element-input-hidden' {$data_calculate} data-element='{$elements[$element->e_id]->type}' data-factor='{$data->factor}' id='{$el_id}' name='{$el_name}' type='hidden' value='{$data->value}' />";
				break;

				case "textfield":
					$el_label .= "<label class='ezfc-label' for='{$el_id}' {$css_label_width}>" . __($data->label, "ezfc") . "{$required_char}</label>";
					$el_text .= "<textarea class='{$data->class} ezfc-element ezfc-element-textarea' name='{$el_name}' id='{$el_id}' placeholder='{$data->placeholder}' data-element='{$elements[$element->e_id]->type}' {$required}>{$data->value}</textarea>";
				break;

				default: $el_text = $element->id; break;
			}

			if (!empty($data->label)) {
				$el_text = $el_label . $el_text;
			}

			$html .= $el_pre.$el_text.$el_suf;
		}

		// price
		if ($options["show_price_position"]->value == 1 ||
			$options["show_price_position"]->value == 3) {
			$html .= "<div class='ezfc-element'>";
			$html .= "	<label {$css_label_width}>{$options["price_label"]->value}</label>";
			$html .= "	<div class='ezfc-price-wrapper'>";
			$html .= "		<span class='ezfc-price'>-</span>";
			$html .= "	</div>";
			$html .= "</div>";
		}

		// submit
		if ($options["submission_enabled"]->value == 1) {
			$html .= "<div class='ezfc-element'>";
			$html .= "	<label {$css_label_width}></label>";
			$html .= "	<input class='btn ezfc-element ezfc-element-submit ezfc-submit' id='ezfc-submit-{$form->id}' type='submit' value='" . __("Submit", "ezfc") . "' />";
			$html .= "</div>";
		}

		$html .= "</form>";

		if ($options["show_required_char"]->value != 0) {
			$html .= "<div class='ezfc-required-notification'><span class='ezfc-required-char'>*</span> " . __("Required", "ezfc") . "</div>";
		}

		$html .= "<div class='ezfc-success-text' data-id='{$id}'>" . __($options["success_text"]->value, "ezfc") . "</div>";
		$html .= "</div>";

		return $html;
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

	function send_message($type, $msg, $id=0) {
		return array(
			$type => $msg,
			"id"  => $id
		);
	}
}