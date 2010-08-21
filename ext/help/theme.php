<?php
class HelpTheme extends Themelet {

	public function display_content($title, $body){
		global $page;

		if($body == "Error"){
			$title = "Error";
			$body = "There is no documentation to show.";
		}

		$page->set_title($title);
		$page->set_heading($title);
		$page->add_block(new Block($title, $body, "main", 0));
	}
	
}
?>