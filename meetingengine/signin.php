<?php
/**
 * Persony Web Conferencing 2.0
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2008 Persony, Inc.
 * @version     2.0
 * 
 */

require_once("includes/common.php");
require_once("includes/brand.php");
require_once("dbobjects/vuser.php");
require_once("dbobjects/vprovider.php");
require_once("dbobjects/vlicensekey.php");

GetArg('password', $password);
GetArg('login_id', $login);
GetArg('ret', $retPage);
if ($retPage=='') {
//	$retPage=$_SERVER['PHP_SELF']."?page=".PG_MEETINGS."&brand=".$GLOBALS['BRAND_NAME']."&brandUrl=".$GLOBALS['BRAND_URL'];
	$retPage=$GLOBALS['BRAND_URL']."?page=".PG_MEETINGS;
//	$retPage=$gBrandInfo['site_url']."?page=".PG_MEETINGS."&brand=".$GLOBALS['BRAND_NAME'];
	if (SID!='')
		$retPage.="&".SID;

}
/*
$signinPage=$GLOBALS['BRAND_URL']."signin.php"."?".SID;
$signoutPage=$GLOBALS['BRAND_URL']."signout.php"."?".SID;
$signupPage=$GLOBALS['BRAND_URL']."signup.php"."?".SID;
*/
//$signinPage=$_SERVER['PHP_SELF']."?".SID;

//$signupPage=$GLOBALS['BRAND_URL']."?page=".PG_SIGNUP."&".SID;

$joinPage=$GLOBALS['BRAND_URL'];

$loginMsg='';
GetArg('login_msg', $loginMsg);
$getpwdMsg='';

