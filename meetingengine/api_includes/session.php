<?php
/**
 * Persony Web Conferencing 2.0
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2008 Persony, Inc.
 * @version     2.0
 * 
 */
// The script will exit if it's run as the top-level script, but will not exit if it's included from another script. 
if (isset($_SERVER['SCRIPT_FILENAME']) && realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME'])) exit;

require_once("dbobjects/vsession.php");

// CONVERT_TZ MySQL function crashes on MySQL 4.1.9 when used in WHREE
// it works on MySQL 5.0.37
// not sure what version is needed so set it to 5.0.0 for now
/*
if (phpversion()<'5.0.0')
	$convertTz=false;
else
	$convertTz=true;	
*/


GetArg('user', $userLogin);
GetArg('meeting', $meetingId);
GetArg('date', $date);
GetArg('from_date', $fromDate);
GetArg('to_date', $toDate);
GetArg('group_id', $groupId);
GetArg('brand_id', $brandId);
//GetArg('brand', $brandId);
GetArg('provider_id', $providerId);
GetArg('brand', $brandName);
GetArg('format', $format);
GetArg("file_name", $fileName);

if ($providerId=='' && $brandName=='' && $brandId=='') {
	return API_EXIT(API_ERR, "Missing input prameter.");	
}

// to get info about the provider, we need to log in as a provider
if ($providerId!='') {
	$pid=GetSessionValue('provider_id');
	if ($pid=='' || $pid!=$providerId)
		return API_EXIT(API_ERR, "Not authorized to get info about this provider.");
}

if ($brandName!='') {
	VObject::Find(TB_BRAND, 'name', $brandName, $brandInfo);
	if (!isset($brandInfo['id']))
		return API_EXIT(API_ERR, "Brand '$brandName' does not match our records");	

	$brandId=$brandInfo['id'];
}

$memberBrand=GetSessionValue('member_brand');
$memberPerm=GetSessionValue('member_perm');
$memberId=GetSessionValue('member_id');

// to get info about the brand, we need to log in as a brand user or as a provider
if ($brandId!='' && $providerId=='') {
	if ($memberBrand!=$brandId)
		return API_EXIT(API_ERR, "Not authorized to get info about this brand.");
}

// if the user is not an admin of the brand or the provider, he can only get info about his account or meeting
if ($memberPerm!='ADMIN' && $providerId=='') 
{
	if ($meetingId!='') {
		VObject::Find(TB_MEETING, 'access_id', $meetingId, $meetingInfo);
		if (!isset($meetingInfo['id']))
			return API_EXIT(API_ERR, "Meeting $meegingId not found.");
		
		if ($meetingInfo['host_id']!=$memberId) 
			return API_EXIT(API_ERR, "Not authorized to get info about this meeting.");
	}	
		
	if ($userLogin!='') {
		$query="brand_id='".$memberBrand."' AND LOWER(login)='".addslashes(strtolower($userLogin))."'";		
		$errMsg=VObject::Select(TB_USER, $query, $userInfo);

		if (!isset($userInfo['id']))
			return API_EXIT(API_ERR, "User $userLogin not found.");
		
		if ($userInfo['id']!=$memberId) 
			return API_EXIT(API_ERR, "Not authorized to get info about this user.");
	}
}


$dQuery='1';

if ($meetingId!='') {
	$dQuery.=" AND (meeting_aid='$meetingId')";
} elseif ($userLogin!='') {
	$dQuery.=" AND (LOWER(host_login)='".addslashes(strtolower($userLogin))."')";		
}

$startTime=$endTime='';

GetArg('time_zone', $tz);
if ($tz=='')
	$tz="+00:00";
elseif ($tz[0]==' ')
	$tz[0]='+';

if ($fromDate!='')
	$startTime=$fromDate." 00:00:00";

if ($toDate!='')
	$endTime=$toDate." 23:59:59";

//$reportDate="";

if ($date=='ALL' || $date=='') {

} elseif ($date=='NOW') {
	$dQuery.=" AND ".VSession::GetInProgressQuery();
} elseif ($date!='') {
	$dateItems=explode("-", $date);
	$dtCount=count($dateItems);
	
	// month specified
	if ($dtCount==2) {
		$startTime=$date."-"."01 00:00:00";
		
		$lastDay='31';
		$yy=$dateItems[0];
		$mm=$dateItems[1];
		if ($mm=='2') {
			if ($yy%4==0)
				$lastDay='29';
			else
				$lastDay='28';
		} else if ($mm=='4'||$mm=='6'||$mm=='9'||$mm=='11')
			$lastDay='30';
		$endTime=$date."-"."$lastDay 23:59:59";
/*
		$year=$dateItems[0];
		$mn=$dateItems[1];

		if ($convertTz)		
			$dQuery.=" AND (YEAR(CONVERT_TZ(start_time, 'SYSTEM', '$tz'))='$year' AND MONTH(CONVERT_TZ(start_time, 'SYSTEM', '$tz'))='$mn')";
		else
			$dQuery.=" AND (YEAR(start_time)='$year' AND MONTH(start_time)='$mn')";
		
		$reportDate=$date;
*/	
	// date specified
	} else if ($dtCount==3) {
//		$reportDate=$date;
/*
		if ($convertTz)
			$dQuery.=" AND (DATE(CONVERT_TZ(start_time, 'SYSTEM', '$tz'))='$date')";	
		else
			$dQuery.=" AND (DATE(start_time)='$date')";	
*/
		$startTime=$date." 00:00:00";
		$endTime=$date." 23:59:59";		

	}
	
}

if ($startTime!='') {
	VObject::ConvertTZ($startTime, $tz, 'SYSTEM', $sysStartTime);
	$dQuery.=" AND (start_time>='$sysStartTime')";
}
if ($endTime!='') {
	VObject::ConvertTZ($endTime, $tz, 'SYSTEM', $sysEndTime);	
	$dQuery.=" AND (start_time<='$sysEndTime')";	
}	
	
if ($groupId!='') {
	// find all users of the group
	$groupQuery="group_id='$groupId'";
	$errMsg=VObject::SelectAll(TB_USER, $groupQuery, $groupResult);
	if ($errMsg!='') {
		ShowError($errMsg);
		return;
	}
	
	$num_rows = mysql_num_rows($groupResult);
	
	$userQuery='';
	while ($auser = mysql_fetch_array($groupResult, MYSQL_ASSOC)) {
		if ($userQuery!='')
			$userQuery.=" OR ";
		// for each user, add a query
		$userQuery.="LOWER(host_login)='".addslashes(strtolower($auser['login']))."'";
	}
	if ($userQuery!='') {
		$dQuery.=" AND ($userQuery)";
	} else {
		$dQuery.=" AND (0)"; // no users in the group
	}
}


$brandQuery='';
if ($providerId!='')
	$brandQuery.="provider_id='".$providerId."'";


if ($brandId!='') {
	if ($brandQuery!='')
		$brandQuery.=" AND ";
	$brandQuery.="(id = '$brandId')";
}

$errMsg=VObject::SelectAll(TB_BRAND, $brandQuery, $brandResults);
if ($errMsg!='') {
	return API_EXIT(API_ERR, $errMsg);
}

//echo $dQuery; exit();

if ($format=='' || $format=='csv') {
	// must add this for IE7 to work on SSL download
	header('Pragma: private');
	header('Cache-control: private, must-revalidate');
	
	if ($fileName!='') {

		header("Content-Type: text/csv; name=\"$fileName.csv\"");	
		header("Content-Disposition: attachment; filename=$fileName.csv");
	} else {
		header("Content-Type: text/csv");
	}
	
	echo "#Title: Meeting session report\n\n";
	echo "#Period: from $startTime to $endTime\n";
	echo "#Timezone: $tz\n";
	echo "#SI: Session ID\n";
	echo "#PN: Meeting participant name\n";
	echo "#ST: Meeting participant start time\n";
	echo "#ET: Meeting participant end time\n";
	echo "#BT: Meeting participant break time--time away between start and end time (in seconds)\n";
	echo "#DT: Meeting participant duration (in seconds). DT=ET-ST-BT\n";	
	echo "#WT: Webcam usage time (in seconds).\n";	
	echo "#PA: Meeting participant IP address\n";
	echo "#MI: Meeting ID\n";
	echo "#GI: Group ID\n";
	echo "#HL: Meeting host login email\n";
	echo "#AT: Account type\n";
	echo "#NP: Number of participants in the session\n";
//	echo "#MP: Max. number of concurrent participants in the session\n";
	echo "#CD: Meeting client data\n";
	echo "\n";

	// for each brand
	while ($abrandInfo = mysql_fetch_array($brandResults, MYSQL_ASSOC)) {
		
		$url=$abrandInfo['site_url'];
		
		echo "#URL: ".$url."\n";
//		echo "SI,PN,ST,ET,BT,DT,PA,MI,GI,HL,AT,NP,MP,CD\n";
		echo "SI,PN,ST,ET,BT,DT,WT,PA,MI,GI,HL,AT,NP,CD\n";
		
		$sessQuery="brand_id='".$abrandInfo['id']."' AND ".$dQuery;
		$sessQuery.=" AND (host_login <> '')";	// exclude replay sessions, which has blank license_code
		$errMsg=VObject::SelectAll(TB_SESSION, $sessQuery, $sessResults);
		if ($errMsg!='')
			return API_EXIT(API_ERR, $errMsg);
	
		$userInfo=array();
		$lastUser='';
		while ($sessInfo = mysql_fetch_array($sessResults, MYSQL_ASSOC)) {
			
			$sessId=$sessInfo['id'];
			$meetId=$sessInfo['meeting_aid'];
			$host=$sessInfo['host_login'];
			$cd=$sessInfo['client_data'];
						
			if ($host!=$lastUser) {
				unset($userInfo);
				$query="brand_id='".$abrandInfo['id']."' AND LOWER(login)='".addslashes(strtolower($host))."'";
				$errMsg=VObject::Select(TB_USER, $query, $userInfo);			
//				VObject::Find(TB_USER, 'login', $host, $userInfo);
				$lastUser=$host;
			}
			if (isset($userInfo['group_id']))
				$grpId=$userInfo['group_id'];
			else
				$grpId='n/a';
/*			
			$max=$sessInfo['max_concur_att'];
			if ($max=='0')
				$max='1';
*/
			$code=$sessInfo['license_code'];
			if ($code=='')
				$code='n/a';
			
			$attQuery="session_id='".$sessInfo['id']."'";
//			$attSelect="user_name, user_ip, start_time, mod_time, break_time, (TIME_TO_SEC(TIMEDIFF(mod_time, start_time))-break_time) as duration";
			$attSelect="user_name, user_ip, CONVERT_TZ(start_time, 'SYSTEM', '$tz') as start_time, CONVERT_TZ(mod_time, 'SYSTEM', '$tz') as mod_time, break_time, (TIME_TO_SEC(TIMEDIFF(mod_time, start_time))-break_time) as duration, cam_time";
			$errMsg=VObject::SelectAll(TB_ATTENDEE, $attQuery, $attResults, 0, 0, $attSelect);
			if ($errMsg!='')
				return API_EXIT(API_ERR, $errMsg);
			
			$num=mysql_num_rows($attResults);
/* don't include live session data because they are no longer stored in the database
			// see if the session is live
			if ($num==0) {
				$errMsg=VObject::SelectAll(TB_ATTENDEE_LIVE, $attQuery, $attResults, 0, 0, $attSelect);
			}
*/
			if ($errMsg!='')
				return API_EXIT(API_ERR, $errMsg);
				
			$num=mysql_num_rows($attResults);
			while ($attInfo = mysql_fetch_array($attResults, MYSQL_ASSOC)) {
				$attName=RemoveComma($attInfo['user_name']);
				$attIp=$attInfo['user_ip'];
				$attStart=$attInfo['start_time'];
				$attEnd=$attInfo['mod_time'];
				$break=$attInfo['break_time'];
				$dur=$attInfo['duration'];
				$camTime=$attInfo['cam_time'];
//				echo "$sessId,$attName,$attStart,$attEnd,$break,$dur,$attIp,$meetId,$grpId,$host,$code,$num,$max,$cd\n";				
				echo "$sessId,$attName,$attStart,$attEnd,$break,$dur,$camTime,$attIp,$meetId,$grpId,$host,$code,$num,$cd\n";				
			}
				
		}
		
		echo "\n";

	}
	exit;

} elseif ($format=='xml') {
	// must add this for IE7 to work on SSL download
	header('Pragma: private');
//	header('Cache-control: no-cache, must-revalidate');
	header('Cache-control: private, must-revalidate');
	
	if ($fileName!='') {

		header("Content-Type: text/xml; name=\"$fileName.xml\"");	
		header("Content-Disposition: attachment; filename=$fileName.xml");	
	} else {
		header("Content-Type: text/xml");
	}

	echo XML_HEADER."\n";
	echo "<reports>\n";
	echo "<from_time>$startTime</from_time>\n";
	echo "<to_time>$endTime</to_time>\n";
	echo "<time_zone>$tz</time_zone>\n";
		
	if ($userLogin!='')
		echo "<user>$userLogin</user>\n";
	if ($meetingId!='')
		echo "<meeting_id>$meetingId</meeting_id>\n";

print <<<END
	<comments>
	<comment>The report contains a list of sessions from 'start_date' to 'end_date'. Each session is one instance of a meeting</comment>
	<comment>All date and time info is specified in the 'time_zone' time</comment>
	<comment>'meeting_host' is either a member's login id or the DNS name of the host's IP address if the host is not a member.</comment>
	<comment>Each session contains a list of attendees</comment>
	<comment>'break_time' in each attendee record is the time in seconds the attendee is absent between 'start_time' and 'end_time' (the attendee leaves and rejoins a meeting.)</comment>
	<comment>'duration' is the time in seconds the attendee is present during the meeting. It's computed from 'end_time'-'start_time'-'break_time'</comment>
	</comments>

END;
	
	// for each brand
	while ($abrandInfo = mysql_fetch_array($brandResults, MYSQL_ASSOC)) {
	
//		echo "<url>".$abrandInf['site_url']."</url>\n";
	
		echo "<sessions>\n";
//FIXME: construct the query to the sessions. NOT DONE YET.

		$aquery=''; //???
		$errMsg=VObject::SelectAll(TB_SESSION, $aquery, $aresult, 0, 0, "*", "start_time", true);		
			
		while ($row = mysql_fetch_array($aresult, MYSQL_ASSOC)) {
			echo (VSession::GetXml($row, $tz));
		}

		echo "</sessions>\n";

	}
	echo "</reports>\n";
	API_EXIT(API_NOMSG);
	
}

?>