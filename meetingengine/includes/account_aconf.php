<?php
/**
 * Persony Web Conferencing 2.0
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2008 Persony, Inc.
 * @version     2.0
 * 
 */


require_once("dbobjects/vteleserver.php");
require_once("api_includes/free_conf.php");

$noRecText=_Text("Audio recording is not available for this phone number.");
$hasRecText=_Text("Audio recording is available for this phone number.");


$message='';		
$memberId=GetSessionValue('member_id');
$user=new VUser($memberId);
$userInfo=array();
$user->Get($userInfo);
if (!isset($userInfo['id'])) {
	ShowError("User not found");
	return;
}

$group=new VGroup($userInfo['group_id']);
$groupInfo=array();
$group->Get($groupInfo);
if (!isset($groupInfo['id'])) {
	ShowError("Group not found");
	return;
}
	
$phone=$userInfo['tele_num'];
$modcode=$userInfo['tele_mcode'];
$attcode=$userInfo['tele_pcode'];

$teleNum=$userInfo['conf_num'];
$teleNum2=$userInfo['conf_num2'];
$teleMcode=$userInfo['conf_mcode'];
$telePcode=$userInfo['conf_pcode'];

//$freeConf=$userInfo['free_conf'];

$canRecord=false;
$teleServerId=$groupInfo['teleserver_id'];
//if ($teleServerId!='0' && $gBrandInfo['can_record']) {
$hasFreeConf=false;
if ($teleServerId!='0') {
	$teleServer=new VTeleServer($teleServerId);
	$teleServer->Get($teleInfo);
	if (!isset($teleInfo['id'])) {
		ShowError("Teleconference server not found.");
		return;
	}
	
//	$freeConf=$teleInfo['can_getconf'];

	if ($userInfo['use_teleserver']=='Y' && $teleInfo['can_record']=='Y') {
		$canRecord=true;
	}
	if ($teleInfo['can_getconf']=='Y') {
		$hasFreeConf=true;
	}
}

if ($teleNum=='' && !$hasFreeConf) {	
	$teleNum='N/A';
}


$postUrl=$_SERVER['PHP_SELF']."?page=".PG_ACCOUNT_AUDIO_CONF."&brand=".$GLOBALS['BRAND_NAME']."&brandUrl=".$GLOBALS['BRAND_URL'];
if (SID!='')
	$postUrl.="&".SID;

$message='';
$requestMsg='';	
	
	
if (GetArg('set_own_conf', $arg) && $arg!='') {
	GetArg('phone', $phone);
	GetArg('modcode', $modcode);
	GetArg('attcode', $attcode);

	$newInfo=array();
	$newInfo['tele_num']=$phone;
	$newInfo['tele_mcode']=$modcode;
	$newInfo['tele_pcode']=$attcode;

	if ($user->Update($newInfo)!=ERR_NONE) {
		ShowError($user->GetErrorMsg());
		return;
	}
	$message=_Text("Phone number is set.");
}

if (isset($_POST['request'])) {
	
	$resp=GetFreeConfManager($userInfo, $confMgr, $confUser, $confPass);
	
	if ($confMgr=='') {
		if ($resp!='')
			ShowError($resp);
		else
			ShowError("Free Conference Manager is not enabled.");
		return;
	}
	
	$requestMsg=FreeConfRequest($confMgr, $confUser, $confPass, $teleNum, $teleMcode, $telePcode);
	
	if ($teleNum!='') {
		
//		$freeConf='Y';
		$teleNum=AddSpacesToPhone($teleNum);
		$newInfo=array();
		$newInfo['conf_num']=$teleNum;
		$newInfo['conf_num2']='';
		$newInfo['conf_mcode']=$teleMcode;
		$newInfo['conf_pcode']=$telePcode;
		$newInfo['use_teleserver']='Y';
		//		$newInfo['free_conf']=$freeConf;
		
		if ($user->Update($newInfo)!=ERR_NONE) {
			ShowError($user->GetErrorMsg());
			return;
		}
		
		// find all meetings using the pre-set number and change them too
		$userId=$userInfo['id'];
		$query="host_id = '$userId' AND tele_conf='Y' AND tele_num='$teleNum' AND tele_mcode='$teleMcode'";
		$errMsg=VObject::SelectAll(TB_MEETING, $query, $result);
		
		while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
			$meeting=new VMeeting($row['id']);
			$meetInfo=array();
			$meetInfo['tele_num']=$teleNum;
			$meetInfo['tele_num2']='';
			$meetInfo['tele_mcode']=$teleMcode;
			$meetInfo['tele_pcode']=$telePcode;
			$meeting->Update($meetInfo);				
		}
		
	}
} else if (isset($_POST['verify'])) {
		
	$resp=GetFreeConfManager($userInfo, $confMgr, $confUser, $confPass);
	if ($confMgr=='') {
		if ($resp!='')
			ShowError($resp);
		else
			ShowError("Free Conference Manager is not enabled.");
		return;
	}

	$requestMsg=FreeConfVerify($confMgr, $confUser, $confPass, $teleNum, $teleMcode, $telePcode);
	$requestMsg=str_replace("ONOK ON", "ON",  $requestMsg); // fix a bug in the returned message with duplicated OK ON		
		
} else if (isset($_POST['delete'])) {
	
	// delete the old free conference number if it was set
//	if (isset($userInfo['free_conf']) && $userInfo['free_conf']=='Y' && $userInfo['tele_num']!='') {
	if ($teleNum!='') {
		
		$resp=GetFreeConfManager($userInfo, $confMgr, $confUser, $confPass);
		if ($confMgr=='') {
			if ($resp!='')
				ShowError($resp);
			else
				ShowError("Free Conference Manager is not enabled.");
			return;
		}
		$requestMsg=FreeConfDelete($confMgr, $confUser, $confPass, $teleNum, $teleMcode, $telePcode);
		
	}
	$teleNum=$teleMcode=$telePcode='';
//	$freeConf='N';
//	$newInfo['free_conf']=$freeConf;
	$newInfo['conf_num']='';
	$newInfo['conf_mcode']='';
	$newInfo['conf_pcode']='';
	if ($user->Update($newInfo)!=ERR_NONE) {
		ShowError($user->GetErrorMsg());
		return;
	}
	
}

