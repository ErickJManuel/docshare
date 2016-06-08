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


require_once("includes/meetings_common.php");
require_once("dbobjects/vmeeting.php");
require_once("dbobjects/vregistration.php");
require_once("dbobjects/vregform.php");
require_once("dbobjects/vuser.php");

if (GetArg('meeting', $accessId)) {

	$meetingInfo=array();
	$errMsg=VObject::Find(TB_MEETING, 'access_id', $accessId, $meetingInfo);
	if ($errMsg!='') {
		ShowError($errMsg);
		return;
	}
	if (!isset($meetingInfo['id'])) {
		ShowError("Meeting id not found.");
		return;
	}

	$meeting=new VMeeting($meetingInfo['id']);
	if ($meeting->Get($meetingInfo)!=ERR_NONE) {
		ShowError($meeting->GetErrorMsg());
		return;
	}
} else {
	ShowError("The 'meeting' parameter is not set");
	return;	
}


$formId=$meetingInfo['regform_id'];
$formInfo=array();
if ($formId=='0') {
	if (($errMsg=VRegForm::GetDefault($formInfo))!='') {
		ShowError($errMsg);
		return;
	}	
} else {
	$form=new VRegForm($formId);
	if ($form->Get($formInfo)!=ERR_NONE) {
		ShowError($form->GetErrorMsg());
		return;
	}
}

if (!isset($formInfo['id'])) {
	ShowError("Registration form is not found");
	return;
}


/*
if ($tz!='') {
	SetSessionValue('time_zone', $tz);

} else {
	$tz=GetSessionValue("time_zone");
}
GetSessionTimeZone($tzName, $dtz);
*/

$host=new VUser($meetingInfo['host_id']);
$host->Get($hostInfo);
$hostTz=$hostInfo['time_zone'];

if ($meetingInfo['scheduled']=='Y') {

	$dtime=$meetingInfo['date_time'];
	
	GetArg('time_zone', $tz);
	if ($tz[0]==' ')
		$tz[0]='+';
		
	$stz='';
	if ($tz!='')
		$stz=$tz;
	elseif ($hostTz!='')
		$stz=$hostTz;
	elseif  (isset($gBrandInfo['time_zone']))
		$stz=$gBrandInfo['time_zone'];
		

//	GetTimeZoneName($stz, $tzName, $dtz);
	GetTimeZoneByDate($stz, $dtime, $tzName, $dtz);
	
	$thisPage=$_SERVER['PHP_SELF'];
	$tzPage=$thisPage."?page=".$GLOBALS['SUB_PAGE']."&meeting=".$meetingInfo['access_id'];
	$timezones=GetTimeZones($stz, "return ChangeTimeZone('time_zone', '$tzPage');");

	VObject::ConvertTZ($dtime, 'SYSTEM', $dtz, $tzTime);
	$meetingTimeStr=$dateStr='';
	if ($tzTime!='') {
		list($meetingDateStr, $time24Str)=explode(" ", $tzTime);
		list($year, $mon, $day)=explode("-", $meetingDateStr);
		list($meetHour, $meetMin, $meetSec)=explode(":", $time24Str);
		$theDate=mktime($meetHour, $meetMin, $meetSec, $mon, $day, $year);
		$meetingTimeStr=H24ToH12($meetHour, $meetMin);
		$dateStr=date('l F d, Y', $theDate);
	}

}

$reqFields=explode(",", $formInfo['required_fields']);
$meetingPage=$GLOBALS['BRAND_URL']."?meeting=".$meetingInfo['access_id'];

if (isset($formInfo['auto_reply']) && $formInfo['auto_reply']=='N') {
	$msg=_Text("Your registration has been submitted.");
	$thisPage=$_SERVER['PHP_SELF'];
	$retPage=$thisPage."?page=".PG_HOME_INFORM."&message=".rawurlencode($msg)."&brand=".$GLOBALS['BRAND_NAME']."&brandUrl=".$GLOBALS['BRAND_URL'];
	$retPage.="&ret=".$meetingPage;
	if (SID!='')
		$retPage.="&".SID;	
	
} else {
//	$msg=_Text("Your registration has been submitted. You shall receive your registration information via email.");

	$backPage=$_SERVER['PHP_SELF']."?page=".PG_HOME_JOIN."&meeting=".$meetingInfo['access_id'];
	$backPage=VWebServer::EncodeDelimiter2($backPage);
	$msg=_Text("Your registration has been submitted. Please look for log-on information in your email to join the meeting later.<br><br>OR,");
	$retLabel=_Text("Click here to join the meeting now");
	
	$retPage="index.php?page=".PG_HOME_INFORM."&ret=".$backPage."&retLabel=$retLabel&message=".rawurlencode($msg)."&brand=".$GLOBALS['BRAND_NAME']."&brandUrl=".$GLOBALS['BRAND_URL'];
	
}
	


