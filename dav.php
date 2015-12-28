<?php

/* -AFTERLOGIC LICENSE HEADER- */

if (!defined('PSEVEN_APP_ROOT_PATH')) {
	
	define('PSEVEN_APP_ROOT_PATH', rtrim(realpath(__DIR__), '\\/').'/');
}

require_once dirname(__FILE__).'/core/api.php';

\set_time_limit(3000);

/* Mapping PHP errors to exceptions */
function exception_error_handler($errno, $errstr, $errfile, $errline )
{
	throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
}

\set_error_handler(function ($errno, $errstr, $errfile, $errline)  {
	
	throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
});

// CApi::$bUseDbLog = false;

$sCurrentFile = \basename(__FILE__);
$sRequestUri = empty($_SERVER['REQUEST_URI']) ? '' : \trim($_SERVER['REQUEST_URI']);

$sBaseUri = false === \strpos($sRequestUri, $sCurrentFile) ? '/' :
	\substr($sRequestUri, 0, \strpos($sRequestUri,'/'.$sCurrentFile)).'/'.$sCurrentFile.'/';

\Afterlogic\DAV\Server::getInstance($sBaseUri)->exec();
