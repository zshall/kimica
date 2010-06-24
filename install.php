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
	if(isset($_POST['database_dsn'])) {
		install_process($_POST['database_dsn']);
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
			<h1>Shimmie Installer</h1>

			$err
			$thumberr
			$dberr

			<h3>Install</h3>
			<form action="install.php" method="POST">
				<center>
					<table>
						<tr><td>Database:</td><td><input type="text" name="database_dsn" size="40"></td></tr>
						<tr><td colspan="2"><center><input type="submit" value="Go!"></center></td></tr>
					</table>
				</center>
			</form>

			<h3>Help</h3>
					
			<p>Databases should be specified like so:
			<br>ie: <code>protocol://username:password@host/database?options</code>
			<br>eg: <code>mysql://shimmie:pw123@localhost/shimmie?persist</code>
		</div>
EOD;
} // }}}
function install_process($database_dsn) { // {{{
	build_dirs();
	create_tables($database_dsn);
	insert_defaults($database_dsn);
	write_config($database_dsn);
	
	header("Location: index.php");
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
		die("Unknown database engine; Shimmie currently officially supports MySQL
		(mysql://), with hacks for Postgres (pgsql://) and SQLite (sqlite://)");
	}

	$db = NewADOConnection($dsn);
	if(!$db) {
		die("Couldn't connect to \"$dsn\"");
	}
	else {
		$engine->init($db);

		$db->execute($engine->create_table_sql("aliases", "
			oldtag VARCHAR(128) NOT NULL PRIMARY KEY,
			newtag VARCHAR(128) NOT NULL,
			INDEX(newtag)
		"));
		$db->execute($engine->create_table_sql("users", "
			id SCORE_AIPK,
			ip CHAR(15) NOT NULL,
			name VARCHAR(32) UNIQUE NOT NULL,
			pass CHAR(32),
			joindate SCORE_DATETIME NOT NULL DEFAULT SCORE_NOW,
			validate CHAR(16),
			role ENUM('g', 'u', 'm', 'a', 'o') NOT NULL DEFAULT 'g',
			email VARCHAR(128)
		"));
		$db->execute($engine->create_table_sql("config", "
			name VARCHAR(128) NOT NULL PRIMARY KEY,
			value TEXT
		"));
		$db->execute($engine->create_table_sql("prefs", "
			user_id INTEGER NOT NULL,
			name VARCHAR(128) NOT NULL PRIMARY KEY,
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
			status ENUM('l', 'a', 'p', 'd') NOT NULL DEFAULT 'p',
			INDEX(owner_id),
			INDEX(width),
			INDEX(height),
			FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE
		"));
		$db->execute($engine->create_table_sql("tags", "
			id SCORE_AIPK,
			tag VARCHAR(64) UNIQUE NOT NULL,
			count INTEGER NOT NULL DEFAULT 0
		"));
		$db->execute($engine->create_table_sql("tag_bans", "
			id SCORE_AIPK,
			name VARCHAR(64) UNIQUE NOT NULL,
			status ENUM('p', 'd') NOT NULL DEFAULT 'p'
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
		$db->execute($engine->create_table_sql("image_tags", "
			image_id INTEGER NOT NULL,
			tag_id INTEGER NOT NULL,
			INDEX(image_id),
			INDEX(tag_id),
			UNIQUE(image_id, tag_id),
			FOREIGN KEY (image_id) REFERENCES images(id) ON DELETE CASCADE,
			FOREIGN KEY (tag_id) REFERENCES tags(id) ON DELETE CASCADE
		"));
		$db->execute("INSERT INTO config(name, value) VALUES('db_version', 8)");
	}
	$db->Close();
} // }}}
function insert_defaults($dsn) { // {{{
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
			die("Unknown database engine; Shimmie currently officially supports MySQL
			(mysql://), with hacks for Postgres (pgsql://) and SQLite (sqlite://)");
		}
		$engine->init($db);

		$config_insert = $db->Prepare("INSERT INTO config(name, value) VALUES(?, ?)");
		$user_insert = $db->Prepare("INSERT INTO users(name, pass, joindate,validate, role) VALUES(?, ?, now(), ?, ?)");

		$db->Execute($user_insert, Array('Anonymous', null, null, 'g'));
		$db->Execute($config_insert, Array('anon_id', $db->Insert_ID()));

		if(check_im_version() > 0) {
			$db->Execute($config_insert, Array('thumb_engine', 'convert'));
		}

		$db->Close();
	}
} // }}}
function build_dirs() { // {{{
	// *try* and make default dirs. Ignore any errors --
	// if something is amiss, we'll tell the user later
	if(!file_exists("images")) @mkdir("images");
	if(!file_exists("thumbs")) @mkdir("thumbs");
	if(!file_exists("data")  ) @mkdir("data");
	if(!is_writable("images")) @chmod("images", 0755);
	if(!is_writable("thumbs")) @chmod("thumbs", 0755);
	if(!is_writable("data")  ) @chmod("data", 0755);

	if(
			!file_exists("images") || !file_exists("thumbs") || !file_exists("data") ||
			!is_writable("images") || !is_writable("thumbs") || !is_writable("data")
	) {
		print "Shimmie needs three folders in it's directory, 'images', 'thumbs', and 'data',
		       and they need to be writable by the PHP user (if you see this error,
			   if probably means the folders are owned by you, and they need to be
			   writable by the web server).
			   <p>Once you have created these folders, hit 'refresh' to continue.";
		exit;
	}
} // }}}
function write_config($dsn) { // {{{
	$file_content = "<?php \$database_dsn='$dsn'; ?>";
	
	if(is_writable("./") && file_put_contents("config.php", $file_content)) {
		assert(file_exists("config.php"));
	}
	else {
		$h_file_content = htmlentities($file_content);
		print <<<EOD
		The web server isn't allowed to write to the config file; please copy
	    the text below, save it as 'config.php', and upload it into the shimmie
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
