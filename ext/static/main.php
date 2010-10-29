<?php
class Static extends SimpleExtension {

	public function onInitExt($event) {
		global $config;
		
		if($config->get_int("ext_static_version") < 1) {		
			$config->set_int("ext_static_version", 1);
		}
	}
	
	public function onPageRequest($event) {
		if($event->page_matches("static")) {
		}
	}

}
?>