<?php

if (!defined('PSEVEN_APP_ROOT_PATH'))
{
	define('PSEVEN_APP_ROOT_PATH', rtrim(realpath(__DIR__), '\\/').'/');
}

// utilizing WebMail Pro API
include_once __DIR__.'/system/api.php';

/*
$oAuthDecorator = \CApi::GetModuleDecorator('Auth');

$mResult = $oAuthDecorator->CreateAccount(
	246,
	'test4',
	'p12345'
);
 * 
 */

$oEavManager = \CApi::GetCoreManager('eav', 'db');
class CTest extends api_APropertyBag
{
	/**
	 * Creates a new instance of the object.
	 * 
	 * @return void
	 */
	public function __construct($sModule)
	{
		parent::__construct(get_class($this), $sModule);
		
		$this->__USE_TRIM_IN_STRINGS__ = true;
		
		$this->aStaticMap = array(
			'Test'	=> array('string', '')
		);
		
		$this->SetDefaults();
	}
	
	public static function createInstance()
	{
		return new self('Test');
	}

	/**
	 * Checks if the user has only valid data.
	 * 
	 * @return bool
	 */
	public function isValid()
	{
		return true;
	}
}

$oTest = new CTest('Test');
$oTest->Test = 'test';
$oTest->Test1 = 'test1';
$oTest->Test2 = 'test2';

//$iObjectId = $oEavManager->saveObject($oTest);
$oNew = $oEavManager->getObjects('CTest', array('Test1', 'Test2', 'Test3'), 0, 0, array('Test1' => 'test1'));

foreach ($oNew as $oOBj)
{
	print_r($oOBj->Test1);
}


//$oAuthDecorator = \CApi::GetModuleDecorator('Auth');

//$oAuthDecorator->CreateAccount(0, 246, 'test555', 'p12345');

/*$oManagerApi = \CApi::GetModule('Auth')->GetManager('accounts');
$aItems = $oManagerApi->getAccountList(0, 0);*/

	