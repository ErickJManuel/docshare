<?php
/**
 * @package     VShow
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2007 Persony, Inc.
 * @version     2.0
 */
require_once("vobject.php");
/**
* Const
*/
define("VSESSION_TIMEOUT", 35);

/**
 * @package     VShow
 * @access      public
 */
class VSession extends VObject 
{
	/**
	 * Constructor
	 * @param integer set $id to non-zero to associate the object with an existing row.
	 */	
	function VSession($id=0)
	{
		$this->VObject(TB_SESSION);
		$this->SetRowId($id);
	}
	/**
	* @static
	* @param array  column values. 
	* @return string
	*/		
	function GetXML($sessionInfo, $timeZone="+00:00")
	{
require_once("vattendee.php");
		$xml="<session id=\"".$sessionInfo['id']."\"\n";		
		
		$startTime=$sessionInfo['start_time'];
		VObject::ConvertTZ($startTime, 'SYSTEM', $timeZone, $tzTime);
		$xml.="start_time=\"".$tzTime."\"\n";
		$endTime=$sessionInfo['mod_time'];
		VObject::ConvertTZ($endTime, 'SYSTEM', $timeZone, $tzTime);
		$xml.="end_time=\"".$tzTime."\"\n";
		$xml.="meeting_title=\"".VObject::StrToXML($sessionInfo['meeting_title'])."\"\n";
		$xml.="meeting_host=\"".VObject::StrToXML($sessionInfo['host_login'])."\"\n";
		$xml.=">\n";
		
		$xml.="<attendees>\n";		
//		$xml.="max_concur_attendees=\"".$sessionInfo['max_concur_att']."\"";
//		$xml.="total_attendee_time=\"".$attTime."\"";
				
		$select="id, user_id, user_name, start_time, mod_time, break_time, (TIME_TO_SEC(TIMEDIFF(mod_time, start_time))-break_time) as duration";
		
		$query="session_id='".$sessionInfo['id']."'";
		$errMsg=VObject::SelectAll(TB_ATTENDEE, $query, $result, 0, 0, $select, "start_time");
		if ($errMsg!='') {
			return($errMsg);
		}
			
		$num_rows = mysql_num_rows($result);
/*		
		if ($num_rows==0) {
			$errMsg=VObject::SelectAll(TB_ATTENDEE_LIVE, $query, $result, 0, 0, $select, "start_time");
			if ($errMsg!='') {
				return($errMsg);
			}
		}
*/
		$totalTime=0;
		$rowCount=0;
		$lastDate='';
		while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
			$xml.="<attendee ";
			$xml.="name=\"".$row['user_name']."\" ";
			VObject::ConvertTZ($row['start_time'], 'SYSTEM', $timeZone, $tzTime);
			$xml.="start_time=\"".$tzTime."\"\n";
			VObject::ConvertTZ($row['mod_time'], 'SYSTEM', $timeZone, $tzTime);
			$xml.="end_time=\"".$tzTime."\" ";

			$xml.="break_time=\"".$row['break_time']."\" ";
			$xml.="duration=\"".$row['duration']."\" ";
			$xml.="webcam_time=\"".$row['cam_time']."\" ";
			$xml.="/>\n";			
		}
		$xml.="</attendees>\n";
		$xml.="</session>\n";
		
