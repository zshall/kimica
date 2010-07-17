<?php
if(class_exists("Votes")){
	class CustomVotesTheme extends VotesTheme {
		public function get_voter_html(Image $image, $vote) {
			
			$score_info = "Current Score: $image->votes";
									
			if($vote["vote"] == -1){
				$buttons = "<input id='post-vote-up' onclick='Post.Vote($image->id, \"up\");' type='button' value='Vote Up'>";
				$buttons .= "<input id='post-vote-remove' onclick='Post.Vote($image->id, \"null\");' type='button' value='Remove Vote'>";
				$buttons .= "<input style='display:none;' id='post-vote-down' onclick='Post.Vote($image->id, \"down\");' type='button' value='Vote Down'>";
			}
			elseif($vote["vote"] == 1){
				$buttons = "<input style='display:none;' id='post-vote-up' onclick='Post.Vote($image->id, \"up\");' type='button' value='Vote Up'>";
				$buttons .= "<input id='post-vote-remove' onclick='Post.Vote($image->id, \"null\");' type='button' value='Remove Vote'>";
				$buttons .= "<input id='post-vote-down' onclick='Post.Vote($image->id, \"down\");' type='button' value='Vote Down'>";
			}
			else{
				$buttons = "<input id='post-vote-up' onclick='Post.Vote($image->id, \"up\");' type='button' value='Vote Up'>";
				$buttons .= "<input id='post-vote-remove' onclick='Post.Vote($image->id, \"null\");' type='button' value='Remove Vote'>";
				$buttons .= "<input id='post-vote-down' onclick='Post.Vote($image->id, \"down\");' type='button' value='Vote Down'>";
			}	
			
			return $score_info.$buttons;		
		}
	}
}
?>
