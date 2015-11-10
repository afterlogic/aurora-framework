<?php

/* -AFTERLOGIC LICENSE HEADER- */

	if (!defined('PSEVEN_APP_ROOT_PATH'))
	{
		define('PSEVEN_APP_ROOT_PATH', dirname(rtrim(realpath(__DIR__), '\\/')).'/');
	}
	
	include 'core/cadminpanel.php';
	
	$oAdminPanel = new CAdminPanel(__FILE__);
	$oAdminPanel->Run()->End();