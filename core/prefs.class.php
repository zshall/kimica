<?php
/**
 * User preferences - user_id, name, value. Rename hack of config.
 */
interface iPrefs {
	/**
	 * Save the list of name:value pairs to wherever they came from,
	 * so that the next time a page is loaded it will use the new
	 * configuration
	 */
	public function save($name=null, $uid=null); //Make it easy on ourselves... don't want to change this much at all.

	/** @name set_*
	 * Set a configuration option to a new value, regardless
	 * of what the value is at the moment
	 */
	//@{
	public function set_int($name, $value);
	public function set_string($name, $value);
	public function set_bool($name, $value);
	public function set_array($name, $value);
	//@}

	/** @name set_default_*
	 * Set a configuration option to a new value, if there is no
	 * value currently. Extensions should generally call these
	 * from their InitExtEvent handlers. This has the advantage
	 * that the values will show up in the "advanced" setup page
	 * where they can be modified, while calling get_* with a
	 * "default" paramater won't show up.
	 */
	//@{
	public function set_default_int($name, $value);
	public function set_default_string($name, $value);
	public function set_default_bool($name, $value);
	public function set_default_array($name, $value);
	//@}

	/** @name get_*
	 * pick a value out of the table by name, cast to the
	 * appropritate data type
	 */
	//@{
	public function get_int($name, $default=null);
	public function get_string($name, $default=null);
	public function get_bool($name, $default=null);
	public function get_array($name, $default=array());
	//@}
}


/**
 * Common methods for manipulating the list, loading and saving is
 * left to the concrete implementation
 */
abstract class BasePrefs implements iPrefs {
	var $values = array();

	public function set_int($name, $value, $userid=null) {
		$this->values[$name] = parse_shorthand_int($value);
		$this->save($name, $userid);
	}
	public function set_string($name, $value, $userid=null) {
		$this->values[$name] =  $value; //probably better to let bbcode and the extensions handle filtering.
		$this->save($name, $userid);
	}
	public function set_bool($name, $value, $userid=null) {
		$this->values[$name] = (($value == 'on' || $value === true) ? 'Y' : 'N');
		$this->save($name, $userid);
	}
	public function set_array($name, $value, $userid=null) {
		assert(is_array($value));
		$this->values[$name] = implode(",", $value);
		$this->save($name, $userid);
	}

	public function set_default_int($name, $value) {
		if(is_null($this->get($name))) {
			$this->values[$name] = parse_shorthand_int($value);
		}
	}
	public function set_default_string($name, $value) {
		if(is_null($this->get($name))) {
			$this->values[$name] = $value;
		}
	}
	public function set_default_bool($name, $value) {
		if(is_null($this->get($name))) {
			$this->values[$name] = (($value == 'on' || $value === true) ? 'Y' : 'N');
		}
	}
	public function set_default_array($name, $value) {
		assert(is_array($value));
		if(is_null($this->get($name))) {
			$this->values[$name] = implode(",", $value);
		}
	}
	
	public function get_int($name, $default=null) {
		return (int)($this->get($name, $default));
	}
	public function get_string($name, $default=null) { 
		return $this->get($name, $default);
	}
	public function get_bool($name, $default=null) {
		return undb_bool($this->get($name, $default));
	}
	public function get_array($name, $default=array()) {
		return explode(",", $this->get($name, ""));
	}

	private function get($name, $default=null) {
		if(isset($this->values[$name])) { 
			return $this->values[$name]; 
		}
		else {
			return $default;
		}
	}
}

/**
 * Loads the preferences from a table in a given database, the table should
 * be called prefs and have the schema:
 *
 * \code
 *  CREATE TABLE prefs(
 *  	user_id INTEGER NOT NULL,
 *      name VARCHAR(128) NOT NULL,
 *      value TEXT
 *  );
 * \endcode
 */
class DatabasePrefs extends BasePrefs {
	var $database = null;
	
	/*
	 * Load user preferences from a the database.
	 */
	public function DatabasePrefs($database, $userid) {
		$this->database = $database;
		$cached = $this->database->cache->get("prefs");
		if($cached) {
			$this->values = $cached;
		}
		else {
			$this->values = $this->database->db->GetAssoc("SELECT name, value FROM prefs WHERE user_id = $userid");
			$this->database->cache->set("prefs", $this->values);
		}
	}

	/*
	 * Save the current values for the current user.
	 */
	public function save($name=null, $uid=null) {
		if(is_null($uid)) {
			global $user;
			$uid = $user->id;
		}
		if(is_null($name)) {
			foreach($this->values as $name => $value) {
				$this->save($name);
			}
		}
		else {
			$this->database->Execute("DELETE FROM prefs WHERE name = ? AND user_id = ?", array($name, $uid));
			$this->database->Execute("INSERT INTO prefs VALUES (?, ?, ?)", array($uid, $name, $this->values[$name]));
		}
		$this->database->cache->delete("prefs");
	}
}

class Prefs {
	public static function by_id($id) {
		assert(is_numeric($id));
		global $database;
		$user_prefs = new DatabasePrefs($database, $id);
		return $user_prefs;
	}
}
?>
