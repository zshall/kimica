<?php
/**
 * Name: Pools System
 * Author: Sein Kraft <mail@seinkraft.info>
 * License: GPLv2
 * Description: Allow users to create groups of images
 * Documentation:
 */

class PoolCreationException extends SCoreException {
}

class Pools extends SimpleExtension {
	public function onInitExt($event) {
		global $config, $database;

		if ($config->get_int("ext_pools_version") < 1){
			$database->create_table("pools", "
					id SCORE_AIPK,
					user_id INTEGER NOT NULL,
					public SCORE_BOOL NOT NULL DEFAULT SCORE_BOOL_N,
					title VARCHAR(255) NOT NULL,
					description TEXT,
					date DATETIME NOT NULL,
					posts INTEGER NOT NULL DEFAULT 0,
					INDEX (id)
					");
			$database->create_table("pool_images", "
					pool_id INTEGER NOT NULL,
					image_id INTEGER NOT NULL,
					image_order INTEGER NOT NULL DEFAULT 0
					");
			$database->create_table("pool_history", "
					id SCORE_AIPK,
					pool_id INTEGER NOT NULL,
					user_id INTEGER NOT NULL,
					action INTEGER NOT NULL,
					images TEXT,
					count INTEGER NOT NULL DEFAULT 0,
					date DATETIME NOT NULL,
					INDEX (id)
					");

			$config->set_int("ext_pools_version", 1);

			$config->set_int("poolsMaxImportResults", 1000);
			$config->set_int("poolsImagesPerPage", 20);
			$config->set_int("poolsListsPerPage", 20);
			$config->set_int("poolsUpdatedPerPage", 20);
			$config->set_bool("poolsInfoOnViewImage", "N");
			$config->set_bool("poolsAdderOnViewImage", "N");

			log_info("pools", "extension installed");
		}
	}

	public function onSetupBuilding(SetupBuildingEvent $event) {
		$sb = new SetupBlock("Pools");
		$sb->add_int_option("poolsMaxImportResults", "Max results on import: ");
		$sb->add_int_option("poolsImagesPerPage", "<br>Images per page: ");
		$sb->add_int_option("poolsListsPerPage", "<br>Index list items per page: ");
		$sb->add_int_option("poolsUpdatedPerPage", "<br>Updated list items per page: ");
		$sb->add_bool_option("poolsInfoOnViewImage", "<br>Show pool info on image: ");
		//$sb->add_bool_option("poolsAdderOnViewImage", "<br>Show pool adder on image: ");
		$event->panel->add_block($sb);
	}

	public function onPageRequest($event) {
		global $config, $page, $user;

		if($event->page_matches("pool")) {
			$this->theme->pool_navigation();
			
			switch($event->get_arg(0)) {
				case "list": //index
					$this->list_pools($page, int_escape($event->get_arg(1)));
					break;

				case "new": // Show form
					if(!$user->is_anon()){
						$this->theme->new_pool_composer($page);
					} else {
						$errMessage = "You must be registered and logged in to create a new pool.";
						$this->theme->display_error($errMessage);
					}
					break;

				case "create": // ADD _POST
					try {
						$newPoolID = $this->add_pool();
						$page->set_mode("redirect");
						$page->set_redirect(make_link("pool/view/".$newPoolID));
					}
					catch(PoolCreationException $pce) {
						$this->theme->display_error($pce->getMessage());
					}
					break;

				case "view":
					$poolID = int_escape($event->get_arg(1));
					$this->get_posts($event, $poolID);
					break;

				case "history":
					$this->get_history(int_escape($event->get_arg(1)));
					break;

				case "revert":
					if(!$user->is_anon()) {
						$historyID = int_escape($event->get_arg(1));
						$this->revert_history($historyID);
						$page->set_mode("redirect");
						$page->set_redirect(make_link("pool/updated"));
					}
					break;

				case "edit":
					$poolID = int_escape($event->get_arg(1));
					$pools = $this->get_pool($poolID);

					foreach($pools as $pool) {
						// if the pool is public and user is logged OR if the user is admin OR the user is the owner
						if(($pool['public'] == "Y" && !$user->is_anon()) || $user->is_admin() || $user->id == $pool['user_id']) {
							$this->theme->edit_pool($page, $this->get_pool($poolID), $this->edit_posts($poolID));
						} else {
							$page->set_mode("redirect");
							$page->set_redirect(make_link("pool/view/".$poolID));
						}
					}
					break;

				case "order":
					if($_SERVER["REQUEST_METHOD"] == "GET") {
						$poolID = int_escape($event->get_arg(1));
						$pools = $this->get_pool($poolID);

						foreach($pools as $pool) {
							//if the pool is public and user is logged OR if the user is admin
							if(($pool['public'] == "Y" && !$user->is_anon()) || $user->is_admin() || $user->id == $pool['user_id']) {
								$this->theme->edit_order($page, $this->get_pool($poolID), $this->edit_order($poolID));
							} else {
								$page->set_mode("redirect");
								$page->set_redirect(make_link("pool/view/".$poolID));
							}
						}
					}
					else {
						$pool_id = int_escape($_POST["pool_id"]);
						$pool = $this->get_single_pool($pool_id);

						if(($pool['public'] == "Y" && !$user->is_anon()) || $user->is_admin() || $user->id == $pool['user_id']) {
							$this->order_posts();
							$page->set_mode("redirect");
							$page->set_redirect(make_link("pool/view/".$pool_id));
						} else {
							$this->theme->display_error("Permssion denied.");
						}
					}
					break;

				case "import":
					$pool_id = int_escape($_POST["pool_id"]);
					$pool = $this->get_single_pool($pool_id);

					if(($pool['public'] == "Y" && !$user->is_anon()) || $user->is_admin() || $user->id == $pool['user_id']) {
						$this->import_posts();
					} else {
						$this->theme->display_error("Permssion denied.");
					}
					break;

				case "add_posts":
					$pool_id = int_escape($_POST["pool_id"]);
					$images_id = $_POST['check'];
					
					$pool = $this->get_single_pool($pool_id);

					if(($pool['public'] == "Y" && !$user->is_anon()) || $user->is_admin() || $user->id == $pool['user_id']) {					
						$this->add_posts($pool_id, $images_id);
						$page->set_mode("redirect");
						$page->set_redirect(make_link("pool/view/".$pool_id));
					} else {
						$this->theme->display_error("Permssion denied.");
					}
					break;

				case "remove_posts":
					$pool_id = int_escape($_POST["pool_id"]);
					$images_id = $_POST['check'];
					
					$pool = $this->get_single_pool($pool_id);

					if(($pool['public'] == "Y" && !$user->is_anon()) || $user->is_admin() || $user->id == $pool['user_id']) {
						$this->remove_posts($pool_id, $images_id);
						$page->set_mode("redirect");
						$page->set_redirect(make_link("pool/view/".$pool_id));
					} else {
						$this->theme->display_error("Permssion denied.");
					}

					break;

				case "nuke":
					$pool_id = int_escape($_POST['pool_id']);
					$pool = $this->get_single_pool($pool_id);

					// only admins and owners may do this
					if($user->is_admin() || $user->id == $pool['user_id']) {
						$this->nuke_pool($pool_id);
						$page->set_mode("redirect");
						$page->set_redirect(make_link("pool/list"));
					} else {
						$this->theme->display_error("Permssion denied.");
					}
					break;

				default:
					$page->set_mode("redirect");
					$page->set_redirect(make_link("pool/list"));
					break;
			}
		}
	}

	/*
	 * HERE WE GET THE POOLS WHERE THE IMAGE APPEARS WHEN THE IMAGE IS DISPLAYED
	 */
	public function onDisplayingImage($event) {
		global $config, $database, $page;

		if($config->get_bool("poolsInfoOnViewImage")) {
			$imageID = $event->image->id;
			$poolsIDs = $this->get_pool_id($imageID);

			$linksPools = array();
			foreach($poolsIDs as $poolID) {
				$pools = $this->get_pool($poolID['pool_id']);
				foreach ($pools as $pool){
					$linksPools[] = "<a href='".make_link("pool/view/".$pool['id'])."'>".html_escape($pool['title'])."</a>";
				}
			}
			$prev_link=NULL;
			$next_link=NULL;
			
			if($poolsIDs){	
				$prev = $this->get_prev_image($poolID['pool_id'], $imageID);
				if($prev){
					$prev_link = "<a href='".make_link("post/view/".$prev['image_id'])."'>Prev</a>";
				}
				
				$next = $this->get_next_image($poolID['pool_id'], $imageID);
				if($next){
					$next_link = "<a href='".make_link("post/view/".$next['image_id'])."'>Next</a>";
				}
			}	
			
			$this->theme->pool_info($linksPools, $prev_link, $next_link);
		}
	}

	public function onImageAdminBlockBuilding($event) {
		global $config, $database, $user;
		if($config->get_bool("poolsAdderOnViewImage") && !$user->is_anon()) {
			if($user->is_admin()) {
				$pools = $database->get_all("SELECT * FROM pools");
			}
			else {
				$pools = $database->get_all("SELECT * FROM pools WHERE user_id=?", array($user->id));
			}
			if(count($pools) > 0) {
				$event->add_part($this->theme->get_adder_html($event->image, $pools));
			}
		}
	}
	
	public function onTagSet($event){
		global $database;
		$matches = array();
		foreach($event->tags as $tag){
			if(preg_match("/^pool:(\d+)/", $tag, $matches)) {
				$pool_id = $matches[1];
				if($pool_id != 0){
					$images = array($event->image->id);
					$this->add_posts($pool_id, $images);
				}
			}
			if(preg_match("/^-pool:(\d+)/", $tag, $matches)) {
				$pool_id = $matches[1];
				if($pool_id != 0){
					$images = array($event->image->id);
					$this->remove_posts($pool_id, $images);
				}
			}
		}
	}


	/*
	 * HERE WE GET THE LIST OF POOLS
	 */
	private function list_pools(Page $page, $pageNumber) {
		global $config, $database;

		if(is_null($pageNumber) || !is_numeric($pageNumber))
			$pageNumber = 0;
		else if ($pageNumber <= 0)
			$pageNumber = 0;
		else
			$pageNumber--;

		$poolsPerPage = $config->get_int("poolsListsPerPage");

		$pools = $database->get_all("
				SELECT p.id, p.user_id, p.public, p.title, p.description,
				       p.posts, u.name as user_name
				FROM pools AS p
				INNER JOIN users AS u
				ON p.user_id = u.id
				ORDER BY p.date DESC
				LIMIT ? OFFSET ?
				", array($poolsPerPage, $pageNumber * $poolsPerPage)
				);

		$totalPages = ceil($database->db->GetOne("SELECT COUNT(*) FROM pools") / $poolsPerPage);

		$this->theme->list_pools($page, $pools, $pageNumber + 1, $totalPages);
	}


	/*
	 * HERE WE CREATE A NEW POOL
	 */
	private function add_pool() {
		global $user, $database;

		if($user->is_anon()) {
			throw new PoolCreationException("You must be registered and logged in to add a image.");
		}
		if(empty($_POST["title"])) {
			throw new PoolCreationException("Pool needs a title");
		}

		$public = $_POST["public"] == "Y" ? "Y" : "N";
		$database->execute("
				INSERT INTO pools (user_id, public, title, description, date)
				VALUES (?, ?, ?, ?, now())",
				array($user->id, $public, $_POST["title"], $_POST["description"]));

		$result = $database->get_row("SELECT LAST_INSERT_ID() AS poolID"); # FIXME database specific?

		log_info("pools", "Pool {$result["poolID"]} created by {$user->name}");

		return $result["poolID"];
	}

	private function get_pool($poolID) {
		global $database;
		return $database->get_all("SELECT * FROM pools WHERE id=?", array($poolID));
	}

	private function get_single_pool($poolID) {
		global $database;
		return $database->get_row("SELECT * FROM pools WHERE id=?", array($poolID));
	}
		
	private function get_prev_image($poolID, $imageID) {
		global $database;
		$curr_image = $database->get_row("SELECT * FROM pool_images WHERE image_id = ?", array($imageID));
		return $database->get_row("SELECT image_id FROM pool_images WHERE pool_id = ? AND image_order < ? ORDER BY image_order DESC LIMIT 1", array($poolID, $curr_image['image_order']));
	}
	
	private function get_next_image($poolID, $imageID) {
		global $database;
		$curr_image = $database->get_row("SELECT * FROM pool_images WHERE image_id = ?", array($imageID));
		return $database->get_row("SELECT image_id FROM pool_images WHERE pool_id = ? AND image_order > ? ORDER BY image_order ASC LIMIT 1", array($poolID, $curr_image['image_order']));
	}

	/*
	 * HERE WE GET THE ID OF THE POOL FROM AN IMAGE
	 */
	private function get_pool_id($imageID) {
		global $database;
		return $database->get_all("SELECT pool_id FROM pool_images WHERE image_id=?", array($imageID));
	}


	/*
	 * HERE WE GET THE IMAGES FROM THE TAG ON IMPORT
	 */
	private function import_posts() {
		global $page, $config, $database;

		$pool_id = int_escape($_POST["pool_id"]);

		$poolsMaxResults = $config->get_int("poolsMaxImportResults", 1000);

		$images = $images = Image::find_images(0, $poolsMaxResults, Tag::explode($_POST["pool_tag"]));
		$this->theme->pool_result($page, $images, $pool_id);
	}


	/*
	 * HERE WE ADD CHECKED IMAGES FROM POOL AND UPDATE THE HISTORY
	 */
	private function add_posts($pool_id, $images_id) {
		global $database;

		$images = "";
		foreach ($images_id as $image_id){
			if(!$this->check_post($pool_id, $image_id)){
				$database->execute("
						INSERT INTO pool_images (pool_id, image_id)
						VALUES (?, ?)",
						array($pool_id, $image_id));

				$images .= " ".$image_id;
			}
		}

		if(!strlen($images) == 0) {
			$count = $database->db->GetOne("SELECT COUNT(*) FROM pool_images WHERE pool_id=?", array($pool_id));
			$this->add_history($pool_id, 1, $images, $count);
		}

		$database->Execute("
			UPDATE pools
			SET posts=(SELECT COUNT(*) FROM pool_images WHERE pool_id=?)
			WHERE id=?",
			array($pool_id, $pool_id)
		);
		return $pool_id;	 
	}

	private function order_posts() {
		global $database;

		$poolID = int_escape($_POST['pool_id']);

		foreach($_POST['imgs'] as $data) {
			list($imageORDER, $imageID) = $data;
			$database->Execute("
				UPDATE pool_images
				SET image_order = ?
				WHERE pool_id = ? AND image_id = ?",
				array($imageORDER, $poolID, $imageID)
			);
		}

		return $poolID;
	}


	/*
	 * HERE WE REMOVE CHECKED IMAGES FROM POOL AND UPDATE THE HISTORY
	 */
	private function remove_posts($pool_id, $images_id) {
		global $database;

		$images = "";
		foreach($images_id as $image_id) {
			$database->execute("DELETE FROM pool_images WHERE pool_id = ? AND image_id = ?", array($pool_id, $image_id));
			$images .= " ".$image_id;
		}

		$count = $database->db->GetOne("SELECT COUNT(*) FROM pool_images WHERE pool_id=?", array($pool_id));
		$this->add_history($pool_id, 0, $images, $count);
		return $pool_id;	 
	}


	/*
	 * HERE WE CHECK IF THE POST IS ALREADY ON POOL
	 * USED IN add_posts()
	 */
	private function check_post($poolID, $imageID) {
		global $database;
		$result = $database->db->GetOne("SELECT COUNT(*) FROM pool_images WHERE pool_id=? AND image_id=?", array($poolID, $imageID));
		return ($result != 0);
	}


	/*
	 * HERE WE GET ALL IMAGES FOR THE POOL
	 */
	private function get_posts($event, $poolID) {
		global $config, $user, $database;

		$pageNumber = int_escape($event->get_arg(2));
		if(is_null($pageNumber) || !is_numeric($pageNumber))
			$pageNumber = 0;
		else if ($pageNumber <= 0)
			$pageNumber = 0;
		else
			$pageNumber--;

		$poolID = int_escape($poolID);

		$imagesPerPage = $config->get_int("poolsImagesPerPage");

		// WE CHECK IF THE EXTENSION RATING IS INSTALLED, WHICH VERSION AND IF IT
		// WORKS TO SHOW/HIDE SAFE, QUESTIONABLE, EXPLICIT AND UNRATED IMAGES FROM USER
		if(class_exists("Ratings")) {
			$rating = Ratings::privs_to_sql(Ratings::get_user_privs($user));

			$result = $database->get_all("
					SELECT p.image_id
					FROM pool_images AS p
					INNER JOIN images AS i ON i.id = p.image_id
					WHERE p.pool_id = ? AND i.rating IN ($rating)
					ORDER BY p.image_order ASC
					LIMIT ? OFFSET ?",
					array($poolID, $imagesPerPage, $pageNumber * $imagesPerPage));

			$totalPages = ceil($database->db->GetOne("
					SELECT COUNT(*) 
					FROM pool_images AS p
					INNER JOIN images AS i ON i.id = p.image_id
					WHERE pool_id=? AND i.rating IN ($rating)",
					array($poolID)) / $imagesPerPage);
		}
		else {
			$result = $database->get_all("
					SELECT image_id
					FROM pool_images
					WHERE pool_id=?
					ORDER BY image_order ASC
					LIMIT ? OFFSET ?",
					array($poolID, $imagesPerPage, $pageNumber * $imagesPerPage));
			$totalPages = ceil($database->db->GetOne("SELECT COUNT(*) FROM pool_images WHERE pool_id=?", array($poolID)) / $imagesPerPage);
		}

		$images = array();
		foreach($result as $singleResult) {
			$images[] = Image::by_id($singleResult["image_id"]);
		}

		$pool = $this->get_pool($poolID);
		$this->theme->view_pool($pool, $images, $pageNumber + 1, $totalPages);
	}


	/*
	 * WE GET THE ORDER OF THE IMAGES
	 */
	private function edit_posts($poolID) {
		global $database;

		$result = $database->Execute("SELECT image_id FROM pool_images WHERE pool_id=? ORDER BY image_order ASC", array($poolID));

		$images = array();
		while(!$result->EOF) {
			$image = Image::by_id($result->fields["image_id"]);
			$images[] = array($image);
			$result->MoveNext();
		}

		return $images;
	}


	/*
	 * WE GET THE ORDER OF THE IMAGES BUT HERE WE SEND KEYS ADDED IN ARRAY TO GET THE ORDER IN THE INPUT VALUE
	 */
	private function edit_order($poolID) {
		global $database;

		$result = $database->Execute("SELECT image_id FROM pool_images WHERE pool_id=? ORDER BY image_order ASC", array($poolID));									
		$images = array();
		while(!$result->EOF) {
			$image = $database->get_row("
					SELECT * FROM images AS i
					INNER JOIN pool_images AS p ON i.id = p.image_id
					WHERE pool_id=? AND i.id=?",
					array($poolID, $result->fields["image_id"]));
			$image = ($image ? new Image($image) : null);
			$images[] = array($image);
			$result->MoveNext();
		}
		//		Original code
		//		
		//		$images = array();
		//		while(!$result->EOF) {
		//			$image = Image::by_id($result->fields["image_id"]);
		//			$images[] = array($image);
		//			$result->MoveNext();
		//		}
		return $images;
	}


	/*
	 * HERE WE NUKE ENTIRE POOL. WE REMOVE POOLS AND POSTS FROM REMOVED POOL AND HISTORIES ENTRIES FROM REMOVED POOL
	 */
	private function nuke_pool($poolID) {
		global $user, $database;

		if($user->is_admin()) {
			$database->execute("DELETE FROM pool_history WHERE pool_id = ?", array($poolID));
			$database->execute("DELETE FROM pool_images WHERE pool_id = ?", array($poolID));
			$database->execute("DELETE FROM pools WHERE id = ?", array($poolID));
		} elseif(!$user->is_anon()) {
			// FIXME: WE CHECK IF THE USER IS THE OWNER OF THE POOL IF NOT HE CAN'T DO ANYTHING
			$database->execute("DELETE FROM pool_history WHERE pool_id = ?", array($poolID));
			$database->execute("DELETE FROM pool_images WHERE pool_id = ?", array($poolID));
			$database->execute("DELETE FROM pools WHERE id = ? AND user_id = ?", array($poolID, $user->id));
		}
	}


	/*
	 * HERE WE ADD A HISTORY ENTRY
	 * FOR $action 1 (one) MEANS ADDED, 0 (zero) MEANS REMOVED
	 */
	private function add_history($poolID, $action, $images, $count) {
		global $user, $database;
		$database->execute("
				INSERT INTO pool_history (pool_id, user_id, action, images, count, date)
				VALUES (?, ?, ?, ?, ?, now())",
				array($poolID, $user->id, $action, $images, $count));
	}


	/*
	 * HERE WE GET THE HISTORY LIST
	 */
	private function get_history($pageNumber) {
		global $config, $database;

		if(is_null($pageNumber) || !is_numeric($pageNumber))
			$pageNumber = 0;
		else if ($pageNumber <= 0)
			$pageNumber = 0;
		else
			$pageNumber--;


		$historiesPerPage = $config->get_int("poolsUpdatedPerPage");

		$history = $database->get_all("
				SELECT h.id, h.pool_id, h.user_id, h.action, h.images,
				       h.count, h.date, u.name as user_name, p.title as title
				FROM pool_history AS h
				INNER JOIN pools AS p
				ON p.id = h.pool_id
				INNER JOIN users AS u
				ON h.user_id = u.id
				ORDER BY h.date DESC
				LIMIT ? OFFSET ?
				", array($historiesPerPage, $pageNumber * $historiesPerPage));

		$totalPages = ceil($database->db->GetOne("SELECT COUNT(*) FROM pool_history") / $historiesPerPage);

		$this->theme->show_history($history, $pageNumber + 1, $totalPages);
	}



	/*
	 * HERE GO BACK IN HISTORY AND ADD OR REMOVE POSTS TO POOL
	 */
	private function revert_history($historyID) {
		global $database;
		$status = $database->get_all("SELECT * FROM pool_history WHERE id=?", array($historyID));

		foreach($status as $entry) {
			$images = trim($entry['images']);
			$images = explode(" ", $images);
			$poolID = $entry['pool_id'];
			$imageArray = "";

			if($entry['action'] == 0) {
				// READ ENTRIES
				foreach($images as $image) {	
					$imageID = $image;		
					$this->add_post($poolID, $imageID);

					$imageArray .= " ".$imageID;
					$newAction = 1;
				}
			}
			else if($entry['action'] == 1) {
				// DELETE ENTRIES
				foreach($images as $image) {
					$imageID = $image;		
					$this->delete_post($poolID, $imageID);

					$imageArray .= " ".$imageID;
					$newAction = 0;
				}
			}

			$count = $database->db->GetOne("SELECT COUNT(*) FROM pool_images WHERE pool_id=?", array($poolID));
			$this->add_history($poolID, $newAction, $imageArray, $count);
		}
	}



	/*
	 * HERE WE ADD A SIMPLE POST FROM POOL
	 * USED WITH FOREACH IN revert_history()
	 */
	private function add_post($poolID, $imageID) {
		global $database;

		if(!$this->check_post($poolID, $imageID)) {
			$database->execute("
					INSERT INTO pool_images (pool_id, image_id)
					VALUES (?, ?)",
					array($poolID, $imageID));
		}

		$database->execute("UPDATE pools SET posts=(SELECT COUNT(*) FROM pool_images WHERE pool_id=?) WHERE id=?", array($poolID, $poolID));
	}



	/*
	 * HERE WE REMOVE A SIMPLE POST FROM POOL
	 * USED WITH FOREACH IN revert_history()
	 */
	private function delete_post($poolID, $imageID) {
		global $database;

		$database->execute("DELETE FROM pool_images WHERE pool_id = ? AND image_id = ?", array($poolID, $imageID));
		$database->execute("UPDATE pools SET posts=(SELECT COUNT(*) FROM pool_images WHERE pool_id=?) WHERE id=?", array($poolID, $poolID));
	}

}
?>
