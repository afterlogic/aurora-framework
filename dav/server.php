<?php

/* -AFTERLOGIC LICENSE HEADER- */

$sCurrentFile = \basename(__FILE__);
$sRequestUri = empty($_SERVER['REQUEST_URI']) ? '' : \trim($_SERVER['REQUEST_URI']);

$iLen = 4 + \strlen($sCurrentFile);
if (\strlen($sRequestUri) >= $iLen && 'dav/'.$sCurrentFile === \substr($sRequestUri, -$iLen))
{
	\header('Location: ./server.php/');
	exit();
}

if (!defined('PSEVEN_APP_ROOT_PATH'))
{
	$sV = PHP_VERSION;
	if (-1 === version_compare($sV, '5.3.0') || !function_exists('spl_autoload_register'))
	{
		echo
			'PHP '.$sV.' detected, 5.3.0 or above required.
			<br />
			<br />
			You need to upgrade PHP engine installed on your server.
			If it\'s a dedicated or your local server, you can download the latest version of PHP from its
			<a href="http://php.net/downloads.php" target="_blank">official site</a> and install it yourself.
			In case of a shared hosting, you need to ask your hosting provider to perform the upgrade.';
		
		exit(0);
	}

	define('PSEVEN_APP_ROOT_PATH', rtrim(realpath(__DIR__ . '/../'), '\\/').'/');
	define('PSEVEN_APP_START', microtime(true));

	require_once dirname(__FILE__).'/../system/api.php';

	\set_time_limit(3000);
	\set_error_handler(function ($errno, $errstr, $errfile, $errline) {
		throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
	});

	// CApi::$bUseDbLog = false;

	$sBaseUri = false === \strpos($sRequestUri, 'dav/'.$sCurrentFile) ? '/' :
		\substr($sRequestUri, 0, \strpos($sRequestUri,'/'.$sCurrentFile)).'/'.$sCurrentFile.'/';

	$oServer = \Afterlogic\DAV\Server::getInstance($sBaseUri);
	$oServer->exec();
}
