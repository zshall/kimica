<?php
/**
 * Name: Handle Pixel
 * Author: Shish <webmaster@shishnet.org>
 * Description: Handle JPEG, PNG, GIF, etc files
 */

class PixelFileHandler extends SimpleExtension {
	
	public function onDataUpload($event){
		if($this->supported_ext($event->type) && $this->check_contents($event->tmpname)){
		
			if(!warehouse_file($event)) return;
							
			$image = $this->create_image_from_data(warehouse_path("images", $event->hash), $event->metadata);
				
			if(is_null($image)) {
				throw new UploadException("Data handler failed to create image object from data");
			}
			
			$iae = new ImageAdditionEvent($event->user, $image);
			send_event($iae);
				
			$event->image_id = $iae->image->id;		
		}
	}
		
	public function onDisplayingImage($event) {
		$this->theme->display_image($event->image);
	}
	
	protected function supported_ext($ext) {
		$exts = array("jpg", "jpeg", "gif", "png");
		return in_array(strtolower($ext), $exts);
	}

	protected function create_image_from_data($filename, $metadata) {
		global $config;

		$image = new Image();

		$info = "";
		if(!($info = getimagesize($filename))) return null;

		$image->width = $info[0];
		$image->height = $info[1];

		$image->filesize  = $metadata['size'];
		$image->hash      = $metadata['hash'];
		$image->filename  = $metadata['filename'];
		$image->ext       = $metadata['extension'];
		$image->tag_array = Tag::explode($metadata['tags']);
		$image->source    = $metadata['source'];

		return $image;
	}

	protected function check_contents($file) {
		$valid = Array(IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_JPEG);
		if(!file_exists($file)) return false;
		$info = getimagesize($file);
		if(is_null($info)) return false;
		if(in_array($info[2], $valid)) return true;
		return false;
	}
}
add_event_listener(new PixelFileHandler());
?>
