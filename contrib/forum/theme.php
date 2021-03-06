<?php
class ForumTheme extends Themelet {

    public function display_thread_list(Page $page, $threads, $showAdminOptions, $pageNumber, $totalPages){
        if(count($threads) == 0){
            $html = "There are no threads to show.";
			$pagination = "";
		}
        else{
            $html = $this->make_thread_list($threads, $showAdminOptions);
			$pagination = $this->build_paginator("forum/list", null, $pageNumber, $totalPages);
		}
				
		$page->set_title(html_escape("Forum"));
		$page->set_heading(html_escape("Forum"));
        $page->add_block(new Block("Forum", $html.$pagination, "main", 10));
    }



    public function display_new_thread_composer(Page $page, $threadText = null, $threadTitle = null){
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
			$html .= "<tr><td>Sticky:</td><td><input name='sticky' type='checkbox' value='Y' /></td></tr>";
		}
			$html .= "<tr><td>Subscribe:</td><td><input name='subscribe' type='checkbox' value='Y' /></td></tr>";
			$html .= "</table>
					  <input type='submit' value='Submit' />
					  </form>
					  ";

        $blockTitle = "Write a new thread";
		$page->set_title(html_escape($blockTitle));
		$page->set_heading(html_escape($blockTitle));
        $page->add_block(new Block($blockTitle, $html, "main", 20));
    }
	
	
	
    public function display_new_post_composer(Page $page, $threadID){
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
							
		$html .= "</table>
				 <input type='submit' value='Submit' />
				 </form>
				 ";

        $blockTitle = "Answer";
        $page->add_block(new Block($blockTitle, $html, "main", 30));
    }



    public function display_thread($posts, $is_admin, $is_logged, $threadID, $pageNumber, $totalPages){
		global $config, $page/*, $user*/;

		$title = "";
		
		$n = 0;
			
        $html = "<table id='postList' class='zebra'>".
			"<thead><tr>".
            "<th>User</th>".
            "<th>Message</th>".
			"</tr></thead>";
		
        foreach ($posts as $post)
        {
            $unformated = $post["message"];
			
			$title = $post["title"];

            $tfe = new TextFormattingEvent($unformated);
            send_event($tfe);
            $message = $tfe->formatted;
			
			$message = str_replace('\n\r', '<br>', $message);
            $message = str_replace('\r\n', '<br>', $message);
            $message = str_replace('\n', '<br>', $message);
            $message = str_replace('\r', '<br>', $message);
			
			
            $user = "<a href='".make_link("account/profile/".$post["user_name"]."")."'>".$post["user_name"]."</a>";

            $poster = User::by_name($post["user_name"]);
			$gravatar = $poster->get_avatar_html();

            $oe = ($n++ % 2 == 0) ? "even" : "odd";
			
			$rank = $poster->role_to_human();
						
			$postID = $post['id'];
					
			$unformated = str_replace("'", "\'", $unformated);
						
			$quote_link = "";
			if(!$is_logged){
				$quote_link = " <a href='#' OnClick=\"BBcode.Quote('message', '".$post["user_name"]."', '".$unformated."'); return false;\">Quote</a>";
			}
			
			$message_link = "";
			if(!$is_logged){
				$message_link = " <a href=".make_link("account/messages/new/".$poster->id).">Message</a>";
			}
			
			$report_link = "";
			if(!$is_logged){
				$report_link = " <a href=".make_link("forum/report/".$threadID."/".$postID).">Report</a>";
			}
			
			$delete_link = "";
			if($is_admin){
				$delete_link = " <a href=".make_link("forum/delete/".$threadID."/".$postID).">Delete</a>";
			}
            
            $html .= "<tr id='post-$postID' class='$oe'>".
                "<td class='forum_user'>".$user."<br>".$rank."<br>".$gravatar."</td>".
                "<td class='forum_message'>".$message."</td>"."</tr>
				<tr class='$oe'>
					<td class='forum_subuser'><small>".autodate($post["date"])."</small></td>
					<td class='forum_submessage'>".$message_link.$quote_link.$report_link.$delete_link."</td>
				</tr>";

        }
		
        $html .= "</tbody></table>";
        
		$pagination = $this->build_paginator("forum/thread/".$threadID, null, $pageNumber, $totalPages);

		$page->set_title(html_escape($title));
		$page->set_heading(html_escape($title));
        $page->add_block(new Block("Thread", $html.$pagination, "main", 20));

    }
	
	
	public function display_post($posts, $is_admin, $is_logged){
		global $config, $page/*, $user*/;
			
		$n = 0;
			
        $html = "<table id='postList' class='zebra'>".
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
			
            $user = "<a href='".make_link("account/profile/".$post["user_name"]."")."'>".$post["user_name"]."</a>";

            $poster = User::by_name($post["user_name"]);
			$gravatar = $poster->get_avatar_html();

            $oe = ($n++ % 2 == 0) ? "even" : "odd";
			
			$rank = $poster->role_to_human();
						
			$postID = $post['id'];
					
			$unformated = str_replace("'", "\'", $unformated);
						
			$quote_link = "";
			if(!$is_logged){
				$quote_link = " <a href='#' OnClick=\"BBcode.Quote('message', '".$post["user_name"]."', '".$unformated."'); return false;\">Quote</a>";
			}
			
			$message_link = "";
			if(!$is_logged){
				$message_link = " <a href=".make_link("account/messages/new/".$poster->id).">Message</a>";
			}
			
			$report_link = "";
			if(!$is_logged){
				$report_link = " <a href=".make_link("forum/report/".$post["thread_id"]."/".$postID).">Report</a>";
			}
			
			$delete_link = "";
			if($is_admin){
				$delete_link = " <a href=".make_link("forum/delete/".$post["thread_id"]."/".$postID).">Delete</a>";
			}
            
            $html .= "<tr id='post-$postID' class='$oe'>".
                "<td class='forum_user'>".$user."<br>".$rank."<br>".$gravatar."</td>".
                "<td class='forum_message'>".$message."</td>"."</tr>
				<tr class='$oe'>
					<td class='forum_subuser'><small>".autodate($post["date"])."</small></td>
					<td class='forum_submessage'>".$message_link.$quote_link.$report_link.$delete_link."</td>
				</tr>";

        }
		
        $html .= "</tbody></table>";
        
		$page->set_title("Forum Post");
		$page->set_heading("Forum Post");
        $page->add_block(new Block("Forum Post", $html, "main", 20));

    }	
	

    public function add_actions_block(Page $page, $threadID, $sticky, $locked, $subscribed){
		global $user;
		
		if($subscribed){
			$html = '<a href="'.make_link("forum/subscription/delete/".$threadID).'">Un-Subscribe</a>';
		}
		else{
			$html = '<a href="'.make_link("forum/subscription/create/".$threadID).'">Subscribe</a>';
		}
		
		if($user->is_admin()){
			if($sticky){
				$html .= '<br>';
				$html .= '<a href="'.make_link("forum/sticky/unset/".$threadID).'">Un-Sticky</a>';
			}
			else{
				$html .= '<br>';
				$html .= '<a href="'.make_link("forum/sticky/set/".$threadID).'">Sticky</a>';
			}
			
			if($locked){
				$html .= '<br>';
				$html .= '<a href="'.make_link("forum/lock/unset/".$threadID).'">Un-Lock</a>';
			}
			else{
				$html .= '<br>';
				$html .= '<a href="'.make_link("forum/lock/set/".$threadID).'">Lock</a>';
			}
			
			$html .= '<br>';
			$html .= '<a href="'.make_link("forum/nuke/".$threadID).'">Delete</a>';
		}
        
		if(!$user->is_anon()){
        	$page->add_block(new Block("Manage Thread", $html, "left", 10));
		}
    }



    private function make_thread_list($threads, $is_admin){
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
			
			
			$prefix = "";
			if($thread["sticky"] == "Y"){
				$prefix = "Sticky: ";
			}
			
			if($thread["locked"] == "Y"){
				$prefix = "Locked: ";
			}
            
            $html .= "<tr class='$oe'>".
                '<td class="textleft">'.$prefix.'<a href="'.make_link("forum/thread/".$thread["id"]).'">'.$title."</a></td>".
				'<td><a href="'.make_link("account/profile/".$thread["user_name"]).'">'.$thread["user_name"]."</a></td>".
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