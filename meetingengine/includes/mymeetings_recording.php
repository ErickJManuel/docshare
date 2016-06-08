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
require_once("dbobjects/vobject.php");
require_once("dbobjects/vmeeting.php");
require_once("dbobjects/vfolder.php");

$memberId=GetSessionValue('member_id');
$memberBrand=GetSessionValue('member_brand');
$userId=$memberId;
$user=new VUSer($userId);
	
$maxListChars=100;

$tz=GetSessionValue("time_zone");
/*
GetArg('set_tz', $setTz);
if ($setTz==1) {
	$user->GetValue('time_zone', $tz);
	if ($tz!='')
		SetSessionValue('time_zone', $tz);	
}
*/
GetSessionTimeZone($tzName, $dtz);
//$timezones=GetTimeZones($tz);

$thisPage=$_SERVER['PHP_SELF'];
$retPage=$GLOBALS['BRAND_URL']."?page=".PG_MEETINGS_RECORDINGS;
if (SID!='')
	$retPage.="&".SID;
$retPage=VWebServer::EncodeDelimiter1($retPage);

$reportIcon="themes/preview.gif";
$reportBtn="<img src=\"$reportIcon\">".$gText['M_REPORTS'];
$reportUrl=$GLOBALS['BRAND_URL']."?page=".PG_MEETINGS_REPORT;
if (SID!='')
	$reportUrl.="&".SID;

$deleteUrl=VM_API."?cmd=DELETE_MEETING&return=$retPage";
if (SID!='')
	$deleteUrl.="&".SID;

$editPage=$GLOBALS['BRAND_URL']."?page=".PG_MEETINGS_DETAIL;
if (SID!='')
	$editPage.="&".SID;

$regUrl=$GLOBALS['BRAND_URL']."?page=".PG_MEETINGS_REGIST;
if (SID!='')
	$regUrl.="&".SID;
	
	
$endRecUrl=VM_API."?cmd=END_RECORDING&return=$retPage";
if (SID!='')
	$endRecUrl.="&".SID;


$invitePage=$GLOBALS['BRAND_URL']."?page=".PG_HOME_INVITE;
if (SID!='')
	$invitePage.="&".SID;
	
$downloadIcon="themes/download.gif";

	
$userInfo=array();
$user->Get($userInfo);
if (!isset($userInfo['id'])) {
	ShowError("User not found");
	return;
}

$showFolderId=$moveFolderId='';
//print_r($_POST);
if (isset($_POST['submit_select'])) {
	GetArg("folder_id", $showFolderId);
} else if (isset($_POST['submit_move'])) {
	GetArg("folder_id", $moveFolderId);
} else if (isset($_POST['submit_delete']) || isset($_POST['submit_rename'])) {
		
	GetArg("folder_id", $folderId);
	
	$folder=new VFolder($folderId);
	$folder->GetValue('owner_id', $ownerId);
	if ($ownerId!=$memberId) {
		ShowError("Not authorized.");
		return;
	}
	
	if (isset($_POST['submit_rename'])) {
		$folderInfo=array();
		if (GetArg('name', $arg))
			$folderInfo['name']=$arg;

		$query="name='".VObject::MyAddSlashes($folderInfo['name'])."' AND brand_id='$memberBrand' AND owner_id='$memberId'";
		VObject::Count(TB_FOLDER, $query, $total);
		if ($total>0) {
			ShowError("The folder '".$folderInfo['name']."' already exists.");
			return;
		}
		if ($folder->Update($folderInfo)!=ERR_NONE) {
			ShowError($folder->GetErrorMsg());
			return;
		}	
		$showFolderId=$folderId;
	} else if (isset($_POST['submit_delete'])) {
		$query="folder_id='$folderId'";
		$errMsg=VObject::SelectAll(TB_MEETING, $query, $result);
		if ($errMsg!='') {
			ShowError($errMsg);
			return;
		}
		
		// move all meetings in the folder to the top folder (id=0)
		$aMeetingInfo=array();
		$aMeetingInfo['folder_id']=0;		
		while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
			$aMeeting=new VMeeting($row['id']);
			$aMeeting->Update($aMeetingInfo);
		}
		
		if ($folder->Drop()!=ERR_NONE) {
			ShowError( $folder->GetErrorMsg());	
			return;
		}	
		$showFolderId='0';
			
	}
}

