<?php
/*
 * Name: Ads System
 * Author: Sein Kraft <seinkraft@hotmail.com>
 * License: GPLv2
 * Description: Allows to manage ads on the system and display them on diferent sections and modules.
 * Documentation: This extension alows to create and displays ads on the system.<br><br><b>Config:</b> On the board config you can set to who are displayed the ads, by default them will be visible to everybody banned, guests, users, contributors, moderators, admins and owners. Also the admin can limit them to display many banners as the admin wish. Allows to display them randomly and to set the url of the close button if the theme alows them. The close link could be usefull if the admin want to ask for donations to redirect the click on it to another page.<br><br><b>Location:</b> The location set where the ads will be displayed. By defualt the blocs are left (sidebar) and main (main content) but the admin could set more places editing themelet.class.php.<br><br><b>Position:</b> The position asigns whene the ad will be displayed. For example if is displayed in the sidebar, the position 0 will set the ad in the first block, if the position is 100 or more it will be the last block in the sidebar.<br><br><b>Priority:</b> Its possible to set priority to the ads in the case of personal ads or buyed ads.<br><br><b>Section:</b> A section is a system section. For example if you set a ad to be displayed in post module, the ad will be displayed on post/list, post/view, post/popular, etc. To display an ad on every section the section should be "*" without quotes.<br><br><b>Note:</b> Only the ads generated with url and image could ctrack the clicks. Those ads who only used the html section could not be tracked, only the impression nothing more, they are thinked to ads wich uses iframes or javascript.<br><br>
 */
class Ads extends SimpleExtension {
	public function onInitExt($event) {
		global $config, $database;
            
		if($config->get_int("ext_ads_version") < 1) {
			$database->create_table("ads", "
									id SCORE_AIPK,
									prints INTEGER NOT NULL DEFAULT '0',
									clicks INTEGER NOT NULL DEFAULT '0',
									until_prints INTEGER NOT NULL DEFAULT '0',
									location VARCHAR(64) NOT NULL,
									position INTEGER NOT NULL DEFAULT '0',
									priority INTEGER NOT NULL DEFAULT '0',
									section VARCHAR(64) NOT NULL,
									rating ENUM('s', 'q', 'e') NOT NULL DEFAULT 'q',
									advertirser VARCHAR(128) NOT NULL,
									url VARCHAR(256) NOT NULL,
									image VARCHAR(256) NOT NULL,
									html TEXT NOT NULL,
									INDEX (id),
									INDEX (section),
									INDEX (rating)
									
			");
					
			$config->set_int("ext_ads_version", 1);
			$config->set_string("ads_visible_to", "bgucmao");
			$config->set_int("ads_limited_to", "1");
			$config->set_bool("ads_randomized", "N");
			$config->set_string("ads_close_url", "");
			
			log_info("ads", "ads system intalled");	
		}
	}
	
	public function onSetupBuilding(SetupBuildingEvent $event) {
		$sb = new SetupBlock("Ads");
		$sb->add_text_option("ads_visible_to", "Ads visible to: ");
		$sb->add_int_option("ads_limited_to", "<br>Max ads per page: ");
		$sb->add_bool_option("ads_randomized", "<br>Randomize ads: ");
		$sb->add_text_option("ads_close_url", "<br>Ads close url: ");
		
		$event->panel->add_block($sb);
	}
	
	public function onPageRequest($event){
		global $page, $user;
		
		$this->get_ads_section($event->get_arg(-1));
            
		if($event->page_matches("ads/list")) {
			if($user->is_admin()){
				$this->theme->list_ads($this->list_ads());
			}
		}
		
		if($event->page_matches("ads/create")) {
			if($user->is_admin()){
				$this->theme->add_ad();
			}
		}
				
		if($event->page_matches("ads/add")) {
			if($user->is_admin()){
				$this->add_ad();
			}
			
			$page->set_mode("redirect");
			$page->set_redirect(make_link("ads/list"));
		}
		
		if($event->page_matches("ads/remove")) {
			if($user->is_admin()){
				$id = int_escape($event->get_arg(0));
				$this->remove_ad($id);
			}
			
			$page->set_mode("redirect");
			$page->set_redirect(make_link("ads/list"));
		}
		
		if($event->page_matches("ads/redirect")) {
			$id = int_escape($event->get_arg(0));
			$this->add_click($id);
			
			$page->set_mode("redirect");
			$page->set_redirect($this->get_ad_link($id));
		}
	}
	
	public function list_ads(){
		global $database;
		
		return $database->get_all("SELECT * FROM ads");
	}
	
	public function get_ad_link($id){
		global $database;
		
		$ad = $database->get_row("SELECT url FROM ads WHERE id = ?", array($id));
		return $ad['url'];
	}
	
	public function add_ad(){
		global $database;
		
		$prints = int_escape($_POST["until_prints"]);
		$location = $_POST["location"];
		$position = int_escape($_POST["position"]);
		$priority = int_escape($_POST["priority"]);
		$section = $_POST["section"];
		$rating = $_POST["rating"];
		$advertirser = html_escape($_POST["advertirser"]);
		$url = $_POST["url"];
		$image = $_POST["image"];
		$html = $_POST["html"];
		
		$database->execute("
                INSERT INTO ads
                    (until_prints, location, position, priority, section, rating, advertirser, url, image, html)
                VALUES
                    (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                array($prints, $location, $position, $priority, $section, $rating, $advertirser, $url, $image, $html));
	}
	
	public function remove_ad($id){
		global $database;
		
		$database->execute("DELETE FROM ads WHERE id = ?", array($id));
	}
	
	public function get_ads_section($section){
		global $config, $database, $user;
		
		if(!$user->is_anon()){
			if(class_exists("Ratings")){
				$rating = Ratings::privs_to_sql(Ratings::get_user_privs($user));
				$rating = "(rating IN ($rating))";
			}
			else{
				$rating = "(rating IN ('s', 'q', 'e'))";
			}
		}
		else{
			$rating = "(rating IN ('s'))";
		}
		
		$ads_visible = $user->get_auth_from_str($config->get_string("ads_visible_to"));
		$limit = $config->get_int("ads_limited_to");
		$random = $config->get_bool("ads_randomized");
		
		if($ads_visible){
			
			if($random){
				$ads = $database->get_all("SELECT DISTINCT id, location, position, image, html FROM ads WHERE prints < until_prints AND $rating AND (section = ? OR section = '*') ORDER BY RAND() DESC LIMIT ?", array($section, $limit));
			}
			else{
				$ads = $database->get_all("SELECT id, location, position, image, html FROM ads WHERE prints < until_prints AND $rating AND (section = ? OR section = '*') ORDER BY priority DESC LIMIT ?", array($section, $limit));
			}
		
			foreach($ads as $ad){
				$database->execute("UPDATE ads SET prints = prints + 1 WHERE id = ?", array($ad['id']));
				$this->theme->display_ad($ad);
			}
		}
	}
	
	public function add_click($id){
		global $database;
		$database->execute("UPDATE ads SET clicks = clicks + 1 WHERE id = ?", array($id));
	}
}
?>