<?php

	include_once __DIR__.'/../../../libraries/afterlogic/api.php';

	if (!class_exists('CApi') || !CApi::IsValid())
	{
		exit('AfterLogic API isn\'t available');
	}

	$sEmail = isset($_REQUEST['email']) ? strtolower(trim($_REQUEST['email'])) : '';
	$sPassword = isset($_REQUEST['password']) ? $_REQUEST['password'] : '';

	if (0 === strlen(trim($sEmail)) || 0 === strlen($sPassword))
	{
		exit('Invalid argument');
	}

	try
	{
		$oApiIntegratorManager = /* @var $oApiIntegratorManager CApiIntegratorManager */ CApi::Manager('integrator');

		if ($oApiIntegratorManager)
		{
			$oAccount = $oApiIntegratorManager->loginToAccount($sEmail, $sPassword);
			if (!$oAccount)
			{
				echo $oApiIntegratorManager->GetLastErrorMessage();
				exit();
			}

			$oApiIntegratorManager->setAccountAsLoggedIn($oAccount);
			CAPi::Location('../../../');
		}
		else
		{
			exit('Unknown error');
		}
	}
	catch (Exception $oException)
	{
		echo $oException->getMessage();
		exit();
	}
