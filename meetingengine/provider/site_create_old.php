<?php
/**
 * Persony Web Conferencing 2.0
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2008 Persony, Inc.
 * @version     2.0
 * 
 */

require_once("dbobjects/vbrand.php");
require_once("dbobjects/vuser.php");

$minPhpVersion='4.3.0';
$silent=true;
$configFile="server_config.php";


require_once("vinstall/vversion.php");
require_once($configFile);

$providerId=GetSessionValue('provider_id');
$provider=new VProvider($providerId);
$providerInfo=array();
$provider->Get($providerInfo);

if (defined("LOG_DIR") && LOG_DIR!='')	
	$logFile=LOG_DIR."createsite.log";
else
	$logFile='';

$phpMessage='';
$setupMessage='';
$postUrl=$_SERVER['PHP_SELF']."?page=SITE_CREATE";
if (SID!='')
	$postUrl.="&".SID;
	
$directory=getcwd();
umask(0);
$mode=fileperms("./");
$octperms=sprintf("%o", $mode);
$dirPerm=substr($octperms, -3);
	
	
// check if the brand already exists
$brandInfo=array();
GetArg('brand', $brandName);
if ($brandName!='')
	VObject::Find(TB_BRAND, "name", $brandName, $brandInfo);

$step=0;
$errorMsg='';
$newBrand=true;
$siteLogin='';
$sitePassword='';

if (isset($brandInfo['id'])) {
	
	$brandId=$brandInfo['id'];
	$brand=new VBrand($brandId);
	$brand->GetValue('admin_id', $adminId);
	$admin=new VUser($adminId);
	$admin->GetValue('login', $adminEmail);
	$admin->GetValue('password', $password);
	
	$siteUrl=$brandInfo['site_url'];
	$fromEmail=$brandInfo['from_email'];
	$fromName=$brandInfo['from_name'];
	$productName=$brandInfo['product_name'];
	$brandName=$brandInfo['name'];
	$newBrand=false;
	
	$groupInfo=array();
	$errMsg=VObject::Find(TB_GROUP, "brand_id", $brandId, $groupInfo);
	if ($errMsg!='') {
		ShowError($errMsg);
		return;
	}
	if (!isset($groupInfo['id'])) {
		ShowError("Missing group id");
		return;
	}		
	$groupId=$groupInfo['id'];
	
	$webServer=new VWebServer($groupInfo['webserver_id']);
	$webServerInfo=array();
	$webServer->Get($webServerInfo);
	if (isset($webServerInfo['id'])) {
		$siteLogin=$webServerInfo['login'];
		$sitePassword=$webServerInfo['password'];	
	}
} else {

	if (isset($_SERVER['HTTPS']))
		$siteUrl='https://';
	else
		$siteUrl='http://';
	
	$adminName=$providerInfo['first_name'].' '.$providerInfo['last_name'];
	$adminEmail=$providerInfo['admin_email'];
//	$fromEmail=$providerInfo['admin_email'];
//	$fromName=$providerInfo['company_name'];
	$fromEmail=SERVER_EMAIL;
	$fromName='';
	$productName='';
	$brandName=$providerInfo['company_name'];
	$password='';
	
	GetArg('admin_name', $adminName);
	GetArg('admin_email', $adminEmail);
	GetArg('password1', $password);
}


if (GetArg('site_url', $arg) && $arg!='')
	$siteUrl=$arg;

GetArg('from_email', $fromEmail);
GetArg('from_name', $fromName);
GetArg('product_name', $productName);

// if setting us a ssl site, use the ssl management server url if available
if (strpos($siteUrl, "https://")===0) {
	$serverUrl=SSL_SERVER_URL;
	if ($serverUrl=='')
		$serverUrl=SERVER_URL;
} else {
	$serverUrl=SERVER_URL;
}
if ($serverUrl[strlen($serverUrl)-1]!='/')
	$serverUrl.='/';
	
if (isset($_POST['create_site'])) {
	
	if (($maxSites=(integer)$providerInfo['max_sites'])>0) {
		$query="provider_id='$providerId' AND status='ACTIVE'";
		$errMsg=VObject::Count(TB_BRAND, $query, $numSites);
		if ($numSites>=$maxSites) {
			ShowError("Your account allows you to create $numSites sites and you have exceeded the limit. Please deactivate a site first.");
			return;
		}		
	}
	$errorMsg=CreateBrand();
	if ($errorMsg=='')
		$step=1;
	else {
		ShowError($errorMsg);
		return;
	}
} else if (isset($_POST['send_pwd'])) {
	$errorMsg=SendPwd();	
}


