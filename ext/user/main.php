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

class UserCreationException extends SCoreException {}

class UserPage extends SimpleExtension {
	public function onInitExt(Event $event) {
		global $config;
		$config->set_default_bool("login_signup_enabled", true);
		$config->set_default_int("login_memory", 365);
		$config->set_default_string("avatar_host", "none");
		$config->set_default_string("account_email", "example@example.com");
		$config->set_default_int("avatar_gravatar_size", 80);
		$config->set_default_string("avatar_gravatar_default", "");
		$config->set_default_string("avatar_gravatar_rating", "g");
		$config->set_default_bool("login_tac_bbcode", true);
	}

	public function onPageRequest(Event $event) {
		global $config, $database, $page, $user;

		// user info is shown on all pages
		if($user->is_anonymous()) {
			$this->theme->display_login_block($page);
		}
		else {
			$ubbe = new UserBlockBuildingEvent();
			send_event($ubbe);
			ksort($ubbe->parts);
			$this->theme->display_user_block($page, $user, $ubbe->parts);
		}

		if($event->page_matches("account")) {
			if($event->get_arg(0) == "login") {
				if(isset($_POST['user']) && isset($_POST['pass'])) {
					$this->login($page);
				}
				else {
					$this->theme->display_login_page($page);
				}
			}
			else if($event->get_arg(0) == "logout") {
				set_prefixed_cookie("session", "", time()+60*60*24*$config->get_int('login_memory'), "/");
				log_info("user", "Logged out");
				$page->set_mode("redirect");
				$page->set_redirect(make_link());
			}
			else if($event->get_arg(0) == "change_pass") {
				$this->change_password_wrapper($page);
			}
			else if($event->get_arg(0) == "change_email") {
				$this->change_email_wrapper($page);
			}
			else if($event->get_arg(0) == "recover") {
				$user = User::by_name($_POST['username']);
				if(is_null($user)) {
					$this->theme->display_error($page, "Error", "There's no user with that name");
				}
				else {
					$this->theme->display_login_page($page);
				}
			}
			else if($event->get_arg(0) == "create") {
				if(!$config->get_bool("login_signup_enabled")) {
					$this->theme->display_signups_disabled($page);
				}
				else if(!isset($_POST['name'])) {
					$this->theme->display_signup_page($page);
				}
				else if($_POST['pass1'] != $_POST['pass2']) {
					$this->theme->display_error($page, "Password Mismatch", "Passwords don't match");
				}
				else {
					try {
						if(!captcha_check()) {
							throw new UserCreationException("Error in captcha");
						}

						$uce = new UserCreationEvent($_POST['name'], $_POST['pass1'], $_POST['email']);
						send_event($uce);
						$page->set_mode("redirect");
						$page->set_redirect(make_link("account/validate"));
					}
					catch(UserCreationException $ex) {
						$this->theme->display_error($page, "User Creation Error", $ex->getMessage());
					}
				}
			}
			else if($event->get_arg(0) == "validate") {
				
				$name = $event->get_arg(1);
				$code = $event->get_arg(2);
				
				if(isset($_POST["name"]) || isset($_POST["code"])){
					$name = $_POST["name"];
					$code = $_POST["code"];
				}
				
				if(!isset($name)){
					$this->theme->display_validation_page($page);
				}
				else if(!isset($code) || !strlen($code) == 16){
					$this->theme->display_validation_page($page);
				}
				else {
					$this->validate($page, $name, $code);
				}
			}
			else if($event->get_arg(0) == "set_more") {
				$this->set_more_wrapper($page);
			}
			else if($event->get_arg(0) == "list") {
// select users.id,name,joindate,admin,
// (select count(*) from images where images.owner_id=users.id) as images,
// (select count(*) from comments where comments.owner_id=users.id) as comments from users;

// select users.id,name,joindate,admin,image_count,comment_count
// from users
// join (select owner_id,count(*) as image_count from images group by owner_id) as _images on _images.owner_id=users.id
// join (select owner_id,count(*) as comment_count from comments group by owner_id) as _comments on _comments.owner_id=users.id;
				$this->theme->display_user_list($page, User::by_list(0), $user);
			}
		}

		if(($event instanceof PageRequestEvent) && $event->page_matches("user")) {
			$display_user = ($event->count_args() == 0) ? $user : User::by_name($event->get_arg(0));
			if($event->count_args() == 0 && $user->is_anonymous()) {
				$this->theme->display_error($page, "Not Logged In",
					"You aren't logged in. First do that, then you can see your stats.");
			}
			else if(!is_null($display_user)) {
				send_event(new UserPageBuildingEvent($display_user));
			}
			else {
				$this->theme->display_error($page, "No Such User",
					"If you typed the ID by hand, try again; if you came from a link on this ".
					"site, it might be bug report time...");
			}
		}
	}