$query="`owner_id`='$userId'";
//$checked= ($showFolderId=='-1')?'selected':'';
//$prepend="<option $checked value=\"-1\">".$gText['M_ALL_FOLDERS']."</option>\n";
$checked= ($showFolderId=='0')?'selected':'';
$prepend="<option $checked value=\"0\">"."Top folder"."</option>\n";
$prepend.="<option value=''>"."----------"."</option>\n";
$onChange="return SelectShowFolder();";
$folderOpts=VObject::GetFormOptions(TB_FOLDER, $query, "folder_id", "name", $showFolderId, $prepend, 'select_folder', $onChange);

$checked= ($moveFolderId=='-1')?'selected':'';
$prepend="<option $checked value=\"-1\">"."New folder"."</option>\n";
$checked= ($moveFolderId=='0')?'selected':'';
$prepend.="<option $checked value=\"0\">"."Top folder"."</option>\n";
$prepend.="<option value=''>"."----------"."</option>\n";
$onChange="return SelectMoveFolder();";
$toFolderOpts=VObject::GetFormOptions(TB_FOLDER, $query, "folder_id", "name", $moveFolderId, $prepend, 'move_folder', $onChange);

$folderName='Untitled';

//$retPage=$thisPage."?page=".PG_MEETINGS_RECORDINGS."&set_tz=1";
$retPage=$thisPage."?page=".PG_MEETINGS_RECORDINGS."&submit_select=Go&folder_id=$showFolderId";
if (SID!='')
	$retPage.="&".SID;

$retPage=VWebServer::EncodeDelimiter1($retPage);

$postMovePage=VM_API."?cmd=SET_MEETINGS&return=$retPage";
if (SID!='')
	$postMovePage.="&".SID;
	
//$confirmDelMsg="delete \'".addslashes($row['title'])."\'";

// get the web server url
//$webServerId=VUser::GetWebServerId($userInfo);
/*
if ($meetingInfo['webserver_id']!=0)
	$webServerId=$meetingInfo['webserver_id'];
else
	$webServerId=VUser::GetWebServerId($userInfo);

$webServer=new VWebServer($webServerId);
$serverInfo=array();
if ($webServer->Get($serverInfo)!=ERR_NONE) {
	ShowError($webServer->GetErrorMsg());
	return;		
}
$serverUrl=VWebServer::AddSlash($serverInfo['url']);
*/

?>


<script type="text/javascript">
<!--
function SelectShowFolder()
{
	var form=document.selectfolder_form;

	if (form.folder_id.selectedIndex>1) {
		document.getElementById('set_folder').style.visibility='visible';
	} else {
		document.getElementById('set_folder').style.visibility='hidden';
	}

	return true;
}

function SelectMoveFolder()
{
	var form=document.movetofolder_form;
	if (form.folder_id.selectedIndex==0) {
		document.getElementById('to_folder_name').style.display='inline';
	} else {
		document.getElementById('to_folder_name').style.display='none';
	}

	return true;
}

function ConfirmDelete()
{
	var form=document.selectfolder_form;
	var msg='';
	if (form.folder_id.selectedIndex>1) {
		msg="Do you want to delete '"+form.folder_id.options[form.folder_id.selectedIndex].text+"'?\n";
		msg+="All items in the folder will be moved to the Top folder."	
	}
	return confirm(msg);
}


//-->
</script>
<form method="POST" action="" name="selectfolder_form">
<table width="100%">
<tr>
<td>
<?php echo $gText['M_SHOW']?>:
<?php echo $folderOpts?>
<input type="submit" name="submit_select" value="<?php echo $gText['M_GO']?>">
</td>
<td id="set_folder">
Rename folder to:
<input type="text" name="name" value="" size='15' maxlength='31'>
<input type="submit" name="submit_rename" value="Rename">
<input onclick="return ConfirmDelete()" type="submit" name="submit_delete" value="Delete Folder">
</td>
</tr>
</table>
</form>
<br>
<form method="POST" action="<?php echo $postMovePage?>" name="movetofolder_form">
<div>
Move selections to:
<?php echo $toFolderOpts?>
<span id="to_folder_name"><?php _Text("Folder name")?>:
<input type="text" name="folder_name" value="<?php echo $folderName?>" size='15' maxlength='31'>
</span>
<input type="submit" name="submit_move" value="Move">
</div>

