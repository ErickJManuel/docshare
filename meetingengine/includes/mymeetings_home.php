<?php
/**
 * Persony Web Conferencing 2.0
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2010 Persony, Inc.
 * @version     2.0
 * 
 */
// The script will exit if it's run as the top-level script, but will not exit if it's included from another script. 
if (isset($_SERVER['SCRIPT_FILENAME']) && realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME'])) exit;

require_once("includes/meetings_common.php");
require_once("dbobjects/vobject.php");
require_once("dbobjects/vmeeting.php");

$tz=GetSessionValue("time_zone");

$brandUrl=$GLOBALS['BRAND_URL'];

$memberId=GetSessionValue('member_id');
$target=$GLOBALS['TARGET'];

$meetingOpts='';
$query="host_id = '$memberId' AND status<>'REC' AND (scheduled='N' OR CURDATE()<=DATE(date_time)) AND brand_id ='".$GLOBALS['BRAND_ID']."'";

$errMsg=VObject::SelectAll(TB_MEETING, $query, $result, 0, 100, "*", "date_time", false);
if ($errMsg!='')
	ShowError($errMsg);
else {
	$num_rows = mysql_num_rows($result);
	if ($num_rows==0) {
		$url=$brandUrl."?page=MEETINGS_ADD";
		$text=_Text("Add a Meeting");
		$meetingOpts="<a target=$target href='$url'>$text</a>";
		
	} else {
		$meetingOpts="<select id='meeting_id'>\n";
		while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
			$title=$row['title'];
			if (strlen($title)>30)
				$title=substr($title, 0, 30);
			$title=htmlspecialchars($title);
			$mid=$row['access_id'];
			$meetingOpts.="<option value='$mid'>".$title."</option>\n";
			
		}
		$meetingOpts.="</select>";
	}
}

$query="host_id = '$memberId' AND status<>'REC' AND (scheduled='Y' AND CURDATE()=DATE(date_time)) AND brand_id ='".$GLOBALS['BRAND_ID']."'";
$errMsg=VObject::SelectAll(TB_MEETING, $query, $result, 0, 100, "*", "date_time", false);
if ($errMsg!='')
	ShowError($errMsg);
else {
	$num_rows = mysql_num_rows($result);
	if ($num_rows==0) {
		$todayList="<div class='mpage_text'>"._Text("No meeting is scheduled for today")."</div>";
	} else {
		$todayList="<table class='mpage_list'>\n";
		while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
			$title=$row['title'];
			if (strlen($title)>28) {
				$title=substr($title, 0, 25);
				$title.="...";
			}
			$title=htmlspecialchars($title);
			$dtime=$row['date_time'];
			GetTimeZoneByDate($tz, $dtime, $tzName, $dtz);
			VObject::ConvertTZ($dtime, 'SYSTEM', $dtz, $tzTime);
			if ($tzTime!='') {
				$url=$brandUrl."?meeting=".$row['access_id'];
				list($date, $time)=explode(" ", $tzTime);
				//if ($date==date('Y-m-d')) {
					list($hh, $mm, $ss)=explode(":", $time);
					$todayList.="<tr class='mpage_list_item'><td class='mpage_list_label'>".H24ToH12($hh, $mm)."</td>";
					$todayList.="<td class='mpage_list_val'><a target='$target' href='$url'>$title</a></td></tr>\n";
				//}
			}		
		}
		$todayList.="</table>\n";
	}	
}

$helpPage=$brandUrl."?page=HELP";
$presenterPage=$brandUrl."?page=HOME_DOWNLOAD";


?>

<script type="text/javascript">
<!--
<?php
	if ($GLOBALS['TARGET']=='_self')
		$location="location";
	else
		$location="self.parent.location";
?>


	function getMeetingId() {
		var elem=document.getElementById('meeting_id');
		if (elem && elem.options.length>0) {
			if (elem.selectedIndex>=0)
				index=elem.selectedIndex;
			else
				index=0;
			var id=elem.options[index].value;
			return id;
		}
		return 0;
	}

	function invite() {

		var id=getMeetingId();
		if (id>0) {
			var page="<?php echo $brandUrl?>"+"?page=HOME_INVITE&meeting="+id;
			//self.parent.location=page;
			<?php echo "$location=page;\n" ?>
		} else {
			alert("No meeting is selected. Please add a meeting first.");
		}
	}
	
	function start() {
		var id=getMeetingId();
		if (id>0) {		
			var page="<?php echo $brandUrl?>"+"?page=MEETINGS_START&start=1&redirect=1&meeting="+id;
			//self.parent.location=page;
			<?php echo "$location=page;\n" ?>
		} else {
			add();
		}
	}
	
	function add() {
		var page="<?php echo $brandUrl?>"+"?page=MEETINGS_ADD";
		//self.parent.location=page;
		<?php echo "$location=page;\n" ?>
	}

//-->
</script>

<div class="mpage_row">
<div class="mpage_col">
<div class="heading1"><?php echo _Text("Start a meeting now")?></div>
<div class='mpage_label'><?php echo _Text("Select a meeting:")?></div>
<?php echo $meetingOpts?>
<div class="mpage_button1"><a href="javascript:void(0)" onclick='start(); return false;'><img class="mpage_img1" src="themes/start-button.png"></a></div>

<div class="mpage_button2" style="padding-left: 50px" ><a href="javascript:void(0)" onclick='invite(); return false;'><img class="mpage_img2" src="themes/mail-icon.png"><?php echo _Text("Invite")?></a></div>
</div>
<div class="mpage_col">
<div class="heading1"><?php echo _Text("Schedule a meeting")?></div>

<div style="height: 35px"></div>
<div class="mpage_button1"><a href="javascript:void(0)" onclick='add(); return false;'><img class="mpage_img1" src="themes/calendar_icon.png"></a></div>
<div class='mpage_title'><?php echo _Text("Today's Meetings")?></div>
<?php echo $todayList?>
</div>
</div>

<div class="mpage_row">
<div class="mpage_col">
<div class="mpage_button2"><a target=_blank href="<?php echo $helpPage?>"><img class="mpage_img2" src="themes/help_icon.png"><?php echo _Text("Help")?></a></div>
</div>

<div class="mpage_col">
<div class="mpage_button2"><a target=<?php echo $target?> href="<?php echo $presenterPage?>"><img class="mpage_img2" src="themes/presentation.png"><?php echo _Text("Presenter Client")?></a></div>
</div>

</div>