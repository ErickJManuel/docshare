<?php
/**
 * Persony Web Conferencing 2.0
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2008 Persony, Inc.
 * @version     2.0
 * 
 */

require_once("dbobjects/vwebserver.php");
//require_once("dbobjects/vsite.php");
/*
$ec2Images=array(
	"ami-f6fd189f" => "persony2_site-ami-images/image.manifest.xml"
);

$imageOptions="<select name='ec2_image'>";
foreach ($ec2Images as $k => $v) {
	$imageOptions.="<option value='$k'>$k ($v)</option>";
}
*/
$installer="vinstall.php";
$getVersion="vversion.php";
$cancelUrl=$GLOBALS['BRAND_URL']."?page=".PG_ADMIN_HOSTING;
if (SID!='')
	$cancelUrl.="&".SID;

$webInfo=array();
$installStr='';
$message='';
if (GetArg('id', $webServerId)) {
	$webServer=new VWebServer($webServerId);
	$webServer->Get($webInfo);
	if (!isset($webInfo['id'])) {
		ShowError("Account not found");
		return;
	}
	if ($webInfo['brand_id']!=$GLOBALS['BRAND_ID']) {
		ShowError("The account does not belong to this site.");
		return;
	}
	$silent=true;
	include_once("vinstall/vversion.php");
	
	$versionStr=$version;
		
	$installedVersion=$webInfo['installed_version'];
	
	$awsId=$webInfo['aws_id'];
	
	$url=$webInfo['url'];
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
		$webServer->Update($newInfo);			

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
	
	if (defined("ENABLE_CACHING_SERVERS") && ENABLE_CACHING_SERVERS=='1') {
		$slaveIds=$webInfo['slave_ids'];
		if ($slaveIds!='')
			$slaveList=explode(",", $slaveIds);
				
		$brandId=$gBrandInfo['id'];
		$prepend="<option value=\"0\">None</option>";	
		$query="`brand_id` = '$brandId' AND `id`<>'$webServerId'";
		
		$serverOpts=array();

		for($i=0; $i<4; $i++) {
			$name="slaveid_".$i;
			$selectedId='';
			if (isset($slaveList[$i]))
				$selectedId=$slaveList[$i];
			$serverOpts[$i]=VObject::GetFormOptions(TB_WEBSERVER, $query, $name, "name", $selectedId, $prepend);
		}
	}
} else {
	
	$webInfo['name']='';
	$webInfo['login']='host';
	$webInfo['password']=mt_rand(100000, 999999);
	if (strpos($GLOBALS['BRAND_URL'], "https://")===0)
		$webInfo['url']='https://';
	else
		$webInfo['url']='http://';
		
//	$webInfo['php_ext']='';
//	$webInfo['def_page']='';
//	$webInfo['file_perm']='';
//	$webInfo['path']='';
//	$webInfo['ftp_server']='';
//	$webInfo['ftp_login']='';
//	$webInfo['ftp_pass']='';
	
}

$thisPage=$_SERVER['PHP_SELF'];

if (isset($webInfo['id'])) 
{

	$webPage=$GLOBALS['BRAND_URL']."?page=".PG_ADMIN_HOSTING;
	if (SID!='')
		$webPage.="&".SID;

	$webPage=VWebServer::EncodeDelimiter2($webPage);

	$retPage="index.php?page=".PG_HOME_INFORM."&ret=".$webPage."&message=".rawurlencode($gText['M_SUBMIT_OK']);

	//$retPage=$thisPage."?page=".PG_ADMIN_INSTALL."&id=".$webInfo['id']."&brand=".$GLOBALS['BRAND_NAME']."&brandUrl=".$GLOBALS['BRAND_URL'];	
//	$retPage=$thisPage."?page=".PG_ADMIN_EDIT_WEB."&id=".$webInfo['id'];
	if (SID!='')
		$retPage.="&".SID;

	$retPage=VWebServer::EncodeDelimiter1($retPage);
	$postUrl=VM_API."?cmd=SET_WEB&return=$retPage"."&brand=".$GLOBALS['BRAND_NAME']."&brandUrl=".$GLOBALS['BRAND_URL'];

	$connections="<select name='max_connections'>";
	
	$selectedVal='';
	$foundSelect=false;
	if ($webInfo['max_connections']==-1) {
		$selectedVal='selected';
		$foundSelect=true;
	}
	
	$connections.="<option $selectedVal value='-1'>do not use caching servers</option>";
	for ($i=0; $i<=5; $i++) {
		$num=$i*50;
		$selectedVal='';
		if ($webInfo['max_connections']==$num) {
			$selectedVal='selected';
			$foundSelect=true;
		}

		$text="when number of participants > ".$num;
		
		$connections.="<option $selectedVal value='$num'>$text</option>";
	}
	
	// add an additional option to handle a non-standard value
	if (!$foundSelect) {
		$num=$webInfo['max_connections'];
		$connections.="<option selected value='$num'> > $num participants</option>";		
	}
	$connections.="</select>";


} else {
	$retPage=$thisPage."?page=".PG_ADMIN_INSTALL."&brand=".$GLOBALS['BRAND_NAME']."&brandUrl=".$GLOBALS['BRAND_URL'];
	if (SID!='')
		$retPage.="&".SID;

	$retPage=VWebServer::EncodeDelimiter1($retPage);
	$postUrl=VM_API."?cmd=ADD_WEB&return=$retPage"."&brand=".$GLOBALS['BRAND_NAME']."&brandUrl=".$GLOBALS['BRAND_URL'];
}
if (SID!='')
	$postUrl.="&".SID;
	
	
