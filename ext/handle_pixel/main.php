<?php
/**
 * Name: Handle Pixel
 * Author: Shish <webmaster@shishnet.org>
 * Description: Handle JPEG, PNG, GIF, etc files
 */

class PixelFileHandler extends SimpleExtension {
	
	public function onDataUpload($event){
		if($this->supported_ext($event->type) && $this->check_contents($event->tmpname)){
		
			if(!warehouse_file($event->tmpname, $event->hash, $event->type)) return;
							
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
		if($this->supported_ext($event->image->ext)) {
			$this->theme->display_image($event->image);
		}
	}
	
	public function onSetupBuilding($event) {
		$sb = new SetupBlock("Post Zoom");
		$sb->add_bool_option("post_zoom", "Auto zoom posts: ");
		$event->panel->add_block($sb);
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
		
		$min_w = $config->get_int("upload_min_width", -1);
		$min_h = $config->get_int("upload_min_height", -1);
		$max_w = $config->get_int("upload_max_width", -1);
		$max_h = $config->get_int("upload_max_height", -1);
		
		if($min_w > 0 && $image->width < $min_w) throw new UploadException("Image too small.");
		if($min_h > 0 && $image->height < $min_h) throw new UploadException("Image too small.");
		if($max_w > 0 && $image->width > $max_w) throw new UploadException("Image too large.");
		if($max_h > 0 && $image->height > $max_h) throw new UploadException("Image too large.");

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