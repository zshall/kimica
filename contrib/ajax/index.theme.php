<?php

class CustomIndexTheme extends IndexTheme {
	
	protected function build_table($images, $query) {
		global $config;
		$columns = floor(100 / $config->get_int('index_width'));
		$table = "<ul class='thumbblock'>";
		foreach($images as $image) {
			$table .= "<li id='thumb_$image->id' class=\"thumb\" style='width: {$columns}%;'>" . $this->build_thumb_html($image, $query) . "</li>";
		}
		$table .= "</ul>";
		return $table;
	}
	
	public function build_thumb_html(Image $image, $query=null) {
		global $config;
		$h_view_link = make_link("post/view/{$image->id}", $query);
		$h_tip = html_escape($image->get_tooltip());
		$h_thumb_link = $image->get_thumb_link();
		$tsize = get_thumbnail_size($image->width, $image->height);
		$style = "display:inline-block; height: {$tsize[1]}px; width: {$tsize[0]}px;";
		return "<a style='$style' class='{$image->width}' id='thumb_$image->id' href='$h_view_link' onclick='FileClick($image->id);'>
					<img title='$h_tip' alt='$h_tip' width='{$tsize[0]}' height='{$tsize[1]}' src='$h_thumb_link' />
				</a>";
	}
}
?>
