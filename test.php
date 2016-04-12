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

$iCount = $oEavManager->getObjectsCount('CUser'); 
echo $iCount;

$aObjects = $oEavManager->getObjects('CUser');
print_r($aObjects);

//$oAccount->Description = 'Description';
//$oEavManager->saveObject($oAccount);

//print_r($oEavManager->getObjectById($oAccount->iObjectId));

//$oEavManager->deleteObject($oAccount->iObjectId);



	