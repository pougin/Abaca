<?php
#########################################
# Abaca 基础模块：MAIL 类
/*
$options= array(
			username=>"账号",	// smtp，必须是完整的，包含@后部分，如pougin@qqgexing.com 
			password=>"密码", 			// smtp
			host=>"smtp.126.com",		// smtp
			port=>"25"					// smtp
);
$info= array(
			from=>"发件人地址",
			fromName=>"发件人姓名",
			subject=>"邮件标题",
			altBody=>"邮件正文不支持HTML的备用显示",
			body=>"邮件主体内容",
			to=>array(array(mail=>"收件人地址",name=>"收件人姓名"),.....)
			//cc,bcc like to
			attachment=>array("附件内容1","附件内容2"....),
);*/
#########################################

class Mail{
	private $mail;
	
	/**
	 * Mail constructor
	 * @param S $type, the method to send email, can be sendmail,smtp,mail
	 * @param A $options, only for smtp
	 */
	public function __construct($type = 'mail', $options = array()) {
		$this->mail = new PHPMailer ();
		switch ($type) {
			case 'sendmail' :
				$this->mail->IsSendmail ();
				break;
			case 'smtp' :
				$this->mail->IsSMTP ();
				$this->mail->SMTPAuth = true;
				$this->mail->Host = $options ['host'];
				$this->mail->Port = $options ['port'];
				$this->mail->Username = $options ['username'];
				$this->mail->Password = $options ['password'];
				break;
			case 'mail' :
			default :
				$this->mail->IsMail ();
				break;
		}
	}
	
	/**
	 * send email
	 * @param A $info, like array('from'=>'anc@ad.cc',to=>array(array('name'=>'yourname','mail'=>'to@xxx.com')))
	 */
	function send($info = array()) {
		$this->mail->From = $info ['from'];
		$this->mail->FromName = $info ['fromName'];
		$this->mail->Subject = $info ['subject'];
		if(!empty($info ['ConfirmReadingTo'])){
			$this->mail->ConfirmReadingTo = $info ['ConfirmReadingTo'];
		}
		if (!empty($info ['altBody'])) {
			$this->mail->AltBody = $info ['altBody'];
		}
		//TODO why repalce [] to space?
		$body= str_replace(array('[',']'),'',$info ['body']);
		$this->mail->MsgHTML ( $body );
		$this->mail->WordWrap = 60;
		
		foreach ( $info ['to'] as $v ) {
			$this->mail->AddAddress ( $v ['mail'], $v ['name'] );
		}
		if (! empty ( $info ['cc'] )) {
			foreach ( $info ['cc'] as $v ) {
				$this->mail->AddCC ( $v ['mail'], $v ['name'] );
			}
		}
		if (! empty ( $info ['bcc'] )) {
			foreach ( $info ['bcc'] as $v ) {
				$this->mail->AddBCC ( $v ['mail'], $v ['name'] );
			}
		}
		if (! empty ( $info ['attachment'] )) {
			foreach ( $info ['attachment'] as $v ) {
				$this->mail->AddAttachment ( $v );
			}
		}
		$this->mail->IsHTML ( true );
		
		return $this->mail->Send ();
//		if (! $this->mail->Send ()) {
//			echo "Mailer Error: " . $this->mail->ErrorInfo;
//		} else {
//			echo "Message sent!";
//		}
	}
}