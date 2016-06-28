<?php

if (!defined('PSEVEN_APP_ROOT_PATH'))
{
	define('PSEVEN_APP_ROOT_PATH', rtrim(realpath(__DIR__), '\\/').'/');
}

// utilizing WebMail Pro API
include_once __DIR__.'/system/api.php';

$oEavManager = \CApi::GetSystemManager('eav', 'db');
$aUsers = $oEavManager->getEntities('CUser');

foreach($aUsers as $oUser)
{
	$oCalendarModule = CApi::GetModule('Calendar');
	if ($oCalendarModule)
	{
		$oCalendarModule->setDisabledForEntity($oUser);
//		$oCalendarModule->setEnabledForEntity($oUser);
	}
	$oContactsModule = CApi::GetModule('Contacts');
	if ($oContactsModule)
	{
		$oContactsModule->setDisabledForEntity($oUser);
//		$oContactsModule->setEnabledForEntity($oUser);
	}
}


	