<?php
/**
 * A class to turn a Page data structure into a blob of HTML
 */
class Layout {
	/**
	 * turns the Page into HTML
	 */
	public function display_page(Page $page) {
		global $config, $user;
		
		$theme_name = $config->get_string('theme', 'default');
		$data_href = get_base_href();
		$contact_link = $config->get_string('contact_link');

		$headers_html = "";
		ksort($page->headers);
		foreach($page->headers as $line) {
			$headers_html .= "\t\t$line\n";
		}

		$left_block_html = "";
		$user_block_html = "";
		$main_block_html = "";
		$subheading_block_html = "";
		$ads_block_html = "";
		
		foreach($page->blocks as $block) {
			switch($block->section) {
				case "left":
					$left_block_html .= $this->block_to_html($block, true);
					break;
				case "user":
					$user_block_html .= $block->body; // $this->block_to_html($block, true);
					break;
				case "main":
					$main_block_html .= $this->block_to_html($block, false);
					break;
				case "subheading":
					$subheading_block_html .= "<div class='msg'>".$block->body."</div>";
					break;
				case "top_ads":
					$ads_block_html .= $this->ads_to_html($block, false);
					break;
				default:
					print "<p>error: {$block->header} using an unknown section ({$block->section})";
					break;
			}
		}
						
		$site_name = $config->get_string('site_title'); // bzchan: change from normal default to get title for top of page
		$main_page = $config->get_string('main_page'); // bzchan: change from normal default to get main page for top of page
		
		$username = url_escape($user->name);
		$user_id= int_escape($user->id);
		
		// bzchan: CUSTOM LINKS are prepared here, change these to whatever you like
		$custom_links = "";
		if(!$user->is_anon()){
			$custom_links .= $this->build_nav('account/profile/'.$username, 'My Account');
		}
		$custom_links .= $this->build_nav('post/list', 'Posts');
		$custom_links .= $this->build_nav('comment/list', 'Comments');
		$custom_links .= $this->build_nav('tags', 'Tags');
		$custom_links .= $this->build_nav('note/list', 'Notes');
		$custom_links .= $this->build_nav('artist/list', 'Artists');
		$custom_links .= $this->build_nav('pool/list', 'Pools');
		$custom_links .= $this->build_nav('wiki', 'Wiki');
		$custom_links .= $this->build_nav('forum/list', 'Forum');

		$custom_sublinks = "";
		// hack
		$qp = _get_query_parts();
		// php sucks
		switch($qp[0]) {
			default:
				$custom_sublinks .= $user_block_html;
				break;
			case "account":
				if(!$user->is_anon()){
					$custom_sublinks .= "<li><a href='".make_link("post/list/user:$username/1")."'><span>Posts</span></a></li>";
					$custom_sublinks .= "<li><a href='".make_link("post/list/favorited_by:$username/1")."'><span>Favorites</span></a></li>";
					$custom_sublinks .= "<li><a href='".make_link("account/subscriptions")."'><span>Subscriptions</span></a></li>";
					$custom_sublinks .= "<li><a href='".make_link("account/blacklist")."'><span>Blacklist</span></a></li>";
					$custom_sublinks .= "<li><a href='".make_link("account/messages")."'><span>Messages</span></a></li>";
					$custom_sublinks .= "<li><a href='".make_link("account/logout")."'><span>Logout</span></a></li>";
				}
				break;
			case "post":
			case "upload":
				$custom_sublinks .= "<li><a href='".make_link("post/list")."'><span>All</span></a></li>";
				$custom_sublinks .= "<li><a href='".make_link("upload")."'><span>Upload</span></a></li>";
				if(!$user->is_anon()){
					$custom_sublinks .= "<li><a href='".make_link("post/list/favorited_by:$username/1")."'><span>Favorites</span></a></li>";
				}
				$custom_sublinks .= "<li><a href='".make_link("post/popular")."'><span>Popular</span></a></li>";
				$custom_sublinks .= "<li><a href='".make_link("post/random")."'><span>Random</span></a></li>";
				$custom_sublinks .= "<li><a href='".make_link("help/posts")."'><span>Help</span></a></li>";
				break;
			case "journals":
				$custom_sublinks .= "<li><a href='".make_link("journals/list")."'><span>All</span></a></li>";
				$custom_sublinks .= "<li><a href='".make_link("journals/new")."'><span>New</span></a></li>";
				$custom_sublinks .= "<li><a href='".make_link("journals/user/$username/1")."'><span>My Journals</span></a></li>";
				$custom_sublinks .= "<li><a href='".make_link("help/journals")."'><span>Help</span></a></li>";
				break;
			case "comment":
				$custom_sublinks .= "<li><a href='".make_link("comment/list")."'><span>All</span></a></li>";
				$custom_sublinks .= "<li><a href='".make_link("help/comments")."'><span>Help</span></a></li>";
				break;
			case "forum":
				$custom_sublinks .= "<li><a href='".make_link("forum/list")."'><span>All</span></a></li>";
				$custom_sublinks .= "<li><a href='".make_link("forum/new")."'><span>New topic</span></a></li>";
				$custom_sublinks .= "<li><a href='".make_link("help/forum")."'><span>Help</span></a></li>";
				break;
			case "wiki":
				$custom_sublinks .= "<li><a href='".make_link("wiki")."'><span>Index</a></span></li>";
				$custom_sublinks .= "<li><a href='".make_link("wiki/list")."'><span>All</a></span></li>";
				$custom_sublinks .= "<li><a href='".make_link("help/wiki")."'><span>Help</a></span></li>";
				break;
			case "wiki_admin":
				$custom_sublinks .= "<li><a href='".make_link("wiki")."'><span>Index</a></span></li>";
				$custom_sublinks .= "<li><a href='".make_link("wiki/list")."'><span>All</a></span></li>";
				$custom_sublinks .= "<li><a href='".make_link("help/wiki")."'><span>Help</a></span></li>";
				break;
			case "artist":
				$custom_sublinks .= "<li><a href='".make_link("artist/list")."'><span>List</span></a></li>";
				$custom_sublinks .= "<li><a href='".make_link("artist/new")."'><span>New</span></a></li>";
				$custom_sublinks .= "<li><a href='".make_link("help/artists")."'><span>Help</a></span></li>";
				break;
			case "tags":
				$custom_sublinks .= "<li><a href='".make_link("tags/map")."'><span>Map</span></a></li>";
				$custom_sublinks .= "<li><a href='".make_link("tags/alphabetic")."'><span>Alphabetic</span></a></li>";
				$custom_sublinks .= "<li><a href='".make_link("tags/popularity")."'><span>Popularity</span></a></li>";
				$custom_sublinks .= "<li><a href='".make_link("tags/categories")."'><span>Categories</span></a></li>";
				$custom_sublinks .= "<li><a href='".make_link("tags/alias")."'><span>Aliases</span></a></li>";
				$custom_sublinks .= "<li><a href='".make_link("tags/history")."'><span>History</span></a></li>";
				$custom_sublinks .= "<li><a href='".make_link("help/tags")."'><span>Help</span></a></li>";
				break;
			case "ads":
				$custom_sublinks .= "<li><a href='".make_link("ads/list")."'><span>List</span></a></li>";
				$custom_sublinks .= "<li><a href='".make_link("ads/new")."'><span>New</span></a></li>";
				break;
			case "note":
				$custom_sublinks .= "<li><a href='".make_link("note/list")."'><span>List</span></a></li>";
				$custom_sublinks .= "<li><a href='".make_link("note/search")."'><span>Search</span></a></li>";
				$custom_sublinks .= "<li><a href='".make_link("note/requests")."'><span>Requests</span></a></li>";
				$custom_sublinks .= "<li><a href='".make_link("note/history")."'><span>History</span></a></li>";
				$custom_sublinks .= "<li><a href='".make_link("help/notes")."'><span>Help</span></a></li>";
				break;
			case "pool":
				$custom_sublinks .= "<li><a href='".make_link("pool/list")."'><span>List</span></a></li>";
				$custom_sublinks .= "<li><a href='".make_link("pool/new")."'><span>New</span></a></li>";
				$custom_sublinks .= "<li><a href='".make_link("pool/history")."'><span>History</span></a></li>";
				$custom_sublinks .= "<li><a href='".make_link("help/pools")."'><span>Help</span></a></li>";
				break;
		}

		$debug = $user->is_admin() ? get_debug_info() : "";

		$contact = empty($contact_link) ? "" : "<br><a href='$contact_link'>Contact</a>";

		$wrapper = "";
		if(strlen($page->heading) > 100) {
			$wrapper = ' style="height: 3em; overflow: auto;"';
		}
		
		$copy_date = date("Y");
		
		global $database;
		$unread = $database->db->GetOne("SELECT COUNT(*) FROM messages WHERE to_id = ? AND status = 'u'", array($user->id));
		if($unread == 0){
			$unread = "Messages";
		}
		else{
			$unread = $unread." Messages";
		}
		
		$gravatar = $user->get_avatar_html();
		$profile_link = "<a href='".make_link("account/profile/".$user->name)."'>Profile</a>";
		$preferences_link = "<a href='".make_link("account/preferences/".$user->name)."'>Preferences</a>";
		$messages_link = "<a href='".make_link("account/messages/inbox")."'>$unread</a>";
		$create_link = "<a href='".make_link("account/create")."'>Create Account</a>";
		$logout_link = "<a href='".make_link("account/logout")."'>Log Out</a>";
		
		if(!$user->is_anon()){
			$user_links = "<p>Welcome, $user->name</p><br>";
			$user_links .= "<p>$profile_link | $preferences_link | $messages_link</p>";
			$account_link = $logout_link;
		}
		else{
			$user_links = "<p>Welcome, Guest</p><br>";
			$user_links .= "<p>&nbsp;</p>";
			$account_link = $create_link;
		}
		
		print <<<EOD
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01//EN">
<html>
	<head>
		<title>{$page->title}</title>
		<meta http-equiv="Content-Type" content="text/html;charset=utf-8">
		<link rel="stylesheet" href="$data_href/themes/$theme_name/style.css" type="text/css">
		$headers_html
		<link href="http://www.furpiled.com/favicon.ico" rel="icon" type="image/x-icon" />
	</head>

	<body>

	<!-- Header Starts -->
	<div id="header">
		<div id="userbox">
			$gravatar
			$user_links
			
			<div class="logout">
				$account_link
			</div>
		</div>
		
	</div>
	<!-- Header Ends -->
	
	<!-- Nav Starts -->
	<div id="nav">
		<ul>
			$custom_links
		</ul>
	</div>
	<!-- Nav Ends -->
	
	<!-- Nav Starts -->
	<div id="sub-nav">
		<ul>
			$custom_sublinks
		</ul>
	</div>
	<!-- Nav Ends -->
			
	<div id="wraper">
		$ads_block_html
		
		<div id="subheading">
			$subheading_block_html
		</div>
	
		<div id="sidebar">
			$left_block_html
		</div>
		<div id="content">
			$main_block_html
		</div>
	</div>
	
	<div id="footer">
		All artwork and other content is copyright its respective owners.
		<br>
		<a href="https://www.furpiled.com/">Furpiled</a> &copy; 2007-$copy_date.
		<br>
		$debug
	</div>
	
	<div id="alert"></div>
	
	</body>
</html>
EOD;
	}

