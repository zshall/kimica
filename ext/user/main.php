<?php
/*
 * Name: User Management
 * Author: Shish
 * Description: Allows people to sign up to the website
 */

class UserBlockBuildingEvent extends Event {
	var $parts = array();

	public function add_link($name, $link, $position=50) {
		while(isset($this->parts[$position])) $position++;
		$this->parts[$position] = array("name" => $name, "link" => $link);
	}
}

class UserPageBuildingEvent extends Event {
	var $display_user;
	var $stats = array();

	public function __construct(User $display_user) {
		$this->display_user = $display_user;
	}

	public function add_stats($html, $position=50) {
		while(isset($this->stats[$position])) $position++;
		$this->stats[$position] = $html;
	}
}

class UserCreationEvent extends Event {
	var $username;
	var $password;
	var $email;

	public function __construct($name, $pass, $email) {
		$this->username = $name;
		$this->password = $pass;
		$this->email = $email;
	}
}

class UserBanEvent extends Event {
	var $duser;
	var $end_date;
	var $reason;

	public function __construct($duser, $end_date, $reason) {
		$this->duser = $duser;
		$this->end_date = $end_date;
		$this->reason = $reason;
	}
}

class UserCreationException extends SCoreException {}

class UserPage extends SimpleExtension {
	public function onInitExt($event) {
		global $config;
		$config->set_default_bool("login_signup_enabled", true);
		$config->set_default_int("login_memory", 365);
		$config->set_default_string("avatar_host", "none");
		$config->set_default_string("signup_validation_email", "example@example.com");
		$config->set_default_int("avatar_gravatar_size", 80);
		$config->set_default_string("avatar_gravatar_default", "");
		$config->set_default_string("avatar_gravatar_rating", "g");
		$config->set_default_bool("signup_tac_bbcode", true);
		$config->set_default_bool("signup_validation_enabled", false);
	}

