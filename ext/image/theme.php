<?php
class ImageIOTheme extends Themelet {
	/*
	 * Display a link to delete an image
	 *
	 * $image_id = the image to delete
	 */
	public function get_deleter_html($image_id) {
		$i_image_id = int_escape($image_id);
		$html = "
			<form action='".make_link("image_admin/delete")."' method='POST'>
				<input type='hidden' name='image_id' value='$i_image_id'>
				<input type='submit' value='Delete'>
			</form>
		";
		return $html;
	}
	
	/*
	 * Show a form which offers to regenerate the thumb of an image with ID #$image_id
	 */
	public function get_regen_html($image_id) {
		return "
			<form action='".make_link("image_admin/regen")."' method='POST'>
			<input type='hidden' name='image_id' value='$image_id'>
			<input type='submit' value='Regenerate'>
			</form>
		";
	}
	
	/*
	 * Show a link to the new thumbnail
	 */
	public function display_results(Page $page, Image $image) {
		$page->set_title("Thumbnail Regenerated");
		$page->set_heading("Thumbnail Regenerated");
		$page->add_header("<meta http-equiv=\"cache-control\" content=\"no-cache\">");
		$page->add_block(new NavBlock());
		$page->add_block(new Block("Thumbnail", $this->build_thumb_html($image)));
	}
}
?>
