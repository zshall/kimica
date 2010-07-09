<?php

class AjaxTheme extends Themelet {

	public function images_control(Page $page){
		global $config, $user;
		$html = "<form action=''>
					<select onchange='PostModeMenu();' id='mode' name='mode'>";
					
		$option_delete = "";
		if($user->is_owner() || $user->is_admin()){
			$option_delete = "<option value='delete'>Delete posts</option>";
			$option_ban = "<option value='ban'>Ban posts</option>";
		}
		
		if($user->get_auth_from_str($config->get_string("index_mode_general"))){
			$html .= "<optgroup label='General'>
							<option value='view'>View posts</option>
							<option value='edit'>Edit posts</option>
							<option value='report'>Report posts</option>
							$option_delete
							$option_ban
							</optgroup>";
		}
		
		if($user->get_auth_from_str($config->get_string("index_mode_admin"))){
			$html .= "<optgroup label='Admin'>
							<option value='admin-approved'>Set approved</option>
							<option value='admin-locked'>Set locked</option>
							<option value='admin-pending'>Set pending</option>
							<option value='admin-deleted'>Set deleted</option>
							</optgroup>";
		}
		
		if($user->get_auth_from_str($config->get_string("index_mode_favorites")) && class_exists("Favorites")){
			$html .= "<optgroup label='Favorites'>
							<option value='add-fav'>Add to favorites</option>
							<option value='remove-fav'>Remove favorites</option>
							</optgroup>";
		}
		
		if($user->get_auth_from_str($config->get_string("index_mode_score")) && class_exists("Votes")){
			$html .= "<optgroup label='Votes'>    
							<option value='vote-up'>Vote up</option>     
							<option value='vote-down'>Vote down</option>
							</optgroup>";
		}
						
		if($user->get_auth_from_str($config->get_string("index_mode_rating")) && class_exists("Ratings")){
			$html .= "<optgroup label='Rating'> 
							<option value='rate-safe'>Rate safe</option>      
							<option value='rate-questionable'>Rate questionable</option>
							<option value='rate-explicit'>Rate explicit</option>   
							</optgroup>";
		}
						
		$html .= "</select>
				</form>";
		$page->add_block(new Block("Image Controls", $html, "left", 10));
	}
}
?>