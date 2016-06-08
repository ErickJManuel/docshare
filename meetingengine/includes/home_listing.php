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
require_once("dbobjects/vuser.php");
require_once("dbobjects/vmeeting.php");

$itemsPerPage=10; // number of items to display per page
$maxPages=10; // max. number of pages to show in the bottom page nav bar
$pageNavInc=10; // number of pages to increase or decrease when the next/prev links are clicked in the page nav bar
$maxListChars=80; // max. number of chars to display in the meeting description box

$memberId=GetSessionValue('member_id');

$thisPage=$_SERVER['PHP_SELF'];
//$meetingsPage=$thisPage."?page=".$GLOBALS['SUB_PAGE']."&brand=".$GLOBALS['BRAND_NAME'];
$meetingsPage=$GLOBALS['BRAND_URL']."?page=".$GLOBALS['SUB_PAGE'];
if (SID!='')
	$meetingsPage.="&".SID;
//$registerPage=$GLOBALS['BRAND_URL']."?page=".PG_HOME_REGISTER;
$registerPage=$GLOBALS['BRAND_URL']."?page=".PG_REGISTER;
if (SID!='')
	$registerPage.="&".SID;

$hostUrl=$GLOBALS['BRAND_URL']."?page=".PG_HOME_USER;
if (SID!='')
	$hostUrl.="&".SID;
	
//$attendUrl="viewer.php?";
//$attendUrl=$GLOBALS['BRAND_URL']."?page=".PG_HOME_JOIN."&redirect=1";
$attendUrl=$GLOBALS['BRAND_URL']."?page=".PG_HOME_JOIN."&redirect=1";
if (SID!='')
	$attendUrl.="&".SID;
	
$startUrl=$GLOBALS['BRAND_URL']."?page=".PG_MEETINGS_START."&redirect=1";
//$startUrl=$_SERVER['PHP_SELF']."?page=".PG_MEETINGS_START;
if (SID!='')
	$startUrl.="&".SID;
	
$resumeUrl=$GLOBALS['BRAND_URL']."?page=".PG_MEETINGS_START."&resume=1&redirect=1";
if (SID!='')
	$resumeUrl.="&".SID;

$retPage=$GLOBALS['BRAND_URL']."?page=".PG_MEETINGS;
if (SID!='')
	$retPage.="&".SID;

$retPage=VWebServer::EncodeDelimiter1($retPage);

$endUrl=VM_API."?cmd=END_MEETING&return=$retPage";
if (SID!='')
	$endUrl.="&".SID;

$endRecUrl=VM_API."?cmd=END_RECORDING&return=$retPage";
if (SID!='')
	$endRecUrl.="&".SID;
/*
function GetMeetingIcons($meetingInfo)
{
	global $gText;
	global $homeIcon, $schedIcon, $pwdIcon, $phoneIcon, $speakerIcon, $regIcon;
	$icons='';
	$id=$meetingInfo['id'];			
	
	if ($meetingInfo['login_type']=='PWD')
		$icons.=
		"<span class='tool_icon' onmouseout=\"FP_changePropRestore()\" onmouseover=\"FP_changeProp('pwd_tip$id', 1, 'style.visibility','visible')\">".
		"<img src=\"$pwdIcon\">".
		"</span><span class=\"tool_tip\" id=\"pwd_tip$id\">".$gText['MD_PASSWORD_TIP']."</span>";

	if ($meetingInfo['tele_conf']=='Y' && $meetingInfo['status']!='REC')
		$icons.=
		"<span class='tool_icon' onmouseout=\"FP_changePropRestore()\" onmouseover=\"FP_changeProp('tele_tip$id', 1, 'style.visibility','visible')\">".
		"<img src=\"$phoneIcon\">".
		"</span><span class=\"tool_tip\" id=\"tele_tip$id\">".$gText['MD_TELE_TIP']."</span>";
	if ($meetingInfo['audio']=='Y')
		$icons.=
		"<span class='tool_icon' onmouseout=\"FP_changePropRestore()\" onmouseover=\"FP_changeProp('audio_tip$id', 1, 'style.visibility','visible')\">".
		"<img src=\"$speakerIcon\">".
		"</span><span class=\"tool_tip\" id=\"audio_tip$id\">".$gText['MD_AUDIO_TIP']."</span>";
	
	return $icons;
}
*/

GetArg('time_zone', $tz);
if ($tz[0]==' ')
	$tz[0]='+';

