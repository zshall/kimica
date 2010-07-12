<?php
/*
 * Name: Image Viewer
 * Author: Shish
 * Description: Allows users to see uploaded images
 */

/*
 * DisplayingImageEvent:
 *   $image -- the image being displayed
 *   $page  -- the page to display on
 *
 * Sent when an image is ready to display. Extensions who
 * wish to appear on the "view" page should listen for this,
 * which only appears when an image actually exists.
 */
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

class ViewImage extends SimpleExtension {
	public function onPageRequest(PageRequestEvent $event) {
		global $page, $user, $database;

		if(
			$event->page_matches("post/prev") ||
			$event->page_matches("post/next")
		) {

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
				$this->theme->display_error($page, "Image not found", "No more images");
			}
		}
			
		if($event->page_matches("post/view")) {
			$image_id = int_escape($event->get_arg(0));

			$image = Image::by_id($image_id);
	
			if(!is_null($image) && ($image->is_approved() || $image->is_locked() || ($user->is_admin() || $user->is_mod()))) {
				send_event(new DisplayingImageEvent($image));
				$iabbe = new ImageAdminBlockBuildingEvent($image, $user);
				send_event($iabbe);
				ksort($iabbe->parts);
				$this->theme->display_admin_block($iabbe->parts);
			}
			else {
				$this->theme->display_error($page, "Image not found", "No image in the database has the ID #$image_id");
			}
		}

		if($event->page_matches("post/set")) {
			$image_id = int_escape($_POST['image_id']);

			send_event(new ImageInfoSetEvent(Image::by_id($image_id)));

			$query = $_POST['query'];
			$page->set_mode("redirect");
			$page->set_redirect(make_link("post/view/$image_id", $query));
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
			}

			$page->set_mode("redirect");
			$page->set_redirect(make_link("post/view/$image_id", $query));
		}
		
	}
	
	public function onImageAdminBlockBuilding(ImageAdminBlockBuildingEvent $event) {
		global $user;
		if($user->is_admin()|| $user->is_mod()){
			$event->add_part($this->theme->get_status_html($event->image, $event->image->status));
		}
	}

	public function onDisplayingImage(DisplayingImageEvent $event) {
		global $user;
		$this->update_views($event->image);
		$iibbe = new ImageInfoBoxBuildingEvent($event->get_image(), $user);
		send_event($iibbe);
		ksort($iibbe->parts);
		$this->theme->display_page($event->get_image(), $iibbe->parts);
	}
	
	public function update_views($image) {
		global $database, $user;
		$database->Execute("DELETE FROM image_views WHERE image_id = ? AND user_id = ?",array($image->id, $user->id));
		$database->Execute("INSERT INTO image_views(image_id, user_id) VALUES(?, ?)",array($image->id, $user->id));
		$database->Execute("UPDATE images SET views=(SELECT COUNT(*) FROM image_views WHERE image_id = ?) WHERE id = ?",array($image->id, $image->id));
	}
}

// Tag Edit Starts

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

class TagEdit implements Extension {
	var $theme;

	public function receive_event(Event $event) {
		global $config, $database, $page, $user;
		if(is_null($this->theme)) $this->theme = get_theme_object($this);

		if($event instanceof ImageInfoSetEvent) {
			if($this->can_tag($event->image) && !is_null($_POST['tag_edit__tags'])) {
				send_event(new TagSetEvent($event->image, $_POST['tag_edit__tags']));
				if($this->can_source($event->image) && !is_null($_POST['tag_edit__source'])) {
					send_event(new SourceSetEvent($event->image, $_POST['tag_edit__source']));
				}
			}
			else {
				$this->theme->display_error($page, "Error", "Anonymous tag editing is disabled");
			}
		}

		if($event instanceof TagSetEvent) {
			if($user->is_admin() || !$event->image->is_locked()) {
				$event->image->set_tags($event->tags);
			}
		}

		if($event instanceof SourceSetEvent) {
			if($user->is_admin() || !$event->image->is_locked()) {
				$event->image->set_source($event->source);
			}
		}

		if($event instanceof ImageDeletionEvent) {
			$event->image->delete_tags_from_image();
		}
		
		if($event instanceof ImageInfoBoxBuildingEvent) {
			if($this->can_tag($event->image)) {
				$event->add_part($this->theme->get_tag_editor_html($event->image), 40);
			}
			if($this->can_source($event->image)) {
				$event->add_part($this->theme->get_source_editor_html($event->image), 41);
			}
		}

		if($event instanceof SetupBuildingEvent) {
			$sb = new SetupBlock("Tag Editing");
			$sb->add_bool_option("tag_edit_anon", "Allow anonymous tag editing: ");
			$sb->add_bool_option("source_edit_anon", "<br>Allow anonymous source editing: ");
			$event->panel->add_block($sb);
		}
	}


	private function can_tag($image) {
		global $config, $user;
		return (
			($config->get_bool("tag_edit_anon") || !$user->is_anonymous()) &&
			($user->is_admin() || !$image->is_locked())
			);
	}

	private function can_source($image) {
		global $config, $user;
		return (
			($config->get_bool("source_edit_anon") || !$user->is_anonymous()) &&
			($user->is_admin() || !$image->is_locked())
			);
	}
}
add_event_listener(new TagEdit());
?>