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


require_once("dbobjects/vmeeting.php");

$memberId=GetSessionValue('member_id');
if ($memberId!='') {

/*	Don't end the meeting when the member signs out because VMeeting::IsMeetingInProgress doesn't work
// all the time and we may end a meeting when someone else is still in it.
// End all idle meetings in cron.php

	// end all idle meetings of the user
	$query="host_id= '$memberId' AND (status='START' OR status='START_REC')";
	$errMsg=VObject::SelectAll(TB_MEETING, $query, $result1);
	if ($errMsg!='') {
		// ignore errors
		//ShowError($errMsg);
	} else {
		$num_rows = mysql_num_rows($result1);
		while ($row = mysql_fetch_array($result1, MYSQL_ASSOC)) {			
			// only end the meeting if it is not in progress (no current attendees)
			if (!VMeeting::IsMeetingInProgress($row)) {
				$ameeting=new VMeeting($row['id']);
				if ($ameeting->EndMeeting()!=ERR_NONE) {
					// ignore errors
					//ShowError("EndMeeting ".$ameeting->GetErrorMsg());
				}
			}
		}
	}
*/
	
	$member=new VUser($memberId);
	$member->GetValue('permission', $memberPerm);
	if ($memberPerm=='ADMIN') {
		$startMessage='';
		
		include_once("dbobjects/vprovider.php");
		$provider=new VProvider($gBrandInfo['provider_id']);
		$provider->Get($providerInfo);
		if (isset($providerInfo['licensekey_id']))
			$licId=$providerInfo['licensekey_id'];
		
		VProvider::ParseLicenseKey($providerInfo, $licenseType, $licenseCounts, $recording);
			
		// If the site is enabled for installing a license key (SMB sites)
		if ($licenseType=='S' || $gBrandInfo['enable_licensekey']=='SITE' || $gBrandInfo['enable_licensekey']=='ALL') {
			// no license key is installed for the site (trial sites) and PURCHASE_PAGE is defined in the config file
			if ((!isset($licId) || $licId=='0') && defined("PURCHASE_PAGE") && constant("PURCHASE_PAGE")!='') {
				// instruct the user where to install a license key
				$url=PURCHASE_PAGE;
//				$startMessage="Go to <a target='$target' href='$url'>Administration/Accounts</a> page to install a license key.";					
				$startMessage="Upgrade your Trial Site at <a target='_blank' href='$url'>$url</a>";
			}
		}		
		
		$silent=true;
		require_once('vinstall/vversion.php');
		$silent=false;
		$query="number>'$version'";
		if ($GLOBALS['SITE_LEVEL']=='')
			$query.=" AND (type='final')";
		elseif ($GLOBALS['SITE_LEVEL']=='beta')
			$query.=" AND (type='final' || type='beta')";
		$errMsg=VObject::Count(TB_VERSION, $query, $numNewVer);
		if ($numNewVer>0) {
			$target=$GLOBALS['TARGET'];
			$url=$GLOBALS['BRAND_URL']."?page=".PG_ADMIN_SITE;
			if (SID!='')
				$url.="&".SID;
				
			if ($startMessage!='')
				$startMessage.="<p>";
			$startMessage.="New version is available. Click <a target=$target href='$url'>Administration/Website</a> to upgrade.";	
		}

		SetSessionValue("start_message", $startMessage);

	}

}
/*	
require_once("dbobjects/vuser.php");

	// delete all guest_user meetings not in progress for the brand
	$brandId=$GLOBALS['BRAND_ID'];
	$query="brand_id= '$brandId' AND login='".VUSER_GUEST."'";
	$errMsg=VObject::SelectAll(TB_USER, $query, $result2);
	if ($errMsg!='') {
		//LogError($errMsg);
	} else {
		$num_rows = mysql_num_rows($result2);
		while ($row = mysql_fetch_array($result2, MYSQL_ASSOC)) {
			
			// find all meetings for the user
			$query1="host_id='".$row['id']."'";
			$errMsg=VObject::SelectAll(TB_MEETING, $query1, $result3);			
			$num_rows1 = mysql_num_rows($result3);
			while ($row1 = mysql_fetch_array($result3, MYSQL_ASSOC)) {
				// only delete the meeting if it is not in progress (no current attendees)
				if (!VMeeting::IsMeetingInProgress($row1)) {
					$ameeting=new VMeeting($row1['id']);
					if ($ameeting->DeleteMeeting()!=ERR_NONE) {
						// ignore errors
						ShowError("DeleteMeeting ".$ameeting->GetErrorMsg());
					}
				}				
			}
	
		}
	}
*/
?>