<?php
class HelpTheme extends Themelet {

	public function display_content($title, $body){
		global $page;

		if($body == "Error"){
			$title = "Error";
			$body = "There is no documentation to show.";
		}

		$page->set_title("Help: ".$title);
		$page->set_heading("Help: ".$title);
		$page->add_block(new Block("Help: ".$title, $body, "main", 0));
	}
	
}
?>