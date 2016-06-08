


<?php
$target=$GLOBALS['TARGET'];

$apiPage=$GLOBALS['BRAND_URL']."?page=".PG_HELP_REST;
if (SID!='')
	$apiPage.="&".SID;
	
$title="<span ><a target='$target' href='$apiPage'>${gText['M_API_DOC']}</a></span>";

GetArg('topic', $topic);
if ($topic!='')
	$title.=" | <span class='heading2'><a target='$target' href='$apiPage&topic=$topic'>$topic</a></span>";

GetArg('sub1', $sub1);	
if ($sub1!='')
	$title.=" | <span class='heading2'><a target='$target'href='$apiPage&topic=$topic&sub1=$sub1'>$sub1</a></span>";
	
echo "<div class='heading1'>$title</div>\n";


if ($topic=='') {	

	require_once("rest_doc/rest_doc_intro.php");
	
} elseif ($topic==$gText['REST_OBJECTS']) {
	
	if ($sub1=='') {

		require_once("rest_doc/rest_doc_objects.php");
		
	} else {		
		require_once("rest_doc/rest_doc_object.php");
				
	}
} elseif ($topic==$gText['REST_HOOKS']) {
	
	require_once("rest_doc/rest_doc_hooks.php");
	
} elseif ($topic==$gText['REST_ERRORS']) {
	
	require_once("rest_doc/rest_doc_errors.php");

} elseif ($topic==$gText['REST_TOKEN']) {
	
	require_once("rest_doc/rest_doc_token.php");
	
} elseif ($topic==$gText['REST_SIGNATURE']) {
	
	require_once("rest_doc/rest_doc_signature.php");
	
}
?>


