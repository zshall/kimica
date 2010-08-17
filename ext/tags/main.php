<?php
/*
 * Name: Taxonomy System
 * Author: Shish
 * Description: Show the tags in various ways
 */
class AddAliasEvent extends Event {
	var $oldtag;
	var $newtag;

	public function AddAliasEvent($oldtag, $newtag) {
		$this->oldtag = $oldtag;
		$this->newtag = $newtag;
	}
}

class AddAliasException extends SCoreException {}

class Tags extends SimpleExtension {
// event handling {{{
	public function onInitExt($event) {
		global $config;
		$config->set_default_int("tag_list_length", 15);
		$config->set_default_int("tags_min", 3);
		$config->set_default_string("info_link", 'http://en.wikipedia.org/wiki/$tag');
		$config->set_default_string("tag_list_image_type", 'related');
	}
	
	
	public function onPageRequest($event) {
		global $config, $page, $user;
		
		if($event->page_matches("tags")) {

			switch($event->get_arg(0)) {
				default:
				case 'map':
					$this->theme->set_heading("Tag Map");
					$this->theme->set_tag_list($this->build_tag_map());
					$this->theme->display_page($page);
					break;
				case 'alphabetic':
					$this->theme->set_heading("Alphabetic Tag List");
					$this->theme->set_tag_list($this->build_tag_alphabetic());
					$this->theme->display_page($page);
					break;
				case 'popularity':
					$this->theme->set_heading("Tag List by Popularity");
					$this->theme->set_tag_list($this->build_tag_popularity());
					$this->theme->display_page($page);
					break;
				case 'categories':
					$this->theme->set_heading("Popular Categories");
					$this->theme->set_tag_list($this->build_tag_categories());
					$this->theme->display_page($page);
					break;
				case 'alias':
					switch($event->get_arg(1)) {
						case 'add':
								$this->add_tag_alias();
							break;
						case 'remove':
								$this->remove_tag_alias();
							break;
						case 'import':
								$this->import_tag_alias();
							break;
						case 'export':
								$this->export_tag_alias();
							break;
						default:
								$this->list_tag_alias($event);
							break;
					}
					break;
				case 'history':
					if(!$config->get_bool('tag_history_enabled')) {
						$page->set_mode("redirect");
						$page->set_redirect(make_link("post/list"));
					}
					
					switch($event->get_arg(1)) {
						case 'revert':
								// this is a request to revert to a previous version of the tags
								if($config->get_bool("tag_edit_anon") || !$user->is_anon()) {
									$this->process_revert_request($_POST['revert']);
								}
							break;
						case 'view':
								$image_id = $event->get_arg(2);
								$this->theme->display_history_editor($page, $image_id, $this->get_tag_history_from_id($image_id));
							break;
						default:
							$this->theme->display_history_editor($page, NULL, $this->get_global_tag_history());
							break;
					}
					break;
				case 'banned':
					switch($event->get_arg(1)) {
						case 'add':
								$this->add_banned_tag();
								
								$page->set_mode("redirect");
								$page->set_redirect(make_link("tags/banned"));
							break;
						case 'remove':
								$this->remove_banned_tag();
								
								$page->set_mode("redirect");
								$page->set_redirect(make_link("tags/banned"));
							break;
						default:
							$this->theme->set_heading("Banned Tags");
							$this->theme->set_banned_list($page, $this->build_tag_bans());
							break;
					}
					break;
				case 'tools':
						if($user->is_admin()){
							$this->theme->display_mass_editor($page);
							$this->theme->display_source_editor($page);
						}
						else{
							$page->set_mode("redirect");
							$page->set_redirect(make_link("tags/list"));
						}
					break;
				case 'replace':
					if($user->is_admin()){
						switch($event->get_arg(1)) {
							case 'tags':
									if(isset($_POST['search']) && isset($_POST['replace'])) {
										$search = $_POST['search'];
										$replace = $_POST['replace'];
										$this->mass_tag_edit($search, $replace);
										$page->set_mode("redirect");
										$page->set_redirect(make_link("tags/tools"));
									}
								break;
							case 'source':
									if(isset($_POST['search']) && isset($_POST['source'])) {
										$search = $_POST['search'];
										$source = $_POST['source'];
										$this->mass_source_edit($search, $source);
										$page->set_mode("redirect");
										$page->set_redirect(make_link("tags/tools"));
									}
								break;
						}
					}
					else{
						$page->set_mode("redirect");
						$page->set_redirect(make_link("tags/list"));
					}
					break;
			}
			$this->theme->display_navigation();
		}
		
		if($event->page_matches("api/internal/tag_list/complete")) {
			global $database;
			$all = $database->get_all(
					"SELECT tag FROM tags WHERE tag LIKE ? AND count > 0 LIMIT 10",
					array($_GET["s"]."%"));
			$res = array();
			foreach($all as $row) {$res[] = $row["tag"];}

			$page->set_mode("data");
			$page->set_type("text/plain");
			$page->set_data(implode("\n", $res));
		}
	}
	
