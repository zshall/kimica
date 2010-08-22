<?php
/**
 * Name: Image List
 * Author: Shish <webmaster@shishnet.org>
 * Link: http://code.shishnet.org/shimmie2/
 * License: GPLv2
 * Description: Show a list of uploaded images
 * Documentation:
 *  Here is a list of the search methods available out of the box;
 *  Shimmie extensions may provide other filters:
 *  <ul>
 *    <li>by tag, eg
 *      <ul>
 *        <li>cat
 *        <li>pie
 *        <li>somethi* -- wildcards are supported
 *      </ul>
 *    <li>size (=, &lt;, &gt;, &lt;=, &gt;=) width x height, eg
 *      <ul>
 *        <li>size=1024x768 -- a specific wallpaper size
 *        <li>size&gt;=500x500 -- no small images
 *        <li>size&lt;1000x1000 -- no large images
 *      </ul>
 *    <li>ratio (=, &lt;, &gt;, &lt;=, &gt;=) width : height, eg
 *      <ul>
 *        <li>ratio=4:3, ratio=16:9 -- standard wallpaper
 *        <li>ratio=1:1 -- square images
 *        <li>ratio<1:1 -- tall images
 *        <li>ratio>1:1 -- wide images
 *      </ul>
 *    <li>filesize (=, &lt;, &gt;, &lt;=, &gt;=) size, eg
 *      <ul>
 *        <li>filesize>1024 -- no images under 1KB
 *        <li>filesize<=3MB -- shorthand filesizes are supported too
 *      </ul>
 *    <li>id (=, &lt;, &gt;, &lt;=, &gt;=) number, eg
 *      <ul>
 *        <li>id<20 -- search only the first few images
 *        <li>id>=500 -- search later images
 *      </ul>
 *    <li>user=Username, eg
 *      <ul>
 *        <li>user=Shish -- find all of Shish's posts
 *      </ul>
 *    <li>hash=md5sum, eg
 *      <ul>
 *        <li>hash=bf5b59173f16b6937a4021713dbfaa72 -- find the "Taiga want up!" image
 *      </ul>
 *    <li>filetype=type, eg
 *      <ul>
 *        <li>filetype=png -- find all PNG images
 *      </ul>
 *    <li>filename=blah, eg
 *      <ul>
 *        <li>filename=kitten -- find all images with "kitten" in the original filename
 *      </ul>
 *    <li>posted=date, eg
 *      <ul>
 *        <li>posted=2009-12-25 -- find images posted on the 25th December
 *      </ul>
 *  </ul>
 *  <p>Search items can be combined to search for images which match both,
 *  or you can stick "-" in front of an item to search for things that don't
 *  match it.
 *  <p>Some search methods provided by extensions:
 *  <ul>
 *    <li>Danbooru API
 *      <ul>
 *        <li>md5:[hash] -- same as "hash=", but the API calls it by a different name
 *      </ul>
 *    <li>Numeric Score
 *      <ul>
 *        <li>score (=, &lt;, &gt;, &lt;=, &gt;=) number -- seach by score
 *        <li>upvoted_by=Username -- search for a user's likes
 *        <li>downvoted_by=Username -- search for a user's dislikes
 *      </ul>
 *    <li>Image Rating
 *      <ul>
 *        <li>rating=se -- find safe and explicit images, ignore questionable and unknown
 *      </ul>
 *    <li>Favorites
 *      <ul>
 *        <li>favorites (=, &lt;, &gt;, &lt;=, &gt;=) number -- search for images favourited a certain number of times
 *        <li>favourited_by=Username -- search for a user's choices
 *      </ul>
 *    <li>Notes
 *      <ul>
 *        <li>notes (=, &lt;, &gt;, &lt;=, &gt;=) number -- search by the number of notes an image has
 *      </ul>
 *  </ul>
 */

/*
 * SearchTermParseEvent:
 * Signal that a search term needs parsing
 */
class SearchTermParseEvent extends Event {
	var $term = null;
	var $context = null;
	var $querylets = array();

	public function SearchTermParseEvent($term, $context) {
		$this->term = $term;
		$this->context = $context;
	}

	public function is_querylet_set() {
		return (count($this->querylets) > 0);
	}

	public function get_querylets() {
		return $this->querylets;
	}

	public function add_querylet($q) {
		$this->querylets[] = $q;
	}
}

class SearchTermParseException extends SCoreException {}

class PostListBuildingEvent extends Event {
	var $search_terms = null;

