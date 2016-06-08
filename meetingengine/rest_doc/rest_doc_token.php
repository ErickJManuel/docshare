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
$signinUrl=$apiUrl."signin/";

?>

Token authentication is designed to authenticate API calls originated from a desktop application or a client-side script.
Token autnentication does not require the distribution of a site API access key to all the clients and therefore is more secure
for client-to-server communication.
<p></p>
You can get two types of access tokens. One for site members (moderators) and the other one for meeting attendees. 
The member token allows an API call to have the same permissions as the member.
The attendee token allows an API call to have the same permissions as a meeting attendee.
To obtain a member token, you must have the member's login id and password. 
To obtain an attendee token, you must have the meeting id and password (if there is one.)
<p>
To make API calls with a token, append the token with <b>'token=[TOKEN_CODE]'</b> to each API query string.
</p>
<p>
Each token will expire after a certain duration (currently 8 hours.) The client application is responsible for making sure
to use a valid token. Multiple requests for a token with the same login credential will result in the same token.
</p>

<h3>Request a token for a member</h3>

Send HTTP POST to the following URL:<br>
<blockquote>
<b><?php echo $signinUrl?></b>
</blockquote>
with the following POST data
<blockquote>
<b>brand=[BRAND_ID]&member_id=[MEMBER_ID]&member_password=[MEMBER_PWD]</b> <br> OR <br>
<b>brand=[BRAND_ID]&member_login=[MEMBER_LOGIN]&member_password=[MEMBER_PWD]</b>
</blockquote>
<ul>
<li>[BRAND]: Site's Brand ID under Admin/API page.</li>
<li>[MEMBER_ID]: Member account ID (number.)</li>
<li>[MEMBER_LOGIN]: Member account login name (email). Only one of member_id or member_login should be given.</li>
<li>[MEMBER_PASSWORD]: Member login password.</li>
</ul>

<h4>Response</h4>

If the request is successful, the response status code is HTTP 200 and with the following body:
<blockquote>
<b>token=[TOKEN_CODE]</b>
</blockquote>

where [TOKEN_CODE] is the token to be appended to all API query string.
<p></p>
If the request fails, the response will return the following HTTP status code depending on the nature of the error.
<ul>
<li>HTTP 400: A required input parameter is missing.</li>
<li>HTTP 401: The password you entered does not match our records.</li>
<li>HTTP 404: The user is not found in our records.</li>
<li>HTTP 500: An internal error is encountered.</li>
</ul>

<h3>Request a token for a meeting attendee</h3>

Send HTTP POST to the following URL:<br>
<blockquote>
<b><?php echo $signinUrl?></b>
</blockquote>
with the following POST data
<blockquote>
<b>brand=[BRAND_ID]&meeting_id=[MEETING_ID]&meeting_password=[MEETING_PASSWORD]&attendee_login=[ATTENDEE_LOGIN]</b>
</blockquote>
<ul>
<li>[BRAND]: Site's Brand ID under Admin/API page.</li>
<li>[MEETING_ID]: Meeting ID</li>
<li>[MEETING_PASSWORD]: Meeting login password (only needed if the meeting has a password.)</li>
<li>[ATTENDEE_LOGIN]: Registered attendee's login email address (only needed if the meeting requires registration.)</li>
</ul>

<h4>Response</h4>

If the request is successful, the response status code is HTTP 200 and with the following body:
<blockquote>
<b>token=[TOKEN_CODE]& reg_name=[REGISTERED_NAME]</b>
</blockquote>
where [TOKEN_CODE] is the token to be appended to all API query string, and [REGISTERED_NAME] is the user name
the attendee used to register for the meeting.
<p></p>
If the request fails, the response will return the following HTTP status code depending on the nature of the error.
<ul>
<li>HTTP 400: A required input parameter is missing.</li>
<li>HTTP 401: The password you entered does not match our records.</li>
<li>HTTP 404: The meeting or attendee is not found in our records.</li>
<li>HTTP 500: An internal error is encountered.</li>
</ul>