	public function onAddAlias(AddAliasEvent $event) {
		global $database;
		$pair = array($event->oldtag, $event->newtag);
		if($database->db->GetRow("SELECT * FROM tag_alias WHERE oldtag=? AND lower(newtag)=lower(?)", $pair)) {
			throw new AddAliasException("That alias already exists");
		}
		else {
			$this->mass_tag_edit($event->oldtag, $event->newtag);
			$database->Execute("INSERT INTO tag_alias(oldtag, newtag) VALUES(?, ?)", $pair);
			log_info("alias_editor", "Added alias for {$event->oldtag} -> {$event->newtag}");
		}
	}
		
	public function onPostListBuilding($event) {
		global $config, $page;
		if($config->get_int('tag_list_length') > 0) {
			if(!empty($event->search_terms)) {
				$this->add_refine_block($page, $event->search_terms);
			}
			else {
				$this->add_popular_block($page);
			}
		}
	}
	
	public function onDisplayingImage($event) {
		global $config, $page;
		if($config->get_int('tag_list_length') > 0) {
			if($config->get_string('tag_list_image_type') == 'related') {
				$this->add_related_block($page, $event->image);
			}
			else {
				$this->add_tags_block($page, $event->image);
			}
		}
	}
	
	public function onTagSet($event) {
		global $config, $user;
		if($config->get_bool('tag_history_enabled')) {
			log_info("historie","tag set");
			$this->add_tag_history($event->image, $event->tags);
		}
		if($user->is_admin() || !$event->image->is_locked()) {
			$event->image->set_tags($event->tags);
		}
	}
	
	//FIXME: The image is deleted and the event returns no id so the deletion from tag_histories doesn't work.
	public function onImageDeletion($event) {
		global $database;
		$image = Image::by_id($event->image->id);
		if($image) {
			$database->execute("DELETE FROM tag_histories WHERE image_id = ?", array($image->id));
		}
	}
			
	public function onSetupBuilding(SetupBuildingEvent $event) {
		$sb = new SetupBlock("Tag Map Options");
		$sb->add_int_option("tags_min", "Only show tags used at least "); $sb->add_label(" times");
		$event->panel->add_block($sb);

		$sb = new SetupBlock("Popular / Related Tag List");
		$sb->add_int_option("tag_list_length", "Show top "); $sb->add_label(" tags");
		$sb->add_text_option("info_link", "<br>Tag info link: ");
		$sb->add_choice_option("tag_list_image_type", array(
			"Image's tags only" => "tags",
			"Show related" => "related"
		), "<br>Image tag list: ");
		$sb->add_bool_option("tag_list_numbers", "<br>Show tag counts: ");
		$event->panel->add_block($sb);
		
		$sb = new SetupBlock("Tag History");
		$sb->add_bool_option("tag_history_enabled", "Enable Tag History: ");
		$sb->add_label("<br>Limit to ");
		$sb->add_int_option("history_limit");
		$sb->add_label(" entires per image");
		$sb->add_label("<br>(-1 for unlimited)");
		$event->panel->add_block($sb);
	}
// }}}
// misc {{{
	private function tag_link($tag) {
		$u_tag = url_escape($tag);
		return make_link("post/list/$u_tag/1");
	}

