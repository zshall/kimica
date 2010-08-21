<?php
class Help extends SimpleExtension {
	
	public function onPageRequest($event) {
		if($event->page_matches("help")) {
			$section = html_escape(strtolower($event->get_arg(0)));
			$content = $this->get_help($section);
			$this->theme->display_content(ucfirst($section), $content);
		}
	}
	
	public function get_help($section){
		if(file_exists("ext/help/archive/".$section.".html")) {
			return file_get_contents("ext/help/archive/".$section.".html");
		}
		return "Error";
	}
}
?>