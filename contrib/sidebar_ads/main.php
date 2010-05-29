<?php
/*
 * Name: Sidebar Ads
 * Author: Shish <webmaster@shishnet.org>
 * License: GPLv2
 * Description: Advertisement on site
 * Documentation:
 *  This extension sets the "description" meta tag in the header
 *  of pages so that search engines can pick it up
 */
class SidebarAds extends SimpleExtension {
	public function onPageRequest(PageRequestEvent $event) {
		global $config, $page, $user;
		
		$ads = "";
		
		if($user->is_logged_in()){
			$ads = $config->get_string("ads_sidebar_adult");
		}else{
			$ads = $config->get_string("ads_sidebar_clean");
		}
		
		if($ads <> ""){
			$this->theme->display_ad($page, $ads);
		}
	}

	public function onSetupBuilding(SetupBuildingEvent $event) {
		$sb = new SetupBlock("Ads");
		$sb->add_longtext_option("ads_sidebar_adult", "Sidebar Adult: ");
		$sb->add_longtext_option("ads_sidebar_clean", "Sidebar Clean: ");
		$event->panel->add_block($sb);
	}
}
?>
