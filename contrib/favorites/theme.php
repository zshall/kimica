<?php

class FavoritesTheme extends Themelet {
	public function get_voter_html(Image $image, $is_favorited) {
		global $page, $user;
				
		$form_open = "<form action='".make_link("change_favorite")."' method='POST'>";
				 
		$image_info = "<input id='image_id' type='hidden' value='$image->id' name='image_id'>";
		
		if(!$is_favorited) {
			$buttons = "<input type='hidden' name='favorite_action' value='set'>
					  	<input id='post-favorite' type='submit' value='Favorite'>";
		}
		else {
			$buttons = "<input type='hidden' name='favorite_action' value='unset'>
					  	<input id='post-favorite' type='submit' value='Un-Favorite'>";
		}
		
		$form_close = "</form>";
		
		if(class_exists("Ajax")){
			return $image_info.$buttons;
		}
		else{
			return $form_open.$image_info.$buttons.$form_close;
		}
	}

	public function display_people($username_array) {
		global $page;

		$i_favorites = count($username_array);
		$html = "$i_favorites people:";

		foreach($username_array as $row) {
			$username = html_escape($row['name']);
			$html .= "<br><a href='".make_link("account/profile/$username")."'>$username</a>";
		}

		$page->add_block(new Block("Favorited By", $html, "left", 25));
	}
	
	public function display_recent_favorites($favorites){
		global $page;
		
		if(!empty($favorites)){
			$page->add_block(new Block("Recent Favorites", $this->build_table($favorites, null), "main", 40));
		}
	}
}

?>
