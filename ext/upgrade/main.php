<?php
/*
 * Name: Database Upgrader
 * Author: Shish
 * Description: Keeps things happy behind the scenes
 * Visibility: admin
 */

class Upgrade extends SimpleExtension {
	public function onInitExt(InitExtEvent $event) {
		global $config, $database;

		if(!is_numeric($config->get_string("db_version"))) {
			$config->set_int("db_version", 2);
		}

		if($config->get_int("db_version") < 6) {
			// cry :S
		}

		if($config->get_int("db_version") < 7) {
			if($database->engine->name == "mysql") {
				$tables = $database->db->MetaTables();
				foreach($tables as $table) {
					log_info("upgrade", "converting $table to innodb");
					$database->execute("ALTER TABLE $table TYPE=INNODB");
				}
			}
			$config->set_int("db_version", 7);
			log_info("upgrade", "Database at version 7");
		}

		if($config->get_int("db_version") < 8) {
			// if this fails, don't try again
			$config->set_int("db_version", 8);
			$database->execute($database->engine->scoreql_to_sql("ALTER TABLE images ADD COLUMN status ENUM('l', 'a', 'p', 'd') NOT NULL DEFAULT 'p'"
			$database->execute($database->engine->scoreql_to_sql("ALTER TABLE users ADD COLUMN validate VARCHAR(16) NULL"
			));
			log_info("upgrade", "Database at version 8");
		}
	}

	public function get_priority() {return 5;}
}
?>
