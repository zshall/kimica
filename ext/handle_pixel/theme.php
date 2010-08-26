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

		$zoom = "<script type=\"text/javascript\">
					var orig_width = $image->width;
					
					var div_width = parseInt($(\"#main_image\").parent().css(\"width\"));
					var img_width = $image->width;
					
					$(\"#main_image\").before(\"<p>Note: Image has been scaled; click to enlarge.</p>\");
					resize();
					
					$(\"#main_image\").click(function(){
						img_width = parseInt($(\"#main_image\").css(\"width\"));
						resize();
					});
					
					function resize(){						
						if(img_width > div_width){
							$(\"#main_image\").parent().find(\"p\").css(\"display\", \"block\");
							$(\"#main_image\").css(\"width\", \"100%\");
						}
						else{
							$(\"#main_image\").parent().find(\"p\").css(\"display\", \"none\");
							$(\"#main_image\").css(\"width\", orig_width+\"px\");
						}
					}
				</script>";
				
		$zoom = $config->get_bool("post_zoom", false) ? $zoom : "";
		
		$page->add_block(new Block("Image", $html.$zoom, "main", 0));
	}
}
?>