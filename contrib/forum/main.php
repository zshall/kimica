<?php
/**
 * Name: Forum
 * Author: Sein Kraft <seinkraft@hotmail.com>
 *         Alpha <alpha@furries.com.ar>
 * License: GPLv2
 * Description: Rough forum extension
 * Documentation:
 */

class Forum extends SimpleExtension {
        public function onInitExt($event) {
		global $config, $database;

		// shortcut to latest
                
                if ($config->get_int("forum_version") < 1)
                {
                    $database->create_table("forum_threads", "
                            id SCORE_AIPK,
							sticky SCORE_BOOL NOT NULL DEFAULT SCORE_BOOL_N,
                            title VARCHAR(255) NOT NULL,
                            user_id INTEGER NOT NULL,
                            date DATETIME NOT NULL,
							uptodate DATETIME NOT NULL,
                            INDEX (date)
                    ");

                    $database->create_table("forum_posts",
                        " id SCORE_AIPK,
                          thread_id INTEGER NOT NULL,
                          user_id INTEGER NOT NULL,
                          date DATETIME NOT NULL,
                          message TEXT,
                          INDEX (date),
                          FOREIGN KEY (thread_id) REFERENCES forum_threads (id) ON UPDATE CASCADE ON DELETE CASCADE
                    ");

                    $config->set_int("forum_version", 1);
                    $config->set_int("forumTitleSubString", 25);
                    $config->set_int("forumThreadsPerPage", 15);
                    $config->set_int("forumPostsPerPage", 15);
					
					$config->set_int("forumMaxCharsPerPost", 512);
					
                    log_info("forum", "extension installed");
                }
				
				if ($config->get_int("forum_version") < 2){
					$database->create_table("forum_subscription", "
							thread_id INTEGER NOT NULL,
                            user_id INTEGER NOT NULL,
                            INDEX (thread_id)
                    ");
					
					$database->execute("ALTER TABLE forum_threads ADD COLUMN locked SCORE_BOOL NOT NULL DEFAULT SCORE_BOOL_N", array());
					
					$config->set_int("forum_version", 2);
					
					log_info("forum", "database updated");
				}
	}
	
	public function onSetupBuilding(SetupBuildingEvent $event) {
		$sb = new SetupBlock("Forum");
		$sb->add_int_option("forumTitleSubString", "Title max long: ");
		$sb->add_int_option("forumThreadsPerPage", "<br>Threads per page: ");
		$sb->add_int_option("forumPostsPerPage", "<br>Posts per page: ");
		
		$sb->add_int_option("forumMaxCharsPerPost", "<br>Max chars per post: ");
		$event->panel->add_block($sb);
	}
	
	public function onUserPageBuilding($event) {
		global $page, $user, $database;
        $posts_count = $database->db->GetOne("SELECT COUNT(*) FROM forum_posts WHERE user_id=?", array($event->display_user->id));
				
        $event->add_stats(array("Forum Posts", "$posts_count"),70);
	}


	public function onPageRequest($event) {
            global $page, $user;
            
            if($event->page_matches("forum")) {
                switch($event->get_arg(0)) {
                    case "list":
                    {
                        $this->show_last_threads($page, $event, $user->is_admin());
                        if(!$user->is_anon()) $this->theme->display_new_thread_composer($page);
                        break;
                    }
                    case "thread":
                    {
                        $threadID = int_escape($event->get_arg(1));
                        $pageNumber = int_escape($event->get_arg(2));

                        $this->show_posts($event);
						
						$sticky = $this->check_sticky_thread($threadID);
						$locked = $this->check_lock_thread($threadID);
						$subscribed = $this->check_user_subscription($threadID);
						
                        $this->theme->add_actions_block($page, $threadID, $sticky, $locked, $subscribed);
                        if(!$user->is_anon() && !$locked) $this->theme->display_new_post_composer($page, $threadID);
                        break;
                    }
					case "post":
					{
						$postID = int_escape($event->get_arg(1));
						$this->show_post($postID);
						break;
					}
                    case "new":
                    {
						global $page;
                        $this->theme->display_new_thread_composer($page);
                        break;
                    }
                    case "create":
                    {
                        $redirectTo = "forum/list";
                        if (!$user->is_anon())
                        {
                            list($hasErrors, $errors) = $this->valid_values_for_new_thread();

                            if($hasErrors)
                            {
                                $this->theme->display_error("Error", $errors);
                                $this->theme->display_new_thread_composer($page, $_POST["message"], $_POST["title"], false);
                                break;
                            }

                            $newThreadID = $this->save_new_thread($user);
                            $this->save_new_post($newThreadID, $user);
                            $redirectTo = "forum/thread/".$newThreadID."/1";
                        }

                        $page->set_mode("redirect");
                        $page->set_redirect(make_link($redirectTo));

                        break;
                    }
					case "delete":
						$threadID = int_escape($event->get_arg(1));
						$postID = int_escape($event->get_arg(2));

                        if ($user->is_admin()) {$this->delete_post($postID);}

                        $page->set_mode("redirect");
                        $page->set_redirect(make_link("forum/thread/".$threadID."/1"));
                        break;
                    case "nuke":
                        $threadID = int_escape($event->get_arg(1));

                        if ($user->is_admin())
                            $this->delete_thread($threadID);

                        $page->set_mode("redirect");
                        $page->set_redirect(make_link("forum/list"));
                        break;
					case "sticky":
                        $action = $event->get_arg(1);
						$threadID = int_escape($event->get_arg(2));

                        if ($user->is_admin()){
                        	switch($action) {
								case "set":
									$this->sticky_thread($threadID);
								break;
								case "unset":
									$this->unsticky_thread($threadID);
								break;
							}
						}

                        $page->set_mode("redirect");
                        $page->set_redirect(make_link("forum/thread/".$threadID));
					break;
					case "lock":
						$action = $event->get_arg(1);
						$threadID = int_escape($event->get_arg(2));
						
						if ($user->is_admin()){
							switch($action) {
								case "set":
									$this->lock_thread($threadID);
								break;
								case "unset":
									$this->unlock_thread($threadID);
								break;
							}
						}
						
						$page->set_mode("redirect");
                   		$page->set_redirect(make_link("forum/thread/".$threadID."/1"));
					break;
                    case "answer":
						$threadID = int_escape($_POST["threadID"]);
						$locked = $this->check_lock_thread($threadID);
						
                        if(!$user->is_anon() && !$locked){
                            list($hasErrors, $errors) = $this->valid_values_for_new_post();

                            if ($hasErrors)
                            {
                                $this->theme->display_error("Error", $errors);
                                $this->theme->display_new_post_composer($page, $_POST["threadID"], $_POST["message"], $_POST["title"], false);
                                break;
                            }
                            
                            $this->save_new_post($threadID, $user);
							
							$page->set_mode("redirect");
                        	$page->set_redirect(make_link("forum/thread/".$threadID."/1"));
                        }
						
						if($locked){
							$this->theme->display_error("Thread", "This thread is locked.");
						}
					break;
					case "subscription":
						$action = $event->get_arg(1);
						$threadID = int_escape($event->get_arg(2));
						
						switch($action) {
                    		case "create":
								$this->save_new_subscription($threadID);
							break;
							case "delete":
								$this->delete_subscription($threadID);
							break;
							default:
								$page->set_mode("redirect");
                        		$page->set_redirect(make_link("forum/thread/".$threadID."/1"));
							break;
						}
						
						$page->set_mode("redirect");
                        $page->set_redirect(make_link("forum/thread/".$threadID."/1"));						
					break;
					case "report":
						if(!$user->is_anon()) {
							$threadID = int_escape($event->get_arg(1));
							$postID = int_escape($event->get_arg(2));
								
							send_event(new AlertAdditionEvent("Forum", "Reported Post", "", "forum/post/".$postID));
								
							$page->set_mode("redirect");
							$page->set_redirect(make_link("forum/thread/".$threadID."/1"));
						}
					break;
                    default:
						$page->set_mode("redirect");
                        $page->set_redirect(make_link("forum/list"));
					break;
                }
            }
		}

        private function get_total_pages_for_thread($threadID){
            global $database, $config;
            $result = $database->get_row("SELECT COUNT(1) AS count FROM forum_posts WHERE thread_id = ?", array($threadID));

            return ceil($result["count"] / $config->get_int("forumPostsPerPage"));
        }

        private function valid_values_for_new_thread(){
            $hasErrors = false;

            $errors = "";
            
            if (!array_key_exists("title", $_POST))
            {
                $hasErrors = true;
                $errors .= "<div id='error'>No title supplied.</div>";
            }
            else if (strlen($_POST["title"]) == 0)
            {
                $hasErrors = true;
                $errors .= "<div id='error'>You cannot have an empty title.</div>";
            }
            else if (strlen(mysql_real_escape_string(htmlspecialchars($_POST["title"]))) > 255)
            {
                $hasErrors = true;
                $errors .= "<div id='error'>Your title is too long.</div>";
            }

            if (!array_key_exists("message", $_POST))
            {
                $hasErrors = true;
                $errors .= "<div id='error'>No message supplied.</div>";
            }
            else if (strlen($_POST["message"]) == 0)
            {
                $hasErrors = true;
                $errors .= "<div id='error'>You cannot have an empty message.</div>";
            }

            return array($hasErrors, $errors);
        }
		
        private function valid_values_for_new_post(){
            $hasErrors = false;

            $errors = "";
            if (!array_key_exists("threadID", $_POST))
            {
                $hasErrors = true;
                $errors = "<div id='error'>No thread ID supplied.</div>";
            }
            else if (strlen($_POST["threadID"]) == 0)
            {
                $hasErrors = true;
                $errors = "<div id='error'>No thread ID supplied.</div>";
            }
            else if (is_numeric($_POST["threadID"]))

            if (!array_key_exists("message", $_POST))
            {
                $hasErrors = true;
                $errors .= "<div id='error'>No message supplied.</div>";
            }
            else if (strlen($_POST["message"]) == 0)
            {
                $hasErrors = true;
                $errors .= "<div id='error'>You cannot have an empty message.</div>";
            }
            
            return array($hasErrors, $errors);
        }
		
        private function get_thread_title($threadID){
            global $database;
            $result = $database->get_row("SELECT t.title FROM forum_threads AS t WHERE t.id = ? ", array($threadID));
            return $result["title"];
        }
		
        private function show_last_threads(Page $page, $event, $showAdminOptions = false){
			global $config, $database;
			
            $pageNumber = $event->get_arg(1);
			
            if(is_null($pageNumber) || !is_numeric($pageNumber))
                $pageNumber = 0;
            else if ($pageNumber <= 0)
                $pageNumber = 0;
            else
                $pageNumber--;
            
            $threadsPerPage = $config->get_int('forumThreadsPerPage', 15);

            $threads = $database->get_all(
                "SELECT f.id, f.sticky, f.locked, f.title, f.date, f.uptodate, u.name AS user_name, u.email AS user_email, u.role AS user_role, sum(1) - 1 AS response_count ".
                "FROM forum_threads AS f ".
                "INNER JOIN users AS u ".
                "ON f.user_id = u.id ".
                "INNER JOIN forum_posts AS p ".
                "ON p.thread_id = f.id ".
                "GROUP BY f.id, f.sticky, f.title, f.date, u.name, u.email, u.role ".
                "ORDER BY f.sticky ASC, f.uptodate DESC LIMIT ?, ?"
                , array($pageNumber * $threadsPerPage, $threadsPerPage)
            );
			
            $totalPages = ceil($database->db->GetOne("SELECT COUNT(*) FROM forum_threads") / $threadsPerPage);
			
            $this->theme->display_thread_list($page, $threads, $showAdminOptions, $pageNumber + 1, $totalPages);
        }
		
		private function show_posts($event){
			global $config, $database, $user;
			
			$threadID = $event->get_arg(1);
            $pageNumber = $event->get_arg(2);
			
            if(is_null($pageNumber) || !is_numeric($pageNumber))
                $pageNumber = 0;
            else if ($pageNumber <= 0)
                $pageNumber = 0;
            else
                $pageNumber--;
				
            $postsPerPage = $config->get_int('forumPostsPerPage', 15);

            $posts = $database->get_all(
                "SELECT t.title, p.id, p.thread_id, p.date, p.message, u.name as user_name, u.email AS user_email, u.role AS user_role ".
                "FROM forum_posts AS p ".
				"INNER JOIN forum_threads AS t ".
                "ON p.thread_id = t.id ".
                "INNER JOIN users AS u ".
                "ON p.user_id = u.id ".
                "WHERE thread_id = ? ".
				"ORDER BY p.date ASC ".
                "LIMIT ?, ? "
                , array($threadID, $pageNumber * $postsPerPage, $postsPerPage)
            );
			
            $totalPages = ceil($database->db->GetOne("SELECT COUNT(*) FROM forum_posts WHERE thread_id = ?", array($threadID)) / $postsPerPage);
						
			$this->theme->display_thread($posts, $user->is_admin(), $user->is_anon(), $threadID, $pageNumber + 1, $totalPages);
        }
		
		private function show_post($postID){
			global $config, $database, $user;
							
            $posts = $database->get_all(
                "SELECT p.id, p.thread_id, p.date, p.message, u.name as user_name, u.email AS user_email, u.role AS user_role ".
                "FROM forum_posts AS p ".
                "INNER JOIN users AS u ".
                "ON p.user_id = u.id ".
                "WHERE p.id = ? ".
				"ORDER BY p.date ASC "
                , array($postID)
            );
						
			$this->theme->display_post($posts, $user->is_admin(), $user->is_anon());
        }

        private function save_new_thread($user){
            $title = $_POST["title"];
			$message = $_POST["message"];
			$sticky = $_POST["sticky"];
			$subscribe = $_POST["subscribe"];
			
			if($sticky == ""){
				$sticky = "N";
			}
			
            global $database;
            $database->execute("
                INSERT INTO forum_threads
                    (title, sticky, user_id, date, uptodate)
                VALUES
                    (?, ?, ?, now(), now())",
                array($title, $sticky, $user->id));
				
            $result = $database->get_row("SELECT LAST_INSERT_ID() AS threadID", array());
			
			log_info("forum", "Thread {$result["threadID"]} created by {$user->name}");
			
			if($subscribe == "Y"){
				$this->save_new_subscription($result["threadID"]);
			}
									
            return $result["threadID"];
        }
		
		private function save_new_subscription($threadID){
			global $user, $database;
			$database->execute("DELETE FROM forum_subscription WHERE thread_id = ? AND user_id = ?", array($threadID, $user->id));
            $database->execute("INSERT INTO forum_subscription (thread_id, user_id) VALUES (?, ?)", array($threadID, $user->id));
		}
		
		private function check_user_subscription($threadID){
			global $user, $database;
			$subscribed = $database->db->GetOne("SELECT COUNT(*) FROM forum_subscription WHERE thread_id = ? AND user_id = ?", array($threadID, $user->id));
			if($subscribed > 0){
				return true;
			}
			else{
				return false;
			}
		}
		
		private function delete_subscription($threadID){
			global $user, $database;
			$database->execute("DELETE FROM forum_subscription WHERE thread_id = ? AND user_id = ?", array($threadID, $user->id));
		}

        private function save_new_post($threadID, $user){
			global $config;
            $userID = $user->id;
            $message = $_POST["message"];
			
			$max_characters = $config->get_int('forumMaxCharsPerPost');
			$message = substr($message, 0, $max_characters);

            global $database;
            $database->execute("INSERT INTO forum_posts
                    (thread_id, user_id, date, message)
                VALUES
                    (?, ?, now(), ?)"
                , array($threadID, $userID, $message));
				
			$this->check_thread_subscriptions($threadID, $message);
			
			$result = $database->get_row("SELECT LAST_INSERT_ID() AS postID", array());
			
			log_info("forum", "Post {$result["postID"]} created by {$user->name}");
			
			$database->execute("UPDATE forum_threads SET uptodate=now() WHERE id=?", array ($threadID));
        }
		
		
		private function check_thread_subscriptions($threadID, $message){
			global $user, $database;
			$subscriptions = $database->get_all("SELECT * FROM forum_subscription WHERE thread_id = ?", array($threadID));
			
			$threadLink = "<a href='".make_http(make_link("forum/thread/".$threadID))."'>".$this->get_thread_title($threadID)."</a>";
			
			foreach($subscriptions as $subscription){
				$duser = User::by_id($subscription["user_id"]);
				
				$email = new Email($duser->email, "New Forum Post", "New Forum Post", $user->name." has updated the thread ".$threadLink.".<br><br><b>".$user->name." has posted:</b><br>".$message);
				if($duser->id != $user->id){
					$email->send();
					log_info("forum", "Subscription mail sent to {$user->name} for the thread {$threadID}");
				}
			}
		}

        private function retrieve_posts($threadID, $pageNumber){
            global $database, $config;
            $postsPerPage = $config->get_int('forumPostsPerPage', 15);

            return $database->get_all(
                "SELECT p.id, p.date, p.message, u.name as user_name, u.email AS user_email, u.role AS user_role ".
                "FROM forum_posts AS p ".
                "INNER JOIN users AS u ".
                "ON p.user_id = u.id ".
                "WHERE thread_id = ? ".
				"ORDER BY p.date ASC ".
                "LIMIT ?, ? "
                , array($threadID, ($pageNumber - 1) * $postsPerPage, $postsPerPage));
        }

        private function delete_thread($threadID){
            global $database, $user;
            $database->execute("DELETE FROM forum_threads WHERE id = ?", array($threadID));
			$database->execute("DELETE FROM forum_posts WHERE thread_id = ?", array($threadID));
			$database->execute("DELETE FROM forum_subscription WHERE thread_id = ? AND user_id = ?", array($threadID, $user->id));
        }
		
		private function check_sticky_thread($threadID){
			global $database;
			$sticky = $database->db->GetOne("SELECT COUNT(*) FROM forum_threads WHERE id = ? AND sticky = 'Y'", array($threadID));
			if($sticky > 0){
				return true;
			}
			else{
				return false;
			}
		}
		
		private function sticky_thread($threadID){
			global $database;
            $database->execute("UPDATE forum_threads SET sticky = 'Y' WHERE id = ?", array($threadID));
		}
		
		private function unsticky_thread($threadID){
			global $database;
            $database->execute("UPDATE forum_threads SET sticky = 'N' WHERE id = ?", array($threadID));
		}
		
		private function check_lock_thread($threadID){
			global $database;
			$sticky = $database->db->GetOne("SELECT COUNT(*) FROM forum_threads WHERE id = ? AND locked = 'Y'", array($threadID));
			if($sticky > 0){
				return true;
			}
			else{
				return false;
			}
		}
		
		private function lock_thread($threadID){
			global $database;
            $database->execute("UPDATE forum_threads SET locked = 'Y' WHERE id = ?", array($threadID));
		}
		
		private function unlock_thread($threadID){
			global $database;
            $database->execute("UPDATE forum_threads SET locked = 'N' WHERE id = ?", array($threadID));
		}
		
		private function delete_post($postID){
            global $database;
            $database->execute("DELETE FROM forum_posts WHERE id = ?", array($postID));
        }
}
?>