function LogErrorExit($msg, $logFp)
{
	if ($logFp) {
		fwrite($logFp, $msg."\r\n");
		fclose($logFp);
	}
	die($msg);
}

function SendPwd() {
require_once("dbobjects/vmailtemplate.php");
//	global $fromName, $fromEmail, $siteUrl;
	global $siteUrl;
	
	$brandInfo=array();
	VObject::Find(TB_BRAND, "site_url", $siteUrl, $brandInfo);
	if (!isset($brandInfo['id']))
		return ("No record exists for the site ".$siteUrl);
	
	$brandId=$brandInfo['id'];
	$brand=new VBrand($brandId);
	$brand->GetValue('admin_id', $adminId);
	$admin=new VUser($adminId);
	$admin->Get($adminInfo);
	
	if (!isset($adminInfo['id'])) {
		return ("No admin record is found for the site.");
	}
	
	$subject="Account Info";
	$body="Your password is ".$adminInfo['password'];
	$toName=$admin->GetFullName($adminInfo);
	$toEmail=$adminInfo['email'];
	if ($toEmail=='')
		$toEmail=$adminInfo['login'];
		
	$fromName=$brandInfo['from_name'];
	$fromEmail=$brandInfo['from_email'];
		
	$err=VMailTemplate::Send($fromName, $fromEmail, $toName, $toEmail, $subject, $body,
			'', '', "", false, null, $brandInfo);

	if ($err!='')
		return $err;
	else
		return "The password has been sent to $toEmail";
	
}

