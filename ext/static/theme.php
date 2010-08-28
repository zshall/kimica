<?php
class StaticTheme extends Themelet {

	public function display_content($title, $content){
		$page->set_title($title);
		$page->set_heading($title);
		$page->add_block(new Block($title, $content, "main", 0));
	}
	
}
?>