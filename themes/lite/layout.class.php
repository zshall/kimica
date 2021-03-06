<?php
/**
* Name: Lite Theme
* Author: Zach Hall <zach@sosguy.net>
* Link: http://seemslegit.com
* License: GPLv2
* Description: A mashup of Default, Danbooru, the interface on qwebirc, and
* 	       some other sites, packaged in a light blue color.
*/
class Layout {
	/**
	 * turns the Page into HTML
	 */
	public function display_page(Page $page) {
		global $config, $user;

		$theme_name = $config->get_string('theme', 'lite');
		$site_name = $config->get_string('site_title');
		$data_href = get_base_href();
		$contact_link = $config->get_string('contact_link');

		$header_html = "";
		foreach($page->headers as $line) {
			$header_html .= "\t\t$line\n";
		}
		
		$menu = "<div class='menu'>
			<script type='text/javascript' src='$data_href/themes/$theme_name/wz_tooltip.js'></script>
			<a href='".make_link()."' onmouseover='Tip(&#39;Home&#39;, BGCOLOR, &#39;#C3D2E0&#39;, FADEIN, 100)' onmouseout='UnTip()'><img src='$data_href/favicon.ico' style='position: relative; top: 3px;'></a>
			<b>{$site_name}</b> ";
		
		// Custom links: These appear on the menu.
		$custom_links = "";
		if($user->is_anon()) {
			$custom_links .= $this->navlinks('account/login', "Account");
		} else {
			$custom_links .= $this->navlinks('user', "Account");
		}
		$custom_links .= $this->navlinks('post/list', "Posts");
		$custom_links .= $this->navlinks('comment/list', "Comments");
		$custom_links .= $this->navlinks('tags', "Tags");
		$custom_links .= $this->navlinks('upload', "Upload");
		if(class_exists("Wiki")) {
			$custom_links .= $this->navlinks('wiki/rules', "Rules"));
			$custom_links .= $this->navlinks('wiki', "Wiki");
		}
		$menu .= "$custom_links</div>";
		
		$left_block_html = "";
		$main_block_html = "";
		$sub_block_html  = "";
		$user_block_html = "";

		foreach($page->blocks as $block) {
			switch($block->section) {
				case "left":
					$left_block_html .= $this->block_to_html($block, true, "left");
					break;
				case "main":
					$main_block_html .= $this->block_to_html($block, false, "main");
					break;
				case "user":
					$user_block_html .= $block->body;
					break;
				case "subheading":
					$sub_block_html .= $this->block_to_html($block, false, "main");
					break;
				default:
					print "<p>error: {$block->header} using an unknown section ({$block->section})";
					break;
			}
		}

		$custom_sublinks = "<div class='sbar'>";
		$cs = null;
		// hack
		global $user;
		$username = url_escape($user->name);
		// hack
		$qp = _get_query_parts();
		$hw = class_exists("Wiki");
		// php sucks
		switch($qp[0]) {
			default:
				$cs = $user_block_html;
				break;
			case "":
				# FIXME: this assumes that the front page is
				# post/list; in 99% of case it will either be
				# post/list or home, and in the latter case
				# the subnav links aren't shown, but it would
				# be nice to be correct
			case "post":
				$cs .= "<a class='tab' href='".make_link('post/list')."'>All</a>";
				$cs .= "<a class='tab' href='".make_link("post/list/favorited_by=$username/1")."'>My Favorites</a>";
				$cs .= "<a class='tab' href='".make_link('rss/images')."'>Feed</a>";
				if($hw) $cs .= "<a class='tab' href='".make_link("wiki/posts")."'>Help</a>";
				break;
			case "comment":
				$cs .= "<a class='tab' href='".make_link('comment/list')."'>All</a>";
				$cs .= "<a class='tab' href='".make_link('rss/comments')."'>Feed</a>";
				$cs .= "<a class='tab' href='".make_link("extensions/docs/comment")."'>Help</a>";
				break;
			case "pool":
				$cs .= "<a class='tab' href='".make_link('pool/list')."'>List</a>";
				$cs .= "<a class='tab' href='".make_link("pool/new")."'>Create</a>";
				$cs .= "<a class='tab' href='".make_link("pool/updated")."'>Changes</a>";
				$cs .= "<a class='tab' href='".make_link("extensions/docs/pools")."'>Help</a>";
				break;
			case "wiki":
				$cs .= "<a class='tab' href='".make_link('wiki')."'>Index</a>";
				$cs .= "<a class='tab' href='".make_link("wiki/rules")."'>Rules</a>";
				$cs .= "<a class='tab' href='".make_link("extensions/docs/wiki")."'>Help</a>";
				break;
			case "tags":
			case "alias":
				$cs .= "<a class='tab' href='".make_link('tags/map')."'>Map</a>";
				$cs .= "<a class='tab' href='".make_link('tags/alphabetic')."'>Alphabetic</a>";
				$cs .= "<a class='tab' href='".make_link('tags/popularity')."'>Popularity</a>";
				$cs .= "<a class='tab' href='".make_link('tags/categories')."'>Categories</a>";
				$cs .= "<a class='tab' href='".make_link('alias/list')."'>Aliases</a>";
				$cs .= "<a class='tab' href='".make_link("extensions/docs/tag_edit")."'>Help</a>";
				break;
			case "upload":
				if($hw) $cs .= "<a class='tab' href='".make_link("wiki/upload_guidelines")."'>Guidelines</a>";
				break;
			case "random":
				$cs .= "<a class='tab' href='".make_link('random/view')."'>Shuffle</a>";
				$cs .= "<a class='tab' href='".make_link('random/download')."'>Download</a>";
				break;
			case "featured":
				$cs .= "<a class='tab' href='".make_link('featured/download')."'>Download</a>";
				break;
		}
		if($cs == "") {$custom_sublinks = "";} else {
		$custom_sublinks .= "$cs</div>";}


