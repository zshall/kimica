<?php
class AdsTheme extends Themelet {
	public function display_ad($ad){
		global $page;
		
		$banner = "";
		
		if($ad['html']){
			$banner = $ad['html'];
		}
		else{
			$banner = '<a href="'.make_http(make_link("ads/redirect/".$ad['id'])).'"><img src="'.$ad['image'].'" /></a>';
		}

		$page->add_block(new Block("Ads", $banner, $ad['location'], $ad['position']));
	}
	
	public function list_ads($ads){
		global $page;
		$sorter = '<script>
			$(document).ready(function() {
				$("#ads").tablesorter();
			});
			</script>';
			
		$html = '<table id="ads">
					<thead>
						<tr>
							<th>Id</th><th>Prints</th><th>Until Prints</th><th>Location</th><th>Module</th><th>Rating</th><th>Actions</th>
						</tr>
					</thead>
					<tbody>';
					
		$n = 0;
		foreach($ads as $ad){
			$oe = ($n++ % 2 == 0) ? "even" : "odd";
			$html .= '<tr class="'.$oe.'">
						<td>'.$ad['id'].'</td>
						<td>'.$ad['prints'].'</td>
						<td>'.$ad['until_prints'].'</td>
						<td>'.$ad['location'].'</td>
						<td>'.$ad['section'].'</td>
						<td>'.$ad['rating'].'</td>
						<td><a href="'.make_link("ads/remove/".$ad['id']).'">Delete</a></td>
					  </tr>';
		}
		$html .= '</tbody>
				</table>';
				
		if(!$ads){
			$html = "There is no ads to show.";
		}
		
		$page->set_title("Ads");
		$page->set_heading("Ads");
		$page->add_block(new Block("Ads", $sorter.$html, "main", 0));
	}
	
	public function add_ad(){
		global $page;
		$html = '<form action="'.make_link("ads/save").'" method="post">
					<table>
						<tbody>
						  <tr>
							<td>Until prints:</td>
							<td><input name="until_prints" type="text" /></td>
						  </tr>
						  <tr>
							<td>Location:</td>
							<td><input name="location" type="text" /></td>
						  </tr>
						  <tr>
							<td>Position:</td>
							<td><input name="position" type="text" /></td>
						  </tr>
						  <tr>
							<td>Priority:</td>
							<td><input name="priority" type="text" /></td>
						  </tr>
						  <tr>
							<td>Module:</td>
							<td><input name="section" type="text" /></td>
						  </tr>
						  <tr>
							<td>Rating:</td>
							<td><input type="radio" name="rating" value="s" />Safe <input type="radio" name="rating" value="q" checked />Questionable <input type="radio" name="rating" value="e" />Explicit</td>
						  </tr>
						  <tr>
							<td>Advertirser:</td>
							<td><input name="advertirser" type="text" /></td>
						  </tr>
						  <tr>
							<td>Url:</td>
							<td><input name="url" type="text" /></td>
						  </tr>
						  <tr>
							<td>Image:</td>
							<td><input name="image" type="text" /></td>
						  </tr>
						  <tr>
							<td>Code:</td>
							<td><textarea name="html"></textarea></td>
						  </tr>
						</tbody>
					</table>
					<input type="submit" value="Save Ad" />
				</form>';
				
		$page->set_title("Ads");
		$page->set_heading("Ads");
		$page->add_block(new Block("Ads", $html, "main", 0));
	}
}
?>