	public function __construct($search) {
		$this->search_terms = $search;
	}
}

class DisplayingImageEvent extends Event {
	var $image, $page, $context;

	public function __construct(Image $image) {
		$this->image = $image;
	}

	public function get_image() {
		return $this->image;
	}
}

class ImageInfoBoxBuildingEvent extends Event {
	var $parts = array();
	var $image;
	var $user;

	public function ImageInfoBoxBuildingEvent(Image $image, User $user) {
		$this->image = $image;
		$this->user = $user;
	}

	public function add_part($html, $position=50) {
		while(isset($this->parts[$position])) $position++;
		$this->parts[$position] = $html;
	}
}

class ImageInfoSetEvent extends Event {
	var $image;

	public function ImageInfoSetEvent(Image $image) {
		$this->image = $image;
	}
}


/*
 * SourceSetEvent:
 *   $image_id
 *   $source
 *
 */
class SourceSetEvent extends Event {
	var $image;
	var $source;

	public function SourceSetEvent(Image $image, $source) {
		$this->image = $image;
		$this->source = $source;
	}
}

/*
 * TagSetEvent:
 *   $image_id
 *   $tags
 *
 */
class TagSetEvent extends Event {
	var $image;
	var $tags;

	public function TagSetEvent(Image $image, $tags) {
		$this->image = $image;
		$this->tags = Tag::explode($tags);
	}
}


class ImageAdminBlockBuildingEvent extends Event {
	var $parts = array();
	var $image = null;
	var $user = null;

	public function ImageAdminBlockBuildingEvent(Image $image, User $user) {
		$this->image = $image;
		$this->user = $user;
	}

	public function add_part($html, $position=50) {
		while(isset($this->parts[$position])) $position++;
		$this->parts[$position] = $html;
	}
}

class RemoveReportedImageEvent extends Event {
	var $id;

	public function RemoveReportedImageEvent($id) {
		$this->id = $id;
	}
}

class AddReportedImageEvent extends Event {
	var $reporter_id;
	var $image_id;
	var $reason;

	public function AddReportedImageEvent($image_id, $reporter_id, $reason) {
		$this->reporter_id = $reporter_id;
		$this->image_id = $image_id;
		$this->reason = $reason;
	}
}

class Post extends SimpleExtension {
	public function onInitExt($event) {
		global $config;
		$config->set_default_int("index_width", 3);
		$config->set_default_int("index_height", 4);
		$config->set_default_bool("index_tips", true);
		
		$config->set_default_int("index_search_max_tags", 3);
		$config->set_default_string("index_search_limited_to", "bgu");
		
		$config->set_default_bool("post_zoom", true);
		
		$config->set_default_string("post_approved_visible_to", "oamcug");
		$config->set_default_string("post_locked_visible_to", "oamcug");
		$config->set_default_string("post_pending_visible_to", "oam");
		$config->set_default_string("post_deleted_visible_to", "oam");
		$config->set_default_string("post_hidden_visible_to", "oa");
								
		$config->set_default_string("index_mode_general", "oamcu");
		$config->set_default_string("index_mode_admin", "oa");
		$config->set_default_string("index_mode_favorites", "oamcu");
		$config->set_default_string("index_mode_score", "oamcu");
		$config->set_default_string("index_mode_rating", "oamcu");
		
		$config->set_default_string("populars_mode", "views");
	}
	