<?php

	$dtStr=date('Y-m-d H:i:s');
	VObject::ConvertTZ($dtStr, 'SYSTEM', $dtz, $localDtStr);

	$query="host_id = '$userId' AND status = 'REC' AND brand_id ='".$GLOBALS['BRAND_ID']."'";
	
	if ($showFolderId!='-1' && $showFolderId!='') {	
		$query.=" AND folder_id='$showFolderId'";
	} else {
		$query.=" AND folder_id='0'";	
	}

	$errMsg=VObject::SelectAll(TB_MEETING, $query, $result, 0, 100, "*", "date_time", true);
	if ($errMsg!='') {
		ShowError($errMsg);
		return;
	}
	
	$idStr=$gText['M_ID'];
	$titleStr=$gText['M_TITLE'];
	$dtStr=$gText['M_DATE']."/".$gText['M_TIME'];

print <<<END
	<table cellspacing="0" class="meeting_list" >
	<tr>
		<th class="tl pipe" >&nbsp;</th>
		<th class="pipe" >${gText['M_ID']}</th>
		<th class="pipe" >${gText['M_TITLE']}</th>
		<th class="pipe" style="width:75px">${gText['M_DATE']}</th>
		<th class="pipe" style="width:65px">&nbsp;</th>
		<th class="tr" style="width:160px">&nbsp;</th>
	</tr>
