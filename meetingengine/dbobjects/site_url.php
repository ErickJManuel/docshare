<?php
/**
 * Persony Web Conferencing 2.0
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2008 Persony, Inc.
 * @version     2.0
 * 
 */

require_once("server_config.php");

if (isset($_SERVER['HTTPS']) && SSL_SERVER_URL!='')
	define('SITE_URL', SSL_SERVER_URL);
else
	define('SITE_URL', SERVER_URL);

?>