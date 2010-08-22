<?php
class CustomCommentListTheme extends CommentListTheme {
	protected function build_postbox($image_id) {
		global $config;

		$i_image_id = int_escape($image_id);
		$hash = CommentList::get_hash();
		$captcha = $config->get_bool("comment_captcha") ? captcha_get_html() : "";

		return "<div id='comment_form'>
				<textarea id='comment-box-$i_image_id' name='comment' rows='5' cols='50'></textarea>
				$captcha
				<br><input id='comment-button' type='submit' onclick='Comment.Post($i_image_id)' value='Post Comment' />
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
		
		$duser = User::by_id($i_uid);

		$anoncode = "";
		if($h_name == "Anonymous" && $this->anon_id >= 0) {
			$anoncode = "<sup>{$this->anon_id}</sup>";
			$this->anon_id++;
		}
		$h_userlink = "<a href='".make_link("account/profile/$h_name")."'>$h_name</a>$anoncode";
		$stripped_nonl = str_replace("\n", "\\n", substr($tfe->stripped, 0, 50));
		$stripped_nonl = str_replace("\r", "\\r", $stripped_nonl);
		
		$h_dellink = $user->is_admin() ?
			"<a onclick=\"Comment.Remove($i_comment_id); return false;\" href='#'>Del</a> |" : "";
		
		$h_toolslinks = !$user->is_anon() ?
			"$h_dellink <a id=\"vote-up-$i_comment_id\" href=\"#\" onclick=\"Comment.Vote($i_comment_id,'up'); return false;\">Vote Up</a> | <a id=\"vote-down-$i_comment_id\" href=\"#\" onclick=\"Comment.Vote($i_comment_id,'down'); return false;\">Vote Down</a> | <a href='#' OnClick=\"BBcode.Quote('comment-box-".$i_image_id."', '".$h_name."', '".$comment->comment."'); return false;\">Quote</a> | <a href=\"#comment-$i_comment_id\">Link</a>" : "";

		if($trim) {
			return "
				$h_userlink: $h_comment
				<a href='".make_link("post/view/$i_image_id")."'>&gt;&gt;&gt;</a>
				$h_toolslinks
			";
		}
		else {
			$avatar = $duser->get_avatar_html();
			$oe = ($this->comments_shown++ % 2 == 0) ? "even" : "odd";
			return "
				<li id='comment-$i_comment_id'>
					<div id='comment' class='$oe'>
						<div class='author'>
							<p>$h_userlink</p>
							<a href='".make_link("account/profile/$h_name")."'>$avatar</a>
						</div>
						<div class='content'>
							<div class='date'>$h_timestamp</div>
							<p>$h_comment</p>	
						</div>
						<div class='footer'>
							$h_toolslinks
						</div>
					</div>
				</li>
			";
		}
	}
}
?>