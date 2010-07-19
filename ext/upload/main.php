<?php
/*
 * Name: Uploader
 * Author: Shish
 * Description: Allows people to upload files to the website
 */

/*
 * DataUploadEvent:
 *   $user     -- the user uploading the data
 *   $tmpname  -- the temporary file used for upload
 *   $metadata -- info about the file, should contain at least "filename", "extension", "tags" and "source"
 *
 * Some data is being uploaded. Should be caught by a file handler.
 */
class DataUploadEvent extends Event {
	var $user, $tmpname, $metadata, $hash, $type, $image_id = -1;

	public function DataUploadEvent(User $user, $tmpname, $metadata) {
		assert(file_exists($tmpname));

		$this->user = $user;
		$this->tmpname = $tmpname;

		$this->metadata = $metadata;
		$this->metadata['hash'] = md5_file($tmpname);
		$this->metadata['size'] = filesize($tmpname);

		// useful for most file handlers, so pull directly into fields
		$this->hash = $this->metadata['hash'];
		$this->type = strtolower($metadata['extension']);
	}
}

class UploadException extends SCoreException {}

class Upload implements Extension {
	var $theme;
// event handling {{{
	public function receive_event(Event $event) {
		global $config, $database, $page, $user;
		if(is_null($this->theme)) $this->theme = get_theme_object($this);

		if($event instanceof InitExtEvent) {
			$config->set_default_int('upload_count', 3);
			$config->set_default_int('upload_size', '1MB');
			$config->set_default_bool('upload_anon', false);
			$config->set_default_string('upload_autoapprove', 'oam');
		}

		if($event instanceof PostListBuildingEvent) {
			if($this->can_upload($user)) {
				$free_num = @disk_free_space(realpath("./images/"));
				if($free_num === FALSE) {
					$is_full = false;
				}
				else {
					$is_full = $free_num < 100*1024*1024;
				}
		
				if($is_full) {
					$this->theme->display_full($page);
				}
				else {
					$this->theme->display_block($page);
				}
			}
		}

		if(($event instanceof PageRequestEvent) && $event->page_matches("upload")) {
			if(count($_FILES) + count($_POST) > 0) {
				$tags = Tag::explode($_POST['tags']);
				$source = isset($_POST['source']) ? $_POST['source'] : null;
				if($this->can_upload($user)) {
					$ok = true;
					foreach($_FILES as $file) {
						$ok = $ok & $this->try_upload($file, $tags, $source);
					}
					foreach($_POST as $name => $value) {
						if(substr($name, 0, 3) == "url" && strlen($value) > 0) {
							$ok = $ok & $this->try_transload($value, $tags, $source);
						}
					}

					$this->theme->display_upload_status($page, $ok);
				}
				else {
					$this->theme->display_permission_denied($page);
				}
			}
			else if(!empty($_GET['url'])) {
				if($this->can_upload($user)) {
					$url = $_GET['url'];
					$tags = array('tagme');
					if(!empty($_GET['tags']) && $_GET['tags'] != "null") {
						$tags = Tag::explode($_GET['tags']);
					}
					$ok = $this->try_transload($url, $tags, $url);
					$this->theme->display_upload_status($page, $ok);
				}
				else {
					$this->theme->display_permission_denied($page);
				}
			}
			else {
				if(!$is_full) {
					$this->theme->display_page($page);
				}
			}
		}
		if(($event instanceof PageRequestEvent) && $event->page_matches("bulk_zip")) {
			global $page;
			if(file_exists($_FILES['data']['tmp_name'])) {
				$td = "";
				for ($i=0; $i<6; $i++) { 
					$d=rand(1,30)%2; 
					$td .= $d ? chr(rand(65,90)) : chr(rand(48,57)); 
				}
				$tmp_dir = $_SERVER["DOCUMENT_ROOT"].'/data/'.$td.'/';
				$zip = new ZipArchive;
				$res = $zip->open($_FILES['data']['tmp_name']);
				if ($res === TRUE) {
					$zip->extractTo($tmp_dir);
					$zip->close();
					set_time_limit(0);
					$this->add_dir($tmp_dir);
					$this->theme->display_upload_results($page);
					$this->delete_directory($tmp_dir);
				} else {
					echo 'failed';
				}
			}
		}

		if($event instanceof SetupBuildingEvent) {
			$sb = new SetupBlock("Upload");
			$sb->position = 10;
			$sb->add_int_option("upload_count", "Max uploads: ");
			$sb->add_shorthand_int_option("upload_size", "<br>Max size per file: ");
			$sb->add_bool_option("upload_anon", "<br>Allow anonymous uploads: ");
			$sb->add_choice_option("transload_engine", array(
				"Disabled" => "none",
				"cURL" => "curl",
				"fopen" => "fopen",
				"WGet" => "wget"
			), "<br>Transload: ");
			
			$arr_autoaprove = array('Owner'=>'o', 'Owner, Admin'=>'oa', 'Owner, Admin, Mod'=>'oam', 'Owner, Admin, Mod, Contributor'=>'oamc', 'Owner, Admin, Mod, Contributor, User'=>'oamcu', 'Owner, Admin, Mod, Contributor, User, Guest'=>'oamcug');
			$sb->add_choice_option("upload_autoapprove", $arr_autoaprove, "<br>Auto approve for: ");
			$event->panel->add_block($sb);
		}
				
		if($event instanceof AdminBuildingEvent) {
			global $page;
			$this->theme->display_admin_block($page);
		}
		
		if(($event instanceof PageRequestEvent) && $event->page_matches("bulk_add")) {
			if($user->is_admin() && isset($_POST['dir'])) {
				set_time_limit(0);

				$this->add_dir($_POST['dir']);
				$this->theme->display_upload_results($page);
			}		
		}
	}
// }}}
// do things {{{
	private function can_upload($user) {
		global $config;
		return ($config->get_bool("upload_anon") || !$user->is_anon());
	}

