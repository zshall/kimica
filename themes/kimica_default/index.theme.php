<?php

class CustomIndexTheme extends IndexTheme {
	public function display_page($page, $images) {
		global $config;
		if(count($this->search_terms) != 0) {
			$search_string = "";
			$i = 1;
			foreach ($this->search_terms as $search_term) {
				$search_string .= "<a href='post/list/".$search_term."/1'>".$search_term."</a>";
				if($i < count($this->search_terms)) $search_string .= " + ";
				$i++;
			}
			$query = url_escape($search_string);
			$page_title = $search_string;
		}
		else {
			$query = null;
			$page_title = $config->get_string('site_title');	
		}
		$nav = $this->build_navigation($this->page_number, $this->total_pages, $this->search_terms);
		$page->set_title($page_title);
		$page->set_heading($page_title);
		if(!$nav == "") {
		$page->add_block(new Block("Search", $nav, "left", 0));}
		if(count($images) > 0) {
			if($query) {
				$page->add_block(new Block("Images", $this->build_table($images, "search=$query"), "main", 10));
				$this->display_paginator($page, "post/list/$query", null, $this->page_number, $this->total_pages);
			}
			else {
				$page->add_block(new Block("Images", $this->build_table($images, null), "main", 10));
				$this->display_paginator($page, "post/list", null, $this->page_number, $this->total_pages);
			}
		}
		else {
			$page->add_block(new Block("No Images Found", "No images were found to match the search criteria"));
		}
	}


	protected function build_navigation($page_number, $total_pages, $search_terms) {
		$h_search_string = count($search_terms) == 0 ? "" : html_escape(implode(" ", $search_terms));
		$h_search_link = make_link();
		if(!$h_search_string == "") {
		$h_search = "
			<script><!--
			$(document).ready(function() {
				$('#search_input').DefaultValue('Search');
				$('#search_input').autocomplete('".make_link("api/internal/tag_list/complete")."', {
					width: 320,
					max: 15,
					highlight: false,
					multiple: true,
					multipleSeparator: ' ',
					scroll: true,
					scrollHeight: 300,
					selectFirst: false
				});
			});
			//--></script>
			<p><form action='$h_search_link' method='GET'>
				<input id='search_input' name='search' type='text'
						value='$h_search_string' autocomplete='off' />
				<input type='hidden' name='q' value='/post/list'>
				<input type='submit' value='Find' style='display: none;' />
			</form>
			<div id='search_completions'></div>";
		} else $h_search = "";
		return $h_search;
	}

	protected function build_table($images, $query) {
		$table = "";
		
		$script = <<<EOD
		<script type="text/javascript">
		// Only create tooltips when document is ready
		$(document).ready(function()
		{
		   // Use the each() method to gain access to each of the elements attributes
		   $('.thumb img').each(function()
		   {
			  $(this).qtip(
			  {
				 content: { text: false },
				 position: {corner: {
					 target: 'topMiddle',
					 tooltip: 'bottomMiddle'
				  }},
				 hide: {
					fixed: true // Make it fixed so it can be hovered over
				 },
				 style: {
					padding: '5px 15px', // Give it some extra padding
					name: 'blue', // And style it with the preset dark theme
					tip:true
				 }
			  });
		   });
		});
		</script>
EOD;
		
		foreach($images as $image) {
			$table .= "\t<span class=\"thumb\">" . $this->build_thumb_html($image, $query) . "</span>\n";
		}
		return $script . $table;
	}
}
?>
