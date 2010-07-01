<?php
/**
 * Name: Test
 * Description: Anything that needs testing can be put here.
 */

class Test extends SimpleExtension {
	public function onPageRequest($event) {
		global $page;
		if($event->page_matches("test/redirect")) {
			$page->set_mode("redirect");
			$page->set_delay(10);
			$page->set_redirect_msg("You have reached the /test/redirect page!");
			$page->set_redirect("http://sosguy.net/");
		}
	}
}