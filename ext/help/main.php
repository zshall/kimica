<?php
class Help extends SimpleExtension {
	
	public function onPageRequest($event) {
		if($event->page_matches("help")) {
			$section = html_escape(strtolower($event->get_arg(0)));
			$this->theme->display_content(ucfirst($section), $this->sanitize($this->get_help($section)));
		}
	}
	
	public function get_help($section){
		if(file_exists("ext/help/archive/".$section.".html")) {
			return file_get_contents("ext/help/archive/".$section.".html");
		}
		else{
			return "Error";
		}
	}
	
	public function sanitize($body){
		$body = str_replace('\n\r', '<br>', $body);
		$body = str_replace('\r\n', '<br>', $body);
		$body = str_replace('\n', '<br>', $body);
		$body = str_replace('\r', '<br>', $body);
		
		return $body;
	}
}
?>