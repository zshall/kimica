<?php
class ForumTheme extends Themelet {

    public function display_thread_list(Page $page, $threads, $showAdminOptions, $pageNumber, $totalPages)
    {
        if(count($threads) == 0){
            $html = "There are no threads to show.";
			$pagination = "";
		}
        else{
            $html = $this->make_thread_list($threads, $showAdminOptions);
			$pagination = $this->build_paginator("forum/index", null, $pageNumber, $totalPages);
		}
				
		$page->set_title(html_escape("Forum"));
		$page->set_heading(html_escape("Forum"));
        $page->add_block(new Block("Forum", $html.$pagination, "main", 10));
    }



    public function display_new_thread_composer(Page $page, $threadText = null, $threadTitle = null)
    {
		global $config, $user;
		$max_characters = $config->get_int('forumMaxCharsPerPost');
		
		$html = "<script type='text/javascript'>
				function textCounter(textarea, counterID, maxLen) {
				cnt = document.getElementById(counterID);
				if (textarea.value.length > maxLen) {
				textarea.value = textarea.value.substring(0,maxLen);
				}
				cnt.innerHTML = maxLen - textarea.value.length;
				}
				</script>";
		
        $postUrl = make_link("forum/create");
        $html .= '<form action="'.$postUrl.'" method="POST">';

       
        if (!is_null($threadTitle))
        $threadTitle = html_escape($threadTitle);

        if(!is_null($threadText))
        $threadText = html_escape($threadText);
		
		$html .= "
				<table style='width: 500px;'>
					<tr><td>Title:</td><td><input type='text' name='title' value='$threadTitle'></td></tr>
					<tr><td>Message:</td><td><textarea id='message' name='message' rows='10' onkeyup=\"textCounter(this,'count_display',$max_characters);\" onkeydown=\"textCounter(this,'count_display',$max_characters);\"></textarea>
					<tr><td></td><td><small><span id='count_display'>$max_characters</span> characters remaining.</small></td></tr>";
		if($user->is_admin()){
			$html .= "<tr><td colspan='2'><label for='sticky'>Sticky:</label><input name='sticky' type='checkbox' value='Y' /></td></tr>";
		}
			$html .= "<tr><td colspan='2'><input type='submit' value='Submit' /></td></tr>
				</table>
				</form>
				";

        $blockTitle = "Write a new thread";
		$page->set_title(html_escape($blockTitle));
		$page->set_heading(html_escape($blockTitle));
        $page->add_block(new Block($blockTitle, $html, "main", 20));
    }
	
	
	
    public function display_new_post_composer(Page $page, $threadID)
    {
		global $config;
		
		$max_characters = $config->get_int('forumMaxCharsPerPost');
		
		$html = "<script type='text/javascript'>
				function textCounter(textarea, counterID, maxLen) {
				cnt = document.getElementById(counterID);
				if (textarea.value.length > maxLen) {
				textarea.value = textarea.value.substring(0,maxLen);
				}
				cnt.innerHTML = maxLen - textarea.value.length;
				}
				</script>";
		
        $postUrl = make_link("forum/answer");
			
        $html .= '<form action="'.$postUrl.'" method="POST">';

        $html .= '<input type="hidden" name="threadID" value="'.$threadID.'" />';
		
		$html .= "
				<table style='width: 500px;'>
					<tr><td>Message:</td><td><textarea id='message' name='message' rows='10' onkeyup=\"textCounter(this,'count_display',$max_characters);\" onkeydown=\"textCounter(this,'count_display',$max_characters);\"></textarea>
					<tr><td></td><td><small><span id='count_display'>$max_characters</span> characters remaining.</small></td></tr>
					</td></tr>";
							
		$html .= "<tr><td colspan='2'><input type='submit' value='Submit' /></td></tr>
				</table>
				</form>
				";

        $blockTitle = "Answer to this thread";
        $page->add_block(new Block($blockTitle, $html, "main", 30));
    }



