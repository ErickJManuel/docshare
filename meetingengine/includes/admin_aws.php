<?php
/**
 * Persony Web Conferencing 2.0
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2008 Persony, Inc.
 * @version     2.0
 * 
 */


require_once("dbobjects/vaws.php");
require_once("dbobjects/vwebserver.php");
require_once("dbobjects/vremoteserver.php");
require_once("dbobjects/vvideoserver.php");
//require_once("dbobjects/vsite.php");
require_once("dbobjects/vbrand.php");

$personyOwnerId="157537607018";
$versionFile="vversion.php";
$installer="vinstall.php";
//VSite::GetSiteUrl($siteUrl);
$siteUrl=SITE_URL;

$showText="Show setup instructions";
$hideText="Hide setup instructions";

$ec2InstancePage="http://aws.amazon.com/ec2/instance-types/";
// $ec2InstancePage=http://docs.amazonwebservices.com/AWSEC2/2007-08-29/DeveloperGuide/instance-types.html;

$ec2Images=array(
	"ami-31c82d58" => "persony_wc2-wc_vc_rc-ami/image.manifest.xml",
	"ami-7f29cc16" => "persony_wc2-wc_vc_rc-v2-x86_64-ami/image.manifest.xml",
);

$imageDescriptions=array(
	"This instance will add web, video conferencing, and remote control hosting accounts.",
);

$smallInstOptions="<select name='instance_type'>";
$smallInstOptions.="<option value='m1.small'>small (1x)</option>";
$smallInstOptions.="<option value='c1.medium'>high-cpu medium (2x)</option>";
$smallInstOptions.="</select>";

$largeInstOptions="<select name='instance_type'>";
$largeInstOptions.="<option value='m1.large'>large (4x)</option>";
$largeInstOptions.="<option value='m1.xlarge'>x-large (8x)</option>";
$largeInstOptions.="<option value='c1.xlarge'>high-cpu x-large (8x)</option>";
$largeInstOptions.="</select>";

$brand=new VBrand($GLOBALS['BRAND_ID']);
//$brand->GetValue('aws_keyfile', $currKeyFile);

$keyFile='';
$checkKeys='checked';
$checkKeyfile='';
$accessKey=$secretKey='';
$accessKey=GetSessionValue("aws_access_key");
$secretKey=GetSessionValue("aws_secret_key");
	
GetArg('key_input', $keyInput);
if ($keyInput=='keys') {
	GetArg('access_key', $accessKey);
	GetArg('secret_key', $secretKey);
	SetSessionValue("aws_access_key", $accessKey);
	SetSessionValue("aws_secret_key", $secretKey);
	
} elseif ($keyInput=='keyfile') {

	if (!isset($_FILES['key_file']['tmp_name']) || $_FILES['key_file']['tmp_name']=='') {
		ShowError("Missing key file");
		return;
	} else {
		$keyFile=$_FILES['key_file']['name'];
//		echo("keyFile=".$keyFile);
		$tempFile=$_FILES['key_file']['tmp_name'];
		$fp=@fopen($tempFile, "r");
		if ($fp) {
			$content=fread($fp, filesize($tempFile));
			fclose($fp);
			list($accessKey, $secretKey)=explode(" ", $content);
			SetSessionValue("aws_access_key", $accessKey);
			SetSessionValue("aws_secret_key", $secretKey);
		}

	}
}

$thisPage=$_SERVER['PHP_SELF'];
/*
$addIcon="themes/add.gif";
$addUrl=$thisPage."?page=".PG_ADMIN_ADD_AWS."&brand=".$GLOBALS['BRAND_NAME'];
if (SID!='')
	$addUrl.="&".SID;
$addBtn="<a href=\"$addUrl\"><img src=\"$addIcon\"> Create an AWS Instance</a>";

$refreshIcon="themes/refresh.gif";
$refreshUrl=$thisPage."?page=".PG_ADMIN_AWS."&refresh=1&brand=".$GLOBALS['BRAND_NAME'];
if (SID!='')
	$refreshUrl.="&".SID;
$refreshBtn="<a href=\"$refreshUrl\"><img src=\"$refreshIcon\"> Refresh AWS Instances</a>";
$deleteIcon="themes/delete.gif";
$deleteBtn="<img src=\"$deleteIcon\">Terminate";
*/