	public function onPageRequest($event) {
		global $config, $database, $page, $user;

		// user info is shown on all pages
		if($user->is_anon()) {
			$this->theme->display_login_block($page);
		}
		else {
			$ubbe = new UserBlockBuildingEvent();
			send_event($ubbe);
			ksort($ubbe->parts);
			$this->theme->display_user_block($page, $user, $ubbe->parts);
		}
		
		if($user->is_banned()){
			set_prefixed_cookie("session", "", time()+60*60*24*$config->get_int('login_memory'), "/");
			log_info("user", "Logged out");
		}

		if($event->page_matches("account")) {
		
			// account/login - Login
			if($event->get_arg(0) == "login") {
				if(isset($_POST['user']) && isset($_POST['pass'])) {
					$this->login($page);
				}
				else {
					$this->theme->display_login_page($page);
				}
			}
			
			// account/logout - Logout
			else if($event->get_arg(0) == "logout") {
				set_prefixed_cookie("session", "", time()+60*60*24*$config->get_int('login_memory'), "/");
				log_info("user", "Logged out");
				$page->set_mode("redirect");
				$page->set_redirect(make_link());
			}
			
			else if($event->get_arg(0) == "profile"){
				$account = $event->get_arg(1);
				if(isset($account)){
					$display_user = User::by_name($account);
				}
				else{
					$display_user = $user;
				}
					
				if(!is_null($display_user)) {
					send_event(new UserPageBuildingEvent($display_user));
				}
				else {
					$this->theme->display_error("No Such User","If you typed the ID by hand, try again; if you came from a link on this site, it might be bug report time...");
				}
			}
			
			else if($event->get_arg(0) == "list"){
				$pageNumber = $event->get_arg(1);
				
				if(is_null($pageNumber) || !is_numeric($pageNumber)){
					$pageNumber = 0;
				}
				else if ($pageNumber <= 0){
					$pageNumber = 0;
				}
				else{
					$pageNumber--;
				}
					
				$totalPages = ceil($database->db->GetOne("SELECT COUNT(*) FROM users") / 50);
				$this->theme->display_user_list(User::by_list($pageNumber * 50), $user, $pageNumber + 1, $totalPages);
			}
			
			// account/change_pass - Change Pass
			else if($event->get_arg(0) == "change_pass") {
				$this->change_password_wrapper($page);
			}
			
			// account/change_email - Change Email
			else if($event->get_arg(0) == "change_email") {
				$this->change_email_wrapper($page);
			}
			
			// account/create  - Create Account
			else if($event->get_arg(0) == "create") {
				if(!$config->get_bool("login_signup_enabled")) {
					$this->theme->display_signups_disabled($page);
				}
				else if(!isset($_POST['name'])) {
					$this->theme->display_signup_page($page);
				}
				else if($_POST['pass1'] != $_POST['pass2']) {
					$this->theme->display_error("Password Mismatch", "Passwords don't match");
				}
				else {
					try {
						if(!captcha_check()) {
							throw new UserCreationException("Error in captcha");
						}

						$uce = new UserCreationEvent($_POST['name'], $_POST['pass1'], $_POST['email']);
						send_event($uce);
						
						if(!$config->get_bool("signup_validation_enabled")){
							$page->set_mode("redirect");
							$page->set_redirect(make_link("account/login"));
						}
						else{
							$page->set_mode("redirect");
							$page->set_redirect(make_link("account/validate"));
						}
					}
					catch(UserCreationException $ex) {
						$this->theme->display_error("User Creation Error", $ex->getMessage());
					}
				}
			}
			
			else if($event->get_arg(0) == "bans") {
				global $database;
				switch ($event->get_arg(1)) {
					case "list":
						$result = $database->get_all("
						SELECT usr.id, usr.name, ban.user_id, ban.end_date, ban.reason
						FROM user_bans AS ban
						INNER JOIN users AS usr ON usr.id = ban.user_id
						ORDER BY usr.name DESC
						LIMIT ?",
						array(20));
						
						$this->theme->display_user_bans($result);
					break;
					
					case "bulk":
						$users = array();
						foreach($_POST['user_id'] as $user_id) {
							$users[] = User::by_id($user_id);
						}
						
						if($users){
							$this->theme->display_user_prebans($users);
						}
						else{
							$page->set_mode("redirect");
							$page->set_redirect(make_link("account/list"));
						}
					break;
					
					case "add":
						foreach($_POST['bans'] as $data) {
							list($end_date, $reason, $user_id) = $data;
							
							$duser = User::by_id($user_id);
							if($duser){
								send_event(new UserBanEvent($duser, $end_date, $reason));
							}
						}
						
						$page->set_mode("redirect");
						$page->set_redirect(make_link("account/bans/list"));
					break;
					
					case "remove":
						foreach($_POST['user_id'] as $user_id) {
							$duser = User::by_id($user_id);
							$this->reset_user_ban($duser);
						}
						
						$page->set_mode("redirect");
						$page->set_redirect(make_link("account/bans/list"));
					break;
					
					default:
					break;
				}
				
			}
			
			else if($event->get_arg(0) == "options") {
				global $user;
				
				$user_name = $event->get_arg(1);
				if($user_name){
					$duser = User::by_name($user_name);
					$user_name = $duser->name;
				}
				else{
					$duser = $user;
				}
				
				$this->theme->build_options($duser);
			}
			
			// account/validate  - Validate Account
			else if($event->get_arg(0) == "validate") {
				
				if(!$config->get_bool("signup_validation_enabled")){
					$page->set_mode("redirect");
					$page->set_redirect(make_link("account/login"));
				}
			
				switch ($event->get_arg(1)) {
					case "resend":
						if(isset($_POST["name"])){
							if(preg_match('/^[a-zA-Z0-9-_]+$/', $_POST["name"])){
								$duser = User::by_name($_POST["name"]);
								
								$link = make_http(make_link("account/validate/$duser->name/$duser->validate"));
								$activation_link = '<a href="'.$link.'">'.$link.'</a>';
								
								$email = new Email($duser->email, "Validation Code", "Validation Code", "You need validate your account. Please follow the next link<br><br>".$activation_link);
								$sent = $email->send();
								
								if($sent){
									$page->set_mode("redirect");
									$page->set_redirect(make_link("account/validate"));
								}
								else{
									$this->theme->display_error("Error", "Error resending the verification code. Please contact support.");
								}
							}
						}
						else{
							$this->theme->display_resend_validation_page($page);
						}
					break;
					default:
						$name = $event->get_arg(1);
						$code = $event->get_arg(2);
						
						if(isset($_POST["name"]) || isset($_POST["code"])){
							$name = $_POST["name"];
							$code = $_POST["code"];
						}
						
						if(!preg_match('/^[a-zA-Z0-9-_]+$/', $name)){
							$this->theme->display_validation_page($page);
						}
						else if(!preg_match('/^[a-fA-F0-9]{16}$/', $code)){
							$this->theme->display_validation_page($page);
						}
						else {
							$this->validate($page, $name, $code);
						}
						break;
				}
			}
			
			// account/recover  - Recover Password
			else if($event->get_arg(0) == "recover") {
				$name = NULL;
				$email = NULL;
				if(isset($_POST["name"]) || isset($_POST["email"])){
					$name = $_POST["name"];
					$email = $_POST["email"];
				}
				
				if(!preg_match('/^[a-zA-Z0-9-_]+$/', $name)){
					$this->theme->display_recover_page($page);
				}
				else if(!isset($email) || !preg_match('/^[_a-zA-Z0-9-]+(\.[_a-zA-Z0-9-]+)*@[a-zA-Z0-9-]+(\.[a-zA-Z0-9-]+)+$/', $email)){
					$this->theme->display_recover_page($page);
				}
				else {
					$this->recover($page, $name, $email);
				}
			}
			else if($event->get_arg(0) == "set_more") {
				$this->set_more_wrapper($page);
			}
			
			// account/messages  - Account Messages
			else if($event->get_arg(0) == "messages") {
				if($user->is_anon()){
					$page->set_mode("redirect");
					$page->set_redirect(make_link("post/list"));
				}
				switch ($event->get_arg(1)) {
					case "new":
						$user_id = $event->get_arg(2);
						if(!is_null($user_id)){
							$duser = User::by_id($user_id);
							$user_name = $duser->name;
						}
						else{
							$user_name = NULL;
						}
						$this->theme->display_composer($page, $user_name);
						break;
					case "inbox":
						$this->theme->display_inbox($page, $this->get_inbox($user, "'r','u'"), "inbox");
						break;
					case "outbox":
						$this->theme->display_outbox($page, $this->get_outbox($user));
						break;
					case "saved":
						$this->theme->display_inbox($page, $this->get_inbox($user, "'s'"), "saved");
						break;
					case "deleted":
						$this->theme->display_inbox($page, $this->get_inbox($user, "'d'"), "deleted");
						break;
					case "view":
						$pm_id = $event->get_arg(2);
					
						$message = $this->view_message($user, $pm_id);
						$this->theme->display_messages_viewer($page, $message["subject"], $message["message"]);
						
						$user_id = $message["from_id"];
						
						if(!is_null($user_id)){
							$duser = User::by_id($user_id);
							$user_name = $duser->name;
						}
						else{
							$user_name = NULL;
						}
						$this->theme->display_composer($page, $user_name, $message["subject"], $message["message"]);
						break;
					case "empty":
						$this->real_delete_message($user);
						$page->set_mode("redirect");
						$page->set_redirect(make_link("account/messages/inbox"));
						break;
					case "action":
						switch ($_POST["action"]) {
							case "Send":
								$this->add_message();
							break;
							case "Save":
								$this->save_message();
							break;
							case "Delete":
								$this->remove_message();
							break;
							case "Un-Save":
							case "Un-Delete":
								$this->undone_message();
							break;
						}
						$page->set_mode("redirect");
						$page->set_redirect(make_link("account/messages/inbox"));
						break;
					case "complete":
						if(isset($_GET['s'])) {
							$all = $database->get_all(
								"SELECT name FROM users WHERE name LIKE ? LIMIT 10",
								array($_GET["s"]."%"));
				
							$res = array();
							foreach($all as $row) {$res[] = $row["name"];}
				
							$page->set_mode("data");
							$page->set_type("text/plain");
							$page->set_data(implode("\n", $res));
						}
						break;
					default:
						$page->set_mode("redirect");
						$page->set_redirect(make_link("account/messages/inbox"));
						break;
				}
				$this->theme->display_messages_sidebar($page, $this->get_count_unread($user));
			}
		}		
	}

