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
	var $name;
	var $email;
	var $join_date;
	var $owner;
	var $admin;
	var $moderator;
	var $user;
	var $anon;

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
		$this->name = $row['name'];
		$this->email = $row['email'];
		$this->join_date = $row['joindate'];
		$this->owner = ($row['role'] == 'o');
		$this->admin = ($row['role'] == 'a' || $row['role'] == 'o');
		$this->moderator = ($row['role'] == 'm');
		$this->user = ($row['role'] == 'u');
		$this->anon = ($row['role'] == 'g');
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
	public function is_moderator() {
		return $this->moderator;
	}
	
	/**
	 * Test if this user is an verified user
	 *
	 * @retval bool
	 */
	public function is_user() {
		return $this->user;
	}
	
	public function is_anon() {
		return $this->anon;
	}
	
	public function set_admin($owner) {
		assert(is_bool($owner));
		global $database;
		$yn = $owner ? 'o' : 'u';
		$database->Execute("UPDATE users SET role=? WHERE id=?", array($yn, $this->id));
		log_info("core-user", "Made {$this->name} owner=$yn");
	}

	public function set_admin($admin) {
		assert(is_bool($admin));
		global $database;
		$yn = $admin ? 'a' : 'u';
		$database->Execute("UPDATE users SET role=? WHERE id=?", array($yn, $this->id));
		log_info("core-user", "Made {$this->name} admin=$yn");
	}
	
	public function set_moderator($moderator) {
		assert(is_bool($moderator));
		global $database;
		$yn = $moderator ? 'm' : 'u';
		$database->Execute("UPDATE users SET role=? WHERE id=?", array($yn, $this->id));
		log_info("core-user", "Made {$this->name} moderator=$yn");
	}
	
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
