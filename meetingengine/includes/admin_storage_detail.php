<?php

require_once("dbobjects/vstorageserver.php");

$installer="vinstall.php";
$getVersion="vversion.php";

$cancelUrl=$GLOBALS['BRAND_URL']."?page=".PG_ADMIN_HOSTING;
if (SID!='')
	$cancelUrl.="&".SID;
	
$storageInfo=array();
$installStr='';
$message='';
if (GetArg('id', $storageId)) {
	$storageServer=new VStorageServer($storageId);
	$storageServer->Get($storageInfo);
	if (!isset($storageInfo['id'])) {
		ShowError("Account not found");
		return;
	}
	if ($storageInfo['brand_id']!=$GLOBALS['BRAND_ID']) {
		ShowError("The account does not belong to this site.");
		return;
	}
	$silent=true;
	include_once("vinstall/vversion.php");
	
	$versionStr=$version;
	$installedVersion=$storageInfo['installed_version'];

	$url=$storageInfo['url'];
	if (GetArg('check_version', $arg) && $url!='') {
		$url.=$getVersion;
		$resp=HTTP_Request($url);
		// this should return a version numbe string, which length should be less than 16
//		if (($resp=@file_get_contents($url))!=false && strlen($resp)<16) {
		if ($resp!=false && strlen($resp)<16) {
			$installedVersion=$resp;
			$installStr=$resp;	
		} else {
			$installedVersion='';
			$installStr="<span class='error'>not available</span>";		
		}
		
		$newInfo=array();
		$newInfo['installed_version']=$installedVersion;
		$storageServer->Update($newInfo);		

	} else {
		if ($installedVersion=='') {
			$installStr='unknown';
		} else
			$installStr=$installedVersion;
		
	}
	
	if ($installedVersion!='' && $version>$installedVersion) {
		$message=_Text("A newer version is available. Click 'Launch Installer' to install.");
	} elseif ($installedVersion=='') {
		$message=_Text("Click 'Check Version' to verify if the installation is completed. Click 'Launch Installer' to install.");		
	}


} else {
	$storageInfo['name']='';
	$storageInfo['url']='';
	$storageInfo['access_script']='';
	$storageInfo['access_code']=mt_rand(100000, 999999);
}

$thisPage=$_SERVER['PHP_SELF'];

$webPage=$GLOBALS['BRAND_URL']."?page=".PG_ADMIN_HOSTING;
$webPage=VWebServer::EncodeDelimiter2($webPage);
$okMsg=$gText['M_SUBMIT_OK'];
$okMsg.=" ".$gText['M_SELECT_PROFILE_FOR_GROUP'];

if (isset($storageInfo['id'])) 
{

	$retPage="$thisPage?page=".PG_HOME_INFORM."&ret=".$webPage."&message=".rawurlencode($okMsg)."&brand=".$GLOBALS['BRAND_NAME']."&brandUrl=".$GLOBALS['BRAND_URL'];
	if (SID!='')
		$retPage.="&".SID;
	$retPage=VWebServer::EncodeDelimiter1($retPage);
/*
	$retPage="$thisPage?page=".PG_ADMIN_EDIT_STORAGE."&id=".$storageInfo['id'];
	if (SID!='')
		$retPage.="&".SID;
	$retPage=VWebServer::EncodeDelimiter1($retPage);
*/	
	$postUrl=VM_API."?cmd=SET_STORAGE&return=$retPage";
} else {
	$retPage=$thisPage."?page=".PG_ADMIN_STORAGE_INSTALL."&brand=".$GLOBALS['BRAND_NAME']."&brandUrl=".$GLOBALS['BRAND_URL'];
	if (SID!='')
		$retPage.="&".SID;
	
	$retPage=VWebServer::EncodeDelimiter1($retPage);
	
	$postUrl=VM_API."?cmd=ADD_STORAGE&return=$retPage";
}
$postUrl.="&brand=".$GLOBALS['BRAND_NAME']."&brandUrl=".$GLOBALS['BRAND_URL'];
if (SID!='')
	$postUrl.="&".SID;	


?>


<script type="text/javascript">
<!--

function CheckWebForm(theForm) {

	if (theForm.name.value=='')
	{
		alert("Please enter a value for the \"Name\" field.");
		theForm.name.focus();
		return (false);
	}

	if (theForm.server_url.value=='')
	{
		alert("Please enter a value for the \"server_url\" field.");
		theForm.server_url.focus();
		return (false);
	}
	
	return (true);
}