//if (IsSubmitted('signin')) {
if (isset($_REQUEST['signin']) || isset($_REQUEST['password'])) {
	
	if ($login=='') {
		$loginMsg="The email address is missing.";		
	} else if ($password=='') {
		$loginMsg="The password is missing.";		
	} else {		
		// search to see if the user already exists
		$userInfo=array();
		if (defined('ROOT_USER') && $login==ROOT_USER)
			$query="LOWER(login)= '".addslashes(strtolower($login))."'";
		else
			$query="LOWER(login)='".addslashes(strtolower($login))."' AND brand_id = '".$GLOBALS['BRAND_ID']."'";
		$loginMsg=VObject::Select(TB_USER, $query, $userInfo);
		//echo("count=".count($userInfo)." login=".$login." id=".$userInfo['id']);
		
		if ($loginMsg!='') {
//			$loginMsg="Error= ".$loginMsg;
			$loginMsg="Error encountered in processing a database command.";
		} else if  (!isset($userInfo['login'])) {
			$loginMsg="The email address you provided does not match our records.";
		} 
		
		// make password not case sensitive
		if ($loginMsg=='' && strtolower($userInfo['password'])!=strtolower(trim($password))) {
			$loginMsg="The password you provided does not match our records.";						
		}
		
		if ($loginMsg=='' && $userInfo['active']!='Y') {
			$loginMsg='Your account is not active. Please contact your service provider.';					
		}

		if ((defined('ENABLE_LICENSE_KEY') && constant('ENABLE_LICENSE_KEY')=='1')) {
			
			if ($loginMsg=='') {
				if ($userInfo['licensekey_id']!='0') {
					if (VUser::VerifyLicense($userInfo, $lcMsg)!=true) {
						$loginMsg=$lcMsg;
					}
				} else {
					$providerId=$gBrandInfo['provider_id'];
					$provider=new VProvider($providerId);
					$provider->GetValue('licensekey_id', $plicId);
					if ($plicId!='0') {
						$licKey=new VLicenseKey($plicId);
						$licKey->GetValue('license_text', $licenseText);
						if (!VLicenseKey::VerifyLicenseText($licenseText, $keyInfo, $errMsg)) {
							$loginMsg=$errMsg;	
/*							
							// if the key is valid, check if it is expired
						} else if ($keyInfo['expiry_date']>"1961-01-01") {
							$today=date("Y-m-d");
							if ($keyInfo['expiry_date']<$today) {
								$loginMsg="The site's license key has expired on ".$keyInfo['expiry_date'];		
							} */
						}
						
					}
				}
			}
		}

		if ($loginMsg=='') {
			
			if (defined('ROOT_USER') && $login==ROOT_USER) {
				$userInfo['brand_id']=$GLOBALS['BRAND_ID'];
				
				$trialGroupId=$gBrandInfo['trial_group_id'];
				
				$rootInfo=array();
				$rootInfo['brand_id']=$GLOBALS['BRAND_ID'];
				$rootInfo['group_id']=$trialGroupId;
				$rootUser=new VUser($userInfo['id']);
				if ($rootUser->Update($rootInfo)!=ERR_NONE)
					$loginMsg=$rootUser->GetErrorMsg();
					
				SetSessionValue("root_user", 'true');

			}
			
		}
				
		if ($loginMsg=='') {
			
				include_once("includes/signin_user.php");
				include_once("includes/house_keeping.php");
								
				//echo("$login $password $memberName");
				//$redirctPage=$GLOBALS['BRAND_URL']."?page=".$retPage."&".SID;
				$redirctPage=$retPage;
//				if (SID!='') {
//					$redirctPage.="?".SID;						
//				}

				header("Location: $redirctPage");
				exit();
//			}

		} else {
			
//			$redirctPage=$gBrandInfo['site_url']."?page=SIGNIN&&login_msg=".rawurlencode($loginMsg);
			$redirctPage=$GLOBALS['BRAND_URL']."?page=SIGNIN&&login_msg=".rawurlencode($loginMsg);

			header("Location: $redirctPage");
			exit();
		}
	}

} else if (IsSubmitted('sendpwd')) {
require_once("dbobjects/vmailtemplate.php");
ob_start();
@include_once($GLOBALS['LOCALE_FILE']);
ob_end_clean();
require_once("includes/common_text.php");

	GetArg('sendpwd_id', $getId);
	
	if ($getId=='') {
		$getpwdMsg="The login id is missing.";
	} else if ($getId==VUSER_GUEST) {
		$getpwdMsg="Invalid login account.";
	} else {
		$userInfo=array();
		$query="LOWER(login)= '".addslashes(strtolower($getId))."' AND brand_id = '".$GLOBALS['BRAND_ID']."'";
		$getpwdMsg=VObject::Select(TB_USER, $query, $userInfo);
		
		if ($getpwdMsg!='') {
//			$getpwdMsg="Error= ".$getpwdMsg;
			$getpwdMsg="Error encountered in processing a database command.";
		} else if  (!isset($userInfo['login'])) {
			$getpwdMsg="The login id you provided does not match our records.";
		}
		
		if ($getpwdMsg=='' && $userInfo['active']!='Y') {
			$getpwdMsg='Your account is not active. Please contact your service provider.';					
		}		
		
		if ($getpwdMsg=='') {		
			$user=new VUser($userInfo['id']);
			$mailInfo=array();
			$getpwdMsg=VMailTemplate::GetMailTemplate($gBrandInfo['id'], 'MT_SEND_PWD', $mailInfo);				
		}
		
		if ($getpwdMsg=='' && !isset($mailInfo['id'])) {
			$getpwdMsg="Email template not found";							
		}
		
		if ($getpwdMsg=='') {
			$from=$gBrandInfo['from_email'];
			$fromName=$gBrandInfo['from_name'];
			$subject=$gText[$mailInfo['subject']];
			$body=VMailTemplate::GetBody($mailInfo, $userInfo, $gBrandInfo, $gText);
			$toName=$user->GetFullName($userInfo);
			$to=$userInfo['email'];
			if ($to=='')
				$to=$userInfo['login'];
				
			if (!valid_email($to)) {
				$getpwdMsg="Your email address is not defined or invalid";							
			} else {
				$err=VMailTemplate::Send($fromName, $from, $toName, $to, $subject, $body,
						'', '', "", false, null, $gBrandInfo);
				
				if ($err!='')
					$getpwdMsg=$err;
				else
					$getpwdMsg=$gText['M_PASSWORD_SENT'];
			}
		}	
	}

}
ob_start();
@include_once($GLOBALS['LOCALE_FILE']);
ob_end_clean();
require_once("includes/common_text.php");

