<?php

class CustomViewImageTheme extends ViewImageTheme {
	/*
	 * Build a page showing $image and some info about it
	 */
	public function display_page(Page $page, Image $image, $editor_parts) {
		$metatags = str_replace(" ", ", ", html_escape($image->get_tag_list()));

		$page->set_mode("data");
		$page->set_data($this->get_html($page, $image, $editor_parts, $metatags));
	}

	private function get_comments($image_id) {
		global $config;
		global $database;
		$i_image_id = int_escape($image_id);
		$rows = $database->get_all("
				SELECT
				users.id as user_id, users.name as user_name, users.email as user_email,
				comments.comment as comment, comments.id as comment_id,
				comments.image_id as image_id, comments.owner_ip as poster_ip,
				comments.posted as posted
				FROM comments
				LEFT JOIN users ON comments.owner_id=users.id
				WHERE comments.image_id=?
				ORDER BY comments.id ASC
				", array($i_image_id));
		$comments = array();
		foreach($rows as $row) {
			$comments[] = new Comment($row);
		}
		return $comments;
	}
	
	private function can_comment() {
		global $config;
		global $user;
		return ($config->get_bool('comment_anon') || !$user->is_anonymous());
	}

	private function get_html(Page $page, Image $image, $editor_parts, $metatags) {
		$html = "";
		$html .= "<meta name=\"keywords\" content=\"$metatags\">";
		$html .= "<div id='view' title='Image {$image->id}' class='panel' selected='true'>";
		$html .= $this->build_pin($image);
		$pixel = new CustomPixelFileHandlerTheme();
		$html .= $pixel->display_image($page,$image);
		$html .= $this->build_info($image);
		$cl = new CommentList();
		$ct = new CustomCommentListTheme();
		$html .= $ct->display_image_comments(
			$image,
			$this->get_comments($image->id),
			$this->can_comment()
		);
		$html .= "</div>";
		return $html;
	}

	protected function build_pin(Image $image) {
		global $database;

		if(isset($_GET['search'])) {
			$search_terms = explode(' ', $_GET['search']);
			$query = "search=".url_escape($_GET['search']);
		}
		else {
			$search_terms = array();
			$query = null;
		}

		$h_prev = "<a id='prevlink' href='".make_link("post/prev/{$image->id}", $query)."'>Prev</a>";
		$h_index = "<a href='".make_link()."'>Index</a>";
		$h_next = "<a id='nextlink' href='".make_link("post/next/{$image->id}", $query)."'>Next</a>";
		$script = "
		<script><!--
		$(document).ready(function() {
			if(document.location.hash.length > 3) {
				query = document.location.hash.substring(1);
				a = document.getElementById(\"prevlink\");
				a.href = a.href + '?' + query;
				a = document.getElementById(\"nextlink\");
				a.href = a.href + '?' + query;
			}
		});
		//--></script>
			";

		return "<fieldset><div class='row'><p>$h_prev | $h_index | $h_next$script</p></div></fieldset>";
	}

	protected function build_info(Image $image) {
		global $user;
		$owner = $image->get_owner();
		$h_owner = html_escape($owner->name);
		$h_ip = html_escape($image->owner_ip);
		$h_source = html_escape($image->source);
		$i_owner_id = int_escape($owner->id);
		$h_date = autodate($image->posted);

		$html = "";
		$html .= "<fieldset>
					<div class='row'>
						<label>Uploaded by</label>
						<span><a href='".make_link("user/$h_owner")."'>$h_owner</a></span>
					</div>
					<div class='row'>
						<label>Date Added</label>
						<span>$h_date</span>
					</div>";

		if($user->is_admin()) {
			$html .= "<div class='row'>
						<label>Owner IP</label>
						<span>$h_ip</span>
					  </div>";
		}
		if(!is_null($image->source)) {
			if(substr($image->source, 0, 7) == "http://") {
				$html .= "<div class='row'>
						<label>Source</label>
						<span><a href='$h_source'>link</a></span>
					  </div>";
			}
			else {
				$html .= "<div class='row'>
						<label>Source</label>
						<span><a href='http://$h_source'>link</a></span>
					  </div>";
			}
		}
		$html .= "</fieldset>";
		
		if($user->is_admin()) {
			$html .= "<br />
			<form action='".make_link("image_admin/delete")."' method='POST'> 
				<input type='hidden' name='image_id' value='".$image->id."'> 
				<input type='submit' class='redButton' value='Delete'> 
			</form> ";
		}

		return $html;
	}
}


?>
