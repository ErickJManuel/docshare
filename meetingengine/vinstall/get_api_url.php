<?php
/**
 * @package     VShow
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2007 Persony, Inc.
 * @version     2.0
 *
 * Do not modify this file. This file will be updated automatically by vinstall.php.
 */

include_once("site_config.php");
if (isset($_GET['commandApi']))
	echo $serverUrl."api.php";
else
	echo $serverUrl."rest/";

?>