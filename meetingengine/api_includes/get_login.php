<?php
/**
 * Persony Web Conferencing 2.0
 * @author      Persony, Inc. <info@persony.com>
 * @copyright   Copyright 2008 Persony, Inc.
 * @version     2.0
 * 
 */


$memberId=GetSessionValue('member_id');
$memberPerm=GetSessionValue('member_perm');
$memberBrandName=GetSessionValue('member_brand_name');

GetArg('brand', $mbrand);

if ($mbrand=='')
	echo "ERROR";
elseif ($memberBrandName!='' && $memberBrandName==$mbrand)
	echo "OK ".$memberId." ".$memberPerm." ".$memberBrandName;
else
	echo "ERROR Not signed in";
exit();
?>