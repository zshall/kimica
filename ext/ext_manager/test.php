<?php
class ExtManagerTest extends SCoreWebTestCase {
	function testAuth() {
		$this->get_page('extensions');
		$this->assert_title("Extensions");

		$this->get_page('extensions/docs');
		$this->assert_title("Extensions");

		$this->get_page('extensions/docs/ext_manager');
		$this->assert_title("Documentation for Extension Manager");
		$this->assert_text("view a list of all extensions");

		# test author without email
		$this->get_page('extensions/docs/user');

		$this->log_in_as_admin();
		$this->get_page('extensions');
		$this->assert_title("Extensions");
		$this->assert_text("SimpleTest integration");
		$this->log_out();

		# FIXME: test that some extensions can be added and removed? :S
	}
}
?>
