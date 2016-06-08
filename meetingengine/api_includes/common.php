<?php
/**
 * Persony Web Conferencing 2.0
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2008 Persony, Inc.
 * @version     2.0
 * 
 */

// Error Codes
/**
 * No Error
 */
define('API_NOERR', 0);
/**
 * Exit without a message
 */
define('API_NOMSG', -1);
/**
 * Error
 */
define('API_ERR', 1);
/*
 * max site banner width
 */
define('MAX_BRAND_LOGO_WIDTH', 420);
/*
 * max site banner height
 */
define('MAX_BRAND_LOGO_HEIGHT', 80);
/*
 * meeting viewer logo width
 */
define('VIEWER_LOGO_WIDTH', 116);
/*
 * meeting viewer logo height
 */
define('VIEWER_LOGO_HEIGHT', 30);

require_once("dbobjects/vobject.php");

$api_exit=true;
$api_error_message='';

function API_EXIT($errCode=API_NOERR, $errMsg='', $funcName='', $logErr=true)
{
	global $cmd, $api_exit, $api_error_message;
	
	if ($errCode!=API_NOERR && $errCode!=API_NOMSG && $logErr) {
		include_once("includes/log_error.php");
		LogError($errMsg);
	}
			
	// if we don't want to exit, this is used when called from the PRestAPI
	if (!$api_exit) {
		if ($errCode!=API_NOERR)
			$api_error_message=$errMsg;
		else
			$api_error_message='';
		
		return;
	}
	
	// not really necessary but close the database to be safe.	
//	require_once("dbobjects/vobject.php");
//	VObject::CloseDB();	
	
	// exit without any message
	if ($errCode==API_NOMSG)
		exit();
		
	GetArg('return', $return);
	
	if ($return=='') {
		// must add this for IE7 to work on SSL download
		header('Pragma: private');
		header('Cache-control: private, must-revalidate');

		header("Content-type: text/xml");
		//$errMsg=wordwrap($errMsg, 65, "<br>", 1);
		$msg=htmlspecialchars($errMsg);
		if ($errCode!=API_NOERR) {
			$scriptFile='';
			if (isset($_SERVER['PHP_SELF']))
				$scriptFile=htmlspecialchars($_SERVER['PHP_SELF']);
			$query='';
			if (isset($_SERVER['QUERY_STRING']))
				$query=htmlspecialchars($_SERVER['QUERY_STRING']);
			echo "<error code=\"$errCode\" message=\"$msg\" cmd=\"$cmd\" script=\"$scriptFile\" query=\"$query\" function=\"$funcName\"/>";
		} else {
			echo "<return message=\"$msg\"/>";
		}
		exit();
	} else {
		if ($errCode==API_NOERR) {
			require_once("dbobjects/vwebserver.php");
			$page=VWebServer::DecodeDelimiter1($return);
			if (SID!='') {
				if (strpos($page, '?')===false)
					$page.="?".SID;
				else
					$page.="&".SID;
			}
			header("Location: $page");
		} else {
			$siteUrl="index.php?brand=".GetSessionValue('brand_name');

			$errPage="$siteUrl&page=".PG_HOME_ERROR."&".SID."&error=";
			header("Location: ".$errPage.rawurlencode($errMsg));
		}
		exit();
	}
}