	private function get_tags_min() {
		if(isset($_GET['mincount'])) {
			return int_escape($_GET['mincount']);
		}
		else {
			global $config;
			return $config->get_int('tags_min');
		}
	}
// }}}
// maps {{{
	private function build_tag_map() {
		global $database;

		$tags_min = $this->get_tags_min();
		$result = $database->execute("
				SELECT
					tag,
					FLOOR(LOG(2.7, LOG(2.7, count - ? + 1)+1)*1.5*100)/100 AS scaled
				FROM tags
				WHERE count >= ?
				ORDER BY tag
			", array($tags_min, $tags_min));
		$tag_data = $result->GetArray();

		$html = "";
		foreach($tag_data as $row) {
			$h_tag = html_escape($row['tag']);
			$size = sprintf("%.2f", (float)$row['scaled']);
			$link = $this->tag_link($row['tag']);
			if($size<0.5) $size = 0.5;
			$h_tag_no_underscores = str_replace("_", " ", $h_tag);
			$html .= "&nbsp;<a style='font-size: ${size}em' href='$link'>$h_tag_no_underscores</a>&nbsp;\n";
		}
		return $html;
	}

	private function build_tag_alphabetic() {
		global $database;

		$tags_min = $this->get_tags_min();
		$result = $database->execute(
				"SELECT tag,count FROM tags WHERE count >= ? ORDER BY tag",
				array($tags_min));
		$tag_data = $result->GetArray();

		$html = "";
		$lastLetter = "";
		foreach($tag_data as $row) {
			$h_tag = html_escape($row['tag']);
			$count = $row['count'];
			if($lastLetter != strtolower(substr($h_tag, 0, 1))) {
				$lastLetter = strtolower(substr($h_tag, 0, 1));
				$html .= "<p>$lastLetter<br>";
			}
			$link = $this->tag_link($row['tag']);
			$html .= "<a href='$link'>$h_tag&nbsp;($count)</a>\n";
		}

		return $html;
	}

	private function build_tag_popularity() {
		global $database;

		$tags_min = $this->get_tags_min();
		$result = $database->execute(
				"SELECT tag,count,FLOOR(LOG(count)) AS scaled FROM tags WHERE count >= ? ORDER BY count DESC, tag ASC",
				array($tags_min));
		$tag_data = $result->GetArray();

		$html = "Results grouped by log<sub>e</sub>(n)";
		$lastLog = "";
		foreach($tag_data as $row) {
			$h_tag = html_escape($row['tag']);
			$count = $row['count'];
			$scaled = $row['scaled'];
			if($lastLog != $scaled) {
				$lastLog = $scaled;
				$html .= "<p>$lastLog<br>";
			}
			$link = $this->tag_link($row['tag']);
			$html .= "<a href='$link'>$h_tag&nbsp;($count)</a>\n";
		}

		return $html;
	}

	private function build_tag_categories() {
		global $database;

		$tags_min = $this->get_tags_min();
		$result = $database->execute("SELECT tag,count FROM tags ORDER BY count DESC, tag ASC LIMIT 9");
		$tag_data = $result->GetArray();

		$html = "<table>";
		$n = 0;
		foreach($tag_data as $row) {
			if($n%3==0) $html .= "<tr>";
			$h_tag = html_escape($row['tag']);
			$link = $this->tag_link($row['tag']);
			$image = Image::by_random(array($row['tag']));
			if(is_null($image)) continue; // one of the popular tags has no images
			$thumb = $image->get_thumb_link();
			$tsize = get_thumbnail_size($image->width, $image->height);
			$html .= "<td><a href='$link'><img src='$thumb' style='height: {$tsize[1]}px; width: {$tsize[0]}px;'><br>$h_tag</a></td>\n";
			if($n%3==2) $html .= "</tr>";
			$n++;
		}
		$html .= "</table>";

		return $html;
	}
	
	private function build_tag_bans() {
		global $user, $database;

		$tags_min = $this->get_tags_min();
		$result = $database->execute("SELECT tag, status FROM tag_bans ORDER BY tag ASC");
		$tag_data = $result->GetArray();

		$h_bans = "";
		$n = 0;
		foreach($tag_data as $row) {
			$oe = ($n++ % 2 == 0) ? "even" : "odd";
			
			$h_tag = "<a href='".make_link("post/list/".url_escape($row["tag"])."/1")."'>".html_escape($row["tag"])."</a>";
			$h_status = "";
			
			switch($row["status"]) {
				case "p": $h_status = "pending";
				case "d": $h_status = "deleted";
				case "h": $h_status = "hidden";
			}
			
			$h_bans .= "<tr class='$oe'><td>$h_tag</td><td>$h_status</td>";
			if($user->is_admin()) {
				$h_bans .= "
					<td>
						<form action='".make_link("tags/banned/remove")."' method='POST'>
							<input type='hidden' name='tag' value='".$row["tag"]."'>
							<input type='submit' value='Remove'>
						</form>
					</td>
				";
			}
			$h_bans .= "</tr>";
		}
		
		$add = "";
		$actions = "";
		if($user->is_admin()){
			$add = "
				<tr>
					<form action='".make_link("tags/banned/add")."' method='POST'>
						<td><input type='text' name='tag'></td>
						<td>
							<select name='status'>
								<option value='p'>Pending</option>
								<option value='d'>Deleted</option>
								<option value='h'>Hidden</option>
							</select> 
						</td>
						<td><input type='submit' value='Add'></td>
					</form>
				</tr>
			";
			
			$actions = "<th>Actions</th>";
		}
						
		$html = "<table id='tag_bans' class='zebra'>
					<thead><tr><th>Tag</th><th>Status</th>$actions</tr></thead>
					<tbody>$h_bans</tbody>
					<tfoot>$add</tfoot>
				</table>";
				
		return $html;
	}
	
	private function add_banned_tag(){
		global $user, $database;
		
		$tag = $_POST["tag"];
		$status= $_POST["status"];
		
		if($user->is_admin()){
			$database->Execute(
				"INSERT INTO tag_bans(
					tag, status)
				VALUES (?, ?)",
				array($tag, $status));
				
			$n = 0;
			while(true) {
				$images = Image::find_images($n, 100, Tag::explode($_POST["tag"]));
				if(count($images) == 0) break;
				foreach($images as $image) {
					send_event(new ImageTagBanEvent($user, $image));
				}
			$n += 100;
			}
		}
	}
	