$retPage=VWebServer::EncodeDelimiter1($retPage);
$postUrl=VM_API."?cmd=REGISTER_MEETING";
if (SID!='')
	$postUrl.="&".SID;

$cancelUrl=$meetingPage;
if (SID!='')
	$cancelUrl.="&".SID;

$meetingTitle=htmlspecialchars($meetingInfo['title']);
$description=htmlspecialchars($meetingInfo['description']);
$description=str_replace("\n", "<br>", $description);

$format=$gText['M_ENTER_VAL'];


$logoFile='';
if ($hostInfo['logo_id']!='0') {
	$logo=new VImage($hostInfo['logo_id']);
	if ($logo->GetValue('file_name', $logoFile)!=ERR_NONE) {
		ShowError ($logo->GetErrorMsg());
	} else {
		$logoFile=VImage::GetFileUrl($logoFile);
	}
}

if ($logoFile!='') {
print <<<END
<script type="text/javascript">
<!--
	document.getElementById('logo_pict').setAttribute("src", "$logoFile");
	document.getElementById('logo_link').setAttribute("href", "javascript:void(0)");
//-->
</script>
END;
}

?>

<script type="text/javascript">
<!--

function CheckRegisterForm(theForm) {

<?php
foreach ($reqFields as $key) {
	$field=str_replace("key", "field", $key);
	$keyText=FormKeyToText($formInfo[$key]);
	$str=sprintf($format, $keyText);
print <<<END
	if (theForm.$field.value=='')
	{
		alert("$str");
		theForm.$field.focus();
		return (false);
	}

END;
}
?>
	if (theForm.security_answer.value=='') {
		alert(" <?php echo sprintf($format, 'Security question')?> ");
		theForm.security_answer.focus();
		return (false);
	}
/*
	var ok=confirm(" <?php echo _Text('Please confirm the email address is correct')?>:\n "+theForm.email.value);
	if (!ok)
		return false;
*/
	return (true);
}

function ShowOtherState(theForm) {

	if (theForm.options[theForm.selectedIndex].value=='Others') {
		document.getElementById('Others').style.display='inline';
	} else {
		document.getElementById('Others').style.display='none';
	}
}

//-->
</script>

<?php

// if this page is shown without the site tabs
if ($GLOBALS['SIDE_NAV']=='off') {
	print <<<END
<div style="float: left; width: 500px;">
END;
	
}
?>


<div class="heading1"><?php echo $meetingTitle?></div>
<?php
if ($meetingInfo['scheduled']=='Y' && $meetingInfo['status']!='REC') {
print <<<END
	<div><b>$dateStr $meetingTimeStr</b> $timezones</div><br>
END;
}


?>
<div class='meeting_desc'>
<?php echo $description?>
</div>

<?php
$disabled='';
if ($meetingInfo['close_register']=='Y' || $meetingInfo['login_type']!='REGIS') {
	if ($meetingInfo['login_type']!='REGIS')
		$text=_Text("Registration is not enabled.");
	else
		$text=_Text("The registration is closed.");

print <<<END
<div class='inform'>$text</div>

END;
	$disabled='disabled';
}

if ($meetingInfo['login_type']=='REGIS')
	$text=_Text("Registered users only").":";
else
	$text="";
$target=$GLOBALS['TARGET'];
$link=$GLOBALS['BRAND_URL']."?page=".PG_HOME_JOIN."&redirect=1&meeting=".$meetingInfo['access_id'];
if ($meetingInfo['status']=='REC')
	$actText=$gText['M_CLICK_PLAY'];
else
	$actText=$gText['M_CLICK_JOIN'];

print <<<END
<div class="inform">$text <a target='$target' href='$link'><img src='$startIcon'>&nbsp;$actText</a></div>
END;
?>


<?php
if ($GLOBALS['SIDE_NAV']=='off') {
	$userPict='';
	if ($hostInfo['pict_id']>0) {
		$pict=new VImage($hostInfo['pict_id']);
		if ($pict->GetValue('file_name', $pictFile)!=ERR_NONE) {
			ShowError ($pict->GetErrorMsg());
		} else {
			$userPict=VImage::GetFileUrl($pictFile);
		}
	}
	
	if ($userPict!='') {
		
		$sizeX=PICT_SIZE;
		$sizeY=PICT_SIZE;
		print <<<END
</div>
<div style="float: right; padding-top: 20px;">
<img  width:'$sizeX' height='$sizeY' src="$userPict">	
</div>
<div style="clear:both"></div>
END;
	} else {
		print <<<END

</div>
<div style="clear:both"></div>
END;
	}
	
}
?>



