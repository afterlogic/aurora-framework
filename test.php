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
	
	public static function createInstanse($sModule = 'Core')
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
			'New'		=> array('string', 'Default value'),
			'IdDomain'	=> array('int', 0),
			'Description'	=> array('text', '')
		);
	}	
}

/* var $oEavManager \CApiEavManager */
$oEavManager = \CApi::GetCoreManager('eav', 'db');

$oAccount = Account::createInstanse('Core');
$oAccount->Name = 'Test' . time();
$oAccount->Email = 'test' . time() . '@local.host';
$oAccount->Phone = '123-45-67';

$oEavManager->saveObject($oAccount);


$aObjects = $oEavManager->getObjectsByType('Account', 
		array(
			'Name', 
			'Email', 
			'Phone',
			'IdDomain',
			'Description'
		), 0, 9999,
		array(), 
		'Sort', \ESortOrder::ASC
);
print_r($aObjects);


$oAccount->Description = 'Description';

$oEavManager->saveObject($oAccount);

print_r($oEavManager->getObjectById($oAccount->iObjectId));

$oEavManager->deleteObject($oAccount->iObjectId);


	