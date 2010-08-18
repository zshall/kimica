<?php
class CommunityPortalTheme extends Themelet {
	public function display_page($page, $all) {
		$inc_path = "/ext/portal";
		$main = "";
		$left = "";
		$right = "";
		foreach($all->mods as $mod) {
			if($mod instanceof PortalMod) {
				$html = $this->pm_to_html($mod);
/*				switch ($mod->section) {
					case "main":
						$main .= $html;
						break;
					case "left":
						$left .= $html;
						break;
					case "right":
						$right .= $html;
						break;
					default:
						die("Module ".$mod->header." using invalid section: ".$mod->section);
				}
*/			}
		}
	}
	
	public function pm_to_html(PortalMod $mod) {
		global $page;
/*		$args = '';
		$id = '';
		$more = '';
		if($mod->movable == true) $args .= "m";
		if($mod->removable == true) $args .= "r";
		if($mod->collapsible == true) $args .= "c";
		if($args != '') $id = "id='$args'";
		if(!is_null($mod->more_link)) $more = "<span style='position:relative;float:right;padding-right:10px;'>
		<a href='#'>More</a></span>'";
*/		$page->add_block(new Block($mod->header.$more, $mod->body, "main"));
		return null;
	}
}
?>