	public function onPageRequest($event) {
		global $config, $database, $page, $user;
		if($event->page_matches("post/list")) {
								
			if(isset($_GET['search'])) {
				$search = url_escape(trim($_GET['search']));
				if(empty($search)) {
					$page->set_mode("redirect");
					$page->set_redirect(make_link("post/list/1"));
				}
				else {
					$page->set_mode("redirect");
					$page->set_redirect(make_link("post/list/$search/1"));
				}
				return;
			}

			$search_terms = $event->get_search_terms();
			$page_number = $event->get_page_number();
			$page_size = $event->get_page_size();
			
			try {
			
				$auth = $user->get_auth_from_str($config->get_string("index_search_limited_to"));
				
				if($auth){
					$max_search = $config->get_int("index_search_max_tags");
					if(count($search_terms) > $max_search){
						throw new SearchTermParseException("You can search up to {$max_search} tags at the same time.");
					}
				}
				
				$total_pages = Image::count_pages($search_terms);
				$images = Image::find_images(($page_number-1)*$page_size, $page_size, $search_terms);
				
				if(count($search_terms) == 0 && count($images) == 0 && $page_number == 1) {
					$this->theme->display_intro($page);
					send_event(new PostListBuildingEvent($search_terms));
				}
				else if(count($search_terms) > 0 && count($images) == 1 && $page_number == 1) {
					$page->set_mode("redirect");
					$page->set_redirect(make_link("post/view/{$images[0]->id}"));
				}
				else {
					send_event(new PostListBuildingEvent($search_terms));
					
					if($images){
						$this->theme->set_page($page_number, $total_pages, $search_terms);
						$this->theme->display_page($images);
					}
					else{
						$this->theme->display_error("Search", "No posts were found to match the search criteria.");
					}
				}
			}
			catch(SearchTermParseException $ex) {
				// FIXME: display the error somewhere
				$total_pages = 0;
				$images = array();
				
				$this->theme->display_error("Search", $ex->getMessage());
			}
		}
		
		if($event->page_matches("post/popular")) {
			global $config;
			
			$date = $event->get_arg(0);
			$images = $event->get_page_size();
			
			if(!isset($date)){
				$date = date("Y-m-d");
			}
			
			if(preg_match("([0-9]{4}-[0-9]{2}-[0-9]{2})", $date) || preg_match("([0-9]{4}-[0-9]{2})", $date) || preg_match("([0-9]{4})", $date)) {
				$search_date = "%".$date."%";
				$rating = "";
				if(class_exists("Ratings")){
					$rating = Ratings::privs_to_sql(Ratings::get_user_privs($user));
					$rating = "(images.rating IN ($rating)) AND";
				}
				
				$status = $this->visible_status();
				if($status){
					$status = "(images.status IN ($status)) AND";
				}
				else{
					$status = "";
				}
				
				$search_mode = $config->get_string('populars_mode', 'views');
							
				$results = $database->get_all("SELECT images.id FROM images WHERE $rating $status (images.posted LIKE ?) ORDER BY images.$search_mode DESC, images.id DESC LIMIT ? OFFSET 0", array($search_date, $images));
				
				$images = array();
				foreach($results as $result) {
					$images[] = Image::by_id($result["id"]);
				}
				
				$this->theme->display_populars($images, $date);
				
				$cal["mday"] = date("d", strtotime($date));
				$cal["year"] = date("Y", strtotime($date));
				$cal["mon"] = date("m", strtotime($date));
				$this->theme->display_popular_calendar(calendar($cal, "post/popular"));
			}
			else{
				$this->theme->display_error("Error", "Malformed date.");
			}
		}
		
		if($event->page_matches("post/random")) {
			$search = $event->get_arg(0);
			
			$search_terms = array();
			if($search){
				$search_terms = explode(' ', $event->get_arg(0));
			}
			
			$image = Image::by_random($search_terms);
			if(!is_null($image)) {
				send_event(new DisplayingImageEvent($image));
				$iabbe = new ImageAdminBlockBuildingEvent($image, $user);
				send_event($iabbe);
				ksort($iabbe->parts);
				$this->theme->display_admin_block($iabbe->parts);
			}
		}
		
		if($event->page_matches("post/prev") ||	$event->page_matches("post/next")) {

			$image_id = int_escape($event->get_arg(0));

			if(isset($_GET['search'])) {
				$search_terms = explode(' ', $_GET['search']);
				$query = "#search=".url_escape($_GET['search']);
			}
			else {
				$search_terms = array();
				$query = null;
			}

			$image = Image::by_id($image_id);
			if($event->page_matches("post/next")) {
				$image = $image->get_next($search_terms);
			}
			else {
				$image = $image->get_prev($search_terms);
			}

			if(!is_null($image)) {
				$page->set_mode("redirect");
				$page->set_redirect(make_link("post/view/{$image->id}", $query));
			}
			else {
				$this->theme->display_error("Post", "No more images.");
			}
		}
		
		if($event->page_matches("post/view")) {
			$image_id = int_escape($event->get_arg(0));

			$image = Image::by_id($image_id);
	
			if(!is_null($image) && ($image->get_status_auth())) {
				send_event(new DisplayingImageEvent($image));
				$iabbe = new ImageAdminBlockBuildingEvent($image, $user);
				send_event($iabbe);
				ksort($iabbe->parts);
				$this->theme->display_admin_block($iabbe->parts);
			}
			else {
				$this->theme->display_error("Post", "No image in the database has the ID #$image_id.");
			}
		}

		if($event->page_matches("post/set")) {
			$image_id = int_escape($_POST['image_id']);

			send_event(new ImageInfoSetEvent(Image::by_id($image_id)));

			$page->set_mode("redirect");
			$page->set_redirect(make_link("post/view/$image_id", url_escape($_POST['query'])));
		}
		
		if($event->page_matches("post/ban")) {			
			$image = Image::by_id(int_escape($_POST['image_id']));
			
			if($image){
				send_event(new AddImageHashBanEvent($image->hash, html_escape($_POST['reason'])));
				
				$page->set_mode("redirect");
				$page->set_redirect(make_link("post/view/".$image->id));
			}
		}
		
		if($event->page_matches("post/status")) {
			$image_id = int_escape($_POST['image_id']);
			$action = html_escape($_POST['status']);
			
			if($user->is_admin() || $user->is_mod()){
				$image = Image::by_id($image_id);
				if($action == "l"){
					$image->set_status("l");
				}
				else if($action == "a"){
					$image->set_status("a");
				}
				else if($action == "p"){
					$image->set_status("p");
				}
				else if($action == "d"){
					$image->set_status("d");
				}
				else if($action == "h"){
					$image->set_status("h");
				}
			}

			$page->set_mode("redirect");
			$page->set_redirect(make_link("post/view/$image_id", $query));
		}
		
		
		if($event->page_matches("post/report")) {
			if(!$user->is_anon()) {
				if(isset($_POST['image_id']) && isset($_POST['reason'])) {
					$post_id = int_escape($_POST['image_id']);
					$reason = html_escape($_POST['reason']);
					
					send_event(new AlertAdditionEvent("Posts", "Reported Post: ".$reason, "post/view/".$post_id));
					
					$page->set_mode("redirect");
					$page->set_redirect(make_link("post/view/$post_id"));
				}
			}
		}
	}

