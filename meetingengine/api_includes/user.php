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
require_once("dbobjects/vviewer.php");
require_once("dbobjects/vbrand.php");
require_once("dbobjects/vlicense.php");
require_once("dbobjects/vprovider.php");
require_once("dbobjects/vteleserver.php");
require_once("dbobjects/vmeeting.php");
require_once("dbobjects/vimage.php");
require_once('api_includes/common.php');


$brandId='0';
$brandInfo=array();
$userInfo=array();

if ($cmd=='GET_VCARD') {
	
	if (GetArg('user', $access_id)) {

		VObject::Find(TB_USER, 'access_id', $access_id, $userInfo);
		if (!isset($userInfo['id']))
			return API_EXIT(API_ERR, "User not found");

	} else if (GetArg('user_id', $userId)) {
		
		$user=new VUser($userId);
		if ($user->Get($userInfo)!=ERR_NONE)
			return API_EXIT(API_ERR, $user->GetErrorMsg());
		
		if (!isset($userInfo['id']))
			return API_EXIT(API_ERR, "User not found");
	}
	
//	$userName=VUser::GetFullName($userInfo);
	$fileName="user_".$userInfo['access_id'];
	$vcf=VUser::GetVCard($userInfo);
	
	// must add this for IE7 to work on SSL download
	header('Pragma: private');
	header('Cache-control: private, must-revalidate');

	header("Content-Type: text/x-vcard; charset=us-ascii; name=\"$fileName.vcf\"");	
	header("Content-Disposition: attachment; filename=$fileName.vcf");	
	echo $vcf;
	API_EXIT(API_NOMSG);
	
/*	
} elseif ($cmd=='GET_UPLOAD_INFO') {
@include_once("download/vpresent_version.php");
	$version='';
	if (isset($vpresent_version))
		$version=$vpresent_version;
		
	$minVersion='';
	if (isset($required_version))
		$minVersion=$required_version;		

	$siteUrl=SITE_URL;
	$downloadUrl=VWebServer::AddPaths($siteUrl, "download/download.php");
	
	if (!GetArg('user', $access_id) || $access_id=='')
		return API_EXIT(API_ERR, "Missing user");

	VObject::Find(TB_USER, 'access_id', $access_id, $userInfo);
	if (!isset($userInfo['id']))
		return API_EXIT(API_ERR, "User not found");	
			
	$xml=XML_HEADER."\n";
	$xml.=VUser::GetUploadXML($userInfo, $version, $minVersion, $downloadUrl);
	// must add this for IE7 to work on SSL download
	header('Pragma: private');
	header('Cache-control: private, must-revalidate');

	header("Content-Type: text/xml");
	echo $xml;		
	API_EXIT(API_NOMSG);
*/	
	
} else if ($cmd=='SET_USER' || $cmd=='ADD_USER') {
require_once("dbobjects/vlicense.php");
require_once("dbobjects/vprovider.php");
require_once("dbobjects/vmailtemplate.php");
require_once("dbobjects/vlicense.php");
require_once("dbobjects/vmeeting.php");
//require_once("api_includes/user_common.php");
	
	$trial=false;
	$licId='0';
	$licInfo=array();
	if (GetArg('license', $arg)) {
		VObject::Find(TB_LICENSE, 'code', $arg, $licInfo);
		if (!isset($licInfo['id']))
			return API_EXIT(API_ERR, "License code '$arg' not found");
			
		$licId=$licInfo['id'];
		if ($licInfo['trial']=='Y')
			$trial=true;
	} else if (GetArg('license_id', $arg)) {
		$licId=$arg;
		$license=new VLicense($licId);
		$license->Get($licInfo);
		if (!isset($licInfo['id']))
			return API_EXIT(API_ERR, "License id '$arg' not found");
		if ($licInfo['trial']=='Y')
			$trial=true;		
	}

	if ($cmd=='SET_USER') {
		
		$userErrMsg='';
		if (GetArg('user', $arg) && $arg!='') {
			VObject::Find(TB_USER, 'access_id', $arg, $userInfo);
			if (isset($userInfo['id']))
				$user=new VUser($userInfo['id']);	
			else
				$userErrMsg="User not found";	
		} else if (GetArg('user_id', $arg) && $arg!='') {
			$user=new VUser($arg);
			if ($user->Get($userInfo)!=ERR_NONE)
				$userErrMsg=$user->GetErrorMsg();
				
			elseif (!isset($userInfo['id']))
				$userErrMsg="User not found";	
		}
		
		if ($userErrMsg!='')	
			return API_EXIT(API_ERR, $userErrMsg);

		$memberId=GetSessionValue('member_id');
		$memberPerm=GetSessionValue('member_perm');
		$memberBrand=GetSessionValue('member_brand');

		if ($memberId=='')
			return API_EXIT(API_ERR, "Not signed in");
			
		$isAdmin=false;
//		if ($memberId!=$userId) {
			// check if the member is an admin
//			$member=new VUser($memberId);
//			$memberInfo=array();
//			$member->Get($memberInfo);			
			
			// check if the member id an admin of the brand
//			$user->GetValue('brand_id', $userBrandId);
			if ($memberPerm!='ADMIN' || $memberBrand!=$userInfo['brand_id']) 
			{
				// if the member is not an admin and wants to set another user
				if ($memberId!=$userInfo['id'])
					return API_EXIT(API_ERR, "Not authorized", 'SET_USER');
					
				if ($userInfo['login']==VUSER_GUEST)
					return API_EXIT(API_ERR, "You cannot set the properties of a default user");

			} else {
				$isAdmin=true;
			}			
//		}
//		$user->GetValue('brand_id', $brandId);
		$brandId=$userInfo['brand_id'];
		$brand=new VBrand($brandId);
		$brand->Get($brandInfo);
		if ($brandInfo['status']=='INACTIVE')
			return API_EXIT(API_ERR, "The brand is not active");
				
		$newInfo=array();
		$newUser=new VUser($userInfo['id']);		
		
		if ($licId!=0) {
			
			$userLicId=$userInfo['license_id'];
			
			$userLicense=new VLicense($userLicId);
			$userLicense->Get($userLicInfo);
			
			$provider_id=$brandInfo['provider_id'];
			
			// if we are accessing this from the API ($api_exit is false) instead of from the UI
			// and the user has a non-trial license ($userLicInfo['trial']!='Y')
			// don't allow changing the license_id of the user if the provider has a "Name-user" licensing model
			// this is to prevent the provider from dynamically assign a user to a different license on-the-fly and share the same 'name-user' license.
			if ($api_exit==false && $licId!=$userLicId && isset($userLicInfo['trial']) && $userLicInfo['trial']!='Y') {
				
				$provider=new VProvider($provider_id);
				$providerInfo=array();				
				if ($provider->Get($providerInfo)!=ERR_NONE) {
					return API_EXIT(API_ERR, $provider->GetErrorMsg());	
				}
				
				VProvider::ParseLicenseKey($providerInfo, $licenseType, $licenseCounts, $recording);
				if ($licenseType=='N') {
					return API_EXIT(API_ERR, "Not allowed to change 'license_code' from the API for a 'Name-user' license.");	
				}
			}
			
			// if we are giving the user a non-trial license, make sure a license is available in the provider account
			if ($licId!=$userLicId && !$trial) {
				$provider_id=$brandInfo['provider_id'];
				
				//$licCode=$licInfo['code'];
				$avail=VProvider::GetLicenseAvailable($provider_id, $brandId, $licInfo);
				if ($avail==0) {
					return API_EXIT(API_ERR, "No available license in the provider account");	
				}			
			}
			$newInfo['license_id']=$licId;
		}
		
		// if this is not an admin, old_password is needed to change password
		if (!$isAdmin && ((GetArg('password', $password) && $password!='') || (GetArg('password1', $password1) && $password1!=''))) {
			GetArg('old_password', $oldPwd);
//			$user->GetValue('password', $oldUserPwd);
			if ($oldPwd!=$userInfo['password']) {
				return API_EXIT(API_ERR, "The current password you entered does not match our records.");	
			}
		}
		
		GetArg('permission', $permission);
		if (!$isAdmin && $permission=='ADMIN')
			return API_EXIT(API_ERR, "Not authorized to set this parameter.");
		else if ($memberId==$userInfo['id'] && $permission=='HOST') {
			return API_EXIT(API_ERR, "You cannot change your own permission.");
		} elseif ($userInfo['id']==$brandInfo['admin_id'] && $permission=='HOST') {
			return API_EXIT(API_ERR, "You cannot change the Site Administrator's permission.");			
		}
		$group=new VGroup($userInfo['group_id']);
		
/*		
		GetArg('conf_num', $teleNum);
		GetArg('conf_mcode', $teleMcode);
		GetArg('conf_pcode', $telePcode);
		
		if ($userInfo['conf_num']!=$teleNum || $userInfo['conf_mcode']!=$teleMcode 
				|| $userInfo['conf_pcode']!=$telePcode) 
		{
			
			$group=new VGroup($userInfo['group_id']);
			$group->GetValue('teleserver_id', $teleServerId);			
			
			$newInfo['conf_num']=$teleNum;
			$newInfo['conf_mcode']=$teleMcode;
			$newInfo['conf_pcode']=$telePcode;
		}	
*/	
	} else {
		
		// ADD_USER	
		if ($licId=='0'|| $licId==null) {
			return API_EXIT(API_ERR, "License code or id is missing");
		}

		GetArg('brand', $brandName);
		$brandId='';
		if ($brandName!='') {
			$errMsg=VObject::Find(TB_BRAND, 'name', $brandName, $brandInfo);
			if ($errMsg!='')
				return API_EXIT(API_ERR, $errMsg);
			if (!isset($brandInfo['id']))
				return API_EXIT(API_ERR, "Brand '$brandName' not found");
			if ($brandInfo['status']=='INACTIVE')
				return API_EXIT(API_ERR, "The brand is not active");
			
			$brandId=$brandInfo['id'];

		} else {
			return API_EXIT(API_ERR, "Brand name not set");			
		}
		
		if ($brandId=='0' || $brandId=='') {
			return API_EXIT(API_ERR, "Brand id is missing");
		}

		GetArg('permission', $permission);
		
		// adding a non-trial member or a member with ADMIN permission requires sign-in
		if (!$trial || $permission=='ADMIN') {
	
			$memberId=GetSessionValue('member_id');
			$memberPerm=GetSessionValue('member_perm');
			$memberBrand=GetSessionValue('member_brand');
			if ($memberId=='')
				return API_EXIT(API_ERR, "Not signed in");
				
			if ($memberPerm!='ADMIN' || $memberBrand!=$brandId) 
			{
				return API_EXIT(API_ERR, "Member is not an administrator.", 'ADD_USER');	
			}

		}
	
		GetArg('login', $login);
		$login=trim($login);
		$login=str_replace(" ", "_", $login);		
		if ($login=='')
			return API_EXIT(API_ERR, "login not set");
		else if ($login==VUSER_GUEST)
			return API_EXIT(API_ERR, "Illegal login name $login");
		else if ($login==ROOT_USER)
			return API_EXIT(API_ERR, "Illegal login name $login");
/* login id doesn't need to be an email	address
		if (GetArg('validate_login', $arg)) {
			require_once("includes/common_lib.php");
			if (valid_email($login)==false)
				return API_EXIT(API_ERR, "$login is not a valid email address.");
			
		}
*/
		$newUser=new VUser();	
		$newInfo=array();		

		$query="brand_id='".$brandId."' AND LOWER(login)='".addslashes(strtolower($login))."'";						
		VObject::Select(TB_USER, $query, $oldUserInfo);
		
		if (isset($oldUserInfo['id']))
			return API_EXIT(API_ERR, "$login already exists");	
		
		GetArg('group_id', $groupId);
		if ($groupId=='') {
			$groupId=$brandInfo['trial_group_id'];
//			return API_EXIT(API_ERR, "Missing group_id");
		}
		
		$group=new VGroup($groupId);
		$group->GetValue('brand_id', $groupBid);
		if ($groupBid!=$brandId)
			return API_EXIT(API_ERR, "Invalid group_id.");
		
		// check if the provider account has enough licenses for this type
//		if (!$trial) {
			//$licCode=$licInfo['code'];
			$provider_id=$brandInfo['provider_id'];
			$avail=VProvider::GetLicenseAvailable($provider_id, $brandId, $licInfo);
			if ($avail==0) {
				return API_EXIT(API_ERR, "No license available for this type in the provider account");	
			}
//		}

//		$user=new VUser();
		$newInfo['brand_id']=$brandId;	
		$newInfo['license_id']=$licId;
		$newInfo['group_id']=$groupId;

//		$userInfo['viewer_id']=$brandInfo['viewer_id'];
		$newInfo['login']=$login;
		$newInfo['create_date']=date('Y-m-d H:i:s');
		$newInfo['room_description']="";
		
		GetArg('conf_num', $confNum);
		GetArg('conf_num2', $confNum2);
		if (GetArg('free_conf', $arg) && $arg=='1' && $confNum=='' && $confNum2=='') {
				
			// if tele_num is not specified and free_conf is available for the group
			// get a free_conf number
			$group->GetValue('teleserver_id', $teleServerId);
			if ($teleServerId>0) {
				$teleServer=new VTeleServer($teleServerId);
				$teleServer->Get($teleInfo);
				
				if ($teleInfo['can_getconf']=='Y') {
					require_once("api_includes/free_conf.php");
					$resp=GetFreeConfManager($newInfo, $confMgr, $confUser, $confPass);
					if ($confMgr=='') {
						if ($resp!='')
							return API_EXIT(API_ERR, $resp);
						else
							return API_EXIT(API_ERR, "Free Conference Manager is not enabled.");
					} else {
						
						$freeNum=$freeMcode=$freePcode='';	
						$resp=FreeConfRequest($confMgr, $confUser, $confPass, $freeNum, $freeMcode, $freePcode);
						
						if ($freeNum=='') {
							return API_EXIT(API_ERR, "Free Conference Manager returned ".$resp);
						} else {
							$freeNum=AddSpacesToPhone($freeNum);
							
							$newInfo['conf_num']=$freeNum;
							$newInfo['conf_mcode']=$freeMcode;
							$newInfo['conf_pcode']=$freePcode;
							$newInfo['use_teleserver']='Y';
						}
					}	
				}

			}
		}
		

	}	
	

		
	if (GetArg('full_name', $arg)) {
		$words=explode(" ", $arg);
		if (count($words)>0)
			$newInfo['first_name']=$words[0];	
		if (count($words)>1)
			$newInfo['last_name']=$words[1];
	}
	
	$pass='';
	if (GetArg('password', $arg)) {
		$pass=trim($arg);
	}
	elseif (GetArg('password1', $pass1)) {
		GetArg('password2', $pass2);
		if ($pass1!=$pass2)
			return API_EXIT(API_ERR, "The passwords you entered do not match.");
		
		$pass=trim($pass1);
	}
	
	if (strlen($pass)>8)
		return API_EXIT(API_ERR, "Password must be no more than 8 characters.");
	elseif ($pass!='')
		$newInfo['password']=$pass;
		
	if (GetArg('first_name', $arg))
		$newInfo['first_name']=$arg;	
	if (GetArg('last_name', $arg))
		$newInfo['last_name']=$arg;
	if (GetArg('title', $arg))
		$newInfo['title']=$arg;
	if (GetArg('org', $arg))
		$newInfo['org']=$arg;
	if (GetArg('street', $arg))
		$newInfo['street']=$arg;
	if (GetArg('city', $arg))
		$newInfo['city']=$arg;
	if (GetArg('state', $arg))
		$newInfo['state']=$arg;
	if (GetArg('zip', $arg))
		$newInfo['zip']=$arg;
	if (GetArg('country', $arg))
		$newInfo['country']=$arg;
	if (GetArg('phone', $arg))
		$newInfo['phone']=$arg;
	if (GetArg('email', $arg)) {
		if ($arg!='' && !valid_email($arg)) {
			return API_EXIT(API_ERR, "The email address '$arg' is invalid.");
		}
		$newInfo['email']=$arg;
	}
	if (GetArg('room_description', $arg))
		$newInfo['room_description']=$arg;

	if (GetArg('group_id', $arg))
		$newInfo['group_id']=$arg;
	if (GetArg('active', $arg))
		$newInfo['active']=$arg;
	if (GetArg('webserver_id', $arg))
		$newInfo['webserver_id']=$arg;
	if (GetArg('videoserver_id', $arg))
		$newInfo['videoserver_id']=$arg;
	if (GetArg('remoteserver_id', $arg))
		$newInfo['remoteserver_id']=$arg;		
	if (GetArg('time_zone', $arg))
		$newInfo['time_zone']=$arg;
	if (GetArg('permission', $arg)) {		
		$newInfo['permission']=$arg;
	}
	if (GetArg('mobile', $arg))
		$newInfo['mobile']=$arg;
	if (GetArg('fax', $arg))
		$newInfo['fax']=$arg;
		
	if (GetArg('tele_num', $arg))
		$newInfo['tele_num']=$arg;
	if (GetArg('tele_mcode', $arg))
		$newInfo['tele_mcode']=$arg;
	if (GetArg('tele_pcode', $arg))
		$newInfo['tele_pcode']=$arg;
				
	// if the number has not been assigned by free conf server		
	if (!isset($newInfo['conf_num'])) {
		if (GetArg('conf_num', $confNum))
			$newInfo['conf_num']=$confNum;
		if (GetArg('conf_mcode', $confMcode))
			$newInfo['conf_mcode']=$confMcode;
		if (GetArg('conf_pcode', $confPcode))
			$newInfo['conf_pcode']=$confPcode;
		if (GetArg('conf_num2', $confNum2))
			$newInfo['conf_num2']=$confNum2;

		if (GetArg('use_teleserver', $arg))
			$newInfo['use_teleserver']=$arg;
		
		// use this to handle a checkbox type form submission
		if (GetArg('use_teleserver_checkbox', $arg) && $arg=="1") {
			if (GetArg('use_teleserver_checked', $arg))
				$newInfo['use_teleserver']='Y';
			else
				$newInfo['use_teleserver']='N';
		}
	
		// if the conf number is changed for an existing user
		// find all meetings of the user using the number and change those too
		if (isset($userInfo['conf_num']) && isset($newInfo['conf_num']) &&
			($userInfo['conf_num']!=$confNum || $userInfo['conf_mcode']!=$confMcode 
			|| $userInfo['conf_pcode']!=$confPcode || $userInfo['conf_num2']!=$confNum2)
			)
		{
			// find all meetings of the user that use the old number
			$userId=$userInfo['id'];
			$userNum=$userInfo['conf_num'];
			$userMcode=$userInfo['conf_mcode'];
			$userPcode=$userInfo['conf_pcode'];
			$query="host_id = '$userId' AND tele_conf='Y' AND tele_num='$userNum' AND tele_mcode='$userMcode' AND tele_pcode='$userPcode'";
			$errMsg=VObject::SelectAll(TB_MEETING, $query, $result);
	
			// change them to use the new number
			while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
				$meeting=new VMeeting($row['id']);
				$meetInfo=array();
				$meetInfo['tele_num']=$confNum;
				$meetInfo['tele_num2']=$confNum2;
				$meetInfo['tele_mcode']=$confMcode;
				$meetInfo['tele_pcode']=$confPcode;
				$meeting->Update($meetInfo);				
			}
		}
	}	


	if (isset($_FILES['pict_file']['tmp_name']) && $_FILES['pict_file']['tmp_name']!='') {
		
		$tempFile=$_FILES['pict_file']['tmp_name'];
		$srcFile=$_FILES['pict_file']['name'];
		$pictId=0;
		if ($cmd=='SET_USER')
			$user->GetValue('pict_id', $pictId);
		
		$errMsg=ProcessUploadImage($tempFile, $srcFile, $memberId, $pictId, 'jpg', PICT_SIZE, PICT_SIZE, 'RESIZE_CROP');
		if ($errMsg!='')
			return API_EXIT(API_ERR, $errMsg);
		
		$newInfo['pict_id']=$pictId;
	} else if (GetArg('reset_pict', $arg) && $arg=='1' && $cmd=='SET_USER') {
		$user->GetValue('pict_id', $pictId);

		if ($pictId>0) {
			$pict=new VImage($pictId);
			$pict->GetValue('file_name', $oldFile);
						
			$oldFile=VImage::GetFilePath($oldFile);
			if (file_exists($oldFile))
				unlink($oldFile);
				
			$newInfo['pict_id']='0';
		}		
	}

	// required for all $gText values
