<?php

class WikiTheme extends Themelet {
	/*
	 * Show a page
	 *
	 * $page = the shimmie page object
	 * $wiki_page = the wiki page, has ->title and ->body
	 * $nav_page = a wiki page object with navigation, has ->body
	 */
	public function display_page(Page $page, WikiPage $wiki_page, $nav_page) { // $nav_page = WikiPage or null
		if(is_null($nav_page)) {
			$nav_page = new WikiPage();
			$nav_page->body = "";
		}

		$tfe = new TextFormattingEvent($nav_page->body);
		send_event($tfe);

		// only the admin can edit the sidebar
		global $user;
		if($user->is_admin()) {
			$tfe->formatted .= "<p>(<a href='".make_link("wiki/wiki:sidebar", "edit=on")."'>Edit</a>)";
		}

		$page->set_title(html_escape($wiki_page->title));
		$page->set_heading(html_escape($wiki_page->title));
		
		if($tfe->formatted){
			$page->add_block(new Block("Wiki Index", $tfe->formatted, "left", 10));
		}
		
		$this->display_nav();
		
		$page->add_block(new Block(html_escape($wiki_page->title), $this->create_display_html($wiki_page), "main", 10));
	}
	
	public function display_nav(){
		global $page;
		$html = '<form method="GET" action="'.make_link("wiki/list").'">
				 	<input type="text" autocomplete="off" value="" name="search" id="search_input" class="ac_input">
					<input type="submit" style="display: none;" value="Find">
				 </form>';
		$page->add_block(new Block("Navigation", $html, "left", 0));
	}
	
	public function display_changes($changes){
		global $page;
		$tfe = new TextFormattingEvent($changes);
		send_event($tfe);
		$page->add_block(new Block("Recent Changes", $tfe->formatted, "left", 20));
	}

	public function display_page_editor(Page $page, WikiPage $wiki_page) {
		$page->set_title(html_escape($wiki_page->title));
		$page->set_heading(html_escape($wiki_page->title));
		$page->add_block(new Block("Editor", $this->create_edit_html($wiki_page)));
	}
	
	public function display_wiki_pages($wikis, $search, $pageNumber, $totalPages){
		global $page;
		
		if(empty($search)){
			$pagination = $this->build_paginator("wiki/list", null, $pageNumber, $totalPages);
		}
		else{
			$pagination = $this->build_paginator("wiki/list/$search", null, $pageNumber, $totalPages);
		}
		
		$html = "<table><thead><tr><th>Title</th><th>Date</th><th>Updater</th></tr></thead><tbody>";
		
		$n = 0;
		foreach($wikis as $wiki){
			$oe = ($n++ % 2 == 0) ? "even" : "odd";
						
			$page_link = "<a href='".make_link("wiki/".html_escape($wiki["title"]))."'>".html_escape($wiki["title"])."</a> ";
			$user_link = "<a href='".make_link("account/profile/".$wiki['updater'])."'>".$wiki['updater']."</a> ";
			
			$html .= "<tr class='$oe'><td>".$page_link."</td><td>".autodate($wiki['date'])."</td><td>".$user_link."</td></tr>";
		}
		
		$html .= "</tbody></table>";
		
		$this->display_nav();
		
		$page->set_title("Wiki");
		$page->set_heading("Wiki");
		$page->add_block(new Block("Wiki", $html.$pagination, "main", 10));
	}

	protected function create_edit_html(WikiPage $page) {
		$h_title = html_escape($page->title);
		$u_title = url_escape($page->title);
		$i_revision = int_escape($page->revision) + 1;

		global $user;
		if($user->is_admin()) {
			$val = $page->is_locked() ? " checked" : "";
			$lock = "<br>Lock page: <input type='checkbox' name='lock'$val>";
		}
		else {
			$lock = "";
		}
		return "
			<form action='".make_link("wiki_admin/save")."' method='POST'>
				<input type='hidden' name='title' value='$h_title'>
				<input type='hidden' name='revision' value='$i_revision'>
				<textarea name='body' style='width: 100%' rows='20'>".html_escape($page->body)."</textarea>
				$lock
				<br><input type='submit' value='Save'>
			</form>
		";
	}

	protected function create_display_html(WikiPage $page) {
		$owner = $page->get_owner();

		$tfe = new TextFormattingEvent($page->body);
		send_event($tfe);

		global $user;
		$edit = "<table><tr>";
		$edit .= Wiki::can_edit($user, $page) ?
			"
				<td><form action='".make_link("wiki_admin/edit")."' method='POST'>
					<input type='hidden' name='title' value='".html_escape($page->title)."'>
					<input type='hidden' name='revision' value='".int_escape($page->revision)."'>
					<input type='submit' value='Edit'>
				</form></td>
			" :
			"";
		if($user->is_admin()) {
			$edit .= "
				<td><form action='".make_link("wiki_admin/delete_revision")."' method='POST'>
					<input type='hidden' name='title' value='".html_escape($page->title)."'>
					<input type='hidden' name='revision' value='".int_escape($page->revision)."'>
					<input type='submit' value='Delete This Version'>
				</form></td>
				<td><form action='".make_link("wiki_admin/delete_all")."' method='POST'>
					<input type='hidden' name='title' value='".html_escape($page->title)."'>
					<input type='submit' value='Delete All'>
				</form></td>
			";
		}
		$edit .= "</tr></table>";

		return "
			<div class='wiki-page'>
			$tfe->formatted
			<hr>
			<p class='wiki-footer'>
				Revision {$page->revision}
				by <a href='".make_link("account/profile/{$owner->name}")."'>{$owner->name}</a>
				at {$page->date}
				$edit
			</p>
			</div>
		";
	}
	
	public function display_tag_related($posts){
		global $page;
		if(!empty($posts)){
			$page->add_block(new Block("Related Posts", $this->build_table($posts, null), "main", 20));
		}
	}
}
?>
