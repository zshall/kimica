<?php
class CustomVotesTheme extends VotesTheme {
	public function get_voter_html(Image $image) {
		$i_score = int_escape($image->votes);

		$html = "
			Current Score: $i_score

			<p><input type='submit' onclick='Post.Vote($image->id,\"up\");' value='Vote Up'>
			<br>
			<input type='submit' onclick='Post.Vote($image->id,\"down\");' value='Vote Down'>
			</p>
		";
		return $html;
	}
}
?>