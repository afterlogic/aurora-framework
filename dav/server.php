<?php
/**
 * This code is licensed under AGPLv3 license or Afterlogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 *
 * @license https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @license https://afterlogic.com/products/common-licensing Afterlogic Software License
 * @copyright Copyright (c) 2019, Afterlogic Corp.
 */

$sCurrentFile = \basename(__FILE__);
$sRequestUri = empty($_SERVER['REQUEST_URI']) ? '' : \trim($_SERVER['REQUEST_URI']);

$iLen = 4 + \strlen($sCurrentFile);
if (\strlen($sRequestUri) >= $iLen && 'dav/'.$sCurrentFile === \substr($sRequestUri, -$iLen))
{
	\header('Location: ./server.php/');
	exit();
}

require_once \dirname(__FILE__).'/../autoload.php';

\Aurora\System\Api::Init();


//http_response_code(401); exit;

\set_time_limit(3000);
\set_error_handler(function ($errno, $errstr, $errfile, $errline) {
	throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
});

// CApi::$bUseDbLog = false;

$sBaseUri = false === \strpos($sRequestUri, 'dav/'.$sCurrentFile) ? '/' :
	\substr($sRequestUri, 0, \strpos($sRequestUri,'/'.$sCurrentFile)).'/'.$sCurrentFile.'/';

\Afterlogic\DAV\Server::getInstance($sBaseUri)->exec();
