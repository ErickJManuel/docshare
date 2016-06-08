<?php 

// (c)Copyright 2004, Persony, Inc. All rights reserved.
/*
	function startElement($parser, $name, $attrs) { 
		global $attendee;
		if ($name=="attendee" && sizeof($attrs)) { 
			while (list($k, $v) = each($attrs)) { 
				$attendee[$k]=$v;
			}
		} else if ($name=="attendeeinfo" && sizeof($attrs)) { 
			while (list($k, $v) = each($attrs)) { 
				$attendee[$k]=$v;
			}
		}
	} 
	function endElement($parser, $name) { 
	} 
*/

	define("CALLID_PREFIX", "i");

	class TeleParticipant {
		var $CallId='';
		var $ParticipantNumber='';
		var $CallerName='';
		var $CreationTime='';
		var $Muted='';
		var $ParticipantType='';
		var $CallType='';
		var $CallState='';
		var $ActiveTalker='';
		var $inWeb=false;
	};

	$participants=array();
	$current_tag='';
	$itemIndex=0;
	$inItem=false;
	$current_part=null;

	function start_participant_tag($parser, $name, $attribs) {
		global $current_tag, $itemIndex, $inItem, $participants, $current_part;
		$current_tag = $name;
		if ($name=='Participant') {
			$inItem=true;
			//		$itemIndex=count($participants);
			$current_part=new TeleParticipant();
		}
	}

	function end_participant_tag($parser, $name) {
		global $current_tag, $itemIndex, $inItem, $participants, $current_part;
		$current_tag = '';
		if ($name=='Participant') {
			$inItem=false;
			// ParticipantNumber may have duplicates so can't use it as an array index
			// use CallId, which is unique
			$participants[$current_part->CallId]=$current_part;
//			$num=$current_part->ParticipantNumber;
//			if ($num!='Unavailable' && $num!='')
//				$participants[$num]=$current_part;
//			else
//				$participants["i".$current_part->CallId]=$current_part;
		}
	}
	function parse_participant($parser, $data) {
		global $current_tag, $itemIndex, $inItem, $participants, $current_part;
		
		switch ($current_tag) {
			case "CallId":
				if ($inItem)
					$current_part->CallId=$data;
				break;
			case "CallerName":
				if ($inItem)
					$current_part->CallerName=$data;
				break;
			case "ParticipantNumber":
				if ($inItem) {
					$current_part->ParticipantNumber=$data;
				}
				break;
			case "CreationTime":
				if ($inItem)
					$current_part->CreationTime=$data;
				break;
			case "Muted":
				if ($inItem)
					$current_part->Muted=$data;
				break;
			case "CallType":
				if ($inItem)
					$current_part->CallType=$data;
				break;
			case "CallState":
				if ($inItem)
					$current_part->CallState=$data;
				break;
			case "ActiveTalker":
				// disable this because it is not supported by the conf bridge
				//if ($inItem)
				//	$current_part->ActiveTalker=$data;
				break;
			case "ParticipantType":
				if ($inItem)
					$current_part->ParticipantType=$data;
				break;
		}
	}

	function GetParticipantXml($part)
	{
//		$xml="<ThinkEngine\n";
		$xml="<callerinfo\n";	// give it a genric name since we support more than TEN
		$xml.="ParticipantNumber=\"".htmlspecialchars($part->ParticipantNumber)."\"\n";
		$xml.="Muted=\"".htmlspecialchars($part->Muted)."\"\n";
		$xml.="CallId=\"".htmlspecialchars($part->CallId)."\"\n";
		$xml.="CallerName=\"".htmlspecialchars($part->CallerName)."\"\n";
		$xml.="CallState=\"".htmlspecialchars($part->CallState)."\"\n";
		$xml.="CallType=\"".htmlspecialchars($part->CallType)."\"\n";
		$xml.="ActiveTalker=\"".htmlspecialchars($part->ActiveTalker)."\"\n";
		$xml.="/>\n";
		return $xml;
	}

	
	function GetAttendeeXml($userid, $active, $startTime, $endTime, $breakTime, $username, $userip, $useremail, $usertype,
		$drawing, $isHost, $isPresenter, $emoticon, $webcam, $camTime, $callerId, $lastCallerId, $teleAttXml) {
		$xml="<attendee userid=\"".$userid."\"\n";
		
		$xml.="active=\"".htmlspecialchars($active)."\"\n";
		$xml.="startTime=\"".htmlspecialchars($startTime)."\"\n";
		$xml.="endTime=\"".htmlspecialchars($endTime)."\"\n";
		$xml.="breakTime=\"".htmlspecialchars($breakTime)."\"\n";
		$xml.="drawing=\"".htmlspecialchars($drawing)."\"\n";
		$xml.="isHost=\"".htmlspecialchars($isHost)."\"\n";
		$xml.="isPresenter=\"".htmlspecialchars($isPresenter)."\"\n";
		$xml.="emoticon=\"".htmlspecialchars($emoticon)."\"\n";
		$xml.="webcam=\"".htmlspecialchars($webcam)."\"\n";
		$xml.="camTime=\"".htmlspecialchars($camTime)."\"\n";
		$xml.=">\n";
		
//		$xml.=$attData."\n";
		
		// write out the web attendee info	
		$xml.="<attendeeinfo\n";
		$xml.="fullname=\"".htmlspecialchars($username)."\"\n";
		$xml.="ip=\"".htmlspecialchars($userip)."\"\n";
		$xml.="email=\"".htmlspecialchars($useremail)."\"\n";
		$xml.="type=\"".htmlspecialchars($usertype)."\"\n";
		$xml.="callerid=\"".htmlspecialchars($callerId)."\"\n";
		$xml.="lastCallerid=\"".htmlspecialchars($lastCallerId)."\"\n";
		$xml.="/>\n";
		
		// if this is also a teleconference participant, write out the call info
		if ($teleAttXml!='')
			$xml.=$teleAttXml."\n";
		
		$xml.="</attendee>\n";				
		return $xml;
		
	}
	
	function Output($data) {
		global $output;
		if (isset($output))
			$output.=$data;
		else
			echo $data;
	}
		

	$includeFile='vinclude.php';
	if (isset($gScriptDir))
		$includeFile=$gScriptDir.$includeFile;
	@include_once($includeFile);
	
	@include_once($gHostFile); //defined in vinclude.php
	
	// this file may be included by another PHP script
	// The embedding PHP should define input parameters in $VARS array
	// If $output is defined, the output data will be written to it.
	// Otherwise, it is written to stdout
	
