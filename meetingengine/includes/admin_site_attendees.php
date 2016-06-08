<?php
/**
 * Persony Web Conferencing 2.0
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2008 Persony, Inc.
 * @version     2.0
 * 
 */
require_once("dbobjects/vwebserver.php");

GetArg('site_id', $siteId);
if ($siteId=='') {
	ShowError("Missing site_id");
	return;
}
$server=new VWebServer($siteId);
if ($server->Get($serverInfo)!=ERR_NONE) {
	ShowError($server->GetErrorMsg());
	return;
}
if (!isset($serverInfo['id'])) {
	ShowError("Couldn't find server for id ".$siteId);
	return;
}

$editWebUrl=$GLOBALS['BRAND_URL']."?page=".PG_ADMIN_EDIT_WEB;
if (SID!='')
	$editWebUrl.="&".SID;

$aurl=$serverInfo['url'].'scripts/vgetstats.php';
$aurl.="?id=".$serverInfo['login'];
$aurl.="&code=".md5($serverInfo['password']);

// this should return a list of live attendees on the site
$resp=HTTP_Request($aurl, '', 'GET', 15);
$attList=array();
$attCount=0;

$errMsg='';
if ($resp) {
	$items=explode("\n", $resp);
	if ($items[0]=='OK') {
		foreach ($items as $anitem) {
			if ($anitem=='OK' || $anitem=='')
				continue;
			if ($anitem=='ERROR')
				break;
			$args=explode("&", $anitem);
			$attList[$attCount]=array();
			foreach ($args as $anArg) {
				$keyVal=explode("=", $anArg);
				$attList[$attCount][$keyVal[0]]=isset($keyVal[1])?rawurldecode($keyVal[1]):'';
			}
			$attCount++;
		}
	} else {
		$errMsg='Invalid response returned from '.$serverInfo['url'];
	}
} else {
	$errMsg='Could not get a response from '.$serverInfo['url'];
}

?>

<div class=heading1><?php echo _Text("Current Attendees")?></div>

<div><?php echo $serverInfo['id'];?> <b><?php echo $serverInfo['name']?></b> 
</div>
<?php if ($errMsg!='') echo "<div class='error'>$errMsg</div>"; ?>

<table cellspacing="0" class="meeting_list" >

<tr>
    <th class="tl pipe">&nbsp;</th>
    <th class="pipe"><?php echo $gText['MD_MEETING_ID']?></th>
    <th class="pipe"><?php echo _Text("User Name")?></th>
    <th class="pipe"><?php echo _Text("User ID")?></th>
    <th class="tr"><?php echo "User IP"?></th>
</tr>

<?php

if (count($attList)==0) {
	echo "<tr>";
	echo "<td colspan=5>&nbsp;</td>";
	echo "</tr>";
} else {
	$rowCount=0;
	$serverList=array();
	
	foreach ($attList as $row) {
		if ($rowCount % 2)
			echo "<tr class=\"u_bg\">\n";
		else
			echo "<tr>\n";
		echo "<td class=\"u_item_c\">".($rowCount+1)."</td>\n";
		
		$meetingid=isset($row['meetingid'])?$row['meetingid']:'';
		if ($meetingid=='')
			$meetingid='&nbsp;';
		echo "<td class=\"u_item_c\">".$meetingid."</td>\n";
		
		$serverid=isset($row['serverid'])?$row['serverid']:'';
			
		// if serverip is set, this is a caching server connection
		if (isset($row['serverip'])) {
			$serverName='';
			if ($serverid!='') {
				if (!isset($serverList[$serverid])) {
					$server=new VWebServer($serverid);
					$server->GetValue('name', $serverList[$serverid]);
				}
				
				$serverName=htmlspecialchars($serverList[$serverid]);
			}
			$name="[".$serverName."]";
		} else {
			$name=isset($row['username'])?htmlspecialchars($row['username']):'';
		}
			
		if ($name=='')
			$name='&nbsp;';
		echo "<td class=\"u_name\">".$name."</a></td>\n";
		
		$userid=isset($row['userid'])?$row['userid']:'';
		if ($userid=='')
			$userid='&nbsp;';
		echo "<td class=\"u_item_c\">".$userid."</td>\n";
		
		$ip=isset($row['userip'])?$row['userip']:(isset($row['serverip'])?$row['serverip']:'');
		if ($ip=='')
			$ip='&nbsp;';
		echo "<td class=\"u_item_c\">".$ip."</td>\n";	
			
		echo "</tr>\n";
		
		$rowCount++;
	}
}
?>
</table>
<div id='meeting_stat'><?php echo _Text("Total")?>: <b><?php echo count($attList)?></b></div>
