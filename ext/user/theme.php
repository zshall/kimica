<?php

class UserPageTheme extends Themelet {
	public function display_login_page(Page $page) {
		$page->set_title("Login");
		$page->set_heading("Login");
		$page->add_block(new NavBlock());
		$page->add_block(new Block("Login There",
			"There should be a login box to the left"));
	}

	public function display_user_list($users, User $user, $page_number, $total_pages) {	
		global $page;
			
		$page->set_title("User List");
		$page->set_heading("User List");
		$page->add_block(new NavBlock());
		$html = "<script>
			$(document).ready(function() {
				$(\"#users\").tablesorter();
			});
			</script>
			<form action='".make_link("account/bans/bulk")."' method='POST'>
			<table id='users' class='zebra'>";
			
		$message = "";
		if(!$user->is_anon()){
			$message = "<th>Contact</th>";
		}
		$actions = "";
		if($user->is_admin() || $user->is_owner()){
			$actions = "<th>Actions</th>";
		}
		$html .= "<thead><tr><th>User</th><th>Role</th><th>Joined</th>$message$actions</thead>
				<tbody>";
		$n = 0;
		foreach($users as $duser) {
			$oe = ($n++ % 2 == 0) ? "even" : "odd";
			$html .= "<tr class='$oe'>";
			$html .= "<td><a href='".make_link("user/".$duser->name)."'>".html_escape($duser->name)."</a></td>";
			$html .= "<td>".ucfirst($duser->role_to_human())."</td>";
			$html .= "<td>".$duser->join_date."</td>";
			if(!$user->is_anon()){
				$html .= "<th><a href='".make_link("account/messages/new/".$duser->id)."'>Send Message</a></th>";
			}
			if($user->is_admin() || $user->is_owner()){
				$html .= "<th><input name='user_id[]' type='checkbox' value='".$duser->id."' /></th>";
			}
			$html .= "</tr>";
		}
		if($user->is_admin() || $user->is_owner()){
			$actions = "<input type='Submit' value='Ban'>";
		}
		$html .= "</tbody></table>$actions</form>";
		
		$pagination = $this->build_paginator("user/list", null, $page_number, $total_pages);
		$page->add_block(new Block("Users", $html.$pagination));
	}
	
	public function display_user_bans($banned){
		global $user, $page;
		$page->set_title("Banned Users");
		$page->set_heading("Banned Users");
		$html = "<script>
			$(document).ready(function() {
				$(\"#users\").tablesorter();
			});
			</script>
			<form action='".make_link("account/bans/remove")."' method='POST'>
			<table id='users' class='zebra'>";
		
		$actions = "";
		if($user->is_admin() || $user->is_owner()){
			$actions = "<th>Un-Ban</th>";
		}
		
		$html .= "<thead><tr><th>User</th><th>End Date</th><th>Reason</th>$actions</thead>
				<tbody>";
		$n = 0;
		foreach($banned as $duser) {
			$oe = ($n++ % 2 == 0) ? "even" : "odd";
			$html .= "<tr class='$oe'>";
			$html .= "<td><a href='".make_link("user/".$duser["name"])."'>".html_escape($duser["name"])."</a></td>";
			$html .= "<td>".$duser["end_date"]."</td>";
			$html .= "<td>".$duser["reason"]."</td>";
			if($user->is_admin() || $user->is_owner()){
				$html .= "<th><input name='user_id[]' type='checkbox' value='".$duser["id"]."' /></th>";
			}
			$html .= "</tr>";
		}
		if($user->is_admin() || $user->is_owner()){
			$actions = "<input type='Submit' value='Un-Ban'>";
		}
		$html .= "</tbody></table>$actions</form>";
		$page->add_block(new Block("Banned Users", $html));
		
	}
	
	public function display_user_prebans($prebans){
		global $user, $page;
		$page->set_title("Banned Users");
		$page->set_heading("Banned Users");
		$html = "<script>
			$(document).ready(function() {
				$(\"#users\").tablesorter();
			});
			</script>
			<form action='".make_link("account/bans/add")."' method='POST'>
			<table id='users' class='zebra'>";
			
		$actions = "";
		if($user->is_admin() || $user->is_owner()){
			$actions = "<th>Ban</th>";
		}
			
		$html .= "<thead><tr><th>User</th><th>End Date</th><th>Reason</th>$actions</thead>
				<tbody>";
		$n = 0;
		foreach($prebans as $duser) {
			$oe = ($n++ % 2 == 0) ? "even" : "odd";
			$html .= "<tr class='$oe'>";
			$html .= "<td><a href='".make_link("user/".$duser->name)."'>".html_escape($duser->name)."</a></td>";
			$html .= "<td><input name='bans[".$n."][]' type='text' /></td>";
			$html .= "<td><input name='bans[".$n."][]' type='text' /></td>";
			if($user->is_admin() || $user->is_owner()){
				$html .= "<th><input name='bans[".$n."][]' type='checkbox' value='".$duser->id."' /></th>";
			}
			$html .= "</tr>";
		}
		
		$actions = "";
		if($user->is_admin() || $user->is_owner()){
			$actions = "<input type='Submit' value='Ban'>";
		}
		$html .= "</tbody></table>$actions</form>";
		$page->add_block(new Block("Banned Users", $html));
		
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
				<table style='width:100%;' summary='Login Form'>
					<tr><td>Name</td></tr>
					<tr><td><input id='user' type='text' name='user'></td></tr>
					<tr><td>Password</td></tr>
					<tr><td><input id='pass' type='password' name='pass'></td></tr>
				</table>
				<input type='submit' value='Log In'>
			</form>
		";
		if($config->get_bool("login_signup_enabled")) {
			$html .= "<small><a href='".make_link("account/create")."'>Signup</a></small>&nbsp;|&nbsp;";
		}
		$html .= "<small><a href='".make_link("account/recover")."'>Recover Password</a></small>";
		$page->add_block(new Block("Login", $html, "left", 90));
	}

		public function display_user_page(User $duser, $stats) {
		global $page, $user;
		assert(is_array($stats));
		
		$html = "<table id='stats' class='zebra'><tbody>";
						
		$n = 0;
		foreach($stats as $stat) {
			$oe = ($n++ % 2 == 0) ? "even" : "odd";
			$html .= "<tr class='$oe'><td>".$stat['0']."</td><td>".$stat['1']."</td></tr>";
		}
		
		$html .= "</tbody></table>";
		

		$page->set_title("{$duser->name}'s Page");
		$page->set_heading("{$duser->name}'s Page");
		$page->add_block(new NavBlock());
		$page->add_block(new Block("Stats", $html, "main", 0));

		if(!$user->is_anon()) {
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
		else if($user->is_admin() && ((!$duser->is_admin()) || ($user->id == $duser->id))) {
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
		
		$html = "<a href='".make_link("account/messages/new")."'>New</a><br>";
		$html .= "<a href='".make_link("account/messages/inbox")."'>Inbox</a> (<a href='".make_link("account/messages/inbox")."'>".$unread."</a>)<br>";
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

		if(empty($pms)){
			$html = "You have no messages.";
		}
		
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
