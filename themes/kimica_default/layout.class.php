<?php
/**
* Name: Danbooru Theme
* Author: Bzchan <bzchan@animemahou.com>
* Link: 
* License: GPLv2
* Description: This is a simple theme changing the css to make shimme
*              look more like danbooru as well as adding a custom links
*              bar and title to the top of every page.
*/
//Small changes added by zshall <http://seemslegit.com>
//Changed CSS and layout to make Kimica look even more like danbooru
/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
Danbooru Theme - Notes (Bzchan)

Files: default.php, sidebar.js, style.css

How to use a theme
- Copy the danbooru folder with all its contained files into the "themes"
  directory in your Kimica installation.
- Log into your Kimica and change the Theme in the Board Config to your
  desired theme.

Changes in this theme include
- Adding and editing various elements in the style.css file.
- $site_name and $front_name retreival from config added.
- $custom_link and $title_link preparation just before html is outputed.
- Altered outputed html to include the custom links and removed heading
  from being displayed (subheading is still displayed) 
- Note that only the sidebar has been left aligned. Could not properly
  left align the main block because blocks without headers currently do
  not have ids on there div elements. (this was a problem because
  paginator block must be centered and everything else left aligned)
  
Tips
- You can change custom links to point to whatever pages you want as well as adding
  more custom links.
- The main title link points to the Front Page set in your Board Config options.
- The text of the main title is the Title set in your Board Config options.
- Themes make no changes to your database or main code files so you can switch
  back and forward to other themes all you like.

* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * */

