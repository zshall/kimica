<?php

class Layout {
	function display_page($page) {
		global $config, $database;

		//$theme_name = $config->get_string('theme', 'default');
		$theme_name = 'iui';
		$data_href = get_base_href();
		$contact_link = $config->get_string('contact_link');

		$header_html = "";
		ksort($page->headers);
		foreach($page->headers as $line) {
			$header_html .= "\t\t$line\n";
		}

		$left_block_html = "";
		$main_block_html = "";
		$sub_block_html = "";

		foreach($page->blocks as $block) {
			switch($block->section) {
				case "left":
					$left_block_html .= $this->block_to_html($block, true, "left");
					break;
				case "main":
					$main_block_html .= $this->block_to_html($block, false, "main");
					break;
				case "subheading":
					$sub_block_html .= $block->body; // $this->block_to_html($block, true);
					break;
				default:
					print "<p>error: {$block->header} using an unknown section ({$block->section})";
					break;
			}
		}

		$debug = get_debug_info();

		$contact = empty($contact_link) ? "" : "<br><a class='whiteButton iui-cache-update-button' type='button' target='_blank' href='$contact_link'>Contact</a>";
		$subheading = empty($page->subheading) ? "" : "<div id='subtitle'>{$page->subheading}</div>";

		$wrapper = "";
		if(strlen($page->heading) > 100) {
			$wrapper = ' style="height: 3em; overflow: auto;"';
		}
		$qp = _get_query_parts();
		if($qp[0] == "") {
			 $selected = 'selected="true"';
		} else $selected = "";
		

		
		print <<<EOD
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01//EN">
<html>
	<head>
		<title>{$page->title}</title>
		<meta http-equiv="Content-Type" content="text/html;charset=utf-8">
		
$header_html
		<script src='$data_href/themes/$theme_name/sidebar.js' type='text/javascript'></script>
		<script src='$data_href/themes/$theme_name/script.js' type='text/javascript'></script>
        <link rel="stylesheet" href="$data_href/themes/$theme_name/style.css" type="text/css">
        <meta name="viewport" content="width=device-width; initial-scale=1.0; maximum-scale=1.0; user-scalable=0;"/>
        <link rel="apple-touch-icon" href="$data_href/themes/$theme_name/iui/iui-logo-touch-icon.png" />
        <meta content="yes" name="apple-mobile-web-app-capable" />
        <meta name="apple-touch-fullscreen" content="YES" />
        <style type="text/css" media="screen">@import "$data_href/themes/$theme_name/iui/iui.css";</style>
        <script type="application/x-javascript" src="$data_href/themes/$theme_name/iui/iui.js"></script>
	</head>

	<body>
    
    <div class="toolbar">
        <h1 id="pageTitle"></h1>
        <a id="backButton" class="button" href="#"></a>
        <a class="button" href="#searchForm">Search</a>
    </div>
    

	$main_block_html

	</body>
</html>
EOD;
	}

	function block_to_html($block, $hidable=false, $salt="") {
		$h = $block->header;
		$b = $block->body;
        $html = "";
        //if(!is_null($h)) $html .= "<h2>$h</h2>\n";
		if(!is_null($b)) $html .= "$b\n";
		return $html;
	}
}
?>
