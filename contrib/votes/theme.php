<?php

class VotesTheme extends Themelet {
	public function get_voter_html(Image $image, $vote) {
		$i_image_id = int_escape($image->id);
		$i_score = int_escape($image->votes);

		$html = "Current Score: $i_score";
		
		$button_up = "<p><form action='".make_link("votes")."' method='POST'>
			<input type='hidden' name='image_id' value='$i_image_id'>
			<input type='hidden' name='vote' value='up'>
			<input type='submit' value='Vote Up'>
			</form>";
		
		$button_remove ="<form action='".make_link("votes")."' method='POST'>
			<input type='hidden' name='image_id' value='$i_image_id'>
			<input type='hidden' name='vote' value='null'>
			<input type='submit' value='Remove Vote'>
			</form>";
			
		$button_down = "<form action='".make_link("votes")."' method='POST'>
			<input type='hidden' name='image_id' value='$i_image_id'>
			<input type='hidden' name='vote' value='down'>
			<input type='submit' value='Vote Down'>
			</form>";
		
		if($vote["vote"] == -1){
			$html .= $button_up.$button_remove;
		}
		elseif($vote["vote"] == 1){
			$html .= $button_remove.$button_down;
		}
		else{
			$html .= $button_up.$button_remove.$button_down;
		}
		return $html;
	}
}

?>