		return $xml;
	}
	function XmlToHtmlTranscript($xmlTranscript)
	{
		global $current_tag;
		global $reportHtml;
		global $eventType, $eventTime, $eventStartTime;
		global $eventData, $eventSender, $eventTarget;
		
		if ($xmlTranscript==null || $xmlTranscript=='')
			return '';
			
		$reportHtml="
<table cellspacing='0' class='meeting_list'>
<tr>
    <th class='pipe' width='80px'>Time</th>
    <th class='pipe'>Action</th>
</tr>
";
		$eventTime=$eventStartTime=0;
		$eventType=$eventData=$eventSender=$eventTarget=$eventUrl='';
		$current_tag='';

		$xml_parser = xml_parser_create("UTF-8"); 
		xml_parser_set_option($xml_parser, XML_OPTION_CASE_FOLDING, 0);
		xml_set_element_handler($xml_parser, "startTransElement", "endTransElement"); 
		xml_set_character_data_handler($xml_parser, "parseTransData");
		xml_parse($xml_parser, $xmlTranscript, true);
		
		$reportHtml.='</table>';
		return $reportHtml;
	}
	function XmlToHtmlPollResults($sessionId, $questions, $results)
	{
		// simplexml only works in PHP 5
		$pollXml = simplexml_load_string($results);
		if ($pollXml==false)
			return '';
			
		require_once("dbobjects/vquestion.php");
		require_once("dbobjects/vattendee.php");
		
		$quesXml = simplexml_load_string($questions);
		// get a list of questions indexed by the question id
		$questions=array();
		if ($quesXml) {
			foreach ($quesXml->children() as $ques) {
				$qid=(string)$ques->id[0];
				$questions[$qid]= $ques;
			}	
		}
		$labels=array("choice_1"=>"A", "choice_2"=>"B", "choice_3"=>"C", "choice_4"=>"D", "choice_5"=>"E");
		$ansText=_Text("Answer");
		$correctText=_Text("(correct)");
		$partText=_Text("Participant");
		$pollHtml= <<<EOD
EOD;

		// parse the polling results
		$ques=array();	
		foreach ($pollXml->children() as $aPoll) {
			if (!isset($aPoll->event[0]))
				continue;
			if (!isset($aPoll->event[0]->answer[0]["questionid"]))
				continue;
								
			$qid=(string)$aPoll->event[0]->answer[0]["questionid"];
			$qitem=$questions[$qid];
			$type=$text=$correct='';
			if ($qitem) {			
				$type=$qitem->type[0];
				$text=htmlspecialchars($qitem->question[0]);
				$correct=$qitem->correct[0];
				$answers=array($qitem->choice_1[0],$qitem->choice_2[0],$qitem->choice_3[0],$qitem->choice_4[0],$qitem->choice_5[0]);
			}
			
			// go through all events to count the votes for each choice
			$choices=array("choice_1"=>0, "choice_2"=>0, "choice_3"=>0, "choice_4"=>0, "choice_5"=>0);
			$total=0;
			if ($type=='S') {
				foreach ($aPoll->children() as $aEvent) {
					$choice=(string)$aEvent->answer[0]->choice[0];
					if (isset($choices[$choice])) {
						$choices[$choice]++;
					}
					$total++;
				}
			}
			$pollHtml.= <<<EOD
<hr>
<div class='heading3'>Q: $text</div>
EOD;

			// report multiple choice results
			if ($type=='S') {
				$pollHtml.= <<<EOD
<table cellspacing='0' class='meeting_list'>
<tr><td style='width: 500px'>&nbsp;</td><td style='width: 100px'>&nbsp;</td><td style='width: 100px'>&nbsp;</td></tr>

EOD;
				for ($i=1; $i<=5; $i++) {
					$ci="choice_".$i;
					$answer=htmlspecialchars($answers[$i-1]);
					if ($answer=='' || $answer==null)
						continue;
					if ($correct==$i)
						$answer.=" <b>".$correctText."</b>";

					$label=$labels[$ci];
					if ((int)$total>0)
						$perc=round((int)$choices[$ci]*100/(int)$total, 1);
					else
						$perc=0;
	
					if ($perc>0 && $perc<100) {
						$bar="<table class='poll_tb'><tr><td class='poll_bar' style='width: ".$perc."px;'>&nbsp;</td><td class='poll_bg' style='width: ".(100-$perc)."px;'>&nbsp;</td></tr></table>";
					} else if ($perc==0) {
						$bar="<table class='poll_tb'><tr><td class='poll_bg' style='width:100px'>&nbsp;</td></tr></table>";
					} else if ($perc>=100) {
						$bar="<table class='poll_tb'><tr><td class='poll_bar' style='width:100px'>&nbsp;</td></tr></table>";
					}
					$result=$perc."%";
					$pollHtml.= <<<EOD
	<tr>
		<td class='u_item'><b>$label</b>. $answer</td>
		<td class='u_item'>$bar</td>
		<td class='u_item'><b>$result</b></td>
	</tr>

EOD;
				}
				$pollHtml.= <<<EOD
</table>
EOD;
			}

			// report participant responses
			$pollHtml.= <<<EOD
<table cellspacing='0' class='meeting_list'>
	<tr>
		<th class='pipe' width='20px'>&nbsp;</th>
		<th class='pipe' width='150px'>$partText</th>
		<th class='pipe'>$ansText</th>
	</tr>
EOD;
			$count=0;
			$namelist=array();
			foreach ($aPoll->children() as $aEvent) {
				$count++;
				if ($count%2==0)
					$greybg="class='u_bg'";
				else
					$greybg='';
					
				$from=(string)$aEvent['from'];
				if (!isset($namelist[$from])) {
					$qy="attendee_id='$from' AND session_id='$sessionId'";
					$errMsg=VObject::Select(TB_ATTENDEE, $qy, $attInfo);
					// DB error; should log it
					if ($errMsg!='')
						return '';
					
					if (isset($attInfo['user_name']))
						$name=$attInfo['user_name'];
					else
						$name='[unknown]';
					
					$namelist[$from]=$name;
				} else {
					$name=$namelist[$from];
				}
				
				$name=htmlspecialchars($name);
					
				if (isset($aEvent->answer[0]->choice[0])) {
					$choice=(string)$aEvent->answer[0]->choice[0];
					$answer=$labels[$choice];
					if ($choice=="choice_".$correct)
						$answer.=" <b>".$correctText."</b>";
				} else if (isset($aEvent->answer[0]->text[0])) {
					$answer=htmlspecialchars((string)$aEvent->answer[0]->text[0]);
				}
				$pollHtml.= <<<EOD
	<tr $greybg>
		<td class='u_item_c'>$count</td>
		<td class='u_item'><b>$name</b></td>
		<td class='u_item'>$answer</td>
	</tr>
EOD;
			}
			$pollHtml.= <<<EOD
</table>
<br>
EOD;

		}
			
		return $pollHtml;
	}
	/**
	* 
	* @return string query
	 */	
	function GetInProgressQuery()
	{
		// We need to creat a query to return all live sessions
		// For version prior to 2.2.10.0, the mod_time in the session table is touched very 15 seconds if there is a live attendee
		// so we can use it to determine if the session is live
		// If mod_time is less than VSESSION_TIMEOUT sec from the current time, assume the session is in progress
		$oldQuery="TIME_TO_SEC(TIMEDIFF(NOW(), mod_time))<'".VSESSION_TIMEOUT."'";
		return $oldQuery;

		// From version 2.2.10, we have removed database access during a meeting so  mod_time is no longer changed during a meeting
		// We assume a session is in progress if mod_time=start_time (2.2.10+) or mod_time is within timeout (older version)
		$query="((mod_time=start_time) OR ($oldQuery))";
		return $query;
		
	}
	/**
	* 
	* @return string query
	 */	
	function GetNotInProgressQuery()
	{
		$oldQuery="TIME_TO_SEC(TIMEDIFF(NOW(), mod_time))>='".VSESSION_TIMEOUT."'";
		return $oldQuery;

		// see comment in GetInProgressQuery
		$query="((mod_time<>start_time) AND ($oldQuery))";
		return $query;
	}
	/**
	* Check if a session is in progress.
	* This only works if the moderator is in the meeting as the moderator will send the heart beat. 
	* It's possible that a session is in progress but without a moderator. 
	* The only way to know for sure is to call GetAttendees to find out if
	* there are attendees in the session. However, that requires calling the hosting server, which
	* may not be up.
	* 
	* @return boolean Return true if a session is in progress
	 */	
	function IsInProgress()
	{
		$this->mErrorCode=ERR_NONE;
		$this->mErrorMsg='';
		if ($this->mId==0) {
			$this->mErrorCode=ERR_ILL;
			$this->SetErrorMsg("id not set");
			return $this->mErrorCode;
		}
		$sessId=$this->mId;
		
		$query="id='$sessId' AND ".VSession::GetInProgressQuery();
		VObject::Count(TB_SESSION, $query, $count);
		
//		$info=array();
//		$this->Select(TB_SESSION, $query, $info);
/*
echo "query=".$query."<br>";
print_r($info);
if (isset($info['id']))
	echo "session_id=".$info['id']."<br>";
*/	
//		if (isset($info['id']) && $info['id']==$sessId)
//		if (count($info)==1)
		if ($count>0)
			return true;
		else
			return false;		
		
	}
	/**
	 * For live sessions only. Each item is an associative array for one attendee.
	 * This function should not access the database
	 * $meetingId, $phoneNum, $modCode are only required to get the tele-conf participants
	 *returns array List of attendees. 
	*/
	static function GetAttendees($meetingDirUrl, $sessionId, $requesterId, $countOnly, $allAttendees, $meetingId, $phoneNum, $modCode, &$attCount, &$attList, $maxWaitTime=5)
	{
		$meetingInfo=array();
		$meetingInfo['session_id']=$sessionId;
		$evtDir=VMeeting::GetEventDir($meetingInfo);
		$url=$meetingDirUrl."vscript.php?s=vgetattendees&evtdir=".$evtDir."/&format=text";
		if ($countOnly) {
			$url.="&countOnly=1";
		}
		// include all past attendees or only the current attendees
		if ($allAttendees) {
			$url.="&allAttendees=1";
		}
		if ($requesterId!='')
			$url.="&from=$requesterId";
					
		if ($meetingId!='' && $phoneNum!='' && $modCode!='') {
			$phoneNum=RemoveSpacesFromPhone($phoneNum);
			$modCode=RemoveNonNumbers($modCode);

			$url.="&meeting_id=$meetingId&tele_num=$phoneNum&tele_mcode=$modCode";
		}
		
		$attCount=0;
		if ($response = HTTP_Request($url, "", "GET", $maxWaitTime)) {
			// parse data
			// format OK\nkey1=value1,key2=value1\n...
			$lineList=explode("\n", $response);
			$lineCount=count($lineList);
			if ($lineCount>0 && $lineList[0]=="OK") {
				if ($countOnly) {
					if (isset($lineList[1]))
						$attCount=$lineList[1];
				} else {
					for ($i=1; $i<$lineCount; $i++) {
						if ($lineList[$i]=='')
							continue;
							
						$itemList=explode(",", $lineList[$i]);
						$itemCount=count($itemList);
						$attList[$i-1]=array();
						
						foreach($itemList as $anItem) {
							$keyVal=explode("=", $anItem);
							$keyCount=count($keyVal);
							$key='';
							$val='';
							if (isset($keyVal[0])) {
								$key=$keyVal[0];
							} 
							if (isset($keyVal[1])) {
								$val=$keyVal[1];
							}
							$attList[$i-1][$key]=$val;
						}
						$attCount++;
					}
				}
				return true;
			} else {
//				echo ("error $lineCount $lineList[0]");
			}
		} else {
//			echo ("error $url");
		}
		return false;
	}
	/**
	* 
	* @return integer error code
	 */	
