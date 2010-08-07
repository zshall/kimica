<?php

class CustomIndexTheme extends IndexTheme {
	public function display_page($page, $images) {
		global $config;

		if(count($this->search_terms) == 0) {
			$query = null;
			$page_title = $config->get_string('site_title');
		}
		else {
			$search_string = implode(' ', $this->search_terms);
			$query = url_escape($search_string);
			$page_title = html_escape($search_string);
		}
		$qp = _get_query_parts();
		if($qp[1] == "list_raw") { $raw = true; } else { $raw = false; }
		$nav = $this->build_navigation($this->page_number, $this->total_pages, $this->search_terms);
		$page->set_mode("data");
		if(count($images) > 0) {
			if($query) {
				$page->set_data($this->build_table($images, "search=$query", $this->page_number, $this->total_pages, $raw));
				//$this->display_paginator($page, "post/list/$query", null, $this->page_number, $this->total_pages);
			}
			else {
				$page->set_data($this->build_table($images, null, $this->page_number, $this->total_pages, $raw));
				//$this->display_paginator($page, "post/list", null, $this->page_number, $this->total_pages);
			}
		}
		else {
			$page->set_data("No more images or 0 results found.");
		}
	}


	protected function build_navigation($page_number, $total_pages, $search_terms) {
		$h_search_string = count($search_terms) == 0 ? "" : html_escape(implode(" ", $search_terms));
		$h_search_link = make_link();
		$h_search = "
			<p><form action='$h_search_link' method='GET'>
				<input name='search' type='text'
						value='$h_search_string' autocomplete='off' />
				<input type='hidden' name='q' value='/post/list'>
				<input type='submit' value='Find' style='display: none;' />
			</form>
			<div id='search_completions'></div>";

		return $h_search;
	}

	protected function build_table($images, $query, $page_num, $total_pages, $raw) {
		if($raw == false) {
			$table = "<ul id='listing' selected='true'>";
		} else $table = "";
		foreach($images as $image) {
			$table .= "<li>" . $this->build_thumb_html($image, $query) . "</li>";
		}
		if($page_num < $total_pages) {
			$page_num++;
			$table .= "<li><a href='/post/list_raw/$page_num' target='_replace'>Continue browsing...</a></li>";
		}
		if($raw == false) {
			$table .= "</ul>";
		}
		return $table;
	}
}
?>
