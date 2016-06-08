<?php
/**
 * @package     VShow
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2007 Persony, Inc.
 * @version     2.0
 */
require_once("vobject.php");
require_once("vobject.php");
/**
* Const
*/
/*
$gMailKeys=array();
//$gMailKeys['FIRST_NAME']="[FIRST_NAME]";
//$gMailKeys['LAST_NAME']="[LAST_NAME]";
//$gMailKeys['PRODUCT_NAME']="[PRODUCT_NAME]";
$gMailKeys['LOGIN_URL']="[LOGIN_URL]";
$gMailKeys['LOGIN']="[LOGIN]";
$gMailKeys['PASSWORD']="[PASSWORD]";
$gMailKeys['MEETING_TITLE']="[MEETING_TITLE]";
$gMailKeys['MEETING_URL']="[MEETING_URL]";
$gMailKeys['REGISTERED_EMAIL']="[REGISTERED_EMAIL]";
$gMailKeys['MEETING_DATE']="[MEETING_DATE]";
$gMailKeys['MEETING_TIME']="[MEETING_TIME]";
$gMailKeys['FULL_NAME']="[FULL_NAME]";
$gMailKeys['MEETING_PHONE']="[MEETING_PHONE]";
*/
/**
 * @package     VShow
 * @access      public
 */
