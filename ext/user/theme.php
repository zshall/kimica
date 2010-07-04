<?php

class UserPageTheme extends Themelet {
	public function display_login_page(Page $page) {
		$page->set_title("Login");
		$page->set_heading("Login");
		$page->add_block(new NavBlock());
		$page->add_block(new Block("Login There",
			"There should be a login box to the left"));
	}

	public function display_user_list(Page $page, $users, User $user) {
		$page->set_title("User List");
		$page->set_heading("User List");
		$page->add_block(new NavBlock());
		$html = "<table>";
		$html .= "<tr><td>Name</td></tr>";
		foreach($users as $duser) {
			$html .= "<tr>";
			$html .= "<td><a href='".make_link("user/"+$duser->name)."'>".html_escape($duser->name)."</a></td>";
			$html .= "</tr>";
		}
		$html .= "</table>";
		$page->add_block(new Block("Users", $html));
	}
	
	public function display_user_block(Page $page, User $user, $parts) {
		$h_name = html_escape($user->name);
		$html = "Logged in as $h_name";
		foreach($parts as $part) {
			$html .= "<br><a href='{$part["link"]}'>{$part["name"]}</a>";
		}
		$page->add_block(new Block("User Links", $html, "left", 90));
	}
	
	public function display_user_links(Page $page, User $user, $parts) {
                # $page->add_block(new Block("User Links", join(", ", $parts), "main", 10));
    }

	public function display_validation_page(Page $page) {
		global $config;

		$html = "
		<form action='".make_link("account/validate")."' method='POST'>
			<table style='width: 300px;'>
				<tr><td>User</td><td><input type='text' name='name'></td></tr>
				<tr><td>Code</td><td><input type='text' name='code'></td></tr>
				<tr><td colspan='2'><input type='Submit' value='Validate'></td></tr>
			</table>
			<br>
			<a href='".make_link("account/validate/resend")."'>Resend Code</a>
		</form>
		";

		$page->set_title("Validate Account");
		$page->set_heading("Validate Account");
		$page->add_block(new NavBlock());
		$page->add_block(new Block("Validate Account", $html));
	}
	
	public function display_resend_validation_page(Page $page) {
		global $config;

		$html = "
		<form action='".make_link("account/validate/resend")."' method='POST'>
			<table style='width: 300px;'>
				<tr><td>Name</td><td><input type='text' name='name'></td></tr>
				<tr><td colspan='2'><input type='Submit' value='Resend Code'></td></tr>
			</table>
		</form>
		";

		$page->set_title("Validate Account");
		$page->set_heading("Validate Account");
		$page->add_block(new NavBlock());
		$page->add_block(new Block("Validate Account", $html));
	}
	
	public function display_recover_page(Page $page) {
		global $config;

		$html = "
		<form action='".make_link("account/recover")."' method='POST'>
			<table style='width: 300px;'>
				<tr><td>User</td><td><input type='text' name='name'></td></tr>
				<tr><td>Email</td><td><input type='text' name='email'></td></tr>
				<tr><td colspan='2'><input type='Submit' value='Recover'></td></tr>
			</table>
		</form>
		";

		$page->set_title("Recover Password");
		$page->set_heading("Recover Password");
		$page->add_block(new NavBlock());
		$page->add_block(new Block("Recover Password", $html));
	}
	
	public function display_signup_page(Page $page) {
		global $config;
		$tac = $config->get_string("signup_tac", "");

		if($config->get_bool("signup_tac_bbcode")) {
			$tfe = new TextFormattingEvent($tac);
			send_event($tfe);
			$tac = $tfe->formatted;
		}

		if(empty($tac)) {$html = "";}
		else {$html = "<p>$tac</p>";}

		$reca = "<tr><td colspan='2'>".captcha_get_html()."</td></tr>";

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
		$page->add_block(new NavBlock());
		$page->add_block(new Block("Create Account", $html));
	}

	public function display_signups_disabled(Page $page) {
		$page->set_title("Signups Disabled");
		$page->set_heading("Signups Disabled");
		$page->add_block(new NavBlock());
		$page->add_block(new Block("Signups Disabled",
			"The board admin has disabled the ability to create new accounts~"));
	}

