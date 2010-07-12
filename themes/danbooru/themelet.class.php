<?php

class Themelet {
	public function display_error(Page $page, $title, $message) {
		$page->set_title($title);
		$page->set_heading($title);
		$page->add_block(new NavBlock());
		$page->add_block(new Block("Error", $message));
	}


	public function display_permission_denied(Page $page) {
		header("HTTP/1.0 403 Permission Denied");
		$this->display_error($page, "Permission Denied", "You do not have permission to access this page");
	}


	protected function build_table($images, $query) {
		global $config;
		$columns = floor(100 / $config->get_int('index_width'));
		$table = "<ul class='thumbblock'>";
		foreach($images as $image) {
			$table .= "<li class=\"thumb\" style='width: {$columns}%;'>" . $this->build_thumb_html($image, $query) . "</li>";
		}
		$table .= "</ul>";
		return $table;
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
		$style = "display:inline-block; height: {$tsize[1]}px; width: {$tsize[0]}px;";
		return "<a href='$h_view_link' style='$style'>
					<img id='thumb_$i_id' title='$h_tip' alt='$h_tip' style='height: {$tsize[1]}px; width: {$tsize[0]}px;' src='$h_thumb_link'>
				</a>";
	}


	/**
	 * Add a generic paginator
	 */
	public function build_paginator($base_url, $query, $current_page, $total_pages) {
		$next = $current_page + 1;
		$prev = $current_page - 1;
		$rand = rand(1, $total_pages);

		$at_start = ($current_page <= 3 || $total_pages <= 3);
		$at_end = ($current_page >= $total_pages -2);

		$first_html  = $at_start ? "" : $this->gen_page_link($base_url, $query, 1,            "1");
		$prev_html   = $at_start ? "" : $this->gen_page_link($base_url, $query, $prev,        "&lt;&lt;");
		$next_html   = $at_end   ? "" : $this->gen_page_link($base_url, $query, $next,        "&gt;&gt;");
		$last_html   = $at_end   ? "" : $this->gen_page_link($base_url, $query, $total_pages, "$total_pages");

		$start = $current_page-2 > 1 ? $current_page-2 : 1;
		$end   = $current_page+2 <= $total_pages ? $current_page+2 : $total_pages;

		$pages = array();
		foreach(range($start, $end) as $i) {
			$pages[] = $this->gen_page_link_block($base_url, $query, $i, $current_page, $i);
		}
		$pages_html = implode(" ", $pages);

		if(strlen($first_html) > 0) $pdots = "...";
		else $pdots = "";

		if(strlen($last_html) > 0) $ndots = "...";
		else $ndots = "";

		if($total_pages > 0){
			return "<div id='paginator'>$prev_html $first_html $pdots $pages_html $ndots $last_html $next_html</div>";
		}
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
}
?>
