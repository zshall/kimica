<?php
/*
 * Name: Handle Flash
 * Author: Shish <webmaster@shishnet.org>
 * Description: Handle Flash files
 */
class FlashFileHandler extends SimpleExtension {

	public function onPageRequest($event) {
		global $page, $user;
		if($event->page_matches("post/regen")) {
			if($user->is_admin() && isset($_POST['image_id'])) {
				$image = Image::by_id($_POST['image_id']);
				
				if($image){
					if($this->supported_ext($image->ext)){
						$this->create_thumb($image->hash);
						$page->set_mode("redirect");
						$page->set_redirect(make_link("post/view/".$image->id));
					}
				}
			}
		}
	}
	
	public function onImageAdminBlockBuilding($event) {
		global $user;
		if($user->is_admin()) {
			if($this->supported_ext($event->image->ext)) {
				$event->add_part($this->theme->get_regen_html($event->image->id));
			}
		}
	}

	public function onDataUpload($event) {
		if($this->supported_ext($event->type) && $this->check_contents($event->tmpname)) {
		
			if(!warehouse_file($event->tmpname, $event->hash, $event->type)) return;
			
			$this->create_thumb($event->hash);
			$image = $this->create_image_from_data(warehouse_path("images", $event->hash), $event->metadata);
			
			if(is_null($image)) {
				throw new UploadException("Flash handler failed to create image object from data");
			}
			
			$iae = new ImageAdditionEvent($event->user, $image);
			send_event($iae);
			
			$event->image_id = $iae->image->id;
		}
	}
	

	public function onDisplayingImage($event) {
		if($this->supported_ext($event->image->ext)) {
			$this->theme->display_image($event->image);
		}
	}
	
	protected function create_thumb($hash) {
		if(!warehouse_thumb('contrib/handle_flash/thumb.jpg', $hash, 'jpg')) return;
	}

	protected function supported_ext($ext) {
		$exts = array("swf");
		return in_array(strtolower($ext), $exts);
	}

	private function create_image_from_data($filename, $metadata) {
		global $config;

		$image = new Image();

		$image->filesize  = $metadata['size'];
		$image->hash      = $metadata['hash'];
		$image->filename  = $metadata['filename'];
		$image->ext       = $metadata['extension'];
		$image->tag_array = Tag::explode($metadata['tags']);
		$image->source    = $metadata['source'];

		// redundant, since getimagesize() works on SWF o_O
//		$rect = $this->swf_get_bounds($filename);
//		if(is_null($rect)) {
//			return $null;
//		}
//		$image->width = $rect[1];
//		$image->height = $rect[3];

		if(!($info = getimagesize($filename))) return null;

		$image->width = $info[0];
		$image->height = $info[1];

		return $image;
	}

	protected function check_contents($file) {
		if(!file_exists($file)) return false;

		$fp = fopen($file, "r");
		$head = fread($fp, 3);
		fclose($fp);
		if(!in_array($head, array("CWS", "FWS"))) return false;

		return true;
	}

	private function str_to_binarray($string) {
		$binary = array();
		for($j=0; $j<strlen($string); $j++) {
			$c = ord($string[$j]);
			for($i=7; $i>=0; $i--) {
				$binary[] = ($c >> $i) & 0x01;
			}
		}
		return $binary;
	}

	private function binarray_to_int($binarray, $start=0, $length=32) {
		$int = 0;
		for($i=$start; $i<$start + $length; $i++) {
			$int = $int << 1;
			$int = $int + ($binarray[$i] == "1" ? 1 : 0);
		}
		return $int;
	}

	private function swf_get_bounds($filename) {
		$fp = fopen($filename, "r");
		$head = fread($fp, 3);
		$version = fread($fp, 1);
		$length = fread($fp, 4);

		if($head == "FWS") {
			$data = fread($fp, 16);
		}
		else if($head == "CWS") {
			$data = fread($fp, 128*1024);
			$data = gzuncompress($data);
			$data = substr($data, 0, 16);
		}

		$bounds = array();
		$rect_bin = $this->str_to_binarray($data);
		$nbits = $this->binarray_to_int($rect_bin, 0, 5);
		$bounds[] = $this->binarray_to_int($rect_bin, 5 + 0 * $nbits, $nbits) / 20;
		$bounds[] = $this->binarray_to_int($rect_bin, 5 + 1 * $nbits, $nbits) / 20;
		$bounds[] = $this->binarray_to_int($rect_bin, 5 + 2 * $nbits, $nbits) / 20;
		$bounds[] = $this->binarray_to_int($rect_bin, 5 + 3 * $nbits, $nbits) / 20;

		return $bounds;
	}
}
add_event_listener(new FlashFileHandler());
?>