<?php

if (!defined('PSEVEN_APP_ROOT_PATH'))
{
	define('PSEVEN_APP_ROOT_PATH', rtrim(realpath(__DIR__), '\\/').'/');
}

// utilizing WebMail Pro API
include_once __DIR__.'/core/api.php';

class Account extends api_APropertyBag
{
	public function __construct($sModule)
	{
		parent::__construct(get_class($this), $sModule);

		$this->__USE_TRIM_IN_STRINGS__ = true;

		$this->SetDefaults();
	}
	
	public static function createInstanse($sModule)
	{
		return new Account($sModule);
	}

	/**
	 * @return array
	 */
	public function getMap()
	{
		return self::getStaticMap();
	}

	/**
	 * @return array
	 */
	public static function getStaticMap()
	{
		return array(
			'Name'		=> array('string', ''),
			'Email'		=> array('string', ''),
			'Phone'		=> array('string', ''),
			'New'		=> array('string', 'Default value')
		);
	}	
}

/* var $oEavManager \CApiEavManager */
$oEavManager = \CApi::GetCoreManager('eav', 'db');

$oAccount = Account::createInstanse('Core');
$oAccount->Name = 'Test' . time();
$oAccount->Email = 'test' . time() . '@local.host';
$oAccount->Phone = '123-45-67';

$oEavManager->createObject($oAccount);

$aObjects = $oEavManager->getObjectsByType('Account', 
		array(
			'Name', 
			'Email', 
			'Phone', 
			'Undefined'
		), 0, 9999,
		array(/*'Name' => 'Test1'*/),
		'Sort', \ESortOrder::ASC
);

print_r($aObjects);

$oAccount->New = 'New!!';

$oEavManager->updateObject($oAccount);

print_r($oEavManager->getObjectById($oAccount->iObjectId));

//$oEavManager->deleteObject($oAccount->iObjectId);


	