END;

	$num_rows = mysql_num_rows($result);
	if ($num_rows==0) {
		echo "<tr><td>&nbsp;</td>\n";
		echo "<td class=\"m_id\">&nbsp;</td>\n";
		echo "<td class=\"m_info\">&nbsp;</td>\n";
		echo "<td class=\"m_date\">&nbsp;</td>\n";
		echo "<td class=\"m_tool\">&nbsp;</td>\n";
		echo "<td class=\"m_but\">&nbsp;</td></tr>\n";
	}		
	while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
		echo "<tr>\n";
		
		$checkStr="<input type='checkbox' name='selected_meetings[]' value='".$row['id']."'>";
		echo "<td>".$checkStr."</td>\n";	
		echo "<td class=\"m_id\">".$row['access_id']."</td>\n";
		$desc=htmlspecialchars($row['description']);
		if (strlen($desc)>43) {
			$desc=substr($desc, 0, 43-3);
			$desc.="...";
		}
		
		$title=htmlspecialchars($row['title']);
		if (strlen($title)>32) {
			$title=substr($title, 0, 32-3);
			$title.="...";
		}
		
		if ($title=='')
			$title='[empty]';

		$titleStr="<a target=${GLOBALS['TARGET']} href=\"".$editPage."&meeting=".$row['access_id']."\">".$title."</a>";			
		echo "<td class=\"m_info\"><ul>\n";
		echo "<li class=\"m_title\">".$titleStr."</li>\n";
		
		if ($desc=='')
			$desc='&nbsp;';
		echo "<li class=\"m_desc\">".$desc."</li>\n";
		
		$icons=GetMeetingIcons($row);
		if ($icons=='')
			$icons='&nbsp;';

		if ($icons!='')
			echo "<li class=\"m_icon\">".$icons."</li>\n";

		echo "</ul></td>\n";
		
		$timeStr='&nbsp;';
		if ($row['scheduled']=='Y' || $row['status']=='REC') {
			$dtime=$row['date_time'];
			VObject::ConvertTZ($dtime, 'SYSTEM', $dtz, $tzTime);
			if ($tzTime!='') {
				list($date, $time)=explode(" ", $tzTime);
				if ($i!=0) {
					$timeStr="<li><b>".$date."</b></li>";
				} else
					$timeStr='';
				list($hh, $mm, $ss)=explode(":", $time);

				$timeStr.="<li>".H24ToH12($hh, $mm)."</li>";
				$timeStr.="<li><i>".$row['duration']."</i></li>";
			}
		}
		
		$progStr='';
		$viewBtn="<img src=\"$startIcon\">&nbsp;".$gText['M_PLAYBACK'];
		$meeting=new VMeeting($row['id']);
		$meeting->GetViewerUrl(false, $playUrl);
		$playUrl=$GLOBALS['BRAND_URL']."?page=".PG_HOME_JOIN."&redirect=1&meeting=".$row['access_id'];
		if (SID!='')
			$playUrl.="&".SID;
		
		$ready=true;
		if ($row['rec_ready']=='N') {
			$ready=false;
		}
		
		if ($ready)	{
			$btn="<li><a target=${GLOBALS['TARGET']} href=\"$playUrl\">$viewBtn</a></li>";
			
			if (defined('ENABLE_DOWNLOAD_RECORDING') && constant('ENABLE_DOWNLOAD_RECORDING')=="1" &&
				(!isset($gBrandInfo['rec_download']) || $gBrandInfo['rec_download']=='Y')) 
			{
				if ($row['login_type']=='NAME' || $row['login_type']=='NONE') {
					$text=_Text("Download Recording");
					$downloadUrl=SITE_URL."api.php?cmd=DOWNLOAD_RECORDING&meeting_id=".$row['access_id'];
					$btn.="<li><a target=_blank href=\"$downloadUrl\"><img src=\"$downloadIcon\"> $text</a></li>";
				}		
			}
			//if (defined('ENABLE_DOWNLOAD_AUDIO') && constant('ENABLE_DOWNLOAD_AUDIO')=="1") {
			if (defined('ENABLE_DOWNLOAD_AUDIO') && constant('ENABLE_DOWNLOAD_AUDIO')=="1" &&
					(!isset($gBrandInfo['rec_download']) || $gBrandInfo['rec_download']=='Y')) {
				
				if ($row['audio']=='Y' && ($row['login_type']=='NAME' || $row['login_type']=='NONE')) {			
					$text=_Text("Download Audio");
					$downloadUrl=SITE_URL."api.php?cmd=DOWNLOAD_RECORDING&audio=1&meeting_id=".$row['access_id'];
					$btn.="<li><a target=_blank href=\"$downloadUrl\"><img src=\"$downloadIcon\"> $text</a></li>";
				}
			}
			$procRecUrl=$GLOBALS['BRAND_URL']."?page=".PG_MEETINGS_RECORDINGS."&process=2&meeting=".$row['access_id'];
			if (SID!='')
				$procRecUrl.="&".SID;
			$btn.="<li>[<a target=${GLOBALS['TARGET']} href='$procRecUrl'>"._Text("Re-process")."</a>]</li>";

		} else {
			
			$btn="<li>"._Text("Not ready")."</li>";
			$procRecUrl=$GLOBALS['BRAND_URL']."?page=".PG_MEETINGS_RECORDINGS."&process=1&meeting=".$row['access_id'];
			if (SID!='')
				$procRecUrl.="&".SID;
			$btn.="<li>[<a target=${GLOBALS['TARGET']} href='$procRecUrl'>"._Text("Process")."</a>]</li>";

		}

		echo "<td class=\"m_date\"><ul>".$timeStr.$progStr."</ul></td>\n";		

		$actionIcons='';
		$actionIcons.="<a target=${GLOBALS['TARGET']} href=\"$invitePage&meeting=".$row['access_id']."\">$inviteBtn</a>\n";
		$actionIcons.="<br><a target=${GLOBALS['TARGET']} href=\"$reportUrl&meeting=".$row['access_id']."\">".$reportBtn."</a>\n";
//			if ($row['login_type']=='REGIS')
//				$actionIcons.="<a target=${GLOBALS['TARGET']} href=\"$regUrl&id=".$row['id']."\">$regBtn</a>\n";
		$format=$gText['M_CONFIRM_DELETE'];
		$msg=sprintf($format, "\'".addslashes($row['title'])."\'");
	
		$actionIcons.="<br><a onclick=\"return MyConfirm('".$msg."')\" target=${GLOBALS['TARGET']} href=\"$deleteUrl&id=".$row['id']."\">$deleteBtn</a>\n";

		echo "<td class=\"m_tool\">".$actionIcons."</td>\n";			
		echo "<td class=\"m_but\"><ul>$btn</ul></td>\n";
		echo "</tr>\n";
	}


	echo "</table>\n";
	
	
	if (GetArg('process', $arg)) {
		$endPageFile="includes/process_audio.php";
	}
?>
</form>

<script type="text/javascript">
<!--
	SelectShowFolder();
	SelectMoveFolder(); 
//-->
</script>