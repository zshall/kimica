<?php
class TagListTest extends ShimmieWebTestCase {
	var $pages = array("map", "alphabetic", "popularity", "categories");

	function testTagList() {
		$this->get_page('tags/map');
		$this->assert_title('Tag List');

		$this->get_page('tags/alphabetic');
		$this->assert_title('Tag List');

		$this->get_page('tags/popularity');
		$this->assert_title('Tag List');

		$this->get_page('tags/categories');
		$this->assert_title('Tag List');

		# FIXME: test that these show the right stuff
	}

	function testMinCount() {
		foreach($this->pages as $page) {
			$this->get_page("tags/$page?mincount=999999");
			$this->assert_title("Tag List");

			$this->get_page("tags/$page?mincount=1");
			$this->assert_title("Tag List");

			$this->get_page("tags/$page?mincount=0");
			$this->assert_title("Tag List");

			$this->get_page("tags/$page?mincount=-1");
			$this->assert_title("Tag List");
		}
	}
}

class AliasEditorTest extends ShimmieWebTestCase {
	function testAliasEditor() {
        $this->get_page('tags/alias');
		$this->assert_title("Alias List");

		$this->log_in_as_admin();

		# test one to one
        $this->get_page('tags/alias');
		$this->assert_title("Alias List");
		$this->set_field('oldtag', "test1");
		$this->set_field('newtag', "test2");
		$this->click("Add");
		$this->assert_text("test1");

		$this->get_page("tags/alias/export/aliases.csv");
		$this->assert_text("test1,test2");

		$image_id = $this->post_image("ext/simpletest/data/pbx_screenshot.jpg", "test1");
		$this->get_page("post/view/$image_id"); # check that the tag has been replaced
		$this->assert_title("Image $image_id: test2");
		$this->get_page("post/list/test1/1"); # searching for an alias should find the master tag
		$this->assert_title("Image $image_id: test2");
		$this->get_page("post/list/test2/1"); # check that searching for the main tag still works
		$this->assert_title("Image $image_id: test2");
		$this->delete_image($image_id);

        $this->get_page('tags/alias');
		$this->click("Remove");
		$this->assert_title("Alias List");
		$this->assert_no_text("test1");

		# test one to many
        $this->get_page('tags/alias');
		$this->assert_title("Alias List");
		$this->set_field('oldtag', "onetag");
		$this->set_field('newtag', "multi tag");
		$this->click("Add");
		$this->assert_text("multi");
		$this->assert_text("tag");

		$this->get_page("tags/alias/export/aliases.csv");
		$this->assert_text("onetag,multi tag");

		$image_id_1 = $this->post_image("ext/simpletest/data/pbx_screenshot.jpg", "onetag");
		$image_id_2 = $this->post_image("ext/simpletest/data/bedroom_workshop.jpg", "onetag");
		// FIXME: known broken
		//$this->get_page("post/list/onetag/1"); # searching for an aliased tag should find its aliases
		//$this->assert_title("onetag");
		//$this->assert_no_text("No Images Found");
		$this->get_page("post/list/multi/1");
		$this->assert_title("multi");
		$this->assert_no_text("No Images Found");
		$this->get_page("post/list/multi%20tag/1");
		$this->assert_title("multi tag");
		$this->assert_no_text("No Images Found");
		$this->delete_image($image_id_1);
		$this->delete_image($image_id_2);

        $this->get_page('tags/alias');
		$this->click("Remove");
		$this->assert_title("Alias List");
		$this->assert_no_text("test1");

		$this->log_out();


        $this->get_page('tags/alias');
		$this->assert_title("Alias List");
		$this->assert_no_text("Add");
	}
}
?>