class Layout {
	public function display_page($page) {
		global $config, $user;

		$theme_name = $config->get_string('theme');
		$base_href = $config->get_string('base_href');
		$data_href = get_base_href();
		$contact_link = $config->get_string('contact_link');
		
		$page_header = $page->heading;


		$header_html = "";
		ksort($page->headers);
		foreach($page->headers as $line) {
			$header_html .= "\t\t$line\n";
		}

		$left_block_html = "";
		$user_block_html = "";
		$main_block_html = "";
		$sub_block_html = "";

		foreach($page->blocks as $block) {
			switch($block->section) {
				case "left":
					$left_block_html .= $this->block_to_html($block, true);
					break;
				case "user":
					$user_block_html .= $block->body; // $this->block_to_html($block, true);
					break;
				case "subheading":
					$sub_block_html .= $block->body; // $this->block_to_html($block, true);
					break;
				case "main":
					if($block->header == "Images") {
						$block->header = "&nbsp;";
					}
					$main_block_html .= $this->block_to_html($block, false);
					break;
				default:
					print "<p>error: {$block->header} using an unknown section ({$block->section})";
					break;
			}
		}

		$debug = get_debug_info();

		$contact = empty($contact_link) ? "" : "<br><a href='$contact_link'>Contact</a>";

		if(empty($this->subheading)) {
			$subheading = "";
		}
		else {
			$subheading = "<div id='subtitle'>{$this->subheading}</div>";
		}

		$site_name = $config->get_string('title'); // bzchan: change from normal default to get title for top of page
		$main_page = $config->get_string('main_page'); // bzchan: change from normal default to get main page for top of page

		// bzchan: CUSTOM LINKS are prepared here, change these to whatever you like
		$custom_links = "";
/*		if($user->is_anon()) {
			$custom_links .= $this->navlinks(make_link('account/login'), "My Account", array("user", "account", "setup", "admin"));
		}
		else {
			$custom_links .= $this->navlinks(make_link('user'), "My Account", array("user", "account", "setup", "admin"));
		}
*/		$custom_links .= $this->navlinks(make_link('post/list'), "Posts", array("post"));
		$custom_links .= $this->navlinks(make_link('comment/list'), "Comments", array("comment"));
		$custom_links .= $this->navlinks(make_link('tags'), "Tags", array("tags"));
		if(class_exists("Pools")) {
			$custom_links .= $this->navlinks(make_link('pool/list'), "Pools", array("pool"));
		}
		$custom_links .= $this->navlinks(make_link('upload'), "Upload", array("upload"));
		if(class_exists("Wiki")) {
			$custom_links .= $this->navlinks(make_link('wiki'), "Wiki", array("wiki"));
			$custom_links .= $this->navlinks(make_link('wiki/more'), "More &raquo;", array("wiki/more"));
		}

		$custom_sublinks = "";
		// hack
		global $user;
		$username = url_escape($user->name);
		// hack
		$qp = _get_query_parts();
		$hw = class_exists("Wiki");
		// php sucks
		switch($qp[0]) {
			default:
				$custom_sublinks .= $user_block_html . "&nbsp;";
				break;
			case "":
				# FIXME: this assumes that the front page is
				# post/list; in 99% of case it will either be
				# post/list or home, and in the latter case
				# the subnav links aren't shown, but it would
				# be nice to be correct
			case "post":
			case "upload":
				$custom_sublinks .= "<li><a href='".make_link('post/list')."'>All</a></li>";
				$custom_sublinks .= "<li><a href='".make_link("post/list/favorited_by=$username/1")."'>My Favorites</a></li>";
				$custom_sublinks .= "<li><a href='".make_link("ext_doc/index")."'>Help</a></li>";
				break;
			case "comment":
				$custom_sublinks .= "<li><a href='".make_link('comment/list')."'>All</a></li>";
				$custom_sublinks .= "<li><a href='".make_link("ext_doc/comment")."'>Help</a></li>";
				break;
			case "pool":
				$custom_sublinks .= "<li><a href='".make_link('pool/list')."'>List</a></li>";
				$custom_sublinks .= "<li><a href='".make_link("pool/new")."'>Create</a></li>";
				$custom_sublinks .= "<li><a href='".make_link("pool/updated")."'>Changes</a></li>";
				$custom_sublinks .= "<li><a href='".make_link("ext_doc/pools")."'>Help</a></li>";
				break;
			case "wiki":
				$custom_sublinks .= "<li><a href='".make_link('wiki')."'>Index</a></li>";
				$custom_sublinks .= "<li><a href='".make_link("wiki/rules")."'>Rules</a></li>";
				$custom_sublinks .= "<li><a href='".make_link("ext_doc/wiki")."'>Help</a></li>";
				break;
			case "tags":
			case "alias":
				$custom_sublinks .= "<li><a href='".make_link('tags/map')."'>Map</a></li>";
				$custom_sublinks .= "<li><a href='".make_link('tags/alphabetic')."'>Alphabetic</a></li>";
				$custom_sublinks .= "<li><a href='".make_link('tags/popularity')."'>Popularity</a></li>";
				$custom_sublinks .= "<li><a href='".make_link('tags/categories')."'>Categories</a></li>";
				$custom_sublinks .= "<li><a href='".make_link('alias/list')."'>Aliases</a></li>";
				$custom_sublinks .= "<li><a href='".make_link("ext_doc/tag_edit")."'>Help</a></li>";
				break;
		}

		// prepare userbar
		$user_bar_search = "<script><!--
			$(document).ready(function() {
				$('#userbar_search').DefaultValue('Search');
				$('#userbar_username').DefaultValue('Username');
				$('#userbar_password').DefaultValue('Password');
				$('#userbar_search').autocomplete('".make_link("api/internal/tag_list/complete")."', {
					width: 169,
					max: 15,
					highlight: false,
					multiple: true,
					multipleSeparator: ' ',
					scroll: true,
					scrollHeight: 300,
					selectFirst: false
				});
			});
			//--></script>";
		$user_bar_search .= '<form action="'.make_link('post/list').'" method="GET"><ul class="flat-list" style="float:right;"><li><input id="userbar_search"  name="search" type="text" autocomplete="off"> <input type="hidden" name="q" value="/post/list">
				<input type="submit" value="»" style="display: none;" /></li></ul></form>';
		if($user->is_anon()) {
			$user_bar = '<div id="userbar">
				<form action="'.make_link("account/login").'" method="post">
				<ul class="flat-list" style="float:left; width:650px;">
			<li><a href="'.make_link().'"><img src="'.$data_href.'/themes/'.$theme_name.'/res/home.gif">'.$site_name.'</a></li>
			<li><input type="text" name="user" id="userbar_username"></li>
			<li><input type="password" name="pass" id="userbar_password">
			<input type="image" src="/themes/'.$theme_name.'/res/blank.gif" alt="Log In" style="border:0px; background:transparent;" />';
			if(($qp[0] == "post" && $qp[1] == "view") || ($qp[0] == "account")) {$user_bar .= '<a href="'.make_link("account/create").'">Sign up</a></li>';} else {
			$user_bar .= '</li><li><a id="signup-link" href="#">Sign up</a></li>'; }
			$user_bar .= '
					</ul></form>'.$user_bar_search.'</div>
					';
		// if on /post/view page, don't display form.
		if(($qp[0] == "post" && $qp[1] == "view") || ($qp[0] == "account")) {} else {
			$user_bar .= "<script>
			$(document).ready(function() {
			  $('#signup-box').hide();
			  $('a#signup-link').click(function() {
				$('#signup-box').slideToggle('slow');
				return false;
			  });
			});
			</script>
			<div id='signup-box'>";
			$tac = $config->get_string("signup_tac", "");
			
			$tfe = new TextFormattingEvent($tac);
			send_event($tfe);
			$tac = $tfe->formatted;
			
			$reca = "<tr><td colspan='2'>".captcha_get_html()."</td></tr>";
			
			if(empty($tac)) {$html = "";}
			else {$tac = "<p>$tac</p>";}
			
			$user_bar .= "<br />
			<br />
			
			<h1>Create Account</h1>
			$tac
			<form action='".make_link("account/create")."' method='POST'>
				<table style='width: 300px;'>
					<tr><td>Name</td><td><input type='text' name='name'></td></tr>
					<tr><td>Password</td><td><input type='password' name='pass1'></td></tr>
					<tr><td>Repeat Password</td><td><input type='password' name='pass2'></td></tr>
					<tr><td>Email</td><td><input type='text' name='email'></td></tr>
					$reca
					<tr><td colspan='2'><input type='Submit' value='Create Account'></td></tr>
				</table>
			</form><br />
			<br />
			";
			$user_bar .= "</div>
			";}
		} else {
			global $prefs;
			if($prefs->get_bool("theme_kimica_userbar_hide")==true) {
			$user_bar = "<script>
			$(document).ready(function() {
			  $('#userbar').hide();
			  $('#userbar_transparent').mouseenter(function() {
				$('#userbar').slideDown('fast');
				return false;
			  });
			  $('#userbar').mouseleave(function() {
				$('#userbar').slideUp('fast');
				return false;
			  });
			});
			</script>";
			$user_bar .= "<div id='userbar_transparent'>&nbsp;</div>"; } else { $user_bar = ''; }
			$user_bar .= '<div id="userbar"><ul class="flat-list" style="float:left; display:inline; width:700px;">
			<li><a href="'.make_link().'"><img src="'.$data_href.'/themes/'.$theme_name.'/res/home.gif">'.$site_name.'</a></li>
			<li><a href="'.make_link("profile").'"><img src="'.$data_href.'/themes/'.$theme_name.'/res/user.gif">'.$user->name.'</a></li>
			<li><a href="'.make_link("account/messages").'"><img src="'.$data_href.'/themes/'.$theme_name.'/res/mail.gif">(0)</a></li>
			<li><a href="'.make_link("account/preferences").'"><img src="'.$data_href.'/themes/'.$theme_name.'/res/pref.gif">Prefs</a></li>';
			if($user->is_mod()) {
			$user_bar .= '<li><a href="'.make_link("tools").'"><img src="'.$data_href.'/themes/'.$theme_name.'/res/tools.gif">Tools</a></li>';
			}
			if($user->is_admin()) {
			$user_bar .= '<li><a href="'.make_link("setup").'"><img src="'.$data_href.'/themes/'.$theme_name.'/res/config.gif">Config</a></li>';
			}
			$user_bar .= '
			<li><a href="'.make_link("account/logout").'"><img src="'.$data_href.'/themes/'.$theme_name.'/res/logout.gif">Log out »</a></li>
			</ul>'.$user_bar_search.'</div>';
		}

		// bzchan: prepare main title link
		$title_link = "<h1 id='site-title'><a href='".make_link($main_page)."'>$page_header</a></h1>";

		if($page->left_enabled) {
			$left = "<div id='nav'>$left_block_html</div>";
			$withleft = "withleft";
		}
		else {
			$left = "";
			$withleft = "noleft";
		}

		print <<<EOD
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01//EN">
<html>
	<head>
		<title>{$page->title}</title>
		<meta http-equiv="Content-Type" content="text/html;charset=utf-8">
		<link rel="stylesheet" href="$data_href/themes/$theme_name/res/style.css" type="text/css">
		<link rel="stylesheet" href="$data_href/themes/$theme_name/res/frame.css" type="text/css">
$header_html
		<script src='$data_href/themes/$theme_name/res/sidebar.js' type='text/javascript'></script>
		<script src='$data_href/themes/$theme_name/res/script.js' type='text/javascript'></script>
	</head>

	<body>
		$user_bar
    	<div id="bg">
            <div id="hd">
                $title_link
            </div>
            <ul id="topline" class="clearfix">
                $custom_links
            </ul>
        </div>
		<ul id="botline" class="clearfix">
			$custom_sublinks
		</ul>
		<div id="body-section">
		$subheading
		$sub_block_html
		
		$left
		<div id="body" class="$withleft clearfix">$main_block_html</div>
		</div>
		<div id="ft">
        <em>
			Images &copy; their respective owners,
			<a href="http://code.shishnet.org/shimmie2/">Shimmie</a> &copy;
			<a href="http://www.shishnet.org/">Shish</a> &amp; Co 2007-2010,
			based on the Danbooru concept.
			$debug
			$contact
        </em>
		</div>
	</body>
</html>
EOD;
	}
	
	function block_to_html($block, $hidable=false) {
		$h = $block->header;
		$s = $block->section;
		$b = $block->body;
		$html = "";
		if($hidable) {
			$i = str_replace(' ', '_', $h.$s);
			if(!is_null($h)) $html .= "\n<h3 id='$i-toggle' onclick=\"toggle('$i')\">$h</h3>\n";
			if(!is_null($b)) $html .= "<div id='$i'>$b</div>\n";
		}
		else {
			$i = str_replace(' ', '_', $h.$s);
			if(!is_null($h)) $html .= "\n<h3>$h</h3>\n";
			if(!is_null($b)) $html .= "<div id='$i'>$b</div>\n"; 
		}
		return $html;
	}
	private function navlinks($link, $desc, $pages_matched) {
	/**
	 * Woo! We can actually SEE THE CURRENT PAGE!! (well... see it highlighted in the menu.)
	 */
		$html = null;
		$url = $_GET['q'];

		$re1='.*?';
		$re2='((?:[a-z][a-z]+))';

		if ($c=preg_match_all ("/".$re1.$re2."/is", $url, $matches)) {
			$url=$matches[1][0];
		}
		
		for($i=0;$i<count($pages_matched);$i++) {
			if($url == $pages_matched[$i]) {
				$html = "<li class='selected'><a href='$link'>$desc</a></li>";
			}
		}
		if(is_null($html)) {$html = "<li><a class='tab' href='$link'>$desc</a></li>";}
		return $html;
	}
}
?>
