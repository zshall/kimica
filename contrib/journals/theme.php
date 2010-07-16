<?php
class JournalsTheme extends Themelet {
	
public function displayNewForm(){
	global $page;
	
	$mood_array = array("accomplished"=>"accomplished", "aggravated"=>"aggravated", "amused"=>"amused", "angry"=>"angry", "annoyed"=>"annoyed", "anxious"=>"anxious", "apathetic"=>"apathetic", "artistic"=>"artistic", "awake"=>"awake", "bitchy"=>"bitchy", "blah"=>"blah", "blank"=>"blank", "bored"=>"bored", "bouncy"=>"bouncy", "busy"=>"busy", "calm"=>"calm", "cheerful"=>"cheerful", "chipper"=>"chipper", "cold"=>"cold", "complacent"=>"complacent", "confused"=>"confused", "contemplative"=>"contemplative", "content"=>"content", "cranky"=>"cranky", "crappy"=>"crappy", "crazy"=>"crazy", "creative"=>"creative", "crushed"=>"crushed", "curious"=>"curious", "cynical"=>"cynical", "depressed"=>"depressed", "determined"=>"determined", "devious"=>"devious", "dirty"=>"dirty", "disappointed"=>"disappointed", "discontent"=>"discontent", "distressed"=>"istressed", "ditzy"=>"ditzy", "dorky"=>"dorky", "drained"=>"drained", "drunk"=>"drunk", "ecstatic"=>"ecstatic", "embarrassed"=>"embarrassed", "energetic"=>"energetic", "enraged"=>"enraged", "enthralled"=>"enthralled", "envious"=>"envious", "exanimate"=>"exanimate", "excited"=>"excited", "exhausted"=>"exhausted", "flirty"=>"flirty", "frustrated"=>"frustrated", "full"=>"full", "geeky"=>"geeky", "giddy"=>"giddy", "giggly"=>"giggly", "gloomy"=>"gloomy", "good"=>"good", "grateful"=>"grateful", "groggy"=>"groggy", "grumpy"=>"grumpy", "guilty"=>"guilty", "happy"=>"happy", "high"=>"high", "hopeful"=>"hopeful", "horny"=>"horny", "hot"=>"hot", "hungry"=>"hungry", "hyper"=>"hyper", "impressed"=>"impressed", "indescribable"=>"indescribable", "indifferent"=>"indifferent", "infuriated"=>"infuriated", "intimidated"=>"intimidated", "irate"=>"irate", "irritated"=>"irritated", "jealous"=>"jealous", "jubilant"=>"jubilant", "lazy"=>"lazy", "lethargic"=>"lethargic", "listless"=>"listless", "lonely"=>"lonely", "loved"=>"loved", "melancholy"=>"melancholy", "mellow"=>"mellow", "mischievous"=>"mischievous", "moody"=>"moody", "morose"=>"morose", "naughty"=>"naughty", "nauseated"=>"nauseated", "nerdy"=>"nerdy", "nervous"=>"nervous", "nostalgic"=>"nostalgic", "numb"=>"numb", "okay"=>"okay", "optimistic"=>"optimistic", "peaceful"=>"peaceful", "pensive"=>"pensive", "pessimistic"=>"pessimistic", "pissed off"=>"pissed off", "pleased"=>"pleased", "predatory"=>"predatory", "productive"=>"productive", "quixotic"=>"quixotic", "recumbent"=>"recumbent", "refreshed"=>"refreshed", "rejected"=>"rejected", "rejuvenated"=>"rejuvenated", "relaxed"=>"relaxed", "relieved"=>"relieved", "restless"=>"restless", "rushed"=>"rushed", "sad"=>"sad", "satisfied"=>"satisfied", "scared"=>"scared", "shocked"=>"shocked", "sick"=>"sick", "silly"=>"silly", "sleepy"=>"sleepy", "sore"=>"sore", "stressed"=>"stressed", "surprised"=>"surprised", "sympathetic"=>"sympathetic", "thankful"=>"thankful", "thirsty"=>"thirsty", "thoughtful"=>"thoughtful", "tired"=>"tired", "touched"=>"touched", "uncomfortable"=>"uncomfortable", "weird"=>"weird", "working"=>"working", "worried"=>"worried");
	
    $html = '<form action="'.make_link("journals/create").'" method="POST"
			<table style="width: 500px;">
			  <tr>
				<td>Title:</td>
				<td><input type="text" name="title" /></td>
			  </tr>
			  <tr>
				<td>Message:</td>
				<td><textarea id="message" name="message" rows="10"></textarea><br><small>You can use bbcode to format text.</samll></td>
			  </tr>
			  <tr>
				<td>Disable Comments:</td>
				<td><input name="disable_comments" type="checkbox" value="Y"/></td>
			  </tr>
			  			  
			  <tr>
				<td>Mood:</td>
				<td>'.$this->dynamic_select($mood_array, "mood", "", $init_value = "accomplished").'</td>
			  </tr>
			  <tr>
				<td>Listening to:</td>
				<td><input type="text" name="listening" /></td>
			  </tr>
			  <tr>
				<td>Reading:</td>
				<td><input type="text" name="reading" /></td>
			  </tr>
			  <tr>
				<td>Watching:</td>
				<td><input type="text" name="watching" /></td>
			  </tr>
			  <tr>
				<td>Playing:</td>
				<td><input type="text" name="playing" /></td>
			  </tr>
			  <tr>
				<td>Eating:</td>
				<td><input type="text" name="eating" /></td>
			  </tr>
			  <tr>
				<td>Drinking:</td>
				<td><input type="text" name="drinking" /></td>
			  </tr>
			  
			  <tr>
				<td colspan="2"><input type="submit" value="Submit" /></td>
			  </tr>
			</table>
			</form>';
	
	$page->set_title(html_escape("New Journal"));
	$page->set_heading(html_escape("New Journal"));
    $page->add_block(new Block("New Journal", $html, "main", 10));
}

public function displayEditForm($journal){
	global $page;
	
	$mood_array = array("accomplished"=>"accomplished", "aggravated"=>"aggravated", "amused"=>"amused", "angry"=>"angry", "annoyed"=>"annoyed", "anxious"=>"anxious", "apathetic"=>"apathetic", "artistic"=>"artistic", "awake"=>"awake", "bitchy"=>"bitchy", "blah"=>"blah", "blank"=>"blank", "bored"=>"bored", "bouncy"=>"bouncy", "busy"=>"busy", "calm"=>"calm", "cheerful"=>"cheerful", "chipper"=>"chipper", "cold"=>"cold", "complacent"=>"complacent", "confused"=>"confused", "contemplative"=>"contemplative", "content"=>"content", "cranky"=>"cranky", "crappy"=>"crappy", "crazy"=>"crazy", "creative"=>"creative", "crushed"=>"crushed", "curious"=>"curious", "cynical"=>"cynical", "depressed"=>"depressed", "determined"=>"determined", "devious"=>"devious", "dirty"=>"dirty", "disappointed"=>"disappointed", "discontent"=>"discontent", "distressed"=>"istressed", "ditzy"=>"ditzy", "dorky"=>"dorky", "drained"=>"drained", "drunk"=>"drunk", "ecstatic"=>"ecstatic", "embarrassed"=>"embarrassed", "energetic"=>"energetic", "enraged"=>"enraged", "enthralled"=>"enthralled", "envious"=>"envious", "exanimate"=>"exanimate", "excited"=>"excited", "exhausted"=>"exhausted", "flirty"=>"flirty", "frustrated"=>"frustrated", "full"=>"full", "geeky"=>"geeky", "giddy"=>"giddy", "giggly"=>"giggly", "gloomy"=>"gloomy", "good"=>"good", "grateful"=>"grateful", "groggy"=>"groggy", "grumpy"=>"grumpy", "guilty"=>"guilty", "happy"=>"happy", "high"=>"high", "hopeful"=>"hopeful", "horny"=>"horny", "hot"=>"hot", "hungry"=>"hungry", "hyper"=>"hyper", "impressed"=>"impressed", "indescribable"=>"indescribable", "indifferent"=>"indifferent", "infuriated"=>"infuriated", "intimidated"=>"intimidated", "irate"=>"irate", "irritated"=>"irritated", "jealous"=>"jealous", "jubilant"=>"jubilant", "lazy"=>"lazy", "lethargic"=>"lethargic", "listless"=>"listless", "lonely"=>"lonely", "loved"=>"loved", "melancholy"=>"melancholy", "mellow"=>"mellow", "mischievous"=>"mischievous", "moody"=>"moody", "morose"=>"morose", "naughty"=>"naughty", "nauseated"=>"nauseated", "nerdy"=>"nerdy", "nervous"=>"nervous", "nostalgic"=>"nostalgic", "numb"=>"numb", "okay"=>"okay", "optimistic"=>"optimistic", "peaceful"=>"peaceful", "pensive"=>"pensive", "pessimistic"=>"pessimistic", "pissed off"=>"pissed off", "pleased"=>"pleased", "predatory"=>"predatory", "productive"=>"productive", "quixotic"=>"quixotic", "recumbent"=>"recumbent", "refreshed"=>"refreshed", "rejected"=>"rejected", "rejuvenated"=>"rejuvenated", "relaxed"=>"relaxed", "relieved"=>"relieved", "restless"=>"restless", "rushed"=>"rushed", "sad"=>"sad", "satisfied"=>"satisfied", "scared"=>"scared", "shocked"=>"shocked", "sick"=>"sick", "silly"=>"silly", "sleepy"=>"sleepy", "sore"=>"sore", "stressed"=>"stressed", "surprised"=>"surprised", "sympathetic"=>"sympathetic", "thankful"=>"thankful", "thirsty"=>"thirsty", "thoughtful"=>"thoughtful", "tired"=>"tired", "touched"=>"touched", "uncomfortable"=>"uncomfortable", "weird"=>"weird", "working"=>"working", "worried"=>"worried");
	
    $html = '<form action="'.make_link("journals/update/".$journal['id']).'" method="POST">
			<table style="width: 500px;">
			  <tr>
				<td>Title:</td>
				<td><input type="text" name="title" value="'.$journal['title'].'" /></td>
			  </tr>
			  <tr>
				<td>Message:</td>
				<td><textarea id="message" name="message" rows="10">'.$journal['message'].'</textarea><br><small>You can use bbcode to format text.</samll></td>
			  </tr>
			  <tr>
				<td>Disable Comments:</td>';
				
	if($journal['disable'] == 'N'){
		$html .= '<td><input name="disable_comments" type="checkbox" value="Y"/></td>';
	}else{
		$html .= '<td><input name="disable_comments" type="checkbox" value="Y" checked/></td>';
	}		
	
	$html .= '</tr>
				
			  <tr>
				<td>Mood:</td>
				<td>'.$this->dynamic_select($mood_array, "mood", "", $journal['mood']).'</td>
			  </tr>
			  <tr>
				<td>Listening to:</td>
				<td><input type="text" name="listening" value="'.$journal['listening'].'"/></td>
			  </tr>
			  <tr>
				<td>Reading:</td>
				<td><input type="text" name="reading" value="'.$journal['reading'].'"/></td>
			  </tr>
			  <tr>
				<td>Watching:</td>
				<td><input type="text" name="watching" value="'.$journal['watching'].'"/></td>
			  </tr>
			  <tr>
				<td>Playing:</td>
				<td><input type="text" name="playing" value="'.$journal['playing'].'"/></td>
			  </tr>
			  <tr>
				<td>Eating:</td>
				<td><input type="text" name="eating" value="'.$journal['eating'].'"/></td>
			  </tr>
			  <tr>
				<td>Drinking:</td>
				<td><input type="text" name="drinking" value="'.$journal['drinking'].'"/></td>
			  </tr>	
				
			  <tr>
			  	<input type="hidden" name="journal_id" value='.$journal['id'].'>
				<td colspan="2"><input type="submit" value="Submit" /></td>
			  </tr>
			</table>
			</form>';
	
	$page->set_title(html_escape("Editing Journal"));
	$page->set_heading(html_escape("Editing Journal"));
    $page->add_block(new Block("Editing Journal", $html, "main", 10));
}




public function displayJournal($journal){
	global $page;
	
	$journalID = $journal['id'];
	$disable = $journal['disable'];
	$title = $journal['title'];
	$message = $journal['message'];
	
	$tfe = new TextFormattingEvent($message);
    send_event($tfe);
    $message = $tfe->formatted;
		
	$html = '<div class="left">'.$message.'</div>';
	
	$page->set_title("Journal $journalID: $title");
	$page->set_heading("Journal $journalID: $title");
	
    $page->add_block(new Block($title, $html, "main", 10));
	
	$this->displayCommentComposer($disable, $journalID);	
}



public function displayComments($comments, $journalID, $pageNumber, $totalPages){
	global $config, $page;
		
	$theme_name = $config->get_string('theme');
	$data_href = $config->get_string('base_href');
	
	$html = '';
	
	foreach($comments as $comment) {
		
	$poster = User::by_name($comment['user_name']);
	$gravatar = $poster->get_avatar_html();
	
	$date = autodate($comment['posted']);
	$user_link = "<a href='".make_link("account/profile/".$comment['user_name'])."'>".$comment['user_name']."</a>";
	
	$tfe = new TextFormattingEvent($comment['comment']);
    send_event($tfe);
    $message = $tfe->formatted;
			
	$html .= '
			<div class="comment">
			<div class="comment-gravatar">'.$gravatar.'</div>
			<div class="comment-date"><strong>Posted:</strong> '.$date.'.</div>
			<div class="comment-message">
			'.$user_link.':<br>
			'.$message.'
			</div>
			<!-- <div class="comment-delete">$h_dellink</div> -->
			</div>
			';
	}
		
	$page->add_block(new Block("Comments", $html, "main", 20));
	
	$this->display_paginator($page, "journals/view/".$journalID, null, $pageNumber, $totalPages);
}



public function displayCommentComposer($disable, $journalID){
	global $user, $page;
	if (!$user->is_anon()){
		if($disable == 'N') {
			$html = '<form action="'.make_link("journals/comment").'" method="POST">';
			 
			$html .= '
					<textarea id="comment" cols="50" rows="5" name="comment"/></textarea>
					<input type="hidden" name="journal_id" value="'.$journalID.'" />
					<br/>
					<br/>
					<input type="submit" value="Post Comment" />
		
					';
					
			$html .= '</form>';
		}else{
			$html = "Comments are disabled.";
		}
		
		$page->add_block(new Block(null, $html, "main", 30));
	}
}



public function displayAllJournals($journals, $pageNumber, $totalPages){
	global $config, $page;
	
	$theme_name = $config->get_string('theme');
	$data_href = $config->get_string('base_href');
	
	 $n = 0;

	$html = "<table id='journalList' class='zebra'>".
		"<thead><tr>".
		"<th>Title</th>".
		"<th>Author</th>".
		"<th>Posted</th>".
		"<th>Comments</th>";
			
	$html .= "</tr></thead>";
	
	foreach($journals as $journal) {
		$oe = ($n++ % 2 == 0) ? "even" : "odd";
		
		$titleSubString = $config->get_int('journalsTitleSubString', 25);
				
		if ($titleSubString < strlen($journal["title"]))
		{
			$title = substr($journal["title"], 0, $titleSubString);
			$title = $title."...";
		} else {
			$title = $journal["title"];
		}
			
		$date = autodate($journal['posted']);
		$user_link = "<a href='".make_link("account/profile/".$journal['user_name'])."'>".$journal['user_name']."</a>";
		$title_link = "<a href='".make_link("journals/view/".$journal['id'])."'>".$title."</a>";
		$edit_link = "<a href='".make_link("journals/edit/".$journal['id'])."'>Delete</a>";
		$delete_link = "<a href='".make_link("journals/delete/".$journal['id'])."'>Delete</a>";
				
		$html .= "<tr class='$oe'>".
				"<td class='left'>".$title_link."</td>".
				"<td>".$user_link."</td>".
				"<td>".$date."</td>".
				"<td>".$journal['comments']."</td>";
	}
	
	$html .= "</tr></tbody></table>";
	
	$page->set_title(html_escape("Journals"));
	$page->set_heading(html_escape("Journals"));
    $page->add_block(new Block("Journals", $html, "main", 10));
	
	$this->display_paginator($page, "journals/list", null, $pageNumber, $totalPages);
}




public function displayUserJournals($journals, $userNAME, $is_owner, $pageNumber, $totalPages){
	global $config, $page;
	
	$theme_name = $config->get_string('theme');
	$data_href = $config->get_string('base_href');
	
	 $n = 0;

	$html = "<table id='journalList' class='zebra'>".
		"<thead><tr>".
		"<th>Title</th>".
		"<th>Posted</th>".
		"<th>Comments</th>";
		
	if($is_owner == TRUE){
		$html .= "<th>Actions</th>";
	}
	
	$html .= "</tr></thead>";
	
	foreach($journals as $journal) {
		$oe = ($n++ % 2 == 0) ? "even" : "odd";
		
		$titleSubString = $config->get_int('journalsTitleSubString', 25);
				
		if ($titleSubString < strlen($journal["title"]))
		{
			$title = substr($journal["title"], 0, $titleSubString);
			$title = $title."...";
		} else {
			$title = $journal["title"];
		}
			
		$date = autodate($journal['posted']);
		$user_link = "<a href='".make_link("account/profile/".$journal['user_name'])."'>".$journal['user_name']."</a>";
		$title_link = "<a href='".make_link("journals/view/".$journal['id'])."'>".$title."</a>";
		$edit_link = "<a href='".make_link("journals/edit/".$journal['id'])."'>Delete</a>";
		$delete_link = "<a href='".make_link("journals/delete/".$journal['id'])."'>Delete</a>";
				
		$html .= "<tr class='$oe'>".
				"<td class='left'>".$title_link."</td>".
				"<td>".$date."</td>".
				"<td>".$journal['comments']."</td>";
		
		if($is_owner == TRUE){
			$html .= "<td>".$delete_link."</td>";
		}
	}
	
	$html .= "</tr></tbody></table>";
	
	$page->set_title(html_escape($userNAME."'s Journals"));
	$page->set_heading(html_escape($userNAME."'s Journals"));
    $page->add_block(new Block($userNAME."'s Journals", $html, "main", 10));
	
	$this->display_paginator($page, "journals/user/".$userNAME, null, $pageNumber, $totalPages);
}

public function sidebar_profile($info){
	global $page;
	
	$poster = User::by_name($info['user']);
	$gravatar = $poster->get_avatar_html();
	
	$user_link = "<a href='".make_link("account/profile/".$info['user'])."'>".$info['user']."</a>";

	$profile = '<div align="center">'.$gravatar.'<br>'.$user_link.'</div><br>';
	
	if(!empty($info['mood'])){
		$profile .='<strong>Mood</strong>: '.$info['mood'].'<br><br>';
	}
	
	if(!empty($info['listening'])){
		$profile .='<strong>Listening</strong>: '.$info['listening'].'<br>';
	}
	if(!empty($info['reading'])){
		$profile .='<strong>Reading</strong>: '.$info['reading'].'<br>';
	}
	if(!empty($info['watching'])){
		$profile .='<strong>Watching</strong>: '.$info['watching'].'<br>';
	}
	if(!empty($info['playing'])){
		$profile .='<strong>Playing</strong>: '.$info['playing'].'<br>';
	}
	if(!empty($info['eating'])){
		$profile .='<strong>Eating</strong>: '.$info['eating'].'<br>';
	}
	if(!empty($info['drinking'])){
		$profile .='<strong>Drinking</strong>: '.$info['drinking'];
	}
	
	$page->add_block(new Block("Journalist", $profile, "left", 10));
}


public function sidebar_options($journalID, $isOwner){
	global $user, $page;
	
	$editor = " <form action='".make_link("journals/post_new")."' method='POST'>
					<input type='submit' name='edit' id='new' value='New Journal'/>
				</form>
				";
	
	if($isOwner){
		$editor .= "
				<script type='text/javascript'>
					function confirm_action() {
						return confirm('Are you sure that you want to delete this pool?');
					}
				</script>
				
				<form action='".make_link("journals/post_edit")."' method='POST'>
					<input type='submit' name='edit' id='edit' value='Edit Journal'/>
					<input type='hidden' name='journal_id' value='".$journalID."'>
				</form>
									
				<form action='".make_link("journals/post_delete")."' method='POST'>
					<input type='submit' name='delete' id='delete' value='Delete Journal' onclick='return confirm_action()' />
					<input type='hidden' name='journal_id' value='".$journalID."'>
				</form>
				";
	}
	$page->add_block(new Block("Manage Journal", $editor, "left", 20));
}

public function dynamic_select($the_array, $element_name, $label, $init_value = "") {
    $menu = ($label != "") ? "<label for=\"".$element_name."\">".$label."</label>\n" : "";
    $menu .= "<select name=\"".$element_name."\" id=\"".$element_name."\">\n";
    if (empty($_REQUEST[$element_name])) {
        if ($init_value == "") {
            $menu .= "  <option value=\"\">...</option>\n";
             $curr_val = "";
        } else {
            $curr_val = $init_value;
        }
    } else {
        $curr_val = $_REQUEST[$element_name];
    }
    foreach ($the_array as $key => $value) {
        $menu .= "  <option value=\"".$key."\"";
        $menu .= ($key == $curr_val) ? " selected>" : ">";
        $menu .= $value."</option>\n";
    }
    $menu .= "</select>\n";
    return $menu;
} 

}
?>
