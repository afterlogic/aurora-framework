<?php

require_once dirname(__FILE__).'/../../poa/scripts/Creds.php';
require_once dirname(__FILE__).'/../../poa/scripts/Field.php';
require_once dirname(__FILE__).'/../../poa/scripts/ArrayTraits.php';
require_once dirname(__FILE__).'/../../poa/scripts/StructuredOutput.php';
require_once dirname(__FILE__).'/../../poa/scripts/Utils.php';

ini_set("soap.wsdl_cache_enabled", 0);
define('NL', "\n");

//$oClient = new SoapClient('http://ray.afterlogic.com/p7/saas/connectors/soap/TenantsManager.wsdl', array('trace' => 1));
//$oClient = new SoapClient('http://quickme.net/saas/connectors/soap/TenantsManager.wsdl', array('trace' => 1));
$oClient = new SoapClient('http://221.afterlogic.com/saas/connectors/soap/TenantsManager.wsdl', array('trace' => 1));

try
{
$creds = new TenantCreds();
$creds->login = 'quickme';
$creds->login = 'ch2';
$creds->login = 'dasreda';
$creds->login = 'dasreda_test';
$creds->password = 'b843999446ca8dbb1714d292dde80a2d'; // afterlogic
$creds->password = 'a2be9395b8a3187d3c88ea2ef2d2ecb9'; // quickme
$creds->password = '41cf8420446048ff879f5e737c825706'; // ch2
$creds->password = '4f3ebcf0a7daac9206b9e456eda89d13'; // dasreda
$creds->password = '477dd036d0bc9ca298d223326423338d'; // dasreda_test

$tenantName = new Field('name', 'akosykh@gmail.com');
$tenantPassword = new Field('password', 'qwe123QWE');
$tenantChannel = new Field('channelLogin', $creds->login);
$tenantQuota = new Field('quota', 5555 * 1000);
$userCountLimit = new Field('userCountLimit', 333);
$enableLogin = new Field('enableLogin', 1);

$tenantFields = array($tenantName, $tenantPassword, $tenantChannel, $tenantQuota, $userCountLimit, $enableLogin);
//$creds->tenantId = $oClient->addInstance($tenantFields, $creds);

var_dump($oClient->listInstances($creds));

}
catch (Exception $oE)
{
	var_dump($oE);
	throw $oE;
}

echo "DONE\n" ;
