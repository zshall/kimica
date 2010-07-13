<?php
/*
 * Name: Simple Journals
 * Author: Sein Kraft <mail@seinkraft.info>
 * License: GPLv2
 * Description: Allow users to create journals
 * Documentation:
 */
class Journals extends SimpleExtension {

public function onInitExt($event) {
	global $config, $database;
                
	if ($config->get_int("ext_journals_version") < 1){
			
		$database->create_table("journals", "
					id SCORE_AIPK,
					user_id INTEGER NOT NULL,
					disable SCORE_BOOL NOT NULL DEFAULT SCORE_BOOL_N,
					title VARCHAR(255) NOT NULL,
					message TEXT,
					comments INTEGER NOT NULL,
					posted DATETIME DEFAULT NULL,
					listening  VARCHAR(255),
					reading  VARCHAR(255),
					watching  VARCHAR(255),
					playing  VARCHAR(255),
					eating  VARCHAR(255),
					drinking  VARCHAR(255),
					INDEX (posted),
					FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
			");
			
		$database->create_table("journal_comments", "
					id SCORE_AIPK,
					journal_id INTEGER NOT NULL,
					user_id INTEGER NOT NULL,
					user_ip SCORE_INET NOT NULL,
					comment TEXT NOT NULL,
					posted DATETIME DEFAULT NULL,
					INDEX (posted),
					FOREIGN KEY (journal_id) REFERENCES journals(id) ON DELETE CASCADE
			");
		
		$config->set_int("journalsTitleSubString", 25);
		$config->set_int("journalsPerPage", 15);
		$config->set_int("journalsCommentsPerPage", 15);
					
		$config->set_int("ext_journals_version", 1);
		
		log_info("journals", "extension installed");
	}
}

public function onPageRequest($event) {
	global $page, $user;
            
	if($event->page_matches("journals")) {
		switch($event->get_arg(0)) {
			case "list":
			{
				$pageNumber = int_escape($event->get_arg(1));
				
				$this->viewAllJournals($pageNumber);
				break;
			}
			case "user":
			{
				$userNAME = html_escape($event->get_arg(1));
				$pageNumber = int_escape($event->get_arg(2));
				
				$this->viewUserJournals($userNAME, $pageNumber);
				break;
			}
			case "view":
			{
				$journalID = int_escape($event->get_arg(1));
				$pageNumber = int_escape($event->get_arg(2));
				
				$this->viewJournal($journalID);
				$this->viewComments($journalID, $pageNumber);
				break;
			}
			case "new":
			{
				if(!$user->is_anon()){
					$this->theme->displayNewForm();
				}else{
					$page->set_mode("redirect");
					$page->set_redirect(make_link("journals/list"));
				}
				break;
			}
			case "post_new":
			{
				$page->set_mode("redirect");
                $page->set_redirect(make_link("journals/new"));
				break;
			}
			case "create":
			{
				list($error, $warnings) = $this->canAddJournal();

				if ($error)
				{
					$this->theme->display_error($page, "Error", $warnings);
					$this->theme->displayNewForm();
					break;
				}
				
				$journalID = $this->addJournal();
				$page->set_mode("redirect");
				$page->set_redirect(make_link("journals/view/".$journalID));
				break;
			}
			case "edit":
			{
				$journalID = int_escape($event->get_arg(1));
				
				if(!$user->is_anon()){
					$this->editJournal($journalID);
				}else{
					$page->set_mode("redirect");
					$page->set_redirect(make_link("journals/view/".$journalID));
				}
				break;
			}
			case "update":
			{
				$journalID = int_escape($event->get_arg(1));						
						
				list($error, $warnings) = $this->canAddJournal();

				if ($error)
				{
					$this->theme->displayEditForm($journal);
					$this->theme->display_error($page, "Error", $warnings);
					break;
				}
				
				$JournalEDITED = $this->updateJournal($journalID);
				
				$page->set_mode("redirect");
				$page->set_redirect(make_link("journals/view/".$JournalEDITED));
				break;
			}
			case "post_edit":
			{
				$journalID = int_escape($_POST["journal_id"]);
				$page->set_mode("redirect");
                $page->set_redirect(make_link("journals/edit/".$journalID));
				break;
			}
			case "delete":
			{
				$journalID = int_escape($event->get_arg(1));
				$this->deleteJournal($journalID);
				
				$page->set_mode("redirect");
				$page->set_redirect(make_link("journals/list"));
				break;
			}
			case "post_delete":
			{
				$journalID = int_escape($_POST["journal_id"]);
				$page->set_mode("redirect");
                $page->set_redirect(make_link("journals/delete/".$journalID));
				break;
			}
			case "comment":
			{
				$journalID = int_escape($_POST["journal_id"]);
				
				list($error, $warnings) = $this->canAddComment();

				if ($error)
				{
					$this->viewJournal($journalID);
					$this->viewComments($journalID, 1);
					
					$this->theme->display_error($page, "Error", $warnings);
					break;
				}
				
				$journalID = $this->addComment();
				$page->set_mode("redirect");
				$page->set_redirect(make_link("journals/view/".$journalID));
				break;
			}
			default:
			{
			$page->set_mode("redirect");
			$page->set_redirect(make_link("journals/list"));
            break;
            }
		}
	}
}


public function onUserPageBuilding($event) {
	global $page, $user, $database;
		
	$journals_count = $database->db->GetOne("SELECT COUNT(*) FROM journals WHERE user_id=?", array($event->display_user->id));
			
	$days_old = ((time() - strtotime($event->display_user->join_date)) / 86400) + 1;
				
	$journals_rate = sprintf("%.1f", ($journals_count / $days_old));		
	$event->add_stats("<a href='".make_link('journals/user/'.$event->display_user->name)."'>Journals</a>: $journals_count, $journals_rate per day");
}


private function canAddJournal(){
	global $user;
	
	$title = $_POST['title'];
	$message = $_POST['message'];
	
	$error = FALSE;
	$warnings = "";
	
	if($user->is_anon()){
		$error = TRUE;
		$warnings .= "You must be logged in.<br>";
	}
	
	if(trim($title) == ""){
		$error = TRUE;
		$warnings .= "Title need text.<br>";
	}elseif(strlen($title) > 255){
		$error = TRUE;
		$warnings .= "Title too long.<br>";
	}
	
	if(trim($message) == ""){
		$error = TRUE;
		$warnings .= "Message needs text.<br>";
	}elseif(strlen($message) > 9000){
		$error = TRUE;
		$warnings .= "Message too long.<br>";
	}elseif(strlen($message)/strlen(gzcompress($message)) > 10){
		$error = TRUE;
		$warnings .= "Message too repetitive.<br>";
	}
	
	return array($error, $warnings);
}



/*
* HERE WE ADD A JOURNAL TO DATABASE
*/
private function addJournal(){
	global $user, $database;
	
	$title = $_POST['title'];
	$message = $_POST['message'];
	$disable = html_escape($_POST['disable_comments']);
	
	$mood = $_POST['mood'];
	$listening = $_POST['listening'];
	$reading = $_POST['reading'];
	$watching = $_POST['watching'];
	$playing = $_POST['playing'];
	$eating = $_POST['eating'];
	$drinking = $_POST['drinking'];
			
	if($disable == ""){
		$disable = "N";
	}
	
	if (!$user->is_anon()){
		$database->execute("
					INSERT INTO journals
						(user_id, disable, title, message, posted, mood, listening, reading, watching, playing, eating, drinking)
					VALUES
						(?, ?, ?, ?, now(), ?, ?, ?, ?, ?, ?, ?)",
					array($user->id, $disable, $title, $message, $mood, $listening, $reading, $watching, $playing, $eating, $drinking));
					
		$result = $database->get_row("SELECT LAST_INSERT_ID() AS journalID");
	}
	//log_info("forum", "Thread {$result["threadID"]} created by {$user->name}");	
	return $result["journalID"];
	
}



/*
* HERE WE VIEW THE JOURNAL
*/
private function viewJournal($journalID){
	global $user, $database;
		
	$journal = $database->get_row("SELECT j.id, j.title, j.message, j.disable, j.posted, j.mood, j.listening, j.reading, j.watching, j.playing, j.eating, j.drinking, u.id as user_id, u.name as user_name ".
								  "FROM journals AS j ".
								  "INNER JOIN users AS u ".
								  "ON j.user_id = u.id ".
								  "WHERE j.id = ?"
								  ,array($journalID));
	
	$this->theme->displayJournal($journal);
	
	$info['user'] = $journal['user_name'];
	$info['mood'] = $journal['mood'];
	$info['listening'] = $journal['listening'];
	$info['reading'] = $journal['reading'];
	$info['watching'] = $journal['watching'];
	$info['playing'] = $journal['playing'];
	$info['eating'] = $journal['eating'];
	$info['drinking'] = $journal['drinking'];
	
	$this->theme->sidebar_profile($info);
	
	if (!$user->is_anon()){
		$this->theme->sidebar_options($journal['id'], $this->isOwner($journal['user_id']));
	}
}



/*
* WE DISPLAY THE JOURNAL EDITOR
*/
private function editJournal($journalID){
	global $user, $database, $page;
	
	$journal = $database->get_row("SELECT * FROM journals WHERE id = ? ",array($journalID));
	
	if($this->isOwner($journal['user_id'])){
		$this->theme->displayEditForm($journal);
	}else{
		$page->set_mode("redirect");
		$page->set_redirect(make_link("journals/view/".$journalID));
	}
}



/*
* WE UPDATE THE JOURNAL
*/
private function updateJournal($journalID){
	global $database;
	
	$poster = $database->get_row("SELECT user_id FROM journals WHERE id = ? ",array($journalID));
	
	global $user, $database;
	
	$title = $_POST['title'];
	$message = $_POST['message'];
	$disable = html_escape($_POST['disable_comments']);
	
	$mood = $_POST['mood'];
	$listening = $_POST['listening'];
	$reading = $_POST['reading'];
	$watching = $_POST['watching'];
	$playing = $_POST['playing'];
	$eating = $_POST['eating'];
	$drinking = $_POST['drinking'];
			
	if($disable == ""){
		$disable = "N";
	}

	if($this->isOwner($poster['user_id'])){
		$database->execute("UPDATE journals SET disable = ?, title = ?, message = ?, mood = ?, listening = ?, reading = ?, watching = ?, playing = ?, eating = ?, drinking = ? WHERE id=?", array($disable, $title, $message, $mood, $listening, $reading, $watching, $playing, $eating, $drinking, $journalID));
	}
	//log_info("forum", "Thread {$result["threadID"]} created by {$user->name}");	
	return $journalID;
}


/*
* WE DELETE THE JOURNAL
*/
private function deleteJournal($journalID){
	global $database;
	
	$poster = $database->get_row("SELECT * FROM journals WHERE id = ? ",array($journalID));
	
	if($this->isOwner($poster['user_id'])){
		$database->execute("DELETE FROM journals WHERE id = ?", array($journalID));
	}
}



private function canAddComment(){
	global $user, $database;
	
	$journalID = int_escape($_POST['journal_id']);
	
	$comment = $_POST['comment'];
	$journal = $database->get_row("SELECT * FROM journals WHERE id = ? ",array($journalID));
	
	$error = FALSE;
	$warnings = "";
	
	if($user->is_anon()){
		$error = TRUE;
		$warnings .= "You must be logged in.<br>";
	}
	
	if($journal['disable'] == "Y"){
		$error = TRUE;
		$warnings .= "Comments are dissabled.<br>";
	}elseif(trim($comment) == ""){
		$error = TRUE;
		$warnings .= "Comment needs text.<br>";
	}elseif(strlen($comment) > 9000){
		$error = TRUE;
		$warnings .= "Comment too long.<br>";
	}elseif(strlen($comment)/strlen(gzcompress($comment)) > 10){
		$error = TRUE;
		$warnings .= "Comment too repetitive.<br>";
	}
	
	return array($error, $warnings);
}



/*
* HERE WE ADD A COMMENT TO JOURNAL
*/
private function addComment(){
	global $user, $database;
		
	$journalID = int_escape($_POST['journal_id']);
	$userID = int_escape($user->id); 
	$comment = $_POST['comment'];
	
	$journal = $database->get_row("SELECT * FROM journals WHERE id = ? ",array($journalID));
	
	//if the journal allow new comments the add the comment
	if($journal['disable'] == 'N') {
		$database->execute("
					INSERT INTO journal_comments
						(journal_id, user_id, user_ip, comment, posted)
					VALUES
						(?, ?, ?, ?, now())",
					array($journalID, $userID, $_SERVER['REMOTE_ADDR'], $comment));
					
		$database->execute("UPDATE journals SET comments=(SELECT COUNT(*) FROM journal_comments WHERE journal_id=?) WHERE id=?", array($journalID, $journalID));
	}
					
	return $journalID;
	
}



/*
* HERE WE VIEW THE COMMENTS FOR THE JOURNAL
*/
private function viewComments($journalID, $pageNumber){
	global $config, $database;
	
	if(is_null($pageNumber) || !is_numeric($pageNumber))
		$pageNumber = 0;
	else if ($pageNumber <= 0)
		$pageNumber = 0;
	else
		$pageNumber--;
		
	$commentsPerPage = $config->get_int('journalsCommentsPerPage', 15);
	
	$comments = $database->get_all(
                "SELECT c.id, c.journal_id, c.user_ip, c.comment, c.posted, u.name as user_name, u.email AS user_email ".
                "FROM journal_comments AS c ".
                "INNER JOIN users AS u ".
                "ON c.user_id = u.id ".
				"WHERE journal_id = ? ".
				"ORDER BY c.posted ASC ".
				"LIMIT ?, ? "
                , array($journalID, $pageNumber * $commentsPerPage, $commentsPerPage)
            );
			
	$totalPages = ceil($database->db->GetOne("SELECT COUNT(*) FROM journal_comments WHERE journal_id = ?", array($journalID)) / $commentsPerPage);
	
	$this->theme->displayComments($comments, $journalID, $pageNumber + 1, $totalPages);
}



/*
* 
*/
private function viewAllJournals($pageNumber){
	global $config, $database;
	
	if(is_null($pageNumber) || !is_numeric($pageNumber))
		$pageNumber = 0;
	else if ($pageNumber <= 0)
		$pageNumber = 0;
	else
		$pageNumber--;
		
	$journalsPerPage = $config->get_int('journalsPostsPerPage', 15);
		
	$journals = $database->get_all(
                "SELECT j.id, j.title, j.comments, j.posted, u.name as user_name ".
                "FROM journals AS j ".
                "INNER JOIN users AS u ".
                "ON j.user_id = u.id ".
				"ORDER BY j.posted DESC ".
                "LIMIT ?, ? "
                , array($pageNumber * $journalsPerPage, $journalsPerPage)
            );
			
	$totalPages = ceil($database->db->GetOne("SELECT COUNT(*) FROM journals") / $journalsPerPage);
	
	$this->theme->displayAllJournals($journals, $pageNumber + 1, $totalPages);
}


/*
* 
*/
private function viewUserJournals($userNAME, $pageNumber){
	global $config, $database;
	
	if(is_null($pageNumber) || !is_numeric($pageNumber))
		$pageNumber = 0;
	else if ($pageNumber <= 0)
		$pageNumber = 0;
	else
		$pageNumber--;
		
	$journalsPerPage = $config->get_int('journalsPostsPerPage', 15);
	
	$poster = User::by_name($userNAME);
	
	$journals = $database->get_all(
                "SELECT j.id, j.disable, j.title, j.message, j.comments, j.posted, u.name as user_name, u.email AS user_email ".
                "FROM journals AS j ".
                "INNER JOIN users AS u ".
                "ON j.user_id = u.id ".
				"WHERE user_id = ? ".
				"ORDER BY j.posted DESC ".
                "LIMIT ?, ? "
                , array($poster->id, $pageNumber * $journalsPerPage, $journalsPerPage)
            );
			
	$totalPages = ceil($database->db->GetOne("SELECT COUNT(*) FROM journals WHERE user_id = ?", array($poster->id)) / $journalsPerPage);
	
	$this->theme->displayUserJournals($journals, $userNAME, $this->isOwner($poster->id), $pageNumber + 1, $totalPages);
}

private function commentChecker(){
}

private function isOwner($posterID){
	global $user;
			
	if($posterID == $user->id || $user->is_admin()) {
		return TRUE;
	}else{
		return FALSE;
	}
}

}
?>