    public function display_thread($posts, $is_admin, $is_logged,  $threadTitle, $threadID, $pageNumber, $totalPages)
    {
		global $config, $page/*, $user*/;

		$theme_name = $config->get_string('theme');
		$data_href = $config->get_string('base_href');
		$base_href = $config->get_string('base_href');
		
		$title = $threadTitle;
		
		$n = 0;
		
		$html ="
				<script language='javascript'>
				function quote(fieldId, user, message)
				{
					field=document.getElementById(fieldId);
				
					if (document.selection) 
					{
						field.focus();
						sel = document.selection.createRange();
						sel.text = '[quote=' + user + ']' + message + '[/quote]';
					}
					else if (field.selectionStart || field.selectionStart == 0) 
					{
						var startPos = field.selectionStart;
						var endPos = field.selectionEnd;
						field.focus();
						field.value = field.value.substring(0, startPos) + '[quote=' + user + ']' + message + '[/quote]' + field.value.substring(endPos, field.value.length);
					} 
				}
			</script>
		";
		
        $html .= "<table id='postList' class='zebra'>".
			"<thead><tr>".
            "<th>User</th>".
            "<th>Message</th>".
			"</tr></thead>";
		
        foreach ($posts as $post)
        {
            $unformated = $post["message"];

            $tfe = new TextFormattingEvent($unformated);
            send_event($tfe);
            $message = $tfe->formatted;
			
			$message = str_replace('\n\r', '<br>', $message);
            $message = str_replace('\r\n', '<br>', $message);
            $message = str_replace('\n', '<br>', $message);
            $message = str_replace('\r', '<br>', $message);
			
			
            $user = "<a href='".make_link("user/".$post["user_name"]."")."'>".$post["user_name"]."</a>";

            $poster = User::by_name($post["user_name"]);
			$gravatar = $poster->get_avatar_html();

            $oe = ($n++ % 2 == 0) ? "even" : "odd";
			
			if ($post["user_role"] == "o") {
			$rank = "<sup>owner</sup>";
			}
			else if ($post["user_role"] == "a") {
			$rank = "<sup>admin</sup>";
			}
			else if ($post["user_role"] == "m") {
			$rank = "<sup>moderator</sup>";
			}
			else {
			$rank = "<sup>user</sup>";
			}
			
			$postID = $post['id'];
			
			//if($user->is_admin()){
			//$delete_link = "<a href=".make_link("forum/delete/".$threadID."/".$postID).">Delete</a>";
			//} else {
			//$delete_link = "";
			//}
			
			$quote_link = "";
			if(!$is_logged){
				$quote_link = " <a href=\"javascript:quote('message', '".$post["user_name"]."', '".$unformated."')\">Quote</a>";
			}
			
			$delete_link = "";
			if($is_admin){
				$delete_link = " <a href=".make_link("forum/delete/".$threadID."/".$postID).">Delete</a>";
			}
            
            $html .= "<tr class='$oe'>".
                "<td class='forum_user'>".$user."<br>".$rank."<br>".$gravatar."</td>".
                "<td class='forum_message'>".$message."</td>"."</tr>
				<tr class='$oe'>
					<td class='forum_subuser'><small>".autodate($post["date"])."</small></td>
					<td class='forum_submessage'>".$quote_link.$delete_link."</td>
				</tr>";

        }
		
        $html .= "</tbody></table>";
        
		$pagination = $this->build_paginator("forum/view/".$threadID, null, $pageNumber, $totalPages);

		$page->set_title(html_escape($title));
		$page->set_heading(html_escape($title));
        $page->add_block(new Block("Thread", $html.$pagination, "main", 20));

    }
	
	

    public function add_actions_block(Page $page, $threadID)
    {
        $html = '<a href="'.make_link("forum/nuke/".$threadID).'">Delete this thread and its posts.</a>';

        $page->add_block(new Block("Admin Actions", $html, "main", 40));
    }



    private function make_thread_list($threads, $is_admin)
    {
        $html = "<table id='threadList' class='zebra'>".
            "<thead><tr>".
            "<th>Title</th>".
            "<th>Author</th>".
			"<th>Updated</th>".
            "<th>Responses</th>";

        if($is_admin){
            $html .= "<th>Actions</th>";
        }

        $html .= "</tr></thead><tbody>";


        $n = 0;
        foreach($threads as $thread)
        {
            $oe = ($n++ % 2 == 0) ? "even" : "odd";
			
			global $config;
			$titleSubString = $config->get_int('forumTitleSubString');
			
			if ($titleSubString < strlen($thread["title"]))
			{
				$title = substr($thread["title"], 0, $titleSubString);
				$title = $title."...";
			} else {
				$title = $thread["title"];
			}
			
			if($thread["sticky"] == "Y"){
				$sticky = "Sticky: ";
			} else {
				$sticky = "";
				}
            
            $html .= "<tr class='$oe'>".
                '<td class="left">'.$sticky.'<a href="'.make_link("forum/view/".$thread["id"]).'">'.$title."</a></td>".
				'<td><a href="'.make_link("user/".$thread["user_name"]).'">'.$thread["user_name"]."</a></td>".
				"<td>".autodate($thread["uptodate"])."</td>".
                "<td>".$thread["response_count"]."</td>";
             
            if($is_admin){
                $html .= '<td><a href="'.make_link("forum/nuke/".$thread["id"]).'" title="Delete '.$title.'">Delete</a></td>';
			}

            $html .= "</tr>";
        }

        $html .= "</tbody></table>";

        return $html;
    }
}
?>