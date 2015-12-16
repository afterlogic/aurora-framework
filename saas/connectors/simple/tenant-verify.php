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

	/* @var $oApiTenantsManager CApiTenantsManager */
	$oApiTenantsManager = CApi::GetCoreManager('tenants');
	if (!$oApiTenantsManager)
	{
		$aResult['message'] = 'Internal error';
		return false;
	}

	$sTenantLogin = $aData['tenant_name'].'_'.$aData['partner_login'];

	$iIdTenant = $oApiTenantsManager->getTenantIdByLogin($sTenantLogin);
	if (0 < $iIdTenant)
	{
		$aResult['message-settings-id'] = 'tenant_name';
		$aResult['message'] = $sTenantLogin.' - Tenant already exists';
		$aResult['message-system'] = $oApiTenantsManager->GetLastErrorMessage();
		return false;
	}

	$aResult['result'] = true;
	return true;
}
