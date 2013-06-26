<?php
class sendMeeting {

	/*******************************************************************
	*@class sendMail SMTP方式发送邮件类
	*By:史晶晶 shijingjing1221@gmail.com
	*GitHub:https://github.com/MouLingtao/PHP-SMTP-Send-Mail
	*
	*感谢所有提供帮助的开源例子。由于各大网站转载后把你们名字去掉或替换
	*成自己的网站名字，在下不知原作者，还望原作者见谅。
	*******************************************************************/


	/**私有变量和函数定义开始******************************************/
	private $smtp, $post, $check, $user, $pass;	//SMTP信息
	private $from, $fromMail, $fromName;		//邮件发送者信息
	private $to, $toMail, $toName;				//邮件接受者信息
	private $subject, $message, $is_html;		//邮件内容					//附件存储
	private $serverName="MAIL SERVER";			//发送服务器名字
	private $socketTimeout=60;					//Socket连接超时时间
	private $startTime, $endTime;               //The time format must be 20100730T150500Z
	private $location;
	private $uid;                               
	//an unique id for a meeting, it's used in cancelled this meeting
	//The canceled meeting email must have the same uid
	private $isCancel;                          //setup the meeting or cancel the meeting
	private $alertTime;                         //How much time popup the alert message before the meeting start
	/*******************************************************************
	*@function myBase64Encode，重载Base64编码方式(私有函数)
	*因为邮件内容的base64编码需要每76个字符就换行。(其实不换行也可以的)
	********************************************************************/
	private function myBase64Encode($str){
		$length=76;
		$str = base64_encode($str);
		if(strlen($str)<=$length) return $str;
		$mystr='';
		while( strlen($str)>=1 ){
			$mystr.=substr($str,0,$length)."\r\n";
			$str=substr($str,$length);
		}
		return $mystr;
	}
	/**私有变量和函数定义结束******************************************/
	
	/*******************************************************************
	*@function setSmtp设置SMTP信息
	*@bool check:服务器是否需要身份验证
	*@string smtp:SMTP服务器信息,可以使用tcp:// , ssl:// , tls:// 打头，默认tcp.
	*@string user:SMTP用户名
	*@string pass:SMTP密码
	*@int post:SMTP服务器端口,默认25
	*******************************************************************/
	public function setSmtp($ckeck=false,$smtp=null,$user=null,$pass=null,$port=25){
		$this->check=$ckeck;
		$this->smtp=$smtp;
		$this->user=$user;
		$this->pass=$pass;
		$this->post=intval($port);		
	}
	
	/*******************************************************************************
	*@function setFrom设置发信人信息
	*@string from:发信人邮箱，需要要在SMTP服务器中授权的
	*@string fromMail:在收件人邮箱的邮件详情上显示的发信人邮箱(为空采用$from变量)
	*@string fromName:屏幕显示的发信人昵称(为空采用$fromMail的@前面字符)
	*******************************************************************************/
	public function setFrom($from,$fromMail=null,$fromName=null){
		$this->from=$from;
		if(empty($fromMail)) $this->fromMail=$from;
		else $this->fromMail=$fromMail;
		if(empty($fromName)) $this->fromName=substr($this->fromMail,0,strpos($this->fromMail,'@'));
		else $this->fromName=$fromName;
	}
	
	/************************************************************************************
	*@function setTo设置收信人信息
	*@string to:真实的收信人邮箱
	*@string toMail:在真实收件人邮箱的邮件详情上显示的收信人邮箱(为空自动采用$to内容)
	*@string toName:在屏幕显示出来的收信人昵称(为空自动采用$toMail的@前面的内容)
	************************************************************************************/
	public function setTo($to,$toMail=null,$toName=null){
		$this->to=$to;
		if(empty($toMail)) $this->toMail=$to;
		else $this->toMail=$toMail;
		if(empty($toName)) $this->toName=substr($this->toMail,0,strpos($this->toMail,'@'));
		else $this->toName=$toName;
	}
	
