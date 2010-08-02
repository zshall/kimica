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
				 and of course start organising your files.</p>
				
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
				$page->add_block(new Block("Posts", $this->build_table($images, "#search=$query").$pagination, "main", 10));
			}
			else {
				$pagination = $this->build_paginator("post/list", null, $this->page_number, $this->total_pages);
				$page->add_block(new Block("Posts", $this->build_table($images, null).$pagination, "main", 10));
			}
		}
		else {
			$page->add_block(new Block("No Posts Found", "No posts were found to match the search criteria."));
		}
	}
	
	public function display_populars($images, $date) {
		global $page;
		
		$html = $this->build_table($images, null);
		
		if(!$images){
			$html = "There is no posts to show.";
		}

		$page->set_title("Popular Posts");
		$page->set_heading("Popular Posts");
		$page->add_block(new Block("Popular Posts: ".$date, $html, "main", 10));
	}
	
	public function display_popular_calendar($html){
		global $page;
		$page->add_block(new Block("Calendar", $html, "left", 10));
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
	
	/*
	 * Build a page showing $image and some info about it
	 */
	public function display_post(Image $image, $editor_parts) {
		global $page;
		
		$metatags = str_replace(" ", ", ", html_escape($image->get_tag_list()));

		$page->set_title("Image {$image->id}: ".html_escape($image->get_tag_list()));
		$page->add_header("<meta name=\"keywords\" content=\"$metatags\">");
		$page->set_heading(html_escape($image->get_tag_list()));
		$page->add_block(new Block("Navigation", $this->build_post_navigation($image), "left", 0));
		$page->add_block(new Block("Statistics", $this->build_stats($image), "left", 10));
		if($editor_parts){
			$page->add_block(new Block("Editor", $this->build_image_editor($image, $editor_parts), "main", 10));
		}
	}

	public function display_admin_block($parts) {
		global $page;
		if(count($parts) > 0) {
			$page->add_block(new Block("Post Controls", join("<br>", $parts), "left", 50));
		}
	}


	protected function build_pin(Image $image) {
		global $database;

		if(isset($_GET['search'])) {
			$search_terms = explode(' ', $_GET['search']);
			$query = "search=".url_escape($_GET['search']);
		}
		else {
			$search_terms = array();
			$query = null;
		}

		$h_prev = "<a id='prevlink' href='".make_link("post/prev/{$image->id}", $query)."'>Prev</a>";
		$h_index = "<a href='".make_link()."'>Index</a>";
		$h_next = "<a id='nextlink' href='".make_link("post/next/{$image->id}", $query)."'>Next</a>";
		$script = "
		<script><!--
		$(document).ready(function() {
			if(document.location.hash.length > 3) {
				query = document.location.hash.substring(1);
				a = document.getElementById(\"prevlink\");
				a.href = a.href + '?' + query;
				a = document.getElementById(\"nextlink\");
				a.href = a.href + '?' + query;
			}
		});
		//--></script>
			";

		return "$h_prev | $h_index | $h_next$script";
	}

	protected function build_post_navigation(Image $image) {
		$h_pin = $this->build_pin($image);
		$h_search = "
			<script><!--
			$(document).ready(function() {
				$(\"#search_input\").DefaultValue(\"Search\");
			});
			//--></script>
			<p><form action='".make_link()."' method='GET'>
				<input type='hidden' name='q' value='/post/list'>
				<input id='search_input' name='search' type='text'>
				<input type='submit' value='Find' style='display: none;'>
			</form>
			<div id='search_completions'></div>";

		return "$h_pin<br>$h_search";
	}

	private function build_stats($image) {
		$h_owner = html_escape($image->get_owner()->name);
		$h_ownerlink = "<a href='".make_link("user/$h_owner")."'>$h_owner</a>";
		$h_ip = html_escape($image->owner_ip);
		$h_date = autodate($image->posted);
		$h_filesize = to_shorthand_int($image->filesize);
		
		
		$votes = "";
		if(class_exists("Votes")){
		$votes = "<br>Score: ".$image->votes;
		}
		
		$rating = "";
		if(class_exists("Ratings")){
		$rating = "<br>Rating: ".Ratings::rating_to_human($image->rating);
		}

		$html = "
		Id: {$image->id}
		<br>Posted: $h_date
		<br>Poster: $h_ownerlink
		$votes
		$rating
		<br>Size: {$image->width}x{$image->height}
		<br>Filesize: $h_filesize
		";
		
		if(!is_null($image->source)) {
			$h_source = html_escape($image->source);
			if(substr($image->source, 0, 7) != "http://") {
				$h_source = "http://" . $h_source;
			}
			$html .= "<br>Source: <a href='$h_source' target='_blank'>Link</a>";
		}
		
		$h_link = $image->get_image_link();
		$html .= "<br>Download: <a href='$h_link'>Link</a>";
		
		return $html;
	}

	protected function build_image_editor(Image $image, $editor_parts) {
		if(count($editor_parts) == 0) return ($image->is_locked() ? "<br>[Image Locked]" : "");

		if(isset($_GET['search'])) {$h_query = "search=".url_escape($_GET['search']);}
		else {$h_query = "";}

		$html = "
				<form action='".make_link("post/set")."' method='POST'>
					<input type='hidden' name='image_id' value='{$image->id}'>
					<input type='hidden' name='query' value='$h_query'>
					<table style='width: 500px;'>
		";
		foreach($editor_parts as $part) {
			$html .= $part;
		}
		$html .= "
					</table>
					<input type='submit' value='Set'>
				</form>
		";
		return $html;
	}
	
	public function get_status_html(Image $image, $status) {
		global $page, $user;
		$locked = "";
		$approved = "";
		$pending = "";
		$deleted = "";
		if($status=="l"){
			$locked = "selected='selected'";
		}
		else if($status=="a"){
			$approved = "selected='selected'";
		}
		else if($status=="p"){
			$pending = "selected='selected'";
		}
		else if($status=="d"){
			$deleted = "selected='selected'";
		}
		$i_image_id = int_escape($image->id);
		$html = "<form action='".make_link("post/status")."' method='POST'>
				<input type='hidden' name='image_id' value='$i_image_id'>
				<select name='status'>
					<option value='l' $locked>Locked</option>
			   		<option value='a' $approved>Approved</option>
			   		<option value='p' $pending>Pending</option>
			   		<option value='d' $deleted>Deleted</option>
				</select> 
				<input type='submit' value='Change Status'>
				</form>
				";

		return $html;
	}
	
	public function get_tag_editor_html(Image $image) {
		$script = "
			<script type='text/javascript'>
			$().ready(function() {
				$('#tag_editor').autocomplete('".make_link("api/internal/tag_list/complete")."', {
					width: 320,
					max: 15,
					highlight: false,
					multiple: true,
					multipleSeparator: ' ',
					scroll: true,
					scrollHeight: 300,
					selectFirst: false
				});
			});
			</script>
		";
		$h_tags = html_escape($image->get_tag_list());
		return "
			<tr>
				<td width='50px'>Tags</td>
				<td width='300px'><input type='text' name='tag_edit__tags' value='$h_tags' id='tag_editor'></td>
			</tr>
			$script
		";
	}

	public function get_source_editor_html(Image $image) {
		$h_source = html_escape($image->get_source());
		return "<tr><td>Source</td><td><input type='text' name='tag_edit__source' value='$h_source'></td></tr>";
	}
}
?>