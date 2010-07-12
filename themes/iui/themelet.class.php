<?php

class Themelet {
	/**
	 * Generic error message display
	 */
	public function display_error(Page $page, $title, $message) {
		$page->set_title($title);
		$page->set_heading($title);
		$page->add_block(new NavBlock());
		$page->add_block(new Block("Error", $message));
	}


	/**
	 * A specific, common error message
	 */
	public function display_permission_denied(Page $page) {
		header("HTTP/1.0 403 Permission Denied");
		$this->display_error($page, "Permission Denied", "You do not have permission to access this page");
	}


	/**
	 * Generic thumbnail code; returns HTML rather than adding
	 * a block since thumbs tend to go inside blocks...
	 */
	public function build_thumb_html(Image $image, $query=null) {
		global $config;
		$i_id = int_escape($image->id);
		$h_view_link = make_link("post/view/$i_id", $query);
		$h_tip = html_escape($image->get_tooltip());
		$h_thumb_link = $image->get_thumb_link();
		$tsize = get_thumbnail_size($image->width, $image->height);
		return "<a href='$h_view_link'><img id='$i_id' title='$h_tip' alt='$h_tip' style='height: {$tsize[1]}px; width: {$tsize[0]}px;' src='$h_thumb_link' /></a>";
	}


	/**
	 * Add a generic paginator
	 */
	public function display_paginator(Page $page, $base, $query, $page_number, $total_pages) {
		if($total_pages == 0) $total_pages = 1;
		$body = $this->build_paginator($page_number, $total_pages, $base, $query);
		$page->add_block(new Block(null, $body, "main", 90));
	}

	private function gen_page_link($base_url, $query, $page, $name) {
		$link = make_link("$base_url/$page", $query);
	    return "<a href='$link'>$name</a>";
	}
	
	private function gen_page_link_block($base_url, $query, $page, $current_page, $name) {
		$paginator = "";
	    if($page == $current_page) $paginator .= "<b>";
	    $paginator .= $this->gen_page_link($base_url, $query, $page, $name);
	    if($page == $current_page) $paginator .= "</b>";
	    return $paginator;
	}
					
	private function build_paginator($current_page, $total_pages, $base_url, $query) {
		$next = $current_page + 1;
		$prev = $current_page - 1;
		$rand = rand(1, $total_pages);

		$at_start = ($current_page <= 1 || $total_pages <= 1);
		$at_end = ($current_page >= $total_pages);

		$first_html  = $at_start ? "First" : $this->gen_page_link($base_url, $query, 1,            "First");
		$prev_html   = $at_start ? "Prev"  : $this->gen_page_link($base_url, $query, $prev,        "Prev");
		$random_html =                       $this->gen_page_link($base_url, $query, $rand,        "Random");
		$next_html   = $at_end   ? "Next"  : $this->gen_page_link($base_url, $query, $next,        "Next");
		$last_html   = $at_end   ? "Last"  : $this->gen_page_link($base_url, $query, $total_pages, "Last");

		$start = $current_page-5 > 1 ? $current_page-5 : 1;
		$end = $start+10 < $total_pages ? $start+10 : $total_pages;

		$pages = array();
		foreach(range($start, $end) as $i) {
			$pages[] = $this->gen_page_link_block($base_url, $query, $i, $current_page, $i);
		}
		$pages_html = implode(" | ", $pages);

		return "<p class='paginator'>$first_html | $prev_html | $random_html | $next_html | $last_html".
				"<br>&lt;&lt; $pages_html &gt;&gt;</p>";
	}
}

/**
* Name: iNterface
* Author: Zach Hall <zach@sosguy.net>
* Link: http://seemslegit.com
* License: GPLv2
* Description: The frontend for the iui theme.
*/

