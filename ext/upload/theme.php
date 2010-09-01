<?php

class UploadTheme extends Themelet {
	var $messages = array();

	public function display_block(Page $page) {
		$page->add_block(new Block("Upload", $this->build_upload_block(), "left", 20));
	}

	public function display_full(Page $page) {
		$page->add_block(new Block("Upload", "Disk nearly full, uploads disabled", "left", 20));
	}

	public function display_page(Page $page) {
		global $config,$user;
		$tl_enabled = ($config->get_string("transload_engine", "none") != "none");

		$upload_list = "";
		for($i=0; $i<$config->get_int('upload_count'); $i++) {
			$n = $i + 1;
			$width = $tl_enabled ? "35%" : "80%";
			$upload_list .= "
				<tr>
					<td>File $n</td>
					<td><input accept='image/jpeg,image/png,image/gif' id='data$i' name='data$i' type='file'></td>
				</tr>
			";
			if($tl_enabled) {
				$upload_list .= "
				<tr>
					<td>URL $n</td>
					<td><input id='url$i' name='url$i' type='text'></td>
				";
			}
			$upload_list .= "
				</tr>
			";
		}
		$max_size = $config->get_int('upload_size');
		$max_kb = to_shorthand_int($max_size);
		$html = "
			<script>
			$(document).ready(function() {
				$('#tag_box').DefaultValue('tagme');
				$('#tag_box').autocomplete('".make_link("api/internal/tag_list/complete")."', {
					width: 320,
					max: 15,
					highlight: false,
					multiple: true,
					multipleSeparator: ' ',
					scroll: true,
					scrollHeight: 300,
					selectFirst: false
				});
			});
			</script>
			<form enctype='multipart/form-data' action='".make_link("upload")."' method='POST'>
				<table id='large_upload_form'>
					$upload_list
					<tr><td>Tags</td><td colspan='3'><input id='tag_box' name='tags' type='text'></td></tr>
					<tr><td>Source</td><td colspan='3'><input name='source' type='text'></td></tr>
					<tr><td colspan='4'><input id='uploadbutton' type='submit' value='Upload'></td></tr>
				</table>
			</form>
			<small>(Max file size is $max_kb)</small><br />
			
		";

		if($tl_enabled) {
			$link = make_http(make_link("upload"));
			$title = "Upload to " . $config->get_string('site_title');
			$html .= '<p><a href="javascript:location.href=&quot;' .
				$link . '?url=&quot;+location.href+&quot;&amp;tags=&quot;+prompt(&quot;enter tags&quot;)">' .
				$title . '</a> (Drag & drop onto your bookmarks toolbar, then click when looking at an image)';
		}

		$page->set_title("Upload");
		$page->set_heading("Upload");
		$page->add_block(new NavBlock());
		$page->add_block(new Block("Upload", $html, "main", 10));
		
		
		if($user->is_admin()) {
			$bulk_zip = "<form enctype='multipart/form-data' action='".make_link("bulk_zip")."' method='POST'>";
			$bulk_zip .="<p>Upload a .ZIP file containing multiple images to add them all at once.</p>
						 <input accept='application/zip' id='data' name='data' type='file'>
						 <input id='uploadbutton' type='submit' value='Upload'>
						 </form>";
			$page->add_block(new Block("Bulk Upload", $bulk_zip, "main", 20));
		}
	}

	public function display_upload_status(Page $page, $ok) {
		if($ok) {
			$page->set_mode("redirect");
			$page->set_redirect(make_link());
		}
		else {
			$page->set_title("Upload Status");
			$page->set_heading("Upload Status");
			$page->add_block(new NavBlock());
		}
	}

	public function display_upload_error(Page $page, $title, $message) {
		$page->add_block(new Block($title, $message));
	}

	protected function build_upload_block() {
		global $config;

		$upload_list = "";
		for($i=0; $i<$config->get_int('upload_count'); $i++) {
			if($i == 0) $style = ""; // "style='display:visible'";
			else $style = "style='display:none'";
			$upload_list .= "<input accept='image/jpeg,image/png,image/gif' size='10' ".
				"id='data$i' name='data$i' $style onchange=\"$('#data".($i+1)."').show()\" type='file'>\n";
		}
		$max_size = $config->get_int('upload_size');
		$max_kb = to_shorthand_int($max_size);
		// <input type='hidden' name='max_file_size' value='$max_size' />
		return "
			<script>
			$(document).ready(function() {
				$('#tag_input').DefaultValue('tagme');
				$('#tag_input').autocomplete('".make_link("api/internal/tag_list/complete")."', {
					width: 320,
					max: 15,
					highlight: false,
					multiple: true,
					multipleSeparator: ' ',
					scroll: true,
					scrollHeight: 300,
					selectFirst: false
				});
			});
			</script>
			<form enctype='multipart/form-data' action='".make_link("upload")."' method='POST'>
				$upload_list
				<input id='tag_input' name='tags' type='text' autocomplete='off'>
				<input type='submit' value='Post'>
			</form>
			<div id='upload_completions' style='clear: both;'><small>(Max file size is $max_kb)</small></div>
			<noscript><a href='".make_link("upload")."'>Larger Form</a></noscript>
		";
	}
	
	/*
	 * Show a standard page for results to be put into
	 */
	public function display_upload_results(Page $page) {
		$page->set_title("Adding folder");
		$page->set_heading("Adding folder");
		$page->add_block(new NavBlock());
		foreach($this->messages as $block) {
			$page->add_block($block);
		}
	}
	
	/*
	 * Add a section to the admin page. This should contain a form which
	 * links to bulk_add with POST[dir] set to the name of a server-side
	 * directory full of images
	 */
	
	public function display_admin_block(Page $page) {
		$html = "
			Add a folder full of images; any subfolders will have their names
			used as tags for the images within.
			<br>Note: this is the folder as seen by the server -- you need to
			upload via FTP or something first.

			<p><form action='".make_link("bulk_add")."' method='POST'>
				Directory to add: <input type='text' name='dir' size='40'>
				<input type='submit' value='Add'>
			</form>
		";
		$page->add_block(new Block("Bulk Add", $html));
	}
	
	public function add_status($title, $body) {
		$this->messages[] = new Block($title, $body);
	}
}
?>
