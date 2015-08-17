<?php

require_once dirname(__FILE__).'/Creds.php';
require_once dirname(__FILE__).'/Field.php';
require_once dirname(__FILE__).'/ArrayTraits.php';
require_once dirname(__FILE__).'/StructuredOutput.php';
require_once dirname(__FILE__).'/Utils.php';

ini_set('soap.wsdl_cache_enabled', '0');
define('NL', "\n");

$command = isset($argv[1]) ? $argv[1] : '';
if ($command !== 'install')
{
	exit(0);
}

try
{
	$site = settingCheckedValue('site', 'checkNotEmpty', 'Invalid value');
	$tenantService = new SoapClient($site.'/saas/connectors/soap/'.'TenantsManager.wsdl');

	// Fill creds
	$creds = new DomainCreds();
	$creds->bTry = true;
	$creds->login = settingCheckedValue('partner_login');
	$creds->password = settingCheckedValue('partner_password');
	$creds->tenantId = 0;

	// Fill tenant fields
	$tenantName = settingCheckedValue('tenant_name');
	$tenantNameField = new Field('name', $tenantName);

	// Search instance
	$tenantSearchFields = array($tenantNameField);
	$instances = $tenantService->findInstances($tenantSearchFields, $creds);
	if ($instances && sizeof($instances))
	{
		reportError(makeMsg('tenant_name', 'Tenant already exists'));
		exit(0);
	}
}
catch (InvalidSettings $err)
{
	reportErrors($err->getErrors());
}
catch (Exception $ex)
{
	$message = '';
	if ($ex->getMessage() === 'ServiceException')
	{
		$message = 'Exception:'.$ex->detail->ServiceException->message.NL;
	}
	else
	{
		$message = 'Exception:'.$ex->getMessage().NL;
	}

	reportError(makeMsg('site', $message));
}

exit(0);
