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

require_once("dbobjects/vuser.php");
require_once("dbobjects/vgroup.php");
require_once("dbobjects/vmeeting.php");
require_once("includes/meetings_common.php");
require_once("dbobjects/vbackground.php");
require_once("dbobjects/vviewer.php");
require_once("dbobjects/vimage.php");

if (!GetArg('room', $userId)) {
	ShowError("Room not set.");
	return;
}

if ($userId=='' || $userId=='0') {
	ShowError("Room not found.");
	return;	
}

//GetLocalTimeZone($tzName, $tz);
//GetSessionTimeZone($tzName, $tz);
$tz='';

$userInfo=array();
$errMsg=VObject::Find(TB_USER, 'access_id', $userId, $userInfo);
if ($errMsg!='') {
	ShowError($errMsg);
	return;
} else if (!isset($userInfo['id'])) {
	ShowError("User id not found.");
	return;
}
$user=new VUser($userInfo['id']);

$userName=htmlspecialchars($user->GetFullName($userInfo));
$roomName=htmlspecialchars($userInfo['room_name']);
if ($roomName=='')
	$roomName=htmlspecialchars($gText['M_MY_ROOM']);
$roomDesc=htmlspecialchars($userInfo['room_description']);
$roomDesc=str_replace("\n", "<br>", $roomDesc);
$public=$userInfo['public'];
$userId=$userInfo['id'];
	
if (GetArg('page', $arg)) {
	$hostUrl=$GLOBALS['BRAND_URL']."?page=".PG_HOME_USER."&user=".$userInfo['access_id'];
	$registerPage=$GLOBALS['BRAND_URL']."?page=".PG_HOME_REGISTER;
} else {
	$hostUrl=$GLOBALS['BRAND_URL']."?user=".$userInfo['access_id'];
	$registerPage=$GLOBALS['BRAND_URL']."?page=".PG_REGISTER;
}
if (SID!='') {
	$hostUrl.="&".SID;
	$registerPage.="&".SID;
}

$pictFile="themes/person.jpg";	
if ($userInfo['pict_id']>0) {
	$pict=new VImage($userInfo['pict_id']);
	if ($pict->GetValue('file_name', $pictFile)!=ERR_NONE) {
		ShowError ($pict->GetErrorMsg());
	} else {
//		$pictFile=DIR_IMAGE.$pictFile;
		$pictFile=VImage::GetFileUrl($pictFile);
	}
}
$userPict="<img width=96 height=96 alt=\"$pictFile\" id=\"back_img\" src=\"$pictFile\">";

$logoFile='';
if ($userInfo['logo_id']!='0') {
	$logo=new VImage($userInfo['logo_id']);
	if ($logo->GetValue('file_name', $logoFile)!=ERR_NONE) {
		ShowError ($logo->GetErrorMsg());
	} else {
		$logoFile=VImage::GetFileUrl($logoFile);
	}
}

$attendUrl=$GLOBALS['BRAND_URL']."?page=".PG_HOME_JOIN."&redirect=1";
if (SID!='')
	$attendUrl.="&".SID;

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

<table>
<tr>
<td id='room_info_l'>
<?php echo $userPict?>
</td>
<td id='room_info_r'>
<div class="heading1"><?php echo $roomName?></div>

<div class='meeting_host'>
<?php echo $gText['MD_HOSTED_BY']?> <a target=<?php echo $GLOBALS['TARGET']?> href='<?php echo $hostUrl?>'><?php echo $userName?></a>
</div>

<div class='meeting_desc'>
<?php echo $roomDesc?>
</div>
</td>
</tr>
</table>


<?php
/* <div class='meetings_tz1'><?php echo $gText['MD_TIME_ZONE']?>: <?php echo $tzName?></div> */

