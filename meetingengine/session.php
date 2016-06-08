<?php

    session_start();
	$expTime=time()+60*60*24*365;
	$CookieInfo = session_get_cookie_params();
	if ( (empty($CookieInfo['domain'])) && (empty($CookieInfo['secure'])) ) {
	    setcookie(session_name(), session_id(), $expTime, $CookieInfo['path']);
	} elseif (empty($CookieInfo['secure'])) {
	    setcookie(session_name(), session_id(), $expTime, $CookieInfo['path'], $CookieInfo['domain']);
	} else {
	    setcookie(session_name(), session_id(), $expTime, $CookieInfo['path'], $CookieInfo['domain'], $CookieInfo['secure']);
	}
	if (isset($_GET['key']))
		$_SESSION[$_GET['key']]=$_GET['value'];

?>