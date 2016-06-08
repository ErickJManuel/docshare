<?php
/**
 * Persony Web Conferencing 2.0
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2008 Persony, Inc.
 * @version     2.0
 * 
 */

require_once("provider/common.php");

EndSession();

$redirctPage="provider.php";
header("Location: $redirctPage");

?>