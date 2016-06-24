<?php

	include_once __DIR__.'/../../../core/api.php';

	if (!class_exists('CApi') || !CApi::IsValid())
	{
		exit('AfterLogic API isn\'t available');
	}

	$sEmail = isset($_REQUEST['email']) ? strtolower(trim($_REQUEST['email'])) : '';
	$sLogin = isset($_REQUEST['login']) ? $_REQUEST['login'] : '';
	$sPassword = isset($_REQUEST['password']) ? $_REQUEST['password'] : '';
//	$sDomain = isset($_REQUEST['domain']) ? strtolower(trim($_REQUEST['domain'])) : ''; // TODO ?

	if (0 === strlen(trim($sEmail)) || 0 === strlen(trim($sLogin)) || 0 === strlen($sPassword))
	{
		exit('Invalid argument');
	}

	$oApiTenantManager = CApi::GetSystemManager('tenants');

	if ($oApiTenantManager)
	{
		$oTenant = new CTenant($sLogin);
		$oTenant->setPassword($sPassword);
		$oTenant->Email = $sEmail;
		
		if (!$oApiTenantManager->createTenant($oTenant))
		{
			exit($oApiTenantManager->GetLastErrorMessage());
		}
	}
	else
	{
		exit('Unknown error');
	}
