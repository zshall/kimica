<?php
class AdminTheme extends Themelet {
	public function display_alerts($alerts){
		global $page;
		
		$sorter = '<script>
			$(document).ready(function() {
				$("#ads").tablesorter();
			});
			</script>';
		
		$html = '<form action="'.make_link("admin/alerts/action").'" method="POST">
					<table id="ads">
					<thead>
						<tr>
							<th>Id</th><th>Section</th><th>Message</th><th>Date</th><th>Status</th><th>Alerter</th><th>Reviewer</th><th>Action</th>
						</tr>
					</thead>
					<tbody>';
					
		$n = 0;
		foreach($alerts as $alert){
			$oe = ($n++ % 2 == 0) ? "even" : "odd";
			$html .= '<tr class="'.$oe.'">
						<td>'.$alert['id'].'</td>
						<td><a href="'.make_link("admin/alerts/view/".$alert['id']).'">'.$alert['section'].'</a></td>
						<td><abbr title="'.$alert['description'].'">'.$alert['message'].'</abbr></td>
						<td>'.autodate($alert['created_at']).'</td>
						<td>'.Admin::alert_to_human($alert['status']).'</td>
						<td>'.$alert['alerter'].'</td>
						<td>'.$alert['reviewer'].'</td>
						<td><input name="id[]" type="checkbox" value="'.$alert["id"].'" /></td>
					  </tr>';
		}
		$html .= '</tbody>
				</table>';
				
		$html .="<input type='submit' name='action' value='Solved'>
					 <input type='submit' name='action' value='Delete'><form>";
	
		if(!$alerts){
			$html = "There is no alerts to display.";
		}
	
		$page->set_title("Alerts");
		$page->set_heading("Alerts");
		$page->add_block(new Block("Alerts", $sorter.$html, "main", 0));
	}
}
?>