<?php
/*
 * Name: Ajax System
 * Author: Sein Kraft <seinkraft@hotmail.com>
 * License: GPLv2
 * Description: Allows to make ajax calls
 */
class Ajax extends SimpleExtension {
	
	public function onPageRequest(PageRequestEvent $event) {
		global $page, $database, $config, $user;
		
		if($event->page_matches("post/list")) {
			$this->theme->images_control($page);
		}
		
				
		/*
		*
		* Edit image tags
		*
		*/
		if($event->page_matches("ajax/image/info")) {
			$image_id = int_escape($_POST['image_id']);
			
			if(isset($image_id)){
				$image = Image::by_id($image_id);
				
				if($image){				
					$image_info = '{"id":"'.$image->id.'","tags":"'.$image->get_tag_list().'","source":"'.$image->source.'","height":"'.$image->height.'","width":"'.$image->width.'","thumb":"'.$image->get_thumb_link().'","tooltip":"'.$image->get_tooltip().'"}';
					
					$page->set_mode("data");
					$page->set_type("application/json");
					$page->set_data($image_info);
				}
			}
		}
		
		
		
		/*
		*
		* Edit image tags
		*
		*/
		if($event->page_matches("ajax/image/next")) {
			$image_id = int_escape($_POST['image_id']);
			
			if(isset($image_id) && ($image_id > 1)){
				$new_id = $database->get_row("SELECT id FROM images WHERE id < ? ORDER BY id DESC LIMIT 1",array($image_id));
				$new_image = Image::by_id($new_id["id"]);
				
				$tsize = get_thumbnail_size($new_image->width, $new_image->height);
				
				if($new_image){				
					$image_info = '{"id":"'.$new_image->id.'","tags":"'.$new_image->get_tag_list().'","source":"'.$new_image->source.'","height":"'.$new_image->height.'","width":"'.$new_image->width.'","thumb":"'.$new_image->get_thumb_link().'","thumb_height":"'.$tsize[1].'","thumb_width":"'.$tsize[0].'","tooltip":"'.$new_image->get_tooltip().'"}';
					
					$page->set_mode("data");
					$page->set_type("application/json");
					$page->set_data($image_info);
				}
			}
			else{
				$page->set_mode("data");
				$page->set_data("error");
			}
		}
		
		
		
		
		
		/*
		*
		* Edit image tags
		*
		*/
		if($event->page_matches("ajax/image/prev")) {
			$image_id = int_escape($_POST['image_id']);
			
			if(isset($image_id)){
				$new_id = $database->get_row("SELECT id FROM images WHERE id > ? ORDER BY id ASC LIMIT 1",array($image_id));
				$new_image = Image::by_id($new_id["id"]);
				
				$tsize = get_thumbnail_size($new_image->width, $new_image->height);
				
				if($new_image){				
					$image_info = '{"id":"'.$new_image->id.'","tags":"'.$new_image->get_tag_list().'","source":"'.$new_image->source.'","height":"'.$new_image->height.'","width":"'.$new_image->width.'","thumb":"'.$new_image->get_thumb_link().'","thumb_height":"'.$tsize[1].'","thumb_width":"'.$tsize[0].'","tooltip":"'.$new_image->get_tooltip().'"}';
					
					$page->set_mode("data");
					$page->set_type("application/json");
					$page->set_data($image_info);
				}
			}
		}
		
		
		
		/*
		*
		* Edit image tags
		*
		*/
		if($event->page_matches("ajax/image/edit")) {
			$image_id = int_escape($_POST['image_id']);
			$tags = $_POST['tags'];
			
			$auth = $user->get_auth_from_str($config->get_string("index_mode_general"));
				
			if($auth){
				$image = Image::by_id($image_id);
					
				if($image){
					if($this->can_tag($image_id) && !is_null($tags)) {
						send_event(new TagSetEvent($image, $tags));
					
						$page->set_mode("data");
						$page->set_data("new tags: ".$tags);
					}
				}
			}
		}
		
		
		
		/*
		*
		* Add image report
		*
		*/
		if($event->page_matches("ajax/image/report")) {
			$image_id = int_escape($_POST['image_id']);
			
			$auth = $user->get_auth_from_str($config->get_string("index_mode_general"));
			if($auth){
				if(isset($_POST['image_id']) && isset($_POST['reason'])) {
					send_event(new AddReportedImageEvent($image_id, $user->id, $_POST['reason']));
					
					$page->set_mode("data");
					$page->set_data("image reported");
				}
			}
		}
		
		
		
		/*
		*
		* Add image report
		*
		*/
		if($event->page_matches("ajax/image/delete")) {
			$image_id = int_escape($_POST['image_id']);
			
			$auth = $user->get_auth_from_str("oa");
			
			if($auth){
				$image = Image::by_id($image_id);
				
				if($image){
					send_event(new ImageDeletionEvent($image));
					
					$page->set_mode("data");
					$page->set_data("image deleted");
				}
			}
		}
		
		
		
		
		/*
		*
		* Add image report
		*
		*/
		if($event->page_matches("ajax/image/ban")) {
			$image_id = int_escape($_POST['image_id']);
			$reason = $_POST['reason'];
			
			$auth = $user->get_auth_from_str("oa");
			
			if($auth){
				$image = Image::by_id($image_id);
				
				if($image){
					send_event(new AddImageHashBanEvent($image->hash, $reason));
					send_event(new ImageDeletionEvent($image));
										
					$page->set_mode("data");
					$page->set_data("image banned");
				}
			}
		}
		
		
		
		/*
		*
		* Edit image status
		*
		*/
		if($event->page_matches("ajax/image/status")) {
			$image_id = int_escape($_POST['image_id']);
			$status = html_escape($_POST['status']);
			
			$auth = $user->get_auth_from_str($config->get_string("index_mode_admin"));
			
			if($auth){
				$image = Image::by_id($image_id);
				
				if($image){
					if (($status == "l") || ($status == "a") || ($status == "p") || ($status == "d") || ($status == "h")) {
						$image->set_status($status);
						
						$page->set_mode("data");
						$page->set_data("image ".$image->status_to_human());
					}
				}
			}
		}
		
		
		
		/*
		*
		* Edit user favorites
		*
		*/
		if($event->page_matches("ajax/image/favorite") && class_exists("Favorites")) {
			$image_id = int_escape($_POST['image_id']);
			$favorite = html_escape($_POST['favorite']);
			
			$auth = $user->get_auth_from_str($config->get_string("index_mode_favorites"));
			if($auth){
				$image = Image::by_id($image_id);
				
				if($image){
					if (($favorite == "set") || ($favorite == "unset")) {
						send_event(new FavoriteSetEvent($image_id, $user, ($favorite == "set")));
						
						$page->set_mode("data");
						$page->set_data("favorite ".$favorite);
					}
				}
			}
		}
		
		
		
		/*
		*
		* Set image rate
		*
		*/
		if($event->page_matches("ajax/image/rate") && class_exists("Ratings")) {
			$image_id = int_escape($_POST['image_id']);
			$rating = html_escape($_POST['rating']);
			
			$auth = $user->get_auth_from_str($config->get_string("index_mode_rating"));
			if($auth){
				$image = Image::by_id($image_id);
				
				if($image){
					if (($rating == "s") || ($rating == "q") || ($rating == "e")) {
						send_event(new RatingSetEvent($image, $user, $rating));
						
						$page->set_mode("data");
						$page->set_data("rated as ".Ratings::rating_to_human($rating));
					}
				}
			}
		}
		
		
		
		/*
		*
		* Set image vote
		*
		*/
		if($event->page_matches("ajax/image/vote") && class_exists("Votes")) {
			$image_id = int_escape($_POST['image_id']);
			$vote = html_escape($_POST['vote']);
			
			$auth = $user->get_auth_from_str($config->get_string("index_mode_score"));
			if($auth){
				if (($vote == "up") || ($vote == "null") || ($vote == "down")) {						
					if($vote == "up"){
						$score = 1;
						send_event(new VoteSetEvent($image_id, $user, $score));
					}
					if($vote == "down"){
						$score = -1;
						send_event(new VoteSetEvent($image_id, $user, $score));
					}
					if($vote == "null"){
						$score = 0;
						send_event(new VoteSetEvent($image_id, $user, $score));
					}
					
					$page->set_mode("data");
					$page->set_data("voted ".$vote);
				}
			}
		}
		
		
		
		/*
		*
		* Add comment report
		*
		*/
		if($event->page_matches("ajax/comment/add") && class_exists("Comment")) {
			$image_id = int_escape($_POST['image_id']);
			$comment = $_POST['comment'];
			
			if(isset($_POST['image_id']) && isset($_POST['comment'])) {
				send_event(new CommentPostingEvent($image_id, $user, $comment));
					
				$page->set_mode("data");
				$page->set_data("comment made");
			}
		}
		
		
		/*
		*
		* Add comment report
		*
		*/
		if($event->page_matches("ajax/comment/remove") && class_exists("Comment")) {
			$comment_id = int_escape($_POST['comment_id']);
			
			if(isset($_POST['comment_id'])) {
				send_event(new CommentDeletionEvent($comment_id));
					
				$page->set_mode("data");
				$page->set_data("comment removed");
			}
		}
		
		
		
		/*
		*
		* Add comment report
		*
		*/
		if($event->page_matches("ajax/comment/vote") && class_exists("Comment")) {
			$comment_id = int_escape($_POST['comment_id']);
			$vote = $_POST['vote'];
			
			if(isset($_POST['comment_id']) && isset($_POST['vote'])) {
				send_event(new CommentVoteEvent($comment_id, $user, $vote));
					
				$page->set_mode("data");
				$page->set_data("comment voted");
			}
		}
	}
	
	private function can_tag($image_id) {
		global $config, $user;
		
		$image = Image::by_id($image_id);
		return (($config->get_bool("tag_edit_anon") || !$user->is_anon()) && ($user->is_admin() || !$image->is_locked()));
	}
}
?>
