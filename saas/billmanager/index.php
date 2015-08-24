<?php

/* -AFTERLOGIC LICENSE HEADER- */

if (!defined('PSEVEN_APP_ROOT_PATH'))
{
	define('PSEVEN_APP_ROOT_PATH', rtrim(realpath(__DIR__), '\\/').'/../../');
	define('PSEVEN_APP_LIBRARY_PATH', PSEVEN_APP_ROOT_PATH.'libraries/Core/');
	define('PSEVEN_APP_START', microtime(true));

	include PSEVEN_APP_ROOT_PATH.'libraries/afterlogic/api.php';
	include PSEVEN_APP_ROOT_PATH.'saas/connectors/billmanager.php';
}
else
{
	echo 'Error: Unknown error';
}