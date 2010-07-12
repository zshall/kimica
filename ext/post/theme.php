<?php

class PostTheme extends Themelet {
	public function set_page($page_number, $total_pages, $search_terms) {
		$this->page_number = $page_number;
		$this->total_pages = $total_pages;
		$this->search_terms = $search_terms;
	}

	public function display_intro(Page $page) {
		$text = "<div style='text-align: left;'>
				 <p>Once logged in you can play with the settings, install extra features,
				 and of course start organising your images :-)</p>
				
				 <p>This message will go away once your first image is uploaded~</p>
				 </div>";

		$page->set_title("Welcome to Kimica ".VERSION);
		$page->set_heading("Welcome to Kimica");
		$page->add_block(new Block("Installation Succeeded!", $text, "main", 0));
	}

	public function display_page($images) {
		global $page, $config;

		if(count($this->search_terms) == 0) {
			$query = null;
			$page_title = $config->get_string('title');
		}
		else {
			$search_string = implode(' ', $this->search_terms);
			$query = url_escape($search_string);
			$page_title = html_escape($search_string);
			if(count($images) > 0) {
				$page->set_subheading("Page {$this->page_number} / {$this->total_pages}");
			}
		}
		if($this->page_number > 1 || count($this->search_terms) > 0) {
			// $page_title .= " / $page_number";
		}

		$nav = $this->build_navigation($this->page_number, $this->total_pages, $this->search_terms);
		$page->set_title($page_title);
		$page->set_heading($page_title);
		$page->add_block(new Block("Navigation", $nav, "left", 0));
		if(count($images) > 0) {
			if($query) {
				//if($total_pages == 0) $total_pages = 1;
				$pagination = $this->build_paginator("post/list/$query", null, $this->page_number, $this->total_pages);
				$page->add_block(new Block("Images", $this->build_table($images, "#search=$query").$pagination, "main", 10));
			}
			else {
				$pagination = $this->build_paginator("post/list", null, $this->page_number, $this->total_pages);
				$page->add_block(new Block("Images", $this->build_table($images, null).$pagination, "main", 10));
			}
		}
		else {
			$page->add_block(new Block("No Images Found", "No images were found to match the search criteria"));
		}
	}
	
	public function display_populars($images, $date) {
		global $page;

		$page->set_title("Popular Images");
		$page->set_heading("Popular Images");
		$page->add_block(new Block("Popular Images: ".$date, $this->build_table($images, null), "main", 10));
	}
	
	public function display_recent_posts($posts){
		global $page;
		
		if(!empty($posts)){
			$page->add_block(new Block("Recent Posts", $this->build_table($posts, null), "main", 30));
		}
	}

	public function display_random_posts($posts){
		global $page;
		
		if(!empty($posts)){
			$page->add_block(new Block("Random Posts", $this->build_table($posts, null), "main", 30));
		}
	}

	protected function build_navigation($page_number, $total_pages, $search_terms) {
		$prev = $page_number - 1;
		$next = $page_number + 1;

		$u_tags = url_escape(implode(" ", $search_terms));
		$query = empty($u_tags) ? "" : "/$u_tags";


		$h_prev = ($page_number <= 1) ? "Prev" : "<a href='".make_link("post/list$query/$prev")."'>Prev</a>";
		$h_index = "<a href='".make_link()."'>Index</a>";
		$h_next = ($page_number >= $total_pages) ? "Next" : "<a href='".make_link("post/list$query/$next")."'>Next</a>";

		$h_search_string = html_escape(implode(" ", $search_terms));
		$h_search_link = make_link();
		$h_search = "
			<script><!--
			$(document).ready(function() {
				$('#search_input').DefaultValue('Search');
				$('#search_input').autocomplete('".make_link("api/internal/tag_list/complete")."', {
					max: 15,
					multiple: true,
					multipleSeparator: ' ',
					scroll: true,
					scrollHeight: 300,
					selectFirst: false
				});
			});
			//--></script>
			<p><form action='$h_search_link' method='GET'>
				<input id='search_input' name='search' type='text'
						value='$h_search_string' autocomplete='off' />
				<input type='hidden' name='q' value='/post/list'>
				<input type='submit' value='Find' style='display: none;' />
			</form>
			<div id='search_completions'></div>";

		return "$h_prev | $h_index | $h_next<br>$h_search";
	}
}
?>