	private function remove_banned_tag(){
		global $user, $database;
		
		$tag = $_POST["tag"];
		if($user->is_admin()){
			$database->execute("DELETE FROM tag_bans WHERE tag = ?", array($tag));
			
			$n = 0;
			while(true) {
				$images = Image::find_images($n, 100, Tag::explode($_POST["tag"]));
				if(count($images) == 0) break;
				foreach($images as $image) {
					send_event(new ImageTagUnBanEvent($image));
				}
			$n += 100;
			}
		}
	}
// }}}
// blocks {{{
	private function add_related_block($page, $image) {
		global $database;
		global $config;

		$query = "
			SELECT t3.tag AS tag, t3.count AS calc_count
			FROM
				image_tags AS it1,
				image_tags AS it2,
				image_tags AS it3,
				tags AS t1,
				tags AS t3
			WHERE
				it1.image_id=?
				AND it1.tag_id=it2.tag_id
				AND it2.image_id=it3.image_id
				AND t1.tag != 'tagme'
				AND t3.tag != 'tagme'
				AND t1.id = it1.tag_id
				AND t3.id = it3.tag_id
			GROUP BY it3.tag_id
			ORDER BY calc_count DESC
			LIMIT ?
		";
		$args = array($image->id, $config->get_int('tag_list_length'));

		$tags = $database->get_all($query, $args);
		if(count($tags) > 0) {
			$this->theme->display_related_block($page, $tags);
		}
	}