	public function display_login_block(Page $page) {
		global $config;
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
		$page->add_block(new Block("Login", $html, "left", 90));
	}

	public function display_ip_list(Page $page, $uploads, $comments) {
		$html = "<table id='ip-history'>";
		$html .= "<tr><td>Uploaded from: ";
		$n = 0;
		foreach($uploads as $ip => $count) {
			$html .= "<br>$ip ($count)";
			if(++$n >= 20) {
				$html .= "<br>...";
				break;
			}
		}

		$html .= "</td><td>Commented from:";
		$n = 0;
		foreach($comments as $ip => $count) {
			$html .= "<br>$ip ($count)";
			if(++$n >= 20) {
				$html .= "<br>...";
				break;
			}
		}

		$html .= "</td></tr>";
		$html .= "<tr><td colspan='2'>(Most recent at top)</td></tr></table>";

		$page->add_block(new Block("IPs", $html));
	}

	public function display_user_page(User $duser, $stats) {
		global $page, $user;
		assert(is_array($stats));
		$stats[] = "User ID: {$duser->id}";

		$page->set_title("{$duser->name}'s Page");
		$page->set_heading("{$duser->name}'s Page");
		$page->add_block(new NavBlock());
		$page->add_block(new Block("Stats", join("<br>", $stats), "main", 0));

		if(!$user->is_anonymous()) {
			if($user->id == $duser->id || $user->is_admin()) {
				$page->add_block(new Block("Options", $this->build_options($duser), "main", 20));
			}
		}
	}

