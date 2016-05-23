<?php

if (!defined('PSEVEN_APP_ROOT_PATH'))
{
	define('PSEVEN_APP_ROOT_PATH', rtrim(realpath(__DIR__), '\\/').'/');
}

// utilizing WebMail Pro API
include_once __DIR__.'/core/api.php';

$oAuthDecorator = \CApi::GetModuleDecorator('Auth');

$mResult = $oAuthDecorator->CreateAccount(
	246,
	'test4',
	'p12345'
);


	