	private function add_tags_block($page, $image) {
		global $database;
		global $config;

		$query = "
			SELECT tags.tag, tags.count as calc_count
			FROM tags, image_tags
			WHERE tags.id = image_tags.tag_id
			AND image_tags.image_id = ?
			ORDER BY calc_count DESC
			LIMIT ?
		";
		$args = array($image->id, $config->get_int('tag_list_length'));

		$tags = $database->get_all($query, $args);
		if(count($tags) > 0) {
			$this->theme->display_related_block($page, $tags);
		}
	}

	private function add_popular_block($page) {
		global $database;
		global $config;

		$query = "
			SELECT tag, count
			FROM tags
			WHERE count > 0
			ORDER BY count DESC
			LIMIT ?
		";
		$args = array($config->get_int('tag_list_length'));

		$tags = $database->get_all($query, $args);
		if(count($tags) > 0) {
			$this->theme->display_popular_block($page, $tags);
		}
	}

	private function add_refine_block($page, $search) {
		global $database;
		global $config;

		$wild_tags = Tag::explode($search);
		// $search_tags = array();

		$tag_id_array = array();
		$tags_ok = true;
		foreach($wild_tags as $tag) {
			$tag = str_replace("*", "%", $tag);
			$tag = str_replace("?", "_", $tag);
			$tag_ids = $database->db->GetCol("SELECT id FROM tags WHERE tag LIKE ?", array($tag));
			// $search_tags = array_merge($search_tags,
			//                  $database->db->GetCol("SELECT tag FROM tags WHERE tag LIKE ?", array($tag)));
			$tag_id_array = array_merge($tag_id_array, $tag_ids);
			$tags_ok = count($tag_ids) > 0;
			if(!$tags_ok) break;
		}
		$tag_id_list = join(', ', $tag_id_array);

		if($tags_ok) {
			$query = "
				SELECT t2.tag AS tag, COUNT(it2.image_id) AS calc_count
				FROM
					image_tags AS it1,
					image_tags AS it2,
					tags AS t1,
					tags AS t2
				WHERE
					t1.id IN($tag_id_list)
					AND it1.image_id=it2.image_id
					AND it1.tag_id = t1.id
					AND it2.tag_id = t2.id
				GROUP BY t2.tag
				ORDER BY calc_count
				DESC LIMIT ?
			";
			$args = array($config->get_int('tag_list_length'));

			$related_tags = $database->get_all($query, $args);
			print $database->db->ErrorMsg();
			if(count($related_tags) > 0) {
				$this->theme->display_refine_block($page, $related_tags, $wild_tags);
			}
		}
	}
// }}}
// {{{ Mass Editor
	private function mass_tag_edit($search, $replace) {
		global $database;
		global $config;

		$search_set = Tag::explode($search);
		$replace_set = Tag::explode($replace);

		$last_id = -1;
		while(true) {
			// make sure we don't look at the same images twice.
			// search returns high-ids first, so we want to look
			// at images with lower IDs than the previous.
			$search_forward = $search_set;
			if($last_id >= 0) $search_forward[] = "id<$last_id";

			$images = Image::find_images(0, 100, $search_forward);
			if(count($images) == 0) break;

			foreach($images as $image) {
				// remove the search'ed tags
				$before = $image->get_tag_array();
				$after = array();
				foreach($before as $tag) {
					if(!in_array($tag, $search_set)) {
						$after[] = $tag;
					}
				}

				// add the replace'd tags
				foreach($replace_set as $tag) {
					$after[] = $tag;
				}

				$image->set_tags($after);

				$last_id = $image->id;
			}
		}
	}
	
