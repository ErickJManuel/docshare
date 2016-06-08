<?php
/**
 * Persony Web Conferencing 2.0
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2008 Persony, Inc.
 * @version     2.0
 * 
 */

require_once("dbobjects/vuser.php");
require_once("dbobjects/vlicense.php");
require_once("dbobjects/vprovider.php");
require_once("dbobjects/vlicense.php");
require_once("dbobjects/vlicensekey.php");

$licenses=array();
$ports=array();
$licenseType='N';

//$offerings=explode(',', $gBrandInfo['offerings']);
//$trialLicId=$gBrandInfo['trial_license_id'];

//$licenses=array();

/*
if ($trialLicId>0) {
	$query.="id='$trialLicId'";
	// first license is the trial license
	$counts[]=-1;	//unlimited trials
//	$licenses[]=$trialLicId;
}
*/

$provider_id=$gBrandInfo['provider_id'];
$licenseCounts=array();
// $providerAccId='';
$licenseType='';

if ($provider_id>0) {
	$provider=new VProvider($provider_id);
	$providerInfo=array();
	
	if ($provider->Get($providerInfo)!=ERR_NONE) {
		ShowError($provider->GetErrorMsg());
		return;
	}
	VProvider::ParseLicenseKey($providerInfo, $licenseType, $licenseCounts, $recording);
/*	
	$providerAccId=$providerInfo['account_id'];
	//show only the first 8 digits
	$len=strlen($providerAccId);
	$providerAccId=substr($providerAccId, 0, 8);
	for ($i=5; $i<$len; $i++)
		$providerAccId.="x";
*/
/*	
	$license=$providerInfo['license'];
	$items=explode(";", $license);
	
	if (count($items)<3) {
		ShowError("Invalid license");
		return;
	} else {
		$licstr=$items[0].";".$items[1];
		$key=str_replace("\n", "", $items[2]); 
		$key=str_replace("\r", "", $key); 
		if (VLicense::EncryptLicense($licstr, $providerInfo['login'])!=$key) {
			ShowError("Invalid license");
			return;
		}
	}

	
	$licenseType=$items[0];
	if ($licenseType=='N') {	// named users
		$licenseStr='';
		if (isset($items[1]))
			$licenseStr=$items[1];
	
		$listItems=explode(',', $licenseStr);
		foreach ($listItems as $v) {
			$subItems=explode(':', $v);
			
			$subItemCount=count($subItems);
			if ($subItemCount==2)
				$licenses[$subItems[0]]=$subItems[1];
			elseif ($subItemCount==1)
				$licenses[$subItems[0]]=0;
		}
	} else if ($licenseType=='P') {	// concurrent ports
		$portStr='';
		if (isset($items[1]))
			$portStr=$items[1];
	
		$listItems=explode(',', $portStr);
		foreach ($listItems as $v) {
			$subItems=explode(':', $v);
			
			$subItemCount=count($subItems);
			if ($subItemCount==2)
				$ports[$subItems[0]]=$subItems[1];
			elseif ($subItemCount==1)
				$ports[$subItems[0]]=0;
		}
		
	} else if ($licenseType=='U') {
		
	}
*/
}

$licenseTypeStr='';
if ($licenseType=='N')
	$licenseTypeStr="Named User";
else if ($licenseType=='P') {
	$licenseTypeStr="Concurrent Port";
	if (isset($licenseCounts['PTS'])) {
		VObject::Find(TB_LICENSE, 'code', 'PTS', $portInfo);
		$portName=$portInfo['name'];
		$licenseTypeStr.=" &nbsp;&nbsp;&nbsp;".$portName.": ".$licenseCounts['PTS'];
	} elseif (isset($licenseCounts['PTV'])) {
		VObject::Find(TB_LICENSE, 'code', 'PTV', $portInfo);
		$portName=$portInfo['name'];
		$licenseTypeStr.=" &nbsp;&nbsp;&nbsp;".$portName.": ".$licenseCounts['PTV'];
	}
} else if ($licenseType=='U')
	$licenseTypeStr="Unlimited";
