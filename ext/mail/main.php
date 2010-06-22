<?php
/**
* Name: Mail System
* Author: Zach Hall <zach@sosguy.net>
* Link: http://seemslegit.com
* License: GPLv2
* Description: Provides an interface for sending and receiving mail.
*/

class Mail extends SimpleExtension {
	public function send($to, $subject, $header, $body) {
		global $config;
		// default data
		$subject = $config->get_string("mail_sub");
		$header_img = $config->get_string("mail_img");
		$footer = $config->get_string("mail_fot");
		$sitename = $config->get_string("title");
		$sitedomain = make_http(make_link());
		$siteemail = $config->get_string("contact_link");
		$date = date("F j, Y");
		// escapes
		$to = html_escape($to);
		$subject .= " " . html_escape($subject);
		$header = html_escape($header);
		// send email
		$email = new Email($to, $subject, $header, $header_img, $sitename, $sitedomain, $siteemail, $date, $body, $footer);
		$email->send();
		log_info("mail", "Sent message '$subject' to '$to'");
	}
	
	public function onSetupBuilding($event) {
		$sb = new SetupBlock("Mailing Options");
		$sb->add_text_option("mail_sub", "<br>Subject prefix: ");
		$sb->add_text_option("mail_img", "<br>Banner Image URL: ");
		$sb->add_longtext_option("mail_fot", "<br>Footer (Use HTML)");
		$sb->add_label("<br><i>Should measure 550x110px. Use an absolute URL");
		$event->panel->add_block($sb);
	}
	
	public function onInitExt($event) {
		global $config;
		$config->set_default_string("mail_sub", $config->get_string("title")." - ");
		$config->set_default_string("mail_img", make_http("ext/mail/banner.png"));
		$config->set_default_string("mail_fot", "<a href='".make_http(make_link())."'>".$config->get_string("title")."</a>");
	}
}
class MailTest extends SimpleExtension {
	public function onPageRequest($event) {
		if($event->page_matches("mail/test")) {
			global $page;
			echo "Alert: uncomment this page's code on /ext/mail/main.php starting on line 52, and change the email address. Make sure you're using a server with a domain, not localhost.";
/*			$page->set_mode("data");
			echo "Preparing to send message:<br>";
			$email = new Mail();
			echo "created new mail object. sending now... ";
			$email->send("test@localhost", "hello", "hello world", "this is a test message.");
			echo "sent.";
*/		}
	}
}
?>