	private function mass_source_edit($search, $source) {
		global $database;
		
		$search_set = Tag::explode($search);
		
		$n = 0;
		while(true) {
			$images = Image::find_images($n, 100, $search_set);
			if(count($images) == 0) break;
			foreach($images as $image) {
				$image->set_source($source);
			}
			$n += 100;
		}
	}
// }}}
// {{{ Tag Aliases
	public function list_tag_alias($event){
		global $config, $database, $user;
		$page_number = $event->get_arg(1);
		if(is_null($page_number) || !is_numeric($page_number)) {
			$page_number = 0;
		}
		else if ($page_number <= 0) {
			$page_number = 0;
		}
		else {
			$page_number--;
		}

		$alias_per_page = $config->get_int('alias_items_per_page', 30);

		$query = "SELECT oldtag, newtag FROM tag_alias ORDER BY newtag ASC LIMIT ? OFFSET ?";
		$alias = $database->db->GetAssoc($query,array($alias_per_page, $page_number * $alias_per_page));

		$total_pages = ceil($database->db->GetOne("SELECT COUNT(*) FROM tag_alias") / $alias_per_page);

		$this->theme->display_aliases($alias, $user->is_admin(), $page_number + 1, $total_pages);
	}

	public function add_tag_alias(){
		global $user, $page;
		if($user->is_admin()) {
			if(isset($_POST['oldtag']) && isset($_POST['newtag'])) {
				try {
					send_event(new AddAliasEvent($_POST['oldtag'], $_POST['newtag']));
					$page->set_mode("redirect");
					$page->set_redirect(make_link("tags/alias"));
				}
				catch(AddAliasException $ex) {
					$this->theme->display_error("Error adding alias", $ex->getMessage());
				}
			}
		}
	}
	
	public function remove_tag_alias(){
		global $database, $user, $page;
		if($user->is_admin()) {
			if(isset($_POST['oldtag']) && isset($_POST['newtag'])) {
				
				$database->Execute("DELETE FROM tag_alias WHERE oldtag=?", array($_POST['oldtag']));
				
				$this->mass_tag_edit($_POST['newtag'], $_POST['oldtag']); // GET BACK TO THE OLD TAGS
				

				log_info("alias_editor", "Deleted alias for ".$_POST['oldtag']);

				$page->set_mode("redirect");
				$page->set_redirect(make_link("tags/alias"));
			}
		}
	}
	
	private function export_tag_alias() {
		global $database, $page;
		$csv = "";
		$aliases = $database->db->GetAssoc("SELECT oldtag, newtag FROM tag_alias");
		foreach($aliases as $old => $new) {
			$csv .= "$old,$new\n";
		}
		$page->set_mode("data");
		$page->set_type("text/plain");
		$page->set_data($csv);
	}

