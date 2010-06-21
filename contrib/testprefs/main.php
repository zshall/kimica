<?php
/*
 * Name: Test Prefs App
 * Author: Zach Hall <zach@sosguy.net>
 * License: GPLv2
 * Description: "Custom Greeting" and "Ice Cream War"... the hello world of Preferences
 *				This could be good for displaying text only to anonymous users if I
 *				can get it so the admin can edit preferences for user_id 1.
 *
 *				Also adds a bunch of other unnecessary controls
 *				to test the functionality.
 */
 
class TestPrefs extends SimpleExtension {
	// Kinda like the news extension
	public function onPostListBuilding($event) {
		global $prefs, $page;
		if(strlen($prefs->get_string("testprefs_text")) > 0 && $prefs->get_bool("testprefs_display") == TRUE) {
			$this->theme->greeting($page, $prefs->get_string("testprefs_text"));
		}
	}
	// Kinda like the config interface
	public function onPrefBuilding($event) {
		$pb = new PrefBlock("Greeting");
		$pb->add_bool_option("testprefs_display", "Greeting off / on");
		$pb->add_longtext_option("testprefs_text","<br />Write a custom greeting for yourself to see in the sidebar!");
		$event->panel->add_block($pb);
		
		// Testing other options
		$pb = new PrefBlock("Random Questionnaire");
		$pb->add_int_option("testprefs_age", "How old are you in years?");
		$pb->add_choice_option("testprefs_icecream", 
			array('Vanilla'=>'vanilla',
			'Chocolate'=>'chocolate',
			'Strawberry'=>'strawberry',
			'No preference'=>'none'), "<br>Favorite type of ice cream?");
		$event->panel->add_block($pb);
	}
	// One way this data could be used:
	public function onPageRequest($event) {
		global $page;
		if($event->page_matches("icecream")) {
			$body = $this->page_body();
			$this->theme->display_page($page, $body);
		}
	}
	public function onUserBlockBuilding($event) {
		global $user;
			$event->add_link("Ice Cream War", make_link("icecream"));
	}
	// Show a chart of who liked what ice cream better.
	private function page_body() {
		global $database;
		global $config;
		$base_href = $config->get_string('base_href');
		$data_href = get_base_href();
		
		$vanilla = ceil($database->db->GetOne('SELECT COUNT(*) FROM `user_prefs` WHERE (`name` = "testprefs_icecream" AND `value` = "vanilla")'));
		$v = "$vanilla";
		
		$chocolate = ceil($database->db->GetOne('SELECT COUNT(*) FROM `user_prefs` WHERE (`name` = "testprefs_icecream" AND `value` = "chocolate")'));
		$c = "$chocolate";
		
		$strawberry = ceil($database->db->GetOne('SELECT COUNT(*) FROM `user_prefs` WHERE (`name` = "testprefs_icecream" AND `value` = "strawberry")'));
		$s = "$strawberry";
		
		$none = ceil($database->db->GetOne('SELECT COUNT(*) FROM `user_prefs` WHERE (`name` = "testprefs_icecream" AND `value` = "none")'));
		$n = "$none";
		
		$body = "<h1>Results:</h1>";
		$body .= "<p>Who liked what flavor of ice cream the best?</p>";
		$body .= "<img src='".$data_href."/ext/testprefs/piechart.php?data=$v*$c*$s*$n&label=Vanilla*Chocolate*Strawberry*No%20Preference' />";
		return $body;
	}
}
?>