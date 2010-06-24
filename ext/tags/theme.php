<?php

class TagListTheme extends Themelet {
	var $heading = "";
	var $list = "";

	public function set_heading($text) {
		$this->heading = $text;
	}

	public function set_tag_list($list) {
		$this->list = $list;
	}
	
	public function set_banned_list(Page $page, $banned) {		
		$page->set_title("Banned Tags");
		$page->set_heading("Banned Tags");
		$page->add_block(new Block("Banned Tags", $banned, "main", 0));
	}

	public function set_navigation($nav) {
		$this->navigation = $nav;
	}

	public function display_page(Page $page) {
		$page->set_title("Tag List");
		$page->set_heading($this->heading);
		$page->add_block(new Block("Tags", $this->list));
	}
	
	public function display_navigation(Page $page) {
		$page->add_block(new Block("Navigation", $this->navigation, "left", 0));
	}

	// =======================================================================

	/*
	 * $tag_infos = array(
	 *                 array('tag' => $tag, 'count' => $number_of_uses),
	 *                 ...
	 *              )
	 */
	public function display_related_block(Page $page, $tag_infos) {
		global $config;

		$html = "";
		$n = 0;
		foreach($tag_infos as $row) {
			$tag = $row['tag'];
			$h_tag = html_escape($tag);
			$h_tag_no_underscores = str_replace("_", " ", $h_tag);
			$count = $row['calc_count'];
			if($n++) $html .= "\n<br/>";
			if(!is_null($config->get_string('info_link'))) {
				$link = str_replace('$tag', $tag, $config->get_string('info_link'));
				$html .= " <a class='tag_info_link' href='$link'>?</a>";
			}
			$link = $this->tag_link($row['tag']);
			$html .= " <a class='tag_name' href='$link'>$h_tag_no_underscores</a>";
			if($config->get_bool("tag_list_numbers")) {
				$html .= " <span class='tag_count'>$count</span>";
			}
		}

		$page->add_block(new Block("Related", $html, "left"));
	}


	/*
	 * $tag_infos = array(
	 *                 array('tag' => $tag, 'count' => $number_of_uses),
	 *                 ...
	 *              )
	 */
	public function display_popular_block(Page $page, $tag_infos) {
		global $config;

		$html = "";
		$n = 0;
		foreach($tag_infos as $row) {
			$tag = $row['tag'];
			$h_tag = html_escape($tag);
			$h_tag_no_underscores = str_replace("_", " ", $h_tag);
			$count = $row['count'];
			if($n++) $html .= "\n<br/>";
			if(!is_null($config->get_string('info_link'))) {
				$link = str_replace('$tag', $tag, $config->get_string('info_link'));
				$html .= " <a class='tag_info_link' href='$link'>?</a>";
			}
			$link = $this->tag_link($row['tag']);
			$html .= " <a class='tag_name' href='$link'>$h_tag_no_underscores</a>";
			if($config->get_bool("tag_list_numbers")) {
				$html .= " <span class='tag_count'>$count</span>";
			}
		}

		$html .= "<br>&nbsp;<br><a class='more' href='".make_link("tags")."'>Full List</a>\n";
		$page->add_block(new Block("Popular Tags", $html, "left", 60));
	}

	/*
	 * $tag_infos = array(
	 *                 array('tag' => $tag),
	 *                 ...
	 *              )
	 * $search = the current array of tags being searched for
	 */
	public function display_refine_block(Page $page, $tag_infos, $search) {
		global $config;

		$html = "";
		$n = 0;
		foreach($tag_infos as $row) {
			$tag = $row['tag'];
			$h_tag = html_escape($tag);
			$h_tag_no_underscores = str_replace("_", " ", $h_tag);
			if($n++) $html .= "\n<br/>";
			if(!is_null($config->get_string('info_link'))) {
				$link = str_replace('$tag', $tag, $config->get_string('info_link'));
				$html .= " <a class='tag_info_link' href='$link'>?</a>";
			}
			$link = $this->tag_link($row['tag']);
			$html .= " <a class='tag_name' href='$link'>$h_tag_no_underscores</a>";
			$html .= $this->ars($tag, $search);
		}

		$page->add_block(new Block("Refine Search", $html, "left", 60));
	}

	protected function ars($tag, $tags) {
		// FIXME: a better fix would be to make sure the inputs are correct
		$tag = strtolower($tag);
		$tags = array_map("strtolower", $tags);
		$html = "";
		$html .= " <span class='ars'>(";
		$html .= $this->get_add_link($tags, $tag);
		$html .= $this->get_remove_link($tags, $tag);
		$html .= $this->get_subtract_link($tags, $tag);
		$html .= ")</span>";
		return $html;
	}

	protected function get_remove_link($tags, $tag) {
		if(!in_array($tag, $tags) && !in_array("-$tag", $tags)) {
			return "";
		}
		else {
			$tags = array_remove($tags, $tag);
			$tags = array_remove($tags, "-$tag");
			return "<a href='".$this->tag_link(join(' ', $tags))."' title='Remove' rel='nofollow'>R</a>";
		}
	}

	protected function get_add_link($tags, $tag) {
		if(in_array($tag, $tags)) {
			return "";
		}
		else {
			$tags = array_remove($tags, "-$tag");
			$tags = array_add($tags, $tag);
			return "<a href='".$this->tag_link(join(' ', $tags))."' title='Add' rel='nofollow'>A</a>";
		}
	}

	protected function get_subtract_link($tags, $tag) {
		if(in_array("-$tag", $tags)) {
			return "";
		}
		else {
			$tags = array_remove($tags, $tag);
			$tags = array_add($tags, "-$tag");
			return "<a href='".$this->tag_link(join(' ', $tags))."' title='Subtract' rel='nofollow'>S</a>";
		}
	}

	protected function tag_link($tag) {
		$u_tag = url_escape($tag);
		return make_link("post/list/$u_tag/1");
	}
	
	public function display_mass_editor(Page $page) {
		$html = "
		<form action='".make_link("tags/replace/tags")."' method='POST'>
			<table style='width: 300px;'>
				<tr><td>Search</td><td><input type='text' name='search'></tr>
				<tr><td>Replace</td><td><input type='text' name='replace'></td></tr>
				<tr><td colspan='2'><input type='submit' value='Replace'></td></tr>
			</table>
		</form>
		";
		$page->add_block(new Block("Mass Tag Edit", $html));
	}
	
	public function display_source_editor(Page $page) {
		$html = "
		<form action='".make_link("tags/replace/source")."' method='POST'>
			<table style='width: 300px;'>
				<tr><td>Search</td><td><input type='text' name='search'></tr>
				<tr><td>Source</td><td><input type='text' name='source'></td></tr>
				<tr><td colspan='2'><input type='submit' value='Set Source'></td></tr>
			</table>
		</form>
		";
		$page->add_block(new Block("Mass Source Edit", $html));
	}
}
?>
