<?php
/*
 * Name: Image Manager
 * Author: Shish
 * Description: Handle the image database
 * Visibility: admin
 */

/*
 * ImageAdditionEvent:
 *   $user  -- the user adding the image
 *   $image -- the image being added
 *
 * An image is being added to the database
 */
class ImageAdditionEvent extends Event {
	var $user, $image;

	public function ImageAdditionEvent(User $user, Image $image) {
		$this->image = $image;
		$this->user = $user;
	}
}

class ImageAdditionException extends SCoreException {
	var $error;

	public function __construct($error) {
		$this->error = $error;
	}
}

/*
 * ImageDeletionEvent:
 *   $image -- the image being deleted
 *
 * An image is being deleted. Used by things like tags
 * and comments handlers to clean out related rows in
 * their tables
 */
class ImageDeletionEvent extends Event {
	var $image;

	public function ImageDeletionEvent(Image $image) {
		$this->image = $image;
	}
}


/*
 * ImageTagBanEvent:
 *   $image -- the image being deleted
 *
 * An image is being deleted. Used by things like tags
 * and comments handlers to clean out related rows in
 * their tables
 */
class ImageTagBanEvent extends Event {
	var $user, $image;

	public function ImageTagBanEvent(User $user, Image $image) {
		$this->image = $image;
		$this->user = $user;
	}
}

/*
 * ImageTagUnBanEvent:
 *   $image -- the image being deleted
 *
 * An image is being deleted. Used by things like tags
 * and comments handlers to clean out related rows in
 * their tables
 */
class ImageTagUnBanEvent extends Event {
	var $image;

	public function ImageTagUnBanEvent(Image $image) {
		$this->image = $image;
	}
}


/*
 * ThumbnailGenerationEvent:
 * Request a thumb be made for an image
 */
class ThumbnailGenerationEvent extends Event {
	var $hash, $type;

	public function ThumbnailGenerationEvent($hash, $type) {
		$this->hash = $hash;
		$this->type = $type;
	}
}


/*
 * ParseLinkTemplateEvent:
 *   $link     -- the formatted link
 *   $original -- the formatting string, for reference
 *   $image    -- the image who's link is being parsed
 */
class ParseLinkTemplateEvent extends Event {
	var $link, $original;
	var $image;

	public function ParseLinkTemplateEvent($link, Image $image) {
		$this->link = $link;
		$this->original = $link;
		$this->image = $image;
	}

	public function replace($needle, $replace) {
		$this->link = str_replace($needle, $replace, $this->link);
	}
}


/*
 * A class to handle adding / getting / removing image
 * files from the disk
 */
class ImageIO extends SimpleExtension {
	public function onInitExt($event) {
		global $config;
		$config->set_default_int('thumb_width', 150);
		$config->set_default_int('thumb_height', 150);
		$config->set_default_int('thumb_quality', 75);
		$config->set_default_int('thumb_mem_limit', parse_shorthand_int('8MB'));
		$config->set_default_string('thumb_convert_path', 'convert.exe');

		$config->set_default_bool('image_show_meta', false);
		$config->set_default_string('image_ilink', '');
		$config->set_default_string('image_tlink', '');
		$config->set_default_string('image_tip', '$tags');
		$config->set_default_string('upload_collision_handler', 'error');
	}

	public function onPageRequest($event) {
		global $page, $user;
		$num = $event->get_arg(0);
		
		if(!is_null($num)) {
			if($event->page_matches("image")) {
				$this->send_file($num, "image");
			}
			else if($event->page_matches("thumb")) {
				$this->send_file($num, "thumb");
			}
		}
		if($event->page_matches("image_admin/delete")) {
			if($user->is_admin() && isset($_POST['image_id'])) {
				$image = Image::by_id($_POST['image_id']);
				if($image) {
					send_event(new ImageDeletionEvent($image));
					$page->set_mode("redirect");
					$page->set_redirect(make_link("post/list"));
				}
			}
		}
		if($event->page_matches("image_admin/regen")) {
			if($user->is_admin() && isset($_POST['image_id'])) {
				$image = Image::by_id(int_escape($_POST['image_id']));
				if($image) {
					send_event(new ThumbnailGenerationEvent($image->hash, $image->ext));
					$this->theme->display_results($page, $image);
				}
			}
		}
		if($event->page_matches("image_admin/warehouse")) {
			if($user->is_admin() && isset($_POST['image_id'])) {
				$image = Image::by_id(int_escape($_POST['image_id']));
				if($image) {
					if(!warehouse_file(warehouse_path('images', $image->hash, 'local'), $image->hash, $image->ext)){
						 $this->theme->display_error($page, "Error", "Image could not be warehoused.");
					};
					if(!warehouse_thumb(warehouse_path('thumbs', $image->hash, 'local'), $image->hash, $image->ext)){
						 $this->theme->display_error($page, "Error", "Thumb could not be warehoused.");
					};
					
					$image->set_warehoused();
					
					$page->set_mode("redirect");
					$page->set_redirect(make_link("post/view/".$image->id));
				}
			}
		}
	}

