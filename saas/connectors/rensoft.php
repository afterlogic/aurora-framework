<?php

/**
 * @param string $sDesc
 * @param int $iLogLevel = ELogLevel::Full
 */
function LogBillManager($sDesc, $iLogLevel = ELogLevel::Full)
{
	CApi::Log('RENSOFT: '.$sDesc, $iLogLevel);
}

LogBillManager('START');

/**
 * @param string $sDesc
 */
function SendErrorMessage($sDesc)
{
	echo 'Error: '.$sDesc;
	LogBillManager('Error: '.$sDesc, ELogLevel::Error);
	LogBillManager('END');
	exit();
}

if (!defined('PSEVEN_APP_ROOT_PATH'))
{
	SendErrorMessage('Unknown error');
}

$oHttp = \MailSo\Base\Http::SingletonInstance();

LogBillManager('Request: '.$oHttp->GetQueryString());

$oApiChannelsManager = CApi::GetSystemManager('channels');
/* @var $oApiChannelsManager CApiChannelsManager */

if (!$oApiChannelsManager)
{
	SendErrorMessage('ApiChannelsManager = false');
}

$oApiTenantsManager = CApi::GetSystemManager('tenants');
/* @var $oApiTenantsManager CApiTenantsManager */

if (!$oApiTenantsManager)
{
	SendErrorMessage('ApiTenantsManager = false');
}

$sChannelLogin = trim($oHttp->GetRequest('product', ''));
$sChannelPassword = trim($oHttp->GetRequest('secret', ''));

$oChannel = null;
$iChannelId = 0 < strlen($sChannelLogin) ? $oApiChannelsManager->getChannelIdByLogin($sChannelLogin) : 0;
if (0 < $iChannelId)
{
	$oChannel = $oApiChannelsManager->getChannelById($iChannelId);
}
else
{
	SendErrorMessage('Cannot get channel name from request (product='.$sChannelLogin.')');
}

if (!$oChannel)
{
	SendErrorMessage('Cannot get channel by ID ('.$iChannelId.')');
}

if ($oChannel->Password !== $sChannelPassword)
{
	SendErrorMessage('Channel password mismatch (ChannelLogin: '.$oChannel->Login.')');
}

$sTenantLogin = trim($oHttp->GetRequest('subid', ''));

if (0 === strlen($sTenantLogin))
{
	SendErrorMessage('Invalid tenant login (subid: '.$sTenantLogin.')');
}

$sTenantLogin .= '_'.$oChannel->Login;

$iTenantId = $oApiTenantsManager->getTenantIdByLogin($sTenantLogin);
if (0 < $iTenantId)
{
	$oTenant = $oApiTenantsManager->getTenantById($iTenantId);
	if (!$oTenant)
	{
		SendErrorMessage('Cannot get Tenant by ID (TenantID: '.$iTenantId.', TenantLogin: '.$sTenantLogin.')');
	}
	else if ($oTenant->IdChannel !== $oChannel->IdChannel)
	{
		SendErrorMessage('Tenant channel mismatch (TenantID: '.$oTenant->IdChannel.', ChannelID: '.$oChannel->IdChannel.')');
	}
}
else
{
	$oTenant = new CTenant();
	$oTenant->Login = $sTenantLogin;
}

$sTenantEmail = $oHttp->GetRequest('email', '');
$sTenantExpared = $oHttp->GetRequest('expired', '');
$sTenantPayUrl = $oHttp->GetRequest('pay_url', '');
$bTenantIsTrial = '1' === (string) $oHttp->GetRequest('is_trial', '0');
$sTenantPassword = $oHttp->GetRequest('password', '');

$iTenantUserCountLimit = (int) $oHttp->GetRequest('ext_user_count_limit', 0);
$iTenantQuotaInMb = (int) $oHttp->GetRequest('ext_tenant_quota_mb', 0);

if ($oTenant)
{
	$oTenant->IdChannel = $oChannel->IdChannel;
	$oTenant->Email = $sTenantEmail;
	$oTenant->PayUrl = $sTenantPayUrl;
	$oTenant->IsTrial = $bTenantIsTrial;
	$oTenant->QuotaInMB = $iTenantQuotaInMb;
	$oTenant->UserCountLimit = $iTenantUserCountLimit;
	$oTenant->IsEnableAdminPanelLogin = true;
	$oTenant->Expared = \MailSo\Base\DateTimeHelper::ParseDateStringType1($sTenantExpared);
	
	$oTenant->setPassword($sTenantPassword);

	$oTenant->AllowChangeAdminEmail = false;
	$oTenant->AllowChangeAdminPassword = false;

	$bUpdate = false;
	$bResult = false;
	if (0 === $oTenant->IdTenant)
	{
		$bResult = $oApiTenantsManager->createTenant($oTenant);
	}
	else
	{
		$bUpdate = true;
		$bResult = $oApiTenantsManager->updateTenant($oTenant);
	}

	if ($bResult)
	{
		echo 'OK';
		LogBillManager('OK');
		LogBillManager('END');
	}
	else
	{
		SendErrorMessage(($bUpdate ? 'updateTenant' : 'createTenant').' ('.$oApiTenantsManager->GetLastErrorMessage().')');
	}
}
else
{
	SendErrorMessage('Tenant in null');
}