function CreateBrand()
{
require_once("dbobjects/vwebserver.php");
require_once("dbobjects/vgroup.php");
require_once("dbobjects/vviewer.php");
require_once("dbobjects/vmailtemplate.php");

	global $serverUrl, $logFile, $siteUrl, $fromEmail, $adminEmail, $adminName;
	global $fromName, $productName, $brandName, $password;
//	global $showRequestPwd;
	global $siteLogin, $sitePassword, $providerId;
	global $brandInfo, $providerInfo;
	if ($logFile!='')
		$logFp=fopen($logFile, "a");
	else
		$logFp=null;
	$ip='';
	if (isset($_SERVER['REMOTE_ADDR']))
		$ip = $_SERVER['REMOTE_ADDR'];
	if ($logFp)
		fwrite($logFp, date('Y-m-d H:i:s')." ".$_SERVER['PHP_SELF']." ".$ip."\r\n");
			
	// create a brand
	// check if the brand exists
	$len=strlen($siteUrl);
	if ($len>0 && $siteUrl[$len-1]!='/')
		$siteUrl.='/';

	// make sure this is a valid url
	$urlItems=@parse_url($siteUrl);
	if (!isset($urlItems['scheme']) || 
		($urlItems['scheme']!='http' && $urlItems['scheme']!='https') ||
		!isset($urlItems['host'])) 
	{
		return ("Invalid site url '$siteUrl'");
	}
		
		
//	$brandInfo=array();
//	VObject::Find(TB_BRAND, "site_url", $siteUrl, $brandInfo);
	
//	$adminInfo=array();
	$adminId=0;
	$oldSiteUrl='';
	if (isset($brandInfo['id'])) {
		if ($logFp)
			fwrite($logFp, "Existing site $siteUrl\r\n");

		$brandId=$brandInfo['id'];
		$brand=new VBrand($brandId);
		$brand->GetValue('admin_id', $adminId);
		$oldSiteUrl=$brandInfo['site_url'];
//		$admin=new VUser($adminId);
//		$admin->Get($adminInfo);

	} else {
		if ($logFp)
			fwrite($logFp, "Create a new site $siteUrl\r\n");
		
		// new brand; check if the url exists
		$oldBrandInfo=array();
		VObject::Find(TB_BRAND, "site_url", $siteUrl, $oldBrandInfo);
		if (isset($oldBrandInfo['id']))
			return("The site record already exists.");
		
	}
	
	// find the first trial license from the database
	// we need it to set the default values
	$trialInfo=array();
	VObject::Find(TB_LICENSE, "trial", 'Y', $trialInfo);
	if (isset($trialInfo['id']))
		$trialId=$trialInfo['id'];
	else
		$trialId=0;

	if ($logFp)
		fwrite($logFp, "Trial license id is $trialId\r\n");
		
	$offerings='';
	VProvider::ParseLicenseKey($providerInfo, $licenseType, $licenseCounts, $recording);
	foreach ($licenseCounts as $key => $val) {
		// don't include PORT licenses
		if ($key=='PTS' || $key=='PTV')
			continue;

		if ($offerings!='')
			$offerings.=",";
		$offerings.=$key;
	}

	$brandInfo['provider_id']=$providerId;
	$brandInfo['from_email']=$fromEmail;
	$brandInfo['offerings']='TPV1,P10,PV10,P25,PV25,P50,PV50,P100,PV100';
//	$brandInfo['offerings']=$offerings;
	$brandInfo['site_url']=$siteUrl;
	$brandInfo['trial_license_id']=$trialId;
	$brandInfo['from_name']=$fromName;
	$brandInfo['product_name']=$productName;
	$brandInfo['locale']='en';
	$brandInfo['theme']='default';	
	$brandInfo['footer1_label']="About Us";	
	$brandInfo['footer2_label']="Contact Us";	
	$brandInfo['footer3_label']="Terms of Service";	
	$brandInfo['footer4_label']="Privacy Policy";	
	$powerUrl="images/poweredby_persony.png";
	$brandInfo['footnote']="<a target=_blank href='http://www.persony.com'><img src='$powerUrl'></a>";	
	$brandInfo['home_text']="";	
	$brandInfo['footer1_text']="";	
	$brandInfo['footer2_text']="";	
	$brandInfo['footer3_text']="";	
	$brandInfo['footer4_text']="";
	
	if (!isset($brandInfo['id'])) {
		// create a viewer for the brand
		if ($logFp)
			fwrite($logFp, "Adding a default viewer for the site\r\n");
		
		$viewer=new VViewer();
		$viewerInfo=array();
		$viewerInfo['logo_id']=1;
		$viewerInfo['back_id']=1;
		if ($viewer->Insert($viewerInfo)!=ERR_NONE) {
			return($viewer->GetErrorMsg());
		}
		$viewer->GetValue("id", $viewerId);
		$brandInfo['viewer_id']=$viewerId;
/*		
		VObject::SelectAny(TB_VIEWER, $viewerInfo);
		if (!isset($viewerInfo['id']))
			return("Couldn't find a viewer");
			
		$brandInfo['viewer_id']=$viewerInfo['id'];
			
		$viewer->GetValue("id", $viewerId);
*/		
		$logoInfo=array();
		VObject::Find(TB_IMAGE, "name", "default_banner.jpg", $logoInfo);
		if (isset($logoInfo['id']))
			$brandInfo['logo_id']=$logoInfo['id'];

		// the 2nd image record should be the default banner
//		$brandInfo['logo_id']=2;		
		$brand=new VBrand();
		if ($logFp)
			fwrite($logFp, "Finding a brand id\r\n");
		
		// create a unique brand name
		for ($i=0; $i<10; $i++) {
			// 7 digits
			$brandName=mt_rand(1000000, 9999999);
			if (!VObject::InTable(TB_BRAND, 'name', $brandName))
				break;
			elseif ($i==9) {
				return ("Couldn't find an available brand id.");
			}
		}
		$brandInfo['name']=$brandName;
		$brandInfo['create_time']='#NOW()';
		
		if ($logFp)
			fwrite($logFp, "Adding a new brand $brandName\r\n");
		
		if ($brand->Insert($brandInfo)!=ERR_NONE) {
			return($brand->GetErrorMsg());
		}
			
		$brand->GetValue("id", $brandId);
	
	} else {
		require_once("dbobjects/vwebserver.php");
		
		if ($logFp)
			fwrite($logFp, "Updating an existing brand\r\n");

	
		if ($brand->Update($brandInfo)!=ERR_NONE) {
			return($brand->GetErrorMsg());
		}
		
		if ($brand->Get($brandInfo)!=ERR_NONE) {
			return($brand->GetErrorMsg());
		}
		$brandName=$brandInfo['name'];
		
		// find the default hosting account for this brand and change its url too
		if ($oldSiteUrl!=$brandInfo['site_url']) {
			$query="brand_id='".$brandInfo['id']."' AND url='".$oldSiteUrl."'";
			$serverInfo=array();
			$errMsg=VObject::Select(TB_WEBSERVER, $query, $serverInfo);
			if ($errMsg!='') {
				return($errMsg);
			}
			if (isset($serverInfo['id'])) {
				if ($logFp)
					fwrite($logFp, "Updating the default hosting account for the brand\r\n");
				$server=new VWebServer($serverInfo['id']);
				$newInfo=array();
				$newInfo['url']=$brandInfo['site_url'];
				if ($server->Update($newInfo)!=ERR_NONE)
					return($server->GetErrorMsg());			
			}
		}
	}
	
	if ($logFp)
		fwrite($logFp, "Finding a default group\r\n");
	
	// create a group
	$groupInfo=array();
	$errMsg=VObject::Find(TB_GROUP, "brand_id", $brandId, $groupInfo);
	if ($errMsg!='')
		return ($errMsg);
		
	if (!isset($groupInfo['id'])) {
		if ($logFp)
			fwrite($logFp, "Adding a default group\r\n");
		
		$webServer=new VWebServer();
		$webServerInfo=array();
		$webServerInfo['brand_id']=$brandId;
		$urls=parse_url($siteUrl);
		$webServerInfo['name']=$urls['host'];
		$webServerInfo['login']='host';
		$webServerInfo['password']=(string)mt_rand(100000, 999999);
		$webServerInfo['url']=$siteUrl;
		
		if ($webServer->Insert($webServerInfo)!=ERR_NONE) {
			return($webServer->GetErrorMsg());
		}
		$webServer->GetValue("id", $webserverId);
		$siteLogin=$webServerInfo['login'];
		$sitePassword=$webServerInfo['password'];
		
		$group=new VGroup();
		$groupInfo['brand_id']=$brandId;
		$groupInfo['name']='default';
		$groupInfo['webserver_id']=$webserverId;
		$groupInfo['description']='';
		
		if ($group->Insert($groupInfo)!=ERR_NONE) {
			return($group->GetErrorMsg());
		}
		$group->GetValue("id", $groupId);
/*
	} else {
		$groupId=$groupInfo['id'];
		
		$webServer=new VWebServer($groupInfo['webserver_id']);
		$webServerInfo=array();
		$webServer->Get($webServerInfo);
		$siteLogin=$webServerInfo['login'];
		$sitePassword=$webServerInfo['password'];
*/		
	}
	
	// create admin user

	if ($adminId==0) {	
		if ($logFp)
			fwrite($logFp, "Adding an admin user\r\n");
	
		$admin=new VUser();
		$adminInfo=array();
		$adminInfo['brand_id']=$brandId;
		$adminInfo['login']=$adminEmail;		
		$words=explode(" ", $adminName);
		if (count($words)>0)
			$adminInfo['first_name']=$words[0];	
		if (count($words)>1)
			$adminInfo['last_name']=$words[1];
		
//		$adminInfo['password']=(string)mt_rand(100000, 999999);
		if ($password=='')
			$password=(string)mt_rand(100000, 999999);
		$adminInfo['password']=$password;
		$adminInfo['permission']='ADMIN';
		$adminInfo['group_id']=$groupId;
		$adminInfo['license_id']=$trialId;
		$adminInfo['create_date']=date('Y-m-d H:i:s');		
		$adminInfo['room_description']="";		
		
		if ($admin->Insert($adminInfo)!=ERR_NONE) {
			return($admin->GetErrorMsg());
		}
		$admin->GetValue("id", $adminId);
//		$password=$adminInfo['password'];	
		
		// update brand
		$newInfo=array();
		$newInfo['admin_id']=$adminId;
		$newInfo['trial_group_id']=$groupId;
		if ($brand->Update($newInfo)!=ERR_NONE) {
			return($brand->GetErrorMsg());
		}
		
		// send email to the admin
		$subject="New Web Conferencing Site";
		$body="A new web conferencing site has been created.\n";
		$body="URL: $siteUrl\n";
		$body.="Admin name: ".$adminName."\n";
		$body.="Admin login: ".$adminInfo['login']."\n";
		$body.="Admin password: ".$adminInfo['password']."\n";
		$toName=$admin->GetFullName($adminInfo);
		$toEmail=$adminInfo['login'];
			
		$err=VMailTemplate::Send($fromName, $fromEmail, $toName, $toEmail, $subject, $body);
		$toName=$admin->GetFullName($adminInfo);
		
		$toEmail=ADMIN_EMAIL;
		if (isset($toEmail) && $toEmail!='')
			$err=VMailTemplate::Send($fromName, $fromEmail, $toName, $toEmail, $subject, $body);

/*
	} else {
		$adminEmail=$adminInfo['login'];
		$password=$adminInfo['password'];
*/
		
	}
	if ($logFp)
		fwrite($logFp, "Brand $brandName url $siteUrl created\r\n");
	
	if ($logFp)
		fclose($logFp);
	
	return '';
}


