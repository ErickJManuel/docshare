<?php
/**
 * Persony Web Conferencing 2.0
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2009 Persony, Inc.
 * @version     2.0
 * 
 * Return a slave server to use for cascading if it is available
 * Return "OK" if no slave server is to use
 * Return "OK\nserver_url=...&server_ip=..."	if a slave server is to use
 * 
 */

// The script will exit if it's run as the top-level script, but will not exit if it's included from another script. 
if (isset($_SERVER['SCRIPT_FILENAME']) && realpath(__FILE__) == realpath($_SERVER['SCRIPT_FILENAME'])) exit;

require_once("api_includes/common.php");

if (!defined("ENABLE_CACHING_SERVERS") || ENABLE_CACHING_SERVERS=='0') {
	// don't use a slave server
	exit();
}


require_once("dbobjects/vmeeting.php");
require_once("dbobjects/vuser.php");

GetArg("meeting_id", $meetingId);
//GetArg("remote_ip", $remoteIp);
//$rips=explode(".", $remoteIp);

// find the hosting server for the meeting

$meetingFile=VMeeting::GetSessionCachePath($meetingId);
$accessDb=true;

if (VMeeting::IsSessionCacheValid($meetingFile)) {
	@include_once($meetingFile);
	if (isset($_meetingStatus) && ($_meetingStatus!='STOP' && $_meetingStatus!='REC')) {
		$accessDb=false;
	}		
}

if (!$accessDb) {
	if ($_meetingStatus=='REC' || !isset($_serverList)) {
		// don't redirect to a slave server if this is a recording playback or there are no cascading servers
		exit();
	}
	
	$serverList=&$_serverList;

} else {
	$errMsg=VObject::Find(TB_MEETING, 'access_id', $meetingId, $meetingInfo);

	if (!isset($meetingInfo['id']))
		API_EXIT(API_ERR, "Meeting is not found.");
		
	if ($meetingInfo['status']=='REC') {
		exit();
	}
	
	if ($meetingInfo['webserver_id']!=0) {
		$webServerId=$meetingInfo['webserver_id'];
	} else {
		$hostId=$meetingInfo['host_id'];
		
		$hostInfo=array();
		$host=new VUser($hostId);
		if ($host->Get($hostInfo)!=ERR_NONE)
			exit();

		$webServerId=VUser::GetWebServerId($hostInfo);
	}
		
	$webServer=new VWebServer($webServerId);
	$serverInfo=array();
	if ($webServer->Get($serverInfo)!=ERR_NONE) {
		exit();
	}
	
	$serverList=array();
	$serverList[0]['url']=$serverInfo['url'];
	$serverList[0]['name']=$serverInfo['name'];
	$serverList[0]['max_connections']=$serverInfo['max_connections'];
	$serverList[0]['id']=$webServerId;
	
	if ($serverInfo['slave_ids']!='') {
		$slaveIds=explode(',', $serverInfo['slave_ids']);
		$i=0;
		foreach ($slaveIds as $aid) {
			if ($aid!='' && $aid>0) {
				$i++;
				$aserver=new VWebServer($aid);
				$aserverInfo=array();
				$aserver->Get($aserverInfo);
				$serverList[$i]['url']=$aserverInfo['url'];
				$serverList[$i]['name']=$aserverInfo['name'];
				$serverList[$i]['max_connections']=$aserverInfo['max_connections'];
				$serverList[$i]['id']=$aid;
			}
		}
	}
}

$count=0;
if (is_array($serverList))
	$count=count($serverList);

if ($count<=1) {
	// there is only one server and no slave servers
	exit();
} else {

	require_once("api_includes/stats.php");
	// cascading servers are used. 
	// respond with alist of servers for the client that the client is allowed to connect to.
	// go through every server and include ones that have not reached the max. connections
	// the first one is the master server
	$resp='';
	for ($i=0; $i<$count; $i++) {
		// first check if the max. connection for the server is exceeded
/*		$max=intval($serverList[$i]['max_connections']);

		if ($max<0) {
			// this server has no max. connection limit. include it
			$resp="\nserver_$i=".$serverList[$i]['url'];
			break;
		} else if ($max==0) {
			// this server does not accept any connections. skip it
			continue;	
		}
*/		
		$items=parse_url($serverList[$i]['url']);
			
		// find the number of connected attendees for this site on the server
		$conn=GetAttendees($items['host']);
		//echo ("conn=$conn"); exit();		
		
		if ($resp!='')
			$resp.="&";
	
		$resp.="url_$i=".$serverList[$i]['url'];
		$resp.="&name_$i=".$serverList[$i]['name'];
		$resp.="&connect_$i=".$conn;
		$resp.="&max_$i=".$serverList[$i]['max_connections'];
		$resp.="&id_$i=".$serverList[$i]['id'];
		
	}
	
	echo $resp;

/*
	if ($i==0) {
		// found the master server
		echo ("OK");
		
	} else if ($i<$count) {
		// found a slave server
		$server=$serverList[$i]['url'];
		$items=parse_url($serverList[$i]['url']);
		$ip=gethostbyname($items['host']);
		// return the slave server url and ip address
		echo ("OK\nserver_url=$server\nserver_ip=$ip");
		
	} else {
		// couldn't find any server that matches; use the master
		echo ("OK");
	}
*/
}

exit();

?>