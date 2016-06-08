<?php
/**
 * @package     VShow
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2007 Persony, Inc.
 * @version     2.0
 */
require_once("server_config.php");
require_once("provider/common.php");
require_once("dbobjects/site_url.php");
require_once("dbobjects/vprovider.php");
//@include_once($GLOBALS['LOCALE_FILE']);
require_once("includes/common_text.php");

GetArg('password', $password);
GetArg('login_id', $login);
$retPage="provider.php";

$signinPage="provider_signin.php";
if (SID!='')
	$signinPage.="?".SID;

$loginMsg='';
$getpwdMsg='';

if (isset($_POST['signin'])) {
	if ($login=='') {
		$loginMsg="The login name is missing.";		
	} else if ($password=='') {
		$loginMsg="The password is missing.";		
	} else {		
		// search to see if the user already exists
		$providerInfo=array();
		$query="LOWER(login)= '".addslashes(strtolower($login))."'";
		$loginMsg=VObject::Select(TB_PROVIDER, $query, $providerInfo);
		
		if ($loginMsg!='') {
			$loginMsg="Error= ".$loginMsg;
		} else if  (!isset($providerInfo['login'])) {
			$loginMsg="The login name you provided does not match our records.";
		} 
		
		if ($loginMsg=='' && $providerInfo['password']!=$password) {
			$loginMsg="The password you provided does not match our records.";						
		}
		
		if ($loginMsg=='' && $providerInfo['status']=='INACTIVE') {
			$loginMsg='Your account is not active. Please contact your service provider.';					
		}
		
		if ($loginMsg=='') {
				
			if (!SetSessionValue("provider_id", $providerInfo['id'])) {
				$loginMsg="Session cookie cannot be set.";
			} else {
				SetSessionValue("provider_login", $providerInfo['login']);
				header("Location: $retPage");
				DoExit();
			}

		}
	}
} else if (IsSubmitted('sendpwd')) {
require_once("dbobjects/vmailtemplate.php");

	GetArg('sendpwd_id', $getId);
	
	if ($getId=='') {
		$getpwdMsg="The id is missing.";
	} else {
		$providerInfo=array();
		$query="LOWER(login)= '".addslashes(strtolower($getId))."'";
		$getpwdMsg=VObject::Select(TB_PROVIDER, $query, $providerInfo);
		
		if ($getpwdMsg!='') {
			$getpwdMsg="Error= ".$getpwdMsg;
		} else if  (!isset($providerInfo['id'])) {
			$getpwdMsg="The login name or email you provided does not match our records.";
		}
		
		if ($getpwdMsg=='' && $providerInfo['status']=='INACTIVE') {
			$getpwdMsg='Your account is not active. Please contact your service provider.';					
		}		
		
		if ($getpwdMsg=='') {		
			$provider=new VProvider($providerInfo['id']);
			
			$fromEmail=SERVER_EMAIL;
			$fromName="";
			$subject="Account Info";
			$toName=$providerInfo['first_name']." ".$providerInfo['last_name'];
			$body="Hi $toName,\n";
			$body.="Here is your account info for\n";
			$body.=SITE_URL."/provider.php\n";
			$body.="Login Name: ".$providerInfo['login']."\n";
			$body.="Password: ".$providerInfo['password'];
			$toEmail=$providerInfo['admin_email'];
			
			if (valid_email($toEmail)) {	
				$err=VMailTemplate::Send($fromName, $fromEmail, $toName, $toEmail, $subject, $body);				
				
				if ($err!='')
					$getpwdMsg=$err;
				else
					$getpwdMsg=$gText['M_PASSWORD_SENT'];
			} else {
				$getpwdMsg="Your email address is not defined or is invalid.";
			}
		}	
	}

}


$GLOBALS['TAB']="Sign In";
$GLOBALS['SUB_MENUS']=array(
		);

if (isset($_GET['page']))		
	$GLOBALS['SUB_PAGE']=$_GET['page'];
else
	$GLOBALS['SUB_PAGE']='';
	
 // will this page have a right hand side navigation?
$GLOBALS['SIDE_NAV'] = 'off';

 // if side nave on, what is the path and filename to the include?
if($GLOBALS['SIDE_NAV'] == 'on') { $GLOBAL['SIDE_INCLUDE'] = 'provider/right.php'; }

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title><?php echo $GLOBALS['TAB']?></title>
<script type="text/javascript">
<!--


//-->
</script>

<?php 
include_once('provider/header.php'); 
?>

<div class="heading1">Sign In</div>
<div class="error"><?php echo $loginMsg?></div>

<div class="login">
<form action="<?php echo $signinPage?>" method="POST" accept-charset="UTF-8"  name="signin_form">

	<table>

    <tr>
    <td align="right" width="30%">Login Name:</td>
    <td colspan="2" width="70%"><input type="text" name="login_id" value="<?php echo $login?>" size="25"></td>
    </tr>

    <tr>
    <td align="right">Password:</td>
    <td colspan="2" width="70%"><input type="password" name="password" value="<?php echo $password?>" size="25"></td>
    </tr>

	<tr valign="top">
		<td width="30%">&nbsp;</td>
		<td colspan="2" width="70%"><input type="submit" name="signin" value="Sign In"></td>
	</tr>
	
	</table>
	
    <div id="cookieDisabled">Make sure you have cookies and Javascript enabled in your browser before signing in. </div>
    <script type="text/javascript">
<!--
		var cookieEnabled=(navigator.cookieEnabled)? true : false;

		//if not IE4+ nor NS6+
		if (typeof navigator.cookieEnabled=="undefined" &&!cookieEnabled){
			document.cookie="testcookie"
			cookieEnabled=(document.cookie.indexOf("testcookie")!=-1)? true : false;
		}
		if (cookieEnabled) {
			if(document.getElementById('cookieDisabled')) document.getElementById('cookieDisabled').style.display = 'none';
		}
//-->
    </script>

  
	<input type="hidden" name="ret" value="<?php echo $retPage?>">

</form>
</div>
<br>

<div id="forgotpwd">
<form method="POST" action="<?php echo $signinPage?>" name="sendpwd_form" accept-charset="UTF-8">
	<b>Forgot password?</b><br>
	Enter your login name and we'll send it to your email address on file.<br>
	<b>Login Name:</b>&nbsp; <input type="text" name="sendpwd_id" size="25">
	<input type="submit" value="Submit" name="sendpwd">
</form>
<div id="getpwderror"><?php echo $getpwdMsg?></div>
</div>

<?php
include_once('provider/footer.php'); 
?>
