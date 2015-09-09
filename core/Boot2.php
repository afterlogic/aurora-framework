<?php

/* -AFTERLOGIC LICENSE HEADER- */

$oUserManager = \CApi::GetCoreManager('users');
$oAccount = $oUserManager->getAccountByEmail('test@local.host');
if ($oAccount)
{
	$oModuleManager = \CApi::GetModuleManager();
	$aModules = $oModuleManager->GetModules();
	$aModuleNames = array();
	foreach ($aModules as $oModule)
	{
		if ($oModule && $oModule instanceof AApiModule)
		{
			$aModuleNames[] = $oModule->GetName();
		}
	}
	print_r($aModuleNames);
	
	$aCalendars = $oModuleManager->ExecuteMethod('Calendar', 'GetCalendars', array(
		$oAccount
	));
	if ($aCalendars)
	{
		foreach ($aCalendars as $oCalendarItem)
		{
			if ($oCalendarItem && isset($oCalendarItem['Id']))
			{
				$aEvents = $oModuleManager->ExecuteMethod('Calendar', 'GetEvents', array(
					$oAccount,
					$oCalendarItem['Id'],
					100000,
					time()+1000000
				));
				print_r($aEvents);		
			}
		}
	}
}