/* this function is no longer used because live attendees are no longer stored in the database
* use GetAttendees instead
*/
/*
	function GetAttendeeCount(&$count)
	{
		$this->mErrorCode=ERR_NONE;
		$this->mErrorMsg='';
		if ($this->mId==0) {
			$this->mErrorCode=ERR_ILL;
			$this->SetErrorMsg("id not set");
			return $this->mErrorCode;
		}
		$timeoutSec=VSESSION_TIMEOUT;
		$sessionId=$this->mId;
//		$query="session_id = '$sessionId' AND mod_time>SUBTIME(NOW(), '0 0:0:$timeoutSec')";
		$query="session_id = '$sessionId' AND TIME_TO_SEC(TIMEDIFF(NOW(), mod_time))<'$timeoutSec'";
		$errMsg=VObject::Count(TB_ATTENDEE_LIVE, $query, $count);
		if ($errMsg!='') {
			$this->mErrorCode=ERR_ERROR;
			$this->SetErrorMsg($errMsg);
			return $this->mErrorCode;
		}
		return $this->mErrorCode;
	}
*/
	/**
	* Update the mod_time
	* @return integer error code
	 */	
	function Touch()
	{
		$this->mErrorCode=ERR_NONE;
		$this->mErrorMsg='';
		if ($this->mId==0) {
			$this->mErrorCode=ERR_ILL;
			$this->SetErrorMsg("id not set");
			return $this->mErrorCode;
		}
		$info=array();
//		$info['mod_time']='#NULL';
		$info['mod_time']='#NOW()';
		if ($this->Update($info)!=ERR_NONE) {
			$this->mErrorCode=ERR_ERROR;
			$this->SetErrorMsg($this->GetErrorMsg());
			return $this->mErrorCode;
		}
		return $this->mErrorCode;
	}
	/**
	* End the session
	* @return integer error code
	 */	
	function End(&$transcripts=null, &$pollResults=null, &$pollQuestions=null)
	{
		$this->mErrorCode=ERR_NONE;
		$this->mErrorMsg='';
		if ($this->mId==0) {
			$this->mErrorCode=ERR_ILL;
			$this->SetErrorMsg("id not set");
			return $this->mErrorCode;
		}
		$info=array();
		$info['mod_time']='#NOW()';
		if (defined('ENABLE_TRANSCRIPTS') && constant('ENABLE_TRANSCRIPTS')=='1') {
			if ($transcripts && $transcripts!='')
				$info['transcripts']=$transcripts;
		}
		
		if ($pollResults!=null)
			$info['poll_results']=$pollResults;
			
		if ($pollQuestions!=null)
			$info['poll_questions']=$pollQuestions;
		
		if ($this->Update($info)!=ERR_NONE) {
			$this->mErrorCode=ERR_ERROR;
			$this->SetErrorMsg($this->GetErrorMsg());
			return $this->mErrorCode;
		}
		return $this->mErrorCode;
	}
	
}

