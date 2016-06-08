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

require_once("dbobjects/vcontent.php");
require_once("dbobjects/vuser.php");
require_once("dbobjects/vgroup.php");
require_once('api_includes/user_common.php');

if ($userErrMsg!='')
	API_EXIT(API_ERR, $userErrMsg);

if ($cmd=='ADD_CONTENT' || $cmd=='SET_CONTENT')
{	
	if (!isset($userInfo['id'])) {
		API_EXIT(API_ERR, "User not set. ".$_SERVER['QUERY_STRING']);	
	}
	if (GetSessionValue('member_id')!=$userInfo['id'] &&
		(GetSessionValue('member_perm')!='ADMIN' || GetSessionValue('member_brand')!=$userInfo['brand_id'])
		)
	{
		API_EXIT(API_ERR, "Access is not authorized.");			
	}
	
	$contentInfo=array();
	
	if ($cmd=='SET_CONTENT') {
		GetArg('content_id', $contentId);
		
		if (!isset($contentId))
			API_EXIT(API_ERR, "Missing content_id");
						
		$errMsg=VObject::Find(TB_CONTENT, 'content_id', $contentId, $currContentInfo);
		if ($errMsg!='') {
			API_EXIT(API_ERR, $errMsg);
		}
		
		GetArg('create', $create);

		// content not found
		if (!isset($currContentInfo['id'])) {
			if ($create!='1') {
				API_EXIT(API_ERR, "Record not found.");
			} else {
				// we are asked to create it instead of returning an error				
				$cmd='ADD_CONTENT';
			}
		} else {
		
			$content=new VContent($currContentInfo['id']);
			
			// public library
			if ($currContentInfo['owner_id']=='0') {
				// verify admin permissions
				if ($userInfo['permission']!='ADMIN' || $userInfo['brand_id']!=$currContentInfo['brand_id'])
				{
					API_EXIT(API_ERR, "Not authorized");	
				}			
			} else {
				if ($userInfo['id']!=$currContentInfo['owner_id']) {
					API_EXIT(API_ERR, "Not authorized");	
				}
			}
			
		}		
	
	}
	
	if ($cmd=='ADD_CONTENT')
	{		
		$content=new VContent();
		$contentInfo['brand_id']=$userInfo['brand_id'];
		
		if (GetArg('public', $arg) && $arg=='1') {
			// upload to the public library
			// verify admin permissions
			if ($userInfo['permission']!='ADMIN')
			{
				API_EXIT(API_ERR, "Not authorized");	
			}			
		} else {
			// upload to the user's library
			$contentInfo['owner_id']=$userInfo['id'];
		}
		
		$group = new VGroup($userInfo['group_id']);
		if ($group->GetValue('storageserver_id', $storageId)!=ERR_NONE) {
			API_EXIT(API_ERR, $group->GetErrorMsg());
		}
		
		$contentInfo['storageserver_id']=$storageId;
		$contentInfo['create_date']='#NOW()';
					
	}
	
	if (GetArg('title', $arg))
		$contentInfo['title']=$arg;
	if (GetArg('description', $arg))
		$contentInfo['description']=$arg;
	if (GetArg('keyword', $arg))
		$contentInfo['keyword']=$arg;
	if (GetArg('content_id', $arg))
		$contentInfo['content_id']=$arg;
	if (GetArg('file_name', $arg))
		$contentInfo['file_name']=$arg;
	if (GetArg('type', $arg))
		$contentInfo['type']=$arg;
	if (GetArg('thumb_file', $arg))
		$contentInfo['thumb_file']=$arg;
	if (GetArg('create_date', $arg))
		$contentInfo['create_date']=$arg;
	if (GetArg('copyright', $arg))
		$contentInfo['copyright']=$arg;
	if (GetArg('author_name', $arg))
		$contentInfo['author_name']=$arg;
	if (GetArg('storageserver_id', $arg))
		$contentInfo['storageserver_id']=$arg;
	if (GetArg('slide_titles', $arg))
		$contentInfo['slide_titles']=$arg;
	if (GetArg('slide_files', $arg))
		$contentInfo['slide_files']=$arg;
	if (GetArg('slide_thumbs', $arg))
		$contentInfo['slide_thumbs']=$arg;
	if (GetArg('width', $arg))
		$contentInfo['width']=$arg;
	if (GetArg('height', $arg))
		$contentInfo['height']=$arg;
	
	if ($cmd=='SET_CONTENT')
	{
		if ($content->Update($contentInfo)!=ERR_NONE)
			API_EXIT(API_ERR, $content->GetErrorMsg(), 'Update');
	}
	else
	{		
		if (!isset($contentInfo['title']) || $contentInfo['title']=='')
			API_EXIT(API_ERR, 'Missing title');
		if (!isset($contentInfo['content_id']) || $contentInfo['content_id']=='')
			API_EXIT(API_ERR, 'Missing content_id');
		
		if ($content->Insert($contentInfo)!=ERR_NONE)
			API_EXIT(API_ERR, $content->GetErrorMsg(), 'Insert');	
	}

}
else if ($cmd=='DELETE_CONTENT')
{
	if (!isset($userInfo['id'])) {
		API_EXIT(API_ERR, "User not set.");	
	}
	if (GetSessionValue('member_id')!=$userInfo['id'] &&
		(GetSessionValue('member_perm')!='ADMIN' || GetSessionValue('member_brand')!=$userInfo['brand_id'])
		)
	{
		API_EXIT(API_ERR, "Access is not authorized.");			
	}	
	GetArg('content_id', $contentId);
	
	if (!isset($contentId))
		API_EXIT(API_ERR, "Missing content_id");
					
	$errMsg=VObject::Find(TB_CONTENT, 'content_id', $contentId, $currContentInfo);
	if ($errMsg!='') {
		API_EXIT(API_ERR, $errMsg);
	}		

	if (!isset($currContentInfo['id'])) {
		API_EXIT(API_ERR, "Record not found.");
	}
	$content=new VContent($currContentInfo['id']);
	
	// public library
	if ($currContentInfo['owner_id']=='0') {
		// verify admin permissions
		if ($userInfo['permission']!='ADMIN' || $userInfo['brand_id']!=$currContentInfo['brand_id'])
		{
			API_EXIT(API_ERR, "Not authorized");	
		}			
	} else {
		if ($userInfo['id']!=$currContentInfo['owner_id']) {
			API_EXIT(API_ERR, "Not authorized");	
		}
	}
	
	if ($content->Drop()!=ERR_NONE)
		API_EXIT(API_ERR, $content->GetErrorMsg(), 'Delete');

} else if ($cmd=='GET_CONTENT') {
require_once("dbobjects/vstorageserver.php");
	
	GetArg('brand', $brand);
	if (!isset($brand))
		API_EXIT(API_ERR, "Missing brand");
		
	$errMsg=VObject::Find(TB_BRAND, 'name', $brand, $brandInfo);
	if ($errMsg!='') {
		API_EXIT(API_ERR, $errMsg);
	}
	if (!isset($brandInfo['id']))
		API_EXIT(API_ERR, "Brand not found.");
			
	$query="brand_id=".$brandInfo['id'];
	
	if (GetArg('public', $public) && $public=='1') {
		// get the public library
		$query.=" AND owner_id='0'";	
	} else {
		if (!isset($userInfo['id'])) {
			API_EXIT(API_ERR, "User not set. ".$_SERVER['QUERY_STRING']);	
		}
/*
		if (GetSessionValue('member_id')!=$userInfo['id'] &&
			(GetSessionValue('member_perm')!='ADMIN' || GetSessionValue('member_brand')!=$userInfo['brand_id'])
			)
		{
			API_EXIT(API_ERR, "Access is not authorized. ".GetSessionValue('member_id')." ".$userInfo['id']." ".$_SERVER['QUERY_STRING']);			
		} */
		// get the user's library
		$query.=" AND owner_id='".$userInfo['id']."'";	
	}
	
	// exclude contents from this url
	if (GetArg('excluded_url', $exUrl) && $exUrl!='') {
		if ($exUrl==$brandInfo['site_url']) {
			$query.=" AND storageserver_id<>'0'";
		} else {			
			VObject::Find(TB_STORAGESERVER, 'url', $exUrl, $storageInfo);
			if (isset($storageInfo['id']))
				$query.=" AND storageserver_id<>'".$storageInfo['id']."'";
			
			$group=new VGroup($userInfo['group_id']);
			$group->Get($groupInfo);
			// if both storage servers are set, storage2 is considered a mirror of storage1
			// if excluded_url is storage2's url, we want to exclude storage1 too so we don't get duplicates	
			// similarily, if excluded_url is storage's url, we want to exclude storage2
			if (isset($groupInfo['storageserver_id']) && $groupInfo['storageserver_id']!='' && 
				isset($groupInfo['storageserver2_id']) && $groupInfo['storageserver2_id']!='') 
			{
				$storage1=new VStorageServer($groupInfo['storageserver_id']);
				$storage1->GetValue("url", $storage1Url);
				// exclude mirror copy on storage2
				if (isset($storage1Url) && $storage1Url==$exUrl) {
					$query.=" AND storageserver_id<>'".$groupInfo['storageserver2_id']."'";					
				}
				
				$storage2=new VStorageServer($groupInfo['storageserver2_id']);
				$storage2->GetValue("url", $storage2Url);
				// exclude mirror copy on storage1
				if (isset($storage2Url) && $storage2Url==$exUrl) {
					$query.=" AND storageserver_id<>'".$groupInfo['storageserver_id']."'";					
				}				
			}			
		}
	}
	
	$errMsg=VObject::SelectAll(TB_CONTENT, $query, $result, 0, 0, '*', 'storageserver_id');
	if ($errMsg!='') {
		API_EXIT(API_ERR, $errMsg);	
	}

	header('Pragma: private');
	header('Cache-control: private, must-revalidate');

	header("Content-Type: text/xml");
	echo XML_HEADER."\n";
	echo "<xmlFiles>\n";
	$lastStorageId='0';
	$lastStorageUrl='';
	while ($contInfo = mysql_fetch_array($result, MYSQL_ASSOC)) {
		$storageId=$contInfo['storageserver_id'];
		if ($storageId=='0') {
			$contentUrl=$brandInfo['site_url'];
		} elseif ($storageId!=$lastStorageId) {
			$storageServer=new VStorageServer($storageId);
			$storageServer->GetValue('url', $contentUrl);
			$lastStorageId=$storageId;
			$lastStorageUrl=$contentUrl;
		} else {
			$contentUrl=$lastStorageUrl;			
		}
		if ($contentUrl!='') {
			if ($public=='1')
				$contentUrl.="vlibrary/";
			else
				$contentUrl.=$userInfo['access_id']."/vlibrary/";
		}
		$xmlData=VContent::GetXML($contInfo, $contentUrl);
		echo $xmlData;
	}
	
	echo "</xmlFiles>\n";

	API_EXIT(API_NOMSG);
}

?>