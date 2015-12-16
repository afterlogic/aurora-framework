<?php

/**
 * @param string $sDesc
 * @param int $iLogLevel = ELogLevel::Full
 */
function LogBillManager($sDesc, $iLogLevel = ELogLevel::Full)
{
	CApi::Log('BILLMANAGER: '.$sDesc, $iLogLevel, 'billmanager-');
}

LogBillManager('START');

/**
 * @param string $sDesc
 */
function SendErrorMessage($sDesc)
{
	echo 'ERROR';
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

$aQuery = $oHttp->GetQueryAsArray();
if (count($aQuery) === 0)
{
	Header('Location: ../../adminpanel/');
}

$oApiChannelsManager = CApi::GetCoreManager('channels');
/* @var $oApiChannelsManager CApiChannelsManager */

if (!$oApiChannelsManager)
{
	SendErrorMessage('ApiChannelsManager = false');
}

$oApiTenantsManager = CApi::GetCoreManager('tenants');
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
else
{
	$sAction = trim($oHttp->GetRequest('action', ''));
	if (!empty($sAction))
	{
		LogBillManager($sAction);
		
		$sTenantLogin = trim($oHttp->GetRequest('tenant', ''));
		$sTenantPass = trim($oHttp->GetRequest('tenantpass', ''));

		if (0 === strlen($sTenantLogin))
		{
			SendErrorMessage('Invalid tenant login (tenant: '.$sTenantLogin.')');
		}

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
		
		$bResult = false;
		switch ($sAction) 
		{
			case 'getconfig':
				$bResult = true;
				break;
			case 'open':
				if ($iTenantId === 0)
				{
					$oTenant = new CTenant();
					$oTenant->IdChannel = $oChannel->IdChannel;
					$oTenant->Login = $sTenantLogin;
					$oTenant->IsEnableAdminPanelLogin = true;
					$oTenant->QuotaInMB = trim($oHttp->GetRequest('quota', 0));
					$oTenant->UserCountLimit = trim($oHttp->GetRequest('userlimit', 0));
					$oTenant->Expared = trim($oHttp->GetRequest('expiredate', 0));
					$oTenant->setPassword($sTenantPass);
					$bResult = $oApiTenantsManager->createTenant($oTenant);
				}				
				break;
			case 'setparam':
				if ($oTenant)
				{
					$oTenant->QuotaInMB = trim($oHttp->GetRequest('quota', 0));
					$oTenant->UserCountLimit = trim($oHttp->GetRequest('userlimit', 0));
					$oTenant->Expared = trim($oHttp->GetRequest('expiredate', 0));
					$bResult = $oApiTenantsManager->updateTenant($oTenant);
				}
				break;
			case 'prolong':
				if ($oTenant)
				{
					$oTenant->Expared = trim($oHttp->GetRequest('expiredate', 0));
					$bResult = $oApiTenantsManager->updateTenant($oTenant);
				}
				break;
			case 'suspend':
				if ($oTenant)
				{
					$oTenant->IsDisabled = true;
					$bResult = $oApiTenantsManager->updateTenant($oTenant);
				}
				break;
			case 'resume':
				if ($oTenant)
				{
					$oTenant->IsDisabled = false;
					$bResult = $oApiTenantsManager->updateTenant($oTenant);
				}
				break;
			case 'del':
				if ($oTenant)
				{
					$bResult = $oApiTenantsManager->deleteTenant($oTenant);
				}
				break;
		}
		if ($bResult)
		{
			echo "OK\n";
		}
	}
	else 
	{
		echo "OK\n";
	}
}

LogBillManager('END');

