<?php
/**
 * Persony Web Conferencing 2.0
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2008 Persony, Inc.
 * @version     2.0
 * 
 */

require_once('rest/prestapi.php');
?>

If an error is encountered, the API object returns an XML response with an error code and a message.
Note that the error code is not retuned as the HTTP status code.
<br>
<br>
<table cellspacing="0" class="meeting_list" >
<tr>
	<th class="tl pipe" width='60px'><?php echo $gText['M_ERROR_CODE']?></th>
	<th class="tr"><?php echo $gText['M_ERROR_MESSAGE']?></th>
</tr>
<?php
foreach ($s_errorMessages as $key => $value) {
	print <<<END
<tr>
	<td class="m_param">$key</td>
	<td class="m_desc">$value</td>
</tr>
END;
}

?>
</table>

<div class='heading3'>Response</div>
<?php
$response=file_get_contents("rest/error.xml");
$response=htmlspecialchars($response);
?>

<div class='text-box'>
<pre>
<?php echo $response?>
</pre>
</div>

<br>