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

require_once("dbobjects/vwebserver.php");
require_once("dbobjects/vteleserver.php");
require_once("includes/meetings_common.php");

$hasRegist=GetSessionValue('has_regist');

$downIcon="themes/down-arrow.png";
$rightIcon="themes/right-arrow.png";

$moreOptions="<img src='$rightIcon'>"._Text("More options");
$hideOptions="<img src='$downIcon'>"._Text("Hide options");

GetSessionTimeZone($tzName, $tz);
$stz=GetSessionValue('time_zone');	
$timezones=GetTimeZones($stz);

$postUrl='';
$scheduled='N';
$loginType='NAME';
$password='';
$public='N';
$publicComment='Y';
$title='';
$description='';
$phoneNum='';
$phoneMCode='';
$phonePCode='';
$teleConf='N';
$closeRegister='N';
$meetYear='';
$meetMonth='';
$meetDay='';
$meetHour='';
$meetMin='';
$meetSec='';
$sendReport='Y';

if (isset($meetingInfo) && $meetingInfo['status']=='REC')
	$retPage=$GLOBALS['BRAND_URL']."?page=".PG_MEETINGS_RECORDINGS;
else
	$retPage=$GLOBALS['BRAND_URL']."?page=".PG_MEETINGS_LIST;
if (SID!='')
	$retPage.="&".SID;
	
$cancelUrl=$retPage;

$retPage=VWebServer::EncodeDelimiter1($retPage);


// init date and time with the current date and time
$dtStr=date('Y-m-d H:i:s');
VObject::ConvertTZ($dtStr, 'SYSTEM', $tz, $localDtStr);
//echo($dtStr."=".$localDtStr);
if ($localDtStr!='') {
	list($dateStr, $timeStr)=explode(" ", $localDtStr);

	list($meetYear, $meetMonth, $meetDay)=explode("-", $dateStr, 3);
	list($meetHour, $meetMin, $meetSec)=explode(":", $timeStr, 3);
}

$meetDuration='01:00';

$thisPage=$_SERVER['PHP_SELF'];

$confNum=$confMCode=$confPCode=$confNum2='';
$memberId=GetSessionValue('member_id');
$memberInfo=array();
$member=new VUser($memberId);
if ($member->Get($memberInfo)!=ERR_NONE) {
	ShowError("Couldn't get member info. Error=".$member->GetErrorMsg());
	return;
}

$confNum=$memberInfo['conf_num'];
$confMCode=$memberInfo['conf_mcode'];
$confPCode=$memberInfo['conf_pcode'];
$confNum2=$memberInfo['conf_num2'];

// if the meeting has a phone number set, use that to initialize the "Use my own teleconfence number"
// unless that number is the same as the pre-assigned user number, in which case init with the user own number
if (isset($meetingInfo) && $meetingInfo['tele_num']!='' &&
	($meetingInfo['tele_num']!=$confNum && $meetingInfo['tele_mcode']!=$confMCode)
	) 
{
	$phoneNum=$meetingInfo['tele_num'];
	$phoneMCode=$meetingInfo['tele_mcode'];
	$phonePCode=$meetingInfo['tele_pcode'];
	
} else {
	$phoneNum=$memberInfo['tele_num'];
	$phoneMCode=$memberInfo['tele_mcode'];
	$phonePCode=$memberInfo['tele_pcode'];
}

$group=new VGroup($memberInfo['group_id']);
$groupInfo=array();
$group->Get($groupInfo);
if (!isset($groupInfo['id'])) {
	ShowError("Group not found");
	return;
}

$canRecord='disabled';
$teleServerId=$groupInfo['teleserver_id'];
if ($teleServerId!='0') {
	$teleServer=new VTeleServer($teleServerId);
	$teleServer->Get($teleInfo);
	if (!isset($teleInfo['id'])) {
		ShowError("Teleconference server not found.");
		return;
	}
	
	if ($teleInfo['can_record']=='Y') {
		$canRecord='enabled';
	}
}
	