$eventTypeText=array(
	"StartMeeting"		=>	"starts the meeting",
	"EndMeeting"		=>	"ends the meeting",
	"StartWhiteboard"	=>	"starts a whiteboard session",
	"EndWhiteboard"		=>	"ends the whiteboard session",
	"AddWhiteboard"		=>	"adds a whiteboard",
	"DeleteWhiteboard"	=>	"deletes a whiteboard",
	"EndPresentation"	=>	"ends the slide show",
	"StartScreenSharing"=>	"starts a screen sharing session",
	"PauseScreenSharing"=>	"pauses the screen sharing session",
	"EndScreenSharing"	=>	"ends the screen sharing session",
	"StartRecording"	=>	"starts a recording",
	"EndRecording"		=>	"ends the recording",
	"StartMedia"		=>	"shares a media file",
	"EndMedia"			=>	"ends the media playback",
	"SendSlide"			=>	"shares a slide",
	"SendMessage"		=>	"sends a message",
	"SendDocument"		=>	"sends a document",
	"SendURL"			=>	"sends a URL",
	"LockMeeting"		=>	"locks the meeting",
	"UnlockMeeting"		=>	"unlocks the meeting",
	"RemoveAttendee"	=>	"sends 'remove from meeting'",
	"SendQuestion"		=>	"sends a quesiton",
	"EndQuestion"		=>	"ends the question",
	"SendResults"		=>	"share polling results",
);

