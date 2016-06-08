<?php
require_once("dbobjects/vlicense.php");
require_once("dbobjects/vprovider.php");
require_once("dbobjects/vbrand.php");

$licenses=array();
$ports=array();
$licenseType='N';

$provider_id=GetSessionValue('provider_id');
// $providerAccId='';

$provider=new VProvider($provider_id);
$providerInfo=array();

if ($provider->Get($providerInfo)!=ERR_NONE) {
	ShowError($provider->GetErrorMsg());
	return;
}

// $providerAccId=$providerInfo['account_id'];
VProvider::ParseLicenseKey($providerInfo, $licenseType, $licenseCounts, $recording);

$license=$providerInfo['license'];
$items=explode(";", $license);

$licenseType=$items[0];
if ($licenseType=='N') {	// named users
	$licenseStr='';
	if (isset($items[1]))
		$licenseStr=$items[1];

	$listItems=explode(',', $licenseStr);
	foreach ($listItems as $v) {
		$subItems=explode(':', $v);
		
		$subItemCount=count($subItems);
		if ($subItemCount==2)
			$licenses[$subItems[0]]=$subItems[1];
		elseif ($subItemCount==1)
			$licenses[$subItems[0]]=0;
	}
} else if ($licenseType=='P') {	// concurrent ports
	$portStr='';
	if (isset($items[1]))
		$portStr=$items[1];

	$listItems=explode(',', $portStr);
	foreach ($listItems as $v) {
		$subItems=explode(':', $v);
		
		$subItemCount=count($subItems);
		if ($subItemCount==2)
			$ports[$subItems[0]]=$subItems[1];
		elseif ($subItemCount==1)
			$ports[$subItems[0]]=0;
	}

	
} else if ($licenseType=='U') {
	
}

$licenseTypeStr='';
if ($licenseType=='N')
	$licenseTypeStr='Named User';
else if ($licenseType=='P')
	$licenseTypeStr='Concurrent Port';
else if ($licenseType=='U')
	$licenseTypeStr='Unlimited';


if ($licenseType=='P') {
	if (isset($licenseCounts['PTS'])) {
		VObject::Find(TB_LICENSE, 'code', 'PTS', $portInfo);
		$portName=$portInfo['name'];
		$licenseTypeStr.=" &nbsp;&nbsp;&nbsp;".$portName.": ".$licenseCounts['PTS'];
	} elseif (isset($licenseCounts['PTV'])) {
		VObject::Find(TB_LICENSE, 'code', 'PTV', $portInfo);
		$portName=$portInfo['name'];
		$licenseTypeStr.=" &nbsp;&nbsp;&nbsp;".$portName.": ".$licenseCounts['PTV'];
	}
}

$retPage="provider.php?page=ACCOUNT_LICENSE";
if (SID!='')
	$retPage.="&".SID;

$postLicenseUrl=VM_API."?cmd=SET_BRAND&return=$retPage";
if (SID!='')
	$postLicenseUrl.="&".SID;

// find all brands that use this provider_id
// and create a query string to find all these brands
$othersQuery='';
$query="provider_id='".$provider_id."'";
$errMsg=VObject::SelectAll(TB_BRAND, $query, $result);
if ($errMsg!='') {
	ShowError($errMsg);
	return;
}

$num_rows = mysql_num_rows($result);
while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
	if ($othersQuery!='')
		$othersQuery.=" OR ";
	$othersQuery.="brand_id ='".$row['id']."'";
}


?>


<div class='list_tools'>
<span>License Model:</span>
<span><?php echo $licenseTypeStr?></span>
</div>


<table cellspacing="0" class="meeting_list" >

<tr>
    <th class="pipe"><?php echo $gText['M_ACCOUNT']?></th>
    <th class="pipe"><?php echo $gText['M_ATTENDEES']?></th>
    <th class="pipe"><?php echo $gText['M_LENGTH']?></th>
    <th class="pipe"><?php echo $gText['M_VIDEO_CONF']?></th>
    <th class="pipe">Rec.</th>
    <th class="pipe"><?php echo $gText['M_DISK_QUOTA']?></th>
    <th class="pipe"><?php echo $gText['M_TOTAL']?>*</th>
    <th class="pipe"><?php echo $gText['M_ISSUED']?>*</th>
    <th class="tr"><?php echo $gText['M_AVAILABLE']?>*</th>
</tr>


<?php

$errMsg=VObject::SelectAll(TB_LICENSE, "type='USER'", $result);
if ($errMsg!='') {
	ShowError($errMsg);
	return;
}

$num_rows = mysql_num_rows($result);
$i=0;
while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
	
	$licId=$row['id'];
	
	$issued=0;
	if ($othersQuery!='') {
		$query="($othersQuery) AND (license_id='$licId')";
		VObject::Count(TB_USER, $query, $issued);
	}

	$issuedStr=(string)$issued;
	
	$licCode=$row['code'];
	
	$total=0;
	if ($row['trial']=='Y')
		$total=-1; // unlimited trial
	elseif ($licenseType=='P' || $licenseType=='U') // port or unlimited model
		$total=-1;	// no limit
	elseif (isset($licenses[$licCode])) // a limit is set for this license
		$total=(int)$licenses[$licCode];
	
	if ($total>=0) {
		$available=$total-(int)$issued;
		if ($available<=0) {
			$availStr="<span class='alert'>$available</span>";
		} else {
			$availStr=$available;
		}
	} else {
		$total=$gText['M_NO_LIMIT'];
		$availStr=$gText['M_NO_LIMIT'];
	}
	$i++;
	
	$licName=$row['name'];
	if ($row['expiration']=='0')
		$expStr=$gText['M_NONE'];
	else
		$expStr=$row['expiration']." days";
	
	if ($row['max_att']=='0')
		$attStr=$gText['M_NO_LIMIT'];
	else
		$attStr=$row['max_att'];
	
	if ($row['video_conf']=='N')
		$video=$gText['M_NO'];
	else
		$video=$gText['M_YES'];
		
	if ($row['meeting_length']=='0')
		$length=$gText['M_NO_LIMIT'];
	else
		$length=$row['meeting_length'].' minutes';
	
	if ($row['disk_quota']=='0')
		$disk=$gText['M_NO_LIMIT'];
	else
		$disk=$row['disk_quota']." MB";
	
	$canRecord='Y';
	if ($row['btn_disabled']!='') {
		$btns=explode(",", $row['btn_disabled']);
		foreach ($btns as $abtn) {
			if ($abtn=='record') {
				$canRecord='N';
				break;
			}
		}
	}

	
	print <<<END
<tr>
	<td class="u_item u_item_b">$licName</td>
	<td class="u_item_c">$attStr</td>
	<td class="u_item_c">$length</td>
	<td class="u_item_c">$video</td>
	<td class="u_item_c">$canRecord</td>
	<td class="u_item_c">$disk</td>
	<td class="u_item_c">$total</td>
	<td class="u_item_c">$issuedStr</td>
	<td class="u_item_c">$availStr</td>
</tr>
END;
	
}

?>

</table>



<div class="m_caption">*Total: Total number of licenses under this provider account.</div>
<div class="m_caption">*Issued: Number of licenses issued under this account.</div>
<div class="m_caption">*Available: Number of licenses available to issue under this account.</div>