//$thisPage=$_SERVER['SCRIPT_NAME'];
//$thisPage=$GLOBALS['BRAND_URL'];
$GLOBALS['TAB']='';

 // will this page have a right hand side navigation?
$GLOBALS['SIDE_NAV'] = 'on';

 // if side nave on, what is the path and filename to the include?
if($GLOBALS['SIDE_NAV'] == 'on') { $GLOBAL['SIDE_INCLUDE'] = 'includes/home_right.php'; }

$GLOBALS['PAGE_TITLE']=$GLOBALS['TAB'];


?>
<?php 
require_once('includes/header.php');
require_once('includes/content-top.php');


//$signinPage="signin.php?brand=".$GLOBALS['BRAND_NAME']."&brandUrl=".$GLOBALS['BRAND_URL'];
$signinPage="signin.php";
if (SID!='')
	$signinPage.="?".SID;

$signinPage=$_SERVER['PHP_SELF'];

?>

<div class="heading1">
<?php 
	if (strpos($GLOBALS['THEME'], 'broadsoft')===false)
		//echo _Text("Members or Moderators");
		echo _Text("Meeting Moderator");
?>
</div>
<div class="login">
<form target="<?php echo $GLOBALS['TARGET']?>" action="<?php echo $signinPage?>" method="POST" accept-charset="UTF-8"  name="signin_form">
<input type="hidden" name="ret" value="<?php echo $retPage?>">
<input type="hidden" name="brand" value="<?php echo $GLOBALS['BRAND_NAME']?>">
<input type="hidden" name="brandUrl" value="<?php echo $GLOBALS['BRAND_URL']?>">

<table>	

    <tr id='login_id'>
    <td width="80px"><?php echo _Text("Login")?>:</td>
    <td colspan="2"><input type="text" name="login_id" value="<?php echo htmlspecialchars($login)?>" size="25" autocorrect="off" autocapitalize="off"></td>
    </tr>

    <tr id='login_pass'>
    <td><?php echo _Text("Password")?>:</td>
    <td colspan="2"><input type="password" name="password" value="<?php echo htmlspecialchars($password)?>" size="25"></td>
    </tr>

	<tr valign="top">
		<td>&nbsp;</td>
		<td colspan="2"><input type="submit" name="signin" value='<?php echo _Text("Sign In")?>'></td>
	</tr>
	
    <tr>
		<td colspan="3"><div class='error'><?php echo $loginMsg?></div></td>
    </tr>
<?php
/*
    <tr>
    <td colspan="3"><div id="cookieDisabled" class="inform">Your browser must accept cookies from this site.</div></td>
    </tr>
*/
?>	
	</table>
<?php
/* This check is not necessary and does not work in Safari (always return false for some reason)
    <script type="text/javascript">
<!--
		var tmpcookie = new Date();
		chkcookie = (tmpcookie.getTime() + '');
		document.cookie = "chkcookie=" + chkcookie + "; path=/";
		var cookieEnabled=(document.cookie && (document.cookie.indexOf(chkcookie) >=0))? true : false;
		if (cookieEnabled) {
			if(document.getElementById('cookieDisabled')) document.getElementById('cookieDisabled').style.display = 'none';
		}

//-->
    </script>
*/
?>
  

</form>
</div>

<br>

<div id="forgotpwd">
<form method="POST" action="<?php echo $signinPage?>" name="sendpwd_form" accept-charset="UTF-8">
<?php

if (strpos($GLOBALS['THEME'], 'broadsoft')!==false) {
	echo _Text("Forgot password? Enter email address:");
	echo "<br>\n";	
} else {
	echo _Text("Forgot password?");
	echo "<br>\n";
	echo _Text("Enter your email address and we'll send it to you.");
	echo "<br>\n";
	echo _Text("Email address");
	echo ":&nbsp;";
}
?>
	<input type="text" name="sendpwd_id" size="25" autocorrect="off" autocapitalize="off">
	<input type="submit" value="<?php echo _Text("Submit")?>" name="sendpwd">
</form>
<div id="getpwderror"><?php echo $getpwdMsg?></div>
</div>
<br>


<?php
include_once('includes/content-bottom.php'); 

include_once('includes/footer.php'); 
?>
