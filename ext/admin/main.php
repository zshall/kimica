<?php
/*
* Name: Admin Board
* Author: Sein Kraft
* Description: Generic administration panel
*/

/*
* Section: Name of the extension who send the event
* Message: Reason of the event
* Location: Url of where is located the problem
*/
class AlertAdditionEvent extends Event {
	var $section, $message, $description, $location, $alerter;

	public function AlertAdditionEvent($section, $message, $description="", $location) {
		global $user;
		$this->section = $section;
		$this->message = $message;
		$this->description = $description;
		$this->location = $location;
		$this->alerter = $user->id;
	}
}

 
class Admin extends SimpleExtension {
	public function onPageRequest($event) {
		global $page, $user;
		
		if($event->page_matches("admin")) {
			$this->theme->display_sidebar();
		}
		
		if($event->page_matches("admin/alerts")) {
			$action = $event->get_arg(0);
			
			if($user->is_admin()){
				switch($action){
					case "list":
						$this->theme->display_alerts($this->get_alerts());
					break;
					case "view":
						$alert_id = $event->get_arg(1);
						$this->update_alert('r', $user->id, $alert_id);
						
						$alert = $this->get_alert($alert_id);
						
						$page->set_mode("redirect");
						$page->set_redirect(make_link($alert["location"]));
					break;
					case "remove":
						$alert_id = $event->get_arg(1);
						$this->update_alert($user->id, $alert_id);
					break;
					case "action":
						switch ($_POST["action"]) {
							case "Solved":
								foreach($_POST['id'] as $id) {
									$this->update_alert('s', $user->id, int_escape($id));
								}
								$page->set_mode("redirect");
								$page->set_redirect(make_link("admin/alerts/list"));
							break;
							case "Delete":
								foreach($_POST['id'] as $id) {
									$this->remove_alert(int_escape($id));
								}
								$page->set_mode("redirect");
								$page->set_redirect(make_link("admin/alerts/list"));
							break;
						}
					break;
					default:
						$this->theme->display_alerts($this->get_alerts());
					break;
				}
			}
			else{
				$this->theme->display_permission_denied();
			}
		}
		
		if($event->page_matches("admin/database")) {
			$action = $event->get_arg(0);
						
			if($user->is_admin()){
				$this->theme->display_tag_tools();
				
				if(isset($_POST['action'])){
					switch($_POST['action']) {
						case 'lowercase all tags':
							$this->tags_to_lowercase();
							$redirect = true;
						break;
						case 'recount tag use':
							$this->tags_recount_use();
							$redirect = true;
						break;
						case 'purge unused tags':
							$this->tags_purge_unused();
							$redirect = true;
						break;
						case 'convert to innodb':
							$this->database_to_innodb();
							$redirect = true;
						break;
						case 'database dump':
							$this->database_dump();
						break;					
					}
				}
			}
			else{
				$this->theme->display_permission_denied();
			}
		}
		
		if($event->page_matches("admin/posts")) {
			$this->theme->display_bulk_tag_editor();
			$this->theme->display_bulk_source_editor();
			if(class_exists("Ratings")){
				$this->theme->display_bulk_rater();
			}
			$this->theme->display_bulk_uploader();
		}
	}
	
	public function onUserBlockBuilding($event) {
		$event->add_link("Admin Tools", make_link("admin"), 100);
	}
			
	public function onAlertAddition($event){
		$this->add_alert($event);
	}
	
	public function add_alert($event){
		global $database;
		$database->Execute("INSERT INTO notifications(section, message, description, location, created_at, alerter_id) VALUES(?, ?, ?, ?, NOW(), ?)", array($event->section, $event->message, $event->description, $event->location, $event->alerter));
	}
	
	public function update_alert($status, $user_id, $alert_id){
		global $database;
		$database->Execute("UPDATE notifications SET status = ?, reviewer_id = ? WHERE id = ?", array($status, $user_id, $alert_id));
	}
	
	public function remove_alert($alert_id){
		global $database;
		$database->Execute("DELETE FROM notifications WHERE id = ?", array($alert_id));
	}
	
	public function get_alerts(){
		global $database;		
		return $database->get_all(
                "SELECT n.*, a.name AS alerter, r.name AS reviewer ".
                "FROM notifications AS n ".
                "INNER JOIN users AS a ".
                "ON n.alerter_id = a.id ".
				"INNER JOIN users AS r ".
                "ON n.reviewer_id = r.id ".
                "ORDER BY n.id ASC");
	}
	
	public function get_alert($alert_id){
		global $database;
		return $database->get_row("SELECT * FROM notifications WHERE id = ?", array($alert_id));
	}
	
	public function alert_to_human($status){
		switch($status) {
			case "p": return "pending";
			case "r": return "reviewed";
			case "s": return "solved";
		}
	}
	
	private function tags_to_lowercase() {
		global $database;
		$database->execute("UPDATE tags SET tag=lower(tag)");
	}
	
	private function tags_recount_use() {
		global $database;
		$database->Execute("
			UPDATE tags
			SET count = COALESCE(
				(SELECT COUNT(image_id) FROM image_tags WHERE tag_id=tags.id GROUP BY tag_id),
				0
			)");
	}
	
	private function tags_purge_unused() {
		global $database;
		$this->tags_recount_use();
		$database->Execute("DELETE FROM tags WHERE count=0");
	}
	
	private function database_to_innodb() {
		global $database;
		if($database->engine->name == "mysql") {
			$tables = $database->db->MetaTables();
			foreach($tables as $table) {
				log_info("upgrade", "converting $table to innodb");
				$database->execute("ALTER TABLE $table TYPE=INNODB");
			}
		}
	}
	
	private function database_dump() {
		global $page;
		include "config.php";

		switch($db_type) {
			case 'mysql':
				$cmd = "mysqldump -h$db_host -u$db_user -p$db_pass $db_name";
				break;
		}

		$page->set_mode("data");
		$page->set_type("application/x-unknown");
		$page->set_filename('kimica-'.date('Ymd').'.sql');
		$page->set_data(shell_exec($cmd));
	}
}

?>