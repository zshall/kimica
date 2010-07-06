<?php

class VotesTheme extends Themelet {
	public function get_voter_html(Image $image) {
		$i_image_id = int_escape($image->id);
		$i_score = int_escape($image->votes);

		$html = "
			Current Score: $i_score

			<p><form action='".make_link("votes")."' method='POST'>
			<input type='hidden' name='image_id' value='$i_image_id'>
			<input type='hidden' name='vote' value='up'>
			<input type='submit' value='Vote Up'>
			</form>

			<form action='".make_link("votes")."' method='POST'>
			<input type='hidden' name='image_id' value='$i_image_id'>
			<input type='hidden' name='vote' value='null'>
			<input type='submit' value='Remove Vote'>
			</form>

			<form action='".make_link("votes")."' method='POST'>
			<input type='hidden' name='image_id' value='$i_image_id'>
			<input type='hidden' name='vote' value='down'>
			<input type='submit' value='Vote Down'>
			</form>
		";
		return $html;
	}
}

?>