//	$userip=GetIP();

	$theDir="evt/";
	if (isset($VARS['evtdir']))
		$theDir=$VARS['evtdir'];
	elseif (isset($GET_VARS['evtdir']))
		$theDir=$GET_VARS['evtdir'];
	elseif (isset($gSessionDir))
		$theDir=$gSessionDir."/";

	$fromId="";
	if (isset($VARS['from']))
		$fromId=$VARS['from'];
	elseif (isset($GET_VARS['from']))
		$fromId=$GET_VARS['from'];

//	$maxTimeDelay=$gMaxAttDelay; // seconds before an attendee deemed gone
	
	$first=0;
	if (isset($VARS['first']))
		$first=$VARS['first'];
	elseif (isset($GET_VARS['first']))
		$first=$GET_VARS['first'];
		
	$maxAtt=9999;
	if (isset($VARS['maxAttendees']))
		$maxAtt=$VARS['maxAttendees'];
	elseif (isset($GET_VARS['maxAttendees']))
		$maxAtt=$GET_VARS['maxAttendees'];

	$countOnly=false;
	if (isset($VARS['countOnly']))
		$countOnly=true;
	elseif (isset($GET_VARS['countOnly']))
		$countOnly=true;
		
	$showAllAtt=false;
	if (isset($VARS['allAttendees']))
		$showAllAtt=true;
	elseif (isset($GET_VARS['allAttendees']))
		$showAllAtt=true;

	$format='xml';
	if (isset($VARS['format']))
		$format=$VARS['format'];
	elseif (isset($GET_VARS['format']))
		$format=$GET_VARS['format'];

