<?php
/**
 * Persony Web Conferencing 2.0
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2008 Persony, Inc.
 * @version     2.0
 * 
 */

$target=$GLOBALS['TARGET'];
$apiUrl=SITE_URL."rest/";
$apiObjects=$GLOBALS['BRAND_URL']."?page=".PG_HELP_REST."&topic=".$gText['REST_OBJECTS'];
if (SID!='')
	$apiObjects.="&".SID;
	
$apiPage=$GLOBALS['BRAND_URL']."?page=".PG_ADMIN_API;
if (SID!='')
	$apiPage.="&".SID;

$sigPage=$GLOBALS['BRAND_URL']."?page=".PG_HELP_REST."&topic=".$gText['REST_SIGNATURE'];
if (SID!='')
	$sigPage.="&".SID;

$tokenPage=$GLOBALS['BRAND_URL']."?page=".PG_HELP_REST."&topic=".$gText['REST_TOKEN'];
if (SID!='')
	$tokenPage.="&".SID;

$samples=array(
		"Add and get meetings.",
		"Add a group.",
		"Add a member.",
		"Start a meeting (Download 'Use SSO...' below for complete details)",
		"Get session reports."
		);	
	
$examples=array(
	"The following example returns all meetings of the member 'jandoe@acme.com'.",
	"The following example use form post to add a new meeting to the member 'jandoe@acme.com'. Note that the following form post will add \"submit=Submit\" to the query string, which should be included in the signature computation. The signature field must be the last item in the form.",
	"The following example changes the title of a meeting.",
	);

$requests=array(
"http://license.persony.net/wc2/rest/meetings/?brand=8800101&login=jandoe@acme.com&signature=ef0110a04d30fb629af9f3f4cd26b719",
"<form method='POST' action='http://license.persony.net/wc2/rest/meeting/'> <input type='submit' name='submit' value='Submit'> <input type='hidden' name='brand' value='10023'> <input type='text' name='member_id' value='1694682'> <input type='text' name='title' value='My First Meeting'> <input type='text' name='signature' value='156471ec4e6d1cccef8b7551fec6b167'></form>",
"<form method='POST' action='http://license.persony.net/wc2/rest/meeting/'> <input type='submit' name='submit' value='Submit'> <input type='hidden' name='method' value='PUT'> <input type='hidden' name='brand' value='10023'> <input type='text' name='id' value='5942290'> <input type='text' name='title' value='My Second Meeting'>  <input type='text' name='signature' value='0f0a12195616957c05e77806be6c9348'> </form>",
	);
	
$responses=array(
"<response xmlns=\"http://schemas.persony.com/wc2/rest/1.0\">
	<meetings start='0'>
		<meeting>
			<meeting_id>3207553</meeting_id>
			<meeting_title>My Meeting 1</meeting_title>
		</meeting>

		<meeting>
			<meeting_id>5020216</meeting_id>
			<meeting_title>My Meeting 2</meeting_title>
		</meeting>
	</meetings>
</response>",
"<response xmlns=\"http://schemas.persony.com/wc2/rest/1.0\">
	<meeting>
		<id>5942290</id>
		<title>My First Meeting</title>
		<description></description>
		<status>STOP</status>
		<date_time>0000-01-01 00:00:00</date_time>

		<duration>00:00:00</duration>
		<scheduled>N</scheduled>
		<login_type>NAME</login_type>
		<password></password>
		<public>N</public>
		<public_comment>Y</public_comment>

		<use_tele>N</use_tele>
		<tele_num></tele_num>
		<tele_mcode></tele_mcode>
		<tele_pcode></tele_pcode>
	</meeting>
</response>",
"<response xmlns=\"http://schemas.persony.com/wc2/rest/1.0\">
	<meeting>
		<id>5942290</id>
		<title>My Second Meeting</title>
		<description></description>
		<status>STOP</status>
		<date_time>0000-01-01 00:00:00</date_time>

		<duration>00:00:00</duration>
		<scheduled>N</scheduled>
		<login_type>NAME</login_type>
		<password></password>
		<public>N</public>
		<public_comment>Y</public_comment>

		<use_tele>N</use_tele>
		<tele_num></tele_num>
		<tele_mcode></tele_mcode>
		<tele_pcode></tele_pcode>
	</meeting>
</response>",
);