// add a meeting
if (!isset($meeting)) {
require_once("dbobjects/vuser.php");

//	$meeting=new VMeeting();
	$title=$gText['M_MY_MEETING'];
	$description="";
/*		
	$webServerId=VUser::GetWebServerId($memberInfo);
	if ($webServerId<=0) {
		ShowError("Web conference server is not set.");
	}
*/	
	$postUrl=VM_API."?cmd=ADD_MEETING&return=$retPage"."&brand=".$GLOBALS['BRAND_NAME']."&brandUrl=".$GLOBALS['BRAND_URL'];
	if (SID!='')
		$postUrl.="&".SID;

	
// edit an existing meeting
} else {
	
	if ($meetingInfo['host_id']!=$memberInfo['id']) {
		ShowError("Not authorized.");
		return;
	}

	$postUrl=VM_API."?cmd=SET_MEETING&return=$retPage"."&brand=".$GLOBALS['BRAND_NAME']."&brandUrl=".$GLOBALS['BRAND_URL'];
	if (SID!='')
		$postUrl.="&".SID;

	$previewIcon="themes/preview.gif";
	$viewUrl=$GLOBALS['BRAND_URL']."?meeting=".$meetingInfo['access_id'];
	if (SID!='')
		$viewUrl.="&".SID;

	$scheduled=$meetingInfo['scheduled'];
	//$duration=substr($meetingInfo['duration'], 0, 5);
	$meetDateStr='';
	$meetTimeStr='';
	if ($meetingInfo['scheduled']=='Y' || $meetingInfo['status']=='REC') {
		$dtime=$meetingInfo['date_time'];
		GetTimeZoneByDate($stz, $dtime, $tzName, $dtz);
		VObject::ConvertTZ($dtime, 'SYSTEM', $dtz, $meetDateTimeStr);

		$meetDuration=$meetingInfo['duration'];

		if ($meetDateTimeStr!='') {
			list($meetDateStr, $meetTimeStr)=explode(" ", $meetDateTimeStr);
			list($meetYear, $meetMonth, $meetDay)=explode("-", $meetDateStr, 3);
			list($meetHour, $meetMin, $meetSec)=explode(":", $meetTimeStr, 3);		
		}
	}
/*
	$server=$_SERVER['SERVER_NAME'];
	$scriptPath=$_SERVER['PHP_SELF'];
	//$url_arr=parase_url($path);
	$scriptName=basename($scriptPath);
	$path=str_replace($scriptName, '', $scriptPath);

	$proto="http://";
	$port=$_SERVER['SERVER_PORT'];
	if ($port==443) {
		$proto="https://";
		$port='';
	} else if ($port==80) {
		$port='';
	} else {
		$port=":".$port;
	}
	$meetingUrl=$proto.$server.$port."/".$path.'viewer.php?meeting='.$meetingInfo['access_id'];
*/
	if ($meeting->GetMeetingUrl($meetingUrl)!=ERR_NONE)
		ShowError($meeting->GetErrorMsg());
		
	$title=htmlspecialchars($meetingInfo['title']);
	$description=htmlspecialchars($meetingInfo['description']);
	$loginType=$meetingInfo['login_type'];
	$password=$meetingInfo['password'];
	$public=$meetingInfo['public'];
	$publicComment=$meetingInfo['public_comment'];
	$closeRegister=$meetingInfo['close_register'];
	$sendReport=$meetingInfo['send_report'];
	
	$teleConf=$meetingInfo['tele_conf'];
/*	
	// if the conf num is set for the meeting and it is different than the pre-assigned conf number for the member
	if ($meetingInfo['tele_num']!='' && $meetingInfo['tele_num']!=$confNum) {
		$phoneNum=$meetingInfo['tele_num'];
		$phoneMCode=$meetingInfo['tele_mcode'];
		$phonePCode=$meetingInfo['tele_pcode'];
	}
*/


}

if (isset($meetingInfo['regform_id']))
	$regFormId=$meetingInfo['regform_id'];
else
	$regFormId=0;
	
$query="`author_id` = '$memberId'";
$prepend="<option value=\"\">".$gText['M_DEFAULT']."</option>\n";

$regOpt=VObject::GetFormOptions(TB_REGFORM, $query, "regform_id", "name",$regFormId, $prepend);
$regHtml=$gText['M_REG_FORM'].": ".$regOpt;
$formPage=$thisPage."?page=".PG_MEETINGS_REGFORM."&brand=".$GLOBALS['BRAND_NAME']."&brandUrl=".$GLOBALS['BRAND_URL'];
$formPage.="&brandUrl=".$GLOBALS['BRAND_URL'];
if (SID!='')
	$formPage.="&".SID;
$addForm="<a href=\"$formPage\">${gText['M_ADD']}</a>";
$regHtml.="<span class=\"m_button_s\">".$addForm."</span>";

if (isset($meetingInfo['regform_id']) && $meetingInfo['regform_id']!=0) {
	$formPage.="&id=".$meetingInfo['regform_id'];
	$editForm="<a href=\"$formPage\">${gText['M_EDIT']}</a>";
	$regHtml.="<span class=\"m_button_s\">".$editForm."</span>";
}

$schedHtml=$gText['MD_DATE'].": ".GetMonths($meetMonth)." ".GetDays($meetDay)." ".GetYears($meetYear)."&nbsp;"
	.$gText['MD_TIME'].": ".GetHours($meetHour)." ".GetMinutes($meetMin)."&nbsp;"
	.$gText['MD_DURATION'].": ".GetDuration($meetDuration);
	


/*
$teleHtml=$gText['MD_PHONE_NUM']." <input type=\"text\" name=\"tele_num\" size=\"12\" value=\"$phoneNum\"> ".
	$gText['MD_PHONE_MCODE']." <input type=\"text\" name=\"tele_mcode\" size=\"8\" value=\"$phoneMCode\"> ".
	$gText['MD_PHONE_PCODE']." <input type=\"text\" name=\"tele_pcode\" size=\"8\" value=\"$phonePCode\"> ";

$teleHtml=str_replace("'", "\'", $teleHtml);
*/


