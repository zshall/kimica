<?php
/** @private */
function _new_user($row) {
	return new User($row);
}

/**
 * An object representing a row in the "users" table.
 *
 * The currently logged in user will always be accessable via the global variable $user
 */
class User {
	var $id;
	var $ip;
	var $name;
	var $email;
	var $join_date;
	var $validate;
	var $role;
	var $owner;
	var $admin;
	var $mod;
	var $user;
	var $cont;
	var $anon;
	var $banned;

	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	* Initialisation                                               *
	*                                                              *
	* User objects shouldn't be created directly, they should be   *
	* fetched from the database like so:                           *
	*                                                              *
	*    $user = User::by_name("bob");                             *
	* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

	/**
	 * One will very rarely construct a user directly, more common
	 * would be to use User::by_id, User::by_session, etc
	 *
	 * Roles are definied by enum('g','u','m','a') guest, user, moderator, admin
	 */
	public function User($row) {
		$this->id = int_escape($row['id']);
		$this->ip = $row['ip'];
		$this->name = $row['name'];
		$this->email = $row['email'];
		$this->join_date = $row['joindate'];
		$this->validate = $row['validate'];
		$this->role = $row['role'];
		$this->owner = ($row['role'] == 'o');
		$this->admin = ($row['role'] == 'a' || $row['role'] == 'o');
		$this->mod = ($row['role'] == 'm' || $row['role'] == 'a' || $row['role'] == 'o');
		$this->cont = ($row['role'] == 'c');
		$this->user = ($row['role'] == 'u');
		$this->anon = ($row['role'] == 'g');
		$this->banned = ($row['role'] == 'b');
	}

	public static function by_session($name, $session) {
		global $config, $database;
		if($database->engine->name == "mysql") {
			$query = "SELECT * FROM users WHERE name = ? AND md5(concat(pass, ?)) = ?";
		}
		else {
			$query = "SELECT * FROM users WHERE name = ? AND md5(pass || ?) = ?";
		}
		$row = $database->get_row($query, array($name, get_session_ip($config), $session));
		return is_null($row) ? null : new User($row);
	}

	public static function by_id($id) {
		assert(is_numeric($id));
		global $database;
		$row = $database->get_row("SELECT * FROM users WHERE id = ?", array($id));
		return is_null($row) ? null : new User($row);
	}

	public static function by_name($name) {
		assert(is_string($name));
		global $database;
		$row = $database->get_row("SELECT * FROM users WHERE name = ?", array($name));
		return is_null($row) ? null : new User($row);
	}
	
	public static function by_email($email) {
		assert(is_string($email));
		global $database;
		$row = $database->get_row("SELECT * FROM users WHERE email = ?", array($email));
		return is_null($row) ? null : new User($row);
	}

	public static function by_name_and_hash($name, $hash) {
		assert(is_string($name));
		assert(is_string($hash));
		assert(strlen($hash) == 32);
		global $database;
		$row = $database->get_row("SELECT * FROM users WHERE name = ? AND pass = ?", array($name, $hash));
		return is_null($row) ? null : new User($row);
	}
	
	public static function by_validation_and_name($name, $code) {
		assert(is_string($name));
		assert(is_string($code));
		assert(strlen($code) == 16);
		global $database;
		$row = $database->get_row("SELECT * FROM users WHERE name = ? AND validate = ?", array($name, $code));
		return is_null($row) ? null : new User($row);
	}

	public static function by_list($offset, $limit=50) {
		assert(is_numeric($offset));
		assert(is_numeric($limit));
		global $database;
		$rows = $database->get_all("SELECT * FROM users WHERE id >= ? AND id < ?", array($offset, $offset+$limit));
		return array_map("_new_user", $rows);
	}


	/*
	 * useful user object functions start here
	 */

	/**
	 * Test if this user is anonymous (not logged in)
	 *
	 * @retval bool
	 */
	public function is_anonymous() {
		global $config;
		return ($this->id == $config->get_int('anon_id'));
	}

	/**
	 * Test if this user is logged in
	 *
	 * @retval bool
	 */
	public function is_logged_in() {
		global $config;
		return ($this->id != $config->get_int('anon_id'));
	}
	
	/**
	 * Test if this user is an owner
	 *
	 * @retval bool
	 */
	public function is_owner() {
		return $this->owner;
	}

