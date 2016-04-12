<?php

if (!defined('PSEVEN_APP_ROOT_PATH'))
{
	define('PSEVEN_APP_ROOT_PATH', rtrim(realpath(__DIR__), '\\/').'/');
}

// utilizing WebMail Pro API
include_once __DIR__.'/core/api.php';

$oCoreDecorator = CApi::GetModuleDecorator('Core');
$mResult = $oCoreDecorator->SetMobile(true, false);

var_dump($mResult);


	