$confirmRequest=_Text("Do you want to request a new phone number and codes?\\nYour current phone number and codes will be removed.");
$confirmDelete=_Text("Do you want to delete the current phone number and codes assigned to you?");
$phoneEmpty=_Text("Phone number is empty.");
	
?>



<script type="text/javascript">
<!--

function ConfirmRequest () {
	var elem=document.getElementById('tele_num_id');
	if (elem.value!='') {
		return confirm( "<?php echo $confirmRequest?>");
	} else
		return true;
}

function ConfirmDelete () {
	var elem=document.getElementById('tele_num_id');
	if (elem.value!='') {
		return confirm( "<?php echo $confirmDelete?>");
	} else {
		alert( "<?php echo $phoneEmpty?>");
		return false;
	}
}

//-->

</script>
<?php echo _Text("Set teleconference numbers to be used in my meetings.")?>

<form id='request_form' method="POST" action="<?php echo $postUrl?>" name="requestNumberForm">

<div class="meeting_frame_top">
<div class="meeting_frame_bot">
<table>


<tr>
	<td class='conf_info'>
		
	<div class='sub_val1'>
		<div class='conf_key'><?php echo _Text("Teleconference number assigned to me:")?></div>
<?php
if ($canRecord)
	echo "<div class='m_caption'>".$hasRecText."</div>\n";
else
	echo "<div class='m_caption'>".$noRecText."</div>\n";

?>
	</div>
	</td>

</tr>
<tr>
	<td class='conf_info1'>

		<input style='display:none' id='request_btn' onclick='return ConfirmRequest();' type='submit' value='Request Number' name='request'>
		<input style='display:none' id='verify_btn' type='submit' value=" Verify" name="verify">
		<input style='display:none' id='delete_btn' onclick='return ConfirmDelete();' type='submit' value="Delete" name="delete">

		<div id='conf_message' class='inform'><?php echo $requestMsg?></div>
	<table>

		<tr>
			<td class='info_key2'><?php echo _Text("Phone number:")?></td>
			<td>
			<input readOnly id='tele_num_id' type="number" name="tele_num" size="20" value="<?php echo $teleNum?>"> 
			</td>
		</tr>
		<tr>
			<td class='info_key2'><?php echo _Text("Alternate Phone number:")?></td>
			<td>
			<input readOnly id='tele_num2_id' type="number" name="tele_num2" size="20" value="<?php echo $teleNum2?>"> 
			</td>
		</tr>
		<tr>
			<td class='info_key2'><?php echo _Text("Moderator code:")?></td>
			<td>
			<input readOnly id='tele_mcode_id' type="number" name="tele_mcode" size="20" value="<?php echo $teleMcode?>"></td>
		</tr>
		<tr>
			<td class='info_key2'><?php echo _Text("Attendee code:")?></td>
			<td>
			<input readOnly id='tele_pcode_id' type="number" name="tele_pcode" size="20" value="<?php echo $telePcode?>"></td>
		</tr>

	</table>
	</td>
</tr>


<tr>
	<td class='conf_info'>

	<div class='sub_val1'>
		<div class='conf_key'><?php echo _Text("My own teleconference number:")?></div>
		<div class='m_caption'><?php echo $noRecText?></div>
<?php
if ($message!='')
	echo ("<div class='inform'>$message</div>");
?>
	</div>
	</td>
</tr>

<tr>
	<td class='conf_info1'>
	<table>

		<tr>
			<td class='info_key2'><?php echo _Text("Phone number:")?></td>
			<td>
			<input id='phone_number' type="number" name="phone" size="20" value="<?php echo $phone?>">
			</td>
		</tr>
		<tr>
			<td class='info_key2'><?php echo _Text("Moderator code:")?></td>
			<td>
			<input id='mod_code' type="number" name="modcode" size="10" value="<?php echo $modcode?>"></td>
		</tr>
		<tr>
			<td class='info_key2'><?php echo _Text("Attendee code:")?></td>
			<td>
			<input id='att_code' type="number" name="attcode" size="10" value="<?php echo $attcode?>">&nbsp;
			<input id='submit_btn' type="submit" name="set_own_conf" value="Submit">
			</td>
		</tr>

	</table>
	</td>
</tr>
</table>




<br>
</div>
</div>
</form>



<script type="text/javascript">
<!--

	
<?php

if (isset($teleInfo['can_getconf']) && $teleInfo['can_getconf']=='Y') {

	echo ("SetElemDisplay('request_btn', 'inline');\n");
	
//	if ($teleNum!='' && $freeConf=='Y') {
	if ($teleNum!='') {
		echo ("SetElemDisplay('verify_btn', 'inline');\n");
		echo ("SetElemDisplay('delete_btn', 'inline');\n");
	}
}

?>

//-->

</script>
