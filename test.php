<?php

if (!defined('PSEVEN_APP_ROOT_PATH'))
{
	define('PSEVEN_APP_ROOT_PATH', rtrim(realpath(__DIR__), '\\/').'/');
}

// utilizing WebMail Pro API
include_once __DIR__.'/core/api.php';

/*
$oAuthDecorator = \CApi::GetModuleDecorator('Auth');

$mResult = $oAuthDecorator->CreateAccount(
	246,
	'test4',
	'p12345'
);
 * 
 */

$oAuthDecorator = \CApi::GetModuleDecorator('Auth');

$oAuthDecorator->CreateAccount(0, 246, 'test555', 'p12345');

/*$oManagerApi = \CApi::GetModule('Auth')->GetManager('accounts');
$aItems = $oManagerApi->getAccountList(0, 0);*/

	