function GetDuration($selected)
{
	// remove second if present
	if (strlen($selected)>5)
		$selected=substr($selected, 0, 5);
	$str= "<select name=\"duration\">";
	$durs=array("00:30", "01:00", "01:30", "02:00", "02:30", "03:00", "03:30", "04:00");

	$count=count($durs);
	for ($i=0; $i<$count; $i++) {
		$durStr=$durs[$i];		
		if ($selected!='' && $selected==$durStr)
			$str.="<option value=\"$durStr\" selected>$durStr</option>";
		else
			$str.="<option value=\"$durStr\">$durStr</option>";
	}
    $str.="</select>";
	return $str;
}

function GetMinutes($selected)
{
	$str="<select name=\"minute\">";
	for ($i=0; $i<4; $i++) {

		if ($i==0)
			$min="00";
		else
			$min=(string)($i*15);

		if ($min==$selected)
			$str.="<option value=\"$min\" selected>$min</option>";
		else
			$str.="<option value=\"$min\">$min</option>";
	}

    $str.="</select>";
	return $str;
}

function GetHours($selected)
{
	$str="<select name=\"hour\">";
	for ($i=0; $i<24; $i++) {

		$val=$i;
		if ($i==0)
			$hourStr="12 am";
		elseif  ($i==12)
			$hourStr="12 pm";
		else if ($i<12)
			$hourStr=$i." am";
		else
			$hourStr=($i-12)." pm";

		if ($val==$selected)
			$str.="<option value=\"$val\" selected>$hourStr</option>";
		else
			$str.="<option value=\"$val\">$hourStr</option>";
	}

    $str.="</select>";
	return $str;
}

function GetMonths($selected)
{
	$str="<select name=\"month\">";
	$months=array("Jan", "Feb", "Mar", "April", "May", "Jun", "July", "Aug", "Sep", "Oct", "Nov", "Dec");

	for ($i=1; $i<13; $i++) {

		$monStr=$months[$i-1];
			
		if ($i<10)
			$val='0'.(string)$i;
		else
			$val=(string)$i;
			
		if ($val==$selected)
			$str.="<option value=\"$val\" selected>$monStr</option>";
		else
			$str.="<option value=\"$val\">$monStr</option>";
	}
    $str.="</select>";
	return $str;
}
function GetYears($selected)
{
	$str="<select name=\"year\">";
	$year=date("Y");

	$count=5;
	for ($i=0; $i<$count; $i++) {

		$yearStr=$year+$i;

		if ($selected==$yearStr)
			$str.="<option value=\"$yearStr\" selected>$yearStr</option>";
		else
			$str.="<option value=\"$yearStr\">$yearStr</option>";
	}
   $str.="</select>";
	return $str;
}

function GetDays($selected)
{
	$str="<select name=\"day\">";
/*	if ($month==0 || $month==2 || $month==4 || $month==6 || $month==7 || $month==9 || $month==11) {
		$days=31;
	} else if ($month==1) {
		if (($year%4)==0)
			$days=29;
		else
			$days=28;
	} else {
		$days=30;
	}
*/		
	for ($i=1; $i<32; $i++) {

		if ($i<10)
			$val='0'.(string)$i;
		else
			$val=(string)$i;
		
		if ($val==$selected)
			$str.="<option value=\"$val\" selected>$val</option>";
		else
			$str.="<option value=\"$val\">$val</option>";
	}
   $str.="</select>";
	return $str;
}	

$format=$gText['M_ENTER_VAL'];

?>


<style type="text/css">
	#more-options {
		display:none;
	}
</style>


<script type="text/javascript">
<!--
	
function CheckMeetingForm (theForm) {
	if (theForm.tele_conf_choice) {
		if (theForm.tele_conf_choice[2].checked && theForm.tele_num.value=='')
		{
			alert("<?php echo sprintf($format, 'Phone')?>");
			theForm.tele_num.focus();
			return (false);
		}
		else if (theForm.tele_conf_choice[1].checked && theForm.conf_num.value=='')
		{
			alert("<?php echo sprintf($format, 'Phone')?>");
			theForm.conf_num.focus();
			return (false);
		}
	}
	return true;
}

function ShowOptions() {

	var elem=document.getElementById('show-label');
	var opt=document.getElementById('more-options');
	var showIt;
	if (opt.style.display=='inline')
		showIt=false;
	else
		showIt=true;

	if (showIt)
		elem.innerHTML="<?php echo $hideOptions?>";
	else
		elem.innerHTML="<?php echo $moreOptions?>";
		
	if (showIt)
		opt.style.display='inline';
	else
		opt.style.display='none';

/*
	var x=document.getElementsByTagName("tr");
	for (var i=0; i<x.length; i++)
	{
		if (x[i].className=='more-options') {
			if (showIt)
				x[i].style.display='table-row';
			else
				x[i].style.display='none';
			
		}
	} 
*/
}


