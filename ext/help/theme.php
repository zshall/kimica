<?php
class HelpTheme extends Themelet {

	public function display_content($title, $body){
		global $page;

		if($body == "Error"){
			$title = "Error";
			$body = "There is no documentation to show.";
		}

		$page->set_title("Help: ".$title);
		$page->set_heading("Help: ".$title);
		$page->add_block(new Block("Help: ".$title, $body, "main", 0));
	}
	
	public function display_sidebar(){
		global $page;
		
		$html = "";
		$html .= "<a href='".make_link("help/accounts")."'>Accounts</a><br />";
		$html .= "<a href='".make_link("help/artists")."'>Artists</a><br />";
		$html .= "<a href='".make_link("help/bbcode")."'>BBcode</a><br />";
		$html .= "<a href='".make_link("help/cheatsheet")."'>Cheatsheet</a><br />";
		$html .= "<a href='".make_link("help/comments")."'>Comments</a><br />";
		$html .= "<a href='".make_link("help/favorites")."'>Favorites</a><br />";
		$html .= "<a href='".make_link("help/forum")."'>Forum</a><br />";
		$html .= "<a href='".make_link("help/notes")."'>Notes</a><br />";
		$html .= "<a href='".make_link("help/pools")."'>Pools</a><br />";
		$html .= "<a href='".make_link("help/posts")."'>Posts</a><br />";
		$html .= "<a href='".make_link("help/tags")."'>Tags</a><br />";
		$html .= "<a href='".make_link("help/wiki")."'>Wiki</a>";
		
		$page->add_block(new Block("Help", $html, "left", 0));
	}
	
}
?>