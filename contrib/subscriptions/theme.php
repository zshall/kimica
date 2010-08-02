<?php
class SubscriptionTheme extends Themelet {

	public function subscriptions($tags, $can_add, $instant_digest){
		global $page;
		
		$html = '';
		
		$add_instant = $instant_digest ? '<option value="i">Instant Digest</option>' : '';
		
		if($can_add){
			$html .= '<form action="'.make_link("account/subscriptions/add").'" method="POST">
						<table style="width: 300px;">
							<tr><td>Tag:</td><td><input id="subscriptionTag" class="editor_tags" type="text" name="tag"></td></tr>
							<tr><td>Type:</td><td>
							<select name="digest">
								'.$add_instant.'
								<option value="d">Daily  Digest</option>
								<option value="w">Weekly Digest</option>
								<option value="m">Monthly Digest</option>
							</select>
							</td></tr>
							<tr><td>Private:</td><td><input name="private" type="checkbox" value="Y"/></td></tr>
							<tr><td colspan="2"><input type="submit" value="Add Subscription" /></td></tr>
						</table>
					   </form>
					   ';
		} else {
			$html = 'You\'ve reached the max subcriptions allowed per user. Delete a subscription to create a new one.';
		}
		
		$html .= '<table id="subscriptionList" class="zebra">'.
				'<thead><tr>'.
				'<th>Tag</th>'.
				'<th>Type</th>';
				
		$html .= "<th>Private</th><th>Actions</th>";
		$html .= "</tr></thead><tbody>";
		
		$n = 0;
		foreach($tags as $tag)
		{
			$oe = ($n++ % 2 == 0) ? "even" : "odd";
			
			if($tag["digest"] == "i"){
				$type = "Instant";
			}elseif($tag["digest"] == "d"){
				$type = "Daily";
			}elseif($tag["digest"] == "w"){
				$type = "Weekly";
			}elseif($tag["digest"] == "m"){
				$type = "Monthly";
			}
							
			$html .= '<tr class="'.$oe.'">'.
					 '<td><a href="'.make_link("post/list/".$tag["tag_name"]).'/1">'.$tag["tag_name"].'</a></a></td>'.
					 '<td>'.$type.'</td>'.
					 '<td><a href="'.make_link("account/subscriptions/private/".$tag["id"]).'">'.$tag["private"].'</a></td>'.
					 '<td><a href="'.make_link("account/subscriptions/delete/".$tag["id"]).'">Delete</a></td>'.
					 '</tr>';
		}
	
		$html .= "</tbody></table>";
		
		$page->set_title("Tag Subscriptions");
		$page->set_heading("Tag Subscriptions");
		$page->add_block(new Block("Tag Subscriptions", $html, "main", 10));
	}
	
	public function display_subscriptions($tag, $posts, $pos){
		global $page;
				
		if(!empty($posts)){
			$page->add_block(new Block("Subscription: ".$tag, $this->build_table($posts, null), "main", $pos));
		}
	}
}
?>
