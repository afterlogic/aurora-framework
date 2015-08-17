<?php

function script_simple_task(&$aResult, $aData)
{
	foreach (array('tenant_name', 'partner_login', 'partner_password') as $sProp)
	{
		if (!isset($aData[$sProp]))
		{
			$aResult['message-settings-id'] = $sProp;
			$aResult['message'] = $sProp.' - Invalid value';
			return true;
		}
	}

	$oChannel = validatePartner($aResult, $aData);
	if (!$oChannel)
	{
		return true;
	}

	$oTenant = validateTenant($aResult, $aData, $oChannel);
	if (!$oTenant)
	{
		return true;
	}

	$aResult['result'] = true;
	$aResult['data'] = array(
		'type' => 'tenant-resource',
		'resources' => array(
			'account_usage' => $oTenant->getUserCount(),
			'tenant_diskusage' => round($oTenant->AllocatedSpaceInMB)
		)
	);

	return true;
}