$postUrl=$thisPage."?page=".PG_ADMIN_AWS."&brand=".$GLOBALS['BRAND_NAME']."&brandUrl=".$GLOBALS['BRAND_URL'];
if (SID!='')
	$postUrl.="&".SID;
	
$editUrl=$thisPage."?page=".PG_ADMIN_EDIT_AWS."&brand=".$GLOBALS['BRAND_NAME']."&brandUrl=".$GLOBALS['BRAND_URL'];
$editUrl.="&access_key=$accessKey";
if (SID!='')
	$editUrl.="&".SID;
	
$action='';
if (isset($_POST['refresh'])) {
	$action='refresh';
} else if (isset($_POST['create'])) {
	$action='create';
} else if (isset($_POST['stop'])) {
	$action='stop';
} else if (isset($_POST['reboot'])) {
	$action='reboot';
} else if (isset($_POST['delete'])) {
	$action='delete';
} else if (isset($_POST['create_keyfile'])) {
	$action='create_keyfile';
}

$message='';
$errMsg='';
if ($accessKey!='' && $secretKey!='' && GetArg('refresh_ami', $arg)) {
	
	$ec2Images=array();
	$awsImages=new VAWS();
	$awsImages->SetKeyPair($accessKey, $secretKey);
	$err=$awsImages->DescribeImages($personyOwnerId, true, $imageList);

	if ($err!=ERR_NONE)
		$errMsg=$awsImages->GetErrorMsg();
	else {
		foreach ($imageList as $v) {
			$imageId=$v->imageId;
			$loc=$v->imageLocation;
			if (strpos($loc, "persony_wc2")!==false)
				$ec2Images[$imageId]=$loc;
		}
	}
}

$imageOptions="<select onclick=\"return SetInstanceType();\" name='aws_image'>\n";
foreach ($ec2Images as $k=> $v) {
	$imageOptions.="<option value='$k'>$k ($v)</option>\n";
}
$imageOptions.="</select>\n";