else if ($licenseType=='S')
// this is the same as 'Named User' but is limited to one site only and with some admin functions such as Hosting and Groups page disabled
// this is for Plesk APS provisioning to create a site on a user's server
	$licenseTypeStr="User Site";
else
	$licenseTypeStr="Trial";


$thisPage=$_SERVER['PHP_SELF'];
$retPage=$thisPage."?page=".PG_ADMIN_ACCOUNTS."&brand=".$GLOBALS['BRAND_NAME']."&brandUrl=".$GLOBALS['BRAND_URL'];
if (SID!='')
	$retPage.="&".SID;
$retPage=VWebServer::EncodeDelimiter1($retPage);
$postLicenseUrl=VM_API."?cmd=SET_BRAND&return=$retPage";
if (SID!='')
	$postLicenseUrl.="&".SID;
	
	
$postProviderUrl=VM_API."?cmd=SET_BRAND&return=$retPage";
if (SID!='')
	$postProviderUrl.="&".SID;

// find other brands that use this provider_id
// and create a query string to find all these brands
$othersQuery='';
if ($provider_id!=0) {
	$query="provider_id='".$provider_id."'";
	$query.=" AND (id<>'".$GLOBALS['BRAND_ID']."')";
	$errMsg=VObject::SelectAll(TB_BRAND, $query, $result);
	if ($errMsg!='') {
		ShowError($errMsg);
		return;
	}
	
	$num_rows = mysql_num_rows($result);
	while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
		if ($othersQuery!='')
			$othersQuery.=" OR ";
		$othersQuery.="brand_id ='".$row['id']."'";
	}

}

$codeText=_Text("Code"); //_Comment: License code
$videoText=_Text("Video"); //_Comment: Video conference
// disk quota is not enfored so don't show it
//$diskText=_Text("Disk"); //_Comment: Disk quota for storing contents

// If this is an SBM or APS site, allows the site admin to install a license key 
//if ($gBrandInfo['enable_licensekey']=='SITE' || $gBrandInfo['enable_licensekey']=='ALL') {
if ($licenseType=='S' || $gBrandInfo['enable_licensekey']=='SITE' || $gBrandInfo['enable_licensekey']=='ALL') {
	$text1=_Text("License key file");
	
	$backPage=$GLOBALS['BRAND_URL']."?page=".PG_ADMIN_ACCOUNTS;
	if (SID!='')
		$backPage.="&".SID;
	$backPage=VWebServer::EncodeDelimiter2($backPage);
	
	$msg="Your license key has been applied to the site. To change your account type, please edit your member profile in the Administration/Members page."; 
	
	$retPage="index.php?page=".PG_HOME_INFORM."&ret=".$backPage."&message=".rawurlencode($msg)."&brand=".$GLOBALS['BRAND_NAME']."&brandUrl=".$GLOBALS['BRAND_URL'];
	if (SID!='')
		$retPage.="&".SID;
	
	$retPage=VWebServer::EncodeDelimiter1($retPage);
	
	$postUrl=VM_API."?cmd=INSTALL_LICENSE";
	$uploadText=_Text("Install");

	// if no license key has been installed or I am the root user
	if ($providerInfo['licensekey_id']==0 || GetSessionValue("root_user")=='true') {
		print <<<END
<br>
Install a license key file for the site:
<form enctype="multipart/form-data" method="POST" action="$postUrl" name="license_form">
<div><span class='heading2'>$text1:</span>
<input type='hidden' name='return' value='$retPage'> 
<input type='file' name='license_file' size='40'>
<input type='submit' name='submit' value='$uploadText'></div>
</form>

END;
	}
	if ($providerInfo['licensekey_id']!=0) {
		$lkey=new VLicenseKey($providerInfo['licensekey_id']);
		$valid=$lkey->VerifyKey($keyInfo, $keyErrMsg);
		
		if (!$valid) {
			print <<<END
<div class='error'>$keyErrMsg</div>
END;
		}			
		
		if (isset($keyInfo['domain']) && $keyInfo['domain']!='') {
			$val=$keyInfo['domain'];
			print <<<END
<div><b>License domain</b>: $val</div>
END;
		}
		if (isset($keyInfo['expiry_date']) && $keyInfo['expiry_date']>'1961-01-01') {
			$val=$keyInfo['expiry_date'];
			print <<<END
<div><b>License renewal date</b>: $val</div>
END;
		}
		if (isset($keyInfo['reg_name']) && $keyInfo['reg_name']!='') {
			$val=htmlspecialchars($keyInfo['reg_name']);
			print <<<END
<div><b>License registration name</b>: $val</div>
END;
		}
		
	}
	
}
?>


