<?php

class TagsTheme extends Themelet {
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

	public function display_page(Page $page) {
		$page->set_title("Tag List");
		$page->set_heading($this->heading);
		$page->add_block(new Block("Tags", $this->list));
	}
	
	public function display_navigation() {
		global $page, $user;
		
		$h_index = "<a href='".make_link()."'>Index</a>";
		$h_map = "<a href='".make_link("tags/map")."'>Map</a>";
		$h_alphabetic = "<a href='".make_link("tags/alphabetic")."'>Alphabetic</a>";
		$h_popularity = "<a href='".make_link("tags/popularity")."'>Popularity</a>";
		$h_cats = "<a href='".make_link("tags/categories")."'>Categories</a>";
		$h_aliases = "<a href='".make_link("tags/alias")."'>Alias</a>";
		$h_histories = "<a href='".make_link("tags/histories")."'>Histories</a>";
		$h_bans = "";
		$h_tools = "";
		if($user->is_mod()){
			$h_bans = "<a href='".make_link("tags/banned")."'>Banned</a><br>";
		}
		if($user->is_admin()){
			$h_tools = "<a href='".make_link("tags/tools")."'>Tools</a><br>";
		}
		$h_all = "<a href='?mincount=1'>Show All</a>";
		
		$html = "$h_index<br>&nbsp;<br>$h_map<br>$h_alphabetic<br>$h_popularity<br>$h_cats<br>$h_aliases<br>$h_histories<br>$h_bans$h_tools<br>$h_all";
		
		$page->add_block(new Block("Tags", $html, "left", 0));
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

		$page->add_block(new Block("Related Tags", $html, "left"));
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
		$page->set_title("Tag Tools");
		$page->set_heading("Tag Tools");
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
		$page->set_title("Tag Tools");
		$page->set_heading("Tag Tools");
		$page->add_block(new Block("Mass Source Edit", $html));
	}
	
	
	
	
	/*
	 * Show a page of aliases:
	 *
	 * $aliases = an array of ($old_tag => $new_tag)
	 * $is_admin = whether things like "add new alias" should be shown
	 */
	public function display_aliases($aliases, $is_admin, $pageNumber, $totalPages) {
		global $page;
		if($is_admin) {
			$action = "<th>Action</th>";
			$add = "
				<tr>
					<form action='".make_link("tags/alias/add")."' method='POST'>
						<td><input type='text' name='oldtag'></td>
						<td><input type='text' name='newtag'></td>
						<td><input type='submit' value='Add'></td>
					</form>
				</tr>
			";
		}
		else {
			$action = "";
			$add = "";
		}

		$h_aliases = "";
		$n = 0;
		foreach($aliases as $old => $new) {
			$h_old = html_escape($old);
			$h_new = html_escape($new);
			$h_new_link = "<a href='".make_link("post/list/".$h_new."/1")."'>".$h_new."</a>";
			$oe = ($n++ % 2 == 0) ? "even" : "odd";
			
			$h_aliases .= "<tr class='$oe'><td>$h_old</td><td>$h_new_link</td>";
			if($is_admin) {
				$h_aliases .= "
					<td>
						<form action='".make_link("tags/alias/remove")."' method='POST'>
							<input type='hidden' name='oldtag' value='$h_old'>
							<input type='hidden' name='newtag' value='$h_new'>
							<input type='submit' value='Remove'>
						</form>
					</td>
				";
			}
			$h_aliases .= "</tr>";
		}
		$html = "
			<script>
			$(document).ready(function() {
				$(\"#aliases\").tablesorter();
			});
			</script>
			<table id='aliases' class='zebra'>
				<thead><tr><th>From</th><th>To</th>$action</tr></thead>
				<tbody>$h_aliases</tbody>
				<tfoot>$add</tfoot>
			</table>
		";
		
		if($is_admin) {
			$html .= "<p><a href='".make_link("tags/alias/export/aliases.csv")."'>Download as CSV</a></p>";
		}

		$bulk_html = "
			<form enctype='multipart/form-data' action='".make_link("tags/alias/import")."' method='POST'>
				<input type='file' name='alias_file'>
				<input type='submit' value='Upload List'>
			</form>
		";
		
		$pagination = $this->build_paginator("tags/alias", null, $pageNumber, $totalPages);

		$page->set_title("Alias List");
		$page->set_heading("Alias List");
		$page->add_block(new Block("Aliases", $html.$pagination));
		if($is_admin) {
			$page->add_block(new Block("Bulk Upload", $bulk_html, "main", 51));
		}
	}
	
	
	public function display_history_editor(Page $page, $image_id = NULL, $history) {
		global $user;
		$start_string = "
			<div style='text-align: left'>
				<form enctype='multipart/form-data' action='".make_link("tags/histories/revert")."' method='POST'>
					<ul style='list-style-type:none;'>
		";

		$history_list = "";
		$n = 0;
		foreach($history as $fields)
		{
			$n++;
			$current_id = $fields['id'];
			$current_tags = html_escape($fields['tags']);
			$name = $fields['name'];
			$setter = "<a href='".make_link("account/profile/".url_escape($name))."'>".html_escape($name)."</a>";
			if($user->is_admin()) {
				$setter .= " / " . $fields['user_ip'];
			}
			$selected = ($n == 2) ? " checked" : "";
			$history_list .= "
				<li>
					<input type='radio' name='revert' id='$current_id' value='$current_id'$selected>
					<label for='$current_id'>$current_tags (Set by $setter)</label>
				</li>\n";
		}

		$end_string = "
					</ul>
					<input type='submit' value='Revert'>
				</form>
			</div>
		";
		$history_html = $start_string . $history_list . $end_string;
		
		$heading = "Tag History";
		if(!is_null($image_id)){
			$heading = "Tag History: Image $image_id";
		}
		
		$page->set_title($heading);
		$page->set_heading($heading);
		$page->add_block(new Block("Tag History", $history_html, "main", 10));
	}
}
?>
