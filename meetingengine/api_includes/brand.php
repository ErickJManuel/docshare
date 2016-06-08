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

require_once("dbobjects/vbrand.php");
require_once("dbobjects/vuser.php");
require_once('api_includes/user_common.php');

/*
print $_SERVER['QUERY_STRING'];
echo "<pre>";
print_r($_REQUEST);
print_r($VARGS);
GetArg("locale", $arg);
echo ("locale=".$arg);
echo "</pre>";
*/

if ($userErrMsg!='')
	API_EXIT(API_ERR, $userErrMsg);
	
GetArg('add_br', $addBr);
	
GetArg('brand', $name);
if ($name=='') 
	API_EXIT(API_ERR, "Brand name not set");	
	
if ($userInfo['permission']!='ADMIN') {
	API_EXIT(API_ERR, "You are not an administrator");	
}
// make sure the user has signed in as an admin of the site
if (GetSessionValue('member_perm')!='ADMIN' || GetSessionValue('member_brand')!=$userInfo['brand_id'])
{
	API_EXIT(API_ERR, "Access is not authorized.");			
}	
VObject::Find(TB_BRAND, 'name', $name, $info);
if (!isset($info['id']))
	API_EXIT(API_ERR, "Brand '$name' does not match our records");	

$brandId=$info['id'];
$brand=new VBrand($brandId);
$brand->Get($oldBrandInfo);

if ($userInfo['brand_id']!=$brandId)
	API_EXIT(API_ERR, "You are not an administrator of this brand");	


