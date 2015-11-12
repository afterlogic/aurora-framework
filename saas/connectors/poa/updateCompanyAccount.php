<?php

	include_once __DIR__.'/../../../core/api.php';

	if (!class_exists('CApi') || !CApi::IsValid())
	{
		exit('AfterLogic API isn\'t available');
	}

	$sTenantLogin = isset($_REQUEST['tenant_login']) ? $_REQUEST['tenant_login'] : '';
	$sUserCountLimit = isset($_REQUEST['user_limit']) ? $_REQUEST['user_limit'] : '';

	if (0 === strlen(trim($sTenantLogin)) || 0 === strlen(trim($sUserCountLimit)))
	{
		exit('Invalid argument');
	}

	$oApiTenantManager = CApi::Manager('tenants');
	if ($oApiTenantManager)
	{
		$oTenant = $oApiTenantManager->getTenantById(
			$oApiTenantManager->getTenantIdByLogin($sTenantLogin));

		if ($oTenant)
		{
			$oTenant->UserCountLimit = $sUserCountLimit;

			if (!$oApiTenantManager->updateTenant($oTenant))
			{
				exit($oApiTenantManager->GetLastErrorMessage());
			}
		}
		else
		{
			exit('Tenant does\'t exist');
		}
	}
	else
	{
		exit('Unknown error');
	}