	public function onSetupBuilding($event) {
		$sb = new SetupBlock("Posts Options");
		$sb->position = 20;

		$sb->add_label("Index table size:");
		$sb->add_int_option("index_width", "<br>Columns: ");
		$sb->add_int_option("index_height", "<br>Rows: ");
		
		$sb->add_int_option("index_search_max_tags","<br>Max tags in search: ");
		
		$sb->add_label("<br><br><b>Populars</b>");
		$options = array();
		$options['Views'] = 'views';
		if(class_exists("Votes")){
			$options['Votes'] = 'votes';
		}
		if(class_exists("Favorites")){
			$options['Favorites'] ='favorites';
		}
		$sb->add_choice_option("populars_mode", $options, "<br>Display by: ");
		
		$sb->add_bool_option("show_random_block", "<br>Show Random Image Block: ");

		$event->panel->add_block($sb);
		
		$sb = new SetupBlock("Tag Editing");
		$sb->add_bool_option("tag_edit_anon", "Allow anonymous tag editing: ");
		$sb->add_bool_option("source_edit_anon", "<br>Allow anonymous source editing: ");
		
		$event->panel->add_block($sb);

		$sb = new SetupBlock("Report Post Options");
		$sb->add_bool_option("report_post_enable", "Enable post reporting: ");
		$sb->add_bool_option("report_post_anon", "<br>Allow anonymous post reporting: ");
		$event->panel->add_block($sb);
	}
	
	public function onImageAdminBlockBuilding(ImageAdminBlockBuildingEvent $event) {
		global $user, $config;
		if(($config->get_bool('report_post_anon') || !$user->is_anon()) && ($config->get_bool('report_post_enable'))) {
			$event->add_part($this->theme->get_reporter_html($event->image), 70);
		}
		if($user->is_admin()|| $user->is_mod()){
			$event->add_part($this->theme->get_banner_html($event->image), 80);
			$event->add_part($this->theme->get_status_html($event->image, $event->image->status), 90);
		}
	}
		
	public function onUserPageBuilding($event) {
		$display_user = $event->display_user->name;
		if($display_user){
			$this->theme->display_recent_posts($this->recent_posts($event->display_user));
		}	
	}
	
	public function onUserBlockBuilding($event) {
		global $user, $config;
		
		$username = url_escape($user->name);		
		$event->add_link("My Posts", make_link("post/list/user=$username/1"), 10);
		
		if($user->is_admin() && $config->get_bool('report_post_enable')){
			$event->add_link("Reported Posts", make_link("post/reports"), 30);
		}
	}
		
	public function onPortalBuilding($event) {
		$this->theme->display_recent_posts($this->recent_posts());
		$this->theme->display_random_posts($this->random_posts());
	}
	
