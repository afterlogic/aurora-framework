<?php

/* -AFTERLOGIC LICENSE HEADER- */

defined('PSEVEN_APP_ROOT_PATH') || define('PSEVEN_APP_ROOT_PATH', (dirname(__FILE__).'/../'));

include_once PSEVEN_APP_ROOT_PATH.'core/api.php';

if (class_exists('CApi') && CApi::IsValid())
{
	$oApiHelpdeskManager = CApi::Manager('helpdesk');
	if ($oApiHelpdeskManager)
	{
		$oApiHelpdeskManager->notificateOutdatedThreads();
	}
}
