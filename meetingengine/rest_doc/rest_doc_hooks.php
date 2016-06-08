<?php
/**
 * Persony Web Conferencing 2.0
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2008 Persony, Inc.
 * @version     2.0
 * 
 */

include_once("dbobjects/vhook.php");


GetArg('sub1', $obj);

if ($obj=='') {
	
	$examples=array(
		"The following PHP example allows an event to proceed.",
		"The following PHP example aborts an event.",
		"The following PHP example redirects the user to a custom page.",
		);
		
	$hookPage=$GLOBALS['BRAND_URL']."?page=".PG_ADMIN_API_HOOKS;
	if (SID!='')
		$hookPage.='&'.SID;
	
	$xml=file_get_contents("rest_doc/hook_response.xml");
	$xml=htmlspecialchars($xml);

?>

Hooks are callback HTTP URLs on the client server invoked by the system when certain events occur, such as when a meeting is about to start or end.
The client hooks for the site are set in this page:
<ul>
<li><a href='<?php echo $hookPage?>'>Set API Hooks</a> (Admin account is required.)</li>
</ul>

<p>
When certain events occur, the system calls the client hooks, if they are defined, with HTTP GET requests and pass in certain CGI paramters.
A client hook should return an XML response that contains a status code, which should be one of:
<ul>
<li><b>200</b>: OK. The system will continue the event.</li>
<li><b>300</b>: Redirect. The system will continue the event but redirect the event to a client page provided in the response.</li>
<li><b>400</b>: Abort. The system will abort the event about to occur and display a message to the user.
The code is only applicable to events that have not already occurred.</li>
</ul>

The API hooks are called even if an event is triggered by an API object. For instance, using the "meeting" API object to start a meeting will result in
the "start_meeting" and "meeting_started" API hooks to be called. If the "start_meeting" hook returns the '400' code to abort, the API object will return
an error message to the caller. However, the "redirect" URL returned by the "meeting_started" hook is ignored if the event is triggered by an API object.
<p>
The XML response should follow the format below:
<p>
'hook_response.xml'
<div class='text-box'>
<pre>
<?php echo $xml?>
</pre>
</div>
<p>
<ul>
<li>[CODE] should be either '200', '300' or '400'.</li>
<li>[MESSAGE] should be a string that will be displayed to the user if code '400' is returned.</li>
<li>[LINK] should be a redirect url if code '300' is returned.</li>
</ul>
<hr size='1'>
<?php

$count=count($examples);

for ($i=0; $i<$count; $i++) {
	
	$num=$i+1;
	$text=$examples[$i];
	$exp=file_get_contents("rest_doc/hook_example".$num.".php");
	$exp=htmlspecialchars($exp);
	print <<<END
<div class='heading2'>Example $num</div>
$text<p>
<div class='text-box'>
<pre>
$exp
</pre>
</div>
END;
}



	
} else {
	
	$synopsis=$s_hook_info[$obj];

?>

<div><?php echo $synopsis?></div>
<br>

<div class='heading3'>Input paramters</div>	
<table cellspacing="0" class="meeting_list" >
<tr>
	<th class="tl pipe" width="120px"><?php echo $gText['M_PARAMETER']?></th>
	<th class="tr"><?php echo $gText['M_DESCRIPTION']?></th>
</tr>
<?php
foreach ($s_hook_params[$obj] as $key => $value) {
	print <<<END
<tr>
	<td class="m_param">$key</td>
	<td class="m_desc">$value</td>
</tr>
END;
}

?>
</table>
<br>

<div class='heading3'>Response</div>	
<table cellspacing="0" class="meeting_list" >
<tr>
	<th class="tl pipe" width="120px"><?php echo $gText['M_CODE']?></th>
	<th class="tr"><?php echo $gText['M_DESCRIPTION']?></th>
</tr>
<?php
foreach ($s_hook_codes[$obj] as $key => $value) {
	print <<<END
<tr>
	<td class="m_param">$key</td>
	<td class="m_desc">$value</td>
</tr>
END;
}

?>
</table>

<br>

<?php
}

?>

