<?php
/*
 * Name: Taxonomy System
 * Author: Shish
 * Description: Show the tags in various ways
 */

class TagList implements Extension {
	var $theme = null;

// event handling {{{
	public function receive_event(Event $event) {
		global $config, $database, $page, $user;
		if($this->theme == null) $this->theme = get_theme_object($this);

		if($event instanceof InitExtEvent) {
			$config->set_default_int("tag_list_length", 15);
			$config->set_default_int("tags_min", 3);
			$config->set_default_string("info_link", 'http://en.wikipedia.org/wiki/$tag');
			$config->set_default_string("tag_list_image_type", 'related');
		}

		if(($event instanceof PageRequestEvent) && $event->page_matches("tags")) {
			$this->theme->set_navigation($this->build_navigation());
			switch($event->get_arg(0)) {
				default:
				case 'map':
					$this->theme->set_heading("Tag Map");
					$this->theme->set_tag_list($this->build_tag_map());
					break;
				case 'alphabetic':
					$this->theme->set_heading("Alphabetic Tag List");
					$this->theme->set_tag_list($this->build_tag_alphabetic());
					break;
				case 'popularity':
					$this->theme->set_heading("Tag List by Popularity");
					$this->theme->set_tag_list($this->build_tag_popularity());
					break;
				case 'categories':
					$this->theme->set_heading("Popular Categories");
					$this->theme->set_tag_list($this->build_tag_categories());
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
			}
			$this->theme->display_page($page);
		}

		if(($event instanceof PageRequestEvent) && $event->page_matches("api/internal/tag_list/complete")) {
			$all = $database->get_all(
					"SELECT tag FROM tags WHERE tag LIKE ? AND count > 0 LIMIT 10",
					array($_GET["s"]."%"));

			$res = array();
			foreach($all as $row) {$res[] = $row["tag"];}

			$page->set_mode("data");
			$page->set_type("text/plain");
			$page->set_data(implode("\n", $res));
		}

		if($event instanceof PostListBuildingEvent) {
			if($config->get_int('tag_list_length') > 0) {
				if(!empty($event->search_terms)) {
					$this->add_refine_block($page, $event->search_terms);
				}
				else {
					$this->add_popular_block($page);
				}
			}
		}

		if($event instanceof DisplayingImageEvent) {
			if($config->get_int('tag_list_length') > 0) {
				if($config->get_string('tag_list_image_type') == 'related') {
					$this->add_related_block($page, $event->image);
				}
				else {
					$this->add_tags_block($page, $event->image);
				}
			}
		}

		if($event instanceof SetupBuildingEvent) {
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
		}
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
	private function build_navigation() {
		global $user;
		
		$h_index = "<a href='".make_link()."'>Index</a>";
		$h_map = "<a href='".make_link("tags/map")."'>Map</a>";
		$h_alphabetic = "<a href='".make_link("tags/alphabetic")."'>Alphabetic</a>";
		$h_popularity = "<a href='".make_link("tags/popularity")."'>Popularity</a>";
		$h_cats = "<a href='".make_link("tags/categories")."'>Categories</a>";
		$h_bans = "";
		if($user->is_mod()){
			$h_bans = "<a href='".make_link("tags/banned")."'>Banned</a><br>";
		}
		$h_all = "<a href='?mincount=1'>Show All</a>";
		
		return "$h_index<br>&nbsp;<br>$h_map<br>$h_alphabetic<br>$h_popularity<br>$h_cats<br>$h_bans&nbsp;<br>$h_all";
	}

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
		$result = $database->execute("SELECT name, status FROM tag_bans ORDER BY name ASC");
		$tag_data = $result->GetArray();

		$h_bans = "";
		$n = 0;
		foreach($tag_data as $row) {
			$oe = ($n++ % 2 == 0) ? "even" : "odd";
			
			$h_tag = "<a href='".make_link("post/list/".url_escape($row["name"])."/1")."'>".html_escape($row["name"])."</a>";
			$h_status = "";
			
			switch($row["status"]) {
				case "p": $h_status = "pending";
				case "d": $h_status = "deleted";
			}
			
			$h_bans .= "<tr class='$oe'><td>$h_tag</td><td>$h_status</td>";
			if($user->is_admin()) {
				$h_bans .= "
					<td>
						<form action='".make_link("tags/banned/remove")."' method='POST'>
							<input type='hidden' name='tag' value='".$row["name"]."'>
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
					name, status)
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
			$database->execute("DELETE FROM tag_bans WHERE name = ?", array($tag));
			
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
}
add_event_listener(new TagList());
?>
