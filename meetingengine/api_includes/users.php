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

require_once("dbobjects/vuser.php");
require_once("dbobjects/vgroup.php");
require_once("dbobjects/vlicense.php");

$memberId=GetSessionValue('member_id');
$memberPerm=GetSessionValue('member_perm');
$memberBrand=GetSessionValue('member_brand');

if ($memberId=='')
	return API_EXIT(API_ERR, "Not signed in.");
if ($memberPerm!='ADMIN')
	return API_EXIT(API_ERR, "Not authorized.");

$query=GetSessionValue('member_query');
/*
GetArg("query", $query);
if (strpos($query, "brand_id='$memberBrand'")===false)
	return API_EXIT(API_ERR, "Invalid query.");
*/
GetArg("format", $format);
GetArg("filename", $fileName);
if ($fileName=='')
	$fileName="members_".time();
GetArg("sortby", $sortBy);
if ($sortBy=='')
	$sortBy="create_date";

$reverseSort=false;
if ($sortBy=='create_date')
	$reverseSort=true;

$errMsg=VObject::SelectAll(TB_USER, $query, $result, 0, 0, "*", $sortBy, $reverseSort);
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
	echo "date,user_id,login,first,last,company,title,email,phone,account,permission,group,active\n";
	$groupId=0;
	$licenseId=0;
	$groupName='';
	$licenseName='';
	while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
		if ($row['login']==ROOT_USER)
			continue;
		if ($row['group_id']!=$groupId) {
			$group=new VGroup($row['group_id']);
			$group->GetValue('name', $groupName);
		}
		
		if ($row['license_id']!=$licenseId) {
			$license=new VLicense($row['license_id']);
			$license->GetValue('name', $licenseName);
		}
		
		$items=explode(" ", $row['create_date']);
		echo $items[0].",";
		echo $row['access_id'].",";
		echo $row['login'].",";
		echo RemoveComma($row['first_name']).",";
		echo RemoveComma($row['last_name']).",";
		echo RemoveComma($row['org']).",";
		echo RemoveComma($row['title']).",";
		echo RemoveComma($row['email']).",";
		echo RemoveComma($row['phone']).",";
		echo $licenseName.",";
		echo $row['permission'].",";		
		echo $groupName.",";
		echo $row['active']."\n";	
		
	}
	
	API_EXIT(API_NOMSG);
}


?>