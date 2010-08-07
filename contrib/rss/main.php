<?php
/*
 * Name: RSS for Images & Comments
 * Author: Shish <webmaster@shishnet.org>
 * License: GPLv2
 * Description: Self explanatory
 */

class RSS_Images extends SimpleExtension {

	public function onPostListBuilding($event) {
		global $config, $page;
		$title = $config->get_string('site_title');

		if(count($event->search_terms) > 0) {
			$search = html_escape(implode(' ', $event->search_terms));
			$page->add_header("<link id=\"images\" rel=\"alternate\" type=\"application/rss+xml\" ".
				"title=\"$title - Images with tags: $search\" href=\"".make_link("rss/images/$search/1")."\" />");
		}
		else {
			$page->add_header("<link id=\"images\" rel=\"alternate\" type=\"application/rss+xml\" ".
				"title=\"$title - Images\" href=\"".make_link("rss/images/1")."\" />");
		}
		
		$page->add_header("<link rel=\"alternate\" type=\"application/rss+xml\" ".
			"title=\"$title - Comments\" href=\"".make_link("rss/comments")."\" />");
	}

	public function onPageRequest($event) {
		if($event->page_matches("rss/images")) {
			$search_terms = $event->get_search_terms();
			$page_number = $event->get_page_number();
			$page_size = $event->get_page_size();
			$images = Image::find_images(($page_number-1)*$page_size, $page_size, $search_terms);
			$this->do_images_rss($images, $search_terms, $page_number);
		}
		
		if($event->page_matches("rss/comments")) {
			$this->do_comments_rss();
		}
	}


	private function do_images_rss($images, $search_terms, $page_number) {
		global $page;
		global $config;
		$page->set_mode("data");
		$page->set_type("application/rss+xml");

		$data = "";
		foreach($images as $image) {
			$link = make_http(make_link("post/view/{$image->id}"));
			$tags = html_escape($image->get_tag_list());
			$owner = $image->get_owner();
			$thumb_url = $image->get_thumb_link();
			$image_url = $image->get_image_link();
			$posted = date(DATE_RSS, $image->posted_timestamp);
			$content = "<p>" . Themelet::build_thumb_html($image) . "</p>" .
				"<p>Uploaded by " . html_escape($owner->name) . "</p>";

			$data .= "
					<item>
						<title>{$image->id} - $tags</title>
						<link>$link</link>
						<guid isPermaLink=\"true\">$link</guid>
						<pubDate>$posted</pubDate>
						<description>$content</description>
					</item>
			";
		}

		$title = $config->get_string('site_title');
		$base_href = make_http($config->get_string('base_href'));

		$version = VERSION;
		$xml = "<?xml version=\"1.0\" encoding=\"utf-8\"?>
				<rss version=\"2.0\">
					<channel>
						<title>$title</title>
						<description>The latest uploads to the image board</description>
						<link>$base_href</link>
						<generator>$version</generator>
						<copyright>(c) 2007 Shish</copyright>
						$data
					</channel>
				</rss>";
		$page->set_data($xml);
	}
	
	private function do_comments_rss() {
		global $config, $database, $page;
		
		$page->set_mode("data");
		$page->set_type("application/rss+xml");
		
		$comments = $database->get_all("
				SELECT
				users.id as user_id, users.name as user_name,
				comments.comment as comment, comments.id as comment_id,
				comments.image_id as image_id, comments.owner_ip as poster_ip,
				UNIX_TIMESTAMP(posted) AS posted_timestamp
				FROM comments
				LEFT JOIN users ON comments.owner_id=users.id
				ORDER BY comments.id DESC
				LIMIT 10
				");

		$data = "";
		foreach($comments as $comment) {
			$image_id = $comment['image_id'];
			$comment_id = $comment['comment_id'];
			$link = make_http(make_link("post/view/$image_id"));
			$owner = html_escape($comment['user_name']);
			$posted = date(DATE_RSS, $comment['posted_timestamp']);
			$comment = html_escape($comment['comment']);
			$content = html_escape("$owner: $comment");

			$data .= "
				<item>
					<title>$owner comments on $image_id</title>
					<link>$link</link>
					<guid isPermaLink=\"false\">$comment_id</guid>
					<pubDate>$posted</pubDate>
					<description>$content</description>
				</item>
			";
		}

		$title = $config->get_string('site_title');
		$base_href = make_http($config->get_string('base_href'));
		$version = $config->get_string('version');
		$xml = "<?xml version=\"1.0\" encoding=\"utf-8\"?>
				<rss version=\"2.0\">
					<channel>
						<title>$title</title>
						<description>The latest comments on the image board</description>
						<link>$base_href</link>
						<generator>$version</generator>
						<copyright>(c) 2007 Shish</copyright>
						$data
					</channel>
				</rss>";
				
		$page->set_data($xml);
	}
}
?>
