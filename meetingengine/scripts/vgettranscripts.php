<?php
/**
 * Persony Web Conferencing 2.0
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2008 Persony, Inc.
 * @version     2.0
 * 
 */
	$includeFile='vinclude.php';
	if (isset($gScriptDir))
		$includeFile=$gScriptDir.$includeFile;
	include_once $includeFile;
	include_once $gHostFile;
	
	$evtDir="evt/";
	if (isset($_GET['evtdir']))
		$evtDir=$_GET['evtdir'];
	elseif (isset($gSessionDir))
		$evtDir=$gSessionDir."/";
		
	if ($evtDir=='')
		die("ERROR missing parameter 'evtdir'");
		
	if ($evtDir[strlen($evtDir)-1]!='/')
		$evtDir.="/";
	
	$hostId='';	
	if (isset($_GET['hostid']))
		$hostId=$_GET['hostid'];
		
	if ($hostId=='')
		die("ERROR missing parameter 'hostid'");
	
	// list events to record in the transcript
	$gEvents=array(
		'StartMeeting',
		'EndMeeting',
		'StartWhiteboard',
		'EndWhiteboard',
		'AddWhiteboard',
		'DeleteWhiteboard',
		'EndPresentation',
		'StartScreenSharing',
		'PauseScreenSharing',
		'EndScreenSharing',
		'StartRecording',
		'EndRecording',
		'SendSlide',
		'SendMessage',
		'SendURL',
		'SendDocument',
		'StartMedia',
		'EndMedia',
		'LockMeeting',
		'UnlockMeeting',
		'RemoveAttendee',	// for recording kick-out events. 
		'SendQuestion',
		'EndQuestion',
		'SendResults',
		);
	
	$attendees=array();
	
	$evtDir.="vevents/";			
	$filePrefix="evt";
	$fileExt=".xml";
		
	// must add this for IE7 to work on SSL download
	header('Pragma: private');
	header('Cache-control: private, must-revalidate');	
	header("Content-Type: text/xml");
	
	echo $gXMLHeader;

	echo "\n<transcripts>\n";
    
	$i=0;
	while(1) {
		$filename=$evtDir.$filePrefix.$i.$fileExt;
		$ifp= @fopen($filename, "r");
		if (!$ifp)
			break;
							
		$content=fread($ifp, filesize($filename));
		$eventType=$eventTime=$eventData='';
		$eventFrom=$eventTo=$eventFromName=$eventUrl='';

		$xml_parser = xml_parser_create("UTF-8"); 
		xml_parser_set_option($xml_parser, XML_OPTION_CASE_FOLDING, 0);
		xml_set_element_handler($xml_parser, "startEvtElement", "endEvtElement"); 
		xml_set_character_data_handler($xml_parser, "parseEvtData");
		
		if (xml_parse($xml_parser, $content, true)) {
			
			// only process events sent to everyone or to/from the host
			if ($eventTo=='' || $eventTo==$hostId || $eventFrom==$hostId) {
				
				if ($eventType=='AddAttendee' && $eventFrom!='' && $eventFromName!='') {
					// store the attendee name indexed by their id
					$attendees[$eventFrom]=$eventFromName;							
				}
				
				if ($eventType=='RemoveAttendee' && $eventTo=='') {
					// Ignore this because they are from someone leaving voluntarily				
				} else if (in_array($eventType, $gEvents)) {
					
					if ($eventFromName!='')
						$fromName=htmlspecialchars($eventFromName);
					else if ($eventFrom!='' && isset($attendees[$eventFrom]))
						$fromName=htmlspecialchars($attendees[$eventFrom]);
					else
						$fromName='';
						
					// look up the attendee name from the id
					if ($eventTo!='' && isset($attendees[$eventTo]))
						$toName=htmlspecialchars($attendees[$eventTo]);
					else
						$toName='';
					
					$data=htmlspecialchars($eventData);
					$itemXml="<item type=\"$eventType\" time=\"$eventTime\" senderId=\"$eventFrom\" senderName=\"$fromName\"";
					if ($eventTo!='')
						$itemXml.=" targetId=\"$eventTo\" targetName=\"$toName\"";
					if ($eventUrl!='') {
						$url=htmlspecialchars($eventUrl);
						$itemXml.=" url=\"$url\"";
					}
					
					$itemXml.=">$data</item>";
					
					echo $itemXml."\n";
					
				}
					
			}
		}
		fclose($ifp);
		xml_parser_free($xml_parser); 

		$i++;

	}
	echo "</transcripts>\n";

	exit();

	function startEvtElement($parser, $name, $attrs) { 
		global $eventType, $eventTime;
		global $eventData, $eventFrom, $eventTo, $eventFromName, $eventUrl;
		global $current_tag;
		
		$current_tag=$name;
		if ($name=="event" && isset($attrs['type'])) { 
			if (isset($attrs['type']))
				$eventType=$attrs['type'];
			if (isset($attrs['time']))
				$eventTime=$attrs['time'];
			if (isset($attrs['from']))
				$eventFrom=$attrs['from'];
			if (isset($attrs['to']))
				$eventTo=$attrs['to'];
			if (isset($attrs['fromname']))
				$eventFromName=$attrs['fromname'];				
		
		} else if ($name=='documentinfo') {
			if (isset($attrs['filename']))
				$eventData=$attrs['filename'];
		} else if ($name=='slide' || $name=='media') {
			if (isset($attrs['title']))
				$eventData=$attrs['title'];
			if (isset($attrs['slideurl']))
				$eventUrl=$attrs['slideurl'];
			elseif (isset($attrs['url']))
				$eventUrl=$attrs['url'];
			
		} else if ($name=='message') {
			$eventData='';
		} else if ($name=='question') {
			if (isset($attrs['text']))
				$eventData=$attrs['text'];
		} else if ($name=='result') {
			$eventData='';
			if (isset($attrs['total']))
				$eventData.="total=".$attrs['total'].";";
		
			$labels=array("A", "B", "C", "D", "E");
			for ($i=1; $i<=5; $i++) {
				$keyname="result_".$i;
				if (isset($attrs[$keyname]))
					$eventData.=$labels[$i-1]."=".$attrs[$keyname].";";
			}
		}
	} 
	function endEvtElement($parser, $name) { 
		global $current_tag;
	
	} 
	function parseEvtData($parser, $data) {
		global $current_tag;
		global $eventData;

		switch ($current_tag) {
			case "message":
				$eventData.=$data;
				break;
		}
	}

?>