if ($errMsg=='' && $action=='create') {
	GetArg('aws_image', $awsImage);
	GetArg('instance_type', $instanceType);

	if ($awsImage=='') {
		$errMsg='Missing aws_image parameter';
	} else {
	
		$aws=new VAWS;
		$aws->SetKeyPair($accessKey, $secretKey);

		$instanceId='';
		$state='';
		$err=$aws->RunInstances($awsImage, $instanceType, $instanceId, $state);

		if ($err!=ERR_NONE) {
			$errMsg=$aws->GetErrorMsg();
		} else if ($instanceId=='') {
			$errMsg="Instance not created for unknown reasons.";
		} else {
			$awsInfo=array();
			$awsInfo['image_id']=$awsImage;
			$awsInfo['instance_id']=$instanceId;
			$awsInfo['state']=$state;
			$awsInfo['brand_id']=$GLOBALS['BRAND_ID'];

			$awsId='';
			if ($aws->Insert($awsInfo)!=ERR_NONE) {
				$errMsg=$aws->GetErrorMsg();
			} else {
				$aws->GetValue('id', $awsId);
			}
			
			$hostingStr='';
			if ($errMsg=='') {
				$webServer=new VWebServer();
				$webInfo=array();
				$webInfo['brand_id']=$GLOBALS['BRAND_ID'];
				$webInfo['aws_id']=$awsId;
				$webInfo['name']='AWS Web '.$instanceId;
				$webInfo['login']='host';
				$webInfo['url']='';
				$webInfo['password']=mt_rand(100000, 999999);
				if ($webServer->Insert($webInfo)!=ERR_NONE) {
					$errMsg=$webServer->GetErrorMsg();		
				} else {
					$hostingStr.="<li>${webInfo['name']}</li>";
				}
			}
			if ($errMsg=='') {
				$server=new VVideoServer();
				$serverInfo=array();
				$serverInfo['brand_id']=$GLOBALS['BRAND_ID'];
				$serverInfo['aws_id']=$awsId;
				$serverInfo['name']='AWS Video '.$instanceId;
				$serverInfo['url']='';
				if ($server->Insert($serverInfo)!=ERR_NONE) {
					$errMsg=$server->GetErrorMsg();		
				} else {
					$hostingStr.="<li>${serverInfo['name']}</li>";
				}
			}
			
			if ($errMsg=='') {
				$remote=new VRemoteServer();
				$remoteInfo=array();
				$remoteInfo['brand_id']=$GLOBALS['BRAND_ID'];
				$remoteInfo['aws_id']=$awsId;
				$remoteInfo['name']='AWS Remote '.$instanceId;
				$remoteInfo['server_url']='';
				$remoteInfo['client_url']='';
				if ($remote->Insert($remoteInfo)!=ERR_NONE) {
					$errMsg=$remote->GetErrorMsg();		
				} else {
					$hostingStr.="<li>${remoteInfo['name']}</li>";
				}
			}
					
			$message="A server instance '$instanceId' is created. It may take a few mintues for the instance to be running. Click 'Refresh' to update the state.";
			if ($hostingStr!='')
				$message.="<br>\nThe following hosting accounts have been created for the instance:<br>\n<ul>$hostingStr</ul>";
		}
	}
} else if ($errMsg=='' && $action=='reboot') {
	$message='';
	if (isset($_POST['aws_id'])) {
		foreach ($_POST['aws_id'] as $k => $v) {
			$instanceId=$v;
			$aws=new VAWS();
			$aws->SetKeyPair($accessKey, $secretKey);
			$err=$aws->RebootInstance($instanceId);
			if ($err!=ERR_NONE) {
				$errMsg=$aws->GetErrorMsg();
				break;
			} else
				$message="Rebooting instance '$instanceId'...<br>";
		}
	}
} else if ($errMsg=='' && $action=='stop') {
	
	$message='';
	if (isset($_POST['aws_id'])) {
		foreach ($_POST['aws_id'] as $k => $v) {
			
			$instanceId=$v;
			VObject::Find(TB_AWS, "instance_id", $instanceId, $awsInfo);
			
			if (isset($awsInfo['id'])) {
				$awsId=$awsInfo['id'];
				$aws=new VAWS($awsId);
				
				// make sure no hosting account is using this server
				$dropStr='';
				$iquery="brand_id ='".$GLOBALS['BRAND_ID']."'";
				$iquery.=" AND aws_id='$awsId'";
				VObject::SelectAll(TB_WEBSERVER, $iquery, $iresult1);
				while ($irow = mysql_fetch_array($iresult1, MYSQL_ASSOC)) {
					$iweb=new VWebServer($irow['id']);
					$iweb->GetValue('name', $webName);
					$dropStr.="<li>$webName</li>";
				}
				VObject::SelectAll(TB_VIDEOSERVER, $iquery, $iresult2);
				while ($irow = mysql_fetch_array($iresult2, MYSQL_ASSOC)) {
					$iweb=new VVideoServer($irow['id']);
					$iweb->GetValue('name', $webName);
					$dropStr.="<li>$webName</li>";
				}		
				VObject::SelectAll(TB_REMOTESERVER, $iquery, $iresult3);
				while ($irow = mysql_fetch_array($iresult3, MYSQL_ASSOC)) {
					$iremote=new VRemoteServer($irow['id']);
					$iremote->GetValue('name', $remoteName);
					$dropStr.="<li>$remoteName</li>";
				}
				
				if ($dropStr!='') {
					$errMsg="Instance '$instanceId' cannot be terminated because the following hosting accounts are using it. Please remove the hosting accounts first.<br>\n";
					$errMsg.="<ul>$dropStr</ul>";
					break;
				} else {
					$aws->SetKeyPair($accessKey, $secretKey);		
					$hostName=$awsInfo['host_name'];		
					$err=$aws->TerminateInstance($instanceId, $theInstance);

					if ($err!=ERR_NONE) {
						$errMsg=$aws->GetErrorMsg();
						break;
					} else {
						$newInfo=array();
						$newInfo['state']='terminating';
						if ($aws->Update($newInfo)!=ERR_NONE) {
							$errMsg=$aws->GetErrorMsg();
							break;
						}
								
						$message.="Terminating '$instanceId'...<br>";
					}			
				}
			}
		}
	}

} else if ($errMsg=='' && $action=='delete') {
	
	if (isset($_POST['aws_id'])) {	
		foreach ($_POST['aws_id'] as $k => $v) {
		
			$instanceId=$v;
			VObject::Find(TB_AWS, "instance_id", $instanceId, $awsInfo);
			
			if (isset($awsInfo['id'])) {
				$aws=new VAWS($awsInfo['id']);
				$awsId=$awsInfo['id'];

				$aws->SetKeyPair($accessKey, $secretKey);		

				$state=$awsInfo['state'];			
				if ($state=='pending' || $state=='running' || $state=='') {
					$errMsg= "Please terminate the instance '$instanceId' first.";
					break;
				} else {
					if ($aws->Drop()!=ERR_NONE) {
						$errMsg=$aws->GetErrorMsg();
						break;
					}
/*
					$dropStr='';
					$iquery="brand_id ='".$GLOBALS['BRAND_ID']."'";
					$iquery.=" AND aws_id='$awsId'";
					VObject::SelectAll(TB_WEBSERVER, $iquery, $iresult1);
					while ($irow = mysql_fetch_array($iresult1, MYSQL_ASSOC)) {
						$iweb=new VWebServer($irow['id']);
						$iweb->GetValue('name', $webName);
						$dropStr.="<li>$webName</li>";
						//$iweb->Drop();
					}
					VObject::SelectAll(TB_VIDEOSERVER, $iquery, $iresult2);
					while ($irow = mysql_fetch_array($iresult2, MYSQL_ASSOC)) {
						$iweb=new VVideoServer($irow['id']);
						$iweb->GetValue('name', $webName);
						$dropStr.="<li>$webName</li>";
						//$iweb->Drop();
					}		
					VObject::SelectAll(TB_REMOTESERVER, $iquery, $iresult3);
					while ($irow = mysql_fetch_array($iresult3, MYSQL_ASSOC)) {
						$iremote=new VRemoteServer($irow['id']);
						$iremote->GetValue('name', $remoteName);
						$dropStr.="<li>$remoteName</li>";
						//$iremote->Drop();				
					}
					
					if ($dropStr!='') {
						$message="The following hosting accounts are using this server. Please remove or change them.<br>\n";
						$message.="<ul>$dropStr</ul>";
					}
*/
				}
			}
		}
	}

}



