<?php
class BlacklistTheme extends Themelet {

	public function display_blacklist($tags, $can_add){
		global $page;
		
		$html = '';
				
		if($can_add){
			$html .= '<form action="'.make_link("account/blacklist/add").'" method="POST">
						<table style="width: 300px;">
							<tr><td>Tag:</td><td><input id="subscriptionTag" class="editor_tags" type="text" name="tag"></td></tr>
							<tr><td colspan="2"><input type="submit" value="Add Tag" /></td></tr>
						</table>
					   </form>
					   ';
		} else {
			$html = 'You\'ve reached the max tags allowed per user. Delete a tag to create a new one.';
		}
		
		$html .= '<table id="blackList" class="zebra">'.
				'<thead><tr>'.
				'<th>Tag</th>';
				
		$html .= "<th>Actions</th>";
		$html .= "</tr></thead><tbody>";
		
		$n = 0;
		foreach($tags as $tag)
		{
			$oe = ($n++ % 2 == 0) ? "even" : "odd";
										
			$html .= '<tr class="'.$oe.'">'.
					 '<td><a href="'.make_link("post/list/".$tag["tag"]).'/1">'.$tag["tag"].'</a></a></td>'.
					 '<td><a href="'.make_link("account/blacklist/delete/".$tag["tag"]).'">Delete</a></td>'.
					 '</tr>';
		}
	
		$html .= "</tbody></table>";
		
		$page->set_title("Tag Blacklist");
		$page->set_heading("Tag Blacklist");
		$page->add_block(new Block("Tag Blacklist", $html, "main", 10));
	}
}
?>
