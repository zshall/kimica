<?php
/*
 * Name: Extended Api
 * Author: Sein Kraft <seinkraft@hotmail.com>
 * License: GPLv2
 * Description: Really extended api
 * Documentation:
 */
class ExtendedApi extends SimpleExtension {

	public function onInitExt($event) {
		global $config, $database;
		
		if($config->get_int("ext_extended_api", 0) < 1){
			$database->create_table("api", "
				id SCORE_AIPK,
				user_id INTEGER NOT NULL,
				user_ip SCORE_INET NOT NULL,
				downloads INTEGER NOT NULL,
				acess DATETIME DEFAULT NULL,
				baned SCORE_BOOL NOT NULL DEFAULT SCORE_BOOL_N,
				INDEX (user_id),
				FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
			");
			$config->set_int("ext_extended_api", 1);
		}
	}
	
	public function onSetupBuilding(SetupBuildingEvent $event) {
		$sb = new SetupBlock("Extended Api");
		$sb->add_text_option("ext_api_version", "Version: ");
		$sb->add_text_option("ext_api_update", "<br>Update: ");
		$sb->add_text_option("ext_api_downloads", "<br><br>Max Downloads: ");
		$event->panel->add_block($sb);
	}
	
	public function onPageRequest($event){
		global $page, $user;
				
		if($event->page_matches("api")){
			switch($event->get_arg(0)){
				case "add_post":
				{
					$this->setPost();
					break;
				}
				case "check_post":
				{
					$this->checkPost();
					break;
				}
				case "get_posts":
				{
					$this->getPosts();
					break;
				}
				case "get_file":
				{
					$this->getFile();
					break;
				}
				case "get_tags":
				{
					$this->getTags();
					break;
				}
				case "set_favorite":
				{
					$this->setFavorite();
					break;
				}
				case "set_score":
				{
					$this->setScore();
					break;
				}
				case "check_login":
				{
					$this->checkLogin();
					break;
				}
				case "get_sync":
				{
					$this->getSync();
					break;
				}
				case "get_update":
				{
					$this->getUpdate();
					break;
				}
			}
		}
	}
	
	private function getAuth(){
		global $config, $database, $user;
	
		if(isset($_REQUEST['username']) && isset($_REQUEST['password'])){
			// Get this user from the db, if it fails the user becomes anonymous
			// Code borrowed from /ext/user
			$name = $_REQUEST['username'];
			$hash = $_REQUEST['password'];
			//$hash = md5( strtolower($name) . $pass );
			$duser = User::by_name_and_hash($name, $hash);
			if(!is_null($duser)) {
				$user = $duser;
			} else {
				$user = User::by_id($config->get_int("anon_id", 0));
			}
		}
	}
	
	private function setPost(){
		global $page, $config, $database, $user;
		
		$page->set_mode("data");
		$page->set_type("application/xml");
		
		$this->getAuth();
		
		if($config->get_bool("upload_anon") || !$user->is_anon()){
			$file = NULL;
			$filename = "";
			$source = "";
			
			if(isset($_FILES['file'])){	
				// A file was POST'd in
				$file = $_FILES['file']['tmp_name'];
				$filename = $_FILES['file']['name'];
				// If both a file is posted and a source provided, I'm assuming source is the source of the file
				if(isset($_REQUEST['source']) && !empty($_REQUEST['source'])){
					$source = $_REQUEST['source'];
				}else{
					$source = null;
				}
			}elseif(isset($_FILES['post'])){
				$file = $_FILES['post']['tmp_name']['file'];
				$filename = $_FILES['post']['name']['file'];
				
				if(isset($_REQUEST['post']['source']) && !empty($_REQUEST['post']['source'])){
					$source = $_REQUEST['post']['source'];
				}else{
					$source = null;
				}
			}elseif(isset($_REQUEST['url']) || isset($_REQUEST['post']['url'])){	
				// A url was provided
				$url = isset($_REQUEST['url']) ? $_REQUEST['url'] : $_REQUEST['post']['url'];
				$source = isset($_REQUEST['source']) ? $_REQUEST['source'] : $_REQUEST['post']['source'];
				$tmp_filename = tempnam("/tmp", "shimmie_transload");
				// Are we using fopen wrappers or curl?
				if($config->get_string("transload_engine") == "fopen"){
					$fp = fopen($url, "r");
					
					if(!$fp){
						header("HTTP/1.0 409 Conflict");
						header("X-Danbooru-Errors: fopen read error");
					}
					
					$data = "";
					$length = 0;
					
					while(!feof($fp) && $length <= $config->get_int('upload_size')){
						$data .= fread($fp, 8192);
						$length = strlen($data);
					}
					
					fclose($fp);
					$fp = fopen($tmp_filename, "w");
					fwrite($fp, $data);
					fclose($fp);
				}
				
				if($config->get_string("transload_engine") == "curl"){
					$ch = curl_init($url);
					$fp = fopen($tmp_filename, "w");
					curl_setopt($ch, CURLOPT_FILE, $fp);
					curl_setopt($ch, CURLOPT_HEADER, 0);
					curl_exec($ch);
					curl_close($ch);
					fclose($fp);
				}
				
				$file = $tmp_filename;
				$filename = basename($url);
			}else{	
				// Nothing was specified at all
				header("HTTP/1.0 200 Ok");
				
				$xml = "<status>\n";
				$xml .= "<info code=\"211\" message=\"No imput file.\"/>\n";
				$xml .= "</status>";
			
				$page->set_data($xml);
				
				return;
			}
			// Get tags out of url
			$posttags = Tag::explode(isset($_REQUEST['tags']) ? $_REQUEST['tags'] : $_REQUEST['post']['tags']);
			$hash = md5_file($file);
			
			// Was an md5 supplied? Does it match the file hash?
			if(isset($_REQUEST['md5'])){
			
				if(strtolower($_REQUEST['md5']) != $hash){
					header("HTTP/1.0 200 Ok");
					
					$xml = "<status>\n";
					$xml .= "<info code=\"243\" message=\"MD5 Missmatch.\"/>\n";
					$xml .= "</status>";
					
					$page->set_data($xml);
					
					return;
				}
			}
	
			
			switch($_REQUEST['rating']){
				case 's': 
				{
					$rating = "s";
					break;
				}
				case 'q': 
				{
					$rating = "q";
					break;
				}
				case 'e': 
				{
					$rating = "e";
					break;
				}
				default: 
				{
					$rating = "q";
					break;
				}
			}
			
			$fileinfo = pathinfo($filename);
			
			if(isset($_REQUEST['filename']))
			{
				$metadata['filename'] = $_REQUEST['filename'];
			}else{
				$metadata['filename'] = $fileinfo['basename'];
			}
			
			$metadata['extension'] = $fileinfo['extension'];
			$metadata['tags'] = $posttags;
			$metadata['source'] = $source;
			
			try {
				send_event(new DataUploadEvent($user, $file, $metadata));
				// If it went ok, grab the id for the newly uploaded image and pass it in the header
				$newimg = Image::by_hash($hash);
				$newid = make_http(make_link("post/view/" . $newimg->id));
				
				// we send the rating event
				send_event(new RatingSetEvent($newimg, $user, $rating));
				
				//IF SUCESS ADD UPLOAD TO API DATABASE
				$this->downloadUpdate($user->id, "upload");
				
				// Did we POST or GET this call?
				if($_SERVER['REQUEST_METHOD'] == 'POST')
				{
					header("HTTP/1.0 200 Ok");
					
					$xml = "<status>\n";
					$xml .= "<info code=\"200\" message=\"File has been posted with id: $newimg->id\" id=\"$newimg->id\" />\n";
					$xml .= "</status>";
					
					$page->set_data($xml);
				}else{
					header("HTTP/1.0 200 Ok");
					
					$xml = "<status>\n";
					$xml .= "<info code=\"200\" message=\"File has been posted with id: $newimg->id\" id=\"$newimg->id\" />\n";
					$xml .= "</status>";
					
					$page->set_data($xml);
				}
			}
			catch(UploadException $ex) {
				header("HTTP/1.0 200 Ok");
										
				$xml = "<status>\n";
				$xml .= "<info code=\"299\" message=\"".$ex->getMessage()."\"/>\n";
				$xml .= "</status>";
					
				$page->set_data($xml);
				
				return;
			}
		}else{
			header("HTTP/1.0 200 Ok");
			
			$xml = "<status>\n";
			$xml .= "<info code=\"145\" message=\"Authentication error.\"/>\n";
			$xml .= "</status>";
			
			$page->set_data($xml);
			return;
		}
	}
	
	private function getFile(){
		global $page, $user;
		
		$page->set_mode("data");
		$page->set_type("application/xml");
		
		$this->getAuth();
		
		if(!$user->is_anon()){
			if($this->downloadCheck($user->id)){
				
				if(isset($_GET['id'])){
					$image = Image::by_id($_GET['id']);
					
					if(!is_null($image)) {				
						$file = $image->get_image_filename();
						
						$page->set_type($image->get_mime_type());
						$page->set_data(file_get_contents($file));
						
						$this->downloadUpdate($user->id, "download");
					}else{
						$xml = "<status>\n";
						$xml .= "<info code=\"854\" message=\"Image does not Exists.\"/>\n";
						$xml .= "</status>";
						
						$page->set_data($xml);
					}
				}else{
					$xml = "<status>\n";
					$xml .= "<info code=\"200\" message=\"User can continue with downloads.\"/>\n";
					$xml .= "</status>";
								
					$page->set_data($xml);
				}
				
			}else{
				$xml = "<status>\n";
				$xml .= "<info code=\"145\" message=\"Yor has reached your daily limit.\"/>\n";
				$xml .= "</status>";
				
				$page->set_data($xml);
			}
		}else{			
			$xml = "<status>\n";
			$xml .= "<info code=\"145\" message=\"Authentication error.\"/>\n";
			$xml .= "</status>";
			
			$page->set_data($xml);
		}
	}
	
	private function checkPost(){
		global $page;
		
		$page->set_mode("data");
		$page->set_type("application/xml");
		
		if(isset($_GET['md5'])){
			$existing = Image::by_hash($_GET['md5']);
			if(!is_null($existing)) {
				header("HTTP/1.0 200 Ok");
				
				$xml = "<status>\n";
				$xml .= "<info code=\"237\" message=\"Image already exists with id: $existing->id\" id=\"$existing->id\" />\n";
				$xml .= "</status>";
				
				$page->set_data($xml);
			}else{
				header("HTTP/1.0 200 Ok");
			
				$xml = "<status>\n";
				$xml .= "<info code=\"230\" message=\"Image doesn't exists.\"/>\n";
				$xml .= "</status>";
			
				$page->set_data($xml);
			}
		}
	}
	
	private function getPosts(){
		global $page, $config, $database, $user;
		
		$page->set_mode("data");
		$page->set_type("application/xml");
		
		$this->getAuth();
		
		if(isset($_GET['md5'])){
					
			$md5list = explode(",",$_GET['md5']);
			foreach($md5list as $md5){
				$results[] = Image::by_hash($md5);
				$count = 1;
			}
		}elseif(isset($_GET['id'])){
			$idlist = explode(",",$_GET['id']);
		
			foreach($idlist as $id){
				$results[] = Image::by_id($id);
				$count = 1;
			}
		}else{
			$limit = isset($_GET['limit']) ? int_escape($_GET['limit']) : 100;
			$start = isset($_GET['offset']) ? int_escape($_GET['offset']) : 0;
			$tags = isset($_GET['tags']) ? Tag::explode($_GET['tags']) : array();
			$results = Image::find_images($start, $limit, $tags);
			$count =  Image::count_images($tags);
		}
	
		// Now we have the array $results filled with Image objects
		// Let's display them
		$xml = "<posts count=\"$count\">\n";
		foreach($results as $img){
			// Sanity check to see if $img is really an image object
			// If it isn't (e.g. someone requested an invalid md5 or id), break out of the this
			if(!is_object($img))
				continue;
			$taglist = $img->get_tag_list();
			$owner = $img->get_owner();
							
			$xml .= "<post id=\"$img->id\" poster=\"$owner->name\" posted=\"$img->posted\" filename=\"" . $this->xmlspecialchars($img->filename) . "\" filesize=\"$img->filesize\" hash=\"$img->hash\" ext=\"$img->ext\" width=\"$img->width\" height=\"$img->height\" tags=\"" . $this->xmlspecialchars($taglist) . "\" source=\"" . $this->xmlspecialchars($img->source) . "\" rating=\"$img->rating\" score=\"$img->numeric_score\"  favorites=\"$img->favorites\"/>\n";
		}
		$xml .= "</posts>";
		$page->set_data($xml);
	}
	
	private function getTags(){
		global $page, $config, $database, $user;
		
		$page->set_mode("data");
		$page->set_type("application/xml");
		
		$this->getAuth();
		
		if(isset($_GET['id'])){
			$idlist = explode(",",$_GET['id']);
			
			foreach($idlist as $id){
				$sqlresult = $database->execute("SELECT id,tag,count FROM image_tags JOIN tags ON image_tags.tag_id = tags.id WHERE image_id = ? ", array($id));
						
				foreach($sqlresult as $result){
					$results[] = array($result['count'], $result['tag'], $result['id']);
				}
			}
		}elseif(isset($_GET['name'])){
			$namelist = explode(",",$_GET['name']);
			
			foreach($namelist as $name){
				$sqlresult = $database->execute("SELECT id,tag,count FROM image_tags JOIN tags ON image_tags.tag_id = tags.id WHERE tag = ? ", array($name));
				
				foreach($sqlresult as $result){
					$results[] = array($result['count'], $result['tag'], $result['id']);
				}
			}
		}else{
			$start = isset($_GET['after_id']) ? int_escape($_GET['offset']) : 0;
			$sqlresult = $database->execute("SELECT id,tag,count FROM tags WHERE count > 0 AND id >= ? ORDER BY tag ASC",array($start));
			
			while(!$sqlresult->EOF){
				$results[] = array($sqlresult->fields['count'], $sqlresult->fields['tag'], $sqlresult->fields['id']);
				$sqlresult->MoveNext();
			}
		}
	
				// Tag results collected, build XML output
		$xml = "<tags>\n";
		foreach($results as $tag){
			$xml .= "<tag id=\"$tag[2]\" tag=\"" . $this->xmlspecialchars($tag[1]) . "\" count=\"$tag[0]\"/>\n";
		}
		$xml .= "</tags>";
		
		$page->set_data($xml);
	}
	
	private function setFavorite(){
		global $page, $config, $database, $user;
		
		$page->set_mode("data");
		$page->set_type("application/xml");
		
		$this->getAuth();
		
		if(!$user->is_anon()){
			
			if(isset($_REQUEST['id']) && !empty($_REQUEST['id'])){
				$id = $_GET['id'];
				$image = Image::by_id($id);
			}
					
			if(isset($_REQUEST['hash']) && !empty($_REQUEST['hash'])){
				$hash = $_GET['hash'];
				$image = Image::by_hash($hash);
			}
			
			if($_GET['set'] == "set"){
				$set = TRUE;
			}else{
				$set = FALSE;
			}
			
			if(!is_null($image)){
				
				send_event(new FavoriteSetEvent($image->id, $user, $set));
				
				if($set){
					$setted = "Added";
				}else{
					$setted = "Removed";
				}
				
				$xml = "<status>\n";
				$xml .= "<info code=\"500\" message=\"Favorite $setted.\"/>\n";
				$xml .= "</status>";
	
				$page->set_data($xml);
			}else{
				$xml = "<status>\n";
				$xml .= "<info code=\"145\" message=\"Image does not exist.\"/>\n";
				$xml .= "</status>";
	
				$page->set_data($xml);
			}
			
		}else{
			$xml = "<status>\n";
			$xml .= "<info code=\"145\" message=\"Authentication error.\"/>\n";
			$xml .= "</status>";
			
			$page->set_data($xml);
		}
	}
	
	private function setScore(){
		global $page, $config, $database, $user;
		
		$page->set_mode("data");
		$page->set_type("application/xml");
		
		$this->getAuth();
		
		if(!$user->is_anon()){
			
			if(isset($_REQUEST['id']) && !empty($_REQUEST['id'])){
				$id = $_GET['id'];
				$image = Image::by_id($id);
			}
									
			if(!is_null($image)) {
						
				if(isset($_REQUEST['score']) && !empty($_REQUEST['score'])){
					$char = $_GET['score'];
							
					if($char == "up"){
						$score = 1;
						send_event(new VoteSetEvent($image->id, $user, $score));
					}elseif($char == "down"){
						$score = -1;
						send_event(new VoteSetEvent($image->id, $user, $score));
					}
				}
							
				$xml = "<status>\n";
				$xml .= "<info code=\"600\" message=\"Score Setted.\"/>\n";
				$xml .= "</status>";
		
				$page->set_data($xml);
			}else{
				$xml = "<status>\n";
				$xml .= "<info code=\"145\" message=\"Image does not exist.\"/>\n";
				$xml .= "</status>";
	
				$page->set_data($xml);
			}
					
		}else{
			$xml = "<status>\n";
			$xml .= "<info code=\"145\" message=\"Authentication error.\"/>\n";
			$xml .= "</status>";
					
			$page->set_data($xml);
		}
	}
	
	// Addon Functions
	private function checkLogin(){
		global $page, $database;
	
		$page->set_mode("data");
		$page->set_type("application/xml");
	
		$name = $_REQUEST['username'];
		$hash = $_REQUEST['password'];
		
		$username = $database->db->GetOne("SELECT * FROM users WHERE name = ?", array($name));
		
		if ($username == 0){
			header("HTTP/1.0 200 Ok");
			
			$xml = "<status>\n";
			$xml .= "<info code=\"160\" message=\"Account doesn't exists.\"/>\n";
			$xml .= "</status>";
		
			$page->set_data($xml);
		}else{
			$account = $database->db->GetOne("SELECT * FROM users WHERE name = ? AND pass = ?", array($name, $hash));
			
			if ($account == 0){
				header("HTTP/1.0 200 Ok");
				
				$xml = "<status>\n";
				$xml .= "<info code=\"133\" message=\"Wrong password.\"/>\n";
				$xml .= "</status>";
				
				$page->set_data($xml);
			}else{
				header("HTTP/1.0 200 Ok");
				
				$xml = "<status>\n";
				$xml .= "<info code=\"100\" message=\"Sucessfuly logged in.\"/>\n";
				$xml .= "</status>";
				
				$page->set_data($xml);
			}
		}
	}
	
	private function getSync(){
		global $config, $database, $user;
	
		$page->set_mode("data");
		$page->set_type("application/xml");
		
		$this->authenticate_user();
	
		if(!$user->is_anon()){
			$scores = $database->get_all("SELECT image_id, score FROM image_votes WHERE user_id = ?", array($user->id));
			$favorites = $database->get_all("SELECT image_id FROM user_favorites WHERE user_id = ?", array($user->id));
	
			$xml = "<user>\n";
				$xml .= "<scores>\n";
				foreach($scores as $score){
					$xml .= "<score image_id=\"".$score['image_id']."\" image_score=\"".$score['score']."\"/>\n";
				}
				$xml .= "</scores>";
		
				$xml .= "<favorites>\n";
				foreach($favorites as $favorite){
					$xml .= "<favorite image_id=\"".$favorite['image_id']."\"/>\n";
					}
				$xml .= "</favorites>";
			$xml .= "</user>";
			
			$page->set_data($xml);
		}else{
			$xml = "<status>\n";
			$xml .= "<info code=\"145\" message=\"Authentication error.\"/>\n";
			$xml .= "</status>";
					
			$page->set_data($xml);
		}
	}
	
	private function getUpdate(){
		global $page, $config;
		
		$page->set_mode("data");
		$page->set_type("application/xml");
		
		$version = $config->get_string("ext_api_version");
		$update = $config->get_string("ext_api_update");
		
		$hash = md5_file($update);
		
		$xml = "<suite>\n";
		$xml .= "<installer version=\"$version\" hash=\"$hash\" url=\"$update\" />\n";
		$xml .= "</suite>";
		
		$page->set_data($xml);
	}
	
	private function xmlspecialchars($text){
		$text = str_replace ('&',  '&amp;', $text );
		$text = str_replace ('\\', '', $text );
		$text = str_replace ('\'', '', $text );
		$text = str_replace ('\"', '&quot;', $text );
		$text = str_replace ('<', '&lt;', $text );
		return str_replace ('>', '&gt;', $text );
		//return htmlspecialchars($text, ENT_QUOTES);
		//return str_replace('&#039;', '&apos;', htmlspecialchars($text, ENT_QUOTES));
	}
	
	//Download Control Functions
	private function downloadCreate($user_id){
		global $config, $database;
			
			$user_ip = $_SERVER['REMOTE_ADDR'];
			
			$downloads = $config->get_int("ext_api_downloads", 20);
			
			$result = $database->db->GetOne("SELECT COUNT(*) FROM api WHERE user_id = ?", array($user_id));
			
			if ($result == 0) {
				$database->execute("
									INSERT INTO api 
										(user_id, user_ip, downloads, acess) 
									VALUES (?, ?, ?, now())", 
										array($user_id, $user_ip, $downloads)); 
			}
	}
	
	private function downloadUpdate($user_id, $process){
		global $database;
			
		$user_ip = $_SERVER['REMOTE_ADDR'];
		
		//WE ADD TO DATABASE THE UPLOAD TO USE AS BONUS DOWNLOAD.
		if ($process == "upload"){
			$database->Execute("UPDATE api SET user_ip = ?, downloads = downloads + 1, acess = now() WHERE user_id = ?", array($user_ip, $user_id));
		} elseif ($process == "download"){
			$database->Execute("UPDATE api SET user_ip = ?, downloads = downloads - 1, acess = now() WHERE user_id = ?", array($user_ip, $user_id));
		}
	}
	
	private function downloadCalculate($user_id){ //Used on downloadCheck
		global $config, $database, $user;
		
		$max_downloads = $config->get_int("ext_api_downloads", 20);
		
		$api = $database->get_row("SELECT acess FROM api WHERE user_id = ?", array($user_id));
		
		$past = strtotime(date("Ymd", strtotime($api['acess'])));
	
		$present = strtotime(date("Ymd"));
		
		$days = ($present - $past) / 86400;
		
		$amount = $days * $max_downloads; 
		
		return $amount;
	}
	
	private function downloadCheck($user_id){
		global $database, $user;
			
		$user = User::by_id($user_id);
		
		$this->downloadCreate($user->id);
	
		$api = $database->get_row("SELECT downloads, acess, baned FROM api WHERE user_id = ?", array($user->id));
		
		$past = strtotime(date("Ymd", strtotime($api['acess'])));
		$present = strtotime(date("Ymd"));
		
		$downloads = $api['downloads'];
		$baned = $api['baned'];
		
		if($past < $present){
			$downloads = $this->downloadCalculate($user->id);
		
			if($downloads > 0){
				$database->Execute("UPDATE api SET downloads = downloads + ?, acess = now() WHERE user_id = ?", array($downloads, $user->id));
			}
		}
			
		if (($downloads > 0) && ($baned == "N") || ($user->is_admin())) {
			return true;
		}else{
			return false;
		}
	}

}
add_event_listener(new ExtendedApi());
?>