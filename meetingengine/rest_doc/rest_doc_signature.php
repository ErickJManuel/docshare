<?php
/**
 * Persony Web Conferencing 2.0
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2008 Persony, Inc.
 * @version     2.0
 * 
 */

?>

<h4>Signature Authentication</h4>
You should only use the signature authentication if you are making API calls from another web service (server-to-server communication.)
A signature must be appended to each query for the purpose of authentication. 
The signature is computed from the md5 hash of the query and the <a target='<?php echo $target?>' href='<?php echo $apiPage?>'>API Access Key</a>,
a secret key that is available only to a site administrator.
<blockquote>
<b>signature=md5([query][API_Access_Key])</b>
</blockquote>

'[query][API_Access_Key]' is the concatenated string of the query and the API Access Key with no space or any character in between.
<p>
The query string can optionally include 'ip=[IP_ADDRESS]'. If the 'ip' parameter is present in the query, the requester's ip address
must match [IP_ADDRESS].
<p>
For HTTP GET, all query values should be url-encoded and the signature should be computed from the encoded values.
For example, you should compute the signature from 'title=My%20Meeting' instead of 'title=My Meeting'.
<p>
For HTTP POST (including PUT and DELETE), the query values should be url-encoded but the signature should be computed from the non-encoded values.
For example, if the POST field is "name=Sales%26Marketing", you should compute the signature from 'name=Sales&Marketing'.
<p>
The order of the values matters in the signature computation. If you are using an html form to send POST requests, the order of the form parameters
may not match the actual post data and the form may also add a "submit" field, which you will need to include in the signature computation.
It is recommand that you use the "token" authentication instead of the signature authentication when you send a POST request from an html form.
</p>
<p>
An authenticated request has the same access permission as a site administrator.

<?php

$text=
"<?php
	//construct the query string
	\$query=\"brand=88000871&name=\".urlencode(\"sales&marketing\");
	//get the API access key
	\$apiKey=\"3f868375aac01d88a4cafaaecaa20de\";
	//contrust the string for signature computation
	if (method=='GET')
	  \$sigstr=\$query;
	else
	  \$sigstr=\"brand=88000871&name=sales&marketing\";
	//compute the signature
	\$signature=md5(\$sigstr.\$apiKey);
	//append the signature to the query
	\$query.=\"&signature=\".\$signature;
?>";

$text=htmlspecialchars($text);


?>

<p>
<b>Compute Query Signature (PHP example)</b>
<div class='text-box'>
<pre>
<?php echo $text?>
</pre>
</div>