?>


<script type="text/javascript">
<!--

function CheckCreateForm(theForm) {

	var theForm=document.getElementById('create_site');

	if (theForm.site_url.value=='')
	{
		alert("Please enter a value for the \"Web Site URL\" field.");
		theForm.site_url.focus();
		return (false);
	}
<?php
if (isset($newSite) && $newSite) {
	print <<<END

	if (theForm.admin_name.value=='')
	{
		alert("Please enter a value for the \"Admin Name\" field.");
		theForm.admin_name.focus();
		return (false);
	}
	if (theForm.admin_email.value=='')
	{
		alert("Please enter a value for the \"Admin Email\" field.");
		theForm.admin_email.focus();
		return (false);
	}

	if (theForm.password1.value=='')
	{
		alert("Please enter a value for the \"Password\" field.");
		theForm.password1.focus();
		return (false);
	}
	if (theForm.password1.length<5 || theForm.password1.length>8)
	{
		alert("Incorrect length for the \"Password\" field.");
		theForm.password1.focus();
		return (false);
	}
	if (theForm.password1.value!=theForm.password2.value)
	{
		alert("Passwords do not match.");
		theForm.password1.focus();
		return (false);
	}
END;
}
?>
	return (true);
}

function MyConfirm(msg) {

	return confirm(msg);

}