//-->
</script>

<?php
if (isset($meeting)) {
	echo "<div class=\"list_tools\"><a target=${GLOBALS['TARGET']} href=\"$viewUrl\"><img src=\"$previewIcon\">${gText['M_VIEW_MEETING']}</a>";
	
	if ($meetingInfo['login_type']=='REGIS' && $hasRegist=='Y') {
		$custRegisText=_Text('Customize Registration'); 
		$editIcon='themes/edit.gif';
		$custRegisPage=$GLOBALS['BRAND_URL']."?page=".PG_MEETINGS_REGFORM;

		$custRegisPage.="&meeting_id=".$meetingInfo['id'];
		
		$viewText=_Text("View Registration Page");
		$regUrl=$GLOBALS['BRAND_URL']."?page=REGISTER&meeting=".$meetingInfo['access_id'];
		echo " &nbsp; <a target=${GLOBALS['TARGET']} href=\"$regUrl\"><img src=\"$previewIcon\">$viewText</a>";
		
		echo " &nbsp; <a target=\"{$GLOBALS['TARGET']}\" href='$custRegisPage'><img src=\"$editIcon\"> $custRegisText</a>";
	}

	echo "</div>\n";
}
?>

<form onSubmit="return CheckMeetingForm(this)" target=<?php echo $GLOBALS['TARGET']?> method="POST" action="<?php echo $postUrl?>" name="updatemeeting_form">

<?php
if (isset($meetingInfo['id'])) {
	$id=$meetingInfo['id'];
print <<<END
<input type="hidden" name="id" value="$id">
END;
} else {
	// new meeting
//<input type="hidden" name="webserver_id" value="$webServerId">

print <<<END
<input type="hidden" name="host_id" value="$memberId">

END;
}
?>
<table class="meeting_detail">

<tr>
	<td class="m_key"><?php echo $gText['MD_MEETING_TITLE']?></td>
	<td colspan="3" class="m_val"><input type="text" name="title" size="60" value="<?php echo $title?>"></td>
</tr>

<?php
$dateText=$gText['MD_DATE_TIME'];
if (isset($meetingInfo['status']) && $meetingInfo['status']=='REC') {
	// recording
//	$recText=$gText['MD_RECORDED'];
	$durText=$gText['MD_DURATION'];
	$timeStr=H24ToH12($meetHour, $meetMin);
	print <<<END
<tr>
	<td class="m_key"><img src="$schedIcon"> $dateText</td>
	<td colspan="3" class="m_val">
	Recorded on <strong>$meetDateStr $timeStr</strong> &nbsp; $durText <strong>$meetDuration</strong>
	</td>
</tr>
END;
} else {
	$noneLabel=$gText['MD_UNSCHEDULED'];
	$noneText=$gText['MD_UNSCHEDULED_TEXT'];
	$yesLabel=$gText['MD_SCHEDULED'];
	$yesText=$gText['MD_SCHEDULED_TEXT'];
	$check1='';
	if ($scheduled=='N')
		$check1='checked';
	$check2='';
	if ($scheduled=='Y')
		$check2='checked';
	$checkText='';
	
	$text1=_Text("Add to my calendar (send an iCalendar file to me.)");
	$text2=_Text("Meeting does not automatically start on the scheduled time.");
	$text3=_Text("Time zone");
	$text4=_Text("Set as default time zone");

	print <<<END
<tr>
	<td class="m_key"><img src="$schedIcon"> $dateText</td>
	<td colspan="3" class="m_val">
	<div class='sub_val1'><input $check1 type="radio" name="scheduled" value="N" onclick="return SetElemHtml('sched_form', '')"><b>$noneLabel:</b> $noneText</div>
	<div class='sub_val1'><input $check2 type="radio" name="scheduled" value="Y" onclick="return SetElemHtml('sched_form', 'sched_form')"><b>$yesLabel:</b></div>
	<div id="sched_form" class='sub_val2'>$schedHtml
	<div class='sub_val2'>$text3: $timezones <input type="checkbox" name="set_tz" value='1'>$text4</div>
	<div class='sub_val2'><input type='checkbox' name='send_email' value='1'>$text1</div>
	<div class='m_caption'>$text2</div>
	</div>
	</td>
</tr>
END;
}


?>
<tr>
	<td class="m_key"><?php echo $gText['MD_DESCRIPTION']?></td>
	<td colspan="3" class="m_val"><textarea id="meet_desc" name="description" rows="4" cols="65"><?php echo $description?></textarea></td>
</tr>


<tr>
	<td class="m_key"><img src="<?php echo $pwdIcon?>"> <?php echo $gText['MD_LOGIN']?></td>
	<td colspan="3" class="m_val">
