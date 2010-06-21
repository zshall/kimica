<?php
/*
 * Name: User Preferences
 * Author: Zach Hall <zach@sosguy.net>
 * Visibility: user, admin (extended)
 * Description: Allows the user to configure user-configurable settings to his or her taste.
 *				Cheap renaming hack of Shish's setup extension!!
 */

/* PrefSaveEvent {{{
 *
 * Sent when the setup screen's 'set' button has been
 * activated; new config options are in $_POST
 */
class PrefSaveEvent extends Event {
	var $prefs_setup;

	public function PrefSaveEvent($prefs_setup) {
		$this->prefs_setup = $prefs_setup;
	}
}
// }}}
/* SetupBuildingEvent {{{
 *
 * Sent when the setup page is ready to be added to
 */
class PrefBuildingEvent extends Event {
	var $panel;

	public function PrefBuildingEvent($panel) {
		$this->panel = $panel;
	}

	public function get_panel() {
		return $this->panel;
	}
}
// }}}
/* SetupPanel {{{
 *
 */
class PrefPanel {
	var $blocks = array();

	public function add_block($block) {
		$this->blocks[] = $block;
	}
}
// }}}
/* SetupBlock {{{
 *
 */
class PrefBlock extends Block {
	var $header;
	var $body;

	public function PrefBlock($title) {
		$this->header = $title;
		$this->section = "main";
		$this->position = 50;
	}

	public function add_label($text) {
		$this->body .= $text;
	}

	public function add_text_option($name, $label=null) {
		global $prefs_setup;
		$val = html_escape($prefs_setup->get_string($name));
		$val = htmlspecialchars_decode($val); // Prevent snowballing.
		if(!is_null($label)) {
			$this->body .= "<label for='$name'>$label</label>";
		}
		$this->body .= "<input type='text' id='$name' name='_userprefs_$name' value='$val'>\n";
		$this->body .= "<input type='hidden' name='_type_$name' value='string'>\n";
	}

	public function add_longtext_option($name, $label=null) {
		global $prefs_setup;
		$val = html_escape($prefs_setup->get_string($name));
		$val = htmlspecialchars_decode($val); // Prevent snowballing.
		if(!is_null($label)) {
			$this->body .= "<label for='$name'>$label</label>";
		}
		$this->body .= "<textarea rows='5' id='$name' name='_userprefs_$name'>$val</textarea>\n";
		$this->body .= "<!--<br><br><br><br>-->\n"; // setup page auto-layout counts <br> tags
		$this->body .= "<input type='hidden' name='_type_$name' value='string'>\n";
	}

	public function add_bool_option($name, $label=null) {
		global $prefs_setup;
		$checked = $prefs_setup->get_bool($name) ? " checked" : "";
		if(!is_null($label)) {
			$this->body .= "<label for='$name'>$label</label>";
		}
		$this->body .= "<input type='checkbox' id='$name' name='_userprefs_$name'$checked>\n";
		$this->body .= "<input type='hidden' name='_type_$name' value='bool'>\n";
	}

//	public function add_hidden_option($name, $label=null) {
//		global $prefs_setup;
//		$val = $prefs_setup->get_string($name);
//		$this->body .= "<input type='hidden' id='$name' name='$name' value='$val'>";
//	}

	public function add_int_option($name, $label=null, $disabled=null) {
		global $prefs_setup;
		$val = html_escape($prefs_setup->get_string($name));
		if(!is_null($label)) {
			$this->body .= "<label for='$name'>$label</label>";
		}
		$dt = "";
		if($disabled==true) { $dt = "readonly='true' disabled='true'"; }
		$this->body .= "<input type='text' id='$name' name='_userprefs_$name' value='$val' size='4' style='text-align: center;' $dt>\n";
		$this->body .= "<input type='hidden' name='_type_$name' value='int'>\n";
	}

	public function add_shorthand_int_option($name, $label=null) {
		global $prefs_setup;
		$val = to_shorthand_int($prefs_setup->get_string($name));
		if(!is_null($label)) {
			$this->body .= "<label for='$name'>$label</label>";
		}
		$this->body .= "<input type='text' id='$name' name='_userprefs_$name' value='$val' size='6' style='text-align: center;'>\n";
		$this->body .= "<input type='hidden' name='_type_$name' value='int'>\n";
	}

	public function add_choice_option($name, $options, $label=null) {
		global $prefs_setup;
		$current = $prefs_setup->get_string($name);

		if(!is_null($label)) {
			$this->body .= "<label for='$name'>$label</label>";
		}
		$html = "<select id='$name' name='_userprefs_$name'>";
		foreach($options as $optname => $optval) {
			if($optval == $current) $selected=" selected";
			else $selected="";
			$html .= "<option value='$optval'$selected>$optname</option>\n";
		}
		$html .= "</select>";
		$this->body .= "<input type='hidden' name='_type_$name' value='string'>\n";

		$this->body .= $html;
	}

