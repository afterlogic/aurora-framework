<?php

/* -AFTERLOGIC LICENSE HEADER- */

	// remove the following line for real use
//	exit('remove this line');

	// Example of logging into WebMail account using email and password for incorporating into another web application

	// utilizing API
	include_once __DIR__.'/../core/api.php';

	if (class_exists('CApi') && CApi::IsValid())
	{
		// Getting required API class
		$oApiUsersManager = CApi::Manager('users');

		$oAccount = $oApiUsersManager->getAccountByEmail('ray@afterlogic.com');
		if ($oAccount)
		{
			$oApiVoiceManager = /* @var $oApiVoiceManager \CApiVoiceManager */ CApi::Manager('voice');
			var_dump($oApiVoiceManager->GetNamesByCallersNumbers($oAccount, array('777888', '55 55', '+7777 596')));

//			$oApiVoiceManager = /* @var $oApiVoiceManager \CApiVoiceManager */ CApi::Manager('voice');
//			if ($oApiVoiceManager) {
//				$oApiVoiceManager->flushCallersNumbersCache($oAccount);
//			}
		}
	}
	else
	{
		echo 'AfterLogic API isn\'t available';
	}