<?php
if (isset($meetingInfo) && $meetingInfo['status']=='REC') {
	if ($loginType=='NONE')
		$checkNone='checked';
	else
		$checkNone='';
		
print <<<END
	<div class="sub_val1"><input $checkNone type="radio" name="login_type" value="NONE" onclick="SetElemHtml('pwd_form', '');  SetElemHtml('reg_form', ''); SetElemHtml('close_reg_form', ''); return true;"><b>${gText['MD_NONE']}</b></div>
END;
}
?>
	<div class="sub_val1"><input <?php if ($loginType=='NAME') echo 'checked';?> type="radio" name="login_type" value="NAME" onclick="SetElemHtml('pwd_form', '');  SetElemHtml('reg_form', ''); SetElemHtml('close_reg_form', ''); return true;"><b><?php echo $gText['MD_NAME']?>:</b> <?php echo $gText['MD_NAME_TEXT']?></div>
	<div class="sub_val1"><input <?php if ($loginType=='PWD') echo 'checked';?> type="radio" name="login_type" value="PWD" onclick="SetElemHtml('pwd_form', 'pwd_form'); SetElemHtml('reg_form', ''); SetElemHtml('close_reg_form', ''); return true;"><b><?php echo $gText['MD_NAMEPWD']?>:</b> <?php echo $gText['MD_NAMEPWD_TEXT']?></div>
	<div id="pwd_form" class="sub_val2"><?php echo $gText['MD_PASSWORD']?> <input type="text" name="password" size="8" maxlength='8' value="<?php echo $password?>"> <span class="m_caption"><?php echo _Text("Up to 8 characters")?></span></div>
<?php

if ($hasRegist=='Y') {
	if ($loginType=='REGIS')
		$checkRegis='checked';
	else
		$checkRegis='';
	
	$checkOpen=$checkClose='';
	if ($closeRegister=='N')
		$checkOpen='checked';
	else
		$checkClose='checked';
		
	$text0=$gText['MD_REGISTRATION'];
	$text01=$gText['MD_REGISTRATION_TEXT'];
	$text1=_Text("Open registration");
	$text11=_Text("Allow participants to register");
	$text2=_Text("Close registration");
	$text22=_Text("No more participants can register");
print <<<END
	<div class='sub_val1'><input $checkRegis type="radio" name="login_type" value="REGIS" onclick="SetElemHtml('pwd_form', ''); SetElemHtml('reg_form', 'reg_form'); SetElemHtml('close_reg_form', 'close_reg_form'); return true;"><b>$text0:</b> $text01
	&nbsp;
	</div>	
    <div id="close_reg_form">
	<div class="sub_val2"><input $checkOpen type="radio" name="close_register" value="N">
	<b>$text1:</b> $text11</div>
	<div class="sub_val2"><input $checkClose type="radio" name="close_register" value="Y">
	<b>$text2:</b> $text22</div>
	</div>
END;
}
?>
	</td>
</tr>

<?php

if (!isset($meetingInfo['status']) || $meetingInfo['status']!='REC') {
//	$phoneText=$gText['MD_TELEPHONE'];
	$phoneText=_Text("Teleconference");
	$noneText=$gText['MD_NONE'];
	$usePhoneText=$gText['MD_HAS_TELE'];
	$check1='';
	if ($teleConf=='N')
		$check1='checked';
	$check2='';
	$check3='';
	if ($teleConf=='Y') {
		if ($meetingInfo['tele_num']==$confNum && $meetingInfo['tele_mcode']==$confMCode)
			$check2='checked';
		else
			$check3='checked';
	}
	$checkText='';
	
	$disable2='';
	if ($check2=='' && $confNum=='')
		$disable2="disabled";
	
	
	$audioConfPage=$GLOBALS['BRAND_URL']."?page=".PG_ACCOUNT_AUDIO_CONF;
//	<div class='m_caption'>Audio recording: $canRecord &nbsp;&nbsp; Telephony controls: $canControl</div>
//	<div class='m_caption'>Audio recording and control are available only on pre-assigned teleconference numbers.</div>

	$text1=_Text("Use teleconference number assigned to me.");
	$text2=_Text("Required for audio recording (if recording is enabled.)");
	$text3=_Text("Use my own teleconference number");
	$text4=_Text("Set my teleconference");
	$text5=_Text("Audio recording is not available for your own conference number.");
	
	print <<<END
<tr>
	<td class="m_key">$phoneText</td>
	<td colspan="3" class="m_val">
	<div class='sub_val1'><input $check1 type="radio" name="tele_conf_choice" value="NONE" onclick="SetElemHtml('tele_form', ''); SetElemHtml('conf_form', ''); return true;">
	<b>$noneText</b></div>
	<div class='sub_val1'><input $check2 $disable2 type="radio" name="tele_conf_choice" value="CONF" onclick="SetElemHtml('tele_form', ''); SetElemHtml('conf_form', 'conf_form'); return true;">
	<b>$text1</b></div>
	<div class='m_caption'>$text2</div>
	<div id="conf_form">
	<div class='sub_val2'>${gText['MD_PHONE_NUM']}: <input readOnly type="text" name="conf_num" size="15" value="$confNum">
	Or <input readOnly type="text" name="conf_num2" size="15" value="$confNum2"></div>
	<div class='sub_val2'>${gText['MD_PHONE_MCODE']}: <input readOnly type="text" name="conf_mcode" size="10" value="$confMCode">
	${gText['MD_PHONE_PCODE']}: <input readOnly type="text" name="conf_pcode" size="10" value="$confPCode"></div>
	</div>	
	<div class='sub_val1'><input $check3 type="radio" name="tele_conf_choice" value="TELE" onclick="SetElemHtml('tele_form', 'tele_form'); SetElemHtml('conf_form', ''); return true;">
	<b>$text3</b>: &nbsp;
	<a target=${GLOBALS['TARGET']} href='$audioConfPage'>$text4</a></div>
	<div class='m_caption'>$text5</div>
	<div id="tele_form" >
	${gText['MD_PHONE_NUM']}: <input type="text" name="tele_num" size="11" value="$phoneNum">
	${gText['MD_PHONE_MCODE']}: <input type="text" name="tele_mcode" size="7" value="$phoneMCode">
	${gText['MD_PHONE_PCODE']}: <input type="text" name="tele_pcode" size="7" value="$phonePCode">
	</div>
	</td>
</tr>
END;
}
?>

