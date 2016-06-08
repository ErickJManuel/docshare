<?php
/**
 * Persony Web Conferencing 2.0
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2008 Persony, Inc.
 * @version     2.0
 * 
 */


GetArg("type", $type);

GetArg('meeting', $meetingId);

if ($meetingId!='') {
	
	$meetingInfo=array();
	$errMsg=VObject::Find(TB_MEETING, 'access_id', $meetingId, $meetingInfo);
	
	if ($errMsg!='') {
		ShowError($errMsg);
		return;
	}
	if (!isset($meetingInfo['id'])) {
		ShowError("Couldn't find a meeting record that matches id ".$meetingId);
		return;
	}
}

$thisPage=$GLOBALS['BRAND_URL']."?page=".PG_ADMIN_REPORT;
//	$thisPage=$_SERVER['PHP_SELF']."?page=".PG_ADMIN_REPORT;
if (SID!='')
	$thisPage.="&".SID;

$sessionUrl=$thisPage;
$portUrl=$thisPage."&type=port";
$serverPageUrl=$thisPage."&type=site";

/*
<div class='list_tools'>
<span class='list_item'><a href='<?php echo $detailUrl?>'><?php echo $gText['M_SHOW_DETAILS']?></a></span>
<span class='list_item'><a href='<?php echo $summaryUrl?>'><?php echo $gText['M_SHOW_SUMMARY']?></a></span>
</div>
*/
/*
<span class='list_item'><input <?php echo $checkN?> onclick="return GoToUrl('<?php echo $detailUrl?>');" type="radio" name="summary" value="N"><?php echo $gText['M_SHOW_DETAILS']?></span>
<span class='list_item'><input <?php echo $checkY?> onclick="return GoToUrl('<?php echo $summaryUrl?>');" type="radio" name="summary" value="Y"><?php echo $gText['M_SHOW_SUMMARY']?></span>
*/

/*
	if (!isset($_GET['user']) && !isset($userReport)) {
		print <<<END
<div class='list_tools'>
<form>
<span class='list_item'><input onclick="return GoToUrl('$detailUrl');" type="button" name="detailBtn" value="${gText['M_SHOW_DETAILS']}"></span>
<span class='list_item'><input onclick="return GoToUrl('$summaryUrl');" type="button" name="summaryBtn" value="${gText['M_SHOW_SUMMARY']}"></span>
</form>
</div>
<hr>
END;
	}
*/

if (!isset($userReport) && $meetingId=='') {

	$sessionPage=_Text("Meeting sessions");
	$portPage=_Text("Concurrent ports");
	$serverPage=_Text("Meeting sites");
	
	$selectSession=$selectPort=$selectServer='';
	if ($type=='')
		$selectSession="class='on'";
	else if ($type=='port')
		$selectPort="class='on'";
	else if ($type=='site')
		$selectServer="class='on'";
		
	$target=$GLOBALS['TARGET'];
print <<<END
	<div id='page_menu'>
	<ul>
	<li $selectSession><a target=$target href='$sessionUrl'>$sessionPage</a></li>
	<li $selectPort><a target=$target href='$portUrl'>$portPage</a></li>
	<li $selectServer><a target=$target href='$serverPageUrl'>$serverPage</a></li>
	</ul>
	</div>
	<div>&nbsp;</div><br>
	
END;

}

if (isset($meetingInfo['id']) && $meetingInfo['status']=='REC') {
	require_once("includes/admin_report_replay.php");
} elseif ($type=='' || isset($userReport))
	require_once("includes/admin_report_detail.php");
elseif ($type=='port')
	require_once("includes/admin_report_port.php");
elseif ($type=='site')
	require_once("includes/admin_report_site.php");


?>