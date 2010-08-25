<?php

class VotesTheme extends Themelet {
	public function get_voter_html(Image $image, $vote) {
		
		$score_info = "Current Score: $image->votes";
		
		$form_open = "<form action='".make_link("votes")."' method='POST'>";
		$image_info = "<input id='image_id' type='hidden' value='$image->id' name='image_id'>";
		
		$button_up = "<input type='hidden' name='vote' value='up'>
					  <input id='post-vote-up' type='submit' value='Vote Up'>";
		
		$button_remove ="<input type='hidden' name='vote' value='null'>
						 <input id='post-vote-remove' type='submit' value='Remove Vote'>";
			
		$button_down = "<input type='hidden' name='vote' value='down'>
						<input id='post-vote-down' type='submit' value='Vote Down'>";
						
		$form_close = "</form>";
		
		if($vote["vote"] == -1){
			if(class_exists("Ajax")){
				return $score_info.$image_info.$button_up.$button_remove;
			}
			else{
				return $score_info.$form_open.$image_info.$button_up.$form_close.$form_open.$image_info.$button_remove.$form_close;
			}
		}
		elseif($vote["vote"] == 1){
			if(class_exists("Ajax")){
				return $score_info.$image_info.$button_remove.$button_down;
			}
			else{
				return $score_info.$form_open.$image_info.$button_remove.$form_close.$form_open.$image_info.$button_down.$form_close;
			}
		}
		else{
			if(class_exists("Ajax")){
				return $score_info.$image_info.$button_up.$button_remove.$button_down;
			}
			else{
				return $score_info.$form_open.$image_info.$button_up.$form_close.$form_open.$image_info.$button_remove.$form_close.$form_open.$image_info.$button_down.$form_close;
			}
		}			
	}
	
	public function add_vote_block($image, $vote){
		global $page;
		$page->add_block(new Block("Post Votes", $this->get_voter_html($image, $vote), "left", 40));
	}
}

?>
