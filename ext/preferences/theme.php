<?php

class UserPrefsSetupTheme extends Themelet {
	/*
	 * Display a set of preferences option blocks
	 * 
	 * Zach: This renaming is almost comical. I got no idea if this will work or not.
	 *
	 * $panel = the container of the blocks
	 * $panel->blocks the blocks to be displayed, unsorted
	 *
	 * It's recommented that the theme sort the blocks before doing anything
	 * else, using:  usort($panel->blocks, "blockcmp");
	 *
	 * The page should wrap all the options in a form which links to preferences_save
	 */
	public function display_page(Page $page, PrefPanel $panel, $userid, $username) {
		$prefblock_html1 = "";
		$prefblock_html2 = "";

		usort($panel->blocks, "blockcmp");

		/*
		 * Try and keep the two columns even; count the line breaks in
		 * each an calculate where a block would work best
		 */
		$len1 = 0;
		$len2 = 0;
		foreach($panel->blocks as $block) {
			if($block instanceof PrefBlock) {
				$html = $this->sb_to_html($block);
				$len = count(explode("<br>", $html))+1;
				if($len1 <= $len2) {
					$prefblock_html1 .= $this->sb_to_html($block);
					$len1 += $len;
				}
				else {
					$prefblock_html2 .= $this->sb_to_html($block);
					$len2 += $len;
				}
			}
		}
			
		$table = "
			<form action='".make_link("account/preferences/$username/save")."' method='POST'><div id='setup'>
			<div class='col'>$prefblock_html1</div>
			<div class='col'>$prefblock_html2</div>
			<div class='save'><input type='submit' value='Save Settings'></div>
			</div></form>
			";

		$page->set_title("User Preferences");
		$page->set_heading("User Preferences");
		$page->add_block(new Block("Navigation", "<a href='".make_link()."'>Index</a>", "left", 0));
		$page->add_block(new Block("Preferences", $table));
	}

	public function display_advanced(Page $page, $options) {
		$rows = "";
		$n = 0;
		ksort($options);
		foreach($options as $name => $value) {
			$h_value = html_escape($value);
			$len = strlen($h_value);
			$oe = ($n++ % 2 == 0) ? "even" : "odd";

			$box = "";
			if(strpos($value, "\n") > 0) {
				$box .= "<textarea cols='50' rows='4' name='_config_$name'>$h_value</textarea>";
			}
			else {
				$box .= "<input type='text' name='_config_$name' value='$h_value'>";
			}
			$box .= "<input type='hidden' name='_type_$name' value='string'>";
			$rows .= "<tr class='$oe'><td>$name</td><td>$box</td></tr>";
		}

		$table = "
			<script>
			$(document).ready(function() {
				$(\"#settings\").tablesorter();
			});
			</script>
			<form action='".make_link("preferences/$username/save")."' method='POST'><table id='settings' class='zebra'>
				<thead><tr><th width='25%'>Name</th><th>Value</th></tr></thead>
				<tbody>$rows</tbody>
				<tfoot><tr><td colspan='2'><input type='submit' value='Save Settings'></td></tr></tfoot>
			</table></form>
			";

		$page->set_title("User Preferences");
		$page->set_heading("User Preferences");
		//$page->add_block(new Block("Navigation", "hello world", "left", 0));
		$page->add_block(new Block("Preferences", $table));
	}

	protected function sb_to_html(PrefBlock $block) {
		return "<div class='setupblock'><b>{$block->header}</b><br>{$block->body}</div>\n";
	}
}
?>