//	$localeFile="locales/".$brandInfo['locale'].".php";
	if (isset($GLOBALS['LOCALE_FILE'])) {
		$localeFile=$GLOBALS['LOCALE_FILE'];
		@include_once($localeFile);
	}
	require_once("includes/common_lib.php");
	require_once("includes/common_text.php");
	
	if ($cmd=='SET_USER') {
		// update an existing user
		
		$memberName=GetSessionValue('member_name');
		$memberId=GetSessionValue('member_access_id');
		$newName=VUSer::GetFullName($newInfo);
		
		// need to change the existing login user name
		if ($memberId==$userInfo['access_id'] && 
			$memberName!='' && $newName!='' && $newName!=$memberName) {
			SetSessionValue('member_name', $newName);
		}
		// need to change the time zone session value
		$tz=GetSessionValue('time_zone');
		if ($memberId==$userInfo['access_id'] && isset($newInfo['time_zone']))
			SetSessionValue('time_zone', $newInfo['time_zone']);

		if ($user->Update($newInfo)!=ERR_NONE)
			return API_EXIT(API_ERR, $user->GetErrorMsg(), 'Update');
			
		$user->Get($userInfo);
	} else {
		
		if (!isset($newInfo['password'])) {
			$newInfo['password']=RandomPassword();
		}
		if (!isset($newInfo['first_name']) && !isset($newInfo['last_name'])) {
			$newInfo['first_name']="Unnamed";
		}
		// add a new user
		if ($newUser->Insert($newInfo)!=ERR_NONE)
			return API_EXIT(API_ERR, $newUser->GetErrorMsg(), 'Insert');
			
		if ($newUser->UpdateServer()!=ERR_NONE)
			return API_EXIT(API_ERR, $newUser->GetErrorMsg(), 'UpdateServer');

		if ($newUser->Get($userInfo)!=ERR_NONE)
			return API_EXIT(API_ERR, $newUser->GetErrorMsg());
			
		if (GetArg('add_meeting', $arg) && $arg=='1') {
						
			// add a new meeting automatically for the user
			$meeting=new VMeeting();
			$meetingInfo=array();
			$meetingInfo['host_id']=$userInfo['id'];
			$meetingInfo['title']=_Text("My Meeting");
			$meetingInfo['brand_id']=$brandInfo['id'];
			$meetingInfo['description']="";
			$meetingInfo['keyword']="";
			
			// if the user has a teleconf number assigned, add that number to the meeting automatically
			if (isset($newInfo['conf_num']) && $newInfo['conf_num']!='') {
				$meetingInfo['tele_num']=$newInfo['conf_num'];
				$meetingInfo['tele_mcode']=$newInfo['conf_mcode'];
				$meetingInfo['tele_pcode']=$newInfo['conf_pcode'];
				$meetingInfo['tele_conf']='Y';
				if (isset($newInfo['conf_num2']))
					$meetingInfo['tele_num2']=$newInfo['conf_num2'];

			}

			// add a new meeting
			if ($meeting->Insert($meetingInfo)!=ERR_NONE)
				return API_EXIT(API_ERR, $meeting->GetErrorMsg());

			if ($meeting->UpdateServer()!=ERR_NONE)
				return API_EXIT(API_ERR, $meeting->GetErrorMsg());
						
		}
												

		if ($brandInfo['notify']=='Y' && $brandInfo['admin_id']!=0) {
			$admin=new VUser($brandInfo['admin_id']);
			if ($admin->Get($adminInfo)!=ERR_NONE) {
				return API_EXIT(API_ERR, $admin->GetErrorMsg());			
			}				
			$toName=$admin->GetFullName($adminInfo);
			$to=$adminInfo['email'];
			if ($to=='')
				$to=$adminInfo['login'];
			
			if (valid_email($to)) {
				$from=$brandInfo['from_email'];
				$fromName=$brandInfo['from_name'];
				$subject=_Text("New Member");
				
				$body="Login=".$userInfo['login']."\n";
				$body.="Name=".$userInfo['first_name']." ".$userInfo['last_name']."\n";
				$body.=$brandInfo['site_url']."?user=".$userInfo['access_id'];
				$errMsg=VMailTemplate::Send($fromName, $from, $toName, $to, $subject, $body,
						'', '', "", false, null, $brandInfo);
				
				if ($errMsg!='')
					return API_EXIT(API_ERR, $errMsg);			
			}
		}
		
	}
	
	if (GetArg('notify', $arg) && $arg=='1') {
		
		$mailInfo=array();
		
		if ($cmd=='ADD_USER')		
			$errMsg=VMailTemplate::GetMailTemplate($brandInfo['id'], 'MT_ADD_MEMBER', $mailInfo);
		else
			$errMsg=VMailTemplate::GetMailTemplate($brandInfo['id'], 'MT_EDIT_MEMBER', $mailInfo);
		
		if ($errMsg!='')
			return API_EXIT(API_ERR, $errMsg);
			
		if (!isset($mailInfo['id'])) {
			return API_EXIT(API_ERR, "Email template not found");				
		}			
		
		$from=$brandInfo['from_email'];
		$fromName=$brandInfo['from_name'];
		$subject=$gText[$mailInfo['subject']];
//		$subject=$mailInfo['subject'];
//			$subject.=" [".$accessId."]";
		$body=VMailTemplate::GetBody($mailInfo, $userInfo, $brandInfo, $gText);

		$toName=$newUser->GetFullName($userInfo);
		$to=$userInfo['email'];
		if ($to=='')
			$to=$userInfo['login'];
		if (valid_email($to)) {
			$errMsg=VMailTemplate::Send($fromName, $from, $toName, $to, $subject, $body,
					'', '', "", false, null, $brandInfo);
			
			if ($errMsg!='')
				return API_EXIT(API_ERR, $errMsg);
		}	
	}


} else if ($cmd=='DELETE_USER') {
		
	if (!GetArg('user_id', $userId))
		return API_EXIT(API_ERR, "Missing user_id");
		
	$memberId=GetSessionValue('member_id');
	if ($memberId=='')
		return API_EXIT(API_ERR, "Not signed in");
		
	$user=new VUser($userId);
	$user->Get($userInfo);	
	if ($memberId!=$userId) {
		$memberPerm=GetSessionValue('member_perm');
		$memberBrand=GetSessionValue('member_brand');
		
		// check if the member is an admin of the brand
		if ($memberPerm!='ADMIN' || $memberBrand!=$userInfo['brand_id']) 
		{
			return API_EXIT(API_ERR, "Not authorized", "DELETE_USER");
		}
		
		$brand=new VBrand($userInfo['brand_id']);
		$brand->Get($brandInfo);
		if ($brandInfo['admin_id']==$userId)
			return API_EXIT(API_ERR, "User is the Site Administrator and cannot be deleted.", "DELETE_USER");
					

	} else {
		return API_EXIT(API_ERR, "You cannot delete yourself", "DELETE_USER");		
	}
	
	$userLicId=$userInfo['license_id'];
	$userLicense=new VLicense($userLicId);
	$userLicense->Get($userLicInfo);

	// if we are accessing this from the API ($api_exit is false) instead of from the UI
	// and the user has a non-trial license ($userLicInfo['trial']!='Y')
	// don't allow changing the license_id of the user if the provider has a "Name-user" licensing model
	// this is to prevent the provider from dynamically assign a user to a different license on-the-fly and share the same 'name-user' license.
	if ($api_exit==false && isset($userLicInfo['trial']) && $userLicInfo['trial']!='Y') {
		
		$provider_id=$brandInfo['provider_id'];
		$provider=new VProvider($provider_id);
		$providerInfo=array();				
		if ($provider->Get($providerInfo)!=ERR_NONE) {
			return API_EXIT(API_ERR, $provider->GetErrorMsg());	
		}
		
		VProvider::ParseLicenseKey($providerInfo, $licenseType, $licenseCounts, $recording);
		if ($licenseType=='N') {
			return API_EXIT(API_ERR, "Not allowed to delete a licensed user with the API for a 'Name-user' license.");	
		}
	}
	
	if ($user->DeleteUser()!=ERR_NONE)
		return API_EXIT(API_ERR, $user->GetErrorMsg(), 'DELETE_USER');

}


?>