$addAwsUrl=$thisPage."?page=".PG_ADMIN_ADD_AWS;

$format=$gText['M_ENTER_VAL'];


?>

<script type="text/javascript">
<!--

function CheckWebForm(theForm) {

	if (theForm.name.value=='')
	{
		alert("<?php echo sprintf($format, 'Name')?>");
		theForm.name.focus();
		return (false);
	}
	if (theForm.url.value=='')
	{
		alert("<?php echo sprintf($format, 'URL')?>");
		theForm.url.focus();
		return (false);
	}

	return (true);
}

function SelectWeb() {
	document.getElementById('web_info').style.display='inline';
	document.getElementById('add_aws').style.display='none';
}

function SelectAWS() {
	document.getElementById('web_info').style.display='none';
	document.getElementById('add_aws').style.display='inline';
}

//-->
</script>


<?php
if (isset($webInfo['id'])) 
{
	$name=htmlspecialchars($webInfo['name']);
	$siteUrl=SITE_URL;
/*	
	if (($errMsg=VSite::GetSiteUrl($siteUrl))!='') {
		ShowError($errMsg);
		return;
	}
	
	// get the current version available
	$checkUrl=VWebServer::AddPaths($siteUrl, $getVersion);
	$currentVersion=@file_get_contents($checkUrl);
*/
/*	
	// get the version installed on the web server
	$checkUrl=VWebServer::AddPaths($webInfo['url'], $getVersion);
	$installedVersion=@file_get_contents($checkUrl);
*/
	
//	$installUrl=$webInfo['url']."$installer?server=".$siteUrl."&login=".$webInfo['login']."&password=".$webInfo['password'];

	$installUrl=$webInfo['url']."vinstall.php";
	$siteLogin=$webInfo['login'];
	$sitePassword=$webInfo['password'];
	$brandName=$GLOBALS['BRAND_NAME'];
	$serverUrl=$siteUrl;
	// get the management server url from the database in case it is changed after the first install
	VObject::Find(TB_VERSION, 'number', $version, $verInfo);
	if ($siteUrl==SSL_SERVER_URL && isset($verInfo['ssl_source_url']))
		$serverUrl=$verInfo['ssl_source_url'];
	else if (isset($verInfo['source_url']))
		$serverUrl=$verInfo['source_url'];
	
	$winTitle=$gBrandInfo['product_name'];
	$checkUrl=$thisPage."?page=".PG_ADMIN_EDIT_WEB."&check_version=1&brand=".$GLOBALS['BRAND_NAME']."&brandUrl=".$GLOBALS['BRAND_URL']."&id=".$webInfo['id'];
	if (SID!='')
		$checkUrl.='&'.SID;

//	<input type='hidden' name='no_update' value='1'>
	
	$verText=_Text("Version installed");
	$checkText=_Text("Check Version");
	
print <<<END
<div class="heading1">$name</div>
<div class='inform'>$message</div>

	<form target=_blank method='POST' action="$installUrl" name="create_form">
	<b>$verText</b>: $installStr 
	<a href='$checkUrl'>[$checkText]</a>&nbsp;
	<b>Version available</b>: $versionStr &nbsp;
	<input type='hidden' name='login' value='$siteLogin'>
	<input type='hidden' name='password' value='$sitePassword'>
	<input type='hidden' name='brand' value='$brandName'>
	<input type='hidden' name='win_title' value='$winTitle'>
	<input type='hidden' name='server' value='$serverUrl'>
	<input type='submit' name='submit' value='Launch Installer'>
	</form>

END;
//<li>Current Version: <b>$currentVersion</b> <a target=_blank href='$installUrl'>Launch Scripts Installer</a></li>

/*
if ($installedVersion==false) {
	$installPage=$thisPage."?page=".PG_ADMIN_INSTALL."&id=".$webInfo['id'];

echo "<li>Version Installed: <b>Not installed.<b> <a href='$installPage'>Install Meeting Scripts</a></li>\n";	
} else {
echo "<li>Version Installed in the Meeting Directory: <b>$installedVersion</b> <a target=_blank href='$installUrl'>Launch Scripts Installer</a></li>\n";
}
*/

} else {
	$name='';
	
	$awsPage=$thisPage."?page=".PG_ADMIN_AWS;
	if (SID!='')
		$awsPage.="&".SID;

	$text1=_Text("Web conferencing requires a Web server, such as Apache or Windows IIS, running PHP 4.3 or above. You will be asked to install some PHP files in a directory on the server after you create the account.");
	$awsLink="<a target=${GLOBALS['TARGET']} href='$awsPage'>Amazon Web Services (AWS)</a>";
	$text2=sprintf(_Text("Alternatively, you can launch a pre-configured server on %s."), $awsLink);

print <<<END
<div class="heading1">${gText['M_ADD_WEB']}</div>

$text1
<p>
$text2

END;
/*
<p>
<form>
<span><input checked onclick='SelectWeb(); return true;' type="radio" name='web_type'><b>Install on a Web Server</a></span>&nbsp;&nbsp;
<span><input onclick='SelectAWS(); return true;' type="radio" name='web_type'><b>Launch a pre-configured AWS server</b></span>
</form>
<br>
*/
}