	private function import_tag_alias() {
		global $database, $user, $page;
		if($user->is_admin()) {
			if(count($_FILES) > 0) {
				$tmp = $_FILES['alias_file']['tmp_name'];
				$csv = file_get_contents($tmp);
				
				$csv = str_replace("\r", "\n", $csv);
				foreach(explode("\n", $csv) as $line) {
					$parts = explode(",", $line);
					if(count($parts) == 2) {
						$database->execute("INSERT INTO tag_alias(oldtag, newtag) VALUES(?, ?)", $parts);
					}
				}
				
				$page->set_mode("redirect");
				$page->set_redirect(make_link("tags/alias"));
			}
			else {
				$this->theme->display_error("No File Specified", "You have to upload a file");
			}
		}
		else {
			$this->theme->display_error("Admins Only", "Only admins can edit the alias list");
		}
	}
// }}}
// {{{ Tag Histories
	public function get_global_tag_history()
	{
		global $database;
		$row = $database->get_all("
				SELECT tag_histories.*, users.name
				FROM tag_histories
				JOIN users ON tag_histories.user_id = users.id
				ORDER BY tag_histories.id DESC
				LIMIT 100");
		return ($row ? $row : array());
	}
	
	public function get_tag_history_from_id($image_id)
	{
		global $database;
		$row = $database->get_all("
				SELECT tag_histories.*, users.name
				FROM tag_histories
				JOIN users ON tag_histories.user_id = users.id
				WHERE image_id = ?
				ORDER BY tag_histories.id DESC",
				array($image_id));
		return ($row ? $row : array());
	}
	
	public function get_tag_history_from_revert($revert_id)
	{
		global $database;
		$row = $database->execute("
				SELECT tag_histories.*, users.name
				FROM tag_histories
				JOIN users ON tag_histories.user_id = users.id
				WHERE tag_histories.id = ?", array($revert_id));
		return ($row ? $row : null);
	}
	
	/*
	 * this function is called just before an images tag are changed
	 */
	public function add_tag_history($image, $tags)
	{
		global $database;
		global $config;
		global $user;

		$new_tags = Tag::implode($tags);
		$old_tags = Tag::implode($image->get_tag_array());
		
		if($new_tags == $old_tags) return;
		
		// add a history entry		
		$allowed = $config->get_int("history_limit");
		if($allowed == 0) return;
		
		log_debug("tag_history", "adding tag history: [$old_tags] -> [$new_tags]");

		$row = $database->execute("
				INSERT INTO tag_histories(image_id, tags, user_id, user_ip, date_set)
				VALUES (?, ?, ?, ?, now())",
				array($image->id, $new_tags, $user->id, $_SERVER['REMOTE_ADDR']));
		
		// if needed remove oldest one
		if($allowed == -1) return;
		$entries = $database->db->GetOne("SELECT COUNT(*) FROM tag_histories WHERE image_id = ?", array($image->id));
		if($entries > $allowed)
		{
			// TODO: Make these queries better
			$min_id = $database->db->GetOne("SELECT MIN(id) FROM tag_histories WHERE image_id = ?", array($image->id));
			$database->execute("DELETE FROM tag_histories WHERE id = ?", array($min_id));
		}
	}
	
	/*
	 * this function is called when a revert request is received
	 */
	private function process_revert_request($revert_id) {
		global $page;
		// check for the nothing case
		if($revert_id=="nothing")
		{
			// tried to set it too the same thing so ignore it (might be a bot)
			// go back to the index page with you
			$page->set_mode("redirect");
			$page->set_redirect(make_link());
			return;
		}
		
		$revert_id = int_escape($revert_id);
		
		// lets get this revert id assuming it exists
		$result = $this->get_tag_history_from_revert($revert_id);
		
		if($result==null)
		{
			// there is no history entry with that id so either the image was deleted
			// while the user was viewing the history, someone is playing with form
			// variables or we have messed up in code somewhere.
			die("Error: No tag history with specified id was found.");
		}
		
		// lets get the values out of the result
		$stored_result_id = $result->fields['id'];
		$stored_image_id = $result->fields['image_id'];
		$stored_tags = $result->fields['tags'];
		
		log_debug("tag_history", "Reverting tags of $stored_image_id to [$stored_tags]");
		// all should be ok so we can revert by firing the SetUserTags event.
		send_event(new TagSetEvent(Image::by_id($stored_image_id), $stored_tags));
		
		// all should be done now so redirect the user back to the image
		$page->set_mode("redirect");
		$page->set_redirect(make_link("post/view/$stored_image_id"));
	}
// }}}
}
add_event_listener(new Tags(), 40);
?>