<?php
/**
 * Persony Web Conferencing 2.0
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2008 Persony, Inc.
 * @version     2.0
 * 
 */

require_once("rest/prestapi.php");

PRestAPI::GetObjectNames($objects);
echo "<ul class='bullet_list'>\n";
foreach ($objects as $obj) {
	$thePage=$apiPage."&topic=".$gText['REST_OBJECTS']."&sub1=$obj";
	echo "<li><a target='$target' href='$thePage'>$obj</a></li>\n";
}		
echo "</ul>\n";
		
	
?>