?>
<script type="text/javascript">
<!--


function CheckWebForm(theForm) {
	if (theForm.key_input.value=='keys') {
		if (theForm.access_key.value=='')
		{
			alert("Please enter a value for the \"Access Key\" field.");
			theForm.access_key.focus();
			return (false);
		}
		if (theForm.secret_key.value=='')
		{
			alert("Please enter a value for the \"Secret Key\" field.");
			theForm.secret_key.focus();
			return (false);
		}
	} else if (theForm.key_input.value=='keyfile') {
		if (theForm.key_file.value=='')
		{
			alert("Please enter a value for the \"Key File\" field.");
			theForm.key_file.focus();
			return (false);
		}	
	
	}
	return (true);
}

function ConfirmAction(action) {

	if ((document.aws_form.access_key.value=='' || document.aws_form.secret_key.value=='') &&
		document.aws_form.key_file.value=='') 
	{
		alert('An AWS key is missing.');
		return false;
	}
		
	if (document.aws_form.ami_count==undefined) {
		return false;
	}
		
	var count=Number(document.aws_form.ami_count.value);
	
	if (action=='refresh')
		return true;

	var selectCount=0;
	var confirmText ="Do you want to "+action;
	for(var i=0; i < count; i++){
		var elem=document.getElementById('aws_id_'+i);
		if(elem.checked) {
			confirmText +="\n   '"+elem.value+"'";
			selectCount+=1;
		}
			
	}
	confirmText+="?";
	
	if (selectCount==0) {
		alert("Select an instance first.");
		return false;
	}
	
	var ok=confirm(confirmText);
	if (ok)
		return true;
	else
		return false;

}


function SelectKeys() {
	document.getElementById('enter_keys').style.display='inline';
	document.getElementById('browse_keyfile').style.display='none';
	return true;
}

function SelectKeyFile() {
	document.getElementById('enter_keys').style.display='none';
	document.getElementById('browse_keyfile').style.display='inline';
	return true;
}

function SetInstanceType() {
	if (document.aws_form.aws_image.options[0].selected == true)
	{
		document.getElementById('inst-types').innerHTML= "<?php echo $smallInstOptions;?>";
	} else {
		document.getElementById('inst-types').innerHTML= "<?php echo $largeInstOptions;?>";	
	}

	return true;
}