		$debug = get_debug_info();

		$contact = empty($contact_link) ? "" : "<br><a href='$contact_link'>Contact</a>";
		$subheading = empty($page->subheading) ? "" : "<div id='subtitle'>{$page->subheading}</div>";

		$wrapper = "";
		if(strlen($page->heading) > 100) {
			$wrapper = ' style="height: 3em; overflow: auto;"';
		}
		if($page->left_enabled==false) {
			$left_block_html = "";
			$main_block_html = "<div id='body_noleft'>$main_block_html</div>";
		} else {
			$left_block_html = "<div id='nav'>$left_block_html</div>";
			$main_block_html = "<div id='body'>$main_block_html</div>";
		}

		print <<<EOD
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01//EN">
<html>
	<head>
		<title>{$page->title}</title>
		<meta http-equiv="Content-Type" content="text/html;charset=utf-8">
		<link rel="stylesheet" href="$data_href/themes/$theme_name/style.css" type="text/css">
		$header_html
	</head>

	<body>
		$menu
		$custom_sublinks
		
		$sub_block_html

		$left_block_html
		$main_block_html
		

		<div id="footer">
			Images &copy; their respective owners,
			<a href="http://code.shishnet.org/shimmie2/">Shimmie</a> &copy;
			<a href="http://www.shishnet.org/">Shish</a> &amp; Co 2007-2010,
			based on the Danbooru concept.<br />
			Lite Theme by <a href="http://seemslegit.com">Zach</a>
			$debug
			$contact
		</div>
	</body>
</html>
EOD;
	}

	/**
	 * A handy function which does exactly what it says in the method name
	 */
	private function block_to_html($block, $hidable=false, $salt="") {
		$h = $block->header;
		$b = $block->body;
		$html = "";
		$i = str_replace(' ', '_', $h) . $salt;
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
		if(!is_null($h)) {
			if($salt == "main") {
				$html .= "<div class='maintop navside tab' id='$i-toggle'>$h</div>";
			} else {
				$html .= "<div class='navtop navside tab' id='$i-toggle'>$h</div>";
			}
			}
		if(!is_null($b)) {
			//if(strpos($b, "<!-- cancel border -->")) {
			if($salt =="main") {
				$html .= "<div class='blockbody' id='$i'>$b</div>";
			}
			else {
				$html .= "
					<div class='navside tab' id='$i'>$b</div>
				";
			}
		}

		return $html;
	}
		
	private function navlinks($link, $title){
		$url = _get_query_parts();
		$section = explode("/", $link);
		if($url['0'] == $section['0']){
			return "<a class='tab-selected' href='".make_link($link)."'>".$title."</a>";
		}
		else{
			return "<a class='tab' href='".make_link($link)."'>".$title."</a>";
		}
	}
}
?>