if ($tz!='') {
	SetSessionValue('time_zone', $tz);

} else {
	$tz=GetSessionValue("time_zone");
}

//GetSessionTimeZone($tzName, $dtz);


GetArg('select', $select);
GetArg('search', $search);
$search=VObject::MyAddSlashes($search);

$selectOpts=array(
	"CURRENT" => $gText['M_SELECT_CURRENT'],
	"INPROGRESS" => $gText['M_SELECT_INPROGRESS'],
//	"TODAY" => $gText['M_SELECT_TODAY'],
//	"PAST" => $gText['M_SELECT_PAST'],
	"ALL" => $gText['M_ALL_MEETINGS'],
//	"RECORDINGS" => $gText['M_RECORDINGS']
);

$showText=$gText['M_SHOW'];
$postPage=$thisPage."?page=".$GLOBALS['SUB_PAGE']."&".SID;
$postPage=addslashes($postPage);
$timezones=GetTimeZones($tz, "return ChangeTimeZone('time_zone', '$postPage');");
//$timezones=GetTimeZones($tz);

$selections='<select name="select">';

foreach ($selectOpts as $key => $value) {
	if ($select==$key)
		$selections.="<option value=\"$key\" selected>".$value."</option>";
	else
		$selections.="<option value=\"$key\">".$value."</option>";
}

$selections.="</select>";


?>

<script type="text/javascript">
<!--
/*
var lastTz='';
function ChangeTimeZone(url)
{
	var elem=document.getElementById('time_zone');

	window.location=url+"&time_zone="+elem.value;		

	return true;
}
*/
//-->
</script>



<form method="GET" action="<?php echo $GLOBALS['BRAND_URL']?>" name="selectmeeting_form">
<input type="hidden" name="page" value="<?php echo $GLOBALS['SUB_PAGE']?>">
<?php
if (SID!='') {
	$sessname=ini_get('session.name');
	$sessid=session_id();
	echo "<input type='hidden' name='$sessname' value='$sessid'>\n";
}
?>
<div class='meetings_select'>
<?php
if ($GLOBALS['SUB_PAGE']==PG_HOME_MEETINGS || $GLOBALS['SUB_PAGE']==PG_HOME || $GLOBALS['SUB_PAGE']=='') {
	print <<<END
<span id="select_show">${gText['M_SHOW']}:$selections</span>
<input type="submit" name="submit_select" value="${gText['M_GO']}">
END;
}
?>
<span id="select_search">
<input type="text" name="search" size="20" value="">
<input type="submit" name="submit_search" value="<?php echo $gText['M_SEARCH']?>">
</span>
</div>
</form>

<?php
//if ($GLOBALS['SUB_PAGE']==PG_HOME_MEETINGS) {
	print <<<END
<form method="POST" action="$postPage" name="tz_form">
<div class='meetings_tz1'>
${gText['MD_TIME_ZONE']}: $timezones
</div>
</form>
END;
//}
?>


<table cellspacing="0" class="meeting_list" >

<tr>
    <th class="pipe"><?php echo $gText['M_TITLE']?></th>
    <th class="pipe"><?php echo $gText['M_DATE']?></th>
    <th class="tr">&nbsp;</th>
</tr>
<?php

$query="public = 'Y'";
if ($search!='') {
	$query.=" AND ((access_id LIKE '%$search%') OR  (title LIKE '%$search%') OR (description LIKE '%$search%'))";
} else {
//	if ($select=='RECORDINGS')
	if ($GLOBALS['SUB_PAGE']==PG_HOME_RECORDINGS)
		$query.=" AND status='REC'";
	else {
		$query.=" AND status<>'REC'";
		
		if ($select=='' ||  $select=='CURRENT') {
			$query.=" AND (scheduled='N' OR CURDATE()<=DATE(date_time))";
		} else if  ($select=='TODAY') {
			$query.=" AND (scheduled='Y' AND CURDATE()=DATE(date_time))";
		} else if  ($select=='INPROGRESS') {
			$query.=" AND (status<>'STOP')";
		} else if ($select=='PAST') {
			$query.=" AND (scheduled='Y' AND CURDATE()>DATE(date_time))";
		} else if ($select=='SCHEDULED') {
			$query.=" AND (scheduled='Y')";
		} else if ($select=='UNSCHEDULED') {
			$query.=" AND (scheduled='N')";
		} else if ($select=='ALL_MEETINGS') {
			// nothing to do
		}
	}
}
$query.=" AND brand_id ='".$GLOBALS['BRAND_ID']."'";

