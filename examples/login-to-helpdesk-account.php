<?php

/* -AFTERLOGIC LICENSE HEADER- */

// remove the following line for real use
exit('remove this line');

// Example of logging into Helpdesk account using email, password and tenant hash for incorporating into another web application

// utilizing API
include_once __DIR__.'/../libraries/afterlogic/api.php';

if (class_exists('CApi') && CApi::IsValid())
{
	// data for logging into account
	$sName = 'user'; //display name
	$sEmail = 'user@domain.com';
	$sPassword = '12345';
	$sTenantHash = ''; //fill if tenant system

//	$sName = '';
//	$sEmail = 'ivaniv333@gmail.com';
//	$sPassword = 'ivaniv';
//	$sTenantHash = '';

	try
	{
		// Getting required API class
		$oApiIntegratorManager = CApi::Manager('integrator');
		$oApiHelpdeskManager = CApi::Manager('helpdesk');
		$oApiTenantsManager = CApi::Manager('tenants');

		$iIdTenant = $oApiTenantsManager->getTenantIdByHash($sTenantHash);

		// checking existence of user
		$oUser = $oApiHelpdeskManager->GetUserByEmail($iIdTenant, $sEmail);

		if($oUser)
		{
			// attempting to obtain object for account we're trying to log into
			$oAccount = $oApiIntegratorManager->loginToHelpdeskAccount($iIdTenant, $sEmail, $sPassword);

			if ($oAccount)
			{
				// populating session data from the account
				$oApiIntegratorManager->setHelpdeskUserAsLoggedIn($oAccount, false);

				// redirecting to Aurora Helpdesk
				CApi::Location('../?helpdesk');
			}
			else
			{
				// login error
				echo $oApiIntegratorManager->GetLastErrorMessage();
			}
		}
		else
		{
			// registering helpdesk account
			if (!!$oApiIntegratorManager->registerHelpdeskAccount($iIdTenant, $sEmail, $sName, $sPassword))
			{
				echo 'account successfully registered, please confirm registration and login';
			}
		}
	}
	catch (Exception $oException)
	{
		$iCode = $oException->getCode();

		// if logged into Helpdesk account
		if($iCode === CApiErrorCodes::HelpdeskManager_AccountSystemAuthentication)
		{
			// redirecting to Aurora Helpdesk
			CApi::Location('../?helpdesk');
		}
		else
		{
			// login error
			echo $oException->getMessage();
		}
	}
}
else
{
	echo 'AfterLogic API isn\'t available';
}