//-->
</script>

<?php
if (isset($storageInfo['id'])) 
{
	// TODO: get versions from actual storage server	
	$verText=_Text("Version installed");
	$checkText=_Text("Check Version");
	$name=htmlspecialchars($storageInfo['name']);
	$installUrl=$storageInfo['url'].$installer;
	
	$siteLogin="host";
	$sitePassword=$storageInfo['access_code'];
	$serverUrl=SITE_URL;
	$brandName=$GLOBALS['BRAND_NAME'];
	$checkUrl=$thisPage."?page=".PG_ADMIN_EDIT_STORAGE."&check_version=1&brand=".$GLOBALS['BRAND_NAME']."&brandUrl=".$GLOBALS['BRAND_URL']."&id=".$storageInfo['id'];

	
print <<<END
	
	<div class="heading1">$name</div>
	<div class='inform'>$message</div>
	<form target=_blank method='POST' action="$installUrl" name="create_form">
	<b>$verText</b>: $installStr 
	<a href='$checkUrl'>[$checkText]</a>&nbsp;
	<b>Version available</b>: $versionStr &nbsp;
	<input type='hidden' name='storage' value='1'>
	<input type='hidden' name='login' value='$siteLogin'>
	<input type='hidden' name='password' value='$sitePassword'>
	<input type='hidden' name='brand' value='$brandName'>
	<input type='hidden' name='server' value='$serverUrl'>
	<input type='submit' name='submit' value='Launch Installer'>
	</form>

END;
}
?>

<form onSubmit="return CheckWebForm(this)" method="POST" action="<?php echo $postUrl?>" name="web_form">
<?php
if (isset($storageInfo['id'])) 
{
	$id=$storageInfo['id'];
	
print <<<END
<input type="hidden" name="id" value="$id">
END;
} else {
	$name='';

	$downloadUrl='download/prc.zip';
	$awsPage=$thisPage."?page=".PG_ADMIN_AWS;
	if (SID!='')
		$awsPage.="&".SID;
		
	
print <<<END
<div class="heading1">${gText['M_ADD_STORAGE']}</div>

<p><b>Optional</b>
<p>
Storage Servers are used to store library content or recorded meetings. 
If no storage server is set, the content is stored on the same server hosting the web conference site.
By using a separate storage server, you may improve the system performance.
You can add additional Storage Servers as needed. 
All new content for a user will be stored in the current Storage Server set in the user's Group settings.
<p>
To add a Storage Server, you must create a folder on a web server and type in the folder's URL below. 
You will be asked to copy an installer to the folder and run it. PHP support is required.
END;
}

?>


<table class="meeting_detail">

<tr>
	<td class="m_key m_key_w">*Profile Name:</td>
	<td colspan=3 class="m_val">
	<input type="text" name="name" size="40" value="<?php echo $name?>">
	<span class='m_caption'>*<?php echo $gText['M_REQUIRED']?></span>
	</td>
</tr>

<tr>
	<td class="m_key m_key_w">*Server Directory URL:</td>
	<td colspan=3 class="m_val">
	<input type="text" name="server_url" size="65" value="<?php echo $storageInfo['url']?>">
	<div class='m_caption'><?php echo _Text("URL for the Storage Server folder. e.g. http://myserver.com/storage/")?> </div>
	</td>
</tr>

<tr>
	<td class="m_key m_key_w"><?php echo _Text("Authentication Code")?>:</td>
	<td colspan=3 class="m_val">
	<input type="text" name="access_code" size="8" value="<?php echo $storageInfo['access_code']; ?>">
	<span class='m_caption'><?php echo _Text("Copy the code to the installer.")?></span>
	</td>	
</tr>

<tr>
	<td class="m_key">&nbsp;</td>
	<td colspan="3" class="m_val"><input type="submit" name="submit" value="<?php echo $gText['M_SUBMIT']?>">&nbsp;
	<input type="reset" name="reset" value="<?php echo $gText['M_RESET']?>">&nbsp;
	<input type="button" name="cancel" value="<?php echo $gText['M_CANCEL']?>" onclick="return GoToUrl('<?php echo $cancelUrl?>')">
	<div class='m_caption'><?php echo $gText['M_SELECT_PROFILE_FOR_GROUP']?></div>
	</td>
</tr>

</table>
</form>
