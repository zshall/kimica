<?php
/**
 * Name: Tag Blacklist
 * Author: Sein Kraft <mail@seinkraft.info>
 * License: GPLv2
 * Description: Simple tag blacklist for users extension
 * Documentation: 
 */
class Blacklist extends SimpleExtension {

	public function onInitExt($event) {
		global $config, $database;
					
			if ($config->get_int("ext_blacklist_version") < 1){
						
				$config->set_int("ext_blacklist_max", 5);
									
				$config->set_int("ext_blacklist_version", 1);
				
				log_info("blacklist", "extension installed");
			}
	}
	
	public function onPageRequest($event) {
		global $page, $config, $user, $database;
			
		if($event->page_matches("account/blacklist")) {
			switch($event->get_arg(0)) {
				case "add":
				{			
					$this->addTag();
					$page->set_mode("redirect");
					$page->set_redirect(make_link("account/blacklist"));
					break;
				}
				case "delete":
				{
					$tag = $event->get_arg(1);
					$this->deleteTag($tag);
					$page->set_mode("redirect");
					$page->set_redirect(make_link("account/blacklist"));
					break;
				}
				default:
				{								
					$tags = $database->get_all("SELECT * FROM tag_blacklist WHERE user_id = ? ORDER BY tag DESC", array($user->id));
											
					$this->theme->display_blacklist($tags, $this->canAddTag($user->id));
				break;
				}
			}
		}
	}
			
	public function onSetupBuilding(SetupBuildingEvent $event) {					
		$sb = new SetupBlock("Tag Blacklist");
		$sb->add_int_option("ext_blacklist_max", "Tags per user: ");
		$event->panel->add_block($sb);
	}
	
	
	
	/*
	* HERE WE ADD A SUBSCRIPTION TO DATABASE. USER ID BY EVENT
	*/
	private function addTag() {
		global $user, $database;
		
		$userID = $user->id;
		$tagNAME = mysql_real_escape_string(html_escape(trim($_POST["tag"])));
		
		//str_word_count() only check for alphabetic words, we add also numbers and then check for alphanumeric words.
		$words = str_word_count($tagNAME, 0, '0123456789');
	
		//insert one tag per entry
		if($words == 1){
			if($this->canAddTag($userID)){
				$database->execute("
							INSERT INTO tag_blacklist
								(user_id, tag)
							VALUES
								(?, ?)",
							array($userID, strtolower($tagNAME)));
			}
		}
	}
	
	
	
	/*
	* HERE WE CHECK IF THE USER CAN ADD A NEW SUBSCRIPTION
	*/
	private function canAddTag($userID){
		global $config, $user, $database;
		$entries = $database->db->GetOne("SELECT COUNT(*) FROM tag_blacklist WHERE user_id = ?", array($userID));
		
		if($entries < $config->get_int("ext_blacklist_max") || $user->is_admin()){
			return TRUE;
		} else {
			return FALSE;
		}
	}	
	
	
	/*
	* HERE WE DELETE A SUBSCRIPTION
	*/
	private function deleteTag($tag) {
		global $user, $database;
		$userID = $user->id;
		
		if(!$user->is_anon()){
			$database->execute("DELETE FROM tag_blacklist WHERE tag = ? AND user_id = ?", array($tag, $userID));
		} 
	}
	
}
?>