	private function try_upload($file, $tags, $source) {
		global $page;
		global $config;

		if(empty($source)) $source = null;

		$ok = true;

		// blank file boxes cause empty uploads, no need for error message
		if(file_exists($file['tmp_name'])) {
			global $user;
			$pathinfo = pathinfo($file['name']);
			$metadata['filename'] = $pathinfo['basename'];
			$metadata['extension'] = $pathinfo['extension'];
			$metadata['tags'] = $tags;
			$metadata['source'] = $source;
			$event = new DataUploadEvent($user, $file['tmp_name'], $metadata);
			try {
				send_event($event);
				if($event->image_id == -1) {
					throw new UploadException("File type not recognised");
				}
				header("X-Shimmie-Image-ID: ".int_escape($event->image_id));
			}
			catch(UploadException $ex) {
				$this->theme->display_upload_error($page, "Error with ".html_escape($file['name']),
					$ex->getMessage());
				$ok = false;
			}
		}

		return $ok;
	}

	private function try_transload($url, $tags, $source) {
		global $page;
		global $config;

		$ok = true;

		if(empty($source)) $source = $url;

		// PHP falls back to system default if /tmp fails, can't we just
		// use the system default to start with? :-/
		$tmp_filename = tempnam("/tmp", "shimmie_transload");
		$filename = basename($url);

		if($config->get_string("transload_engine") == "fopen") {
			$fp = @fopen($url, "r");
			if(!$fp) {
				$this->theme->display_upload_error($page, "Error with ".html_escape($filename),
					"Error reading from ".html_escape($url));
				return false;
			}
			$data = "";
			$length = 0;
			while(!feof($fp) && $length <= $config->get_int('upload_size')) {
				$data .= fread($fp, 8192);
				$length = strlen($data);
			}
			fclose($fp);

			$fp = fopen($tmp_filename, "w");
			fwrite($fp, $data);
			fclose($fp);
		}

		if($config->get_string("transload_engine") == "curl") {
			$ch = curl_init($url);
			$fp = fopen($tmp_filename, "w");

			curl_setopt($ch, CURLOPT_FILE, $fp);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			curl_setopt($ch, CURLOPT_REFERER, $url);
			curl_setopt($ch, CURLOPT_USERAGENT, "Shimmie-".VERSION);

			curl_exec($ch);
			curl_close($ch);
			fclose($fp);
		}

		if($config->get_string("transload_engine") == "wget") {
			$ua = "Shimmie-".VERSION;
			$s_url = escapeshellarg($url);
			$s_tmp = escapeshellarg($tmp_filename);
			system("wget $s_url --output-document=$s_tmp --user-agent=$ua --referer=$s_url");
		}

		if(filesize($tmp_filename) == 0) {
			$this->theme->display_upload_error($page, "Error with ".html_escape($filename),
				"No data found -- perhaps the site has hotlink protection?");
			$ok = false;
		}
		else {
			global $user;
			$pathinfo = pathinfo($url);
			$metadata['filename'] = $pathinfo['basename'];
			$metadata['extension'] = $pathinfo['extension'];
			$metadata['tags'] = $tags;
			$metadata['source'] = $source;
			$event = new DataUploadEvent($user, $tmp_filename, $metadata);
			try {
				send_event($event);
			}
			catch(UploadException $ex) {
				$this->theme->display_upload_error($page, "Error with ".html_escape($url),
					$ex->getMessage());
				$ok = false;
			}
		}

		unlink($tmp_filename);

		return $ok;
	}
	
