<?php
/*
 * Name: Handle MP3
 * Author: Shish <webmaster@shishnet.org>
 * Description: Handle MP3 files
 */

class MP3FileHandler extends DataHandlerExtension {

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
	
	protected function create_thumb($hash) {
		if(!warehouse_thumb('contrib/handle_mp3/thumb.jpg', $hash, 'jpg')) return;
	}

	protected function supported_ext($ext) {
		$exts = array("mp3");
		return in_array(strtolower($ext), $exts);
	}

	protected function create_image_from_data($filename, $metadata) {
		global $config;

		$image = new Image();

		// FIXME: need more flash format specs :|
		$image->width = 0;
		$image->height = 0;

		$image->filesize  = $metadata['size'];
		$image->hash      = $metadata['hash'];
		$image->filename  = $metadata['filename'];
		$image->ext       = $metadata['extension'];
		$image->tag_array = Tag::explode($metadata['tags']);
		$image->source    = $metadata['source'];

		return $image;
	}

	protected function check_contents($file) {
		// FIXME: mp3 magic header?
		return (file_exists($file));
	}
}
add_event_listener(new MP3FileHandler());
?>