	public function onUserPageBuilding($event) {
		global $page, $user, $config;

		$duser = User::by_name($event->display_user->name);

		$h_join_date = autodate($event->display_user->join_date);
		$h_login_date = autodate($event->display_user->login_date);
		$event->add_stats(array("Join Date", "$h_join_date"), 10);
		$event->add_stats(array("Last Login", "$h_login_date"), 20);
		$event->add_stats(array("Role", ucfirst($duser->role_to_human())), 30);

		ksort($event->stats);
		$this->theme->display_user_page($duser, $event->stats);
		if($user->id == $duser->id) {
			$ubbe = new UserBlockBuildingEvent();
			send_event($ubbe);
			ksort($ubbe->parts);
			$this->theme->display_user_links($page, $user, $ubbe->parts);
		}
	}

	public function onSetupBuilding($event) {
		global $config;

		$hosts = array(
			"None" => "none",
			"Gravatar" => "gravatar"
		);

		$sb = new SetupBlock("Account Options");
		$sb->add_bool_option("login_signup_enabled", "Allow new signups: ");
		$sb->add_bool_option("signup_validation_enabled", "<br>Validate accounts: ");
		$sb->add_text_option("signup_validation_email", "<br>Validation Email: ");
		$sb->add_longtext_option("signup_tac", "<br>Terms &amp; Conditions:<br>");
		$sb->add_choice_option("avatar_host", $hosts, "<br>Avatars: ");

		if($config->get_string("avatar_host") == "gravatar") {
			$sb->add_label("<br><br><b>Gravatar Options</b>");
			$sb->add_choice_option("avatar_gravatar_type",
				array(
					'Default'=>'default',
					'Wavatar'=>'wavatar',
					'Monster ID'=>'monsterid',
					'Identicon'=>'identicon'
				),
				"<br>Type: ");
			$sb->add_choice_option("avatar_gravatar_rating",
				array('G'=>'g', 'PG'=>'pg', 'R'=>'r', 'X'=>'x'),
				"<br>Rating: ");
		}

		$event->panel->add_block($sb);
	}
	
