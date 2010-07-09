<?php
class CustomCommentListTheme extends CommentListTheme {
	protected function build_postbox($image_id) {
		global $config;

		$i_image_id = int_escape($image_id);
		$hash = CommentList::get_hash();
		$captcha = $config->get_bool("comment_captcha") ? captcha_get_html() : "";

		return "<div id='comment_form'>
				<textarea id='comment_box' name='comment' rows='5' cols='50'></textarea>
				$captcha
				<br><input id='comment_button' type='submit' onclick='Comment.Post($i_image_id)' value='Post Comment' />
				</div>";
	}
	
	protected function comment_to_html($comment, $trim=false) {
		global $user;

		$tfe = new TextFormattingEvent($comment->comment);
		send_event($tfe);

		$i_uid = int_escape($comment->owner_id);
		$h_name = html_escape($comment->owner_name);
		$h_timestamp = autodate($comment->posted);
		$h_comment = ($trim ? substr($tfe->stripped, 0, 50)."..." : $tfe->formatted);
		$i_comment_id = int_escape($comment->comment_id);
		$i_image_id = int_escape($comment->image_id);

		$anoncode = "";
		if($h_name == "Anonymous" && $this->anon_id >= 0) {
			$anoncode = "<sup>{$this->anon_id}</sup>";
			$this->anon_id++;
		}
		$h_userlink = "<a href='".make_link("user/$h_name")."'>$h_name</a>$anoncode";
		$stripped_nonl = str_replace("\n", "\\n", substr($tfe->stripped, 0, 50));
		$stripped_nonl = str_replace("\r", "\\r", $stripped_nonl);
		$h_dellink = $user->is_admin() ?
			"<a ".
			"onclick=\"Comment.Remove($i_comment_id); return false;\" ".
			"href='#'>Del</a> |" : "";
		
		$h_toolslinks = !$user->is_anonymous() ?
			"<br>($h_dellink <a id=\"vote-up-$i_comment_id\" href=\"#\" onclick=\"Comment.Vote($i_comment_id,'up'); return false;\">Vote Up</a> | <a id=\"vote-down-$i_comment_id\" href=\"#\" onclick=\"Comment.Vote($i_comment_id,'down'); return false;\">Vote Down</a>)" : "";

		//$avatar = "";
		//if(!empty($comment->owner->email)) {
		//	$hash = md5(strtolower($comment->owner->email));
		//	$avatar = "<img src=\"http://www.gravatar.com/avatar/$hash.jpg\"><br>";
		//}
		$oe = ($this->comments_shown++ % 2 == 0) ? "even" : "odd";
		return "
			<div class='$oe comment' id='comment-$i_comment_id'>
			$h_userlink ($h_timestamp): $h_comment
			$h_toolslinks
			</div>
		";
	}
}
?>