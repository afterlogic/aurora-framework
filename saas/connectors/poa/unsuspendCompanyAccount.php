<?php

	include_once __DIR__.'/../../../core/api.php';

	if (!class_exists('CApi') || !CApi::IsValid())
	{
		exit('AfterLogic API isn\'t available');
	}

	$sTenantLogin = isset($_REQUEST['tenant_login']) ? $_REQUEST['tenant_login'] : '';

	if (0 === strlen(trim($sTenantLogin)))
	{
		exit('Invalid argument');
	}

	$oApiTenantManager = CApi::GetCoreManager('tenants');
	if ($oApiTenantManager)
	{
		$oTenant = $oApiTenantManager->getTenantById(
			$oApiTenantManager->getTenantIdByLogin($sTenantLogin));

		if ($oTenant)
		{
			$oTenant->IsDisabled = false;

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