	public function onPrefBuilding($event) {
			$pb = new PrefBlock("Email Options");
			$pb->add_bool_option("send_mail_messages", "Notify Me On New Message: ");
			$event->panel->add_block($pb);
	}

	public function onUserBlockBuilding($event) {
		global $user;
		$event->add_link("Messages", make_link("account/messages/inbox"), 60);
		$event->add_link("My Profile", make_link("account/profile/$user->name"), 70);
		$event->add_link("My Options", make_link("account/options/$user->name"), 80);
		$event->add_link("Log Out", make_link("account/logout"), 100);
	}

	public function onUserCreation($event) {
		$this->check_user_creation($event);
		$this->create_user($event);
	}
	
	public function onUserBan($event) {
		$this->add_user_ban($event->duser, $event->end_date, $event->reason);
	}
// }}}
// Things done *with* the user {{{
	private function validate($page, $name, $code)  {
		global $user;
				
		$duser = User::by_validation_and_name($name, $code);
		if(!is_null($duser)) {
			$duser->set_user(TRUE);
			$page->set_mode("redirect");
			$page->set_redirect(make_link("account/login"));
		}
		else{
			$this->theme->display_error("Error", "No user with those details was found.");
		}
	}
	
	private function recover($page, $name, $email)  {
		global $database, $user;
				
		$duser = User::by_name($name);
		if(!is_null($duser) && ($duser->email == $email) && ($duser->role != "b")) {
			
			$pass = substr(md5(microtime()), 0, 16);
			
			$email = new Email($duser->email, "New Password", "New Password", "Your new password is: $pass");
			$sent = $email->send();
			
			if($sent){
				$database->Execute("UPDATE users SET pass = ? WHERE id = ?", array(md5($duser->name.$pass), $duser->id));
			}
			
			$page->set_mode("redirect");
			$page->set_redirect(make_link("account/login"));
		}
		elseif($duser->role == "b"){
			$this->theme->display_error("Account", "Your account has been suspended.");
		}
		else{
			$this->theme->display_error("Account", "No user with those details was found.");
		}
	}
	