	public function add_dir($base, $subdir="") {
		global $page;

		if(!is_dir($base)) {
			$this->theme->add_status("Error", "$base is not a directory");
			return;
		}

		$list = "";

		foreach(glob("$base/$subdir/*") as $fullpath) {
			$fullpath = str_replace("//", "/", $fullpath);
			$shortpath = str_replace($base, "", $fullpath);

			if(is_link($fullpath)) {
				// ignore
			}
			else if(is_dir($fullpath)) {
				$this->add_dir($base, str_replace($base, "", $fullpath));
			}
			else {
				$pathinfo = pathinfo($fullpath);
				$tags = implode('.', explode('.', trim($pathinfo["basename"]), -1)); // Remove extension from filename
				$list .= "<br>".html_escape("$shortpath (".str_replace(" ", ", ", $tags).")... ");
				try{
					$this->add_image($fullpath, $pathinfo["basename"], $tags);
					$list .= "ok\n";
				}
				catch(Exception $ex) {
					$list .= "failed:<br>". $ex->getMessage();
				}
			}
		}

		if(strlen($list) > 0) {
			$this->theme->add_status("Adding $subdir", $list);
		}
	}

	
	private function add_image($tmpname, $filename, $tags) {
		assert(file_exists($tmpname));

		global $user;
		$pathinfo = pathinfo($filename);
		
		if(!array_key_exists('extension', $pathinfo)) {
			throw new UploadException("File has no extension");
		}
		
		$free_num = @disk_free_space(realpath("./images/"));
		if($free_num === FALSE) {
			$is_full = false;
		}
		else {
			$is_full = $free_num < 100*1024*1024;
		}
		
		if($is_full) {
			throw new UploadException("Upload failed; disk nearly full");
		}
				
		if(filesize($tmpname) > $config->get_int('upload_size')) {
			$size = to_shorthand_int(filesize($event->tmpname));
			$limit = to_shorthand_int($config->get_int('upload_size'));
			throw new UploadException("File too large ($size &gt; $limit)");
		}
		
		$metadata['filename'] = $pathinfo['basename'];
		$metadata['extension'] = $pathinfo['extension'];
		$metadata['tags'] = $tags;
		$metadata['source'] = null;
		$event = new DataUploadEvent($user, $tmpname, $metadata);
		send_event($event);
		if($event->image_id == -1) {
			throw new UploadException("1 File type not recognised");
		}
	}
	
	private function delete_directory($dirname) {
		if (is_dir($dirname))
			$dir_handle = opendir($dirname);
			if (!$dir_handle)
				return false;
			while($file = readdir($dir_handle)) {
				if ($file != "." && $file != "..") {
					if (!is_dir($dirname."/".$file)) unlink($dirname."/".$file);
					else delete_directory($dirname.'/'.$file);     
				}
			}
			closedir($dir_handle);
			rmdir($dirname);
			return true;
	}
// }}}
}

add_event_listener(new Upload(), 40); // early, so it can stop the DataUploadEvent before any data handlers see it
?>