	/**
	 * Test if this user is an administrator
	 *
	 * @retval bool
	 */
	public function is_admin() {
		return $this->admin;
	}
	
	/**
	 * Test if this user is an moderator
	 *
	 * @retval bool
	 */
	public function is_mod() {
		return $this->mod;
	}
	
	/**
	 * Test if this user is a subscriber user
	 *
	 * @retval bool
	 */
	public function is_cont() {
		return $this->cont;
	}
	
	/**
	 * Test if this user is an verified user
	 *
	 * @retval bool
	 */
	public function is_user() {
		return $this->user;
	}
	
	/**
	 * Test if this user is an verified user
	 *
	 * @retval bool
	 */
	public function is_anon() {
		global $config;
		return ($this->anon || ($this->id == $config->get_int('anon_id')));
	}
	
	public function set_role($role) {
		global $database;
		switch($role) { // security check
			case 'o':
			case 'a':
			case 'm':
			case 'c':
			case 'u':
			case 'g':
				$database->Execute("UPDATE users SET role=? WHERE id=?", array($role, $this->id));
				log_info("core-user", "Changed user role for {$this->name}");
				break;
		}
	}
	
	/**
	 * Test if this user is can do an action from a string of roles 
	 * Example: get_auth_from_char('oams') Owner, Admin, Moderator, Subscriber
	 *
	 * @retval bool
	 */
	public function get_auth_from_str($chars) {
		$can_do = FALSE;
		$arr = str_split($chars);
		if(in_array($this->role, $arr)){
			$can_do = TRUE;
		}
		return $can_do;
	}

/*	public function set_admin() {
		global $database;
		$database->Execute("UPDATE users SET role=? WHERE id=?", array("a", $this->id));
		log_info("core-user", "Made {$this->name} admin");
	}
	
	public function set_mod() {
		global $database;
		$database->Execute("UPDATE users SET role=? WHERE id=?", array("m", $this->id));
		log_info("core-user", "Made {$this->name} moderator");
	}
	
	public function set_user() {
		global $database;
		$database->Execute("UPDATE users SET role=? WHERE id=?", array("u", $this->id));
		log_info("core-user", "Made {$this->name} user");
	}
	
	public function set_anon() {
		global $database;
		$database->Execute("UPDATE users SET role=? WHERE id=?", array("g", $this->id));
		log_info("core-user", "Made {$this->name} inactive / anonymous");
	}*/
	
	/*
	* Used in validation
	*/
	public function set_user($user) {
		assert(is_bool($user));
		global $database;
		$yn = $user ? 'u' : 'g';
		$database->Execute("UPDATE users SET validate=?, role=? WHERE id=?", array(NULL, $yn, $this->id));
		log_info("core-user", "Made {$this->name} user=$yn");
	}

	public function set_password($password) {
		global $database;
		$hash = md5(strtolower($this->name) . $password);
		$database->Execute("UPDATE users SET pass=? WHERE id=?", array($hash, $this->id));
		log_info("core-user", "Set password for {$this->name}");
	}

	public function set_email($address) {
		global $database;
		$database->Execute("UPDATE users SET email=? WHERE id=?", array($address, $this->id));
		log_info("core-user", "Set email for {$this->name}");
	}
	
	
	/**
	 * Get user role in human
	 *
	 * @retval string
	 */
	public function role_to_human(){
		switch($this->role) {
			case "b": return "banned";
			case "g": return "guest";
			case "u": return "user";
			case "c": return "contributor";
			case "m": return "mod";
			case "a": return "admin";
			case "o": return "owner";
			default:  return "unknown";
		}
	}

	/**
	 * Get a snippet of HTML which will render the user's avatar, be that
	 * a local file, a remote file, a gravatar, a something else, etc
	 */
	public function get_avatar_html() {
		// FIXME: configurable
		global $config;
		if($config->get_string("avatar_host") == "gravatar") {
			if(!empty($this->email)) {
				$hash = md5(strtolower($this->email));
				$s = $config->get_string("avatar_gravatar_size");
				$d = $config->get_string("avatar_gravatar_default");
				$r = $config->get_string("avatar_gravatar_rating");
				return "<img class=\"avatar gravatar\" src=\"http://www.gravatar.com/avatar/$hash.jpg?s=$s&d=$d&r=$r\">";
			}
		}
		return "";
	}
}
?>
