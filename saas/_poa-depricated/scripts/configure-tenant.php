<?php

require_once dirname(__FILE__).'/Creds.php';
require_once dirname(__FILE__).'/Field.php';
require_once dirname(__FILE__).'/ArrayTraits.php';
require_once dirname(__FILE__).'/StructuredOutput.php';
require_once dirname(__FILE__).'/Utils.php';

ini_set('soap.wsdl_cache_enabled', '0');
define('NL', "\n");

if (!is_array($argv) || count($argv) < 2 || empty($argv[1]) ||
	!in_array($argv[1], array('install', 'disable', 'enable', 'configure', 'remove', 'upgrade')))
{
	reportError(makeMsg('site', 'Usage: install | disable | enable | configure | remove'));
	exit(1);
}

try
{
	$site = settingCheckedValue('site');
	$tenantService = new SoapClient($site.'/saas/connectors/soap/'.'TenantsManager.wsdl');

	// Fill creds
	$creds = new TenantCreds();
	$creds->login = settingCheckedValue('partner_login');
	$creds->password = settingCheckedValue('partner_password');

	// Fill tenant fields
	$tenantName = new Field('name', settingCheckedValue('tenant_name'));
	$tenantPassword = new Field('password', settingCheckedValue('tenant_password'));
	$tenantChannel = new Field('channelLogin', $creds->login);
	$tenantQuota = new Field('quota', settingCheckedValue('tenant_quota_default_mb'));
	$userCountLimit = new Field('userCountLimit', settingCheckedValue('account_limit'));
	$enableLogin = new Field('enableLogin', 1);

	if ($tenantQuota && 0 > $tenantQuota->value)
	{
		$tenantQuota->value = 0;
	}
	
	if ($userCountLimit && 0 > $userCountLimit->value)
	{
		$userCountLimit->value = 0;
	}

	// Dispatch commands
	$command = $argv[1];
	if ($command == 'install')
	{
		$tenantFields = array($tenantName, $tenantPassword, $tenantChannel, $tenantQuota, $userCountLimit, $enableLogin);
		$creds->tenantId = $tenantService->addInstance($tenantFields, $creds);
		if (!$creds->tenantId)
		{
			reportError(makeMsg('tenant_name', 'Can\'t create tenant'));
			exit(1);
		}
		
		exit(0);
	}

	// Search instance
	$tenantSearchFields = array($tenantName);
	$instances = $tenantService->findInstances($tenantSearchFields, $creds);
	if (!$instances || count($instances) > 1)
	{
		reportError(makeMsg('tenant_name', 'Tenant not found'));
		exit(1);
	}

	$instanceFields = ArrayTraits::toPHPArray($instances[0]);
	$creds->tenantId = $instanceFields['id'];

	switch ($command)
	{
		case 'configure' :
			$tenantFields = array($tenantQuota, $userCountLimit);
			$tenantService->setFields($tenantFields, $creds);
			break;
		case 'remove' :
			$tenantService->removeInstance($creds);
			break;
		case 'disable' :
		case 'enable' :
			$tenantFields = array(new Field('disabled', $command == 'enable' ? false : true));
			$tenantService->setFields($tenantFields, $creds);
			break;
		default:
			break;
	}
}
catch (InvalidSettings $err)
{
	reportErrors($err->getErrors());
	exit(1);
}
catch (Exception $ex)
{
	$message = '';
	if ($ex->getMessage() === 'ServiceException' && isset($ex->detail->ServiceException->message))
	{
		$message = 'Exception:'.$ex->detail->ServiceException->message.NL;
	}
	else if ($ex->getMessage() === 'InternalException' && isset($ex->detail->InternalException->message))
	{
		$message = 'Exception:'.$ex->detail->InternalException->message.NL;
	}
	else
	{
		$message = 'Exception:'.$ex->getMessage().NL;
	}

	reportError(makeMsg('site', $message));

	exit(1);
}

exit(0);