function startTransElement($parser, $name, $attrs) {
	global $eventType, $eventTime, $eventStartTime;
	global $eventData, $eventSender, $eventTarget, $eventUrl;
	global $current_tag;
	
	$current_tag=$name;
	if ($name=="item" && isset($attrs['type'])) { 
		$eventType=$attrs['type'];
		if (isset($attrs['time']))
			$eventTime=(integer)$attrs['time'];
		else
			$eventTime=0;
		if (isset($attrs['senderName']))
			$eventSender=$attrs['senderName'];
		else
			$eventSender='Unknown';
		if (isset($attrs['targetName']))
			$eventTarget=$attrs['targetName'];
		else
			$eventTarget='';
		if (isset($attrs['url']))
			$eventUrl=$attrs['url'];
		else
			$eventUrl='';						
		if ($eventStartTime==0)
			$eventStartTime=$eventTime;
		$eventData='';
	}
} 
function endTransElement($parser, $name) { 
	global $current_tag;
	global $reportHtml;
	global $eventType, $eventTime, $eventStartTime;
	global $eventData, $eventSender, $eventTarget, $eventUrl;
	global $eventTypeText;
		
	if ($name=="item" && $eventType!='') {
		$typeText='';
		if (isset($eventTypeText[$eventType]))
			$typeText=$eventTypeText[$eventType];
		
		if ($typeText!='') {	
			$actionText="<b>".htmlspecialchars($eventSender)."</b> ".$typeText;
			if ($eventData!='') {
				// if eventUrl starts with http, add a link to it
				if ($eventUrl!='' && strpos($eventUrl, "http")===0)
					$actionText.=" <a href='$eventUrl'>\"".htmlspecialchars($eventData)."\"</a>";
				else
					$actionText.=" <b>\"".htmlspecialchars($eventData)."\"</b>";
			}
							
			if ($eventTarget!='')
				$actionText.=" to <b>".htmlspecialchars($eventTarget)."</b>";

			$actionText.=".";
			$offsetTime=$eventTime-$eventStartTime;
			$reportHtml.="<tr>";
			$reportHtml.="<td class=\"u_item_c\">".SecToStr($offsetTime)."</td>";
			$reportHtml.="<td class=\"u_item\">".$actionText."</td>";
			$reportHtml.="</tr>\n";
		}
	}

} 
function parseTransData($parser, $data) {
	global $current_tag;
	global $eventData;

	switch ($current_tag) {
		case "item":
			$eventData.=$data;
			break;
	}
}

?>