class VMailTemplate extends VObject 
{
	/**
	 * Constructor
	 * @param integer set $id to non-zero to associate the object with an existing row.
	 */	
	function VMailTemplate($id=0)
	{
		$this->VObject(TB_MAILTEMPLATE);
		$this->SetRowId($id);
	}
	/**
	*
	* @static
	 */	
	static function GetMailTemplate($brandId, $templateName, &$templateInfo)
	{

		// find a template for the brand
//		$query="brand_id ='$brandId' AND type = '$templateType'";
		$query="brand_id ='$brandId' AND name = '$templateName'";
			
		$errMsg=VObject::SelectAll(TB_MAILTEMPLATE, $query, $result);
		$num_rows = mysql_num_rows($result);
		
		if ($num_rows==0) {		
			// find a default template
//			$query="brand_id ='0' AND type = '$templateType'";
			$query="brand_id ='0' AND name = '$templateName'";
			$errMsg=VObject::SelectAll(TB_MAILTEMPLATE, $query, $result);
			$num_rows = mysql_num_rows($result);
		}
		
		if ($num_rows>0) {
			$templateInfo= mysql_fetch_array($result, MYSQL_ASSOC);
		}
		return '';
	}
	/**
	*
	* @static
	 */	
	static function GetBody($mailInfo, $userInfo, $brandInfo, $gText, $meetingInfo=null, $regInfo=null, $meetingUrl='')
	{
//		global $gMailKeys;
		
		$body=$mailInfo['body_text'];
		$type=$mailInfo['type'];
		$body=str_replace('MT_ADD_MEMBER_SUBJECT', $gText['MT_ADD_MEMBER_SUBJECT'], $body);
		$body=str_replace('MT_EDIT_MEMBER_SUBJECT', $gText['MT_EDIT_MEMBER_SUBJECT'], $body);
		$body=str_replace('MT_ADD_MEMBER', $gText['MT_ADD_MEMBER'], $body);
		$body=str_replace('MT_EDIT_MEMBER', $gText['MT_EDIT_MEMBER'], $body);
		$body=str_replace('MT_DEAR_NAME', $gText['MT_DEAR_NAME'], $body);
		$body=str_replace('MT_SIGNUP_INFO', $gText['MT_SIGNUP_INFO'], $body);
		$body=str_replace('MT_EDIT_INFO', $gText['MT_EDIT_INFO'], $body);
		$body=str_replace('MT_MEETING_TITLE', $gText['MT_MEETING_TITLE'], $body);
		$body=str_replace('MT_URL', $gText['MT_URL'], $body);
		$body=str_replace('MT_LOGIN', $gText['MT_LOGIN'], $body);
		$body=str_replace('MT_PASSWORD', $gText['MT_PASSWORD'], $body);
		$body=str_replace('MT_SEND_PWD_SUBJECT', $gText['MT_SEND_PWD_SUBJECT'], $body);
		$body=str_replace('MT_SEND_PWD', $gText['MT_SEND_PWD'], $body);
		$body=str_replace('MT_ACCOUNT_INFO', $gText['MT_ACCOUNT_INFO'], $body);
		$body=str_replace("MT_DATE", $gText['M_DATE'], $body);
		$body=str_replace("MT_TIME", $gText['M_TIME'], $body);
		$body=str_replace("MT_PHONE", $gText['MD_TELEPHONE'], $body);
		$body=str_replace("MT_JOIN_MEETING", _Text("Please join the following meeting."), $body);
		$body=str_replace("MT_PLAY_RECORDING", _Text("Please play the following recording."), $body);

		// don't print data and time because the timezone is not set correctly and the meeting page already has them 
//		$body=str_replace("MT_DATE: [MEETING_DATE]\r\n", "", $body);
//		$body=str_replace("MT_TIME: [MEETING_TIME]\r\n", "", $body);
		
		$body=str_replace("[MEETING_URL]", $meetingUrl, $body);

		if ($meetingInfo) {
			$body=str_replace("[MEETING_TITLE]", $meetingInfo['title'], $body);

			$meetingDate='';
			$meetingTime='';
			$meetingPhone='';
			$meetingPass='';
			if ($meetingInfo['scheduled']=='Y' || $meetingInfo['status']=='REC') {

				$dtime=$meetingInfo['date_time'];							
				
//				GetSessionTimeZone($tzName, $tz);

				$meetDateTimeStr='';
				if ($dtime!='') {
					$stz='';
					if (isset($userInfo['time_zone']) && $userInfo['time_zone']!='')
						$stz=$userInfo['time_zone'];
					elseif  (isset($brandInfo['time_zone']))
						$stz=$brandInfo['time_zone'];
					
					GetTimeZoneByDate($stz, $dtime, $tzName, $dtz);	
					//GetTimeZoneName($stz, $tzName, $tz);
	
					VObject::ConvertTZ($dtime, 'SYSTEM', $dtz, $meetDateTimeStr);
					//VObject::ConvertTZ($dtime, 'SYSTEM', $tz, $meetDateTimeStr);
				}

				if ($meetDateTimeStr!='') {
					list($dateStr, $time24Str)=explode(" ", $meetDateTimeStr);
					list($year, $mon, $day)=explode("-", $dateStr);
					list($meetHour, $meetMin, $meetSec)=explode(":", $time24Str);
					$theDate=mktime($meetHour, $meetMin, $meetSec, $mon, $day, $year);
					$meetingTime=H24ToH12($meetHour, $meetMin);
					$meetingDate=date('l F d, Y', $theDate);
					$meetingTime.=" ".$tzName;
				}

			}
			if ($meetingInfo['status']!='REC' && $meetingInfo['tele_conf']=='Y') {
	
				$meetingPhone=$meetingInfo['tele_num'];
				if ($meetingInfo['tele_num2']!='') {
					$meetingPhone.=" Or ".$meetingInfo['tele_num2'];
				}

				if ($meetingInfo['tele_pcode']!='')
					$meetingPhone.=" ".$gText['MD_PHONE_PCODE'].": ".$meetingInfo['tele_pcode'];
			}
			if ($meetingInfo['login_type']=='PWD')
				$meetingPass=$meetingInfo['password'];
				
			
			$body=str_replace('[MEETING_DATE]', $meetingDate, $body);
			$body=str_replace('[MEETING_TIME]', $meetingTime, $body);
			$body=str_replace('[MEETING_PHONE]', $meetingPhone, $body);
			$body=str_replace('[MEETING_PASSWORD]', $meetingPass, $body);
			$body=str_replace('[MEETING_DESCRIPTION]', $meetingInfo['description'], $body);

		}
		
		if ($userInfo && !$regInfo) {
require_once("vuser.php");
			$fullName=VUser::GetFullName($userInfo);
			$body=str_replace('[FULL_NAME]', $fullName, $body);
			$body=str_replace('[LOGIN]', $userInfo['login'], $body);
			$body=str_replace('[PASSWORD]', $userInfo['password'], $body);
		}
		
		$iphoneJoinText='';
		$iphoneDownloadText='';
		$iphoneOnlyText='';
		$iphoneMeetingUrl='';
		$iphoneAppUrl='';
		$downloadUrl='';
		if ($brandInfo) {
			$signupUrl=$brandInfo['site_url']."?page=MEETINGS";
			$body=str_replace('[LOGIN_URL]', $signupUrl, $body);
			
			if (strpos($brandInfo['mobile'], "iPhone")!==false) {
								
				$iphoneAppUrl=IPHONE_URL;
				$ipadAppUrl=IPAD_URL;
				$iphoneApp=IPHONE_APP;
				$ipadApp=IPAD_APP;

				if (isset($brandInfo['mobile_app']) && $brandInfo['mobile_app']!='') {
					require_once("iphone/common.php");
					$appInfo=array();
					GetMobileAppInfo($brandInfo['mobile_app'], $appInfo);
					if (isset($appInfo['iphone_url']))
						$iphoneAppUrl=$appInfo['iphone_url'];
					if (isset($appInfo['ipad_url']))
						$ipadAppUrl=$appInfo['ipad_url'];
					if (isset($appInfo['iphone_app']))
						$iphoneApp=$appInfo['iphone_app'];
					if (isset($appInfo['ipad_app']))
						$ipadApp=$appInfo['ipad_app'];
				}
				
				if ($iphoneApp!='' && $iphoneAppUrl!='')
					$downloadUrl.="iPhone: ".$iphoneApp."\n".$iphoneAppUrl."\n";
				if ($ipadApp!='' && $ipadAppUrl!='')
					$downloadUrl.="iPad: ".$ipadApp."\n".$ipadAppUrl."\n";
					
				if ($downloadUrl!='') {
					$iphoneOnlyText="------------------\n";
					$iphoneOnlyText.=_Text("For iPhone/iPad users only");				
					$iphoneJoinText=_Text("Use the URL to join the meeting:");
					$iphoneDownloadText=_Text("You must download the iPhone/iPad App first. Download it from:");					
					
					// this url will launch the iPhone app directly without going through a meeting info page
					$iphoneMeetingUrl=$brandInfo['site_url']."iphone/?meeting=".$meetingInfo['access_id'];
					if ($meetingInfo['login_type']=='PWD')
						$iphoneMeetingUrl.="&pass=".rawurlencode($meetingInfo['password']);
					
				}
					
			}					
		}	

		if ($regInfo ) {
			$body=str_replace('[FULL_NAME]', $regInfo['name'], $body);
			$body=str_replace('[REGISTERED_EMAIL]', $regInfo['email'], $body);
			$body=str_replace('MT_REGISTER_SUBJECT', $gText['MT_REGISTER_SUBJECT'], $body);
			$body=str_replace('MT_REGISTER_INFO', $gText['MT_REGISTER_INFO'], $body);
			$body=str_replace('MT_REGISTER', $gText['MT_REGISTER'], $body);			
		}
		
		$body=str_replace("MT_IPHONE_ONLY", $iphoneOnlyText, $body);
		$body=str_replace("[IPHONE_DOWNLOAD_URL]", $downloadUrl, $body);
		$body=str_replace("[IPHONE_MEETING_URL]", $iphoneMeetingUrl, $body);
		$body=str_replace("MT_IPHONE_APP_DOWNLOAD", $iphoneDownloadText, $body);
		$body=str_replace("MT_IPHONE_JOIN", $iphoneJoinText, $body);

		$body=str_replace("\r\n", "\n", $body);
		
		return $body;
				
	}
	
