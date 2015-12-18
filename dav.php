<?php

/* -AFTERLOGIC LICENSE HEADER- */
if (!defined('PSEVEN_APP_ROOT_PATH'))
{
	define('PSEVEN_APP_ROOT_PATH', rtrim(realpath(__DIR__), '\\/').'/');
}

require_once dirname(__FILE__).'/core/api.php';

/* Mapping PHP errors to exceptions */
function exception_error_handler($errno, $errstr, $errfile, $errline )
{
	throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
}

\set_time_limit(3000);
\set_error_handler(function ($errno, $errstr, $errfile, $errline) 
{
	throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
});

// CApi::$bUseDbLog = false;

$baseUri = substr($_SERVER['REQUEST_URI'], 0, strpos($_SERVER['REQUEST_URI'],'/'.basename(__FILE__))).'/'.basename(__FILE__).'/';
\afterlogic\DAV\Server::NewInstance($baseUri)->exec();
