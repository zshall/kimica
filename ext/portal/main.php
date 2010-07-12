<?php
/**
* Name: Community Portal
* Author: Zach Hall <zach@sosguy.net>
* Link: http://seemslegit.com
* License: GPLv2
* Description: A community portal system.
*/

class CommunityPortal extends SimpleExtension {
	public function onPageRequest($event) {
		global $page;
		if($event->page_matches("portal")) {
//			$mods = new PortalPage();
//			send_event(new PortalBuildingEvent($mods));
			send_event(new PortalBuildingEvent());
//			$this->theme->display_page($page, $mods);
		}
	}
	
	public function onPortalBuilding($event) {
/*		// a test module:
		$pm = new PortalMod("Ice Cream War");
		$pm->set_movable(false);
		$pm->set_collapsable(true);
		$pm->set_section("main");
		$pm->set_body("Everyone likes vanilla!");
		$event->mods->add_mod($pm);
		
		$pm = new PortalMod("Mod2");
		$pm->set_section("left");
		$event->mods->add_mod($pm);
		
		$pm = new PortalMod("Mod3");
		$pm->set_section("right");
		$event->mods->add_mod($pm);
*/	}
}

class PortalBuildingEvent extends Event {
/*	var $mods;

	public function PortalBuildingEvent($mods) {
		$this->mods = $mods;
	}
*/}

/*class PortalPage {
	// the collection of all modules
	var $mods = array();

	public function add_mod($mod) {
		$this->mods[] = $mod;
	}
}
*/
/*class PortalMod extends Block {
	var $header;
	var $body;
	var $more_link;
	var $movable;
	var $collapsable;
	var $closable;
	
	public function PortalMod($title) {
		$this->header = $title;
		$this->section = "main";
		$this->position = 50;
		$this->body = "empty";
		$this->more_link = "#";
		$this->movable = true;
		$this->collapsible = true;
		$this->removable = true;
	}
	
	public function set_body($string) {
		$this->body = $string;
	}
	
	public function set_section($string) {
		$this->section = $string;
	}
	
	public function set_more($string) {
		$this->more_link = $string;
	}
	
	public function set_movable($bool) {
		$this->movable = $bool;
	}
	
	public function set_collapsable($bool) {
		$this->collapsable = $bool;
	}
	
	public function set_closable($bool) {
		$this->removable = $bool;
	}	
}
*/?>