<?php
class AdminTheme extends Themelet {

	public function display_sidebar(){
		global $page;
		$html = "<a href='".make_link("admin/alerts")."'>Alerts</a><br><a href='".make_link("admin/posts")."'>Post Tools</a><br><a href='".make_link("admin/bans/posts")."'>Post Bans</a><br><br><a href='".make_link("admin/database")."'>Database Tools</a>";
		$page->add_block(new Block("Tools", $html, "left", 0));
	}
	
	public function display_alerts($alerts){
		global $page;
		
		$sorter = '<script>
			$(document).ready(function() {
				$("#ads").tablesorter();
			});
			</script>';
		
		$html = '<form action="'.make_link("admin/alerts/action").'" method="POST">
					<table id="ads">
					<thead>
						<tr>
							<th>Id</th><th>Section</th><th>Message</th><th>Date</th><th>Status</th><th>Alerter</th><th>Reviewer</th><th>Action</th>
						</tr>
					</thead>
					<tbody>';
					
		$n = 0;
		foreach($alerts as $alert){
			
			$reviewer = $alert['reviewer'];
			if($alert['reviewer'] == "Guest"){
				$reviewer = "None";
			}
		
			$oe = ($n++ % 2 == 0) ? "even" : "odd";
			$html .= '<tr class="'.$oe.'">
						<td>'.$alert['id'].'</td>
						<td><a href="'.make_link("admin/alerts/view/".$alert['id']).'">'.$alert['section'].'</a></td>
						<td>'.$alert['message'].'</td>
						<td>'.autodate($alert['created_at']).'</td>
						<td>'.Admin::alert_to_human($alert['status']).'</td>
						<td>'.$alert['alerter'].'</td>
						<td>'.$reviewer.'</td>
						<td><input name="id[]" type="checkbox" value="'.$alert["id"].'" /></td>
					  </tr>';
		}
		$html .= '</tbody>
				</table>';
				
		$html .="<input type='submit' name='action' value='Solved'>
					 <input type='submit' name='action' value='Delete'><form>";
	
		if(!$alerts){
			$html = "There is no alerts to show.";
		}
	
		$page->set_title("Alerts");
		$page->set_heading("Alerts");
		$page->add_block(new Block("Alerts", $sorter.$html, "main", 0));
	}
	
	public function display_tag_tools() {
		global $page;
		$html = "
			<p><form action='".make_link("admin/database")."' method='POST'>
				<select name='action'>
					<option value='lowercase all tags'>All tags to lowercase</option>
					<option value='recount tag use'>Recount tag use</option>
					<option value='purge unused tags'>Purge unused tags</option>
					<option value='database dump'>Download database contents</option>
					<option value='convert to innodb'>Convert database to InnoDB (MySQL only)</option>
				</select>
				<input type='submit' value='Go'>
			</form>
		";
		$page->set_title("Database Tools");
		$page->set_heading("Database Tools");
		$page->add_block(new Block("Database Tools", $html));
	}
	
	public function display_bulk_tag_editor() {
		global $page;
		$html = "
		<form action='".make_link("tags/replace/tags")."' method='POST'>
			<table style='width: 300px;'>
				<tr><td>Search</td><td><input type='text' name='search'></tr>
				<tr><td>Replace</td><td><input type='text' name='replace'></td></tr>
			</table>
			<input type='submit' value='Set Tags'>
		</form>
		";
		$page->set_title("Post Tools");
		$page->set_heading("Post Tools");
		$page->add_block(new Block("Mass Tag Edit", $html, "main", 10));
	}
	
	public function display_bulk_source_editor() {
		global $page;
		$html = "
		<form action='".make_link("tags/replace/source")."' method='POST'>
			<table style='width: 300px;'>
				<tr><td>Search</td><td><input type='text' name='search'></tr>
				<tr><td>Source</td><td><input type='text' name='source'></td></tr>
			</table>
			<input type='submit' value='Set Sources'>
		</form>
		";
		$page->set_title("Post Tools");
		$page->set_heading("Post Tools");
		$page->add_block(new Block("Mass Source Edit", $html, "main", 20));
	}
	
	public function display_bulk_rater() {
		global $page;
		$html = "
			<form action='".make_link("admin/bulk_rate")."' method='POST'>
				<table style='width: 300px'>
					<tr>
						<td>Search</td>
						<td>
							<input type='text' name='query'>
						</td>
					</tr>
					<tr>
						<td>Rating</td>
						<td>
							<select name='rating'>
								<option value='s'>Safe</option>
								<option value='q'>Questionable</option>
								<option value='e'>Explicit</option>
								<option value='u'>Unrated</option>
							</select>
						</td>
					</tr>
				</table>
				<input type='submit' value='Set Ratings'>
			</form>
		";
		$page->add_block(new Block("Mass Rating Edit", $html, "main", 30));
	}
	
	public function display_bulk_uploader(){
		global $page;
		$bulk_zip = "<form enctype='multipart/form-data' action='".make_link("bulk_zip")."' method='POST'>";
		$bulk_zip .="<p>Upload a .ZIP file containing multiple images to add them all at once.</p>
					 <table style='width: 300px'>
						<tr><td>Zip</td><td> <input accept='application/zip' id='data' name='data' type='file'></tr>
					 </table>
					 <input id='uploadbutton' type='submit' value='Upload'>
					 </form>";
		$page->add_block(new Block("Mass Post Uploader", $bulk_zip, "main", 40));
	}
	
	public function display_post_bans($bans) {
		global $page;
		$h_bans = "";
		$n = 0;
		foreach($bans as $ban) {
			$oe = ($n++ % 2 == 0) ? "even" : "odd";
			$h_bans .= '
				<tr class="'.$oe.'">
					<td>'.$ban["id"].'</td>
					<td>'.$ban["hash"].'</td>
					<td>'.$ban["reason"].'</td>
					<td><input name="hash[]" type="checkbox" value="'.$ban["hash"].'" /></td>
				</tr>
			';
		}
		$html = "
			<script>
			$(document).ready(function() {
				$(\"#image_bans\").tablesorter();
			});
			</script>
			<form action=".make_link("admin/bans/posts/action")." method='POST'>
			<table id='image_bans' class='zebra'>
				<thead><th>ID</th><th>Hash</th><th>Reason</th><th>Action</th></thead>
				$h_bans
			</table>
			<input type='submit' name='action' value='Remove'>
			</form>
		";
		
		if(!$bans){
			$html = "There is no posts to show.";
		}
		
		//$pagination = $this->build_paginator("image_hash_ban/list", null, $page_number, $page_count);
		
		$page->set_title("Post Bans");
		$page->set_heading("Post Bans");
		$page->add_block(new Block("Post Bans", $html));
	}
}
?>