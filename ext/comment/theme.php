<?php
class CommentListTheme extends Themelet {
	var $comments_shown = 0;
	var $anon_id = 1;

	/**
	 * Display a page with a list of images, and for each image,
	 * the image's comments
	 */
	public function display_comment_list($images, $page_number, $total_pages, $can_post) {
		global $config, $page, $user;

		// aaaaaaargh php
		assert(is_array($images));
		assert(is_numeric($page_number));
		assert(is_numeric($total_pages));
		assert(is_bool($can_post));

		// parts for the whole page
		$prev = $page_number - 1;
		$next = $page_number + 1;

		$h_prev = ($page_number <= 1) ? "Prev" :
			"<a href='".make_link("comment/list/$prev")."'>Prev</a>";
		$h_index = "<a href='".make_link()."'>Index</a>";
		$h_next = ($page_number >= $total_pages) ? "Next" :
			"<a href='".make_link("comment/list/$next")."'>Next</a>";

		$page->set_title("Comments");
		$page->set_heading("Comments");
		$pagination = $this->build_paginator("comment/list", null, $page_number, $total_pages);

		// parts for each image
		$position = 10;
		$html = "";
		foreach($images as $pair) {
			$image = $pair[0];
			$comments = $pair[1];

			$thumb_html = $this->build_thumb_html($image);

			$comment_html = "";
			$comment_limit = $config->get_int("comment_list_count", 10);
			$comment_count = count($comments);
			if($comment_limit > 0 && $comment_count > $comment_limit) {
				$hidden = $comment_count - $comment_limit;
				$comments = array_slice($comments, -$comment_limit);
			}
			$this->anon_id = 1;
			foreach($comments as $comment) {
				$comment_html .= $this->comment_to_html($comment);
			}
			if(!$user->is_anon()) {
				if($can_post) {
					$comment_html .= $this->build_postbox($image->id);
				}
			} else {
				if ($can_post) {
					if(!$config->get_bool('comment_captcha')) {
						$comment_html .= $this->build_postbox($image->id);
					}
					else {
						$comment_html .= "<a href='".make_link("post/view/".$image->id)."'>Add Comment</a>";
					}
				}
			}
			
			$tags = "";
			foreach($image->get_tag_array() as $tag){
				$tags .= "<a href='".make_link("post/list/$tag/1")."'>".html_escape($tag)."</a> ";
			}
			
			$poster = User::by_id($image->owner_id);
			$poster_link = "<a href='".make_link("account/profile/$poster->name")."'>".html_escape($poster->name)."</a>";
			
			$ratings = "";
			if(class_exists("Ratings")){
				$rating = Ratings::rating_to_human($image->rating);
				$ratings = "<span class='info'><strong>Rating </strong>".ucfirst($rating)."</span>";
			}
			
			$scores = "";
			if(class_exists("Votes")){
				$scores = "<span class='info'><strong>Score </strong>".$image->votes."</span>";
			}
			
			$html .= "
				<div id='comment-list'>
					<div class='comment-thumb'>$thumb_html</div>
					<ul>
						<li>
							<div class='header'>
								<div>
									<span class='info'><strong>Date </strong>".autodate($image->posted)."</span>
									<span class='info'><strong>User </strong>".$poster_link."</span>
									$ratings
									$scores
								</div>
								<div>
									<span class='tags'><strong>Tags </strong>".$tags."</span>
								</div>
							</div>
						</li>
						$comment_html
					</ul>
				</div>
			";
		}
		
		if(($image->is_approved() || $image->is_locked() || ($user->is_admin() || $user->is_mod()))) {
			$page->add_block(new Block("Comments", $html.$pagination, "main", $position++));
		}
		
		if(!$images){
			$page->add_block(new Block("Comments", "There is no comments to show.", "main", $position++));
		}
	}


	/**
	 * Add some comments to the page, probably in a sidebar
	 *
	 * $comments = an array of Comment objects to be shown
	 */
	public function display_recent_comments($comments) {
		global $page;
		$this->anon_id = -1;
		$html = "";
		foreach($comments as $comment) {
			$html .= $this->comment_to_html($comment, true);
		}
		$html .= "<p><a class='more' href='".make_link("comment/list")."'>Full List</a>";
		$page->add_block(new Block("Comments", $html, "left"));
	}


	/**
	 * Show comments for an image
	 */
	public function display_image_comments(Image $image, $comments, $postbox) {
		global $page;
		$this->anon_id = 1;
		$html = "";
		
		if(!$comments){
			$html .= "There is no comments to show.";
			if($postbox){
				$html .= " Be the first!";
			}
		}
		else{
			$html .= "<div id='comments'><ul>";
			foreach($comments as $comment) {
				$html .= $this->comment_to_html($comment);
			}
			$html .= "</ul></div>";
		}
		
		if($postbox) {
			$html .= $this->build_postbox($image->id);
		}
		
		$page->add_block(new Block("Comments", $html, "main", 30));
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
			"<a ".
			"onclick=\"return confirm('Delete comment by $h_name:\\n$stripped_nonl');\" ".
			"href='".make_link("comment/delete/$i_comment_id")."'>Delete</a> |" : "";
		
		$h_toolslinks = !$user->is_anon() ?
			"$h_dellink <a href=".make_link("comment/vote/up/".$i_comment_id).">Vote Up</a> | <a href=".make_link("comment/vote/down/".$i_comment_id).">Vote Down</a> | <a href='#' OnClick=\"BBcode.Quote('comment-box-".$i_image_id."', '".$h_name."', '".$comment->comment."'); return false;\">Quote</a> | <a href=\"#comment-$i_comment_id\">Link</a>" : "";

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
							<h6>$h_userlink</h6>
							<a href='".make_link("account/profile/$h_name")."'>$avatar</a>
						</div>
						<div class='content'>
							<span>$h_timestamp</span>
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

	protected function build_postbox($image_id) {
		global $config;

		$i_image_id = int_escape($image_id);
		$hash = CommentList::get_hash();
		$captcha = $config->get_bool("comment_captcha") ? captcha_get_html() : "";

		return "<li>
					<form name='comment_form' action='".make_link("comment/add")."' method='POST'>
						<input type='hidden' name='image_id' value='$i_image_id' />
						<input type='hidden' name='hash' value='$hash' />
						<textarea id = 'comment-box-".$i_image_id."' name='comment' rows='5' cols='50'></textarea>
						$captcha
						<br><input type='submit' value='Post Comment' />
					</form>
				</li>
				";
	}
}
?>