	public function login($page)  {
		global $database;

		$name = $_POST['user'];
		$pass = $_POST['pass'];
		$hash = md5(strtolower($name) . $pass);

		$duser = User::by_name_and_hash($name, $hash);
		
		$this->reset_user_ban($duser);
		
		if(!is_null($duser)) {
			if(($duser->role != "b") && ($duser->role != "g")){
			
				$this->set_login_cookie($name, $pass);
				
				switch ($duser->role) {
					case "o":
						log_warning("user", "Owner logged in ({$duser->name})");
						break;
					case "a":
						log_warning("user", "Admin logged in ({$duser->name})");
						break;
					case "m":
						log_warning("user", "Moderator logged in ({$duser->name})");
						break;
					case "c":
						log_warning("user", "Contributor logged in ({$duser->name})");
						break;
					case "u":
						log_info("user", "User logged in ({$duser->name})");
						break;
				}
				
				$ip = $_SERVER['REMOTE_ADDR'];
				
				$database->Execute("UPDATE users SET ip = ?, logindate = now() WHERE id = ?", array($ip, $duser->id));
				
				$page->set_mode("redirect");
				if(!isset($_GET['easysetup'])) {
					$page->set_redirect(make_link("account/profile/$duser->name"));
				} else {
					$page->set_redirect(make_link("setup/easy"));
				}
			}
			elseif($duser->role != "b"){
				$validate_link = "<a href='".make_link("account/validate")."'>Validate</a>";
				$this->theme->display_error("Error", "You need validate your account. $validate_link");
			}
			else{
				$this->theme->display_error("Account", "Your account has been suspended.");
			}
		}
		else {
			$this->theme->display_error("Error", "No user with those details was found");
		}
	}

	private function check_user_creation($event) {
		$name = $event->username;
		$pass = $event->password;
		$email = $event->email;

		global $database;
		
		$ip = $_SERVER['REMOTE_ADDR'];
		
		if(strlen($name) < 1) {
			throw new UserCreationException("Username must be at least 1 character");
		}
		else if(!preg_match('/^[a-zA-Z0-9-_]+$/', $name)) {
			throw new UserCreationException(
					"Username contains invalid characters. Allowed characters are ".
					"letters, numbers, dash, and underscore");
		}
		else if(!preg_match('/^[_a-zA-Z0-9-]+(\.[_a-zA-Z0-9-]+)*@[a-zA-Z0-9-]+(\.[a-zA-Z0-9-]+)+$/', $email)) {
			throw new UserCreationException(
					"Email address is not valid.");
		}
		else if($database->db->GetRow("SELECT * FROM users WHERE name = ?", array($name))) {
			throw new UserCreationException("That username is already taken");
		}
		else if($database->db->GetRow("SELECT * FROM user_bans WHERE user_ip = ?", array($ip))) {
			throw new UserCreationException("The ip address has been suspended.");
		}
	}

	private function create_user($event) {
		global $config, $page, $database;
		
		
		if(!$config->get_bool("signup_validation_enabled")){
			$validate = NULL;
			$sent = TRUE;
			$role = "u";
		}
		else{
			$validate = substr(md5(microtime()), 0, 16);
			$role = "g";
			
			$link = make_http(make_link("account/validate/$event->username/$validate"));
			$activation_link = '<a href="'.$link.'">'.$link.'</a>';
			
			$email = new Email($event->email, "Validation Code", "Validation Code", "You need validate your account. Please follow the next link<br><br>".$activation_link);
			$sent = $email->send();
		}
		
		$hash = md5(strtolower($event->username) . $event->password);
						
		$ip = $_SERVER['REMOTE_ADDR'];
		
		if($sent){
			$database->Execute(
					"INSERT INTO users (ip, name, pass, joindate, validate, role, email) VALUES (?, ?, ?, now(), ?, ?, ?)",
					array($ip, $event->username, $hash, $validate, $role,$event->email));
			$uid = $database->db->Insert_ID();
			log_info("user", "Created User #$uid ({$event->username})");
		}
		else{
			$this->theme->display_error("Error", "Theres was an error triying to send the email");
		}
	}

