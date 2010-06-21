<?php

class TestPrefsTheme extends Themelet {
	// Showing the greeting on the page.
	public function greeting($page, $text) {
		$page->add_block(new Block("Greeting", $text, "left", 5));
	}
	
	public function display_page($page, $body) {
		$page->set_mode("data");
		$page->set_data(<<<EOD
<html>
	<head>
		<title>Example Page</title>
	</head>
	<body>
		$body
	</body>
</html>
EOD
);
	}
}
?>