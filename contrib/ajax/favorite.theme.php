<?php
class CustomFavoritesTheme extends FavoritesTheme {
	public function get_voter_html(Image $image, $is_favorited) {
		global $page, $user;
		
		if(!$is_favorited) {
			$html = "<input id='post-favorite' onclick='PostFavorite($image->id,\"set\");' type='submit' value='Favorite'>";
		}
		else {
			$html = "<input id='post-favorite' onclick='PostFavorite($image->id,\"unset\");' type='submit' value='Un-Favorite'>";
		}
		
		return $html;
	}
}
?>