/*	
	// getting the attendees from the database
	$getAttUrl=$gServerUrl."?cmd=GET_ATTENDEE_LIST&meeting="
		.$meetingId."&attendee_id=".$fromId;
		
	if ($countOnly=='1')
		$getAttUrl.="&count=1";

	if ($response = file_get_contents($getAttUrl)) {
		if (strpos($response, "<error")!==false)
			ErrorExit($response);
	} else {
		ErrorExit("Can't get response from ".$getAttUrl);
	}
	
	echo $response;
	exit();
*/

	// track the number of concurrent attendess on this server and report the number to the management server
	$configFile="../site_config.php";
	$trackerIncFile='vtracker.php';
	if (isset($gScriptDir))
		$trackerIncFile=$gScriptDir.$trackerIncFile;
	require_once($trackerIncFile);

	$attDir="../attendees/";
	if (isset($gScriptDir)) {
		$configFile=$gScriptDir.$configFile;
		$attDir=$gScriptDir.$attDir;
	}
	@include_once($configFile);

	// meeting_id is required but may not be passed in (e.g. the iPhone app)
	// get meeting_id from the directory name because the script should be called under the meeting directory
	$meetingId=basename(getcwd());
	if (isset($VARS['meeting_id']))
		$meetingId=$VARS['meeting_id'];
	elseif (isset($GET_VARS['meeting_id']))
		$meetingId=$GET_VARS['meeting_id'];
	elseif (isset($GET_VARS['meetingId']))
		$meetingId=$GET_VARS['meetingId'];
		
	$serverId='';
	if (isset($VARS['server_id']))
		$serverId=$VARS['server_id'];
	elseif (isset($GET_VARS['server_id']))
		$serverId=$GET_VARS['server_id'];

	$fromName="";
	if (isset($VARS['from_name']))
		$fromName=$VARS['from_name'];
	elseif (isset($GET_VARS['from_name']))
		$fromName=$GET_VARS['from_name'];

	$teleNum='';
	if (isset($VARS['tele_num']))
		$teleNum=$VARS['tele_num'];
	elseif (isset($GET_VARS['tele_num']))
		$teleNum=$GET_VARS['tele_num'];

	$teleMcode='';
	if (isset($VARS['tele_mcode']))
		$teleMcode=$VARS['tele_mcode'];
	elseif (isset($GET_VARS['tele_mcode']))
		$teleMcode=$GET_VARS['tele_mcode'];
		
	
	// if 'no_report' is set, this is a request from a slave server.
	// Track all requests from the same slave server as a single entry so we don't duplicate them
	if (isset($_GET['no_report'])) {
		$attTracker=new VTracker($attDir, $serverUrl);
		$remoteIp=GetIP();
		$trackId=$remoteIp."/".$meetingId;
		$attData="serverip=$remoteIp&meetingid=$meetingId&serverid=$serverId";
		$attTracker->AddValue($trackId, $attData);
	} else if ($fromId!='') {
		$attTracker=new VTracker($attDir, $serverUrl);
		$userip=GetIP();
		$fromName=rawurlencode($fromName);
		$attData="userid=$fromId&username=$fromName&userip=$userip&serverid=$serverId&meetingid=$meetingId";
		$attTracker->AddValue($fromId, $attData);
	}

	// get a list of teleconference participants into the $participants array
	$callerCount=0;