	/**
	*
	* @static
	 */	
	static function Send($fromName, $fromEmail, $toName, $toEmail, $subject, $body, 
		$attachData='', $attachFileName='file', $attachType="application/octet-stream", $isHtml=false,
		$bcc=null, $brandInfo=null, &$smtpLog=null)
	{
require_once("phpmailer/class.phpmailer.php");
		
		set_time_limit(60);

		$mail = new PHPMailer();
		if ($smtpLog!=null)
			$mail->SMTPDebug=2;
        $mail->CharSet = "UTF-8";
		
		if ($brandInfo!=null && $brandInfo['smtp_server']!='') {
			$mail->IsSMTP();            // set mailer to use SMTP
			$mail->Host = $brandInfo['smtp_server'];  // specify main and backup server
			if ($brandInfo['smtp_user']!='') {
				$mail->SMTPAuth = true;     // turn on SMTP authentication
				$mail->Username = $brandInfo['smtp_user'];  // SMTP username
				$mail->Password = $brandInfo['smtp_password']; // SMTP password
			}
			
		} elseif (SMTP_SERVER!='') {
			$mail->IsSMTP();            // set mailer to use SMTP
			$mail->Host = SMTP_SERVER;  // specify main and backup server
			if (SMTP_AUTH=='true') {
				$mail->SMTPAuth = true;     // turn on SMTP authentication
				$mail->Username = SMTP_USER;  // SMTP username
				$mail->Password = SMTP_PASS; // SMTP password
			}
		}
	      	
		$errEmail='';
		$mail->From = $fromEmail;
		$mail->FromName = $fromName;

		if ($toName!='')
			$mail->AddAddress($toEmail, $toName);
		else {
			$toEmail=str_replace(",", " ", $toEmail);
			$toEmail=str_replace(";", " ", $toEmail);
			$toList=explode(" ", $toEmail);
			$count=count($toList);
			for ($i=0; $i<$count; $i++) {
				$toItem=trim($toList[$i]);
				if ($toItem!='') {
					if (valid_email($toItem))
						$mail->AddAddress($toItem);
					else
						$errEmail.="$toItem ";
				}
			}
		}
		
		$mail->WordWrap = 80;                                 // set word wrap to 50 characters
	//	$mail->AddAttachment("/var/tmp/file.tar.gz");         // add attachments
	//	$mail->AddAttachment("/tmp/image.jpg", "new.jpg");    // optional name
//		$mail->IsHTML($html);
		// if the email contains an html link flag, set the body type to html and replace all line breaks with <br>
//		if (strpos($body, "<a href=")>=0) {
		if ($isHtml) {
			$mail->IsHTML(true);
//			$body=str_replace("\n", "<br>", $body);
		}
		
		if ($attachData!='') {
			$mail->AddStringAttachment($attachData, $attachFileName, "base64", $attachType);
		}

		$mail->Subject = $subject;
		$mail->Body    = $body;
	//	$mail->AltBody = "This is the body in plain text for non-HTML mail clients";
	
		if ($bcc) {
			foreach ($bcc as $abcc) {
				$mail->AddBCC($abcc);				
			}
		}
		if ($smtpLog!=null)
			ob_start();
		if(!$mail->Send())
		{
			if ($smtpLog!=null) {
				$smtpLog=ob_get_contents();
				ob_end_clean();
			}
			return "Mailer error: " . $mail->ErrorInfo;
		} else if ($errEmail!='')
			return "Email was not sent to these illegal addresses: $errEmail";
		
		if ($smtpLog!=null) {
			$smtpLog=ob_get_contents();
			ob_end_clean();
		}
		return ''; 
/*
		if ($fromName=='')
			$from=$fromEmail;
		else
			$from="$fromName <$fromEmail>";
			
		if ($toName=='')
			$to=$toEmail;
		else
			$to="$toName <$toEmail>";
		
		$body=str_replace("\r\n", "\n", $body);	
	   	$headers="From: $from\r\n";
		if (!mail($to, $subject, $body, $headers))
			return "Couldn't send email from the mail server.";
			
		return '';
*/					
	}
	
}

?>