if ($cmd=='SET_BRAND') {
require_once("dbobjects/vprovider.php");
require_once('dbobjects/vwebserver.php');
	
	$brandInfo=array();
			
	if (GetArg('site_url', $newSiteUrl) && $newSiteUrl!=$oldBrandInfo['site_url'] && $newSiteUrl!='') {
		if ($newSiteUrl[strlen($newSiteUrl)-1]!='/')
			$newSiteUrl.='/';
			
		// make sure the new url is valid or the site will not be accessible
		$verUrl=$newSiteUrl."vversion.php";
		$resp=@file_get_contents($verUrl);
		// it should return a version string (up to 16 chars)
		if ($resp==false || strlen($resp)>16) {
			API_EXIT(API_ERR, "'$newSiteUrl' is not reachable or not valid.");			
		}
		
		if (strpos($newSiteUrl, 'https://')===0 && strpos($oldBrandInfo['site_url'], 'http://')===0) {
			API_EXIT(API_ERR, "Couldn't change a non-secure site to a secure site.");
		}
		
		$brandInfo['site_url']=$newSiteUrl;
		// find the default hosting account for this brand and change its url too
		$query="brand_id='".$brandId."' AND url='".$oldBrandInfo['site_url']."'";
		$serverInfo=array();
		$errMsg=VObject::Select(TB_WEBSERVER, $query, $serverInfo);
		if ($errMsg!='') {
			API_EXIT(API_ERR, $errMsg);
		}
		if (isset($serverInfo['id'])) {
			$server=new VWebServer($serverInfo['id']);
			$newInfo=array();
			$newInfo['url']=$brandInfo['site_url'];
			if ($server->Update($newInfo)!=ERR_NONE)
				API_EXIT(API_ERR, $server->GetErrorMsg(), 'Update');
			
		}
		
	}
		
	if (GetArg('notify', $arg)&& $arg!=$oldBrandInfo['notify'])
		$brandInfo['notify']=$arg;
		
	if (GetArg('admin_id', $arg)&& $arg!=$oldBrandInfo['admin_id'])
		$brandInfo['admin_id']=$arg;
		
	if (GetArg('company_url', $arg) && $arg!=$oldBrandInfo['company_url']) {
		$brandInfo['company_url']=$arg;
	}
	if (GetArg('from_name', $arg)&& $arg!=$oldBrandInfo['from_name']) {
		$brandInfo['from_name']=$arg;
	}
	if (GetArg('from_email', $arg)&& $arg!=$oldBrandInfo['from_email']) {
		$brandInfo['from_email']=$arg;
	}
	if (GetArg('has_smtp', $arg)) {
		if ($arg=='Y') {
			GetArg('smtp_server', $brandInfo['smtp_server']);
			GetArg('smtp_user', $brandInfo['smtp_user']);
			GetArg('smtp_password', $brandInfo['smtp_password']);
		} else {
			$brandInfo['smtp_server']='';
			$brandInfo['smtp_user']='';
			$brandInfo['smtp_password']='';
		}
	}	

	if (GetArg('product_name', $arg)&& $arg!=$oldBrandInfo['product_name']) {
		$brandInfo['product_name']=$arg;
		
		$query="brand_id='".$brandId."' AND url='".$oldBrandInfo['site_url']."'";
		$serverInfo=array();
		$errMsg=VObject::Select(TB_WEBSERVER, $query, $serverInfo);
		if ($errMsg!='') {
			API_EXIT(API_ERR, $errMsg);
		}
		if (!isset($serverInfo['id']))
			API_EXIT(API_ERR, "Couldn't find the web server record.");	

		// update the site_config.php file of the main site
		$updateUrl=$oldBrandInfo['site_url']."vinstall.php";
		$data="win_title=".rawurlencode($brandInfo['product_name']).
			"&brand=".$brandInfo['name']."&win_title=".rawurlencode($brandInfo['product_name']).
			"&login=".$serverInfo['login']."&password=".$serverInfo['password'].
			"&no_update=1&silence=1&change_title=1";
		
		$content=HTTP_Request($updateUrl, $data, 'POST', 15);
		
		if ($content==false) {
			API_EXIT(API_ERR, "Couldn't get respose from $updateUrl");
		} elseif ($content!='OK') {
			API_EXIT(API_ERR, $content);
		}		
		
	}
	if (GetArg('theme', $theme)&& $arg!=$oldBrandInfo['theme']) {
		
		if ($theme=='[custom]') {
			GetArg('custom_theme', $customTheme);
			if ($customTheme=='')
				API_EXIT(API_ERR, "Missing custom_theme name");
			$theme="_".$customTheme;
		}
			
		if ($theme!='' && !file_exists("themes/".$theme))
			API_EXIT(API_ERR, "Couldn't find theme '$theme'.");
		$brandInfo['theme']=$theme;
	}
	if (GetArg('time_zone', $arg)&& $arg!=$oldBrandInfo['time_zone']) {
		$brandInfo['time_zone']=$arg;
	}
	if (GetArg('locale', $arg) && $arg!=$oldBrandInfo['locale']) {
//		echo "locale2=".$arg; exit();
		$brandInfo['locale']=$arg;
	}
	if (GetArg('has_logo_link', $hasLink)) {
		if ($hasLink=='Y') {
			GetArg('logo_link', $arg);
			$brandInfo['logo_link']=$arg;
		} elseif ($hasLink=='N') {
			$brandInfo['logo_link']='';
		}
	}
	if (GetArg('enable_signin', $arg)) {
		if ($arg=='Y')
			$brandInfo['hide_signin']='N';
		elseif ($arg=='N')
			$brandInfo['hide_signin']='Y';
	}
	if (GetArg('custom_signin_url', $arg)&& $arg!=$oldBrandInfo['custom_signin_url']) {
		$brandInfo['custom_signin_url']=$arg;
	}

	if (GetArg('embed_site', $arg)&& $arg!=$oldBrandInfo['embed_site']) {
		$brandInfo['embed_site']=$arg;
	}
	if (GetArg('enable_home', $arg)) {
		if ($arg=='Y')
			$brandInfo['custom_tabs']='';
		elseif ($arg=='N')
			$brandInfo['custom_tabs']="MEETINGS,LIBRARY,ACCOUNT,ADMIN";		
	}		
	if (GetArg('enable_trial', $arg)) {

		$offerings=$info['offerings'];
		// if the offering starts with T, trial is allowed
		// assume the trial license code is TPV1. This may change.

//		if ($arg=='Y' && $offerings{0}!='T')
//			$brandInfo['offerings']='TPV1,'.$offerings;
//		elseif ($arg=='N' && $offerings{0}=='T')
//			$brandInfo['offerings']=str_replace("TPV1,", "", $offerings);

		// version 2.1.52 or earlier disables the trial by removing the trial license code
		// version 2.2.1 adds 'trial_signup' field to control free trials
		// add the trial license code back if it is missing
		if ($offerings{0}!='T')
			$brandInfo['offerings']='TPV1,'.$offerings;
			
		if ($arg!=$oldBrandInfo['trial_signup']) {
			if ($arg=='Y')
				$brandInfo['trial_signup']='Y';
			elseif ($arg=='N')
				$brandInfo['trial_signup']='N';
		}
		
	}		
	if (GetArg('footnote', $arg) && $arg!=$info['footnote']) {
				
		// check if this is a trial site
		$provider_id=$info['provider_id'];
		$licenseType='T';

		if ($provider_id>0) {
			$provider=new VProvider($provider_id);
			$providerInfo=array();
	
			if ($provider->Get($providerInfo)!=ERR_NONE) {
				ShowError($provider->GetErrorMsg());
				return;
			}
			$licenseCounts=array();
			VProvider::ParseLicenseKey($providerInfo, $licenseType, $licenseCounts, $recording);
		}
		if ($licenseType=='T')
			API_EXIT(API_ERR, "Could not modify 'footnote' for a trial site.");
			
		$brandInfo['footnote']=$arg;
	}
		
	if (GetArg('footer1_label', $arg)&& $arg!=$oldBrandInfo['footer1_label'])
		$brandInfo['footer1_label']=$arg;
		
	if (GetArg('footer1_text', $arg)&& $arg!=$oldBrandInfo['footer1_text'])
		$brandInfo['footer1_text']=LnToBr($arg, $addBr);
		
	if (GetArg('footer2_label', $arg)&& $arg!=$oldBrandInfo['footer2_label'])
		$brandInfo['footer2_label']=$arg;
		
	if (GetArg('footer2_text', $arg)&& $arg!=$oldBrandInfo['footer2_text'])
		$brandInfo['footer2_text']=LnToBr($arg, $addBr);	
		
	if (GetArg('footer3_label', $arg)&& $arg!=$oldBrandInfo['footer3_label'])
		$brandInfo['footer3_label']=$arg;
		
	if (GetArg('footer3_text', $arg)&& $arg!=$oldBrandInfo['footer3_text'])
		$brandInfo['footer3_text']=LnToBr($arg, $addBr);
		
	if (GetArg('footer4_label', $arg)&& $arg!=$oldBrandInfo['footer4_label'])
		$brandInfo['footer4_label']=$arg;
		
	if (GetArg('footer4_text', $arg)&& $arg!=$oldBrandInfo['footer4_text'])
		$brandInfo['footer4_text']=LnToBr($arg, $addBr);
		
	if (GetArg('home_text', $arg)&& $arg!=$oldBrandInfo['home_text'])
		$brandInfo['home_text']=$arg;
		
	if (GetArg('help_text', $arg)&& $arg!=$oldBrandInfo['help_text'])
		$brandInfo['help_text']=$arg;
		
	if (GetArg('custom_help', $arg)&& $arg!=$oldBrandInfo['custom_help'])
		$brandInfo['custom_help']=$arg;

		
	if (GetArg('provider_account_id', $arg)) {
		
		$accId=trim($arg);
		$providerInfo=array();
		VObject::Find(TB_PROVIDER, 'account_id', $accId, $providerInfo);
		if (!isset($providerInfo['id']))
			API_EXIT(API_ERR, "Could not find a provider with the account id");	
		
		$brandInfo['provider_id']=$providerInfo['id'];
		
	}
	
	if (GetArg('send_report', $arg) && $arg!='') {
		$brandInfo['send_report']=$arg;
	}
	
	if (GetArg('share_it', $arg)&& $arg!=$oldBrandInfo['share_it'])
		$brandInfo['share_it']=$arg;
		
/*				
	if (isset($_POST['license']) && is_array($_POST['license'])) {
		$licenses=$_POST['license'];
		$offerings='';
		foreach ($licenses as $k => $v) {
			if ($v=='1') {
				if ($offerings!='')
					$offerings.=",";
				$offerings.=$k;
			}
		}
		$brandInfo['offerings']=$offerings;

	} elseif (GetArg('offerings', $arg))
		$brandInfo['offerings']=$arg;
*/
		
	if (GetArg('reset_logo', $arg) && $arg=='1') {
require_once("dbobjects/vimage.php");
			
		// need to delete old logo if it exists
		$brand->GetValue('logo_id', $pictId);
		
		// only delete if it is my own logo
		if ($pictId!=0) {
			$logo=new VImage($pictId);
			$logo->Get($logoInfo);
			$fileName=$logoInfo['file_name'];
			$authorId=$logoInfo['author_id'];
			// check if the fileName starts with "default". note the "==0" means the index is 0 (found)
			if ($authorId=='0' || strpos($fileName, "default")==0) {
			} else {
//				$oldFile=DIR_IMAGE.$oldFile;
				$oldFile=VImage::GetFilePath($fileName);
				if (file_exists($oldFile))
					unlink($oldFile);
			}
		}
		
		$query="file_name='default_banner.jpg'";
		VObject::Select(TB_IMAGE, $query, $logoInfo);
		if (isset($logoInfo['id']))		
			$brandInfo['logo_id']=$logoInfo['id'];
		else
			$brandInfo['logo_id']=0;
	}
		
	if (isset($_FILES['site_logo_file']['tmp_name']) && $_FILES['site_logo_file']['tmp_name']!='') {
require_once("dbobjects/vimage.php");
		$tempFile = $_FILES['site_logo_file']['tmp_name'];
		$srcFile=$_FILES['site_logo_file']['name'];
		$brand->GetValue('logo_id', $pictId);
		// check if the logo is a default logo or the brand specific one
		// if it is default logo (the file name starts with "default"), don't override it
		// set pictId to 0 so ProcessUploadImage will create a new image
		if ($pictId>0) {
			$logo=new VImage($pictId);
			$logo->Get($logoInfo);
			$fileName=$logoInfo['file_name'];
			$authorId=$logoInfo['author_id'];
			// check if the fileName starts with "default". note the "==0" means the index is 0 (found)
			if ($authorId=='0' || strpos($fileName, "default")==0)
				$pictId=0;
		}
		
		$errMsg=ProcessUploadImage($tempFile, $srcFile, $userInfo['id'], $pictId, '', MAX_BRAND_LOGO_WIDTH, MAX_BRAND_LOGO_HEIGHT, 'MAX_SIZE');
//		$errMsg=ProcessUploadImage($tempFile, $srcFile, $userInfo['id'], $pictId);
		if ($errMsg!='')
			API_EXIT(API_ERR, $errMsg, 'UploadImage');
			
		$brandInfo['logo_id']=$pictId;
	}

		
	if ($brand->Update($brandInfo)!=ERR_NONE)
		API_EXIT(API_ERR, $brand->GetErrorMsg(), 'Update');
		
	if (isset($_POST['test_smtp'])) {
		$smtpLog='';
		$fromName=isset($brandInfo['from_name'])?$brandInfo['from_name']:$oldBrandInfo['from_name'];
		$fromEmail=isset($brandInfo['from_email'])?$brandInfo['from_email']:$oldBrandInfo['from_email'];
		$toId=isset($brandInfo['admin_id'])?$brandInfo['admin_id']:$oldBrandInfo['admin_id'];
		$toUser=new VUser($toId);
		$toUser->Get($toUserInfo);
		if (isset($toUserInfo['email']) && $toUserInfo['email']!='')
			$to=$toUserInfo['email'];
		else if (isset($toUserInfo['login']) && $toUserInfo['login']!='')
			$to=$toUserInfo['login'];
		
		$backPage=$oldBrandInfo['site_url']."?page=".PG_ADMIN_SITE;
		$backPage=VWebServer::EncodeDelimiter2($backPage);
		$siteUrl="index.php?brand=".$oldBrandInfo['name']."&brandUrl=".$oldBrandInfo['site_url'];
		
		if (!isset($to)) {
			$errMsg="Site Administrator is not found.";
		} else if (!valid_email($to)) {
			$errMsg="The Site Administrator does not have a valid email record.";
		} else {				
			require_once("dbobjects/vmailtemplate.php");
			$title="Test Message";
			$body="This is a test message sent from '".$oldBrandInfo['site_url']."' while testing your email server settings.";
			$smtpLog='1';
			$errMsg=VMailTemplate::Send($fromName, $fromEmail, '', $to, $title, $body,
					'', '', '', false, null, $brandInfo, $smtpLog);
			
		}
		if ($errMsg!='') {
			$errMsg.="\nSMTP Logs:\n".$smtpLog;
			$retPage=$siteUrl."&page=".PG_HOME_ERROR."&".SID."&error=".rawurlencode($errMsg)."&ret=".$backPage;
			header("Location: ".$retPage);
		} else {
			
			$text="Email has been sent to the '$to'";
			$retPage=$siteUrl."&page=".PG_HOME_INFORM."&".SID."&message=".rawurlencode($text)."&ret=".$backPage;
			header("Location: $retPage");
		}
		exit();
	}

		
}

function LnToBr($text, $replace)
{
	if ($replace) {
		return str_replace("\r\n", "<br>", $text);
		return str_replace("\n", "<br>", $text);
	} else
		return $text;
}

?>