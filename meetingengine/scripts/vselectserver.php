<?php
/**
 * @package     VShow
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2007 Persony, Inc.
 * @version     2.0
 */

// server speed test results are returned from the client
// return response is url_0=...&time_0=...&max_0&connect_0&ur_1=...&time_1=...
// where ur_x are server urls and time_x are latency for each server in ms
// max_x are numbers of max. connections allowed for each server
// connect_x are numbers of current connected participants on the server
// ur_0 is the master server
$minLatency=9999;
$foundi=-1;
// check if the user has manually selected a server to use
if (isset($_GET['selected']))
	$foundi=intval($_GET['selected']);
	
// no server is selected.
// use the master server (0) if its connection limit is not reached
// otherwise, go through the list of slave servers to find the one with the shortest latency
if ($foundi<0) {
	for ($i=0; $i<10; $i++) {
		if (isset($_GET["url_".$i])) {
			if (isset($_GET['max_'.$i]))
				$max=intval($_GET['max_'.$i]);
			else
				$max=-1;
			if (isset($_GET['connect_'.$i]))
				$conn=intval($_GET['connect_'.$i]);
			else
				$conn=0;
			if ($i==0 && ($conn<$max || $max==-1)) {
				// always use the master server if the max connection is not reached.
				$foundi=0;
				break;
			} else if ($i>0 && isset($_GET['time_'.$i]) && $_GET['time_'.$i]!='') {
				// select a slave server with the lowest latency and max connection is not reached
				if ($conn<$max || $max==-1) {
					$latency=intval($_GET['time_'.$i]);
					if ($latency<$minLatency) {
						$minLatency=$latency;
						$foundi=$i;
					}
				}
			}
		}		
	}
}
//var_dump($_GET);
//die ("found $foundi $minLatency");

if ($foundi>=0)
	$serverId=$_GET['id_'.$foundi];
	
$args=$_SERVER['QUERY_STRING'];

$keys=array("response", "check", "selected");
foreach ($keys as $akey) {
	if (isset($_GET[$akey])) {
		$keystr=$akey."=".$_GET[$akey];
		$args=str_replace($keystr, "", $args);
	}
}
$key2s=array("time", "url", "connect", "max", "id");
// remove all the servers from the query string
foreach ($key2s as $akey2) {
	for ($i=0; $i<10; $i++) {
		$tkey=$akey2."_".$i;
		if (isset($_GET[$tkey])) {
			$keystr="&".$tkey."=".$_GET[$tkey];
			$args=str_replace($keystr, "", $args);
		}			
	}
}
$args=str_replace("&&", "&", $args);
	
// a slave server is determined to have the lowest latency
if ($foundi>0 && isset($_GET['url_'.$foundi])) {

	$cachingServer=$_GET['url_'.$foundi];
	
	$redirectUrl=$cachingServer."viewer.php?server=".$cachingServer."&server_id=".$serverId.$args;

} else {
	$redirectUrl="viewer.php?server_id=".$serverId.$args;
	
}
//die ($redirectUrl);
header("Location: $redirectUrl");
exit();

?>