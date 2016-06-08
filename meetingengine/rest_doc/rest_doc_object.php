<?php
/**
 * Persony Web Conferencing 2.0
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2008 Persony, Inc.
 * @version     2.0
 * 
 */

GetArg('sub1', $obj);

$apiUrl=SITE_URL."rest/$obj/";
$content=HTTP_Request($apiUrl."?help"); 

$requiredParams=array();
$optionalParams=array();
$returnedParams=array();
$addtionalText='';
$inParameter='';
$required='';
$key='';
$current_tag='';
$synopsis=$methods='';
$xml_parser  =  xml_parser_create("");
xml_parser_set_option($xml_parser, XML_OPTION_CASE_FOLDING, false);
xml_set_element_handler($xml_parser, "start_instances_tag", "end_instances_tag");
xml_set_character_data_handler($xml_parser, "parse_instances");
xml_parse($xml_parser, $content, true);
xml_parser_free($xml_parser);


function start_instances_tag($parser, $name, $attribs) {
    global $current_tag, $inParameter, $required;
	global $synopsis, $methods, $addtionalText;
	$current_tag = $name;
	if ($name=='parameter' || $name=='return') {
		$inParameter=$name;
		if (isset($attribs['required']))
			$required=$attribs['required'];
		else
			$required='';
	} else if ($name=='synopsis') {
		$synopsis='';
	} else if ($name=='methods') {
		$methods='';
	} else if ($name=='additional') {
		$addtionalText='';
	}
}

function end_instances_tag($parser, $name) {
    global $current_tag, $inParameter;
	$current_tag = '';
	if ($name=='parameter' || $name=='return') {
		$inParameter='';
		$key='';
	}

}
function parse_instances($parser, $data) {
	global $current_tag, $key, $required, $inParameter, $requiredParams, $optionalParams, $returnedParams;
	global $synopsis, $methods, $addtionalText;
	switch ($current_tag) {
        case "synopsis":
			$synopsis.=$data;
            break;
        case "methods":
			$methods.=$data;
            break;
        case "key":
			if ($inParameter=='parameter' || $inParameter=='return')
				$key=$data;
            break;

        case "value":
			if ($inParameter=='parameter' && $required=='true') {
				if (isset($requiredParams[$key]))
					$requiredParams[$key].=$data;
				else
					$requiredParams[$key]=$data;
			} elseif ($inParameter=='parameter') {
				if (isset($requiredParams[$key]))
					$optionalParams[$key].=$data;
				else
					$optionalParams[$key]=$data;
			} elseif ($inParameter=='return') {
				if (isset($requiredParams[$key]))
					$returnedParams[$key].=$data;
				else
					$returnedParams[$key]=$data;
			}
            break;
		case "additional":
			$addtionalText.=$data;
			break;
		default:
			break;
    }
}

?>

<div><?php echo $synopsis?></div>
<br>
<div>Supported methods: <b><?php echo $methods?></b></div>

<div class='heading3'>Required paramters</div>	
<table cellspacing="0" class="meeting_list" >
<tr>
	<th class="tl pipe" width="120px"><?php echo $gText['M_PARAMETER']?></th>
	<th class="tr"><?php echo $gText['M_DESCRIPTION']?></th>
</tr>
<?php
foreach ($requiredParams as $key => $value) {
echo "<tr>\n";
echo "<td class='m_param'>$key</td>\n";
echo "<td class='m_desc'>$value</td>\n";
echo "</tr>\n";
}

?>
</table>


<div class='heading3'>Optional paramters</div>	
<table cellspacing="0" class="meeting_list" >
<tr>
	<th class="tl pipe" width="120px"><?php echo $gText['M_PARAMETER']?></th>
	<th class="tr"><?php echo $gText['M_DESCRIPTION']?></th>
</tr>
<?php
foreach ($optionalParams as $key => $value) {
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
$response=file_get_contents("rest/$obj/response.xml");
$response=htmlspecialchars($response);
?>

<div class='text-box'>
<pre>
<?php echo $response?>
</pre>
</div>

<ul>
<?php
foreach ($returnedParams as $key => $value) {
	echo ("<li><b>$key</b>: $value</li>\n");
}
?>
</ul>

<?php
if (trim($addtionalText)!='') {
	echo "<div class='heading3'>Additional information</div>\n";
	echo $addtionalText;
}
?>