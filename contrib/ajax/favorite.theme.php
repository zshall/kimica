<?php
class CustomFavoritesTheme extends FavoritesTheme {
	public function get_voter_html(Image $image, $is_favorited) {
		global $page, $user;
		
		$html = "<input id='image_id' type='hidden' value='$image->id' name='image_id'>";
		if(!$is_favorited) {
			$html .= "<input id='post-favorite' type='submit' value='Favorite'>";
		}
		else {
			$html .= "<input id='post-favorite' type='submit' value='Un-Favorite'>";
		}
		
		return $html;
	}
}
?>