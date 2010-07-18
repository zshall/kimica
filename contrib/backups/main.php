<?php
/*
 * Name: Amazon S3 Mirror
 * Author: Shish <webmaster@shishnet.org>
 * License: GPLv2
 * Description: Copy uploaded files to S3
 * Documentation:
 */

require_once "S3.php";

class BackupAdditionEvent extends Event {
	var $image;

	public function BackupAdditionEvent(Image $image) {
		$this->image = $image;
	}
}

class BackupDeletionEvent extends Event {
	var $image;

	public function BackupDeletionEvent(Image $image) {
		$this->image = $image;
	}
}

class Backups extends SimpleExtension {
	public function onInitExt(InitExtEvent $event) {
		global $config;
		$config->set_default_string("amazon_s3_access", "");
		$config->set_default_string("amazon_s3_secret", "");
		$config->set_default_string("amazon_s3_bucket", "");
	}

	public function onSetupBuilding(SetupBuildingEvent $event) {
		$sb = new SetupBlock("Amazon S3");
		$sb->add_text_option("amazon_s3_access", "Access key: ");
		$sb->add_text_option("amazon_s3_secret", "<br>Secret key: ");
		$sb->add_text_option("amazon_s3_bucket", "<br>Bucket: ");
		$event->panel->add_block($sb);
	}

	public function onBackupAddition($event) {
		global $config;
		$access = $config->get_string("amazon_s3_access");
		$secret = $config->get_string("amazon_s3_secret");
		$bucket = $config->get_string("amazon_s3_bucket");

		if(!empty($bucket)) {			
			$s3 = new S3($access, $secret);
			$s3->putBucket($bucket, S3::ACL_PUBLIC_READ);
			$s3->putObjectFile(
				warehouse_path("thumbs", $event->image->hash),
				$bucket,
				'thumbs/'.substr($event->image->hash, 0, 2).'/'.$event->image->hash,
				S3::ACL_PUBLIC_READ,
				array(),
				array(
					"Content-Type" => "image/jpeg",
					"Content-Disposition" => "inline; filename=image-" . $event->image->id . ".jpg",
				)
			);
			$s3->putObjectFile(
				warehouse_path("images", $event->image->hash),
				$bucket,
				'images/'.substr($event->image->hash, 0, 2).'/'.$event->image->hash,
				S3::ACL_PUBLIC_READ,
				array(),
				array(
					"Content-Type" => $event->image->get_mime_type(),
					"Content-Disposition" => "inline; filename=image-" . $event->image->id . "." . $event->image->ext,
				)
			);
			
			log_info("amazon_s3", "Mirroring Image #".$event->image->id." to S3 #$bucket");
		}
	}

	public function onBackupDeletion($event) {
		global $config;
		$access = $config->get_string("amazon_s3_access");
		$secret = $config->get_string("amazon_s3_secret");
		$bucket = $config->get_string("amazon_s3_bucket");
		
		if(!empty($bucket)) {
			$s3 = new S3($access, $secret);
			$s3->deleteObject($bucket, "images/".substr($event->image->hash, 0, 2).'/'.$event->image->hash);
			$s3->deleteObject($bucket, "thumbs/".substr($event->image->hash, 0, 2).'/'.$event->image->hash);
			
			log_info("amazon_s3", "Deleting Image #".$event->image->id." from S3");
		}
	}
}
?>