//	if ($teleServerId!='' && $teleNum!='' && $teleMcode!='') {
//		$getCallerUrl=$gServerUrl."?cmd=GET_TELE_PARTICIPANTS&teleserver_id="
	if ($meetingId!='' && $teleNum!='' && $teleMcode!='') {
		$getCallerUrl=$gServerUrl."?cmd=GET_TELE_PARTICIPANTS&meeting_id="
			.$meetingId."&phone=".urlencode($teleNum)."&confid=".urlencode($teleMcode);
				
		if ($response = file_get_contents($getCallerUrl)) {			
			$xml_parser = xml_parser_create("UTF-8"); 
			xml_parser_set_option($xml_parser, XML_OPTION_CASE_FOLDING, false);
			xml_set_element_handler($xml_parser, "start_participant_tag", "end_participant_tag");
			xml_set_character_data_handler($xml_parser, "parse_participant");
			xml_parse($xml_parser, $response, true);
			xml_parser_free($xml_parser);
			$callerCount=count($participants);
		} else {
//			die("ERROR couldn't get $getCallerUrl");
		}
		
	}	

	// getting a list of web attendees from the local directory
	$attendee=array();

	if (!isset($output)) {	
		header('Pragma: private');
		header('Cache-control: private, must-revalidate');
	}

	if ($format=="xml") {
		if (!isset($output))		
			header("Content-Type: text/xml");
		Output($gXMLHeader."\n");
	} else {
		if (!isset($output))		
			header("Content-Type: text/plain");
	}
	
	if (!$countOnly && $format=='xml') {
		Output("<attendeelist>\r\n");
	}
		
	if ($format!='xml')
		Output("OK\n");
	
	$attCount=0;
	$liveAttCount=0;
	if ($dh = @opendir($theDir.$gAttDir)) { 
	
		$fileIndex=0;
		while (($file = readdir($dh)) !== false) { 
			$filepath=$theDir.$gAttDir.$file;
			if (!IsAttendeeFile($file))
				continue;

			if ($fileIndex>=$first && $fileIndex<($first+$maxAtt)) {				
				@include_once($filepath);
				
				if (isset($_userid)) {
					$readOK=true;
				} else
					$readOK=false;

/*				
				$xml_parser = xml_parser_create("UTF-8"); 
				xml_parser_set_option($xml_parser, XML_OPTION_CASE_FOLDING, 0);
				xml_set_element_handler($xml_parser, "startElement", "endElement"); 

				$theid="";
				$theip="";
				$theTime=0;
				$theName='';
				$readOK=FALSE;
				$ifp= @fopen($filepath, "r");
				$content="";
				if ($ifp) {
					$content=fread($ifp, filesize($filepath));
					$attendee['userid']='';
					$attendee['userip']='';
					$attendee['endTime']='';
					$attendee['fullname']='';
					
					if (xml_parse($xml_parser, $content, feof($ifp))) { 
						$readOK=TRUE;
						$theid=$attendee['userid'];
						$theip=$attendee['userip'];
						$theTime=$attendee['endTime'];
						$theName=$attendee['fullname'];
					}		
					fclose($ifp);
				}
				xml_parser_free($xml_parser); 
*/				
				if ($readOK) {
					$curTime=time();
					if ($_userid==$fromId) {
						//touch($filepath);
						$_modTime=$curTime;
						if ($_webcam=='true' && $_camStartTime>0) {
							$_camTime+=($curTime-$_camStartTime);
							$_camStartTime=$curTime;
						}
						WriteAttendeeFile($filepath, $_useragent, $_userip, $_userid, $_username, $_useremail, $_usertype,
							$_startTime, $_modTime, $_breakTime, $_drawing, $_isHost, $_isPresenter, $_emoticon, $_webcam, $_callerId, 
							$_lastCallerId, $_callId, $_camStartTime, $_camTime, $_inMeeting, $_serverId, true);
						
					}
						
//					$modTime=filemtime($filepath);
//					if ($modTime<$theTime)
//						$modTime=$theTime;

/*					
					if ($theid==$fromId) {
						$search="endTime=\"".$theTime;
						$replace="endTime=\"".$curTime;
						$newContent=str_replace($search, $replace, $content);
						$content=$newContent;
						$theTime=$curTime;
					
						$ifp=@fopen($filepath, "w");
						if ($ifp) {
							fwrite($ifp, $content);
							fclose($ifp);
							@chmod($filepath, 0777);
						}
					}
*/					
					// write the attendee to the output list
					// if we are asked to show all attendees or the attendee is within the time delay	
					$isAttLive=	($curTime-$_modTime)<=$gMaxAttDelay;
					if (isset($_inMeeting) && $_inMeeting=="false")	// defined in the include file
						$isAttLive=false;
					if ($showAllAtt || $isAttLive) {
						$attCount++;
						if ($isAttLive)
							$liveAttCount++;
							
						if (!$countOnly) {
							// merge the web attendees with teleconference participants
							// if the web attendee has calledId set, check if that caller appears
							// in the $participants array (teleconference callers)
							$partXml='';
							$callerContent='';
							if (isset($participants[$_callId])) { 
								$participants[$_callId]->inWeb=true;
								$partXml=GetParticipantXml($participants[$_callId]);
								$callerContent.="callerId=".str_replace(",", "", $participants[$_callId]->ParticipantNumber);
								$callerContent.=",callId=".str_replace(",", "", $participants[$_callId]->CallId);										
								$callerContent.=",callMuted=".str_replace(",", "", $participants[$_callId]->Muted);
								$callerContent.=",activeTalker=".str_replace(",", "", $participants[$_callId]->ActiveTalker);
							}
							if ($_lastCallerId!='') {
								if ($callerContent!='')
									$callerContent.=",";
	
								$callerContent.="lastCallerId=".str_replace(",", "", $_lastCallerId);
							}

							$activeStr='false';
							if ($isAttLive)
								$activeStr='true';

							if ($format=='xml') {
								$content=GetAttendeeXml($_userid, $activeStr, $_startTime, $_modTime, $_breakTime, $_username, $_userip, $_useremail, $_usertype,
									$_drawing, $_isHost, $_isPresenter, $_emoticon, $_webcam, $_camTime, $_callerId, $_lastCallerId, $partXml);
							} else {
								// make sure there is no comma in the name or email
								$_username=str_replace(",", ";", $_username);
								$_useremail=str_replace(",", ";", $_useremail);
								$content="userid=$_userid,active=$activeStr,startTime=$_startTime,modTime=$_modTime,breakTime=$_breakTime,username=$_username,userip=$_userip,useremail=$_useremail,usertype=$_usertype,";
								$content.="drawing=$_drawing,isHost=$_isHost,isPresenter=$_isPresenter,emoticon=$_emoticon,webcam=$_webcam,camTime=$_camTime,serverId=$_serverId";
								if ($callerContent!='')
									$content.=",".$callerContent;
							}
							Output($content."\n");
						}
					}
				}
			}
			$fileIndex++;

		} 
		closedir($dh);
			
	}
	
	// write out all tele-only participants
	if ($callerCount>0) {
		foreach ($participants as $part) {
			if ($part->inWeb==false) {
				if ($format=='xml') {
					$uid='';
					$xml="<attendee userid=\"$uid\" ";
					$xml.="startTime=\"".htmlspecialchars($part->CreationTime)."\" >\n";
					$xml.=GetParticipantXml($part);
					$xml.="</attendee>\n";								
					Output($xml."\n");
				} else {
					$callerContent="active=true,startTime=".str_replace(",", "", $part->CreationTime);
					$callerContent.=",callerId=".str_replace(",", "", $part->ParticipantNumber);
					$callerContent.=",callId=".str_replace(",", "", $part->CallId);
					$callerContent.=",callMuted=".str_replace(",", "", $part->Muted);
					$callerContent.=",activeTalker=".str_replace(",", "", $part->ActiveTalker);
					Output($callerContent."\n");					
				}
			}			
		}
	}

	if (!$countOnly) {
		if ($format=='xml')
			Output("</attendeelist>");

	} else {
		if ($format=='xml')
			Output("<attendeelist count=\"${attCount}\" />");
		else
			Output($attCount);
	}
		
	// if I am the host of the meeting, send a heart beat to keep the session alive and also update the session max. concurrent attendee count
	if ($meetingId!='' && $fromId!='' && IsHost($fromId, $theDir)) {
		$sessionUrl=$gServerUrl."?cmd=SET_SESSION&meeting="
			.$meetingId."&attendee_id=".$fromId."&attendee_count=".$liveAttCount;
			
		@file_get_contents($sessionUrl);
		
	}

?>
