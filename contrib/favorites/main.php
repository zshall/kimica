<?php
/*
 * Name: Favorites
 * Author: Daniel Marschall <info@daniel-marschall.de>
 * License: GPLv2
 * Description: Allow users to favorite images
 * Documentation:
 *  Gives users a "favorite this image" button that they can press
 *  <p>Favorites for a user can then be retrieved by searching for
 *  "favorited_by=UserName"
 *  <p>Popular images can be searched for by eg. "favorites>5"
 *  <p>Favorite info can be added to an image's filename or tooltip
 *  using the $favorites placeholder
 */

class FavoriteSetEvent extends Event {
	var $image_id, $user, $do_set;

	public function FavoriteSetEvent($image_id, User $user, $do_set) {
		assert(is_numeric($image_id));
		assert(is_bool($do_set));

		$this->image_id = $image_id;
		$this->user = $user;
		$this->do_set = $do_set;
	}
}

class Favorites extends SimpleExtension {

	public function onInitExt($event) {
		global $config, $database;	
		
		if($config->get_int("ext_favorites_version") < 1) {
			$database->Execute("ALTER TABLE images ADD COLUMN favorites INTEGER NOT NULL DEFAULT 0");
			$database->Execute("CREATE INDEX images_favorites ON images(favorites)");
			$database->Execute("
				CREATE TABLE user_favorites (
					image_id INTEGER NOT NULL,
					user_id INTEGER NOT NULL,
					created_at DATETIME NOT NULL,
					UNIQUE(image_id, user_id),
					INDEX(image_id),
					INDEX(user_id),
					FOREIGN KEY (image_id) REFERENCES images(id) ON DELETE CASCADE,
					FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
				)
			");
			$config->set_int("ext_favorites_version", 1);
		}
		
		if(($config->get_int("ext_favorites_version") < 2) && ($database->engine->name == "mysql")){
			$database->Execute("CREATE TRIGGER update_favorites_on_insert AFTER INSERT ON user_favorites FOR EACH ROW UPDATE images SET favorites = (SELECT COUNT(*) FROM user_favorites WHERE image_id = NEW.image_id) WHERE images.id = NEW.image_id");
			$database->Execute("CREATE TRIGGER update_favorites_on_delete AFTER DELETE ON user_favorites FOR EACH ROW UPDATE images SET favorites = (SELECT COUNT(*) FROM user_favorites WHERE image_id = OLD.image_id) WHERE images.id = OLD.image_id");
			$config->set_bool("favorites_hide_users", false);
			$config->set_int("ext_favorites_version", 2);
		}
	}

	public function onImageAdminBlockBuilding($event) {
		global $database, $page, $user;
		if(!$user->is_anon()) {
			$user_id = $user->id;
			$image_id = $event->image->id;

			$is_favorited = $database->db->GetOne(
				"SELECT COUNT(*) AS ct FROM user_favorites WHERE user_id = ? AND image_id = ?",
				array($user_id, $image_id)) > 0;
		
			$event->add_part($this->theme->get_voter_html($event->image, $is_favorited));
		}
	}

	public function onDisplayingImage($event) {
		global $config;
		$private = $config->get_bool("favorites_hide_users", false);
		$people = $this->list_persons_who_have_favorited($event->image);
		if((count($people) > 0) && (!$private)) {
			$html = $this->theme->display_people($people);
		}
	}

	public function onPageRequest($event) {
		global $page, $user;
		if($event->page_matches("post/favorite") && !$user->is_anon()) {
			$image_id = int_escape($_POST['image_id']);
			if ((($_POST['favorite_action'] == "set") || ($_POST['favorite_action'] == "unset")) && ($image_id > 0)) {
				send_event(new FavoriteSetEvent($image_id, $user, ($_POST['favorite_action'] == "set")));
			}
			$page->set_mode("redirect");
			$page->set_redirect(make_link("post/view/$image_id"));
		}
	}

	public function onUserPageBuilding($event) {
		$i_favorites_count = Image::count_images(array("favorited_by={$event->display_user->name}"));
		$favorites_link = make_link("post/list/favorited_by={$event->display_user->name}/1");
		$event->add_stats(array("<a href='$favorites_link'>Favorites</a>", "$i_favorites_count"),60);
		
		$this->theme->display_recent_favorites($this->latest_favorites($event->display_user));
	}
	
	public function onPortalBuilding($event) {		
		$this->theme->display_recent_favorites($this->latest_favorites());
	}

	public function onFavoriteSet($event) {
		global $user;
		$this->add_vote($event->image_id, $user->id, $event->do_set);
	}

	public function onImageDeletion($event) {
		global $database;
		$database->execute("DELETE FROM user_favorites WHERE image_id=?", array($event->image->id));
	}

	public function onParseLinkTemplate($event) {
		$event->replace('$favorites', $event->image->favorites);
	}

	public function onUserBlockBuilding($event) {
		global $user;

		$username = url_escape($user->name);
		$event->add_link("My Favorites", make_link("post/list/favorited_by=$username/1"), 20);
	}

	public function onSearchTermParse($event) {
		$matches = array();
		if(preg_match("/favorites(<|>|:<|:>|:)(\d+)/", $event->term, $matches)) {
			$cmp = $matches[1];
			$cmp = strrev(str_replace(":", "=", $cmp));
			$favorites = $matches[2];
			$event->add_querylet(new Querylet("images.id IN (SELECT id FROM images WHERE favorites $cmp $favorites)"));
		}
		else if(preg_match("/favorited_by:(.*)/i", $event->term, $matches)) {
			global $database;
			$user = User::by_name($matches[1]);
			if(!is_null($user)) {
				$user_id = $user->id;
			}
			else {
				$user_id = -1;
			}

			$event->add_querylet(new Querylet("images.id IN (SELECT image_id FROM user_favorites WHERE user_id = $user_id)"));
		}
		else if(preg_match("/favorited_by_id:([0-9]+)/i", $event->term, $matches)) {
			$user_id = int_escape($matches[1]);
			$event->add_querylet(new Querylet("images.id IN (SELECT image_id FROM user_favorites WHERE user_id = $user_id)"));
		}
	}

	private function add_vote($image_id, $user_id, $do_set) {
		global $database, $config;
			
		if ($do_set) {
			$is_favorited = $database->db->GetOne("SELECT COUNT(*) AS ct FROM user_favorites WHERE user_id = ? AND image_id = ?",	array($user_id, $image_id)) > 0;
			if (!$is_favorited) {
				$database->Execute("INSERT INTO user_favorites(image_id, user_id, created_at) VALUES(?, ?, NOW())", array($image_id, $user_id));
			}
		} else {
			$database->Execute("DELETE FROM user_favorites WHERE image_id = ? AND user_id = ?",	array($image_id, $user_id));
		}
		
		
		if($database->engine->name != "mysql"){
			$database->Execute("UPDATE images SET favorites=(SELECT COUNT(*) FROM user_favorites WHERE image_id=?) WHERE id=?",	array($image_id, $image_id));
		}
	}
	
	private function list_persons_who_have_favorited($image) {
		global $database;

		$result = $database->execute(
				"SELECT name FROM users WHERE id IN (SELECT user_id FROM user_favorites WHERE image_id = ?) ORDER BY name",
				array($image->id));
				
		return $result->GetArray();
	}
	
	private function latest_favorites($duser=null){
		global $config, $user, $database;
		
		$max_images = $config->get_int('index_width');

		if(class_exists("Ratings")) {
			$rating = Ratings::privs_to_sql(Ratings::get_user_privs($user));
			if(!is_null($duser)) {
				$result = $database->get_all("
						SELECT fav.image_id
						FROM user_favorites AS fav
						INNER JOIN images AS img ON img.id = fav.image_id
						WHERE fav.user_id = ? AND img.rating IN ($rating)
						ORDER BY fav.created_at DESC
						LIMIT ?",
						array($duser->id, $max_images));
			} else {
				$result = $database->get_all("
						SELECT fav.image_id
						FROM user_favorites AS fav
						INNER JOIN images AS img ON img.id = fav.image_id
						WHERE img.rating IN ($rating)
						ORDER BY fav.created_at DESC
						LIMIT ?",
						array($max_images));
			}
		}
		else{
			if(!is_null($duser)) {
				$result = $database->get_all("SELECT image_id FROM user_favorites WHERE user_id = ? ORDER BY created_at DESC LIMIT ?",array($duser->id, $max_images));
			} else {
				$result = $database->get_all("SELECT image_id FROM user_favorites ORDER BY created_at DESC LIMIT ?",array($max_images));
			}
		}
		
		$images = array();
		foreach($result as $singleResult) {
			$images[] = Image::by_id($singleResult["image_id"]);
		}
		
		return $images;
	}
}
?>