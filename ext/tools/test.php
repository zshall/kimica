<?php
class AdminPageTest extends ShimmieWebTestCase {
	function testAuth() {
		$this->get_page('admin');
		$this->assert_response(403);
		$this->assert_title("Permission Denied");

		$this->log_in_as_user();
		$this->get_page('admin');
		$this->assert_response(403);
		$this->assert_title("Permission Denied");
		$this->log_out();
	}

	function testLowercase() {
		$ts = time(); // we need a tag that hasn't been used before

		$this->log_in_as_admin();
		$image_id_1 = $this->post_image("ext/simpletest/data/pbx_screenshot.jpg", "TeStCase$ts");

        $this->get_page("post/view/$image_id_1");
        $this->assert_title("Image $image_id_1: TeStCase$ts");

		$this->get_page('admin');
		$this->assert_title("Admin Tools");
		$this->set_field("action", "lowercase all tags");
		$this->click("Go");
		$this->log_out();

        $this->get_page("post/view/$image_id_1");
        $this->assert_title("Image $image_id_1: testcase$ts");

		$this->delete_image($image_id_1);
		$this->log_out();
	}

	# FIXME: make sure the admin tools actually work
	function testRecount() {
		$this->log_in_as_admin();
		$this->get_page('admin');
		$this->assert_title("Admin Tools");
		$this->set_field("action", "recount tag use");
		$this->click("Go");
		$this->log_out();
	}

	function testPurge() {
		$this->log_in_as_admin();
		$this->get_page('admin');
		$this->assert_title("Admin Tools");
		$this->set_field("action", "purge unused tags");
		$this->click("Go");
		$this->log_out();
	}

	function testConvert() {
		$this->log_in_as_admin();
		$this->get_page('admin');
		$this->assert_title("Admin Tools");
		$this->set_field("action", "convert to inodb");
		$this->click("Go");
		$this->log_out();
	}

	function testDump() {
		$this->log_in_as_admin();
		$this->get_page('admin');
		$this->assert_title("Admin Tools");
		$this->set_field("action", "database dump");
		$this->click("Go");
		$this->log_out();
	}

}
?>
