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

$pageUrl=$GLOBALS['BRAND_URL']."?page=LIBRARY&fitwindow=1";
if (SID!='')
	$pageUrl.="&".SID;
	
$convPage=$GLOBALS['BRAND_URL']."?page=".PG_LIBRARY_CONVERTER;
if (SID!='')
	$convPage.="&".SID;

$dlPage=$GLOBALS['BRAND_URL']."?page=".PG_HOME_DOWNLOAD;
if (SID!='')
	$dlPage.="&".SID;

$fspPage=$GLOBALS['BRAND_URL']."?page=".PG_HOME_FSP;
if (SID!='')
	$fspPage.="&".SID;
	
$videoPage=$GLOBALS['BRAND_URL']."?page=".PG_HOME_VIDEO;
if (SID!='')
	$videoPage.="&".SID;
	
$hasWindowsClient=true;

if ((defined('ENABLE_WINDOWS_CLIENT') && constant('ENABLE_WINDOWS_CLIENT')=='0'))
{
	$hasWindowsClient=false;
} else {	
	require_once("dbobjects/vviewer.php");
	
	$brandViewer=new VViewer($gBrandInfo['viewer_id']);
	$brandViewerInfo=array();
	$brandViewer->Get($brandViewerInfo);
	
	if (isset($brandViewerInfo['presenter_client']) && $brandViewerInfo['presenter_client']=='JAVA')
		$hasWindowsClient=false;

}

?>


<div class='right-aligned'><a target='<?php echo $target?>' href="<?php echo $pageUrl?>"><img src='<?php echo $popIcon?>'>  Fit window</a></div>

<div class="lib_mgr">

<?php
require_once("includes/library_mgr.php");
?>
</div>


<div>
<div class='info_text'>- <b>Import Pictures</b>: Pictures must be .jpg files. Recommend no more than 200KB per picture.</div>
<div class='info_text'>- <b>Import Presentations</b>: You have multiple ways to import a presentation:
<ul>
<li>Use <a target="<?php echo $target?>" href="<?php echo $convPage?>">Document Converter</a> to upload PowerPoint files to the server for conversion.</li>
<?php
if ($hasWindowsClient) {
?>
<li>Use <a target="<?php echo $target?>" href="<?php echo $dlPage?>">Presenter Windows Client</a> to convert PowerPoint files on your desktop and upload to your library.</li>
<?php
}
?>
<li>Export presentations to JPEG files and upload the files with Library Manager.</li>
<li>To retain animations, export presentations to Flash files with <a target="<?php echo $target?>" href='<?php echo $fspPage?>'>third party programs</a> and upload the files with Library Manager.</li>
</ul>
</div>
<div class='info_text'>- <b>Import videos</b>: Videos must be .flv files. See <a target="<?php echo $target?>" href='<?php echo $videoPage?>'>instructions</a> for encoding videos.</div>
<div class='info_text'>- <b>Import Audios</b>: Audios must be .mp3 files.</div>
<div class='info_text'>- Members can only upload files to <b>'My Library'</b>. Only a site admin can upload files to <b>'Public Library'</b>.</div>
<div class='info_text'>- Max. upload size per file: <b><?php if (isset($uploadSize) && $uploadSize!=0) echo $uploadSize; else echo 'n/a' ?> MB.</b> 
&nbsp;&nbsp;Max. upload time per file: <b><?php if (isset($uploadTime) && $uploadTime!='') echo $uploadTime; else echo 'n/a' ?> sec.</b></div>
</div>