<tr>
	<td class="m_key">
	<a onclick="ShowOptions(); return false;" href="javascript:void(0)"><b><span id="show-label"> <?php echo $moreOptions?> </span></b></a>
	</td>	
	<td colspan="3" class="m_val"><hr size=1>
	</td>
</tr>
</table>

<table id='more-options' class="meeting_detail">

<?php
if (!isset($meetingInfo) || $meetingInfo['status']!='REC') {
	
	$typeText=_Text("Meeting Type");
	$normText=_Text("<b>Normal</b>: start the meeting only when I join.");
	$openText=_Text("<b>Open</b>: start the meeting when anyone joins.");
	$panelText=_Text("<b>Panel</b>: start the meeting when anyone joins and make everyone a presenter.");
	
	$checkNorm=$checkOpen=$checkPanel=$checkPeer='';
	if (isset($meetingInfo['meeting_type']) && $meetingInfo['meeting_type']=='OPEN')
		$checkOpen='checked';
	elseif (isset($meetingInfo['meeting_type']) && $meetingInfo['meeting_type']=='PANEL')
		$checkPanel='checked';
	else
		$checkNorm='checked';
			
print <<<END
<tr>
	<td class="m_key">$typeText</td>
	<td colspan="3" class="m_val">

	<div class="sub_val1"><input $checkNorm type="radio" name="meeting_type" value="NORMAL">$normText</div>
	<div class="sub_val1"><input $checkOpen type="radio" name="meeting_type" value="OPEN">$openText</div>
	<div class="sub_val1"><input $checkPanel type="radio" name="meeting_type" value="PANEL">$panelText</div>
	</td>
</tr>
END;

}

$downloadIcon="themes/download.gif";
$allowDownloadText=_Text("Allow anyone to download the recording.");
$disableDownloadText=_Text("Disable download.");
$noDownloadText=_Text("Download is disabled for recordings that require a password or registration.");

if (isset($meetingInfo['status']) && $meetingInfo['status']=='REC') {
	
	if (defined('ENABLE_DOWNLOAD_RECORDING') && constant('ENABLE_DOWNLOAD_RECORDING')=="1" &&
		(!isset($gBrandInfo['rec_download']) || $gBrandInfo['rec_download']=='Y')) {

		$checkN=$checkY='';	
		if ($meetingInfo['can_download_rec']=='Y')
			$checkY='checked';
		else
			$checkN='checked';
		
		$downloadText=_Text("Download");
		if ($meetingInfo['login_type']=='NAME' || $meetingInfo['login_type']=='NONE') {
	
	print <<<END
<tr>
	<td class="m_key"><img src="$downloadIcon"> $downloadText</td>
	<td colspan="3" class="m_val">
	<div class='sub_val1'><input $checkY type="radio" name="can_download_rec" value="Y"><b>${gText['M_YES']}:</b> $allowDownloadText</div>
	<div class='sub_val1'><input $checkN type="radio" name="can_download_rec" value="N"><b>${gText['M_NO']}:</b> $dsiableDownloadText</div>
	</td>
</tr>
END;
		} else {
	print <<<END
<tr>
	<td class="m_key"><img src="$downloadIcon"> $downloadText</td>
	<td colspan="3" class="m_val">
		$noDownloadText
	</td>
</tr>
END;
		
		}

	}

	if (defined('ENABLE_DOWNLOAD_AUDIO') && constant('ENABLE_DOWNLOAD_AUDIO')=="1") {

		$checkN=$checkY='';	
		if ($meetingInfo['can_download']=='Y')
			$checkY='checked';
		else
			$checkN='checked';
			
		$downloadAudioText=_Text("Download Audio");
		$allowDownloadAudio=_Text("Allow anyone to download the audio portion of the recording.");
		if ($meetingInfo['login_type']=='NAME' || $meetingInfo['login_type']=='NONE') {
	
	print <<<END
<tr>
	<td class="m_key"><img src="$downloadIcon"> $downloadAudioText</td>
	<td colspan="3" class="m_val">
	<div class='sub_val1'><input $checkY type="radio" name="can_download" value="Y"><b>${gText['M_YES']}:</b> $allowDownloadAudio</div>
	<div class='sub_val1'><input $checkN type="radio" name="can_download" value="N"><b>${gText['M_NO']}:</b> $disableDownloadText</div>
	</td>
</tr>
END;

		} else {
	print <<<END
<tr>
	<td class="m_key"><img src="$downloadIcon"> $downloadAudioText</td>
	<td colspan="3" class="m_val">
		$noDownloadText
	</td>
</tr>
END;
		}
	}
}


