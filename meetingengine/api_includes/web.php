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

require_once("dbobjects/vwebserver.php");
require_once("dbobjects/vuser.php");
require_once('api_includes/user_common.php');

if ($userErrMsg!='')
	API_EXIT(API_ERR, $userErrMsg);

if ($userInfo['permission']!='ADMIN') {
	API_EXIT(API_ERR, "Not authorized");	
}

// make sure the user has signed in as an admin of the site
if (GetSessionValue('member_perm')!='ADMIN' || GetSessionValue('member_brand')!=$userInfo['brand_id'])
{
	API_EXIT(API_ERR, "Access is not authorized.");			
}

if ($cmd=='ADD_WEB' || $cmd=='SET_WEB') {
	
	$webInfo=array();
	if ($cmd=='ADD_WEB') {
		
		$web=new VWebServer();
		$webInfo['brand_id']=$userInfo['brand_id'];		
		
	} else {
		GetArg('id', $webserverId);
		
		if (!isset($webserverId))	
			API_EXIT(API_ERR, "Missing webserver id");
		
		$web=new VWebServer($webserverId);
		$web->GetValue('brand_id', $brandId);
		if ($userInfo['brand_id']!=$brandId)
			API_EXIT(API_ERR, "Not an administrator of this brand");	
		
	}

	if (defined("ENABLE_CACHING_SERVERS") && ENABLE_CACHING_SERVERS=='1') {	
	
		if (GetArg('max_connections', $arg)) {
			if ($arg=='')
				$max=-1;
			else
				$max=intval($arg);
			$webInfo['max_connections']=$max;
		}
		if (GetArg('slave_ids', $arg))
			$webInfo['slave_ids']=$arg;
		else if (GetArg('cachingserver_ids', $arg))
			$webInfo['slave_ids']=$arg;		
		else {
			$slaveIds='';
			$setid=false;
			for ($i=0; $i<10; $i++) {
				if (GetArg('slaveid_'.$i, $arg)) {
					$setid=true;
					if ($arg!='0')
						$slaveIds.=$arg.",";
				}
			}
			if ($setid) {
				// remove the trailing ','
				$webInfo['slave_ids']=substr($slaveIds, 0, strlen($slaveIds)-1);
			}	
		}
	}
	
	if (GetArg('installed_version', $arg))
		$webInfo['installed_version']=$arg;	
	
	if (GetArg('password', $arg) && $arg!='')
		$webInfo['password']=$arg;	
	if (GetArg('login', $arg))
		$webInfo['login']=$arg;	
	if (GetArg('name', $arg))
		$webInfo['name']=$arg;
	if (GetArg('url', $arg)) {
		$webInfo['url']=$arg;
		$urlItems=@parse_url($webInfo['url']);
		if (!isset($urlItems['scheme']) || 
			($urlItems['scheme']!='http' && $urlItems['scheme']!='https') ||
			!isset($urlItems['host'])) 
		{
			API_EXIT(API_ERR, "Invalid url ".$webInfo['url']);
		}
		
		$brand=new VBrand($userInfo['brand_id']);
		$brand->GetValue('site_url', $siteUrl);
		$siteUrlItems=@parse_url($siteUrl);
		if ($urlItems['scheme']!=$siteUrlItems['scheme'])
			API_EXIT(API_ERR, "Url protocol '".$urlItems['scheme']."' does not match the site's protocol '".$siteUrlItems['scheme']."'.");
		
	}

		
	if ($cmd=='SET_WEB') {
		if ($web->Update($webInfo)!=ERR_NONE)
			API_EXIT(API_ERR, $web->GetErrorMsg(), 'Update');
	} else {
		
		if (!isset($webInfo['name']) || $webInfo['name']=='')
			API_EXIT(API_ERR, 'Missing name');
		
		if (!isset($webInfo['url']) || $webInfo['url']=='')
			API_EXIT(API_ERR, 'Missing url');
					

		if ($web->Insert($webInfo)!=ERR_NONE)
			API_EXIT(API_ERR, $web->GetErrorMsg(), 'Insert');		
			
		if (GetArg('return', $page))
		{
			if ($web->GetValue('id', $webId)!=ERR_NONE)
				API_EXIT(API_ERR, $web->GetErrorMsg());
							
			$page=VWebServer::DecodeDelimiter1($page);
			if (strpos($page, PG_ADMIN_INSTALL)!==false) {
				if (strpos($page, '?')===false)
					$page.="?id=".$webId."&".SID;
				else
					$page.="&id=".$webId."&".SID;
				
				header("Location: $page");
				API_EXIT(API_NOMSG);
			}
		}
	}	
		
} else if ($cmd=='DELETE_WEB') {
require_once("dbobjects/vbrand.php");
			
	GetArg('id', $webserverId);
	
	if (!isset($webserverId))	
		API_EXIT(API_ERR, "Missing webserver id");
	
	$web=new VWebServer($webserverId);
	$serverInfo=array();
	$web->Get($serverInfo);
	if ($userInfo['brand_id']!=$serverInfo['brand_id'])
		API_EXIT(API_ERR, "Not an administrator of this brand");	

	$brand=new VBrand($userInfo['brand_id']);
	$brand->GetValue('site_url', $siteUrl);
	
	if ($serverInfo['url']==$siteUrl)
		API_EXIT(API_ERR, "Cannot delete the default site");
		
	$iquery="brand_id ='".$userInfo['brand_id']."'";
	$iquery.=" AND webserver_id='$webserverId'";
	$errMsg=VObject::SelectAll(TB_GROUP, $iquery, $iresult2);
	if ($errMsg!='')
		API_EXIT(API_ERR, $errMsg);
	
	$groupName='';
	while ($irow = mysql_fetch_array($iresult2, MYSQL_ASSOC)) {
		$group=new VGroup($irow['id']);
		$group->GetValue('name', $aname);
		$groupName.="'$aname' ";
	}
	if ($groupName!='')
		return API_EXIT(API_ERR, "The server cannot be deleted because the following groups are using it: $groupName");
	
	if ($web->Drop()!=ERR_NONE)
		API_EXIT(API_ERR, $web->GetErrorMsg(), 'Delete');

}

?>