<?php

class Email {
	/**
	 * A generic email.
	 */
	var $to;
	var $subject;
	var $header;
	var $header_img;
	var $sitename;
	var $sitedomain;
	var $siteemail;
	var $date;
	var $body;
	var $footer;
		
	public function __construct($to, $subject, $header, $body) {
		global $config;
		$this->to = $to;
		
		$sub_prefix = $config->get_string("mail_sub");
		
		if(!isset($sub_prefix)){
			$this->subject = $subject;
		}
		else{
			$this->subject = $sub_prefix." ".$subject;
		}
		
		$this->header = html_escape($header);
		$this->header_img = $config->get_string("mail_img");
		$this->sitename = $config->get_string("title");
		$this->sitedomain = make_http(make_link(""));
		$this->siteemail = $config->get_string("contact_link");
		$this->date = date("F j, Y");
		$this->body = $body;
		$this->footer = $config->get_string("mail_fot");
	}
	
	public function send() {
		$headers  = "From: ".$this->sitename." <".$this->siteemail.">\r\n";
		$headers .= "Reply-To: ".$this->siteemail."\r\n";
		$headers .= "X-Mailer: PHP/" . phpversion(). "\r\n";
		$headers .= "errors-to: ".$this->siteemail."\r\n";
		$headers .= "Date: " . date(DATE_RFC2822);
		$headers .= 'MIME-Version: 1.0' . "\r\n";
		$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
		$message = '
		
<html> 
<body leftmargin="0" marginwidth="0" topmargin="0" marginheight="0" offset="0" bgcolor="#EEEEEE" > 
 
 
<STYLE> 
 .headerTop { background-color:#FFCC66; border-top:0px solid #000000; border-bottom:1px solid #FFFFFF; text-align:center; }
 .adminText { font-size:10px; color:#996600; line-height:200%; font-family:verdana; text-decoration:none; }
 .headerBar { background-color:#FFFFFF; border-top:0px solid #333333; border-bottom:10px solid #FFFFFF; }
 .title { font-size:20px; font-weight:bold; color:#CC6600; font-family:arial; line-height:110%; }
 .subTitle { font-size:11px; font-weight:normal; color:#666666; font-style:italic; font-family:arial; }
 .defaultText { font-size:12px; color:#000000; line-height:150%; font-family:trebuchet ms; }
 .footerRow { background-color:#FFFFCC; border-top:10px solid #FFFFFF; }
 .footerText { font-size:10px; color:#996600; line-height:100%; font-family:verdana; }
 a { color:#FF6600; color:#FF6600; color:#FF6600; }
</STYLE> 
 
 
 
<table width="100%" cellpadding="10" cellspacing="0" class="backgroundTable" bgcolor="#EEEEEE" > 
<tr> 
<td valign="top" align="center"> 
 
 
<table width="550" cellpadding="0" cellspacing="0"> 
 
<tr> 
<td style="background-color:#FFFFFF;border-top:0px solid #333333;border-bottom:10px solid #FFFFFF;"><center><a href="'.$this->sitedomain.'"><IMG SRC="'.$this->header_img.'"  alt="'.$this->sitename.'" name="Header" BORDER="0" align="center" title="'.$this->sitename.'"></a> 
</center></td> 
</tr> 

</table> 
 
<table width="550" cellpadding="20" cellspacing="0" bgcolor="#FFFFFF"> 
<tr> 
<td bgcolor="#FFFFFF" valign="top" style="font-size:12px;color:#000000;line-height:150%;font-family:trebuchet ms;"> 
 
<p> 
<span style="font-size:20px; font-weight:bold; color:#3399FF; font-family:arial; line-height:110%;">'.$this->header.'</span><br> 
<span style="font-size:11px;font-weight:normal;color:#666666;font-style:italic;font-family:arial;">'.$this->date.'</span><br> 
</p> 
<p>'.$this->body.'</p> 
<p>'.$this->footer.'</p> 
</td> 
</tr> 
 
<tr> 
<td style="background-color:#FFFFCC;border-top:10px solid #FFFFFF;" valign="top"> 
<span style="font-size:10px;color:#996600;line-height:100%;font-family:verdana;"> 
This email was sent to you since you are a member of <a href="'.$this->sitedomain.'">'.$this->sitename.'</a>. To change your email preferences, visit your <a href="'.make_http(make_link("preferences")).'">Account preferences</a>.<br /> 
 
<br /> 
Contact us:<br /> 
<a href="'.$this->siteemail.'">'.$this->siteemail.'</a><br /><br />
Copyright (C) <a href="'.$this->sitedomain.'">'.$this->sitename.'</a><br />
</span></td> 
</tr> 
 
</table> 

</td> 
</tr> 
</table> 

</body> 
</html>
		';
		$sent = mail($this->to, $this->subject, $message, $headers);
		if($sent){
			log_info("mail", "Sent message '$this->subject' to '$this->to'");
		}
		else{
			log_info("mail", "Error sending message '$this->subject' to '$this->to'");
		}
		
	return $sent;
	}
}
?>