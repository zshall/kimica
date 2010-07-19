<?php

class PixelFileHandlerTheme extends Themelet {
	public function display_image(Image $image) {
		global $config, $page;

		$ilink = $image->get_image_link();
		$html = "<img id='main_image' src='$ilink'>";
		if($config->get_bool("image_show_meta")) {
			# FIXME: only read from jpegs?
			$exif = @exif_read_data($image->get_image_filename(), 0, true);
			if($exif) {
				$head = "";
				foreach ($exif as $key => $section) {
					foreach ($section as $name => $val) {
						if($key == "IFD0") {
							$head .= html_escape("$name: $val")."<br>\n";
						}
					}
				}
				if($head) {
					$page->add_block(new Block("EXIF Info", $head, "left"));
				}
			}
		}
		$page->add_block(new Block("Image", $html, "main", 0));
	}
}
?>
