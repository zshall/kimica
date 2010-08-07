<?php

class FlashFileHandlerTheme extends Themelet {
	public function display_image(Image $image) {
		global $page;
		$ilink = $image->get_image_link();
		// FIXME: object and embed have "height" and "width"
		$html = "
			<object classid='clsid:D27CDB6E-AE6D-11cf-96B8-444553540000'
			        codebase='http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=7,0,19,0'
					height='{$image->height}'
					width='{$image->width}'
					>
				<param name='movie' value='$ilink'/>
				<param name='quality' value='high' />
				<embed src='$ilink' quality='high'
					pluginspage='http://www.macromedia.com/go/getflashplayer'
					height='{$image->height}'
					width='{$image->width}'
					type='application/x-shockwave-flash'></embed>
			</object>";
		$page->add_block(new Block("Flash Animation", $html, "main", 0));
	}
	
	/*
	 * Show a form which offers to regenerate the thumb of an image with ID #$image_id
	 */
	public function get_regen_html($image_id) {
		return "
			<form action='".make_link("post/regen")."' method='POST'>
			<input type='hidden' name='image_id' value='$image_id'>
			<input type='submit' value='Regenerate'>
			</form>
		";
	}
}
?>