	public function onUserPageBuilding(Event $event) {
		global $page, $user, $config;

		$h_join_date = html_escape($event->display_user->join_date);
		$event->add_stats("Join date: $h_join_date", 10);

		$av = $event->display_user->get_avatar_html();
		if($av) $event->add_stats($av, 0);

		ksort($event->stats);
		$this->theme->display_user_page($event->display_user, $event->stats);
		if($user->id == $event->display_user->id) {
			$ubbe = new UserBlockBuildingEvent();
			send_event($ubbe);
			ksort($ubbe->parts);
			$this->theme->display_user_links($page, $user, $ubbe->parts);
		}
		if(
			($user->is_admin() || $user->id == $event->display_user->id) &&
			($user->id != $config->get_int('anon_id'))
		) {
			$this->theme->display_ip_list(
				$page,
				$this->count_upload_ips($event->display_user),
				$this->count_comment_ips($event->display_user));
		}
	}

	public function onSetupBuilding(Event $event) {
		global $config;

		$hosts = array(
			"None" => "none",
			"Gravatar" => "gravatar"
		);

		$sb = new SetupBlock("User Options");
		$sb->add_bool_option("login_signup_enabled", "Allow new signups: ");
		$sb->add_text_option("account_email", "<br>Verification Email:");
		$sb->add_longtext_option("login_tac", "<br>Terms &amp; Conditions:<br>");
		$sb->add_choice_option("avatar_host", $hosts, "<br>Avatars: ");

		if($config->get_string("avatar_host") == "gravatar") {
			$sb->add_label("<br>&nbsp;<br><b>Gravatar Options</b>");
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

	public function onUserBlockBuilding(Event $event) {
		$event->add_link("My Profile", make_link("user"));
		$event->add_link("Log Out", make_link("account/logout"), 99);
	}

	public function onUserCreation(Event $event) {
		$this->check_user_creation($event);
		$this->create_user($event);
	}

	public function onSearchTermParse(Event $event) {
		$matches = array();
		if(preg_match("/^(poster|user)=(.*)$/i", $event->term, $matches)) {
			$user = User::by_name($matches[2]);
			if(!is_null($user)) {
				$user_id = $user->id;
			}
			else {
				$user_id = -1;
			}
			$event->add_querylet(new Querylet("images.owner_id = $user_id"));
		}
		else if(preg_match("/^(poster|user)_id=([0-9]+)$/i", $event->term, $matches)) {
			$user_id = int_escape($matches[2]);
			$event->add_querylet(new Querylet("images.owner_id = $user_id"));
		}
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
			$this->theme->display_error($page, "Error", "No user with those details was found.");
		}
	}
	
	private function login($page)  {
		global $user;

		$name = $_POST['user'];
		$pass = $_POST['pass'];
		$hash = md5(strtolower($name) . $pass);

		$duser = User::by_name_and_hash($name, $hash);
		if(!is_null($duser)) {
			if(!($duser->role == "g")){
				$user = $duser;
				$this->set_login_cookie($name, $pass);
				if($user->is_admin()) {
					log_warning("user", "Admin logged in");
				}
				else if($user->is_mod()) {
					log_warning("user", "Moderator logged in");
				}
				else if($user->is_user()) {
					log_info("user", "User logged in");
				}
				else {
					log_info("user", "User logged in");
				}
				$page->set_mode("redirect");
				$page->set_redirect(make_link("user"));
			}
			else{
				$validate_link = "<a href='".make_link("account/validate")."'>Validate</a>";
				$this->theme->display_error($page, "Error", "You need validate your account. $validate_link");
			}
		}
		else {
			$this->theme->display_error($page, "Error", "No user with those details was found");
		}
	}

	private function check_user_creation($event) {
		$name = $event->username;
		$pass = $event->password;
		$email = $event->email;

		global $database;

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
	}

	private function create_user($event) {
		global $config, $page, $database;

		$hash = md5(strtolower($event->username) . $event->password);
		$email = (!empty($event->email)) ? $event->email : null;
		$code = substr(md5(microtime()), 0, 16);

		// if there are currently no admins, the new user should be one (a for admin, g for non validated users)
		$need_admin = ($database->db->GetOne("SELECT COUNT(*) FROM users WHERE role IN ('o', 't', '1')") == 0);
		$role = $need_admin ? 'o' : 'g';
		$validate = $need_admin ? null : $code;
				
		$link = make_http(make_link("account/validate/$event->username/$validate"));
		$activation_link = '<a href="'.$link.'">'.$link.'</a>';
		
		$site = $config->get_string("title");
		$site_email = $config->get_string("account_email");
		
		$headers  = "From: $site <$site_email>\r\n";
		$headers .= "Reply-To: $site_email\r\n";
		$headers .= "X-Mailer: PHP/" . phpversion(). "\r\n";
		$headers .= "errors-to: $site_email\r\n";
		$headers .= "Date: " . date(DATE_RFC2822);
		$headers .= 'MIME-Version: 1.0' . "\r\n";
		$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
		
		$sent = mail($email,"Validation Code",$activation_link,$headers);
		
		if($sent){
			$database->Execute(
					"INSERT INTO users (name, pass, joindate, validate, role, email) VALUES (?, ?, now(), ?, ?, ?)",
					array($event->username, $hash, $validate, $role, $email));
			$uid = $database->db->Insert_ID();
			log_info("user", "Created User #$uid ({$event->username})");
		}
		else{
			$this->theme->display_error($page, "Error", "Theres was an error triying to send the email");
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
//}}}
// Things done *to* the user {{{
	private function change_password_wrapper($page) {
		global $user;
		global $config;
		global $database;

		if($user->is_anonymous()) {
			$this->theme->display_error($page, "Error", "You aren't logged in");
		}
		else if(isset($_POST['id']) && isset($_POST['pass1']) && isset($_POST['pass2'])) {
			$id = $_POST['id'];
			$pass1 = $_POST['pass1'];
			$pass2 = $_POST['pass2'];

			$duser = User::by_id($id);

			if((!$user->is_admin()) && ($duser->name != $user->name)) {
				$this->theme->display_error($page, "Error",
						"You need to be an admin to change other people's passwords");
			}
			else if($pass1 != $pass2) {
				$this->theme->display_error($page, "Error", "Passwords don't match");
			}
			else {
				// FIXME: send_event()
				$duser->set_password($pass1);

				if($id == $user->id) {
					$this->set_login_cookie($duser->name, $pass1);
					$page->set_mode("redirect");
					$page->set_redirect(make_link("user"));
				}
				else {
					$page->set_mode("redirect");
					$page->set_redirect(make_link("user/{$duser->name}"));
				}
			}
		}
	}

	private function change_email_wrapper($page) {
		global $user;
		global $config;
		global $database;

		if($user->is_anonymous()) {
			$this->theme->display_error($page, "Error", "You aren't logged in");
		}
		else if(isset($_POST['id']) && isset($_POST['address'])) {
			$id = $_POST['id'];
			$address = $_POST['address'];

			$duser = User::by_id($id);

			if((!$user->is_admin()) && ($duser->name != $user->name)) {
				$this->theme->display_error($page, "Error",
						"You need to be an admin to change other people's addressess");
			}
			else if(!preg_match('/^[_a-zA-Z0-9-]+(\.[_a-zA-Z0-9-]+)*@[a-zA-Z0-9-]+(\.[a-zA-Z0-9-]+)+$/', $address)) {
				$this->theme->display_error($page, "Error",
						"Email address is not valid.");
			}
			else {
				$duser->set_email($address);

				if($id == $user->id) {
					$page->set_mode("redirect");
					$page->set_redirect(make_link("user"));
				}
				else {
					$page->set_mode("redirect");
					$page->set_redirect(make_link("user/{$duser->name}"));
				}
			}
		}
	}

	private function set_more_wrapper($page) {
		global $user;
		global $config;
		global $database;

		$page->set_title("Error");
		$page->set_heading("Error");
		$page->add_block(new NavBlock());
		
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
						if($duser->id == $user->id) {
							$page->set_redirect(make_link("user"));
						}
						else {
							$page->set_redirect(make_link("user/{$duser->name}"));
						}
					}
				}
			} else {
				die("Invalid or no user level given: ".$_POST['role']);
			}
		}
	}
// }}}
// ips {{{
	private function count_upload_ips($duser) {
		global $database;
		$rows = $database->db->GetAssoc("
				SELECT
					owner_ip,
					COUNT(images.id) AS count,
					MAX(posted) AS most_recent
				FROM images
				WHERE owner_id=?
				GROUP BY owner_ip
				ORDER BY most_recent DESC", array($duser->id), false, true);
		return $rows;
	}
	private function count_comment_ips($duser) {
		global $database;
		$rows = $database->db->GetAssoc("
				SELECT
					owner_ip,
					COUNT(comments.id) AS count,
					MAX(posted) AS most_recent
				FROM comments
				WHERE owner_id=?
				GROUP BY owner_ip
				ORDER BY most_recent DESC", array($duser->id), false, true);
		return $rows;
	}
// }}}
}
add_event_listener(new UserPage());
?>