<div class='list_tools'>
<span>License Model:</span>
<span><?php echo $licenseTypeStr?></span>
</div>

<!--Select the account types that you want to offer to your memebers: -->

<form method="POST" action="<?php echo $postLicenseUrl?>" name="license_form">
<input type='hidden' name='brand' value="<?php echo $GLOBALS['BRAND_NAME']?>">
<table cellspacing="0" class="meeting_list" >

<tr>
<!--    <th class="pipe">&nbsp;</th> -->
    <th class="pipe"><?php echo $gText['M_ACCOUNT']?></th>
    <th class="pipe"><?php echo $codeText?></th>
    <th class="pipe"><?php echo $gText['M_ATTENDEES']?></th>
    <th class="pipe"><?php echo $gText['M_LENGTH']?></th>
    <th class="pipe"><?php echo $videoText?></th>
<!--    <th class="pipe"><?php echo $diskText?></th>	-->
    <th class="pipe"><?php echo $gText['M_TOTAL']?></th>
    <th class="pipe"><?php echo $gText['M_ISSUED']?></th>
    <th class="pipe"><?php echo $gText['M_OTHERS']?></th> 
    <th class="tr"><?php echo $gText['M_AVAILABLE']?></th>
</tr>


<?php

$offerings=explode(',', $gBrandInfo['offerings']);

$errMsg=VObject::SelectAll(TB_LICENSE, "1", $result);
if ($errMsg!='') {
	ShowError($errMsg);
	return;
}

$num_rows = mysql_num_rows($result);
$i=0;
while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
	
	$licCode=$row['code'];
	if (!in_array($licCode, $offerings))
		continue;
	
	$licId=$row['id'];
	if ((defined('ENABLE_LICENSE_KEY') && constant('ENABLE_LICENSE_KEY')=='1')) {
		$query="(brand_id ='".$GLOBALS['BRAND_ID']."') AND (license_id='$licId') AND (licensekey_id='0')";
	} else {
		$query="(brand_id ='".$GLOBALS['BRAND_ID']."') AND (license_id='$licId')";
	}
	VObject::Count(TB_USER, $query, $issued);
	
//	$query="(brand_id ='".$GLOBALS['BRAND_ID']."') AND (license_id='$licId') AND (access_id='0')";
//	VObject::Count(TB_USER, $query, $released);
	
	$others=0;
	if ($othersQuery!='') {
		if ((defined('ENABLE_LICENSE_KEY') && constant('ENABLE_LICENSE_KEY')=='1')) {
			$query="($othersQuery) AND (license_id='$licId') AND (licensekey_id='0')";
		} else {
			$query="($othersQuery) AND (license_id='$licId')";
		}
		VObject::Count(TB_USER, $query, $others);
	}

	$othersStr=(string)$others;
	
	$issuedLink=$GLOBALS['BRAND_URL']."?page=ADMIN_USERS&license_id=$licId";
	if (SID!='')
		$issuedLink.="&".SID;
		
	if ($issued>0)
		$issuedStr="<a target=${GLOBALS['TARGET']} href='$issuedLink'>$issued</a>";
	else
		$issuedStr=(string)$issued;
	
