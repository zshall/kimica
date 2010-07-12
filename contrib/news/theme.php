<?php

class NewsTheme extends Themelet {
	/*
	 * Show $text on the $page
	 */
	public function display_news(Page $page, $text) {
		$page->add_block(new Block("Note", $text, "left", 5));
	}
	public function display_news_portal(Page $page, $text) {
		$page->add_block(new Block("Note", $text, "main", 5));
	}
}
?>