	private function set_login_cookie($name, $pass) {
		global $config;

		$addr = get_session_ip($config);
		$hash = md5(strtolower($name) . $pass);

		set_prefixed_cookie("user", $name,
				time()+60*60*24*365, '/');
		set_prefixed_cookie("session", md5($hash.$addr),
				time()+60*60*24*$config->get_int('login_memory'), '/');
	}
		
	private function add_user_ban($duser, $end_date, $reason){
		global $database, $user;
				
		if($user->is_owner() || $user->is_admin()){
			$database->Execute("INSERT INTO user_bans (banner_id, user_id, user_ip, user_role, end_date, reason) VALUES (?, ?, ?, ?, ?, ?)", 
			array($user->id, $duser->id, $duser->ip, $duser->role, $end_date, $reason));
			$database->Execute("UPDATE users SET role = 'b' WHERE id = ?", array($duser->id));
		}
	}
	
	private function reset_user_ban($duser){
		global $database, $user;
		
		if($duser){
			$banned = $database->db->GetRow("SELECT end_date, user_role FROM user_bans WHERE user_id = ?", array($duser->id));
			if($banned){
				$end_date = date("Y-m-d", strtotime($banned["end_date"]));
				$today = date("Y-m-d", time());
				
				echo($end_date." - ".$today);
			
				if($today >= $end_date || ($user->is_owner() || $user->is_admin())){
					$database->Execute("UPDATE users SET role = ? WHERE id = ?", array($banned["user_role"], $duser->id));
					$database->execute("DELETE FROM user_bans WHERE user_id = ?", array($duser->id));
				}
			}
		}	
	}
//}}}
// Things done *to* the user {{{
	private function change_password_wrapper($page) {
		global $user;
		global $config;
		global $database;

		if($user->is_anon()) {
			$this->theme->display_error("Error", "You aren't logged in");
		}
		else if(isset($_POST['id']) && isset($_POST['pass1']) && isset($_POST['pass2'])) {
			$id = $_POST['id'];
			$pass1 = $_POST['pass1'];
			$pass2 = $_POST['pass2'];

			$duser = User::by_id($id);

			if((!$user->is_admin()) && ($duser->name != $user->name)) {
				$this->theme->display_error("Error", "You need to be an admin to change other people's passwords");
			}
			else if($pass1 != $pass2) {
				$this->theme->display_error("Error", "Passwords don't match");
			}
			else {
				// FIXME: send_event()
				$duser->set_password($pass1);

				if($id == $user->id) {
					$this->set_login_cookie($duser->name, $pass1);
				}
				
				$page->set_mode("redirect");
				$page->set_redirect(make_link("account/profile/{$duser->name}"));
			}
		}
	}

	private function change_email_wrapper($page) {
		global $user;
		global $config;
		global $database;

		if($user->is_anon()) {
			$this->theme->display_error("Error", "You aren't logged in");
		}
		else if(isset($_POST['id']) && isset($_POST['address'])) {
			$id = $_POST['id'];
			$address = $_POST['address'];

			$duser = User::by_id($id);

			if((!$user->is_admin()) && ($duser->name != $user->name)) {
				$this->theme->display_error("Error", "You need to be an admin to change other people's addressess");
			}
			else if(!preg_match('/^[_a-zA-Z0-9-]+(\.[_a-zA-Z0-9-]+)*@[a-zA-Z0-9-]+(\.[a-zA-Z0-9-]+)+$/', $address)) {
				$this->theme->display_error("Error", "Email address is not valid.");
			}
			else {
				$duser->set_email($address);

				$page->set_mode("redirect");
				$page->set_redirect(make_link("account/profile/{$duser->name}"));
			}
		}
	}