/*	
	$releasedLink='';	
		
	if ($released>0)
		$releasedStr="<a target=${GLOBALS['TARGET']} href='$releasedLink'>$released</a>";
	else
		$releasedStr=(string)$released;
*/	
/*
	if (in_array($licCode, $offerings))
		$checked="checked";
	else
		$checked='';
		
	$licCheck="<input $checked type='checkbox' name='license[$licCode]' value='1'>";
*/	
	$total=0;
	if (isset($licenseCounts[$licCode]))
		$total=$licenseCounts[$licCode];
/*
	if ($row['trial']=='Y')
		$total=-1; // unlimited trial
	elseif ($licenseType=='P' || $licenseType=='U') // port or unlimited model
		$total=-1;	// no limit
	elseif (isset($licenses[$licCode])) // a limit is set for this license
		$total=(int)$licenses[$licCode];
*/
	
	if ($total>=0) {
		$available=$total-(int)$issued-(int)$others;
		if ($available<=0) {
			$availStr="<span class='alert'>$available</span>";
		} else {
			$availStr=$available;
		}
	} else {
		$total=$gText['M_NO_LIMIT'];
		$availStr=$gText['M_NO_LIMIT'];
	}
	$i++;
	
	$licName=$row['name'];
	$licCode=$row['code'];
/*
	if ($row['expiration']=='0')
		$expStr=$gText['M_NONE'];
	else
		$expStr=$row['expiration']." days";
*/	
	if ($row['max_att']=='0')
		$attStr=$gText['M_NO_LIMIT'];
	else
		$attStr=$row['max_att'];
	
	$video=$row['video_conf'];
/*	
	if (strpos($row['btn_disabled'], "record")===false)
		$rec='Y';
	else
		$rec='N';
*/		
	if ($row['meeting_length']=='0')
		$length=$gText['M_NO_LIMIT'];
	else
		$length=$row['meeting_length'];
	
	if ($row['disk_quota']=='0')
		$disk=$gText['M_NO_LIMIT'];
	else
		$disk=$row['disk_quota'];
	
	print <<<END
<tr>
	<td class="u_item u_item_b">$licName</td>
	<td class="u_item_c">$licCode</td>
	<td class="u_item_c">$attStr</td>
	<td class="u_item_c">$length</td>
	<td class="u_item_c">$video</td>
<!--	<td class="u_item_c">$disk</td>	-->
	<td class="u_item_c">$total</td>
	<td class="u_item_c">$issuedStr</td>
	<td class="u_item_c">$othersStr</td>
	<td class="u_item_c">$availStr</td>
</tr>
END;
	
}

?>

</table>
<!--
<div class="list_tools">
	<input type="submit" name="submitLicense" value="<?php echo $gText['M_SUBMIT']?>">&nbsp;
	<input type="reset" name="reset" value="<?php echo $gText['M_RESET']?>">&nbsp;
</div>
-->
</form>
<p>
<div class="m_caption">*<b><?php echo $gText['M_ATTENDEES']?></b>: Max. number of attendees in a meeting.</div>
<div class="m_caption">*<b><?php echo $gText['M_LENGTH']?></b>: Meeting length in minutes.</div>
<div class="m_caption">*<b><?php echo $videoText?></b>: A video conferencing server must be set up under Hosting.</div>
<!-- <div class="m_caption">*<b><?php echo $diskText?></b>: Disk quota in MB for storing meetings, library contents, and recordings.</div>	-->
<div class="m_caption">*<b><?php echo $gText['M_TOTAL']?></b>: Total number of licenses under this provider account. The provider account may be shared by multiple sites.</div>
<div class="m_caption">*<b><?php echo $gText['M_ISSUED']?></b>: Number of licenses issued by this site.</div>
<div class="m_caption">*<b><?php echo $gText['M_OTHERS']?></b>: Number of licenses issued by other sites under this provider account.</div>
<div class="m_caption">*<b><?php echo $gText['M_AVAILABLE']?></b>: Number of licenses available to issue under this provider account.</div>