	public function onPostListBuilding($event) {
		global $config;
		if($config->get_bool("show_random_block")) {
			$image = Image::by_random($event->search_terms);
			if(!is_null($image)) {
				$this->theme->display_random_post($image);
			}
		}
	}
	
	public function onDisplayingImage(DisplayingImageEvent $event) {
		global $user, $config;
		
		$cache = $config->get_bool("admin_cache_tags", false);
		if(($cache) && (is_null($event->image->tags))){
			$event->image->set_tags_cache($event->image->get_tag_array());
		}
		
		$this->update_views($event->image);
		$iibbe = new ImageInfoBoxBuildingEvent($event->get_image(), $user);
		send_event($iibbe);
		ksort($iibbe->parts);
		$this->theme->display_post($event->get_image(), $iibbe->parts);
	}
	
	public function onImageInfoSet($event) {
		global $page;
		if($this->can_tag($event->image) && !is_null($_POST['tag_edit__tags'])) {
			send_event(new TagSetEvent($event->image, $_POST['tag_edit__tags']));
			if($this->can_source($event->image) && !is_null($_POST['tag_edit__source'])) {
				send_event(new SourceSetEvent($event->image, $_POST['tag_edit__source']));
			}
		}
		else {
			$this->theme->display_permission_denied();
		}
	}
	
	public function onSourceSet($event) {
		global $user;
		if($user->is_admin() || !$event->image->is_locked()) {
			$event->image->set_source($event->source);
		}
	}
	
	public function onTagSet($event){
		global $database;
		$matches = array();
		foreach($event->tags as $tag){
			if(preg_match("/^parent:(\d+)/", $tag, $matches)) {
				$parent_id = $matches[1];
				if($parent_id != 0){
					$database->execute("UPDATE images SET parent = ? WHERE id = ?",array($parent_id, $event->image->id));
					$database->execute("UPDATE images SET has_children = 'y' WHERE id = ?",array($parent_id));
				}
				else{
					$database->execute("UPDATE images SET parent = '0' WHERE id = ?",array($event->image->id));
					$posts_count = $database->db->GetOne("SELECT COUNT(id) FROM images WHERE parent = ?", array($event->image->parent));
					if($posts_count == 0){
						$database->execute("UPDATE images SET has_children = 'n' WHERE id = ?",array($event->image->parent));
					}
				}
			}
		}
	}
	
	public function onImageDeletion($event) {
		$event->image->delete_tags_from_image();
	}
	
	public function onImageInfoBoxBuilding($event) {
		if($this->can_tag($event->image)) {
			$event->add_part($this->theme->get_tag_editor_html($event->image), 40);
		}
		if($this->can_source($event->image)) {
			$event->add_part($this->theme->get_source_editor_html($event->image), 41);
		}
	}
	