function ShowInstruction() {

	var elem=document.getElementById('show-label');
	
	if (elem.innerHTML=='+ <?php echo $showText?>') {
		SetElemDisplay('instruction', 'inline');
		elem.innerHTML='- <?php echo $hideText?>';
	} else {
		SetElemDisplay('instruction', 'none');
		elem.innerHTML='+ <?php echo $showText?>';
	}
}

//-->
</script>

<div class='heading1'>Amazon Web Services (AWS)</div>
<!-- <div class='inform'>This feature is under construction</div> -->

You can create server instances on Amazon Web Services (AWS) <a target=_blank href='http://www.amazonaws.com'>www.amazonaws.com</a>
to host Web and video conferencing.
You need to have signed up for AWS Elastic Compute Cloud (EC2) and must have an "Access Key ID" and a "Secret Access Key."

<blockquote>
<a onclick="ShowInstruction(); return false;" href='#'><b><span id='show-label'>+ <?php echo $showText?></span></b></a>
</blockquote>
<div id='instruction' style="display:none">
<ul>
<li>Sign up for Amazon AWS and EC2 services at <a target=_blank href='http://www.amazonaws.com'>www.amazonaws.com</li>
<li><a target=_blank href='http://developer.amazonwebservices.com/connect/entry.jspa?entryID=609'>Download and install EC2 Firefox Extension</a>.</li>
<li>Follow the setup instructions from Step 2 to 5 in <a target=_blank href='http://developer.amazonwebservices.com/connect/thread.jspa?messageID=70391'>this page</a>. Note that the EC2 extension download page in Step 1 is outdated and you should download it from the page above. You can ignore Step 6 and beyond.</li>
<li><b>Important</b>: In Step 5, add the following tcp ports: <b>80, 22, 443, 1935, and 5500-6000</b>, to the 'default' group permissions under the Security Groups tab.
You will not be able to connect to your EC2 instances if you don't open these ports.</li>
<li>Copy your AWS access key ID and secret key to the form below. Create a key file and store it.</li>
<li>Select an AMI and an instance type.</li>
<li>Click <b>'Create Instance'</b> to launch a new instance. It will take about 5 minutes for the instance to be running. 
Click <b>'Refresh'</b> to check the state.
Hosting accounts will be automatically added for the instance.</li>
<li>Once the instance is running, you must <b>launch the installer</b> to install the required software. 
If you cannot get a response from the instance, check that you have opened port 80 in Security Groups setting for your EC2 account.</li>
<li>To use the instance, assign the hosting accounts associated with the instance to any group.</li>
<li>You must run the installer again after you reboot an instance.</li>
<li>Terminating an instance will destroy the server and you cannot restart it. To terminate an instance, you must delete any hosting accounts that use the instance first.</li>
<li>Deleting an instance will remove the instance record. You must terminate the instance first.</li>
<li>This page only shows AWS instances you have created from this site. You should use the EC2 Firefox extension to verify all instances you have created under your AWS account.
Any running instances will incur billing.</li>
</ul>
</div>

<form enctype="multipart/form-data" onsubmit='return CheckWebForm(this);' method="POST" action="<?php echo $postUrl?>" name="aws_form">

<p>
<div>
<span><input <?php echo $checkKeys?> onclick='return SelectKeys();' type="radio" name='key_input' value='keys'><b>Enter account keys</a></span>&nbsp;&nbsp;
<span><input <?php echo $checkKeyfile?> onclick='return SelectKeyFile();' type="radio" name='key_input' value='keyfile'><b>Load keys from a file</b></span>
</div>

<div id='enter_keys'>
<table class="meeting_detail">

<tr>
	<td class="m_key m_key_w">*<?php echo _Text("AWS Access Key ID")?>:</td>
	<td class="m_val">
	<input type="text" name="access_key" size="30" autocorrect="off" autocapitalize="off" value="<?php echo $accessKey?>">
	<span class='m_caption'>*<?php echo $gText['M_REQUIRED']?></span>
	</td>
</tr>
<tr>
	<td class="m_key m_key_w">*<?php echo _Text("AWS Secret Access Key")?>:</td>
	<td class="m_val">
	<input type="type" name="secret_key" size="50" autocorrect="off" autocapitalize="off" value="<?php echo $secretKey?>">
	<input type="submit" name="create_keyfile" value="Create Key File">
	<div class='m_caption'><?php echo _Text("Save the keys to a file so you can load them from the file next time.")?></div>
	</td>
