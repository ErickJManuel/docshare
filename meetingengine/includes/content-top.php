<?php
/**
 * Persony Web Conferencing 2.0
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2010 Persony, Inc.
 * @version     2.0
 * 
 */


if (isset($_COOKIE['hide_nav']) && $_COOKIE['hide_nav']=='1' ||
	isset($gBrandInfo['hide_navbars']) && $gBrandInfo['hide_navbars']=='Y')
{
	$GLOBALS['HIDE_NAV']='on';
	$GLOBALS['HIDE_TABS']='on';
}
if ((isset($gBrandInfo['hide_signin']) && $gBrandInfo['hide_signin']=='Y') || GetSessionValue('hide_signin')=='1')
	$GLOBALS['HIDE_SIGNIN']='on';
		
$mainTabs=explode(",", $GLOBALS['MAIN_TABS']);

$helloText=$gText['TEXT_WELCOME'];

$memberId=GetSessionValue('member_id');
$memberName=GetSessionValue('member_name');
$hasLibrary=true;	// the session value is not set when using 'token' to sign in.
if (GetSessionValue('has_library')=='N')
	$hasLibrary=false;
$memberPerm=GetSessionValue('member_perm');
$memberBrand=GetSessionValue('member_brand');

if (isset($memberId) && $memberId!='' && $memberBrand==$gBrandInfo['id'])
	$signedIn=true;
else
	$signedIn=false;
	
$brandPage=$GLOBALS['BRAND_URL'];

$helpPage=$brandPage."?page=".PG_HELP;
if (SID!='') $helpPage.="&".SID;

//$signinPage="signin.php?brand=".$GLOBALS['brand'];
$signinPage=$brandPage."?page=".PG_MEETINGS;
if (SID!='') $signinPage.="&".SID;

$signoutPage="signout.php?brand=".$GLOBALS['BRAND_NAME']."&brandUrl=".$GLOBALS['BRAND_URL'];
if ($GLOBALS['TARGET']=='_self') {
	$theRetPage="index.php?brand=".$GLOBALS['BRAND_NAME'];
} else {
	$theRetPage=$GLOBALS['BRAND_URL'];
}
$signoutPage.="&ret=".rawurlencode($theRetPage);
//$signoutPage=$brandPage."?page=".PG_SIGNOUT;
if (SID!='') $signoutPage.="&".SID;

$localeUrl="index.php?brand=".$GLOBALS['BRAND_NAME']."&brandUrl=".$GLOBALS['BRAND_URL'];
$localeElemId='locale';
$locales=GetLocaleOptions($localeElemId, $GLOBALS['locale'], $localeElemId, "return ChangeLocale('$localeElemId', '$localeUrl')");

?>
<div id='main_page'>

<table id='top-bar' 
<?php if (isset($GLOBALS['HIDE_NAV']) && $GLOBALS['HIDE_NAV']=='on') echo "style='display:none'";?>
>
	<tr>
		<td id='top-logo'>
<?php
if (isset($GLOBALS['LOGO_URL']) && $GLOBALS['LOGO_URL']!='') {
	if (isset($gBrandInfo['logo_link']) && $gBrandInfo['logo_link']!='') {
		$thePage=$gBrandInfo['logo_link'];
		echo "<a id='logo_link' target='_top' href=\"$thePage\" onclick='return OnLink(this)'><img id='logo_pict' src=\"${GLOBALS['LOGO_URL']}\"></a>\n";
	} else {
		$thePage=$brandPage;
		if (SID!='')
			$thePage.="?".SID;
		echo "<a id='logo_link' target=${GLOBALS['TARGET']} href=\"$thePage\" onclick='return OnLink(this)'><img id='logo_pict' src=\"${GLOBALS['LOGO_URL']}\"></a>\n";
	}
} else {
	echo "<a id='logo_link' target='_top' href=\"javascript:void(0)\"><img id='logo_pict' src=\"themes/1pix.png\"></a>\n";
}
?>		
		</td>
		<td id='sign-in'>

