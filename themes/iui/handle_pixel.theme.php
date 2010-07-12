<?php

class CustomPixelFileHandlerTheme extends PixelFileHandlerTheme {
	public function display_image(Page $page, Image $image) {
		global $config;

		$ilink = $image->get_image_link();
		$html = "<h2>Image</h2><br /><center><a href='$ilink' target='_blank'><img id='main_image' src='$ilink' style='max-width:305px; max-height:305px;' /></a></center><br />";
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
					$html .= "<h2>Exif Data</h2><fieldset><div class='row'><p>$head</p></div></fieldset>";
				}
			}
		}
		return $html;
	}
}
?>
