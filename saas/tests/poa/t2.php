<?php

require_once dirname(__FILE__).'/../../poa/scripts/Creds.php';
require_once dirname(__FILE__).'/../../poa/scripts/Field.php';
require_once dirname(__FILE__).'/../../poa/scripts/ArrayTraits.php';
require_once dirname(__FILE__).'/../../poa/scripts/StructuredOutput.php';
require_once dirname(__FILE__).'/../../poa/scripts/Utils.php';

ini_set("soap.wsdl_cache_enabled", 0);
define('NL', "\n");

//$oClient = new SoapClient('http://ray.afterlogic.com/p7/saas/connectors/soap/TenantsManager.wsdl', array('trace' => 1));
$oClient = new SoapClient('http://quickme.net/saas/connectors/soap/TenantsManager.wsdl', array('trace' => 1));

try
{
	$creds = new TenantCreds();
	$creds->login = 'quickme';
	$creds->password = 'b843999446ca8dbb1714d292dde80a2d'; // afterlogic
	$creds->password = 'a2be9395b8a3187d3c88ea2ef2d2ecb9'; // quickme

	$tenantSearchFields = array(new Field('name', 'rayman1004369_quickme'));
	$instances = $oClient->findInstances($tenantSearchFields, $creds);
	if (!$instances || count($instances) > 1)
	{
		echo('Tenant not found');
		exit(1);
	}

	$instanceFields = ArrayTraits::toPHPArray($instances[0]);
	$creds->tenantId = $instanceFields['id'];

	$diskUsage = round($oClient->field('diskUsage', $creds));
	$userCount = (int) $oClient->field('userCount', $creds);

	echo '<pre>';
	echo htmlspecialchars('<'.'?xml version="1.0"?'.'>'.NL.
		'<resources xmlns="http://apstandard.com/ns/1/resource-output">'.NL.
		'<resource id="account_usage" value="'.$userCount.'"/>'.NL.
		'<resource id="tenant_diskusage" value="'.$diskUsage.'"/>'.NL.
		'</resources>'.NL);
}
catch (Exception $oE)
{
	var_dump($oE);
	throw $oE;
}