	public function onSearchTermParse($event) {
		global $user, $page;
		
//		$tags = $event->context;
//		
//		if(count($tags)>2){
//			$this->theme->display_error("Search", "You could search up to two tags.");
//		}
		
		if(is_null($event->term) && $this->no_status_query($event->context)) {
			$status = $this->visible_status();
			if($status){
				$event->add_querylet(new Querylet("status IN ($status)"));
			}
		}
								
		$matches = array();
		if(preg_match("/^size(<|>|:<|:>|:)(\d+)x(\d+)$/", $event->term, $matches)) {
			$cmp = $matches[1];
			$cmp = strrev(str_replace(":", "=", $cmp));
			$args = array(int_escape($matches[2]), int_escape($matches[3]));
			$event->add_querylet(new Querylet("width $cmp ? AND height $cmp ?", $args));
		}
		else if(preg_match("/^ratio(<|>|<:|>:|:)(\d+):(\d+)$/", $event->term, $matches)) {
			$cmp = $matches[1];
			$cmp = strrev(str_replace(":", "=", $cmp));
			$args = array(int_escape($matches[2]), int_escape($matches[3]));
			$event->add_querylet(new Querylet("width / height $cmp ? / ?", $args));
		}
		else if(preg_match("/^(filesize|id)(<|>|<:|>:|:)(\d+[kmg]?b?)$/i", $event->term, $matches)) {
			$col = $matches[1];
			$cmp = $matches[2];
			$cmp = strrev(str_replace(":", "=", $cmp));
			$val = parse_shorthand_int($matches[3]);
			$event->add_querylet(new Querylet("images.$col $cmp ?", array($val)));
		}
		else if(preg_match("/^(poster|user):(.*)$/i", $event->term, $matches)) {
			$user = User::by_name($matches[2]);
			if(!is_null($user)) {
				$user_id = $user->id;
				$event->add_querylet(new Querylet("images.owner_id = '$user_id'"));
			}
		}
		else if(preg_match("/^(hash|md5):([0-9a-fA-F]*)$/i", $event->term, $matches)) {
			$hash = strtolower($matches[2]);
			$event->add_querylet(new Querylet("images.hash = '$hash'"));
		}
		else if(preg_match("/^(filetype|ext):([a-zA-Z0-9]*)$/i", $event->term, $matches)) {
			$ext = strtolower($matches[2]);
			$event->add_querylet(new Querylet("images.ext = '$ext'"));
		}
		else if(preg_match("/^(filename|name):([a-zA-Z0-9]*)$/i", $event->term, $matches)) {
			$filename = strtolower($matches[2]);
			$event->add_querylet(new Querylet("images.filename LIKE '%$filename%'"));
		}
		else if(preg_match("/^posted:(([0-9\*]*)?(-[0-9\*]*)?(-[0-9\*]*)?)$/", $event->term, $matches)) {
			$val = str_replace("*", "%", $matches[1]);
			$event->add_querylet(new Querylet("images.posted LIKE '%$val%'"));
		}
		else if(preg_match("/tags(<|>|<:|>:|:)(\d+)/", $event->term, $matches)) {
			$cmp = $matches[1];
			$cmp = strrev(str_replace(":", "=", $cmp));
			$tags = $matches[2];
			$event->add_querylet(new Querylet("images.id IN (SELECT DISTINCT image_id FROM image_tags GROUP BY image_id HAVING count(image_id) $cmp $tags)"));
		}
		else if(preg_match("/^parent:(\d+)$/", $event->term, $matches)) {
			$parent = $matches[1];
			$event->add_querylet(new Querylet("parent = ?", array($parent)));
		}
		else if(preg_match("/^status:(l|a|p|d|h)$/", $event->term, $matches)) {
			$status = $matches[1];
			if($user->is_admin() || $user->is_mod()){
				$event->add_querylet(new Querylet("status = ?", array($status)));
			}
		}
	}
	
	private function no_status_query($context) {
		foreach($context as $term) {
			if(preg_match("/^status:/", $term)) {
				return false;
			}
		}
		return true;
	}
	
	public static function visible_status() {
		global $config, $user;
		
		$image_status = Image::get_status_auth_str();
		
		$arr = array();
		for($i=0; $i<strlen($image_status); $i++) {
			$arr[] = "'" . $image_status[$i] . "'";
		}
		$set = join(', ', $arr);
		
		if($image_status){
			return $set;
		}
		else{
			return "";
		}
	}
	
	private function recent_posts($duser=null){
		global $config;
		$max_images = $config->get_int('index_width');
		if(!is_null($duser)) {
			return Image::find_images(0, $max_images, array("user=".$duser->name));
		} else {
			return Image::find_images(0, $max_images);
		}
	}
	
	private function random_posts(){
		global $config;
		$max_images = $config->get_int('index_width');
		$all_images = Image::count_images();
		if($all_images < $max_images) {$max_images = $all_images;}
		$images = array();
		while(count($images)<$max_images) {
			$tmp_image = Image::by_random();
			if(!in_array($tmp_image, $images)) {
				$images[] = $tmp_image;
			}
		}
		return $images;
	}
	
	public function update_views($image) {
		global $database, $user;
		$viewed = $database->db->GetOne("SELECT COUNT(*) FROM image_views WHERE image_id = ? AND user_id = ?",array($image->id, $user->id));
		if($viewed < 1){
			$database->Execute("INSERT INTO image_views(image_id, user_id) VALUES(?, ?)",array($image->id, $user->id));
			$database->Execute("UPDATE images SET views=(SELECT COUNT(*) FROM image_views WHERE image_id = ?) WHERE id = ?",array($image->id, $image->id));
		}
	}
	
	private function can_tag($image) {
		global $config, $user;
		return (($config->get_bool("tag_edit_anon") || !$user->is_anon()) && ($user->is_admin() || !$image->is_locked()));
	}

	private function can_source($image) {
		global $config, $user;
		return (($config->get_bool("source_edit_anon") || !$user->is_anon()) &&	($user->is_admin() || !$image->is_locked()));
	}
}
?>