GetArg('offset', $offset);
if ($offset=='') {	
	VObject::Count(TB_MEETING, $query, $total);	
	$start=0;
	$offset=0;
	$count=$itemsPerPage;
} else {
	GetArg('count', $count);
	GetArg('total', $total);
	GetArg('start', $start);
}
$reverse=false;
if ($GLOBALS['SUB_PAGE']==PG_HOME_RECORDINGS)
	$reverse=true;
$errMsg=VObject::SelectAll(TB_MEETING, $query, $result, $offset, $count, "*", "date_time", $reverse);
if ($errMsg!='') {
	ShowError($errMsg);
} else {
	$num_rows = mysql_num_rows($result);
	if ($num_rows==0) {
//		echo "<tr><td class=\"m_id\">&nbsp;</td>";
		echo "<tr>";
		echo "<td class=\"m_title\">&nbsp;</td>";
		echo "<td class=\"m_date\">&nbsp;</td>";
		echo "<td class=\"m_but\">&nbsp;</td></tr>";
	}		
	while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
		echo "<tr>\n";
		
//		echo "<td class=\"m_id\">".$row['access_id']."</td>\n";
		$desc=$row['description'];
		if (strlen($desc)>$maxListChars) {
			$desc=substr($desc, 0, $maxListChars-3);
			$desc.="...";
		}
		$desc=htmlspecialchars($desc);
		$title=htmlspecialchars($row['title']);
//		$meetingPage=$GLOBALS['BRAND_URL']."?page=".PG_HOME_MEETING."&meeting=".$row['access_id'];
		$meetingPage=$GLOBALS['BRAND_URL']."?meeting=".$row['access_id'];
		if (SID!='')
			$meetingPage.="&".SID;

		$titleStr="<a target=\"${GLOBALS['TARGET']}\" href=\"".$meetingPage."\">".$title."</a>";			
		echo "<td class='m_info'><ul>";
		echo "<li class=\"m_title\">".$titleStr."</li>";
				
		$host=new VUser($row['host_id']);
		$hostInfo=array();
		$host->Get($hostInfo);
		$hostName=$host->GetFullName($hostInfo);
		$hostName=htmlspecialchars($hostName);
		$hostLink="<a target=${GLOBALS['TARGET']} href=\"$hostUrl&user=".$hostInfo['access_id']."\">".$hostName."</a>";
		
		echo "<li class=\"m_host\">".$gText['MD_HOSTED_BY'].": ".$hostLink."</li>";
		
		echo "<li class=\"m_desc\">".$desc."</li>";
		
		$icons=GetMeetingIcons($row, false);

		echo "<li class=\"m_icon\">".$icons."</li>";
		echo "</ul></td>\n";
		
		$timeStr='&nbsp;';
		if ($row['scheduled']=='Y') {
			$dtime=$row['date_time'];
				
			$stz='';
			if ($tz!='')
				$stz=$tz;
			elseif ($hostInfo['time_zone']!='')
				$stz=$hostInfo['time_zone'];
			elseif  (isset($gBrandInfo['time_zone']))
				$stz=$gBrandInfo['time_zone'];
			
			GetTimeZoneByDate($stz, $dtime, $tzName, $dtz);						
			
			VObject::ConvertTZ($dtime, 'SYSTEM', $dtz, $tzTime);
			if ($tzTime!='') {
				list($date, $time)=explode(" ", $tzTime);
				list($hh, $mm, $ss)=explode(":", $time);
				$timeStr="<b>".$date."</b><br>";
				$timeStr.="".H24ToH12($hh, $mm)."<br>";
				if ($row['status']=='REC') {
					$dur=$row['duration'];
					$timeStr.="<i>($dur)</i>";
				} else {
					list($hh, $mm, $ss)=explode(":", $row['duration']);
					$timeStr.="<i>($hh:$mm)</i>";
				}
			}
		}

		$progStr='';
		$started=false;
		if (VMeeting::IsMeetingStarted($row)) 
		{
			$started=true;
			if (VMeeting::IsMeetingInProgress($row))
				$progStr="<br><span class=\"progress\">${gText['M_IN_PROGRESS']}</span>";
			else
				$progStr="<br><span class=\"progress\">${gText['M_IDLE']}</span>";
			
		}
		
		echo "<td class=\"m_date m_date2\">".$timeStr.$progStr."</td>\n";

		$btn="<ul>\n";
		if ($row['login_type']=='REGIS') {
			$btn.="<li><a target=${GLOBALS['TARGET']} href=\"$registerPage&meeting=".$row['access_id']."\">$registerBtn</a></li>";

		} elseif ($row['status']=='REC') {
			if ($row['rec_ready']=='Y')
				$viewBtn="<img src=\"$startIcon\">&nbsp;".$gText['M_PLAYBACK'];
			else
				$viewBtn='';
//			$meetingUrl=$attendUrl."&meeting=".$row['access_id']."&title=".rawurlencode($row['title']);
			$meetingUrl=$attendUrl."&meeting=".$row['access_id'];
			$btn.="<li><a target=${GLOBALS['TARGET']} href=\"$meetingUrl\">$viewBtn</a></li>";
//		} else if ($memberId=='' || $row['host_id']!=$memberId) {
		} else {
			// if the meeting is in progress and I am the host, shows the resume button
			if ($started && $row['host_id']==$memberId && $memberId!='') {
				$resumeMeetUrl=$resumeUrl."&meeting=".$row['access_id'];
				$btn.="<li><a target=${GLOBALS['TARGET']} href=\"$resumeMeetUrl\">$resumeBtn</a></li>";
				
			} elseif ($row['locked']=='Y') {
				$btn.="<li>Closed</li>";
			} else if ($row['host_id']!=$memberId) {
				$viewBtn="<img src=\"$startIcon\">&nbsp;".$gText['M_JOIN'];
				$meetingUrl=$attendUrl."&meeting=".$row['access_id'];
				$btn.="<li><a target=${GLOBALS['TARGET']} href=\"$meetingUrl\">$viewBtn</a></li>";
			}
/*
		} else if ($row['host_id']==$memberId) {
			
			$licenseId=$hostInfo['license_id'];

			$license=new VLicense($licenseId);
			$licInfo=array();
			if ($license->Get($licInfo)!=ERR_NONE) {
				ShowError("License not found");
				return;
			}

			$onStartClick='';
			if ($licInfo['trial']=='Y') {
				$num=$licInfo['max_att'];
				$format=_Text("Your trial account allows you to have %d participants per meeting.");
				$theMsg=sprintf($format, $num);
				$onStartClick="onclick=\"alert('$theMsg'); return true;\"";
			}

			if ($row['status']=='STOP') {				
				$viewBtn="<img src=\"$startIcon\">&nbsp;".$gText['M_START'];
//				$meetingUrl=$startUrl."&start=1&meeting=".$row['access_id']."&title=".rawurlencode($row['title']);
				$meetingUrl=$startUrl."&start=1&meeting=".$row['access_id'];
				$btn.="<li><a $onStartClick target=${GLOBALS['TARGET']} href=\"$meetingUrl\">$viewBtn</a></li>";			
			} elseif (VMeeting::IsMeetingStarted($row)) {
				$viewBtn="<img src=\"$resumeIcon\">&nbsp;".$gText['M_RESUME'];
//				$meetingUrl=$startUrl."&resume=1&meeting=".$row['access_id']."&title=".rawurlencode($row['title']);
				$meetingUrl=$startUrl."&resume=1&meeting=".$row['access_id'];
				$btn.="<li><a target=${GLOBALS['TARGET']} href=\"$meetingUrl\">$viewBtn</a></li>";
				
				$viewBtn="<img src=\"$endIcon\">&nbsp;".$gText['M_END'];
				$btn.="<li><a href=\"$endUrl&meeting=".$row['access_id']."\">$viewBtn</a></li>";
			}
*/		
		}
		$btn.="</ul>";
		
		echo "<td class=\"m_but m_but2\">$btn</td>\n";
		echo "</tr>\n";
	}
}

echo "</table>";

/*
if ($GLOBALS['SUB_PAGE']!=PG_HOME_RECORDINGS)
	$listPage=$meetingsPage;
else
	$listPage=$recordingsPage;
*/

$listPage=$meetingsPage;
if ($select!='')
	$listPage.="&select=".$select;
if ($search!='')
	$listPage.="&search=".$search;
	
ShowPageNavBar($listPage, $start, $offset, $total, $itemsPerPage, $maxPages, $pageNavInc);

?>