	private function set_more_wrapper($page) {
		global $user;
		global $config;
		global $database;

		$page->set_title("Error");
		$page->set_heading("Error");
		
	    if(!isset($_POST['id']) || !is_numeric($_POST['id'])) {
			$page->add_block(new Block("No ID Specified",
					"You need to specify the account number to edit"));
		}
		else {
			if(isset($_POST['role'])) {
				$role = html_escape($_POST['role']);
				if(strlen($role)==1) {
					$duser = User::by_id($_POST['id']);
					if(!$user->is_admin()) {
						$page->add_block(new Block("Not Admin", "Only admins can edit accounts"));
					}
					else if(!$user->is_admin() && !$user->is_owner()) {
						$page->add_block(new Block("Not Owner", "Only owners can edit accounts"));
					}
					else{
						$duser->set_role($role);
						$page->set_mode("redirect");
						$page->set_redirect(make_link("account/profile/{$duser->name}"));
					}
				}
			} else {
				die("Invalid or no user level given: ".$_POST['role']);
			}
		}
	}
// }}}
// private messages {{{
	private function add_message() {
		global $user, $database;
		
		$to = $_POST["to"];
		$subject = $_POST["subject"];
		$message = $_POST["message"];
		$priority = $_POST["message"];
		
		$duser = User::by_name($to);
		$prefs = Prefs::by_id($duser->id);
		
		$send_email = $prefs->get_bool("send_mail_messages");
			
		$priority = "n";
		if(in_array($_POST["priority"], array("l","n","h"))){
			$priority = $_POST["priority"];
		}
		
		$ip = $_SERVER['REMOTE_ADDR'];
		
		$database->execute("
				INSERT INTO messages(
					from_id, from_ip, to_id,
					sent_date, subject, message, priority)
				VALUES(?, ?, ?, now(), ?, ?, ?)",
			array($user->id, $ip, $duser->id, $subject, $message, $priority)
		);
		log_info("pm", "Sent PM to User #{$user->id}");
		
		$insert = $database->get_row("SELECT LAST_INSERT_ID() AS id", array());
		
		if($send_email){
			$link = make_http(make_link("account/messages/view/".$insert["id"]));
			$view_link = '<a href="'.$link.'">Answer</a>';
		
			$email = new Email($duser->email, "New Message", "New Message", "You got a new message from $user->name.<br><br>Subject: $subject<br>Message: $message<br><br>".$view_link);
			$email->send();
		}
	}
	
	private function view_message($user, $id) {
		global $database;
		$owner = $database->get_row("SELECT to_id FROM messages WHERE id = ?", array($id));
		$pm = NULL;
		if($owner["to_id"] == $user->id || $user->is_admin()){
			$database->execute("UPDATE messages SET status = 'r' WHERE id = ?", array($id));
			$pm = $database->get_row("SELECT * FROM messages WHERE id = ?", array($id));
		}
		return $pm;
	}
	
	private function remove_message() {
		global $database;
		foreach($_POST['id'] as $id) {
			$database->execute("UPDATE messages SET status = 'd' WHERE id = ?", array($id));
		}
	}
	
	private function real_delete_message() {
		global $user, $database;
		$database->execute("DELETE FROM messages WHERE to_id = ? AND status = 'd'", array($user->id));
	}
	
	private function save_message() {
		global $database;
		foreach($_POST['id'] as $id) {
			$database->execute("UPDATE messages SET status = 's' WHERE id = ?", array($id));
		}
	}
	
	private function undone_message() {
		global $database;
		foreach($_POST['id'] as $id) {
			$database->execute("UPDATE messages SET status = 'r' WHERE id = ?", array($id));
		}
	}
	
	private function get_inbox($user, $status) {
		global $database;
		$arr = $database->get_all("
			SELECT messages.*,user_from.name AS from_name
			FROM messages
			JOIN users AS user_from ON user_from.id=from_id
			WHERE to_id = ? AND messages.status IN (".$status.")
			ORDER BY messages.sent_date DESC", array($user->id));
			
		return $arr;
	}
	
	private function get_outbox($user) {
		global $database;
		$arr = $database->get_all("
			SELECT messages.*,user_to.name AS to_name
			FROM messages
			JOIN users AS user_to ON user_to.id=to_id
			WHERE from_id = ?
			ORDER BY messages.sent_date DESC", array($user->id));
			
		return $arr;
	}
		
	private function get_count_unread($user) {
		global $database;
		$arr = $database->db->GetOne("SELECT COUNT(*) FROM messages WHERE to_id = ? AND status = 'u'", array($user->id));
		return $arr;
	}
// }}}
}
add_event_listener(new UserPage());
?>