<?php

class FavoritesTheme extends Themelet {
	public function get_voter_html(Image $image, $is_favorited) {
		global $page, $user;
		
		$html = "<form action='".make_link("change_favorite")."' method='POST'>
				 <input type='hidden' name='image_id' value='$image->id'>";
		
		if(!$is_favorited) {
			$html .= "<input type='hidden' name='favorite_action' value='set'>
					  <input type='submit' value='Favorite'>";
		}
		else {
			$html .= "<input type='hidden' name='favorite_action' value='unset'>
					  <input type='submit' value='Un-Favorite'>";
		}
		
		$html .= "</form>";

		return $html;
	}

	public function display_people($username_array) {
		global $page;

		$i_favorites = count($username_array);
		$html = "$i_favorites people:";

		foreach($username_array as $row) {
			$username = html_escape($row['name']);
			$html .= "<br><a href='".make_link("user/$username")."'>$username</a>";
		}

		$page->add_block(new Block("Favorited By", $html, "left", 25));
	}
	
	public function display_favorites($favorites){
		global $page;
		
		if(!empty($favorites)){
			$page->add_block(new Block("Recent Favorites", $this->build_table($favorites, null), "main", 40));
		}
	}
}

?>