function ProcessUploadImage($tempFile, $srcFile, $authorId, &$pictId, $dstType='', $dstW=0, $dstH=0, $resizeMode='STRETCH', $maxWidth=1280, $maxHeight=1024, $maxSize=102400)
{
require_once("dbobjects/vimage.php");
	
	$pinfo=pathinfo($srcFile);
	$ext=strtolower($pinfo['extension']);
	if ($ext!='gif' && $ext!='jpg' && $ext!='png')
		return ("Invalid file type '$srcFile'");

	if ($maxWidth>0 || $maxHeight>0) {
		list($iwd, $ihg, $itype, $iattr)=getimagesize($tempFile);
		if ($maxWidth>0 && $iwd>$maxWidth)
			return ("Image width $iwd exceeds limit $maxWidth.");
		if ($maxHeight>0 && $ihg>$maxHeight)
			return ("Image height $ihg exceeds limit $maxHeight.");
	}

	// the pict file already exists. delete it first				
	if ($pictId>1) {
		$pict=new VImage($pictId);
		if ($pict->GetValue('file_name', $oldFile)!=ERR_NONE)
			return($pict->GetErrorMsg());
					
//		$oldFile=DIR_IMAGE.$oldFile;
		$oldFile=VImage::GetFilePath($oldFile);
		if (file_exists($oldFile))
			unlink($oldFile);
				
	} else {
		// create a new pict file
		$pict=new VImage();
		$pictInfo=array();
		$pictInfo['author_id']=$authorId;
		
		if ($pict->Insert($pictInfo)!=ERR_NONE)
			return($pict->GetErrorMsg());
			
		if ($pict->GetValue('id', $pictId)!=ERR_NONE)
			return($pict->GetErrorMsg());
		
	}
	$dstFile="p".$pictId."_".rand().".$ext";
//	$uid=md5(microtime());
//	$dstFile="pict_".$uid.".$ext";
	
//	$dstPath=DIR_IMAGE.$dstFile;
	$dstPath=VImage::GetFilePath($dstFile, false);
//	$mode=fileperms("./");
	$mode=0666;
	
	if (move_uploaded_file($tempFile, $dstPath)) {
		umask(0);
		@chmod($dstPath, $mode); 			
	} else {
		return("Couldn't move file from ".$tempFile." to ".$dstPath);
	}
	
	if ($dstType=='')
		$dstType=$ext;
	
	// resize to JPEG
	$resize=false;	
	if ($maxSize>0 && filesize($dstPath)>$maxSize) {
		$resize=true;
		$dstType='jpg';
	}
			
	if (($dstW!=0 && $dstH!=0) || $dstType!=$ext || $resize) {
		
		if (!function_exists('imagetypes'))
			return("GD image library is not enabled in PHP."); 				
	
		if ($ext=='jpg') {
			if (!(imagetypes() & IMG_JPG))
				return("JPEG support is not enabled."); 				
			$srcImage=imagecreatefromjpeg($dstPath);
		} elseif  ($ext=='png') {
			if (!(imagetypes() & IMG_PNG))
				return("PNG support is not enabled."); 				
			$srcImage=imagecreatefrompng($dstPath);
		} elseif  ($ext=='gif') {
			if (!(imagetypes() & IMG_GIF))
				return("GIF support is not enabled.");
			$srcImage=imagecreatefromgif($dstPath);
		}
			
		if (!$srcImage)
			return("Couldn't open image file ".$dstPath);
			
		$srcW=imagesx($srcImage);
		$srcH=imagesy($srcImage);
		
		if ($srcW==0 || $srcH==0)
			return("Invalid image size for".$dstPath);
					
		if ($dstType!=$ext && $dstW==0 && $dstH==0) {
			$dstW=$srcW;
			$dstH=$srcH;
		}		
		
		if ($srcW!=$dstW || $srcH!=$dstH || $dstType!=$ext || $resize) {
			
			$sx=0;
			$sy=0;
			$sw=$srcW;
			$sh=$srcH;
			
			if ($resizeMode=='STRETCH') {	
				// no change			
			} else if ($resizeMode=='MAX_SIZE') {
				// make the target no larger than the dest size
				// don't scale if the src is smaller than max_size
				if ($srcW<=$dstW)
					$dstW=$srcW;
				if ($srcH<=$dstH)
					$dstH=$srcH;
			} else if ($resizeMode=='CROP') {
				$sx=($dstW-$srcW)/2;
				$sy=($dstH-$srcH)/2;
				if ($srcX<0)
					$srcX=0;
				if ($srcY<0)
					$srcY=0;
				$sw=$dstW;
				$sh=$dstH;
				if ($sw>$srcW)
					$sw=$srcW;
				if ($sh>$srcH)
					$sh=$srcH;
								
			} else if ($resizeMode=='RESIZE_CROP') {
				// resize src and then crop to the dest. size 
				// so the final image has the same aspect ratio as the src and the size of dest.
				$wRatio=$dstW/$srcW;
				$hRatio=$dstH/$srcH;
			
				if ($wRatio>$hRatio) {
					$sh=$dstH/$wRatio;
					$sy=($srcH-$sh)/2;	// crop vertically			
				} else {
					$sw=$dstW/$hRatio;
					$sx=($srcW-$sw)/2;	// crop horizontally					
				}
			}
		
			if ($sw!=$dstW || $sh!=$dstH || $sx!=0 || $sy!=0 || $dstType!=$ext || $resize) {
				$dstImage=imagecreatetruecolor($dstW, $dstH);		
				if (!$dstImage)
					return("Couldn't create new GD image");
					
				imageantialias($dstImage, TRUE);
					
				imagecopyresampled($dstImage, $srcImage, 0, 0, $sx, $sy, $dstW, $dstH, $sw, $sh);
				
				if ($dstType=='')
					$dstType=$ext;
					
				if ($dstType=='gif' && (imagetypes() & IMG_GIF))
					$dstType='png';
								
				if ($dstType=='gif') {
					if (!(imagetypes() & IMG_GIF))
						return("GIF support is not enabled"); 				
					imagegif($dstImage, $dstPath);
				} elseif ($dstType=='jpg') {
					if (!(imagetypes() & IMG_JPG))
						return("JPEG support is not enabled"); 				
					imagejpeg($dstImage, $dstPath);
				} else {
					if (!(imagetypes() & IMG_PNG))
						return("PNG support is not enabled"); 				
					imagepng($dstImage, $dstPath);
				}
			}
		}
	}
	$newInfo=array();
	$newInfo['file_name']=$dstFile;
	if ($pict->Update($newInfo)!=ERR_NONE) {
		return ($pict->GetErrorMsg());
	}

	return '';
	
}

function IsLogin() {
			
	if (GetSessionValue('member_id')=='')
		return false;
	else
		return true;
}
?>