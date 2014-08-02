<?php

defined( 'ABSPATH' ) OR exit;

global $wpdb;

require_once("class.ezfc_backend.php");
$ezfc = new Ezfc_backend();

$elements    = $ezfc->elements_get();
$forms       = $ezfc->forms_get();
$settings    = $ezfc->get_settings();
$templates   = $ezfc->form_templates_get();

// elements -> js var
$elements_js = array();
foreach ($elements as $e) {
	$elements_js[$e->id] = $e;
}

wp_localize_script("ezfc-backend", "ezfc", array(
	"elements" => $elements_js
));

?>

<div class="ezfc wrap ezfc-wrapper">
	<?php echo "<h2>" . __("Overview", "ezfc") . " <span class='spinner'></span></h2>"; ?>

	<?php if (isset($updated)) { ?>
		<div id="message" class="updated"><?php echo __("Settings saved", "ezfc"); ?>.</div>
	<?php } ?>

	<div class="ezfc-error"></div>
	<div class="ezfc-message"></div>

	<div class="container-12">
		<div class="grid-2">
			<h3><?php echo __("Add form", "ezfc"); ?></h3>

			Template:
			<ul class="ezfc-template-list">
				<li>
					<select id="ezfc-form-template-id" name="ezfc-form-template-id">
						<option value="0"><?php echo __("Blank", "ezfc"); ?></option>

						<?php
						$out = "";
						foreach ($templates as $t) {
							$out .= "<option value='{$t->id}'>{$t->name}</option>";
						}
						echo $out;
						?>
					</select>
				</li>
				<li class="button" data-action="form_add"><i class='fa fa-fw fa-plus-square-o'></i> <?php echo __("Add by template", "ezfc"); ?></li>
				<li class="button" data-action="form_template_delete"><i class='fa fa-fw fa-times'></i> <?php echo __("Delete template", "ezfc"); ?></li>
			</ul>
		</div>

		<div class="ezfc-hidden grid-10 ezfc-inline-list ezfc-form-elements-actions">
			<h3><?php echo __("Actions", "ezfc"); ?></h3>

			<ul>
				<li id="ezfc-form-save" class="button button-primary" data-action="form_save"><i class='fa fa-fw fa-floppy-o'></i> <?php echo __("Update form", "ezfc"); ?></li>
				<li id="ezfc-form-show-options" class="button" data-action="form_show_options"><i class='fa fa-fw fa-cogs'></i> <?php echo __("Show options", "ezfc"); ?></li>
				<li id="ezfc-form-show-submissions" class="button" data-action="form_get_submissions"><i class='fa fa-fw fa-envelope'></i> <?php echo __("Show submissions", "ezfc"); ?></li>
				<li id="ezfc-form-delete" class="button" data-action="form_delete"><i class='fa fa-fw fa-times'></i> <?php echo __("Delete form", "ezfc"); ?></li>
				<li id="ezfc-form-duplicate" class="button" data-action="form_duplicate"><i class='fa fa-fw fa-files-o'></i> <?php echo __("Duplicate form", "ezfc"); ?></li>
				<li id="ezfc-form-save-template" class="button" data-action="form_save_template"><i class='fa fa-fw fa-star'></i> <?php echo __("Save as template", "ezfc"); ?></li>
				<!-- todo!
				<li id="ezfc-form-preview" class="button" data-action="form_preview"><i class='fa fa-fw fa-flask'></i> <?php echo __("Preview", "ezfc"); ?></li>
				-->
			</ul>
			<ul>
				<li class="button ezfc-toggle-show"><i class="fa fa-fw fa-caret-square-o-down"></i> Show all</li>
				<li class="button ezfc-toggle-hide"><i class="fa fa-fw fa-caret-square-o-up"></i> Hide all</li>
			</ul>
		</div>

		<div class="clear"></div>

		<div class="grid-2 ezfc-forms">
			<h3><?php echo __("Forms", "ezfc"); ?></h3>

			<ul class="ezfc-forms-list">
				<li class="button clone" data-action="form_get" data-selectgroup="forms"></li>

				<?php
				foreach ($forms as $f) {
					echo "
						<li class='button ezfc-form' data-id='{$f->id}' data-action='form_get' data-selectgroup='forms'>
							<i class='fa fa-fw fa-list-alt'></i> {$f->id} - <span class='ezfc-form-name'>{$f->name}</span>
						</li>
					";
				}
				?>
			</ul>
		</div>

		<div class="ezfc-hidden grid-5 ezfc-form-elements-container">
			<div class="ezfc-elements-show">
				<h3><?php echo __("Form elements", "ezfc"); ?></h3>

				<form id="form-elements" name="ezfc-form-elements" action="">
					<ul class="ezfc-form-elements">
					</ul>
				</form>
			</div>
		</div>

		<div class="ezfc-hidden grid-5 ezfc-form-options-wrapper">
			<h3><label for="ezfc-form-name">Name</label></h3>
			<input type="text" id="ezfc-form-name" name="ezfc-form-name" value="" />

			<div class="ezfc-elements-add">
				<h3><?php echo __("Add elements", "ezfc"); ?></h3>

				<ul class="ezfc-elements">
					<?php foreach ($elements as $e) {
						echo "<li class='button ezfc-element' data-action='form_element_add' data-id='{$e->id}'><i class='fa fa-fw {$e->icon}'></i> {$e->name}</li>
						";
					} ?>
				</ul>
			</div>

			<h3>Shortcode</h3>
			<p id="ezfc-shortcode">-</p>
		</div>

		<!-- submissions -->
		<div class="ezfc-hidden grid-8 ezfc-form-submissions">
		</div>

		<div class="clear"></div>

		<!-- todo: preview -->
		<div class="ezfc-hidden grid-12 ezfc-form-preview-container">
			<h3>Preview</h3>
			<div class="ezfc-form-preview"></div>
		</div>

		<!-- options modal dialog -->
		<div class="ezfc-options-dialog ezfc-dialog" title="Form options">
			<form id="form-options" name="ezfc-form-options" action="">
				<div id="ezfc-form-options">
					<table class="form-table">
						<tr>

							<?php
							// default options
							$out = array();
						    foreach ($settings as $s) {
						    	$add_class = empty($s->type) ? "" : "ezfc-settings-type-{$s->type}";
						    	$tmp_input = "<input type='text' class='regular-text {$add_class}' id='opt-{$s->id}' name='opt[{$s->id}]' value='{$s->value}' />";

						    	if (!empty($s->type)) {
						    		switch ($s->type) {
						    			case "price_position": {
						    				$type_options = array(
						    					1 => __("Below", "ezfc"),
						    					2 => __("Above", "ezfc"),
						    					3 => __("Below + above", "ezfc")
						    				);

						    				$tmp_input  = "<select class='{$add_class}' id='opt-{$s->id}' name='opt[{$s->id}]'>";
						    				foreach ($type_options as $v=>$desc) {
						    					$selected = "";
						    					if ($s->value == $v) $selected = "selected='selected'";

						    					$tmp_input .= "<option value='{$v}' {$selected}>" . $desc . "</option>";
						    				}

						    				$tmp_input .= "</select>";
						    			} break;
						    			
						    			case "yesno": {
						    				$selected_no = $selected_yes = "";

						    				if ($s->value == 0) $selected_no = " selected='selected'";
						    				else                $selected_yes = " selected='selected'";

						    				$tmp_input  = "<select class='{$add_class}' id='opt-{$s->id}' name='opt[{$s->id}]'>";
						    				$tmp_input .= "    <option value='0' {$selected_no}>" . __("No", "ezfc") . "</option>";
						    				$tmp_input .= "    <option value='1' {$selected_yes}>" . __("Yes", "ezfc") . "</option>";
						    				$tmp_input .= "</select>";
						    			} break;
						    		}
						    	}

						    	$out[] = "
							    	<th scope='row'>
							    		<label for='opt-{$s->id}'>" . __($s->description, "ezfc") . "</label>
							    	</th>
							    	<td>
							    		{$tmp_input}
							    		<p class='description'>" . __($s->description_long, "ezfc") . "</p>
							    	</td>
						    	";
						    }

						    echo implode("</tr><tr>", $out);
						    ?>

						</tr>
					</table>
				</div>

				<!-- placeholder for modal buttons -->
				<button class="button button-primary ezfc-option-save hidden" data-action="form_update_options" data-id=""><?php echo __("Update options", "ezfc"); ?></button>
			</form>
		</div>
	</div>
</div>