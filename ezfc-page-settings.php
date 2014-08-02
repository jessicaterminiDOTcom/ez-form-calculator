<?php

defined( 'ABSPATH' ) OR exit;

global $wpdb;

require_once("class.ezfc_backend.php");
$ezfc = new Ezfc_backend();

if ($_POST["submit"]) {
	$ezfc->update_options($_POST["opt"], $_POST["ezfc-overwrite"], $_POST["ezfc-manual-update"]);

	// additional options
	foreach ($_POST["alt_opt"] as $k=>$v) {
		update_option($k, $v);
	}

	$updated = 1;
}

// schedule settings
$settings = $ezfc->get_settings();

// other settings
$settings_alt = array(
	"price_format"         => array("desc" => "Price format", "desc_add" => "See <a href='http://numeraljs.com/' target='_blank'>numeraljs.com</a> for syntax documentation"),
	"jquery_ui"            => array("desc" => "Add default jQuery UI stylesheet", "desc_add" => "If your theme looks differently after installing this plugin, set this option to 'No' and see again. It may break due to the default jQuery UI stylesheet.", "type" => "yesno"),
	"css_form_label_width" => array("desc" => "CSS label width", "desc_add" => "Width of the labels. Default: 15em"),
	"captcha_public"       => array("desc" => "Recaptcha public key",  "desc_add" => ""),
	"captcha_private"      => array("desc" => "Recaptcha private key", "desc_add" => ""),
	"uninstall_keep_data"  => array("desc" => "Keep data after uninstall", "desc_add" => "The plugin will keep all plugin-related data in the databse when uninstalling. Only select 'Yes' if you want to upgrade the script without losing data.", "type" => "yesno")
);

$langs = array('ar'=>'Arabic','ar-ma'=>'Moroccan Arabic','bs'=>'Bosnian','bg'=>'Bulgarian','br'=>'Breton','ca'=>'Catalan','cy'=>'Welsh','cs'=>'Czech','cv'=>'Chuvash','da'=>'Danish','de'=>'German','el'=>'Greek','en'=>'English','en-au'=>'English (Australia)','en-ca'=>'English (Canada)','en-gb'=>'English (England)','eo'=>'Esperanto','es'=>'Spanish','et'=>'Estonian','eu'=>'Basque','fa'=>'Persian','fi'=>'Finnish','fo'=>'Farose','fr-ca'=>'French (Canada)','fr'=>'French','gl'=>'Galician','he'=>'Hebrew','hi'=>'Hindi','hr'=>'Croatian','hu'=>'Hungarian','hy-am'=>'Armenian','id'=>'Bahasa Indonesia','is'=>'Icelandic','it'=>'Italian','ja'=>'Japanese','ka'=>'Georgian','ko'=>'Korean','lv'=>'Latvian','lt'=>'Lithuanian','ml'=>'Malayalam','mr'=>'Marathi','ms-my'=>'Bahasa Malaysian','nb'=>'Norwegian','ne'=>'Nepalese','nl'=>'Dutch','nn'=>'Norwegian Nynorsk','pl'=>'Polish','pt-br'=>'Portuguese (Brazil)','pt'=>'Portuguese','ro'=>'Romanian','ru'=>'Russian','sk'=>'Slovak','sl'=>'Slovenian','sq'=>'Albanian','sv'=>'Swedish','th'=>'Thai','tl-ph'=>'Tagalog (Filipino)','tr'=>'Turkish','tzm-la'=>'Tamaziɣt','uk'=>'Ukrainian','uz'=>'Uzbek','zh-cn'=>'Chinese','zh-tw'=>'Chinese (Traditional)');

?>

<div class="ezfc wrap">
	<h2><?php echo __("Global settings", "ezfc"); ?></h2> 
	<p>ez Form Calculator v<?php echo ezfc_get_version(); ?></p>

	<?php if ($updated) { ?>
		<div id="message" class="updated"><?php echo __("Settings saved.", "ezfc"); ?></div>
	<?php } ?>

	<form method="POST" name="ezfc-form" class="ezfc-form" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
		<table class="form-table">
			<tr>

			    <?php

			    $out = array();
			    foreach ($settings as $s) {
			    	$add_class = empty($s->type) ? "" : "ezfc-settings-type-{$s->type}";
			    	$tmp_input = "<input type='text' class='regular-text {$add_class}' id='opt-{$s->name}' name='opt[{$s->id}]' value='{$s->value}' />";

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
				    		<label for='opt-{$s->name}'>" . __($s->description, "ezfc") . "</label>
				    	</th>
				    	<td>
				    		{$tmp_input}
				    		<p class='description'>" . __($s->description_long, "ezfc") . "</p>
				    	</td>
			    	";
			    }

			    foreach ($settings_alt as $name=>$s) {
			    	$tmp_opt = get_option("ezfc_{$name}");
			    	$tmp_out = "";

			    	$tmp_out .= "
				    	<th scope='row'>
				    		<label for='alt_opt-{$name}'>" . __($s["desc"], "ezfc") . "</label>
				    	</th>
				    	<td>";

				    $tmp_input = "<input type='text' class='regular-text' id='alt_opt-{$name}' name='alt_opt[ezfc_{$name}]' value='{$tmp_opt}' />";

				    if ($s["type"] == "yesno") {
	    				$selected_no = $selected_yes = "";

	    				if ($tmp_opt == 0) $selected_no  = " selected='selected'";
	    				else               $selected_yes = " selected='selected'";

	    				$tmp_input  = "<select id='alt_opt-{$name}' name='alt_opt[ezfc_{$name}]'>";
	    				$tmp_input .= "    <option value='0' {$selected_no}>" . __("No", "ezfc") . "</option>";
	    				$tmp_input .= "    <option value='1' {$selected_yes}>" . __("Yes", "ezfc") . "</option>";
	    				$tmp_input .= "</select>";
	    			}

				    $tmp_out .= $tmp_input;
				    $tmp_out .= "<p class='description'>" . __($s["desc_add"], "ezfc") . "</p>
				    	</td>";

				    $out[] = $tmp_out;
			    }

			    echo implode("</tr><tr>", $out);
				?>

			</tr>

			<!-- overwrite settings -->
			<tr>
				<th scope='row'>
					<label for="ezfc-overwrite">Overwrite settings</label>
		    	</th>
		    	<td>
		    		<input type="checkbox" name="ezfc-overwrite" id="ezfc-overwrite" value="1" /><br>
		    		<p class="description">Checking this option will overwrite <b>ALL</b> existing form settings!</p>
		    	</td>
		    </tr>

		    <!-- manual update -->
			<tr>
				<th scope='row'>
					<label for="ezfc-manual-update">Manual update</label>
		    	</th>
		    	<td>
		    		<input type="checkbox" name="ezfc-manual-update" id="ezfc-manual-update" value="1" /><br>
		    		<p class="description">Checking this option will perform certain database changes. <strong>Only check this if you manually updated the plugin via FTP.</strong></p>
		    	</td>
		    </tr>	
		</table>

		<!-- save -->
		<p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="<?php echo __("Save", "ezfc"); ?>" /></p>
	</form>
</div>

<script>
jQuery(function($) {
	$(".ezfc-form").on("submit", function() {
		// confirmation
		if ($("#ezfc-overwrite").prop("checked")) {
			if (!confirm("Really overwrite all schedule settings?")) return false;
		}

		// buttonset -> single value
		var day_values = [];
		$(".days_available:checked").each(function(v) {
			day_values.push($(this).val());
		});
		$("#opt-days_available").val(day_values.join(","));
	});
});
</script>