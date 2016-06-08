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

require_once("dbobjects/vregistration.php");
require_once("dbobjects/vmeeting.php");
require_once("dbobjects/vregform.php");

$memberId=GetSessionValue('member_id');
$memberPerm=GetSessionValue('member_perm');
$memberBrand=GetSessionValue('member_brand');

if ($memberId=='')
	return API_EXIT(API_ERR, "Not signed in.");
//if ($memberPerm!='ADMIN')
//	return API_EXIT(API_ERR, "Not authorized.");


if ($cmd=='DELETE_REGISTRATION') {
	GetArg('id', $regId);
	if ($regId=='')
		return API_EXIT(API_ERR, "Missing registration id.");
	
	$reg=new VRegistration($regId);
	if ($reg->Drop()!=ERR_NONE)
		API_EXIT(API_ERR, $reg->GetErrorMsg());

	return;
}

// the query is set in the login session
//GetArg("query", $query);
$query=GetSessionValue('member_query');
if ($query=='')
	API_EXIT(API_ERR, "query data not set.");

GetArg("format", $format);
GetArg("filename", $fileName);
if ($fileName=='')
	$fileName="members_".time();

GetArg('time_zone', $tz);
if ($tz=='')
	$tz="+00:00";
elseif ($tz[0]==' ')
	$tz[0]='+';
	
$select="*, CONVERT_TZ(date_time, 'SYSTEM', '$tz') as tz_time";

$errMsg=VObject::SelectAll(TB_REGISTRATION, $query, $result, 0, 0, $select, "meeting_id", true);
if ($errMsg!='') {
	API_EXIT(API_ERR, $errMsg);
}

$num_rows = mysql_num_rows($result);
$rowCount=0;

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
	echo "#Title: Registrations\n";
	echo "#Timezone: $tz\n";


	$lastMeetingId=0;
	$meetingTitle='';
	$meetingDate='';
	$meetingAccessId='';
	$formInfo=null;
	$max=VRegForm::$maxFields;
	while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {	
		if ($formInfo==null || $formInfo['id']!=$row['regform_id']) {
			$formInfo=array();
			if ($row['regform_id']=='0') 
				VRegForm::GetDefault($formInfo);
			else {
				$regForm=new VRegForm($row['regform_id']);
				$regForm->Get($formInfo);
			}
//			$colNames="meeting,date,emal,name";
			$colNames="meeting_id,meeting_title,meeting_date,registration_date,emal,name";
			for ($i=1; $i<=$max; $i++) {
				$key="key_".$i;
				$field="field_".$i;
				if (isset($formInfo)) {
					$keyVal=$formInfo[$key];
				} else {
					$keyVal=$field;
				}

				$colNames.=",".$keyVal;

			}
			$colNames.="\n";
			echo $colNames;
		}	
		$meetingId=$row['meeting_id'];
		if ($meetingId!=$lastMeetingId) {
			$meeting=new VMeeting($meetingId);
			$meeting->Get($meetingInfo);
			// don't export this one because it doesn't belong to me
			if ($meetingInfo['host_id']!=$memberId)
				continue;
			$meetingTitle=$meetingInfo['title'];
			if ($meetingInfo['scheduled']=='Y' && isset($meetingInfo['date_time']) && $meetingInfo['date_time']!='') {
				VObject::ConvertTZ($meetingInfo['date_time'], 'SYSTEM', $tz, $meetingDate);				
			} else
				$meetingDate='';
			$meetingAccessId=$meetingInfo['access_id'];
			$lastMeetingId=$meetingId;
		}

		echo $meetingAccessId.",";
		echo RemoveComma($meetingTitle).",";
		echo $meetingDate.",";
//		echo $row['date_time'].",";
		echo $row['tz_time'].",";
		echo RemoveComma($row['email']).",";
		echo RemoveComma($row['name']).",";
		for ($i=1; $i<=$max; $i++) {
			$field="field_".$i;
			echo RemoveComma($row[$field]).",";
		}
		echo "\n";	
		
	}
	
	API_EXIT(API_NOMSG);
}


?>