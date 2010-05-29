<?php

class SidebarAdsTheme extends Themelet {
	/*
	 * Build a page showing $image and some info about it
	 */
	public function display_ad(Page $page, $ad) {
		$page->add_block(new Block("Ads", $ad, "left", 100));
	}
}
?>