	protected function build_options(User $duser) {
		global $config, $database, $user;

		$html = "";

		$html .= "
		<form action='".make_link("account/change_pass")."' method='POST'>
			<input type='hidden' name='id' value='{$duser->id}'>
			<table style='width: 300px;'>
				<tr><th colspan='2'>Change Password</th></tr>
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
				<tr><td colspan='2'><input type='Submit' value='Change Email'></td></tr>
			</table>
		</form>
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
					  <option value='c'$h_is_cont>Contributor</option>
					  <option value='u'$h_is_user>User</option>
					  <option value='g'$h_is_anon>Anonymous / Inactive</option>
					</select>
				<input type='submit' value='Change Role'>
				</form>
			";
		}
	return $html;
	}
	
	
	public function display_messages_sidebar(Page $page, $unread) {
		
		$html = "<a href='".make_link("account/messages/new")."'>New</a><br><br>";
		$html .= "<a href='".make_link("account/messages/inbox")."'>Inbox (".$unread.")</a><br>";
		$html .= "<a href='".make_link("account/messages/outbox")."'>Outbox</a><br>";
		$html .= "<a href='".make_link("account/messages/deleted")."'>Deleted</a> (<a href='".make_link("account/messages/empty")."'>empty</a>)<br>";
		$html .= "<a href='".make_link("account/messages/saved")."'>Saved</a>";
		
		$page->add_block(new Block("Messages", $html, "left", 10));
	}
	
	public function display_messages_viewer(Page $page, $subject, $message) {
		$tfe = new TextFormattingEvent($message);
        send_event($tfe);
        $message = $tfe->formatted;
		
		$page->add_block(new Block("Message:".$subject, $message, "main", 10));
	}
	
	public function display_composer(Page $page, $user_name = NULL, $subject = NULL, $message = NULL) {
		$title = "";
		if(isset($subject)){
			$subject = "Re:".$subject;
		}
		
		if(isset($message)){
			$message = "[quote=".$user_name."]".$message."[/quote]";
		}
		
		$html = "<form method='POST' action=".make_link("account/messages/action").">";
		if(!is_null($user_name)){
			$html .="<input type='hidden' name='to' value='{$user_name}'>";
		}
		$html .="<table style='width: 500px;'>";
		$html .="<tbody>";
		if(is_null($user_name)){
			$html .= "
			<script type='text/javascript'> 
			$().ready(function() {
				$('#to').autocomplete('./index.php?q=/account/messages/complete', {
					width: 320,
					max: 15,
					highlight: false,
					multiple: false,
					scroll: true,
					scrollHeight: 300,
					selectFirst: false
				});
			});
			</script> 
			
			<tr><td>To:</td><td><input type='text' value='{$user_name}' name='to' id='to'></td></tr>";
		}
		$html .= "<tr><td>Priority:</td><td><select name='priority'><option value='l'>Low</option><option value='n' selected='selected'>Normal</option><option value='h'>High</option></select></td></tr>";
		$html .= "<tr><td>Subject:</td><td><input type='text' value='{$subject}' name='subject'></td></tr>";
		$html .= "<tr><td>Message:</td><td><textarea rows='10' name='message'>$message</textarea>";
		$html .= "<tr><td colspan='2'><input type='submit' name='action' value='Send'></td></tr>";
		$html .= "</tbody>";
		$html .= "</table>";
		$html .= "</form>";
		
		$page->add_block(new Block("New Message", $html, "main", 20));
	}
		
	public function display_inbox(Page $page, $pms, $inbox) {
		$html = "
			<script>
			$(document).ready(function() {
				$(\"#pms\").tablesorter();
			});
			</script>
			<form action='".make_link("account/messages/action")."' method='POST'>
			<table id='pms' class='zebra'>
				<thead><tr><th>Subject</th><th>From</th><th>Date</th><th>Select</th></tr></thead>
				<tbody>";
		$n = 0;
		foreach($pms as $pm) {
			$oe = ($n++ % 2 == 0) ? "even" : "odd";
			$h_subject = html_escape($pm["subject"]);
			if($pm["status"] == "u") $h_subject = "<strong>".$h_subject."</strong>";
			if(strlen(trim($h_subject)) == 0) $h_subject = "no subject";
			$from_name = $pm["from_name"];
			$h_from = html_escape($from_name);
			$from_url = make_link("user/".url_escape($from_name));
			$pm_url = make_link("account/messages/view/".$pm["id"]);
			$h_date = html_escape($pm["sent_date"]);
			$html .= "<tr class='$oe'><td><a href='$pm_url'>$h_subject</a></td>
			<td><a href='$from_url'>$h_from</a></td><td>$h_date</td>
			<td>
				<input name='id[]' type='checkbox' value='".$pm["id"]."' />
			</td></tr>";
		}
		$html .= "
				</tbody>
			</table>";
			
		if($inbox == "inbox"){
			$html .="<input type='submit' name='action' value='Save'>
					 <input type='submit' name='action' value='Delete'>";
		}
		else if($inbox == "saved"){
			$html .="<input type='submit' name='action' value='Un-Save'>
					 <input type='submit' name='action' value='Delete'>";
		}
		else if($inbox == "deleted"){
			$html .="<input type='submit' name='action' value='Un-Delete'>";
		}
		$html .="
			</form>
		";
		
		$page->set_title("Messages");
		$page->set_heading(ucfirst($inbox));
		$page->add_block(new Block("Private Messages", $html, "main", 10));
	}
	
	public function display_outbox(Page $page, $pms) {
		$html = "
			<script>
			$(document).ready(function() {
				$(\"#pms\").tablesorter();
			});
			</script>
			<form action='".make_link("account/messages/action")."' method='POST'>
			<table id='pms' class='zebra'>
				<thead><tr><th>Subject</th><th>To</th><th>Date</th><th>Select</th></tr></thead>
				<tbody>";
		$n = 0;
		foreach($pms as $pm) {
			$oe = ($n++ % 2 == 0) ? "even" : "odd";
			$h_subject = html_escape($pm["subject"]);
			if(strlen(trim($h_subject)) == 0) $h_subject = "(No subject)";
			$from_name = $pm["to_name"];
			$h_from = html_escape($from_name);
			$from_url = make_link("user/".url_escape($from_name));
			$pm_url = make_link("account/messages/view/".$pm["id"]);
			$h_date = html_escape($pm["sent_date"]);
			$html .= "<tr class='$oe'><td><a href='$pm_url'>$h_subject</a></td>
			<td><a href='$from_url'>$h_from</a></td><td>$h_date</td>
			<td>
				<input name='id[]' type='checkbox' value='".$pm["id"]."' />
			</td></tr>";
		}
		$html .= "
				</tbody>
			</table>
			</form>
		";
		
		$page->set_title("Messages");
		$page->set_heading("Outbox");
		$page->add_block(new Block("Private Messages", $html, "main", 10));
	}
// }}}
}
?>
