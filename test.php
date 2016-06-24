<?php

if (!defined('PSEVEN_APP_ROOT_PATH'))
{
	define('PSEVEN_APP_ROOT_PATH', rtrim(realpath(__DIR__), '\\/').'/');
}
var_dump(getenv('PHP_BINARY'));
var_dump(getenv('PHPBIN'));
var_dump(getenv('PHP_BIN'));
var_dump(getenv('PHP_BINDIR'));

 function find($name, $default = null, array $extraDirs = array())
    {
        if (ini_get('open_basedir')) {
            $searchPath = explode(PATH_SEPARATOR, ini_get('open_basedir'));
            $dirs = array();
            foreach ($searchPath as $path) {
                // Silencing against https://bugs.php.net/69240
                if (@is_dir($path)) {
                    $dirs[] = $path;
                } else {
                    if (basename($path) == $name && is_executable($path)) {
                        return $path;
                    }
                }
            }
        } else {
            $dirs = array_merge(
                explode(PATH_SEPARATOR, getenv('PATH') ?: getenv('Path')),
                $extraDirs
            );
        }
        $suffixes = array('');
        if ('\\' === DIRECTORY_SEPARATOR) {
            $pathExt = getenv('PATHEXT');
            $suffixes = $pathExt ? explode(PATH_SEPARATOR, $pathExt) : array('.exe', '.bat', '.cmd', '.com');
        }
        foreach ($suffixes as $suffix) {
            foreach ($dirs as $dir) {
                if (is_file($file = $dir.DIRECTORY_SEPARATOR.$name.$suffix) && ('\\' === DIRECTORY_SEPARATOR || is_executable($file))) {
                    return $file;
                }
            }
        }
        return $default;
    }
	
echo find('php');

exit;
// utilizing WebMail Pro API
include_once __DIR__.'/system/api.php';


$sss = \api_Utils::EncryptValue('p12345');
var_dump($sss);
var_dump(\api_Utils::DecryptValue($sss));
var_dump(trim(\api_Utils::DecryptValue('SyDxsEwSIWqa3yxCYbygRMewjh2F03uBcvxFjK60qE4E4QQL8f10GmqK4M4529Sziu5RaB2n+On5SJnM5gxnBg==')));

exit;

/*
$oAuthDecorator = \CApi::GetModuleDecorator('BasicAuth');

$mResult = $oAuthDecorator->CreateAccount(
	246,
	'test4',
	'p12345'
);
 * 
 */

$oEavManager = \CApi::GetSystemManager('eav', 'db');
class CTest extends AEntity
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
		
		$this->setStaticMap(array(
			'Test'	=> array('string', '')
		));
		
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
$oNew = $oEavManager->getEntities('CTest');

print_r($oNew);


//$oAuthDecorator = \CApi::GetModuleDecorator('BasicAuth');

//$oAuthDecorator->CreateAccount(0, 246, 'test555', 'p12345');

/*$oManagerApi = \CApi::GetModule('BasicAuth')->GetManager('accounts');
$aItems = $oManagerApi->getAccountList(0, 0);*/

	