<?php
if (isset($GLOBALS['HIDE_SIGNIN']) && $GLOBALS['HIDE_SIGNIN']=='on') {
	
} else {
	// can't use member name to determine if someone signd in or not becaue the name can be empty
	//	if (isset($memberName) && $memberName!='') {
	//	if (isset($memberId) && $memberId!='') {
	if ($signedIn) {
		$len=strlen($memberName);
		if ($len>20)
			$memberName=substr($memberName, 0, 18)."...";
		echo ($helloText." ".htmlspecialchars($memberName)." | ");
		echo ("<a target='${GLOBALS['TARGET']}' href=\"$signoutPage\" onclick='return OnLink(this)'>".$gText['TITLE_SIGNOUT']."</a> | ");
	} else {
		echo ("<a target='${GLOBALS['TARGET']}' href=\"$signinPage\" onclick='return OnLink(this)'>".$gText['TITLE_SIGNIN']."</a> | ");
	}
	
?>
		<a target="_blank" href="<?php echo $helpPage?>"><?php echo $gText['TITLE_HELP']?></a>
	<?php 
	//if (!isset($memberId) || $memberId=='') {
	if (!$signedIn) {
		echo " | ".$locales;
	}
?> 
		<div id="meeting_signin">
	<?php
	
	$joinText=$gText['M_JOIN'];
	
	//if (isset($memberId) && $memberId!='') {
	if ($signedIn) {
		$text=_Text("Meeting ID");
		print <<<END
		<form target="${GLOBALS['TARGET']}" action="${GLOBALS['BRAND_URL']}">
		<input type="hidden" name="page" value="HOME_JOIN">
		$text: <input type="number" size='10' name="meeting"> <input id='join-meeting' type="submit" value="$joinText"><br>
		$locales
		</form>
END;
	}
?>
		</div>
	<?php
}
?>
		</td>
	</tr>
<?php 
if (IsIPhoneUser() && isset($gBrandInfo['mobile']) && strpos($gBrandInfo['mobile'], "iPhone")!==false) {	
	$iphoneUrl=$gBrandInfo['site_url']."iphone";
	$message=_Text("Go to the iPhone optimized web site.");
	
	print <<<END
	<tr>
		<td colspan=2 id='iphone-message'>
		<a target='_top' href='$iphoneUrl'>$message</a>
		</td>
	</tr>
END;
}

/*
	<tr>
		<td colspan=2 style='text-align:center;' class='inform' id='site-message'>

	$startMessage=GetSessionValue('start_message');
	if ($startMessage!='') {
		ShowMessage($startMessage);
		SetSessionValue('start_message', '');
	}
		</td>
	</tr>

*/
?>
</table> <!--top-bar-->


<?php 
if (isset($GLOBALS['HIDE_TABS']) && $GLOBALS['HIDE_TABS']=='on' && strpos($GLOBALS['THEME'], 'broadsoft')!==false) 
	echo "<div id='top-corner-r'><div id='top-corner-l'></div></div>\n";
?>

<table id="main_tabs" cellspacing=0 cellpadding=0>

<?php 
if ((isset($GLOBALS['HIDE_TABS']) && $GLOBALS['HIDE_TABS']=='on') || (isset($GLOBALS['HIDE_NAV']) && $GLOBALS['HIDE_NAV']=='on'))
	echo "<tr id='top-nav-bar' style='display:none'>\n";
else
	echo "<tr id='top-nav-bar'>\n";
?>

		<td colspan="5">
		<div id="nav-primary">
		<ul name="navbar">