	public function onImageAdminBlockBuilding($event) {
		global $config, $user;
		$backup_method = $config->get_string('warehouse_method','local_hierarchy');
		$methods = explode("_",$backup_method);
		
		if($user->is_admin()) {
			$event->add_part($this->theme->get_deleter_html($event->image->id));
		}
		if($user->is_admin()) {
			if(supported_ext($event->image->ext)) {
				$event->add_part($this->theme->get_regen_html($event->image->id));
			}
		}
		if($user->is_admin() && (!$event->image->is_warehoused()) && in_array('amazon', $methods)) {
			$event->add_part($this->theme->get_warehouse_html($event->image->id));
		}
	}

	public function onImageAddition($event) {
		try {
			$this->add_image($event->image);
		}
		catch(ImageAdditionException $e) {
			throw new UploadException($e->error);
		}
	}

	public function onImageDeletion($event) {
		$event->image->delete();
		
		if(class_exists("Backups")){
			send_event(new BackupDeletionEvent($event->image));
		}
	}
	
	public function onThumbnailGeneration($event) {
		if(supported_ext($event->type)) {
			$inname  = warehouse_path("images", $event->hash);
			$outname = warehouse_path("thumbs", $event->hash);
			
			create_thumb($inname, $outname);
		}
	}
	
	public function onImageTagBan($event) {
		global $database;
		foreach ($event->image->get_tag_array() as $banned) {
			$is_banned = $database->db->GetOne("SELECT COUNT(*) FROM tag_bans WHERE tag = ?", array($banned)) > 0;
			if($is_banned){
				$row = $database->db->GetRow("SELECT status FROM tag_bans WHERE tag = ?", $banned);
				$event->image->set_status($row["status"]);
			}
		}
	}
	
	public function onImageTagUnBan($event) {
		global $database;
		foreach ($event->image->get_tag_array() as $image) {
			$event->image->set_status("a");
		}
	}
	
	public function onUserPageBuilding($event) {
		$u_id = url_escape($event->display_user->id);
		$i_image_count = Image::count_images(array("user_id={$event->display_user->name}"));
		$images_link = make_link("post/list/user=$u_id/1");
		$event->add_stats(array("<a href='$images_link'>Posts</a>", "$i_image_count"), 40);
	}