</tr>

</table>
</div>

<div id='browse_keyfile'>
<table class="meeting_detail">
<tr>
	<td class="m_key m_key_w">*<?php echo _Text("Load keys from a file")?>:</td>
	<td class="m_val">
	<input type='file' name='key_file' size=50 value=''>
	</td>
</tr>
</table>
</div>

<hr size='1'>

<div style='padding-bottom:5px;'>
Select an AMI: <?php echo $imageOptions?>
<!-- <input type="submit" name="refresh_ami" value="Refresh AMI"> -->
</div>
<div style='padding-bottom:5px;'><?php echo _Text("Instance Type")?>: <span id="inst-types"><?php echo $smallInstOptions?></span>
<input type="submit" name="create" value="Create Instance">&nbsp;
<span class='m_caption'>See information about <a target=_blank href='<?php echo $ec2InstancePage?>'>Select Instance Type.</a></span>
</div>
<!--
<?php echo $imageDescriptions[0]?>&nbsp;

</div>
-->

<div class='error'><?php echo $errMsg?></div>
<div class='inform'><?php echo $message?></div>

<div style='padding-bottom:5px;'>
<input onclick="return ConfirmAction('refresh');" type="submit" name="refresh" value="Refresh">&nbsp;
<input onclick="return ConfirmAction('reboot');" type="submit" name="reboot" value="Reboot">&nbsp;
<input onclick="return ConfirmAction('terminate');" type="submit" name="stop" value="Terminate">&nbsp;
<input onclick="return ConfirmAction('delete');" type="submit" name="delete" value="Delete">&nbsp;
</div>

<table cellspacing="0" class="meeting_list">

<tr>
    <th class="tl pipe">&nbsp;</th>
    <th class="pipe">Instance</th>
    <th class="pipe">AMI</th>
    <th class="pipe">Type</th>
    <th class="pipe">Host name</th>
    <th class="pipe">State</th>
    <th class="pipe">Time</th>
</tr>

<?php

$query="brand_id ='".$GLOBALS['BRAND_ID']."'";
GetArg('id', $id);
if ($id!='')
	$query.=" AND id='".$id."'";
	
$errMsg=VObject::SelectAll(TB_AWS, $query, $result);
if ($errMsg!='') {
	ShowError($errMsg);
} 

$num_rows = mysql_num_rows($result);

