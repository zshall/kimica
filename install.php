<?php ob_start(); ?>
<html>
<!--
 - install.php (c) Shish 2007
 -
 - Initialise the database, check that folder
 - permissions are set properly, set an admin
 - account.
 -
 - This file should be independant of the database
 - and other such things that aren't ready yet
-->
	<head>
		<title>Installation</title>
		<style>
BODY {background: #EEE;font-family: "Arial", sans-serif;font-size: 14px;}
H1, H3 {border: 1px solid black;background: #DDD;text-align: center;}
H1 {margin-top: 0px;margin-bottom: 0px;padding: 2px;}
H3 {margin-top: 32px;padding: 1px;}
FORM {margin: 0px;}
A {text-decoration: none;}
A:hover {text-decoration: underline;}
#block {width: 512px; margin: auto; margin-top: 64px;}
#iblock {width: 512px; margin: auto; margin-top: 16px;}
TD INPUT {width: 350px;}
		</style>
	</head>
	<body>
<?php if(false) { ?>
		<div id="block">
			<h1>Install Error</h1>
			<p>Shimmie needs to be run via a web server with PHP support -- you
			appear to be either opening the file from your hard disk, or your
			web server is mis-configured.
			<p>If you've installed a web server on your desktop PC, you probably
			want to visit <a href="http://localhost/">the local web server</a>.
		</div>
		<div style="display: none;">
			<PLAINTEXT>
<?php }
assert_options(ASSERT_ACTIVE, 1);
assert_options(ASSERT_BAIL, 1);

/*
 * This file lets anyone destroy the database -- disable it
 * as soon as the admin is done installing for the first time
 */
if(is_readable("config.php")) {
	session_start();
?>
		<div id="iblock">
			<h1>Repair Console</h1>
<?php
	include "config.php";
	if($_SESSION['dsn'] == $database_dsn || $_POST['dsn'] == $database_dsn) {
		if($_POST['dsn']) {$_SESSION['dsn'] = $_POST['dsn'];}

		if(empty($_GET["action"])) {
			echo "<h3>Basic Checks</h3>";
			echo "If these checks fail, something is broken; if they all pass, ";
			echo "something <i>might</i> be broken, just not checked for...";
			eok("Images writable", is_writable("images"));
			eok("Thumbs writable", is_writable("thumbs"));
			eok("Data writable", is_writable("data"));

			/*
			echo "<h3>New Database DSN</h3>";
			echo "
				<form action='install.php?action=newdsn' method='POST'>
					<center>
						<table>
							<tr><td>Database:</td><td><input type='text' name='new_dsn' size='40'></td></tr>
							<tr><td colspan='2'><center><input type='submit' value='Go!'></center></td></tr>
						</table>
					</center>
				</form>
			";
			*/

			echo "<h3>Log Out</h3>";
			echo "
				<form action='install.php?action=logout' method='POST'>
					<input type='submit' value='Leave'>
				</form>
			";
		}
		else if($_GET["action"] == "logout") {
			session_destroy();
		}
	} else {
		echo "
			<h3>Login</h3>
			Enter the database DSN exactly as in config.php (ie, as originally
			installed) to access advanced recovery tools:

			<form action='install.php' method='POST'>
				<center>
					<table>
						<tr><td>Database:</td><td><input type='text' name='dsn' size='40'></td></tr>
						<tr><td colspan='2'><center><input type='submit' value='Go!'></center></td></tr>
					</table>
				</center>
			</form>
		";
	}
	echo "\t\t</div>";
	exit;
}
require_once "core/compat.inc.php";
require_once "core/database.class.php";

do_install();

// utilities {{{
function check_gd_version() {
	$gdversion = 0;

	if (function_exists('gd_info')){
		$gd_info = gd_info();
		if (substr_count($gd_info['GD Version'], '2.')) {
			$gdversion = 2;
		} else if (substr_count($gd_info['GD Version'], '1.')) {
			$gdversion = 1;
		}
	}

	return $gdversion;
}

function check_im_version() {
	if(!ini_get('safe_mode')) {
		$convert_check = exec("convert");
	}
	return (empty($convert_check) ? 0 : 1);
}

function eok($name, $value) {
	echo "<br>$name ... ";
	if($value) {
		echo "<font color='green'>ok</font>\n";
	}
	else {
		echo "<font color='red'>failed</font>\n";
	}
}
// }}}
function do_install() { // {{{
	if(isset($_POST['database_type']) && isset($_POST['database_host']) && isset($_POST['database_name']) && isset($_POST['database_user']) && isset($_POST['database_pass']) && isset($_POST['admin_name']) && isset($_POST['admin_pass']) && isset($_POST['admin_mail'])) {
		// for convienence:
		$database_dsn = $_POST['database_type']."://".$_POST['database_user'].":".$_POST['database_pass']."@".$_POST['database_host']."/".$_POST['database_name']."?persist";
		// for config file:
		$db_type = $_POST['database_type'];
		$db_user = $_POST['database_user'];
		$db_pass = $_POST['database_pass'];
		$db_host = $_POST['database_host'];
		$db_name = $_POST['database_name'];
		// for first account
		$account['username'] = $_POST['admin_name'];
		$account['password'] = $_POST['admin_pass'];
		$account['email'] = $_POST['admin_mail'];
		install_process($database_dsn, $db_type, $db_host, $db_name, $db_user, $db_pass, $account);
	}
	else if(file_exists("auto_install.conf")) {
		install_process(trim(file_get_contents("auto_install.conf")));
		unlink("auto_install.conf");
	}
	else {
		begin();
	}
} // }}}
function begin() { // {{{
	$err = "";
	$thumberr = "";
	$dberr = "";

	if(check_gd_version() == 0 && check_im_version() == 0) {
		$thumberr = "<p>PHP's GD extension seems to be missing, ".
		      "and imagemagick's \"convert\" command cannot be found - ".
			  "no thumbnailing engines are available.";
	}

	if(!function_exists("mysql_connect")) {
		$dberr = "<p>PHP's MySQL extension seems to be missing; you may ".
				"be able to use an unofficial alternative, checking ".
				"for libraries...";
		if(!function_exists("pg_connect")) {
			$dberr .= "<br>PgSQL is missing";
		}
		else {
			$dberr .= "<br>PgSQL is available";
		}
		if(!function_exists("sqlite_open")) {
			$dberr .= "<br>SQLite is missing";
		}
		else {
			$dberr .= "<br>SQLite is available";
		}
	}

	if($thumberr || $dberr) {
		$err = "<h3>Error</h3>";
	}

	print <<<EOD
		<div id="iblock">
			<h1>Kimica Installer</h1>

			$err
			$thumberr
			$dberr

			
			<form action="install.php" method="POST">
				<center>
					<h3>Install</h3>
					<table>
						<tr><td>Protocol:</td><td><select name="database_type"><option value="mysql">Mysql</option><option 
						value="pgsql">Pgsql</option><option value="sqlite">Sqlite</option></select></td></tr>
						<tr><td>Hostname:</td><td><input type="text" name="database_host" size="40"></td></tr>
						<tr><td>Database:</td><td><input type="text" name="database_name" size="40"></td></tr>
						<tr><td>Username:</td><td><input type="text" name="database_user" size="40"></td></tr>
						<tr><td>Password:</td><td><input type="text" name="database_pass" size="40"></td></tr>
					</table>
					<h3>Account</h3>
					<table>
						<tr><td>Username:</td><td><input type="text" name="admin_name" size="40"></td></tr>
						<tr><td>Password:</td><td><input type="text" name="admin_pass" size="40"></td></tr>
						<tr><td>Email:</td><td><input type="text" name="admin_mail" size="40"></td></tr>
					</table>
					<input type="submit" value="Next >>">
				</center>
			</form>
		</div>
EOD;
} // }}}
function install_process($database_dsn, $db_type, $db_host, $db_name, $db_user, $db_pass, $account) { // {{{
	build_dirs();
	create_tables($database_dsn);
	write_config($db_type, $db_host, $db_name, $db_user, $db_pass);
	insert_defaults($database_dsn, $account);
} // }}}
function create_tables($dsn) { // {{{
	if(substr($dsn, 0, 5) == "mysql") {
		$engine = new MySQL();
	}
	else if(substr($dsn, 0, 5) == "pgsql") {
		$engine = new PostgreSQL();
	}
	else if(substr($dsn, 0, 6) == "sqlite") {
		$engine = new SQLite();
	}
	else {
		die("Unknown database engine; Kimica currently officially supports MySQL
		(mysql://), with hacks for Postgres (pgsql://) and SQLite (sqlite://)");
	}

	$db = NewADOConnection($dsn);
	if(!$db) {
		die("Couldn't connect to \"$dsn\"");
	}
	else {
		$engine->init($db);
		$db->execute($engine->create_table_sql("users", "
			id SCORE_AIPK,
			ip CHAR(15) NOT NULL,
			name VARCHAR(32) UNIQUE NOT NULL,
			pass CHAR(32),
			joindate SCORE_DATETIME NOT NULL DEFAULT SCORE_NOW,
			logindate SCORE_DATETIME,
			validate CHAR(16),
			role ENUM('b', 'g', 'u', 'c', 'm', 'a', 'o') NOT NULL DEFAULT 'g',
			email VARCHAR(128)
		"));
		$db->execute($engine->create_table_sql("user_bans", "
			id SCORE_AIPK,
			banner_id INTEGER NOT NULL,
			user_id INTEGER NOT NULL,
			user_ip CHAR(15) NOT NULL,
			user_role ENUM('u', 'c', 'm', 'a', 'o') NOT NULL DEFAULT 'u',
			end_date SCORE_DATETIME,
			reason TEXT
		"));
		$db->execute($engine->create_table_sql("config", "
			name VARCHAR(128) NOT NULL PRIMARY KEY,
			value TEXT
		"));
		$db->execute($engine->create_table_sql("prefs", "
			user_id INTEGER NOT NULL,
			name VARCHAR(128) NOT NULL,
			value TEXT,
			INDEX(user_id),
			FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
		"));
		$db->execute($engine->create_table_sql("messages", "
			id SCORE_AIPK,
			from_id INTEGER NOT NULL,
			from_ip CHAR(15) NOT NULL,
			to_id INTEGER NOT NULL,
			sent_date DATETIME NOT NULL,
			subject VARCHAR(128) NOT NULL,
			message TEXT NOT NULL,
			status ENUM('r', 'u', 's', 'd') NOT NULL DEFAULT 'u',
			priority ENUM('l', 'n', 'h') NOT NULL DEFAULT 'n',
			INDEX (to_id),
			INDEX (from_id),
			FOREIGN KEY (from_id) REFERENCES users(id) ON DELETE CASCADE
		"));
		$db->execute($engine->create_table_sql("notifications", "
			id SCORE_AIPK,
			status ENUM('p', 'r', 's') NOT NULL DEFAULT 'p',
			section VARCHAR(64) NOT NULL,
			message VARCHAR(255) NOT NULL,
			location VARCHAR(255) NOT NULL,
			created_at DATETIME NOT NULL,
			alerter_id INTEGER NOT NULL DEFAULT 1,
			reviewer_id INTEGER NOT NULL DEFAULT 1,
			INDEX (alerter_id),
			FOREIGN KEY (alerter_id) REFERENCES users(id) ON DELETE CASCADE
		"));
		$db->execute($engine->create_table_sql("images", "
			id SCORE_AIPK,
			owner_id INTEGER NOT NULL,
			owner_ip CHAR(15) NOT NULL,
			filename VARCHAR(64) NOT NULL,
			filesize INTEGER NOT NULL,
			hash CHAR(32) UNIQUE NOT NULL,
			ext CHAR(4) NOT NULL,
			source VARCHAR(255),
			width INTEGER NOT NULL,
			height INTEGER NOT NULL,
			posted SCORE_DATETIME NOT NULL DEFAULT SCORE_NOW,
			tags TEXT NULL,
			has_children ENUM('y', 'n') NOT NULL DEFAULT 'n',
			parent INTEGER,
			status ENUM('l', 'a', 'p', 'd', 'h') NOT NULL DEFAULT 'p',
			warehoused ENUM('y', 'n') NOT NULL DEFAULT 'n',
			views INTEGER NOT NULL DEFAULT 0,
			INDEX(owner_id),
			INDEX(width),
			INDEX(height),
			INDEX(parent),
			FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE
		"));
		$db->execute($engine->create_table_sql("image_views", "
			image_id INTEGER NOT NULL,
			user_ip CHAR(15) NOT NULL,
			viewed_at SCORE_DATETIME NOT NULL DEFAULT SCORE_NOW,
			INDEX(image_id),
			INDEX(user_ip),
			FOREIGN KEY (image_id) REFERENCES images(id) ON DELETE CASCADE
		"));
		$db->execute($engine->create_table_sql("tags", "
			id SCORE_AIPK,
			tag VARCHAR(64) UNIQUE NOT NULL,
			type ENUM('general', 'artist', 'character', 'copyright') NOT NULL DEFAULT 'general',
			count INTEGER NOT NULL DEFAULT 0
		"));
		$db->execute($engine->create_table_sql("image_tags", "
			image_id INTEGER NOT NULL,
			tag_id INTEGER NOT NULL,
			INDEX(image_id),
			INDEX(tag_id),
			UNIQUE(image_id, tag_id),
			FOREIGN KEY (image_id) REFERENCES images(id) ON DELETE CASCADE,
			FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
		"));
		$db->execute($engine->create_table_sql("image_bans", "
			id SCORE_AIPK,
			hash CHAR(32) NOT NULL,
			created_at SCORE_DATETIME NOT NULL DEFAULT SCORE_NOW,
			reason TEXT
		"));
		$db->execute($engine->create_table_sql("tag_alias", "
			oldtag VARCHAR(64) NOT NULL PRIMARY KEY,
			newtag VARCHAR(64) NOT NULL,
			INDEX(newtag)
		"));
		$db->execute($engine->create_table_sql("tag_bans", "
			id SCORE_AIPK,
			tag VARCHAR(64) UNIQUE NOT NULL,
			status ENUM('p', 'd') NOT NULL DEFAULT 'p',
			UNIQUE(tag)
		"));
		$db->execute($engine->create_table_sql("tag_histories", "
			id SCORE_AIPK,
			image_id INTEGER NOT NULL,
			user_id INTEGER NOT NULL,
			user_ip CHAR(15) NOT NULL,
			tags TEXT NOT NULL,
			date_set DATETIME NOT NULL,
			INDEX(image_id),
			FOREIGN KEY (image_id) REFERENCES images(id) ON DELETE CASCADE,
			FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
		"));
		$db->execute($engine->create_table_sql("tag_blacklist", "
			user_id INTEGER NOT NULL,
			tag VARCHAR(64) NOT NULL,
			INDEX(user_id),
			FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
		"));
		$db->execute($engine->create_table_sql("comments", "
			id SCORE_AIPK,
			image_id INTEGER NOT NULL,
			owner_id INTEGER NOT NULL,
			owner_ip SCORE_INET NOT NULL,
			posted SCORE_DATETIME NOT NULL DEFAULT SCORE_NOW,
			comment TEXT NOT NULL,
			votes INTEGER NOT NULL DEFAULT 0,
			INDEX (image_id),
			INDEX (owner_ip),
			INDEX (posted),
			FOREIGN KEY (image_id) REFERENCES images(id) ON DELETE CASCADE,
			FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE
		"));
		$db->execute($engine->create_table_sql("comment_votes", "
			comment_id INTEGER NOT NULL,
			user_id INTEGER NOT NULL,
			vote INTEGER NOT NULL DEFAULT 0,
			created_at SCORE_DATETIME NOT NULL DEFAULT SCORE_NOW,
			UNIQUE(comment_id, user_id),
			INDEX(comment_id),
			FOREIGN KEY (comment_id) REFERENCES comments(id) ON DELETE CASCADE,
			FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
		"));
		$db->execute("INSERT INTO config(name, value) VALUES('db_version', 8)");
	}
	$db->Close();
} // }}}
function insert_defaults($dsn, $account) { // {{{
	$db = NewADOConnection($dsn);
	if(!$db) {
		die("Couldn't connect to \"$dsn\"");
	}
	else {
		if(substr($dsn, 0, 5) == "mysql") {
			$engine = new MySQL();
		}
		else if(substr($dsn, 0, 5) == "pgsql") {
			$engine = new PostgreSQL();
		}
		else if(substr($dsn, 0, 6) == "sqlite") {
			$engine = new SQLite();
		}
		else {
			die("Unknown database engine; Kimica currently officially supports MySQL
			(mysql://), with hacks for Postgres (pgsql://) and SQLite (sqlite://)");
		}
		$engine->init($db);
		
		assert(is_array($account));
		assert(is_string($account['username']));
		assert(is_string($account['password']));
		assert(is_string($account['email']));
		
		$username = $account['username'];
		$password = $account['password'];
		$email = $account['email'];
		
		$hash = md5(strtolower($username) . $password);
		
		$db->Execute("INSERT INTO users(name, pass, joindate, validate, role, email) VALUES(?, ?, now(), ?, ?, ?)", array('Guest', null, null, 'g', null));
		$db->Execute("INSERT INTO config(name, value) VALUES(?, ?)", array('anon_id', '1'));
		$db->Execute("INSERT INTO users(name, pass, joindate,validate, role, email) VALUES(?, ?, now(), ?, ?, ?)", array($username, $hash, null, 'o', $email));

		if(check_im_version() > 0) {
			$db->Execute("INSERT INTO config(name, value) VALUES(?, ?)", array('thumb_engine', 'convert'));
		}

		$db->Close();
		print <<<EOD
		<div id="iblock">
			<h1>Step 2</h1>

			$err
			$thumberr
			$dberr

			<h3>Install</h3>
			<form action="index.php?q=/account/login&easysetup=1" method="POST">
				<center>
					<table>
						<tr><td colspan="2"><b>Use these details to log in:</b></td></tr>
						<tr><td>Username:</td><td><code>$username</code><input type="hidden" name="user" value="$username"></td></tr>
						<tr><td>Password:</td><td><code>$password</code><input type="hidden" name="pass" value="$password"></td></tr>
						<tr><td colspan="2"><center><input type="submit" value="Next >>"></center></td></tr>
					</table>
				</center>
			</form>
		</div>
EOD;
	}
} // }}}
function build_dirs() { // {{{
	// *try* and make default dirs. Ignore any errors --
	// if something is amiss, we'll tell the user later
	if(!file_exists("images")) @mkdir("images");
	if(!file_exists("thumbs")) @mkdir("thumbs");
	if(!file_exists("cache") ) @mkdir("cache");
	if(!file_exists("data")  ) @mkdir("data");
	if(!is_writable("images")) @chmod("images", 0755);
	if(!is_writable("thumbs")) @chmod("thumbs", 0755);
	if(!is_writable("cache") ) @chmod("cache", 0755);
	if(!is_writable("data")  ) @chmod("data", 0755);

	if(
			!file_exists("images") || !file_exists("thumbs") || !file_exists("data") ||
			!is_writable("images") || !is_writable("thumbs") || !is_writable("data")
	) {
		print "Kimica needs three folders in it's directory, 'images', 'thumbs', and 'data',
		       and they need to be writable by the PHP user (if you see this error,
			   if probably means the folders are owned by you, and they need to be
			   writable by the web server).
			   <p>Once you have created these folders, hit 'refresh' to continue.";
		exit;
	}
} // }}}
function write_config($db_type, $db_host, $db_name, $db_user, $db_pass) { // {{{
$file_content = '<?php'."\n";
$file_content .= '// Database settings.'."\n";
$file_content .= '$db_type = "'.$db_type.'";'."\n";
$file_content .= '$db_host = "'.$db_host.'";'."\n";
$file_content .= '$db_name = "'.$db_name.'";'."\n";
$file_content .= '$db_user = "'.$db_user.'";'."\n";
$file_content .= '$db_pass = "'.$db_pass.'";'."\n";
$file_content .= '?>';
	
	if(is_writable("./") && file_put_contents("config.php", $file_content)) {
		assert(file_exists("config.php"));
	}
	else {
		$h_file_content = htmlentities($file_content);
		print <<<EOD
		The web server isn't allowed to write to the config file; please copy
	    the text below, save it as 'config.php', and upload it into the Kimica
	    folder manually. Make sure that when you save it, there is no whitespace
		before the "&lt;?php" or after the "?&gt;"

		<p><textarea cols="80" rows="2">$file_content</textarea>
						
		<p>One done, <a href='index.php'>Continue</a>
EOD;
		exit;
	}
} // }}}
?>
	</body>
</html>
