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
				}
			}
			else{
				$this->theme->display_permission_denied();
			}
		}
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
}

?>