?>

The REST API uses HTTP GET, POST, PUT, and DELETE to interface with the system. 
Currently, PUT and DELETE are implemented with POST. The responses are returned
as XML data.

<hr size=1>
<div class='heading2'>Calling Syntax</div>

HTTP GET: <b>[API_url]/[API_object]/?[query]&signature=[signature]</b><br>
HTTP POST: <b>POST [API_url]/[API_object]/ [query]&signature=[signature]</b><br>
&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; or &nbsp;
	<b>POST [API_url]/[API_object]/ [query]&token=[token]</b>


<ul>
<li><b>[API_url]</b>: The API URL in the "<a href='<?php echo $apiPage?>'>Administration/API</a>" page (Admin account required.)</li>
<li><b>[API_object]</b>: One of the <a target='<?php echo $target?>' href='<?php echo $apiObjects?>'>API Objects.</a> Calling the API_url with HTTP GET will return a list of API_objects.</li>
<li><b>[query]</b>: Query string. See the documentation for ech API_object.</li>
<li><b>[signature]</b>: Signature of the query for authentication.</li>
<li><b>[token]</b>: Token code (Alternative authentication. See docs below.)</li>
</ul>
<hr size=1>
<div class='heading2'>Calling Methods</div>
<ul>
<li><b>GET</b>: for requesting information about an object in the system. The query and signature strings are passed in as URL parameters.</li>
<li><b>POST</b>: for adding an object to the system. The query and signature strings are passed in as POST data.</li>
<li><b>PUT</b>: for updating an existing object in the system. Currently, the method is implemented using POST with a query parameter 'method=PUT'.</li>
<li><b>DELETE</b>: for deleting an object from the system. Currently, the method is implemented using POST with a query parameter 'method=DELETE'.</li>
</ul>
<hr size=1>
<div class='heading2'>Authentication</div>
You can use one of the two following methods for API authentication:
<ol>
<li><b>Signature:</b> For API calls originated from a web service. See <a target='<?php echo $target?>' href="<?php echo $sigPage?>">'Signature Authentication'</a> for more details.</li>
<li><b>Token:</b> For API calls originated from a desktop application or a web page on a client computer. See <a target='<?php echo $target?>' href="<?php echo $tokenPage?>">'Token Authentication'</a> for more details.</li>
</ol>

<hr size=1>

<?php

$count=count($examples);

for ($i=0; $i<$count; $i++) {
	
	$num=$i+1;
	$text=$examples[$i];
	$req=htmlspecialchars($requests[$i]);	
	$req=wordwrap($req, 75, "<br>", 1);
	$resp=htmlspecialchars($responses[$i]);
	print <<<END
<div class='heading2'>Example $num</div>
$text
<div class='heading3'>Request</div>
<div class='text-box'>
<pre>
$req
</pre>
</div>
<div class='heading3'>Response</div>
<div class='text-box'>
<pre>
$resp
</pre>
</div>
END;
}

?>

<hr size="1">
<div class='heading2'>PHP Sample Code</div>

<ul>

<?php

$count=count($samples);
for ($i=1; $i<=$count; $i++) {
	$codePage="rest_doc/example$i.php";
	$samplePage=$GLOBALS['BRAND_URL']."?page=".PG_HELP_REST."&code_page=".rawurlencode($codePage);
	if (SID!='')
		$samplePage.="&".SID;
	$sampleText=$samples[$i-1];
	print <<<END
	<li><a target="_blank" href="$samplePage">$sampleText</a></li>
END;
}
?>
<li>Use SSO to sign in a user and start a meeting. <a href="rest_doc/sso_test.zip">Download sample code</a></li>
</ul>