?>



<div id='web_info'>
<form onSubmit="return CheckWebForm(this)" method="POST" action="<?php echo $postUrl?>" name="web_form">
<?php
if (isset($webInfo['id'])) {
	$webServerId=$webInfo['id'];
	print <<<END
<input type="hidden" name="id" value="$webServerId">
END;
} else {
	$login=$webInfo['login'];
	$pass=$webInfo['password'];

	print <<<END
<input type="hidden" name="login" value="$login">
<input type="hidden" name="password" value="$pass">
END;
}

?>

<table class="meeting_detail">

<tr>
	<td class="m_key">*<?php echo _Text("Profile Name")?>:</td>
	<td colspan=3 class="m_val">
	<input type="text" name="name" size="30" value="<?php echo $name?>">
	<span class='m_caption'>*<?php echo $gText['M_REQUIRED']?></span>
	</td>
</tr>

<tr>
	<td class="m_key">*<?php echo _Text("Meeting Directory URL")?>:</td>
	<td colspan=3 class="m_val">
	<input <?php if (isset($webInfo['id'])) echo 'readonly'; ?> type="text" name="url" size="65" autocorrect="off" autocapitalize="off" value="<?php echo $webInfo['url']?>">
	<div class='m_caption'><?php echo _Text("URL of the directory to host your meetings. e.g. http://www.mysite.com/meetings/")?> </div>
	</td>
</tr>

<tr>
	<td class="m_key"><?php echo _Text("Authentication Code")?>:</td>
	<td colspan=3 class="m_val">
	<input <?php if (isset($webInfo['id'])) echo 'readonly'; ?> type="text" name="password" size="8" value="<?php echo $webInfo['password']?>">
	<span class='m_caption'><?php echo _Text("Copy the code to the installer.")?></span>
	</td>
</tr>

<?php
if (isset($webInfo['id']) && defined("ENABLE_CACHING_SERVERS") && ENABLE_CACHING_SERVERS=='1') {
	$cssText=_Text("Caching Servers");
	$csText=_Text("Caching Server");
	$maxConn=$webInfo['max_connections'];
	$target=$GLOBALS['TARGET'];
	$href=$GLOBALS['BRAND_URL']."?page=".PG_ADMIN_ADD_WEB;
	
	if ($maxConn<0)
		$maxConn='';	// no limit
	
print <<<END
<tr>
	<td class="m_key">$cssText:</td>
	<td colspan=3 class="m_val">
	<div class='m_caption'>Caching servers allow you to host a single meeting on mulitiple servers to increase the meeting size. 
	Any Web Conference Server can be used as a caching server. Install a caching server by <a target='$target' href='$href'>adding a Web Conference Hosting Profile</a>.
	</div><br>
	<b>Use cachings servers:</b> 
	$connections
	<div class='m_caption'>Caching servers will be used only when the number of concurrent participants on this server exceeds this number.
	There may be a slightly longer delay whan a participant connects via a caching server.</div>
	<br>

END;
	$i=0;
	foreach ($serverOpts as $aopt) {
		echo "<b>$csText ".($i+1).":</b> $serverOpts[$i]<br>\n";
		$i++;
	}
print <<<END
	</td>
</tr>

END;
	
}
?>

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
</div>

