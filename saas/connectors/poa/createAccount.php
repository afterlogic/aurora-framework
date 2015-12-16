<?php

	include_once __DIR__.'/../../../core/api.php';
	
	if (!class_exists('CApi') || !CApi::IsValid())
	{
		exit('AfterLogic API isn\'t available');
	}
	
	$sEmail = isset($_REQUEST['email']) ? strtolower(trim($_REQUEST['email'])) : '';
	$sLogin = $sEmail;
	$sDomain = isset($_REQUEST['domain']) ? strtolower(trim($_REQUEST['domain'])) : '';
	$sPassword = isset($_REQUEST['password']) ? $_REQUEST['password'] : '';

	if (0 === strlen(trim($sEmail)) || 0 === strlen(trim($sDomain)) || 0 === strlen($sPassword))
	{
		exit('Invalid argument');
	}
	
	$oApiDomainsManager = CApi::GetCoreManager('domains');
	$oApiUsersManager = CApi::GetCoreManager('users');
	
	if ($oApiUsersManager && $oApiDomainsManager)
	{
		$oDomain = $oApiDomainsManager->getDomainByName($sDomain);
		if (!$oDomain)
		{
			exit($oApiDomainsManager->GetLastErrorMessage());
		}
		
		$oAccount = new CAccount($oDomain);
		
		$oAccount->Email = $sEmail;
		$oAccount->IncomingMailLogin = $sLogin;
		$oAccount->IncomingMailPassword = $sPassword;

		if (!$oApiUsersManager->createAccount($oAccount))
		{
			exit($oApiUsersManager->GetLastErrorMessage());
		}
	}
	else
	{
		exit('Unknown error');
	}
