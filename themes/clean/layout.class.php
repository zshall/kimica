<?php
/**
 * A class to turn a Page data structure into a blob of HTML
 */
class Layout {
	/**
	 * turns the Page into HTML
	 */
	public function display_page(Page $page) {
		global $config;

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
					if($block->header == "Images") {
						$block->header = "&nbsp;";
					}
					$main_block_html .= $this->block_to_html($block, false);
					break;
				case "subheading":
					$subheading_block_html .= $this->block_to_html($block, false);
					break;
				case "ads":
					$ads_block_html .= $this->block_to_html($block, false);
					break;
				default:
					print "<p>error: {$block->header} using an unknown section ({$block->section})";
					break;
			}
		}
		
		$site_name = $config->get_string('site_title'); // bzchan: change from normal default to get title for top of page
		$main_page = $config->get_string('main_page'); // bzchan: change from normal default to get main page for top of page
		
		// hack
		global $user;
		$username = url_escape($user->name);
		$user_id= int_escape($user->id);
		
		// bzchan: CUSTOM LINKS are prepared here, change these to whatever you like
		$custom_links = "";
		if(!$user->is_anon()){
			$custom_links .= "<li><a href='".make_link('account/profile/'.$username)."'><span>My Account</span></a></li>";
		}
		$custom_links .= "<li><a href='".make_link('post/list')."'><span>Posts</span></a></li>";
		$custom_links .= "<li><a href='".make_link('comment/list')."'><span>Comments</span></a></li>";
		$custom_links .= "<li><a href='".make_link('note/list')."'><span>Notes</span></a></li>";
		$custom_links .= "<li><a href='".make_link('artist/list')."'><span>Artists</span></a></li>";
		$custom_links .= "<li><a href='".make_link('tags')."'><span>Tags</span></a></li>";
		$custom_links .= "<li><a href='".make_link('pool/list')."'><span>Pools</span></a></li>";
		$custom_links .= "<li><a href='".make_link('wiki')."'><span>Wiki</span></a></li>";
		$custom_links .= "<li><a href='".make_link('forum')."'><span>Forum</span></a></li>";

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
					$custom_sublinks .= "<li><a href='".make_link("post/list/favorited_by=$username/1")."'><span>Favorites</span></a></li>";
					$custom_sublinks .= "<li><a href='".make_link("account/messages")."'><span>Messages</span></a></li>";
					$custom_sublinks .= "<li><a href='".make_link("account/logout")."'><span>Logout</span></a></li>";
				}
				break;
			case "post":
				$custom_sublinks .= "<li><a href='".make_link("post/list")."'><span>All</span></a></li>";
				$custom_sublinks .= "<li><a href='".make_link("upload")."'><span>Upload</span></a></li>";
				if(!$user->is_anon()){
					$custom_sublinks .= "<li><a href='".make_link("post/list/favorited_by=$username/1")."'><span>Favorites</span></a></li>";
				}
				$custom_sublinks .= "<li><a href='".make_link("post/popular")."'><span>Popular</span></a></li>";
				$custom_sublinks .= "<li><a href='".make_link("wiki/Help:posts")."'><span>Help</span></a></li>";
				break;
			case "journals":
				$custom_sublinks .= "<li><a href='".make_link("journals/list")."'><span>All</span></a></li>";
				$custom_sublinks .= "<li><a href='".make_link("journals/new")."'><span>New</span></a></li>";
				$custom_sublinks .= "<li><a href='".make_link("journals/user/$username/1")."'><span>My Journals</span></a></li>";
				$custom_sublinks .= "<li><a href='".make_link("wiki/Help:journals")."'><span>Help</span></a></li>";
				break;
			case "comment":
				$custom_sublinks .= "<li><a href='".make_link("comment/list")."'><span>All</span></a></li>";
				$custom_sublinks .= "<li><a href='".make_link("wiki/Help:comments")."'><span>Help</span></a></li>";
				break;
			case "forum":
				$custom_sublinks .= "<li><a href='".make_link("forum/new")."'><span>New topic</span></a></li>";
				$custom_sublinks .= "<li><a href='".make_link("wiki/Help:forum")."'><span>Help</span></a></li>";
				break;
			case "upload":
				$custom_sublinks .= "<li><a href='".make_link("post/list")."'><span>All</span></a></li>";
				$custom_sublinks .= "<li><a href='".make_link("post/list/poster=$username/1")."'><span>My Posts</span></a></li>";
				$custom_sublinks .= "<li><a href='".make_link("post/list/favorited_by=$username/1")."'><span>My Favorites</span></a></li>";
				$custom_sublinks .= "<li><a href='".make_link("wiki/Help:upload")."'><span>Help</span></a></li>";
				break;
			case "wiki":
				$custom_sublinks .= "<li><a href='".make_link("wiki")."'><span>Index</a></span></li>";
				$custom_sublinks .= "<li><a href='".make_link("wiki/rules")."'><span>Rules</a></span></li>";
				$custom_sublinks .= "<li><a href='".make_link("wiki/Help:wiki")."'><span>Help</a></span></li>";
				break;
			case "wiki_admin":
				$custom_sublinks .= "<li><a href='".make_link("wiki")."'><span>Index</a></span></li>";
				$custom_sublinks .= "<li><a href='".make_link("wiki/rules")."'><span>Rules</a></span></li>";
				$custom_sublinks .= "<li><a href='".make_link("wiki/Help:wiki")."'><span>Help</a></span></li>";
				break;
			case "artist":
				$custom_sublinks .= "<li><a href='".make_link("artist/list")."'><span>List</span></a></li>";
				$custom_sublinks .= "<li><a href='".make_link("artist/new")."'><span>New</span></a></li>";
				break;
			case "tags":
				$custom_sublinks .= "<li><a href='".make_link("tags/map")."'><span>Map</span></a></li>";
				$custom_sublinks .= "<li><a href='".make_link("tags/alphabetic")."'><span>Alphabetic</span></a></li>";
				$custom_sublinks .= "<li><a href='".make_link("tags/popularity")."'><span>Popularity</span></a></li>";
				$custom_sublinks .= "<li><a href='".make_link("tags/categories")."'><span>Categories</span></a></li>";
				$custom_sublinks .= "<li><a href='".make_link("tags/alias")."'><span>Aliases</span></a></li>";
				$custom_sublinks .= "<li><a href='".make_link("wiki/Help:tags")."'><span>Help</span></a></li>";
				break;
			case "advertisement":
				$custom_sublinks .= "<li><a href='".make_link("advertisement/list")."'><span>List</span></a></li>";
				$custom_sublinks .= "<li><a href='".make_link("advertisement/new")."'><span>New Advertisement</span></a></li>";
				$custom_sublinks .= "<li><a href='".make_link("wiki/advertisement")."'><span>Help</span></a></li>";
				break;
			case "note":
				$custom_sublinks .= "<li><a href='".make_link("note/list")."'><span>List</span></a></li>";
				$custom_sublinks .= "<li><a href='".make_link("note/search")."'><span>Search</span></a></li>";
				$custom_sublinks .= "<li><a href='".make_link("note/requests")."'><span>Requests</span></a></li>";
				$custom_sublinks .= "<li><a href='".make_link("note/updated")."'><span>Recent Changes</span></a></li>";
				$custom_sublinks .= "<li><a href='".make_link("wiki/Help:note")."'><span>Help</span></a></li>";
				break;
			case "pool":
				$custom_sublinks .= "<li><a href='".make_link("pool/list")."'><span>List</span></a></li>";
				$custom_sublinks .= "<li><a href='".make_link("pool/new")."'><span>New</span></a></li>";
				$custom_sublinks .= "<li><a href='".make_link("pool/updated")."'><span>Recent Changes</span></a></li>";
				$custom_sublinks .= "<li><a href='".make_link("wiki/Help:pool")."'><span>Help</span></a></li>";
				break;
		}

		$debug = get_debug_info();

		$contact = empty($contact_link) ? "" : "<br><a href='$contact_link'>Contact</a>";

		$wrapper = "";
		if(strlen($page->heading) > 100) {
			$wrapper = ' style="height: 3em; overflow: auto;"';
		}
		
		$copy_date = date("Y");

		print <<<EOD
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01//EN">
<html>
	<head>
		<title>{$page->title}</title>
		<meta http-equiv="Content-Type" content="text/html;charset=utf-8">
		<link rel="stylesheet" href="$data_href/themes/$theme_name/style.css" type="text/css">
		$headers_html
		<link href="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAJVJREFUeNpi/P//PwMlgImBQsCCzGFkZGQwMTEBO+nMmTOMyHLI4siuZkFXtFaTGcwOZjD5DzMEXRxkF9FeQNZMchigaw6+/hfDa3gNIKQZrwHEaAYHPHKIAmPh/yk/XQxFZpsuMyAHHLIenC6AagIDqKH/SQ5EYgwhFI2MhAwhJimjGEIoEJFtQA/1/1j1DHhuBAgwAHTlT2dZrEDHAAAAAElFTkSuQmCC" rel="icon" type="image/x-icon" />
	</head>

	<body>

	<!-- Header Starts -->
	<div id="header">
		<h1 id="logo">
		</h1>
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
	
	<div id="subheading">
	</div>
		
	<div id="page">
		<div id="sidebar">
			$left_block_html
		</div>
		<div id="content">
			$main_block_html
		</div>
	</div>
	
	<div id="footer">
		Images &copy; their respective owners. <a href="https://www.assembla.com/spaces/kimica">Kimica</a> &copy; 2007-$copy_date.
	</div>
	
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
						}
						else {
							$.cookie(\"$i-hidden\", 'false', {path: '/'});
						}
					});
				});
				if($.cookie(\"$i-hidden\") == 'true') {
					$(\"#$i\").hide();
				}
			});
			//--></script>
			
		";
        
		if($hidable) {
			$i = str_replace(' ', '_', $h.$s);
			if(!is_null($h)) $html .= "\n<div id='block-header'><span id='$i-toggle'>$h</span></div>";
			if(!is_null($h)) $html .= "\n<div id='block-main'>$b</div>";
		}
		else {
			$i = str_replace(' ', '_', $h.$s);
			if(!is_null($h)) $html .= "\n<div id='content-header'><span id='$i-toggle'>$h</span></div>";
			if(!is_null($b)) $html .= "\n<div id='content-main'>$b</div>";
		}
		return $html;
	}
}
?>