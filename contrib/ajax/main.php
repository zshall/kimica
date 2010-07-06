<?php
/*
 * Name: Ajax System
 * Author: Sein Kraft <seinkraft@hotmail.com>
 * License: GPLv2
 * Description: Allows to make ajax calls
 */
class Ajax extends SimpleExtension {
	
	public function onPageRequest(PageRequestEvent $event) {
		global $page, $config, $user;
			
		/*
		*
		* Edit image tags
		*
		*/
		if($event->page_matches("ajax/image/edit")) {
			$image_id = int_escape($_POST['image_id']);
			$tags = $_POST['tags'];
			
			if($this->can_tag($image_id) && !is_null($tags)) {
				$image = Image::by_id($image_id);
				
				if($image){
					send_event(new TagSetEvent($image, $tags));
				
					$page->set_mode("data");
					$page->set_data("new tags: ".$tags);
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
					if (($status == "l") || ($status == "a") || ($status == "p") || ($status == "d")) {
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
			
			$auth = $user->get_auth_from_str($config->get_string("index_mode_admin"));
			if($auth){
				if (($favorite == "set") || ($favorite == "unset")) {
					send_event(new FavoriteSetEvent($image_id, $user, ($favorite == "set")));
					
					$page->set_mode("data");
					$page->set_data("favorite ".$favorite);
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
			
			$auth = $user->get_auth_from_str($config->get_string("index_mode_admin"));
			if($auth){
				if (($rating == "s") || ($rating == "q") || ($rating == "e")) {
					send_event(new RatingSetEvent($image_id, $user, $rating));
					
					$page->set_mode("data");
					$page->set_data("rated as ".$rating);
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
			
			$auth = $user->get_auth_from_str($config->get_string("index_mode_admin"));
			if($auth){
				if (($vote == "up") || ($vote == "down")) {						
					if($vote == "up"){
						$score = 1;
						send_event(new VoteSetEvent($image_id, $user, $score));
					}
					if($vote == "down"){
						$score = -1;
						send_event(new VoteSetEvent($image_id, $user, $score));
					}
					
					$page->set_mode("data");
					$page->set_data("voted ".$vote);
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
			
			$auth = $user->get_auth_from_str($config->get_string("index_mode_admin"));
			if($auth){
				if(isset($_POST['image_id']) && isset($_POST['reason'])) {
					send_event(new AddReportedImageEvent($image_id, $user->id, $_POST['reason']));
					
					$page->set_mode("data");
					$page->set_data("image reported");
				}
			}
		}
		
	}
	
	private function can_tag($image_id) {
		global $config, $user;
		
		$image = Image::by_id($image_id);
		return (($config->get_bool("tag_edit_anon") || !$user->is_anonymous()) && ($user->is_admin() || !$image->is_locked()));
	}
}
?>