if (defined('ENABLE_TRANSCRIPTS') && constant('ENABLE_TRANSCRIPTS')=='1' &&
	isset($gBrandInfo['send_report']) && $gBrandInfo['send_report']=='Y' &&
	(!isset($meetingInfo['status']) || $meetingInfo['status']!='REC')) 
{
	$reportText=_Text("Report");
	
	$checkY=$checkN='';
	if ($sendReport=='Y')
		$checkY='checked';
	else if ($sendReport=='N')
		$checkN='checked';

	$text1=_Text("Don't send a report to me at end of the meeting.");
	$text2=_Text("Send a report to me at end of the meeting.");
	$text3=_Text("Apply to all meetings.");
print <<<END
<tr class='more-options'>
	<td class="m_key">$reportText</td>
	<td colspan="3" class="m_val">
	<div class='sub_val1'><input $checkY type="radio" name="send_report" value="Y"><b>${gText['M_YES']}:</b> $text2
	<div class='m_caption'>A report including meeting attendees and transcripts will be sent to my email address.</div>
	<div class='sub_val1'><input $checkN type="radio" name="send_report" value="N"><b>${gText['M_NO']}:</b> $text1</div>
	<div class='sub_val1'><input type="checkbox" name="set_all_reports" value='1'>$text3</div>
	</div>
	</td>
</tr>
END;
}

$checkY=$checkN=$checkS='';
if ($public=='Y')
	$checkY='checked';
else if ($public=='N')
	$checkN='checked';
else if ($public=='S')
	$checkS='checked';

$roomLabel=_Text("Meeting Room");
$roomText=_Text("List in my meeting room page.");
$pubText=_Text("List in my meeting room and the home page.");

// See if Home page is disabled
if (strpos($GLOBALS['MAIN_TABS'], PG_HOME)===false) 
{
	// Home page is not enabled. Hide the Public choice
	$publicDiv='';
} else {
	$publicDiv="<div class='sub_val1'><input $checkY type=\"radio\" name=\"public\" value=\"Y\"><b>${gText['MD_PUBLIC']}:</b> $pubText</div>\n";
}

$roomDiv="<div class='sub_val1'><input $checkS type=\"radio\" name=\"public\" value=\"S\"><b>$roomLabel:</b> $roomText</div>";

print <<<END
<tr>
	<td class="m_key"><img src="$homeIcon"> ${gText['MD_PUBLISH']}</td>
	<td colspan="3" class="m_val">
	<div class='sub_val1'><input $checkN type="radio" name="public" value="N"><b>${gText['MD_NONE']}:</b> ${gText['MD_PRIVATE_TEXT']}</div>
	$roomDiv
	$publicDiv
	</td>
</tr>
END;


$text1=_Text("Allow public comments");
$text2=_Text("Visitors can post a public comment of this meeting.");
$text3=_Text("Private comments only");
$text4=_Text("Vistors can only send a private comment to me.");

$checkY=$publicComment=='Y'?"checked":"";
$checkN=$publicComment=='N'?"checked":"";

print <<<END
<tr>
	<td class="m_key"><img src="$commentIcon"> ${gText['M_COMMENTS']}</td>
	<td colspan="3" class="m_val">
	<div class='sub_val1'><input $checkY type="radio" name="public_comment" value="Y"><b>$text1:</b> $text2</div>
	<div class='sub_val1'><input $checkN type="radio" name="public_comment" value="N"><b>$text3:</b> $text4</div>
	</td>
</tr>
END;