for ($i=0; $i<2; $i++) {

	echo "<table cellspacing='0' class='meeting_list' >";

	echo "<tr><th colspan=3 class='tl pipe'>";

	if ($i==0) {
		$query="host_id = '$userId' AND ( public = 'Y' OR public = 'S') AND status<>'REC' AND brand_id ='".$GLOBALS['BRAND_ID']."'";
		// only show the current meetings (scheduled date >= today)
		$query.=" AND (scheduled='N' OR CURDATE()<=DATE(date_time))"; 
		echo $gText['HOME_MEETINGS'];
	} else {
		$query="host_id = '$userId' AND ( public = 'Y' OR public = 'S') AND status='REC' AND brand_id ='".$GLOBALS['BRAND_ID']."'";
		echo $gText['HOME_RECORDINGS'];
	}

	echo "</th></tr>";
		
	$errMsg=VObject::SelectAll(TB_MEETING, $query, $result, 0, 100, "*", "date_time");
	if ($errMsg!='') {
		ShowError($errMsg);
		return;
	}

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
		if (strlen($desc)>60) {
			$desc=substr($desc, 0, 60-3);
			$desc.="...";
		}
		$desc=htmlspecialchars($desc);
		$title=htmlspecialchars($row['title']);
		if (GetArg('page', $arg))
			$meetingPage=$GLOBALS['BRAND_URL']."?page=".PG_HOME_MEETING."&meeting=".$row['access_id'];
		else
			$meetingPage=$GLOBALS['BRAND_URL']."?meeting=".$row['access_id'];
		if (SID!='')
			$meetingPage.="&".SID;

		$titleStr="<a target=${GLOBALS['TARGET']} href=\"".$meetingPage."\">".$title."</a>";			
		echo "<td class='m_info'><ul>";
		echo "<li class=\"m_title\">".$titleStr."</li>";
				
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
			elseif ($userInfo['time_zone']!='')
				$stz=$userInfo['time_zone'];
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
			/*
			if ($tzTime!='') {
				list($date, $time)=explode(" ", $tzTime);
				list($hh, $mm, $ss)=explode(":", $time);
				$timeStr="<b>".$date."</b><br>";
				$timeStr.="".H24ToH12($hh, $mm)."<br>";
				list($hh, $mm, $ss)=explode(":", $row['duration']);
				$timeStr.="<i>($hh:$mm)</i>";
			}*/
		}

		$progStr='';
		if (VMeeting::IsMeetingStarted($row)) {
require_once("dbobjects/vsession.php");
require_once("includes/meetings_common.php");
			if (VMeeting::IsMeetingInProgress($row))
				$progStr="<br><span class=\"progress\">${gText['M_IN_PROGRESS']}</span>";
			else
				$progStr="<br><span class=\"progress\">${gText['M_IDLE']}</span>";

		}
		
		echo "<td class=\"m_date m_date2\">".$timeStr.$progStr."</td>\n";

		$btn="<ul>\n";
		if ($row['login_type']=='REGIS')
			$btn.="<li><a target=${GLOBALS['TARGET']} href=\"$registerPage&meeting=".$row['access_id']."\">$registerBtn</a></li>";

		//$meeting=new VMeeting($row['id']);
		//$meeting->GetViewerUrl(false, $attendUrl);
		$meetingUrl=$attendUrl."&meeting=".$row['access_id'];
		
		if ($row['status']=='REC')
			$viewBtn="<img src=\"$startIcon\">&nbsp;".$gText['M_PLAYBACK'];
		else
			$viewBtn="<img src=\"$startIcon\">&nbsp;".$gText['M_JOIN'];

		$btn.="<li><a target=_blank href=\"$meetingUrl\">$viewBtn</a></li>";
	//		$btn.="<li><a target=${GLOBALS['TARGET']} href=\"$attendUrl&meeting=".$row['access_id']."\">$attendBtn</a></li>";
		$btn.="</ul>";
		
		echo "<td class=\"m_but m_but2\">$btn</td>\n";
		echo "</tr>\n";
	}
}

echo "</table>";

$query="host_id ='".$userInfo['id']."' AND public='Y'";
$meetingId=0;
$authorId=GetSessionValue('member_id');
$hostId=$userInfo['id'];
$publicComment=$userInfo['public_comment'];
$retPage=$GLOBALS['BRAND_URL']."?page=".PG_HOME_ROOM."&room=".$userInfo['access_id'];
if (SID!='')
	$retPage.="&".SID;
$retPage=VWebServer::EncodeDelimiter1($retPage);

GetArg('post_comment', $postComment);

require_once("includes/comments.php");

?>