<form onSubmit="return CheckRegisterForm(this)" method="POST" action="<?php echo $postUrl?>" name="registration_form">
<input type="hidden" name="id" value="<?php echo $meetingInfo['id']?>">
<input type="hidden" name="return" value="<?php echo $retPage?>">
<input type="hidden" name="brand" value="<?php echo $GLOBALS['BRAND_NAME']?>">
<input type="hidden" name="brandUrl" value="<?php echo $GLOBALS['BRAND_URL']?>">

<div class='heading3'><?php echo $gText['M_REGISTER_FOR']." \"".$meetingTitle."\"";?></div>
<div class="meeting_frame_top" style="">
<div class="meeting_frame_bot" >

<table class="meeting_detail" style="font-size: 90%; margin-top: 0px; padding-top: 0px;">

<?php
/*
<tr>
	<td class="m_key">*<?php echo $gText['M_EMAIL']?>:</td>
	<td colspan="3" class="m_val">
	<input type="text" name="email" size="30" value="">
	<span class='m_caption'>*<?php echo $gText['M_REQUIRED']?></span>
	</td>
</tr>

<tr>
	<td class="m_key">*<?php echo $gText['M_FULL_NAME']?>:</td>
	<td colspan="3" class="m_val">
	<input type="text" name="name" size="30" value="">
	</td>
</tr>
*/
?>

<?php

$max=VRegForm::$maxFields;
for ($i=1; $i<=$max; $i++) {
	$key="key_".$i;
	$field="field_".$i;
	$fieldKey='';
	$req='';
	if ($i==1)
		$reqText="<div class='m_caption'>*${gText['M_REQUIRED']}</div>\n";
	else
		$reqText='';
	if (isset($formInfo[$key]) && $formInfo[$key]!='') {
		
		if ($formInfo[$key]!='') {
			$keyItems=explode("=", $formInfo[$key]);
			$fieldKey=FormKeyToText($keyItems[0]);
			if ($keyItems[0]=='[STATE]') {
				$states=GetUSStates();
				$fieldText="<select $disabled name=\"$field\">";
				$selectText=_Text("Select");
				$fieldText.="<option value=''>$selectText</option>";
				foreach ($states as $aItem) {
					$fieldText.="<option value=\"$aItem\">$aItem</option>";
				}
				$fieldText.="<option value=''>---</option>";
				$fieldText.="<option value='Others'>Others</option>";
				$fieldText.="</select>";

			} elseif (isset($keyItems[1])) {
				$valItems=explode("|", $keyItems[1]);
				$fieldText="<select $disabled name=\"$field\">";
				$selectText=_Text("Select");
				$fieldText.="<option value=''>$selectText</option>";
				foreach ($valItems as $aItem) {
					$fieldText.="<option value=\"$aItem\">$aItem</option>";
				}
				$fieldText.="</select>";
			} else {
				$fieldText="<input $disabled type=\"text\" name=\"$field\" size=\"40\">";
			}
		}
			
		if (in_array($key, $reqFields))
			$req='*';
	} else {
		continue;
	}
	
	if ($fieldKey!='') {
print <<<END
<tr>
	<td class="m_key">$req $fieldKey:</td>
	<td colspan="3" class="m_val">
	$fieldText $reqText
	</td>
</tr>
END;
	}
	
}

//require_once("includes/ClassMathGuard.php");
//$securityQ=MathGuard::returnQuestion();
$securityQ=getSecurityQuestion();

?>
<tr>
	<td class="m_key">&nbsp;</td>
	<td colspan="3"><?php echo $securityQ?> *</td>
</tr>
<tr>
	<td class="m_key">&nbsp;</td>
	<td colspan="3" class="m_val"><input <?php echo $disabled?> type="submit" name="submit" value="<?php echo $gText['M_SUBMIT']?>">&nbsp;
	<input type="reset" name="reset" value="<?php echo $gText['M_RESET']?>">&nbsp;
	<input type="button" name="cancel" value="<?php echo $gText['M_CANCEL']?>" onclick="return GoToUrl('<?php echo $cancelUrl?>')">
	</td>
</tr>

</table>
</div>
</div>
</form>