<?php
$i=0;
foreach ($mainTabs as $tabId) {
	if ($tabId==PG_ADMIN && $memberPerm!="ADMIN")
		continue;
	elseif ($tabId==PG_LIBRARY && !$hasLibrary)
		continue;
	
	$tabText='&nbsp;';
	if ($tabId==PG_HOME)
		$tabText=$gText['HOME_TAB'];
	elseif ($tabId==PG_MEETINGS)
		$tabText=$gText['MEETINGS_TAB'];
	elseif ($tabId==PG_LIBRARY)
		$tabText=$gText['M_MY_LIBRARY'];
	elseif ($tabId==PG_ACCOUNT)
		$tabText=$gText['ACCOUNT_TAB'];
	elseif ($tabId==PG_ADMIN)
		$tabText=$gText['ADMIN_TAB'];
	elseif ($tabId==PG_CUSTOM)
		$tabText=$gText['CUSTOM_TAB'];
	
	$len=strlen($tabText);
	if ($len>17)
		$tabText=substr($tabText, 0, 15)."...";
	$pageLink=$brandPage."?page=".$tabId;
	if (SID!='') $pageLink.="&".SID;
	$alink=$_SERVER['PHP_SELF']."?page=".$tabId;
	
	if ($i==0) {
		if ($GLOBALS['TAB']==$tabId) {
			echo "<li class='on first_tab'><a target=${GLOBALS['TARGET']} href=\"$pageLink\" onclick=\"return OnLink(this)\">$tabText</a></li>\n";
			//echo ("<li class=\"on first_tab\"><a href='$pageLink' onclick=\"ShowContent('$alink'); return false;\" >".$tabText."</a></li>\n");
			//echo"<li class='on first_tab'><a target=_replace href=\"$alink\">$tabText</a></li>\n";
		} else {
			//echo "<li class='first_tab' onmouseover=\"SetClassName(this, 'on first_tab');\" onmouseout=\"SetClassName(this, 'out first_tab');\"><a target=${GLOBALS['TARGET']} href=\"$pageLink\">$tabText</a></li>\n";	
			echo "<li class='first_tab'><a target=${GLOBALS['TARGET']} href=\"$pageLink\" onclick=\"return OnLink(this)\">$tabText</a></li>\n";	
			//echo ("<li class=\"first_tab\"><a href='$pageLink' onclick=\"ShowContent('$alink'); return false;\" >".$tabText."</a></li>\n");
			//echo"<li class='first_tab' onmouseover=\"SetClassName(this, 'on first_tab');\" onmouseout=\"SetClassName(this, 'out first_tab');\"><a target=_replace href=\"$alink\">$tabText</a></li>\n";	
		}			
	} else {
		if ($GLOBALS['TAB']==$tabId) {
			echo "<li class='on'><a target=${GLOBALS['TARGET']} href=\"$pageLink\" onclick=\"return OnLink(this)\">$tabText</a></li>\n";
			//echo ("<li class=\"on\"><a href='$pageLink' onclick=\"ShowContent('$alink'); return false;\" >".$tabText."</a></li>\n");
			//echo"<li class='on'><a target=_replace href=\"$alink\">$tabText</a></li>\n";
		} else {
			//echo "<li onmouseover=\"SetClassName(this, 'on');\" onmouseout=\"SetClassName(this, 'out');\"><a target=${GLOBALS['TARGET']} href=\"$pageLink\">$tabText</a></li>\n";	
			echo "<li><a target=${GLOBALS['TARGET']} href=\"$pageLink\" onclick=\"return OnLink(this)\">$tabText</a></li>\n";	
			//echo ("<li><a href='$pageLink' onclick=\"ShowContent('$alink'); return false;\" >".$tabText."</a></li>\n");
			//echo"<li onmouseover=\"SetClassName(this, 'on');\" onmouseout=\"SetClassName(this, 'out');\"><a target=_replace href=\"$alink\">$tabText</a></li>\n";	
		}			
	}
	
	$i++;
}

?>
		</ul>
	</div> <!--nav-primary-->

	<div id="nav-secondary">
		<ul name="subbar">
<?php
if (!isset($GLOBALS['SUB_MENUS']) || count($GLOBALS['SUB_MENUS'])==0) {
	echo ("<li>&nbsp;</li>\n");	
	
} else {
	foreach ($GLOBALS['SUB_MENUS'] as $pageId => $pageName) {
		$len=strlen($pageName);
		if ($len>20)
			$pageName=substr($pageName, 0, 18)."...";
		
		$pageLink=$brandPage."?page=".$pageId;
		if (SID!='') $pageLink.="&".SID;
		$alink=$_SERVER['PHP_SELF']."?page=".$pageId;
		
		if ($GLOBALS['SUB_PAGE']==$pageId) {
			echo ("<li class=\"on\"><a target=${GLOBALS['TARGET']} href=\"".$pageLink."\" onclick=\"return OnLink(this)\">".$pageName."</a></li>\n");
			//echo ("<li class=\"on\"><a target=_replace href=\"".$alink."\" >".$pageName."</a></li>\n");
			//echo ("<li class=\"on\"><a href='$pageLink' onclick=\"ShowContent('$alink'); return false;\" >".$pageName."</a></li>\n");
		} else {
			echo ("<li><a target=${GLOBALS['TARGET']} href=\"".$pageLink."\" onclick=\"return OnLink(this)\">".$pageName."</a></li>\n");
			//echo ("<li><a target=_replace href=\"".$alink."\" >".$pageName."</a></li>\n");
			//echo ("<li><a href='$pageLink' onclick=\"ShowContent('$alink'); return false;\" >".$pageName."</a></li>\n");
		}
	}
}
?>
		</ul>
	</div> <!--nav-secondary-->
		</td>
	</tr> <!--top-nav-bar-->

</table>


<div id="page-content"> <!--page-content-->
<table id="main_content" cellspacing=0 cellpadding=0>
	<tr>
<?php
if($GLOBALS['SIDE_NAV'] == 'on' ) { 
	echo "<td id=\"left-content\" colspan=\"4\" valign=\"top\">\n"; 
	echo "<div id=\"l-text\">\n"; 
} else {
	echo "<td id=\"one-content\" colspan=\"5\" valign=\"top\">\n";
	echo "<div id=\"one-text\">\n"; 
}
?>