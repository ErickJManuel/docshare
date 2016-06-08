<?php

//header("Location: help_doc/index.html");
//exit();

include_once("includes/common.php");
require_once("includes/brand.php");
ob_start();
@include_once($GLOBALS['LOCALE_FILE']);
ob_end_clean();
require_once("includes/common_text.php");
	

$GLOBALS['TAB']=PG_HOME;

$GLOBALS['SUB_MENUS']=array(

		);
		
if (isset($_GET['page']))		
	$GLOBALS['SUB_PAGE']=$_GET['page'];
else
	$GLOBALS['SUB_PAGE']='';

 // will this page have a right hand side navigation?
if ($GLOBALS['SUB_PAGE']==PG_HELP_REST)
	$GLOBALS['SIDE_NAV'] = 'on';
else
	$GLOBALS['SIDE_NAV'] = 'off';

 // if side nave on, what is the path and filename to the include?
if($GLOBALS['SIDE_NAV'] == 'on') { $GLOBAL['SIDE_INCLUDE'] = 'rest_doc/rest_doc_right.php'; }


// show product doc for the default HELP page unless it is a custom page
if  ((GetArg('doc', $doc) && $doc=='product') || 
		($GLOBALS['SUB_PAGE']==PG_HELP && $gBrandInfo['custom_help']!='Y' && !GetArg('doc', $doc)))  
{

	header("Location: help_doc/index.html");
	exit();
/*
		$title=$gText['TITLE_HELP'];
	
print <<<END
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title>$title</title>
	<meta id="viewport" name="viewport" content="width=device-width; initial-scale=1.0; "/>
</head>
<body>
<iframe src="help_doc/index.html" width="100%" height="100%" marginWidth="0" marginHeight="0" frameBorder="0" scrolling="no">
</iframe>
</body>
</html>
END;
*/
} elseif (GetArg('doc', $doc) && $doc=='sso') {
	include_once('includes/header.php');
	include_once('includes/content-top.php'); 
	
	include_once('sso_doc/sso_doc.php');
	
	include_once('includes/content-bottom.php');
	include_once('includes/footer.php'); 
	
} elseif (GetArg('code_page', $arg)) {
	echo "<pre style='margin: 20px'>";
	$content=file_get_contents($arg);
	$content=htmlspecialchars($content);
	echo $content;
	echo "</pre>";

} else {

	include_once('includes/header.php');
	include_once('includes/content-top.php');
		
	$productDocPage=$GLOBALS['BRAND_URL']."?page=HELP";
	if (SID!='')
		$productDocPage.="&".SID;

	$ssoPage=$GLOBALS['BRAND_URL']."?page=HELP&doc=sso";
	if (SID!='')
		$ssoPage.="&".SID;

	if ($GLOBALS['SUB_PAGE']==PG_HELP || $GLOBALS['SUB_PAGE']=='') {
		
		if ($gBrandInfo['custom_help']=='Y') {
			
print <<<END
	<div class="heading1">${gText['TITLE_HELP']}</div>
END;

			echo $gBrandInfo['help_text'];

		} else {
			
			
			$apiPage=$GLOBALS['BRAND_URL']."?page=".PG_HELP_REST;
			if (SID!='')
				$apiPage.="&".SID;

print <<<END
<div class="heading1">${gText['TITLE_HELP']}</div>
<ul class="bullet_list">
<li><a target='blank' href='$productDocPage'>Product Documentation</a></li>
<li><a target=${GLOBALS['TARGET']} href='$apiPage'>${gText['M_API_DOC']}</a></li>
<li><a target=${GLOBALS['TARGET']} href='$ssoPage'>Single Sign On (SSO) Documentation</a></li>
</ul>
END;

		}
	} else if ($GLOBALS['SUB_PAGE']==PG_HELP_REST) {
		include_once("rest_doc/rest_doc.php");
	}
	include_once('includes/content-bottom.php');
	include_once('includes/footer.php'); 
	
}
?>
