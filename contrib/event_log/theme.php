<?php

class EventLogTheme extends Themelet {
	public function display_page($page, $events) {
		$page->set_title("Event Log");
		$page->set_heading("Event Log");
		$page->add_block(new NavBlock());
		
		$this->display_table($page, $events);
		$this->display_controls($page);
	}

	protected function display_table($page, $events) {
		$table = "
			<style>
			.event_log_table TD {
				font-size: 0.75em;
			}
			.event_log_table TD.entry {
				text-align: left;
				vertical-align: middle;
			}
			</style>
			<table border='1' class='event_log_table'>
				<tr>
					<th><a href='".make_link("event_log", "sort=name")."'>User</a></th>
					<th style='width: 10em;'><a href='".make_link("event_log", "sort=ip")."'>IP</a></th>
					<th rowspan='2' class='entry'>Entry</th>
				</tr>
				<tr>
					<th style='width: 10em;'><a href='".make_link("event_log", "sort=date")."'>Date</a></th>
					<th><a href='".make_link("event_log", "sort=event")."'>Event</a></th>
				</tr>
		";
		foreach($events as $event) {
			$nobrdate = str_replace(" ", "&nbsp;", $event['date']);
			$table .= "
				<tr>
					<td>{$event['name']}</td>
					<td>{$event['owner_ip']}</td>
					<td rowspan='2' class='entry'>{$event['entry']}</td>
				</tr>
				<tr>
					<td>{$nobrdate}</td>
					<td>{$event['event']}</td>
				</tr>
			";
		}
		$table .= "</table>";
		$page->add_block(new Block("Log Contents", $table));
	}

	protected function display_controls($page) {
		$html = "
		<form action='".make_link("event_log")."' method='POST'>
			<input type='hidden' name='action' value='clear'>
			<input type='submit' value='Clear Log'>
		</form>
		";
		$page->add_block(new Block(null, $html, "main", 60));
	}
}
?>