/*Turn off meeting viewer embedding because of two problems:
1. The viewer uses Javascript to do certain work and the embedding doesn't include the javascript code.
2. The viewer may run on any hosting server assigned to the user's group and the embedding code is bound to the login site.
if (isset($meetingInfo['id'])) {
	
	$siteUrl=SITE_URL;
//	$apiUrl=VWebServer::AddPaths($siteUrl, VM_API);
//	$flashVars="MeetingServer=$apiUrl&MeetingID=${meetingInfo['access_id']}";
//	$apiUrl=$GLOBALS['BRAND_URL'].urlencode("get_api_url.php?commandApi=1");
	$apiUrl=$gBrandInfo['site_url'].urlencode("get_api_url.php?commandApi=1");
	$flashVars="MeetingID=".$meetingInfo['access_id'];
	$flashVars.="&GetServerUrl=".$apiUrl;
//	$swfFile=VWebServer::AddPaths($siteUrl, "viewer.swf");
//	$swfFile=VWebServer::AddPaths($GLOBALS['BRAND_URL'], "viewer.swf");
	$swfFile=VWebServer::AddPaths($gBrandInfo['site_url'], "viewer.swf");
	$meetingUrl='';
	$meeting->GetMeetingUrl($meetingUrl);
	
$embedCode=
"<object width=\"100%\" height=\"100%\">
<param name=\"flashvars\" value=\"$flashVars\"/>
<param name=\"movie\" value=\"$swfFile\"/>
<param name=\"wmode\" value=\"opaque\" />
<param name=\"allowFullScreen\" value=\"true\" />
<embed width=\"100%\" height=\"100%\" 
src=\"$swfFile\" flashvars=\"$flashVars\" name=\"viewer\" wmode=\"opaque\" allowFullScreen=\"true\"/>
</object>";
		
$text1=_Text("Embedding");
$text2=_Text("Embed the meeting viewer in your web page (copy the html code below to your page.) Modify the width and height values as needed.");

print <<<END

<tr>
	<td class="m_key">${gText['MD_MEETING_URL']}</td>
	<td colspan="3" class="m_val"><input readonly type="text" name="url" size="80" value="$meetingUrl"></td>
</tr>

<tr>
	<td class="m_key">$text1:</td>
	<td colspan="3" class="m_val">
	<div class='m_caption'>$text2</div>
	<textarea readonly id="meet_desc" name="html" rows="4" cols="65">$embedCode</textarea>
	</td>
</tr>

END;
}
*/

$clientData=isset($meetingInfo['client_data'])?htmlspecialchars($meetingInfo['client_data']):'';
?>

<tr>
	<td class="m_key"><?php echo _Text("Tracking Code")?>:</td>
	<td colspan="3" class="m_val">
	<div class='sub_val1'><input type="text" name="client_data" maxlength='63' size="63" value="<?php echo $clientData?>"></div>
	<div class='m_caption'>Enter your tracking code (up to 63 characters) for the meeting. The code will appear in the field: "#CD: Meeting client data" when you download a meeting report.</div>
	</td>
</tr>
</table>

<table class="meeting_detail">
<tr>
	<td class="m_key">&nbsp;</td>
	<td colspan="3" class="m_val"><input type="submit" name="submit" value="<?php echo $gText['M_SUBMIT']?>">&nbsp;
	<input type="reset" name="reset" value="<?php echo $gText['M_RESET']?>">&nbsp;
	<input type="button" name="cancel" value="<?php echo $gText['M_CANCEL']?>" onclick="return GoToUrl('<?php echo $cancelUrl?>')"></td>
</tr>


</table>
</form>

<script type="text/javascript">
<!--	
var htmlText=new Array();
htmlText['sched_form']= GetElemHtml('sched_form');
htmlText['tele_form']= GetElemHtml('tele_form');
htmlText['pwd_form']= GetElemHtml('pwd_form');
htmlText['reg_form']= GetElemHtml('reg_form');
htmlText['close_reg_form']= GetElemHtml('close_reg_form');
htmlText['conf_form']= GetElemHtml('conf_form');


function SetElemHtml(elemId, textId)
{
	var elem=document.getElementById(elemId);

	if (elem) {
		if (textId!='') {
			elem.innerHTML=htmlText[textId];
			elem.style.display='inline';
		} else {
			elem.innerHTML='';
			elem.style.display='none';
		}
		
	}
	return true;
}

<?php



if ($loginType=='REGIS') {
	echo "SetElemHtml('reg_form', 'reg_form');\n";
	echo "SetElemHtml('close_reg_form', 'close_reg_form');\n";
} else {
	echo "SetElemHtml('reg_form', '');\n";
	echo "SetElemHtml('close_reg_form', '');\n";
}

if ($teleConf=='N') {
	echo "SetElemHtml('tele_form', '');\n";
	echo "SetElemHtml('conf_form', '');\n";
} elseif ($meetingInfo['tele_num']==$confNum && $meetingInfo['tele_mcode']==$confMCode) {
	echo "SetElemHtml('tele_form', '');\n";
	echo "SetElemHtml('conf_form', 'conf_form');\n";
} else {
	echo "SetElemHtml('conf_form', '');\n";
	echo "SetElemHtml('tele_form', 'tele_form');\n";
}
	
if ($scheduled=='Y')
	echo "SetElemHtml('sched_form', 'sched_form');\n";
else
	echo "SetElemHtml('sched_form', '');\n";

if ($loginType=='PWD')
	echo "SetElemHtml('pwd_form', 'pwd_form');\n";
else
	echo "SetElemHtml('pwd_form', '');\n";
	
	
?>
//-->
</script>