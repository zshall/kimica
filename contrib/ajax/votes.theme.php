<?php
class CustomVotesTheme extends VotesTheme {
	public function get_voter_html(Image $image, $vote) {
		$i_score = int_escape($image->votes);
		
		$html = "
			Current Score: $i_score
			<input id='image_id' type='hidden' value='$image->id' name='image_id'>
		";
		
		if($vote["vote"] == -1){
			$html .= "<input id='post-vote-up' type='submit' value='Vote Up'>";
		}
		elseif($vote["vote"] == 1){
			$html .= "<input id='post-vote-down' type='submit' value='Vote Down'>";
		}
		else{
			$html .= "<input id='post-vote-up' type='submit' value='Vote Up'>";
			$html .= "<input id='post-vote-down' type='submit' value='Vote Down'>";
		}
		
		return $html;
	}
}
?>