	public function onSetupBuilding($event) {
		$sb = new SetupBlock("Image Options");
		$sb->position = 30;
		// advanced only
		//$sb->add_text_option("image_ilink", "Image link: ");
		//$sb->add_text_option("image_tlink", "<br>Thumbnail link: ");
		$sb->add_text_option("image_tip", "Image tooltip: ");
		$sb->add_choice_option("upload_collision_handler", array('Error'=>'error', 'Merge'=>'merge'), "<br>Upload collision handler: ");
		
		if(!in_array("OS", $_SERVER) || $_SERVER["OS"] != 'Windows_NT') {
			$sb->add_bool_option("image_show_meta", "<br>Show metadata: ");
		}
		
		$event->panel->add_block($sb);

		$thumbers = array();
		$thumbers['Built-in GD'] = "gd";
		$thumbers['ImageMagick'] = "convert";

		$sb = new SetupBlock("Thumbnailing");
		$sb->add_choice_option("thumb_engine", $thumbers, "Engine: ");

		$sb->add_label("<br>Thumb Width: ");
		$sb->add_int_option("thumb_width");
		$sb->add_label("<br>Thumb Height: ");
		$sb->add_int_option("thumb_height");
		$sb->add_label("<br>Thumb Quality: ");
		$sb->add_int_option("thumb_quality");

		$sb->add_shorthand_int_option("thumb_mem_limit", "<br>Max memory use: ");
		$event->panel->add_block($sb);
	}


// add image {{{
	private function add_image($image) {
		global $page;
		global $user;
		global $database;
		global $config;

		/*
		 * Validate things
		 */
		if(strlen(trim($image->source)) == 0) {
			$image->source = null;
		}
		if(!empty($image->source)) {
			if(!preg_match("#^(https?|ftp)://#", $image->source)) {
				throw new ImageAdditionException("Image's source isn't a valid URL");
			}
		}

		/*
		 * Check for an existing image
		 */
		$existing = Image::by_hash($image->hash);
		if(!is_null($existing)) {
			$handler = $config->get_string("upload_collision_handler");
			if($handler == "merge") {
				$merged = array_merge($image->get_tag_array(), $existing->get_tag_array());
				send_event(new TagSetEvent($existing, $merged));
				return null;
			}
			else {
				$error = "Image <a href='".make_link("post/view/{$existing->id}")."'>{$existing->id}</a> ".
						"already has hash {$image->hash}:<p>".Themelet::build_thumb_html($existing);
				throw new ImageAdditionException($error);
			}
		}
		
		/*
		* Check for user roles or if the image contains a banned tag. It set the image as approved or deleted for review.
		*/
		if($user->get_auth_from_str($config->get_string("upload_autoapprove"))){
			$auto_aprove = "a";
		}
		else{
			$auto_aprove = "p";
		}
		
		foreach ($image->get_tag_array() as $banned) {
			$is_banned = $database->db->GetOne("SELECT COUNT(*) FROM tag_bans WHERE tag = ?",array($banned)) > 0;
			if($is_banned){
				$row = $database->db->GetRow("SELECT status FROM tag_bans WHERE tag = ?", $banned);
				$auto_aprove = $row["status"];
			}
		}
		
		//If it was uploaded sucessfully then set as warehoused.
		$backup_method = $config->get_string('warehouse_method','local_hierarchy');
		$methods = explode("_",$backup_method);
		
		$warehoused = "n";
		if(in_array('amazon', $methods)){
			$warehoused = "y";
		}

		// actually insert the info
		$database->Execute(
				"INSERT INTO images(
					owner_id, owner_ip, filename, filesize,
					hash, ext, width, height, posted, source, status, warehoused)
				VALUES (?, ?, ?, ?, ?, ?, ?, ?, now(), ?, ?, ?)",
				array($user->id, $_SERVER['REMOTE_ADDR'], $image->filename, $image->filesize,
						$image->hash, $image->ext, $image->width, $image->height, $image->source, $auto_aprove, $warehoused));
		if($database->engine->name == "pgsql") {
			$database->Execute("UPDATE users SET image_count = image_count+1 WHERE id = ? ", array($user->id));
			$image->id = $database->db->GetOne("SELECT id FROM images WHERE hash=?", array($image->hash));
		}
		else {
			$image->id = $database->db->Insert_ID();
		}

		log_info("image", "Uploaded Image #{$image->id} ({$image->hash})");

		# at this point in time, the image's tags haven't really been set,
		# and so, having $image->tag_array set to something is a lie (but
		# a useful one, as we want to know what the tags are /supposed/ to
		# be). Here we correct the lie, by first nullifying the wrong tags
		# then using the standard mechanism to set them properly.
		$tags_to_set = $image->get_tag_array();
		$image->tag_array = array();
		send_event(new TagSetEvent($image, $tags_to_set));
	}
// }}}
// fetch image {{{
	private function send_file($image_arg, $type) {
		global $config;
		global $user;
		global $database;
				
		$matches = array();
		if(preg_match("/(\d+)/", $image_arg, $matches)){
			$image = Image::by_id($matches[1]);
		}
		
		if(preg_match("/([0-9a-fA-F]{32})/", $image_arg, $matches)){
			$image = Image::by_hash($matches[1]);
		}

		global $page;
		if(!is_null($image) && ($image->is_approved() || $image->is_locked() || $user->is_admin() || $user->is_mod())) {
			$page->set_mode("data");
			if($type == "thumb") {
				$page->set_type("image/jpeg");
				$file = $image->get_thumb_filename();
			}
			else {
				$page->set_type($image->get_mime_type());
				$file = $image->get_image_filename();
			}

			$page->set_data(file_get_contents($file));

			if(isset($_SERVER["HTTP_IF_MODIFIED_SINCE"])) {
				$if_modified_since = preg_replace('/;.*$/', '', $_SERVER["HTTP_IF_MODIFIED_SINCE"]);
			}
			else {
				$if_modified_since = "";
			}
			$gmdate_mod = gmdate('D, d M Y H:i:s', filemtime($file)) . ' GMT';

			// FIXME: should be $page->blah
			if($if_modified_since == $gmdate_mod) {
				header("HTTP/1.0 304 Not Modified");
			}
			else {
				header("Last-Modified: $gmdate_mod");
				header("Expires: Fri, 2 Sep 2101 12:42:42 GMT"); // War was beginning
			}
		}
		else {
			$page->set_title("Not Found");
			$page->set_heading("Not Found");
			$page->add_block(new Block("Navigation", "<a href='".make_link()."'>Index</a>", "left", 0));
			$page->add_block(new Block("Image not in database",
					"The requested image was not found in the database"));
		}
	}
// }}}
}
?>
