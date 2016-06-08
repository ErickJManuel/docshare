<?php
/**
 * Persony Web Conferencing 2.0
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2008 Persony, Inc.
 * @version     2.0
 * 
 */
include_once("includes/meetings_common.php");

	function GetTwitterShareHtml($siteUrl, $meetingInfo, $timeZone) {
		global $twitterIcon;
		
		$meetingUrl=$siteUrl."?meeting=".$meetingInfo['access_id'];

		$schedule='';
		if ($meetingInfo['status']!='REC' && $meetingInfo['scheduled']=='Y') {
			$dtime=$meetingInfo['date_time'];

			GetTimeZoneName($timeZone, $tzName, $tz);
			
			$meetDateTimeStr='';
			if ($tz!='')
				VObject::ConvertTZ($dtime, 'SYSTEM', $tz, $meetDateTimeStr);

			if ($meetDateTimeStr!='') {
				list($dateStr, $time24Str)=explode(" ", $meetDateTimeStr);
				list($year, $mon, $day)=explode("-", $dateStr);
				list($meetHour, $meetMin, $meetSec)=explode(":", $time24Str);
				$theDate=mktime($meetHour, $meetMin, $meetSec, $mon, $day, $year);
				$meetingTime=H24ToH12($meetHour, $meetMin);
				$meetingDate=date('M d', $theDate);
				$schedule=$meetingDate." ".$meetingTime." ".$timeZone;
			}
		}

		$twitterStr=$meetingInfo['title'];
		$twitterStr.=" ".$meetingUrl;
		if ($schedule!='')
			$twitterStr.=" ".$schedule;
		$twitterStr.=" #webconferencing";
		$twitterUrl="http://twitter.com/home?status=".urlencode($twitterStr);		
		
		return "<a href=\"$twitterUrl\" rel=\"nofollow\" target=\"_blank\"><img style=\"vertical-align: middle;\" alt=\"Twitter\" src=\"$twitterIcon\" ></a> <a href=\"$twitterUrl\" target=\"_blank\">Twitter</a>";		
	}

	function GetFacebookShareHtml($siteUrl, $meetingInfo, $timeZone, $siteName, $hostPictId=0) {
		global $facebookIcon;
		require_once("dbobjects/vimage.php");
		
		$meetingUrl=$siteUrl."?meeting=".$meetingInfo['access_id'];
		$schedule='';
		if ($meetingInfo['status']!='REC' && $meetingInfo['scheduled']=='Y') {
			$dtime=$meetingInfo['date_time'];

			GetTimeZoneName($timeZone, $tzName, $tz);
			
			$meetDateTimeStr='';
			if ($tz!='')
				VObject::ConvertTZ($dtime, 'SYSTEM', $tz, $meetDateTimeStr);

			if ($meetDateTimeStr!='') {
				list($dateStr, $time24Str)=explode(" ", $meetDateTimeStr);
				list($year, $mon, $day)=explode("-", $dateStr);
				list($meetHour, $meetMin, $meetSec)=explode(":", $time24Str);
				$theDate=mktime($meetHour, $meetMin, $meetSec, $mon, $day, $year);
				$meetingTime=H24ToH12($meetHour, $meetMin);
				$meetingDate=date('M d', $theDate);
				$schedule=$meetingDate." ".$meetingTime." ".$tzName;
			}
		}

	
		$fbTitle=$meetingInfo['title'];
		if ($schedule!='')
			$fbTitle.=": ".$schedule;
			
		$fbDesc=$meetingInfo['description'];
		if ($fbDesc=='')
			$fbDesc=$siteName;
		if ($fbDesc=='')
			$fbDesc=_Text("Web Conferencing");	
			
		$userPict='';
		if ($hostPictId>0) {
			$pict=new VImage($hostPictId);
			if ($pict->GetValue('file_name', $pictFile)==ERR_NONE) {
				$userPict=SITE_URL.VImage::GetFileUrl($pictFile);
			}
		}
		
		$fbStr=$meetingUrl."&title=".$fbTitle."&d=".$fbDesc;
		if ($userPict!='')
			$fbStr.="&i=".$userPict;

		$fbStr.="&".rand();
		$fbUrl="http://www.facebook.com/sharer.php?u=".urlencode($fbStr);
	
		return "<a href=\"$fbUrl\" rel=\"nofollow\" target=\"_blank\"><img style=\"vertical-align: middle;\" alt=\"Facebook\" src=\"$facebookIcon\" ></a> <a href=\"$fbUrl\" target=\"_blank\">Facebook</a>";		
	}
?>