	/*****************************************************
	*@function setMail设置邮件信息
	*@string subject:邮件主题
	*@string message:邮件内容
	*@bool is_html:邮件格式,是否为HTML类型,反之为TEXT类型
	*****************************************************/
	public function setMail($startTime, $endTime, $location, $uid, $alertTime = 15, $isCancal = FALSE, $subject=null,$message=null,$is_html=true){
		$this->subject=$subject;
		$this->message=$message;
		$this->is_html=$is_html;
		$this->startTime = $startTime;
		$this->endTime = $endTime;
		$this->location = $location;
		$this->uid = $uid;
		$this->isCancel = $isCancal;
		$this->alertTime = $alertTime;
		
	}
	


	/*******************************************
	*@function send发送邮件
	*成功则return false
	*失败就return "code:description"
	*******************************************/
	public function send () {
		//prepare for the meeting email format

		$mime_boundary = "----Meeting Booking----".MD5(TIME());
		$headers = "From: ".$this->fromName." <".$this->fromMail.">\n";
		$headers .= "Reply-To: ".$this->fromName." <".$this->fromMail.">\n";
		$headers .= "MIME-Version: 1.0\n";
		$headers .= "Content-Type: multipart/alternative; boundary=\"$mime_boundary\"\n";
		$headers .= "Content-class: urn:content-classes:calendarmessage\n";

		//Create Email Body (HTML)
		$message = "--$mime_boundary\r\n";
		$message .= "Content-Type: text/html; charset=UTF-8\r\n";
		$message .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
		$message .= "<html>\n";
		$message .= "<body>\n";
		$message .= $this->message;
		$message .= "</body>\n";
		$message .= "</html>\n";



		$message .= "--$mime_boundary\r\n";
		$message .= 'Content-Type: text/calendar;name="meeting.ics";method=REQUEST'."\r\n";
		$message .= "Content-Transfer-Encoding: 8bit\r\n\r\n";

		$ical = 'BEGIN:VCALENDAR' . "\r\n" .
				'PRODID:-//Microsoft Corporation//Outlook 10.0 MIMEDIR//EN' . "\r\n" .
				'VERSION:2.0' . "\r\n";
				
		if(!$this->isCancel){
			$ical .= "METHOD:REQUEST\r\n";
		}else{
			$ical .= "METHOD:CANCEL\r\n";
		}

		$ical .='BEGIN:VEVENT' . "\r\n" .
				'ORGANIZER;CN="'.$this->fromName.'":MAILTO:'.$this->fromMail. "\r\n" .
				'ATTENDEE;CN="'.$this->toName.'";ROLE=REQ-PARTICIPANT;RSVP=TRUE:MAILTO:'.$this->toMail. "\r\n" .
				'LAST-MODIFIED:' . date("Ymd\TGis\Z") . "\r\n" .
				'UID:'.$this->uid."\r\n" .
				'DTSTART:'.$this->startTime. "\r\n" .
				'DTEND:'.$this->endTime. "\r\n" .
				'TRANSP:OPAQUE'. "\r\n" .
				'SEQUENCE:1'. "\r\n" .
				'SUMMARY:' . $this->subject . "\r\n" .
				'LOCATION:' . $this->location . "\r\n" .
				'CLASS:PUBLIC'. "\r\n" .
				'PRIORITY:5'. "\r\n" .
				'BEGIN:VALARM' . "\r\n" .
				'TRIGGER:-PT'.$this->alertTime.'M' . "\r\n" .
				'ACTION:DISPLAY' . "\r\n" .
				'DESCRIPTION:Reminder' . "\r\n" .
				'END:VALARM' . "\r\n" ;
				if($this->isCancel){
	                  $ical .= "STATUS:CANCELLED\r\n";
	             }

		$ical .='X-WR-RELCALID:'. "THISISENGLISHCLASSREMINDERPID\r\n" .
				'END:VEVENT'. "\r\n" .
				'END:VCALENDAR'. "\r\n";

				$message .= $ical;


        if(empty($this->smtp)){
        	return mail($this->to, $this->subject, $this->message, $this->headers);
        }else{
        	echo "BEFORE CONNECT</br>";
			//连接服务器
			$timeOut = empty($this->socketTimeout) ? 6000 : $this->socketTimeout;
			if(function_exists('fsockopen'))
				$fp = @fsockopen ( $this->smtp, $this->post, $errno, $errstr, $timeOut);
			else if(function_exists('pfsockopen'))
				$fp = @pfsockopen ( $this->smtp, $this->post, $errno, $errstr, $timeOut);
			else if(function_exists('stream_socket_client'))
				$fp = @stream_socket_client ( $this->smtp.':'.$this->post, $errno, $errstr, $timeOut);
			else
				return "0:all socket function isnot exists";
			if (!$fp) return "0:open server socket error,".$errstr."({$errno})";
			stream_set_blocking($fp, true);
			$lastmessage=fgets($fp,512);
			if ( substr($lastmessage,0,3) != '220' ) return "1:$lastmessage";

			//Hello
			if($this->check) $lastact="EHLO ".str_replace(' ','-',$this->serverName)."\r\n";
			else $lastact="HELO ".str_replace(' ','-',$this->serverName)."\r\n";
			fputs($fp, $lastact);
			$lastmessage == fgets($fp,512);
			if (substr($lastmessage,0,3) != '220' ) return "2:$lastmessage";

			do $lastmessage = fgets($fp,512);
			while(!empty($lastmessage) && (substr($lastmessage,3,1) == "-"));
		
			//服务器需要身份验证
			if ($this->check) {
				//发送验证请求
				$lastact="AUTH LOGIN"."\r\n";
				fputs( $fp, $lastact);
				$lastmessage = fgets ($fp,512);
				if (substr($lastmessage,0,3) != "334") return "3:$lastmessage";
				//输入用户账号
				$lastact=base64_encode($this->user)."\r\n";
				fputs( $fp, $lastact);
				$lastmessage = fgets ($fp,512);
				if (substr($lastmessage,0,3) != "334") return "4:$lastmessage";
				//输入用户密码
				$lastact=base64_encode($this->pass)."\r\n";
				fputs( $fp, $lastact);
				$lastmessage = fgets ($fp,512);
				if (substr($lastmessage,0,3) != "235") return "5:$lastmessage";
			}

			//发送MAIL FROM信息
			$lastact="MAIL FROM: <". $this->from . ">\r\n";
			fputs( $fp, $lastact);
			$lastmessage = fgets ($fp,512);
			if (substr($lastmessage,0,3) != '250') return "6:$lastmessage";

			//发送RCPT TO信息
			$lastact="RCPT TO: <". $this->to ."> \r\n";
			fputs( $fp, $lastact);
			$lastmessage = fgets ($fp,512);
			if (substr($lastmessage,0,3) != '250') return "7:$lastmessage";
			
		//发送DATA信息
			$lastact="DATA"."\r\n";
			fputs($fp, $lastact);
			$lastmessage = fgets ($fp,512);
			if (substr($lastmessage,0,3) != '354') return "8:$lastmessage";
			echo " TEST BEFOR SET VALUE</br>";

        	$whole_contents = $headers."\r\n".$message."\r\n\r\n--". $mime_boundary ."--\r\n\r\n.";

			//发送信息
			fputs($fp, $whole_contents);
			$lastmessage = fgets($fp,512);
			if (substr($lastmessage,0,3) != '250') return "8:$lastmessage";
		
			$lastact="QUIT"."\r\n";
			fputs($fp,$lastact);
			fclose($fp);
			return false;
		}
	}
}
?>