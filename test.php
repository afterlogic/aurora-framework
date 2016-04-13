<?php

if (!defined('PSEVEN_APP_ROOT_PATH'))
{
	define('PSEVEN_APP_ROOT_PATH', rtrim(realpath(__DIR__), '\\/').'/');
}

// utilizing WebMail Pro API
include_once __DIR__.'/core/api.php';

//$oCoreDecorator = \CApi::GetModuleDecorator('Core');
//$mResult = $oCoreDecorator->SetMobile(false);

//var_dump($mResult);


/* var $oEavManager \CApiEavManager */
$oEavManager = \CApi::GetCoreManager('eav', 'db');

//$iCount = $oEavManager->getObjectsCount('CUser'); 
//echo $iCount;

$aObjects = $oEavManager->getObjects('CUser');
var_dump($aObjects[0]);

$aObjects[0]->{'Core::TestString'} = 'aaa';

$oEavManager->saveObject($aObjects[0]);

var_dump($oEavManager->getObjectById($aObjects[0]->iObjectId));

//$oEavManager->deleteObject($oAccount->iObjectId);



	