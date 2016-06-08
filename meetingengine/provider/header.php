<?php
/**
 * Persony Web Conferencing 2.0
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2008 Persony, Inc.
 * @version     2.0
 * 
 */


$helloText=$gText['TEXT_WELCOME'];

$memberName=GetSessionValue('provider_login');

$homePage="provider.php";
if (SID!='') $homePage.="?".SID;

$signinPage="provider_signin.php";
if (SID!='') $signinPage.="?".SID;

$signoutPage="provider_signout.php";
if (SID!='') $signoutPage.="?".SID;

?>
<meta name="keywords" content="web conferencing">
<meta name="description" content="Web conferencing">
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<link href="themes/<?php echo $GLOBALS['THEME']?>/" rel="stylesheet" type="text/css">
<script type="text/javascript">
<!--

//-->
</script>
</head>
<body>
<center>
<div id='main_page'>

<table id='top-bar'>
	<tr>
		<td id='top-logo'>
<?php
if (isset($GLOBALS['LOGO_URL']) && $GLOBALS['LOGO_URL']!='')
	echo "<a target=${GLOBALS['TARGET']} href=\"$homePage\"><img src=\"${GLOBALS['LOGO_URL']}\"></a>\n";
?>			
		</td>
		<td id='sign-in'>

<?php 
if (isset($memberName) && $memberName!='') {
	echo ($helloText." ".$memberName." | ");
	echo ("<a target=${GLOBALS['TARGET']} href=\"$signoutPage\">".$gText['TITLE_SIGNOUT']."</a>");
} else {
	echo ("<a href=\"$signinPage\">".$gText['TITLE_SIGNIN']."</a>");
}
?>
		</td>

	</tr>
</table>

<table id="main_content">
<tr>
<!-- needed for colspan to work correctly -->
<td></td>
<td></td>
<td></td>
<td></td>
<td></td>
</tr>
	<tr>
		<td colspan="5">
		<div id="nav-primary">
		<ul name="navbar">
<?php
$menus=$GLOBALS['MAIN_MENUS'];
$count=count($menus);
foreach ($menus as $pageName => $pageId) {
	
	$thePage=$_SERVER['PHP_SELF']."?page=".$pageId;
	if (SID!='') $thePage.="&".SID;
	
	if ($GLOBALS['TAB']==$pageName)
		echo"<li class=\"on\"><a target=${GLOBALS['TARGET']} href=\"$thePage\" name=\"$pageId\">$pageName</a></li>\n";
	else
		echo"<li><a target=${GLOBALS['TARGET']} href=\"$thePage\" name=\"$pageId\">$pageName</a></li>\n";
}

?>
		</ul>
	</div> 

	<div id="nav-secondary">
		<ul name="subbar">

<?php
if (isset($GLOBALS['SUB_MENUS']))
{
	$menus=$GLOBALS['SUB_MENUS'];
	$count=count($menus);
	if ($count>0) {
		foreach ($menus as $pageName => $pageId) {
			
			$subPage=$_SERVER['PHP_SELF']."?page=".$pageId;
			if (SID!='') $subPage.="&".SID;
			
			if ($GLOBALS['SUB_PAGE']==$pageId)
				echo ("<li class=\"on\"><a target=${GLOBALS['TARGET']} href=\"".$subPage."\" >".$pageName."</a></li>\n");
			else
				echo ("<li><a target=${GLOBALS['TARGET']} href=\"".$subPage."\" >".$pageName."</a></li>\n");
		}
	} else {
		echo ("<li>&nbsp;</li>\n");	
	}
} else {
	echo ("<li>&nbsp;</li>\n");	
}

?>
		</ul>
	</div> 
		</td>
	</tr>

	<tr>
<?php
if($GLOBALS['SIDE_NAV'] == 'on' ) { 
	echo "<td id=\"left-content\" colspan=\"1\" valign=\"top\">\n"; 
	echo "<div id=\"l-text\">\n"; 
} else {
	echo "<td id=\"one-content\" colspan=\"1\" valign=\"top\">\n";
	echo "<div id=\"one-text\">\n"; 
}
?>