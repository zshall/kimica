<?php

class CustomUserPageTheme extends UserPageTheme {
	public function display_login_page($page) {
		global $config;
		$page->set_title("Login");
		$page->set_heading("Login");
		$page->disable_left();
		$html = "
			<form action='".make_link("account/login")."' method='POST'>
				<table summary='Login Form'>
					<tr>
						<td width='70'><label for='user'>Name</label></td>
						<td width='70'><input id='user' type='text' name='user'></td>
					</tr>
					<tr>
						<td><label for='pass'>Password</label></td>
						<td><input id='pass' type='password' name='pass'></td>
					</tr>
					<tr><td colspan='2'><input type='submit' value='Log In'></td></tr>
				</table>
			</form>
		";
		if($config->get_bool("login_signup_enabled")) {
			$html .= "<small><a href='".make_link("account/create")."'>Signup</a></small>&nbsp;|&nbsp;";
		}
		$html .= "<small><a href='".make_link("account/recover")."'>Recover Password</a></small>";
		$page->add_block(new Block("Login", $html, "main", 90));
	}

	public function display_user_links($page, $user, $parts) {
		// no block in this theme
	}
	public function display_login_block(Page $page) {
		// no block in this theme
	}

	public function display_user_block($page, $user, $parts) {
		$h_name = html_escape($user->name);
		$html = "";
		$blocked = array("Pools", "Pool Changes", "Alias Editor", "My Profile");
		foreach($parts as $part) {
			if(in_array($part["name"], $blocked)) continue;
			$html .= "<li><a href='{$part["link"]}'>{$part["name"]}</a>";
		}
		$page->add_block(new Block("User Links", $html, "user", 90));
	}

	public function display_signup_page($page) {
		global $config;
		$tac = $config->get_string("signup_tac", "");

		$tfe = new TextFormattingEvent($tac);
		send_event($tfe);
		$tac = $tfe->formatted;
		
		$reca = "<tr><td colspan='2'>".captcha_get_html()."</td></tr>";

		if(empty($tac)) {$html = "";}
		else {$html = "<p>$tac</p>";}

		$html .= "
		<form action='".make_link("account/create")."' method='POST'>
			<table style='width: 300px;'>
				<tr><td>Name</td><td><input type='text' name='name'></td></tr>
				<tr><td>Password</td><td><input type='password' name='pass1'></td></tr>
				<tr><td>Repeat Password</td><td><input type='password' name='pass2'></td></tr>
				<tr><td>Email</td><td><input type='text' name='email'></td></tr>
				$reca
				<tr><td colspan='2'><input type='Submit' value='Create Account'></td></tr>
			</table>
		</form>
		";

		$page->set_title("Create Account");
		$page->set_heading("Create Account");
		$page->disable_left();
		$page->add_block(new Block("Signup", $html));
	}

	public function display_ip_list($page, $uploads, $comments) {
		$html = "<table id='ip-history' style='width: 400px;'>";
		$html .= "<tr><td>Uploaded from: ";
		foreach($uploads as $ip => $count) {
			$html .= "<br>$ip ($count)";
		}
		$html .= "</td><td>Commented from:";
		foreach($comments as $ip => $count) {
			$html .= "<br>$ip ($count)";
		}
		$html .= "</td></tr>";
		$html .= "<tr><td colspan='2'>(Most recent at top)</td></tr></table>";

		$page->add_block(new Block("IPs", $html));
	}

	public function display_user_page(User $duser, $stats) {
		global $page;
		$page->disable_left();
		parent::display_user_page($duser, $stats);
	}

	protected function build_options($duser) {
		global $database;
		global $config;
		global $user;

		$html = "";
		$html .= "
		<form action='".make_link("account/change_pass")."' method='POST'>
			<input type='hidden' name='name' value='{$duser->name}'>
			<input type='hidden' name='id' value='{$duser->id}'>
			<table style='width: 300px;'>
				<tr><td colspan='2'>Change Password</td></tr>
				<tr><td>Password</td><td><input type='password' name='pass1'></td></tr>
				<tr><td>Repeat Password</td><td><input type='password' name='pass2'></td></tr>
				<tr><td colspan='2'><input type='Submit' value='Change Password'></td></tr>
			</table>
		</form>
		<p><form action='".make_link("account/change_email")."' method='POST'>
			<input type='hidden' name='id' value='{$duser->id}'>
			<table style='width: 300px;'>
				<tr><th colspan='2'>Change Email</th></tr>
				<tr><td>Address</td><td><input type='text' name='address' value='".html_escape($duser->email)."'></td></tr>
				<tr><td colspan='2'><input type='Submit' value='Set'></td></tr>
			</table>
		</form></p>
		";
		if($user->is_owner()) {
			$i_user_id = int_escape($duser->id);
			$h_is_owner = $duser->is_owner() ? " selected='yes'" : "";
			$h_is_admin = $duser->is_admin() ? " selected='yes'" : "";
			$h_is_mod   = $duser->is_mod()   ? " selected='yes'" : "";
			$h_is_cont  = $duser->is_cont()  ? " selected='yes'" : "";
			$h_is_user  = $duser->is_user()  ? " selected='yes'" : "";
			$h_is_anon  = $duser->is_anon()  ? " selected='yes'" : "";
			if($h_is_owner != "") { $h_is_admin = ""; $h_is_mod = ""; }
			if($h_is_admin != "") { $h_is_mod   = ""; }
			$html .= "
				<p><form action='".make_link("account/set_more")."' method='POST'>
				<input type='hidden' name='id' value='$i_user_id'>
				User Role: 
					<select name='role'>
					  <option value='o'$h_is_owner>Owner</option>
					  <option value='a'$h_is_admin>Admin</option>
					  <option value='m'$h_is_mod>Moderator</option>
					  <option value='c'$h_is_cont>Contributor</option>
					  <option value='u'$h_is_user>User</option>
					  <option value='g'$h_is_anon>Anonymous / Inactive</option>
					</select>
				<input type='submit' value='Set'>
				</form>
			";
		}
		else if($user->is_admin() && (!$duser->is_admin())) {
			$i_user_id = int_escape($duser->id);
			$h_is_admin = $duser->is_admin() ? " selected='yes'" : "";
			$h_is_mod   = $duser->is_mod()   ? " selected='yes'" : "";
			$h_is_cont  = $duser->is_cont()  ? " selected='yes'" : "";
			$h_is_user  = $duser->is_user()  ? " selected='yes'" : "";
			$h_is_anon  = $duser->is_anon()  ? " selected='yes'" : "";
			if($h_is_admin != "") { $h_is_mod   = ""; }
			$html .= "
				<p><form action='".make_link("account/set_more")."' method='POST'>
				<input type='hidden' name='id' value='$i_user_id'>
				User Role: 
					<select name='role'>
					  <option value='a'$h_is_admin>Admin</option>
					  <option value='m'$h_is_mod>Moderator</option>
					  <option value='s'$h_is_cont>Contributor</option>
					  <option value='u'$h_is_user>User</option>
					  <option value='g'$h_is_anon>Anonymous / Inactive</option>
					</select>
				<input type='submit' value='Change Role'>
				</form>
			";
		}
		return $html;
	}
}
?>
