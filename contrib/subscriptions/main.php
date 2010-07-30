<?php
/**
 * Name: Tag Subscription
 * Author: Sein Kraft <mail@seinkraft.info>
 * License: GPLv2
 * Description: Simple tag subscription extension
 * Documentation: Batch should match with the maximun emails wich could be sent by the server. It's preferible set a samller number to avoid the saturation on server or the server account could be considered as spam if you're on a shared and suspended regarding the TOS of the hosting provider.<br>By default it's needed run two cronjobs in cpanel and execute the next url with GET <a href="">http://www.domain.com/subscription/cron/check</a> once a day and other conjob with GET <a href="">http://www.domain.com/subscription/cron/send</a> every hour, otherwise dayly, weekly and monthly digest shouln't work but yes the instant digest.
 */
class Subscription extends SimpleExtension {

	public function onInitExt($event) {
		global $config, $database;
					
			if ($config->get_int("ext_subscription_version") < 1){
						
				$database->create_table("subscriptions", "
							id SCORE_AIPK,
							user_id INTEGER NOT NULL,
							tag_name VARCHAR(255) NOT NULL,
							digest CHAR(1) NOT NULL,
							private SCORE_BOOL NOT NULL DEFAULT SCORE_BOOL_N,
							INDEX (tag_name),
							FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
					");
					
				$database->create_table("subscription_digest", "
							user_id INTEGER NOT NULL,
							image_id INTEGER NOT NULL,
							image_tag VARCHAR(64) NOT NULL,
							digest CHAR(1) NOT NULL,
							send INT(1) NOT NULL,
							INDEX (user_id)
					");
				
				$message = "<p>Hello {user},<br>".
						   "this email was sent by {site} system because the image {imagelink} was tagged with the tag {taglink} respectively.</p>".
						   "<p>Regards,<br>".
						   "{site} Team.</p>";
				
				$config->set_bool("ext_subsctiption_instant", false);
				$config->set_int("ext_subsctiption_max", 5);
				$config->set_string("ext_subsctiption_email", "mail@domain.com");
				$config->set_string("ext_subsctiption_subject", "Tag Subscription");
				$config->set_int("ext_subsctiption_batch", 250);
				$config->set_string("ext_subsctiption_message", $message);
									
				$config->set_int("ext_subscription_version", 1);
				
				log_info("subscription", "extension installed");
			}
	}
	
	public function onPageRequest($event) {
		global $page, $config, $user, $database;
			
		if($event->page_matches("account/subscriptions")) {
			switch($event->get_arg(0)) {
				case "add":
				{			
					$this->addSubscription();
					$page->set_mode("redirect");
					$page->set_redirect(make_link("account/subscriptions"));
					break;
				}
				case "delete":
				{
					$tagID = $event->get_arg(1);
					$this->deleteSubscription($tagID);
					$page->set_mode("redirect");
					$page->set_redirect(make_link("account/subscriptions"));
					break;
				}
				case "private":
				{
					$subscription_id = $event->get_arg(1);
					$this->changeSubscription($subscription_id);
					$page->set_mode("redirect");
					$page->set_redirect(make_link("account/subscriptions"));
					break;
				}
				case "cron":
				{
					switch($event->get_arg(1)) {
						case "check":
						{
							$this->runCronJobQueue();
							break;
						}
						case "send":
						{
							$this->runCronJobSend();
							break;
						}
					}
					break;
				}
				default:
				{								
					$tags = $database->get_all("SELECT * FROM subscriptions WHERE user_id = ? ORDER BY id DESC", array($user->id));
											
					$this->theme->subscriptions($tags, $this->canAddSubscription($user->id), $config->get_bool("ext_subsctiption_instant", false));
				break;
				}
			}
		}
	}
		
	public function onTagSet($event) {
		$this->checkSubscription($event->image->id);
	}
	
	public function onSetupBuilding(SetupBuildingEvent $event) {
		$replaces = '<strong>{site}</strong> would be replace with site name.<br>
					<strong>{user}</strong> would be replace with username.<br>
					<strong>{taglink}</strong> would be replace with link to tag.<br>
					<strong>{imagelink}</strong> would be replace with link to image.';
					
		$sb = new SetupBlock("Tag Subscription");
		$sb->add_bool_option("ext_subsctiption_instant", "Enable instant subscription?: ");
		$sb->add_int_option("ext_subsctiption_max", "<br>Subscriptions per user: ");
		$sb->add_text_option("ext_subsctiption_email", "<br><br>Email: ");
		$sb->add_text_option("ext_subsctiption_subject", "<br>Subjet: ");
		$sb->add_int_option("ext_subsctiption_batch", "<br>Mails per batch: ");
		$sb->add_longtext_option("ext_subsctiption_message", "<br><br>Message(html): <br>{$replaces}");
		$event->panel->add_block($sb);
	}
	
	
	
	/*
	* WE SET THE DIGESTS TO BE SENT. WILL BE EXECUTED ONCE AT DAY
	*/
	private function runCronJobQueue() {
	
		$this->updateDigestEntry("d");
		
		//run every week. always on sunday
		if(date('w') == "0"){
			$this->updateDigestEntry("w");
		}
		
		//run every month. always on the first day
		if(date('j') == "1"){
			$this->updateDigestEntry("m");
		}
	}
	
	
	
	/*
	* WE SEND THE EMAILS. WILL BE EXECUTED EVERY HOUR
	*/
	private function runCronJobSend() {
	
		$this->runAdvancedDigest("d");
		
		//run every week. always on sunday
		if(date('w') == "0"){
			$this->runAdvancedDigest("w");
		}
		
		//run every month. always on the first day
		if(date('j') == "1"){
			$this->runAdvancedDigest("m");
		}
	}
	
	
	
	/*
	* HERE WE ADD A SUBSCRIPTION TO DATABASE. USER ID BY EVENT
	*/
	private function addSubscription() {
		global $user, $database;
		
		$userID = $user->id;
		
		$tagNAME = mysql_real_escape_string(html_escape(trim($_POST["tag"])));
		$digest = trim($_POST["digest"]);
		$private = html_escape($_POST["private"]);
		
		if($private <> "y"){
			$private = "N";
		}
		
		//str_word_count() only check for alphabetic words, we add also numbers and then check for alphanumeric words.
		$words = str_word_count($tagNAME, 0, '0123456789');
	
		//insert one tag per entry
		if($words == 1){
			if($this->canAddSubscription($userID)){
				$database->execute("
							INSERT INTO subscriptions
								(user_id, tag_name, digest, private)
							VALUES
								(?, ?, ?, ?)",
							array($userID, strtolower($tagNAME), $digest, $private));
			}
		}
	}
	
	
	
	/*
	* HERE WE CHECK IF THE USER CAN ADD A NEW SUBSCRIPTION
	*/
	private function canAddSubscription($userID){
		global $config, $user, $database;
		$entries = $database->db->GetOne("SELECT COUNT(*) FROM subscriptions WHERE user_id = ?", array($userID));
		
		if($entries < $config->get_int("ext_subsctiption_max") || $user->is_admin()){
			return TRUE;
		} else {
			return FALSE;
		}
	}
	
	
	
	/*
	* HERE WE CHANGE THE SUBSCRIPTION TO PRIVATE OR PUBLIC
	*/
	private function changeSubscription($subscription_id) {
		global $user, $database;
		
		$subscription = $database->get_row("SELECT user_id, private FROM subscriptions WHERE id = ?", array($subscription_id));
		
		if ($subscription['user_id'] == $user->id || $user->is_admin()){
			if($subscription['private'] == "Y"){
				$private = "N";	
			}elseif($subscription['private'] == "N"){
				$private = "Y";			
			}
			$database->Execute("UPDATE subscriptions SET private = ? WHERE id = ?", array($private, $subscription_id));
		}
	}
	
	
	
	/*
	* HERE WE DELETE A SUBSCRIPTION
	*/
	private function deleteSubscription($tagID) {
		global $user, $database;
		$userID = $user->id;
		
		if(!$user->is_anon()){
			$database->execute("DELETE FROM subscriptions WHERE id = ? AND user_id = ?", array($tagID, $userID));
		} 
		elseif($user->is_admin){
			$database->execute("DELETE FROM subscriptions WHERE id = ?", array($tagID));
		}
	}
	
	
	
	/*
	* HERE WE GETS SUBSCRIPTIONS FROM USERS AND ADD A DIGEST
	*/
	private function checkSubscription($image_id) {
		global $database;
		
		$image = Image::by_id($image_id);
		$tags = $image->get_tag_list();
		$tags = explode(" ", $tags);
			
		foreach ($tags as $tag){
			$users = $database->get_all("SELECT user_id, tag_name, digest FROM subscriptions WHERE tag_name = ?", array($tag));
			foreach($users as $user){
				$this->addDigestEntry($user['user_id'], $image_id, $user['tag_name'], $user['digest']);
			}
		}
	}
	
	
	
	/*
	* WE ADD A DIGEST TO DATABASE IF NOT IS AN INSTANT DIGEST
	*/
	private function addDigestEntry($user_id, $image_id, $image_tag, $digest){
		global $database;
		
		//if is instantaneous subscription send mail else add todatabase.
		if($digest == "i"){
			$this->runSimpleDigest($user_id, $image_id, $image_tag);
		}else{
		
			$count = $database->db->GetOne("SELECT COUNT(*) FROM subscription_digest WHERE user_id = ? AND image_id = ? AND image_tag = ?", array($user_id, $image_id, $image_tag));
			
			if($count == 0){
				$database->execute("
								INSERT INTO subscription_digest
									(user_id, image_id, image_tag, digest, send)
								VALUES
									(?, ?, ?, ?, ?)",
								array($user_id, $image_id, $image_tag, $digest, 0));
			}
		}
	}
	
	
	
	/*
	* WE UPDATE THE DIGEST TO BE SENT
	*/
	private function updateDigestEntry($digest){
		global $database;
		
		$database->Execute("UPDATE subscription_digest SET send = ? WHERE send = ? AND digest = ?", array(1, 0, $digest));
	}
	
	
	
	/*
	*	WE SEND AN INSTANT DIGEST TO USER. NO NEED OF CRONJOB
	*/
	private function runSimpleDigest($user_id, $image_id, $image_tag){
	
		$imageArray = array();
	
		$imageArray["0"][] = $image_id;
		$imageArray["0"][] = $image_tag;
				
		$this->makeDigest($user_id, $imageArray);
	}
	
	
	
	/*
	*	WE SEND A DAYLY DIGEST TO USER. WE NEED CRONJOB
	*/
	private function runAdvancedDigest($digest){
		global $config, $database;
		$users = $database->get_all("SELECT DISTINCT user_id FROM subscription_digest WHERE digest = ? AND send = ? ORDER BY user_id ASC", array($digest, 1));
		
		$mails = 0;
		$batch = $config->get_int("ext_subsctiption_batch");
		
		foreach($users as $user){
			if($mails < $batch){
				$images = $database->get_all("SELECT image_id, image_tag FROM subscription_digest WHERE user_id = ? AND digest = ? AND send = ? ORDER BY image_id ASC", array($user['user_id'], $digest, 1));
				
				$n = 0;
				
				$imageArray = array();
				foreach($images as $image){
					$imageArray[$n][] = $image['image_id'];
					$imageArray[$n][] = $image['image_tag'];
					
					$n = $n+1;
				}
				
				if($this->makeDigest($user['user_id'], $imageArray)){
					$this->deleteDigest($user['user_id'], $digest);
				}
			}
			$mails = $mails + 1;
		}
	}
	
	
	
	/*
	*	WE PREPARE THE INFO TO SEND A COMPLEX DISGEST
	*/
	private function makeDigest($user_id, $imageArray){
		global $config, $database;
		
		$duser = User::by_id($user_id);
		
		$imagelink = "";
		$imagethumb = "";
		$taglink = "";
				
		foreach ($imageArray as $data) {
			list ($image_id, $image_tag) = $data;
			
			$image = Image::by_id($image_id);
			if(!is_null($image)) {
				$imagelink .= '<a href="'.make_http(make_link("post/view/".$image_id)).'" title="'.$image->get_tooltip().'">'.$image_id.'</a>, ';
				
				$thumb_html = Themelet::build_thumb_html($image);
				$imagethumb .= '<li class="thumb" style="width: 50%;">'.
							   '<a href="$imagelink">'.$thumb_html.'</a>'.
							   '</li>';
				
				$taglink .= '<a href="'.make_http(make_link("post/list/".$image_tag."/1")).'">'.$image_tag.'</a>, ';
			}else{
				$imagelink .= ', ';
				$taglink .= ', ';
			}
		}
		
		//remove the last comma
		$imagelink = substr($imagelink, 0, -2);
		$imagethumb = "<ul class='thumbblock'>".$imagethumb."</ul>";
		$taglink = substr($taglink, 0, -2);
		
		$site = $config->get_string("title");
		$email = $config->get_string("ext_subsctiption_email");
		$username = $duser->name;
		$message = $config->get_string("ext_subsctiption_message");
		
		$message = str_replace("{site}", $site, $message);
		$message = str_replace("{user}", $username, $message);
		$message = str_replace("{imagelink}", $imagelink, $message);
		$message = str_replace("{imagethumb}", $imagethumb, $message);
		$message = str_replace("{taglink}", $taglink, $message);
		
		$email = new Email($duser->email, "Tag Subscription", "Tag Subscription", $message);
		if($email->send()){
			$this->deleteUserDigests($duser->id);
		}
		else{
			$this->deleteUserDigests($duser->id);
		}
	}
	
	/*
	*	ONCE THE DIGGEST HAS BEEN DELIVERED TO USER DELETE FROM DATABASE
	*/
	private function deleteDigest($user_id, $digest){
		global $database;
		$database->execute("DELETE FROM subscription_digest WHERE user_id = ? AND digest = ?", array($user_id, $digest));
	}
	
	private function deleteUserDigests($user_id){
		global $database;
		$database->execute("DELETE FROM subscription_digest WHERE user_id = ?", array($user_id));
	}
}
?>