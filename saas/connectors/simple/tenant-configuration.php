<?php

function script_simple_task(&$aResult, $aData)
{
	$sCommand = $aResult['command'];
	if (!in_array($sCommand, array('install', 'disable', 'enable', 'configure', 'remove')))
	{
		$aResult['message'] = 0 < \strlen($sCommand) ? 'Unknown command "'.$sCommand.'".' : 'Empty command.';
		$aResult['message'] .= ' Usage: install | disable | enable | configure | remove';
		return true;
	}

	foreach (array('tenant_name', 'tenant_password', 'partner_login', 'partner_password', 'account_limit', 'quota') as $sProp)
	{
		if (!isset($aData[$sProp]))
		{
			$aResult['message'] = $sProp.' - Invalid value';
			$aResult['message-settings-id'] = $sProp;
			return true;
		}
	}

	$oChannel = validatePartner($aResult, $aData);
	if (!$oChannel)
	{
		return true;
	}
	
	$oTenant = null;
	if ('install' !== $sCommand)
	{
		$oTenant = validateTenant($aResult, $aData, $oChannel);
		if (!$oTenant)
		{
			return true;
		}
	}

	$sTenantLogin = $aData['tenant_name'].'_'.$aData['partner_login'];

	/* @var $oApiTenantsManager CApiTenantsManager */
	$oApiTenantsManager = CApi::GetCoreManager('tenants');
	
	switch ($sCommand)
	{
		default:
			$aResult['message'] = 'Usage: install | disable | enable | configure | remove';
			break;
		case 'install':
			$oTenant = new CTenant($sTenantLogin);
			$oTenant->IdChannel = $oChannel->IdChannel;
			$oTenant->IsEnableAdminPanelLogin = true;
			$oTenant->setPassword($aData['tenant_password']);
			$oTenant->QuotaInMB = (int) $aData['quota'];
			$oTenant->UserCountLimit = (int) $aData['account_limit'];
			$oTenant->Capa = validateCapa(isset($aData['capabilities']) ? (string) $aData['capabilities'] : $oTenant->Capa);
			
			$aResult['result'] = $oApiTenantsManager->createTenant($oTenant);
			if (!$aResult['result'])
			{
				$aResult['message'] = $sTenantLogin.' - Can\'t create tenant';
				$aResult['message-settings-id'] = 'tenant_name';
				$aResult['message-system'] = $oApiTenantsManager->GetLastErrorMessage();
			}
			break;
		case 'configure':
			if ($oTenant)
			{
				$oTenant->setPassword($aData['tenant_password']);
				$oTenant->QuotaInMB = (int) $aData['quota'];
				$oTenant->UserCountLimit = (int) $aData['account_limit'];
				$oTenant->IsEnableAdminPanelLogin = true;
				$oTenant->Capa = validateCapa(isset($aData['capabilities']) ? (string) $aData['capabilities'] : $oTenant->Capa);
				
				$aResult['result'] = $oApiTenantsManager->updateTenant($oTenant);
				if (!$aResult['result'])
				{
					$aResult['message'] = $sTenantLogin.' - Can\'t configure tenant';
					$aResult['message-settings-id'] = 'tenant_name';
					$aResult['message-system'] = $oApiTenantsManager->GetLastErrorMessage();

				}
			}
			break;
		case 'enable':
		case 'disable':
			if ($oTenant)
			{
				$oTenant->IsDisabled = 'disable' === $sCommand;
				$aResult['result'] = $oApiTenantsManager->updateTenant($oTenant);
				if (!$aResult['result'])
				{
					$aResult['message'] = $sTenantLogin.' - Can\'t '.$sCommand.' tenant';
					$aResult['message-settings-id'] = 'tenant_name';
					$aResult['message-system'] = $oApiTenantsManager->GetLastErrorMessage();
				}
			}
			break;
		case 'remove':
			if ($oTenant)
			{
				$aResult['result'] = $oApiTenantsManager->deleteTenant($oTenant);
				if (!$aResult['result'])
				{
					$aResult['message'] = $sTenantLogin.' - Can\'t remove tenant';
					$aResult['message-settings-id'] = 'tenant_name';
					$aResult['message-system'] = $oApiTenantsManager->GetLastErrorMessage();
				}
			}
			break;
	}

	return true;
}
