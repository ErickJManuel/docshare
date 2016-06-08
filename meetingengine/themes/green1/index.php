<?php
/**
 * Persony Web Conferencing 2.0
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2008 Persony, Inc.
 * @version     2.0
 * 
 */

header('Content-type: text/css');
// allow browser caching to work
header("Expires: ".gmdate("D, d M Y H:i:s", (time()+900)) . " GMT"); 


require_once("const.php");
require_once("../default_style.php");
require_once("../page.php");
?>