class iui_interface extends SimpleExtension {
	public function onPageRequest($event) {
		// general
		if($event->page_matches("i")) {
			global $page;
			$page->add_block(new Block("iNterface", $this->get_interface(), "main", 10));
		}
		if($event->page_matches("i/o/o")) {
			global $page;
			setcookie("shimmie_mobile_optout", true, NULL, "/");
			$page->set_mode("redirect");
			$page->set_redirect(make_link(""));
		}
		// specific functions
		if($event->page_matches("ir/t/a")) {
			global $database;
			global $config;
			global $page;
			
			$tags_min = $config->get_int('tags_min');
			$result = $database->execute(
					"SELECT tag,count FROM tags WHERE count >= ? ORDER BY tag",
					array($tags_min));
			$tag_data = $result->GetArray();
	
			$html1 = "<ul id='tag_list_alpha' selected='true'>";
			$html2 = "";
			$grouplist = "";
			$lastLetter = "";
			foreach($tag_data as $row) {
				$h_tag = html_escape($row['tag']);
				$count = $row['count'];
				if($lastLetter != strtolower(substr($h_tag, 0, 1))) {
					$lastLetter = strtolower(substr($h_tag, 0, 1));
					$html2 .= "</ul><ul id='$lastLetter'><li class='group'>$lastLetter</li>";
					$grouplist .= "<li><a href='#$lastLetter'>$lastLetter</a></li>";
				}
				$link = $this->tag_link($row['tag']);
				$html2 .= "<li><a href='$link'>$h_tag&nbsp;($count)</a></li>\n";
			}
			$grouplist .= "</ul>";
			$html2 .= "</ul>";
			$html = $html1 . $grouplist . $html2;
			$page->set_mode("data");
			$page->set_data($html);
		}
		if($event->page_matches("ir/t/p")) {
			global $database;
			global $config;
			global $page;
			
			$tags_min = $config->get_int('tags_min');
			$result = $database->execute(
					"SELECT tag,count,FLOOR(LOG(count)) AS scaled FROM tags WHERE count >= ? ORDER BY count DESC, tag ASC",
					array($tags_min));
			$tag_data = $result->GetArray();
	
			$html1 = "<ul id='tag_list_popular' selected='true'>
			<li>Results grouped by log<sub>e</sub>(n)</li>";
			$html2 = "";
			$grouplist = "";
			$lastLog = "";
			foreach($tag_data as $row) {
				$h_tag = html_escape($row['tag']);
				$count = $row['count'];
				$scaled = $row['scaled'];
				if($lastLog != $scaled) {
					$lastLog = $scaled;
					$html2 .= "</ul><ul id='$lastLog'><li class='group'>$lastLog</li>";
					$grouplist .= "<li><a href='#$lastLog'>$lastLog</a></li>";
				}
				$link = $this->tag_link($row['tag']);
				$html2 .= "<li><a href='$link'>$h_tag&nbsp;($count)</a></li>\n";
			}
			$grouplist .= "</ul>";
			$html2 .= "</ul>";
			$html = $html1 . $grouplist . $html2;
			$page->set_mode("data");
			$page->set_data($html);
		}
	}
		private function tag_link($tag) {
			$u_tag = url_escape($tag);
			return make_link("post/list/$u_tag/1");
		}
	private function get_interface() {
		global $database, $page, $config;
		$debug = get_debug_info();
		$contact_link = $config->get_string('contact_link');
		$contact = empty($contact_link) ? "" : "<br><a class='whiteButton iui-cache-update-button' type='button' target='_blank' href='$contact_link'>Contact</a>";
		$stat = array();
		$stat['images']   = $database->db->GetOne("SELECT COUNT(*) FROM images");
		$stat['comments'] = $database->db->GetOne("SELECT COUNT(*) FROM comments");
		$stat['users']    = $database->db->GetOne("SELECT COUNT(*) FROM users");
		$stat['tags']     = $database->db->GetOne("SELECT COUNT(*) FROM tags");
		$stat['image_tags'] = $database->db->GetOne("SELECT COUNT(*) FROM image_tags");
		$sitename = $config->get_string("title");
		$html = "";
		$html .= "
			<ul id='home' title='$sitename' selected='true'>
				<li><a href='#images'>Images</a></li>
				<li><a href='#tags'>Tags</a></li>
				<li><a href='#comments'>Comments</a></li>
				<li><a href='#account'>Account</a></li>
				<li><a href='#stats'>Stats / About</a></li>
				<li><span style='color:red'>Full Site</span> (Locked for testing)</li>
			</ul>
		
			<ul id='images' title='Images'>
				<li><a href='/post/list'>Newest</a></li>
				<li><a href='/featured_image/view'>Featured</a></li>
				<li><a href='/random_image/view'>Random</a></li>
			</ul>
		
			<ul id='tags' title='Tags'>
				<li><a href='/ir/t/a'>List</a></li>
				<li><a href='/ir/t/p'>Popular</a></li>
			</ul>
		
		    <div id='stats' class='panel' title='Stats / About'>
				<h2>Shimmie 2.3</h2>
				<fieldset> 
					<div class='row'> 
						<label>Images</label>
						<span>{$stat['images']}</span> 
					</div>
					<div class='row'> 
						<label>Comments</label>
						<span>{$stat['comments']}</span> 
					</div>
					<div class='row'> 
						<label>Users</label>
						<span>{$stat['users']}</span> 
					</div>
					<div class='row'> 
						<label>Tags</label>
						<span>{$stat['tags']}</span> 
					</div>
					<div class='row'> 
						<label>Image Tags</label>
						<span>{$stat['image_tags']}</span> 
					</div>
				</fieldset>
				<h2>Copyright Info</h2>
				<fieldset> 
					<div class='row'> 
						<p>
						Images &copy; their respective owners,
						<a href='http://code.shishnet.org/shimmie2/'>Shimmie</a> &copy;
						<a href='http://www.shishnet.org/'>Shish</a> &amp; Co 2007-2010,
						based on the Danbooru concept.
						</p> 
					</div>
				</fieldset>
				$contact
				$debug
			</div>
			<form id='optout' class='dialog' action='/ir/o/o'>
				<fieldset>
					<h1>Leaving Mobile Site</h1>
					<a class='button leftButton' type='cancel'>Cancel</a>
					<a class='button blueButton' href='iphone/optout' target='_blank'>OK</a>
					<label>You can return to the mobile site by rebooting your browser. (Clearing cookies)</label>
				</fieldset>
			</form>
	<form id='searchForm' class='dialog' action='/post/list' method='GET'>
        <fieldset>
            <h1>Image Search</h1>
            <a class='button leftButton' type='cancel'>Cancel</a>
            <a class='button blueButton' type='submit'>Search</a>
            
            <label>Tags:</label>
            <input id='search_input' name='search' type='text'
						value='' autocomplete='off' />
			<input type='hidden' name='q' value='/post/list'>  
        </fieldset>
    </form>
		";
		return $html;
	}
}
?>
