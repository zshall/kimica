<?php
/*
 * Name: Image Votes
 * Author: Shish <webmaster@shishnet.org>
 * License: GPLv2
 * Description: Allow users to score images
 * Documentation:
 *  Each registered user may vote an image +1 or -1, the
 *  image's score is the sum of all votes.
 */

class VoteSetEvent extends Event {
	var $image_id, $user, $score;

	public function VoteSetEvent($image_id, $user, $score) {
		$this->image_id = $image_id;
		$this->user = $user;
		$this->score = $score;
	}
}

class Votes implements Extension {
	var $theme;

	public function receive_event(Event $event) {
		global $config, $database, $page, $user;
		if(is_null($this->theme)) $this->theme = get_theme_object($this);

		if($event instanceof InitExtEvent) {
			if($config->get_int("ext_votes_version", 0) < 1) {
				$this->install();
			}
		}

		if($event instanceof DisplayingImageEvent) {
			if(!$user->is_anon()) {
				$vote = $database->get_row("SELECT vote FROM image_votes WHERE user_id = ? AND image_id = ?", array($user->id, $event->image->id));
				$html = $this->theme->get_voter_html($event->image, $vote);
				$page->add_block(new Block("Image Votes", $html, "left", 20));
			}
		}

		if(($event instanceof PageRequestEvent) && $event->page_matches("votes")) {
			if(!$user->is_anon()) {
				$image_id = int_escape($_POST['image_id']);
				$char = $_POST['vote'];
				$score = null;
				if($char == "up") $score = 1;
				else if($char == "null") $score = 0;
				else if($char == "down") $score = -1;
				if(!is_null($score) && $image_id > 0) send_event(new VoteSetEvent($image_id, $user, $score));
				$page->set_mode("redirect");
				$page->set_redirect(make_link("post/view/$image_id"));
			}
		}

		if($event instanceof VoteSetEvent) {
			$this->add_vote($event->image_id, $user->id, $event->score);
		}

		if($event instanceof ImageDeletionEvent) {
			$database->execute("DELETE FROM image_votes WHERE image_id=?", array($event->image->id));
		}

		if($event instanceof ParseLinkTemplateEvent) {
			$event->replace('$score', $event->image->votes);
		}

		if($event instanceof SearchTermParseEvent) {
			$matches = array();
			if(preg_match("/^score(<|<=|=|>=|>)(\d+)$/", $event->term, $matches)) {
				$cmp = $matches[1];
				$score = $matches[2];
				$event->add_querylet(new Querylet("votes $cmp $score"));
			}
			if(preg_match("/^upvoted_by=(.*)$/", $event->term, $matches)) {
				$duser = User::by_name($matches[1]);
				if(is_null($duser)) {
					throw new SearchTermParseException(
							"Can't find the user named ".html_escape($matches[1]));
				}
				$event->add_querylet(new Querylet(
					"images.id in (SELECT image_id FROM image_votes WHERE user_id=? AND votes=1)",
					array($duser->id)));
			}
			if(preg_match("/^downvoted_by=(.*)$/", $event->term, $matches)) {
				$duser = User::by_name($matches[1]);
				if(is_null($duser)) {
					throw new SearchTermParseException(
							"Can't find the user named ".html_escape($matches[1]));
				}
				$event->add_querylet(new Querylet(
					"images.id in (SELECT image_id FROM image_votes WHERE user_id=? AND votes=-1)",
					array($duser->id)));
			}
			if(preg_match("/^upvoted_by_id=(\d+)$/", $event->term, $matches)) {
				$iid = int_escape($matches[1]);
				$event->add_querylet(new Querylet(
					"images.id in (SELECT image_id FROM image_votes WHERE user_id=? AND votes=1)",
					array($iid)));
			}
			if(preg_match("/^downvoted_by_id=(\d+)$/", $event->term, $matches)) {
				$iid = int_escape($matches[1]);
				$event->add_querylet(new Querylet(
					"images.id in (SELECT image_id FROM image_votes WHERE user_id=? AND votes=-1)",
					array($iid)));
			}
		}
	}

	private function install() {
		global $database;
		global $config;

		if($config->get_int("ext_votes_version") < 1) {
			$database->Execute("ALTER TABLE images ADD COLUMN votes INTEGER NOT NULL DEFAULT 0");
			$database->Execute("CREATE INDEX images_votes ON images(votes)");
			$database->create_table("image_votes", "
				image_id INTEGER NOT NULL,
				user_id INTEGER NOT NULL,
				vote INTEGER NOT NULL,
				UNIQUE(image_id, user_id),
				INDEX(image_id),
				FOREIGN KEY (image_id) REFERENCES images(id) ON DELETE CASCADE,
				FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
			");
			$config->set_int("ext_votes_version", 1);
		}
		if($config->get_int("ext_votes_version") < 2) {
			$database->Execute("CREATE INDEX image_votes__user_votes ON image_votes(user_id, vote)");
			$config->set_int("ext_votes_version", 2);
		}
	}

	private function add_vote($image_id, $user_id, $score) {
		global $database;
		$database->Execute(
			"DELETE FROM image_votes WHERE image_id=? AND user_id=?",
			array($image_id, $user_id));
		if($score != 0) {
			$database->Execute(
				"INSERT INTO image_votes(image_id, user_id, vote) VALUES(?, ?, ?)",
				array($image_id, $user_id, $score));
		}
		$database->Execute(
			"UPDATE images SET votes=(SELECT SUM(vote) FROM image_votes WHERE image_id=?) WHERE id=?",
			array($image_id, $image_id));
	}
}
add_event_listener(new Votes());
?>