	public function add_db_array($name, $options, $label=null) {
		global $prefs_setup;
		$current = $prefs_setup->get_string($name);

		if(!is_null($label)) {
			$this->body .= "<label for='$name'>$label</label>";
		}
		$html = "<select id='$name' name='_userprefs_$name'>";
		foreach($options as $optval) {
			if($optval == $current) $selected=" selected";
			else $selected="";
			$html .= "<option value='$optval'$selected>$optval</option>\n";
		}
		$html .= "</select>";
		$this->body .= "<input type='hidden' name='_type_$name' value='string'>\n";

		$this->body .= $html;
	}

	public function add_multichoice_option($name, $options, $label=null) {
		global $prefs_setup;
		$current = $prefs_setup->get_array($name);

		if(!is_null($label)) {
			$this->body .= "<label for='$name'>$label</label>";
		}
		$html = "<select id='$name' name='_userprefs_{$name}[]' multiple size='5'>";
		foreach($options as $optname => $optval) {
			if(in_array($optval, $current)) $selected=" selected";
			else $selected="";
			$html .= "<option value='$optval'$selected>$optname</option>\n";
		}
		$html .= "</select>";
		$this->body .= "<input type='hidden' name='_type_$name' value='array'>\n";
		$this->body .= "<!--<br><br><br><br>-->\n"; // setup page auto-layout counts <br> tags

		$this->body .= $html;
	}
}
// }}}

class UserPrefsSetup extends SimpleExtension {
/*	public function onInitExt($event) {
		global $prefs_setup;
		$prefs_setup->set_default_string("test_data", "Input something here");
		$prefs_setup->set_default_string("test_data2", "And here");
		$prefs_setup->set_default_bool("test_data3", true);
	}*/ // Uncomment for debugging.
	

	public function onPageRequest($event) {
		global $prefs_setup, $page, $user, $database;
		if($event->page_matches("preferences")) { // Ah-ha! Here's how we do it.
			if($event->get_arg(0) == NULL) {
				$display_user = User::by_name($user->name);
			} else {
				$display_user = User::by_name($event->get_arg(0)); 
			}
			
			if(is_null($display_user)) { $this->theme->display_error($page, ";_;", "Did you enter a proper username?"); } else {
				$GLOBALS['uid-preferences'] = int_escape($display_user->id); // Need this first.
				$user_id_preferences = $GLOBALS['uid-preferences'];
				if($user->id != $user_id_preferences && !$user->is_admin()) { $this->theme->display_error($page, ";_;", "Who do you think you are?"); }
				else {
				if($user->is_anonymous()) {
					$this->theme->display_permission_denied($page);
				} else {
						// The magic code:
						$prefs_setup = new DatabasePrefs($database, $user_id_preferences);
						if($event->get_arg(1) == "save") {
							send_event(new PrefSaveEvent($prefs_setup, $user_id_preferences));
							$prefs_setup->save_prefs(NULL, $user_id_preferences);
		
							$page->set_mode("redirect");
							$page->set_redirect(make_link("preferences/{$display_user->name}"));
						}
						else if($event->get_arg(1) == "advanced") {
							//$this->theme->display_advanced($page, $prefs_setup->values); //Uncomment for debugging.
							//The way I see it, regular users don't need advanced settings.
						}
						else {
							$panel = new PrefPanel();
							send_event(new PrefBuildingEvent($panel));
							$this->theme->display_page($page, $panel, $user_id_preferences, $display_user->name);
						}
					}
				}
			}
		}		
	}

/*	public function onPrefBuilding($event) { // To test
		$sb = new PrefBlock("Extension testing block");
		$sb->position = 0;
		$sb->add_text_option("test_data", "Data1: ");
		$sb->add_text_option("test_data2", "Data2: ");
		$sb->add_bool_option("test_data3", "Data3: ");
		$event->panel->add_block($sb);
	}*/

	public function onPrefSave($event) {
		global $prefs_setup;
		global $user;
		$userid = $GLOBALS['uid-preferences'];
		foreach($_POST as $_name => $junk) {
			if(substr($_name, 0, 6) == "_type_") {
				$name = substr($_name, 6);
				$type = $_POST["_type_$name"];
				$value = isset($_POST["_userprefs_$name"]) ? $_POST["_userprefs_$name"] : null;
				switch($type) {
					case "string": 
						$tfe = new TextFormattingEvent($value);
						send_event($tfe);
						$value = $tfe->formatted;
						
						$value = str_replace('\n\r', '<br>', $value);
						$value = str_replace('\r\n', '<br>', $value);
						$value = str_replace('\n', '<br>', $value);
						$value = str_replace('\r', '<br>', $value);
						
						$value = stripslashes($value);
						$prefs_setup->set_string($name, $value, $userid); 
						break;
					case "int":    $prefs_setup->set_int($name, $value, $userid);    break;
					case "bool":   $prefs_setup->set_bool($name, $value, $userid);   break;
					case "array":  $prefs_setup->set_array($name, $value, $userid);  break;
				}
			}
		}
		log_warning("userprefs", "Preferences saved for user #$userid");
	}

	public function onUserBlockBuilding($event) {
		global $user;
		$userid = $user->name;
			$event->add_link("Preferences", make_link("preferences/$userid"));
	}
}
?>
