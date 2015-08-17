<?php

require_once dirname(__FILE__).'/Creds.php';
require_once dirname(__FILE__).'/Field.php';
require_once dirname(__FILE__).'/ArrayTraits.php';
require_once dirname(__FILE__).'/Utils.php';

ini_set('soap.wsdl_cache_enabled', '0');
define('NL', "\n");

try
{
	$site = settingCheckedValue('site');
	$tenantService = new SoapClient($site.'/saas/connectors/soap/'.'TenantsManager.wsdl');

	// Fill creds
	$creds = new UserCreds();
	$creds->login = settingCheckedValue('partner_login');
	$creds->password = settingCheckedValue('partner_password');

	$tenantSearchFields = array(new Field('name', settingCheckedValue('tenant_name')));
	$instances = $tenantService->findInstances($tenantSearchFields, $creds);
	if (!$instances || count($instances) > 1)
	{
		echo('Tenant not found');
		exit(1);
	}

	$instanceFields = ArrayTraits::toPHPArray($instances[0]);
	$creds->tenantId = $instanceFields['id'];

	$diskUsage = round($tenantService->field('diskUsage', $creds));
	$userCount = (int) $tenantService->field('userCount', $creds);

	echo '<'.'?xml version="1.0"?'.'>'.NL.
		'<resources xmlns="http://apstandard.com/ns/1/resource-output">'.NL.
		'<resource id="account_usage" value="'.$userCount.'"/>'.NL.
		'<resource id="tenant_diskusage" value="'.$diskUsage.'"/>'.NL.
		'</resources>'.NL;
}
catch (Exception $ex)
{
	if ($ex->getMessage() === 'ServiceException')
	{
		echo $ex->detail->ServiceException->message.NL;
	}
	else
	{
		echo $ex->getMessage().NL;
	}

	exit(1);
}

exit(0);