if ($num_rows==0) {
	echo "<tr>";
	echo "<td colspan=7>&nbsp;</td>";
	echo "</tr>";
}
$rowCount=0;
while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {

	$awsId=$row['id'];
	
	if ($rowCount % 2)
		echo "<tr class=\"u_bg\">\n";
	else
		echo "<tr>\n";
					
	$name=$row['host_name'];
//	$state=$row['state'];
	$state="-";
	$instanceType='-';
	$launchTime='-';

	if ($action=='refresh') {
		$newInfo=array();
		$aws=new VAWS($awsId);
		$aws->SetKeyPair($accessKey, $secretKey);
		$instanceId=$row['instance_id'];
		if ($aws->DescribeInstance($instanceId, $instance)!=ERR_NONE) {
			$state='not found';
			$newInfo['state']=$state;
			ShowError($aws->GetErrorMsg());
		} else {
			$state=$instance->instanceStatename;
			$name=$instance->dnsName;
//			if (strlen($name)>20) {
//				$name=substr($name, 0, 20)."<br>".substr($name, 20);
//			}
			$instanceType=$instance->instanceType;
			$instanceType=str_replace("m1.", "", $instanceType);
			$launchTime=$instance->launchTime;
			$launchTime=str_replace("T", " ", $launchTime);
			$launchTime=str_replace(".000Z", "", $launchTime);
			$newInfo['state']=$state;
			$newInfo['host_name']=$name;
			
			if ($state=='running' && $name!='') {
				$iquery="brand_id ='".$GLOBALS['BRAND_ID']."'";
				$iquery.=" AND aws_id='$awsId'";
				VObject::SelectAll(TB_WEBSERVER, $iquery, $iresult);
				while ($irow = mysql_fetch_array($iresult, MYSQL_ASSOC)) {
					$iweb=new VWebServer($irow['id']);
					$theInfo=array();
					$theInfo['url']="http://".$name."/meeting/";
					$iweb->Update($theInfo);					
					$iweb->Get($theInfo);

					// check if php scripts are intalled
					$url=$theInfo['url'].$versionFile;
					//echo ($url);
			
					// this should return a version numbe string, which length should be less than 16
					$resp=HTTP_Request($url, '', 'GET', 5);
					if ($resp==false || strlen($resp)>16) {
						//if (strlen($resp)>16)
						//	echo $resp;
						
						$theName=$theInfo['name'];
						$installUrl=$theInfo['url'].$installer;
						$installUrl.="?login=".rawurlencode($theInfo['login']);
						$installUrl.="&password=".rawurlencode($theInfo['password']);
						$installUrl.="&server=".$siteUrl;
						$installUrl.="&brand=".$GLOBALS['BRAND_NAME'];
						$installUrl.="&win_title=".rawurlencode($gBrandInfo['product_name']);
						
						echo("<div class='inform'>Installation required for '$theName' [<a target=_blank href='$installUrl'>Install</a>].<div>");
						
/*					
						$out="<div class='inform'>Setting up \"${theInfo['url']}\"...</div>";
						// fill the buffer with extra data so flush will work on Win
						echo $out.str_pad(" ", 512);
						flush();
				
						$installUrl=$theInfo['url'].$installer;
						$data="login=".$theInfo['login'];
						$data.="&password=".$theInfo['password'];
						$data.="&server=".$siteUrl;
						$data.="&brand=".$GLOBALS['BRAND_NAME'];
						$data.="&win_title=".rawurlencode($gBrandInfo['product_name']);
						$data.="&no_update=1&install=1&silence=1";
						
						$content=HTTP_Request($installUrl, $data, 'POST', 15);
						
						if ($content==false) {
							ShowError("Error ".$installUrl);
						} elseif ($content!='OK') {
							ShowError($content);
						} else {
							echo "<div>Installation completed.</div>";
						}
*/
					}
									
				}
				VObject::SelectAll(TB_VIDEOSERVER, $iquery, $iresult);
				while ($irow = mysql_fetch_array($iresult, MYSQL_ASSOC)) {
					$iweb=new VVideoServer($irow['id']);
					$theInfo=array();
					$theInfo['url']="rtmp://".$name."/oflaDemo";
					$iweb->Update($theInfo);				
				}				
				VObject::SelectAll(TB_REMOTESERVER, $iquery, $iresult);
				while ($irow = mysql_fetch_array($iresult, MYSQL_ASSOC)) {
					$iremote=new VRemoteServer($irow['id']);
					$theInfo=array();
					$theInfo['server_url']="http://".$name."/prc_server/run.php";
					$theInfo['client_url']="http://".$name."/prc_client/index.php";
					$theInfo['password']="quirk";
					$iremote->Update($theInfo);				
				}
			}
		}
		$aws->Update($newInfo);

	}
	
	if ($name=='')
		$name='&nbsp;';

	$instanceId=$row['instance_id'];
	print <<<END
<td class="u_item_c"><input type='checkbox' name="aws_id['$awsId']" id='aws_id_$rowCount' value='$instanceId'></td>
<td class="u_item_c">$instanceId</td>
<td class="u_item_c">${row['image_id']}</td>
<td class="u_item_c">$instanceType</td>
<td class="u_item">$name</td>
<td class="u_item_c">$state</td>
<td class="u_item">$launchTime</td>
</tr>
END;

/*
<td class="u_item">
<input type='hidden' name='aws_id[$rowCount]' value='$awsId'>
<input onclick="return ConfirmReboot('$instanceId');" type='submit' name='reboot[$rowCount]' value='Reboot'>
<input onclick="return ConfirmStop('$instanceId');" type='submit' name='stop[$rowCount]' value='Stop'>
<input onclick="return ConfirmDelete('$instanceId');" type='submit' name='delete[$rowCount]' value='Delete'>
</td>
*/
	
	$rowCount++;
}

?>
</table>
<input type='hidden' name='ami_count' value='<?php echo $rowCount; ?>'>

</form>
<br>
<br>

<script type="text/javascript">
<!--
<?php
if ($checkKeyfile=='checked') {
	echo ("SelectKeyFile()\n");	
} else {
	echo ("SelectKeys()\n");
}
?>
//-->
</script>