//-->
</script>




<?php
if ($step==0) {

	if ($newBrand)
		$title="Create a Web Conferencing Site";	
	else
		$title=$siteUrl;
	
	print <<<END

<div class='heading1'>$title</div>
<div class='error'>$errorMsg</div>
<form id='create_site' method="POST" action="$postUrl" name="create_form">
<table class="meeting_list">
	<tr>
		<td class="m_key m_key_m">*Web Conferencing Site URL:</td>
		<td class="m_val">
		<input type="text" name="site_url" size="50" value="$siteUrl">
		<div class="m_caption">URL of my Web conferencing site. The site needs to support PHP 4.3 or above. 
		The URL should link to the home directory or a sub-directory on a Web site. 
		The directory must exist on the server and you will be asked to upload files to this directory.</div>
		</td>
	</tr>
END;

// only shows Admin info if creating a new brand
if ($newBrand) {
	print <<<END
	<tr>
		<td class="m_key m_key_m">*Site Admin:</td>
		<td class="m_val">
		<div class='subval'>Admin Name: <input type="text" name="admin_name" size="30" value="$adminName"></div>
		<div class='subval'>Admin Email: <input type="text" name="admin_email" size="30" value="$adminEmail"></div>
		<div>
		<span class='subval'>Password: <input type="password" name="password1" size="8" maxlength='8' value=''></span>
		<span class='subval'>Retype Password: <input type="password" name="password2" size="8" maxlength='8' value=''></span>
		<span class='m_caption'>Up to 8 characters</span>
		<div class="m_caption">Administrator of the site. You wil use the email address and password to sign in to the site. 
		You can change the password later after signing in.</div>
		</div>
		</td>
	</tr>
END;
} else {
	print <<<END
	<input type='hidden' name='brand' value='$brandName'>
	<tr>
		<td class="m_key m_key_m">Site Admin:</td>
		<td class="m_val">
		<div>
		$adminEmail <span class='subval'><input type="submit" name="send_pwd" value="Send Password"></span>
		</div>
		</td>
	</tr>
END;
	
}

print <<<END

	<tr>
		<td class="m_key m_key_m">Site Email:</td>
		<td class="m_val">
		<div class='subval'>From Name: <input type="text" name="from_name" size="30" value="$fromName"></div>
		<div class='subval'>From Email: <input type="text" name="from_email" size="30" value="$fromEmail"></div>
		<div class=m_caption>Email name and address for outgoing email from the site. The email address does not need to exist. You can change this later.</div>
		</td>
	</tr>
	<tr>
		<td class="m_key m_key_m">Product Name:</td>
		<td class="m_val">
		<input type="text" name="product_name" size="20" value="$productName">
		<div class=m_caption>Name of my Web conferencing product or service. The name will appear in your Web conferencing site window title bar and email.
		You can change this later.</div>
		</td>
	</tr>
	
</table>
<br>

<div class='doit'>
<div class=m_caption>*Required fields.</div>
<div class=m_caption>When you click "Create Site", a record will be created for this site.
You can run this page again to modify the record.
You will be asked to install files in the Web site folder afterward.
</div>
<div style="text-align: center;"><input onclick="return CheckCreateForm();" type="submit" name="create_site" value="Submit"></div>
</div>


</form>

END;

} else if ($step==1) {
	
	$downloadUrl='download/vinstall.zip';
	$installUrlStr=$siteUrl."vinstall.php?server=$serverUrl&login=".rawurlencode($siteLogin)."&password=".rawurlencode($sitePassword)."&brand=$brandName&win_title=".rawurlencode($productName);
	
	$installUrl=$siteUrl."vinstall.php";
	$winTitle=$productName;
	$loginPage=$siteUrl."?page=SIGNIN&login_id=".rawurlencode($adminEmail)."&password=".rawurlencode($password);
	// write out config file
	$content="<?php\n \$brand=\"$brandName\";\n \$winTitle=\"$productName\";\n \$serverUrl=\"$serverUrl\";\n ?>\n";
	
	print <<<END
<div class='heading1'>Set up your Web Conferencing Site</div>
<div class='error'>$errorMsg</div>
<div class='m_val'>Complete the following steps to set up your Web conferencing site.</div>

<table class="meeting_list">
<tr>
	<td class="m_key">Web Site URL:</td>
	<td class="m_val">$siteUrl
	</td>
</tr>
<tr>
	<td class="m_key">1. Download Installer:</td>
	<td class="m_val">
	<div>Download the file and put <b>"vinstall.php"</b> in the above Web site directory.</div>
	<input onclick="parent.location='$downloadUrl'; return true;" type="button" value="Download">
	</td>
</tr>
<!--
<tr>
	<td class="m_key">2. Create Config. File:</td>
	<td class="m_val">
	<div>Create a file <b>"config.php"</b> with the following content and put it in the above Web site directory.</div>
	<textarea readonly rows="5" cols="55">$content</textarea>
	</td>
</tr>
-->
<tr>
	<td class="m_key">2. Launch Installer:</td>
	<td class="m_val">
	<div>Launch the installer to install files on your Web site.</div>
	<form target='_blank' method='post' action="$installUrl" name="create_form">
	<input type='hidden' name='no_update' value='1'>
	<input type='hidden' name='login' value='$siteLogin'>
	<input type='hidden' name='password' value='$sitePassword'>
	<input type='hidden' name='brand' value='$brandName'>
	<input type='hidden' name='win_title' value='$winTitle'>
	<input type='hidden' name='server' value='$serverUrl'>
	<input type='submit' name='submit' value='Launch Installer'>
	</form>
	</td>
</tr>
<tr>
	<td class="m_key">3. Login:</td>
	<td class="m_val">
	<div class='m_caption'>Log in to the above Web site with the following account.</div>
	<div class='subval'><a target=_blank href='$loginPage'>$loginPage</a></div>
	<span class='subval'>Login: <input readonly type="text" size="20" value="$adminEmail"></span>
	<span class='subval'>Password: <input readonly type="text" size="10" value="$password"></span>
	</td>
</tr>

</table>
END;
	
} else {
	

}
?>