	/**
	 * A handy function which does exactly what it says in the method name
	 */
	function block_to_html($block, $hidable=false) {
		$h = $block->header;
		$s = $block->section;
		$b = $block->body;
		$html = "";
        $i = str_replace(' ', '_', $h.$s);
        if($hidable) $html .= "
			<script><!--
			$(document).ready(function() {
				$(\"#$i-toggle\").click(function() {
					$(\"#$i\").slideToggle(\"slow\", function() {
						if($(\"#$i\").is(\":hidden\")) {
							$.cookie(\"$i-hidden\", 'true', {path: '/'});
							$(\"#$i-toggle\").removeClass('block-header').addClass('block-header-rounded');
							$(\"#$i-toggle .block-inner\").removeClass('block-inner').addClass('block-inner-rounded');
						}
					});
					
					$.cookie(\"$i-hidden\", 'false', {path: '/'});
					$(\"#$i-toggle\").removeClass('block-header-rounded').addClass('block-header');
					$(\"#$i-toggle .block-inner-rounded\").removeClass('block-inner-rounded').addClass('block-inner');
				});
				
				if($.cookie(\"$i-hidden\") == 'true') {
					$(\"#$i\").hide();
					$(\"#$i-toggle\").removeClass('block-header').addClass('block-header-rounded');
					$(\"#$i-toggle .block-inner\").removeClass('block-inner').addClass('block-inner-rounded');
				}
			});
			//--></script>
			
		";
		
		if($h == NULL){
			$subclass = 'hide';
		}
		else{
			$subclass = 'main';
		}
        
		if($hidable) {
			$i = str_replace(' ', '_', $h.$s);
			if(!is_null($h)) $html .= "\n<div id='$i-toggle' class='block-header'><div class='block-inner'><span>$h</span></div></div>";
			if(!is_null($h)) $html .= "\n<div id='$i' class='block-main'>$b</div>";
			if(!is_null($h)) $html .= "\n<div class='block-close'></div>";
		}
		else {
			$i = str_replace(' ', '_', $h.$s);
			if(!is_null($h)) $html .= "\n<div class='content-header'><div class='content-inner'><span id='$i-toggle'>$h</span></div></div>";
			if(!is_null($b)) $html .= "\n<div class='content-$subclass'>$b</div>";
			if(!is_null($b)) $html .= "\n<div class='content-close'></div>";
		}
		return $html;
	}
	
	function ads_to_html($block) {
		global $config;
		$close = $config->get_string('ads_close_url', '#');
		$b = $block->body;
		$html = "<div id='header-ads'>".$block->body."<a class='close' href='".$close."' title='Close ads.'></a></div>";;
		return $html;
	}
	
	private function build_nav($link, $title){
		$url = _get_query_parts();
		$section = explode("/", $link);
		if($url['0'] == $section['0']){
			return "<li class='active'><a href='".make_link($link)."'><span>".$title."</span></a></li>";
		}
		else{
			return "<li><a href='".make_link($link)."'><span>".$title."</span></a></li>";
		}
	}
}
?>