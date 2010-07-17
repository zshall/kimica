<?php
if(class_exists("Favorites")){
	class CustomFavoritesTheme extends FavoritesTheme {
		public function get_voter_html(Image $image, $is_favorited) {
			global $page, $user;
									
			if(!$is_favorited) {
				$buttons = "<input id='post-favorite-set' onclick='Post.Favorite($image->id, \"set\");' type='button' value='Favorite'>";
				$buttons .= "<input style='display:none;' id='post-favorite-unset' onclick='Post.Favorite($image->id, \"unset\");' type='button' value='Un-Favorite'>";
			}
			else {
				$buttons = "<input style='display:none;' id='post-favorite-set' onclick='Post.Favorite($image->id, \"set\");' type='button' value='Favorite'>";
				$buttons .= "<input id='post-favorite-unset' onclick='Post.Favorite($image->id, \"unset\");' type='button' value='Un-Favorite'>";
			}
			
			return $buttons;
		}
	}
}
?>