<?php

/**
 * @param array $aResult
 * @param array $aData
 * @return CChannel|bool
 */
function validatePartner(&$aResult, $aData)
{
	/* @var $oApiChannelsManager CApiChannelsManager */
	$oApiChannelsManager = CApi::Manager('channels');
	if (!$oApiChannelsManager)
	{
		$aResult['message'] = 'Internal error';
		return false;
	}

	$iChannelId = $oApiChannelsManager->getChannelIdByLogin($aData['partner_login']);
	if (0 === $iChannelId)
	{
		$aResult['message'] = 'Partner access deny';
		$aResult['message-settings-id'] = 'partner_login';
		$aResult['message-system'] = $oApiChannelsManager->GetLastErrorMessage();
		return false;
	}

	$oChannel = $oApiChannelsManager->getChannelById($iChannelId);
	if (!$oChannel || $oChannel->Password !== $aData['partner_password'])
	{
		$aResult['message'] = 'Partner access deny';
		$aResult['message-settings-id'] = 'partner_password';
		$aResult['message-system'] = $oApiChannelsManager->GetLastErrorMessage();
		return false;
	}
	
	return $oChannel;
}

/**
 *
 * @param array $aResult
 * @param array $aData
 * @param CChannel $oChannel
 * 
 * @return CTenant|bool
 */
function validateTenant(&$aResult, $aData, $oChannel)
{
	/* @var $oApiTenantsManager CApiTenantsManager */
	$oApiTenantsManager = CApi::Manager('tenants');
	if (!$oApiTenantsManager)
	{
		$aResult['message'] = 'Internal error';
		return false;
	}

	$sTenantLogin = $aData['tenant_name'].'_'.$aData['partner_login'];

	$oTenant = false;
	$iIdTenant = $oApiTenantsManager->getTenantIdByLogin($sTenantLogin);
	if (0 < $iIdTenant)
	{
		$oTenant = $oApiTenantsManager->getTenantById($iIdTenant);
	}

	if (!$oTenant)
	{
		$aResult['message'] = $sTenantLogin.' - Tenant not found';
		$aResult['message-settings-id'] = 'tenant_name';
		$aResult['message-system'] = $oApiTenantsManager->GetLastErrorMessage();
		return false;
	}
	else if ($oTenant->IdChannel !== $oChannel->IdChannel)
	{
		$aResult['message'] = 'Tenant access deny';
		$aResult['message-settings-id'] = 'tenant_name';
		$aResult['message-system'] = $oApiTenantsManager->GetLastErrorMessage();
		return false;
	}

	return $oTenant;
}

/**
 *
 * @param string $sCapa
 *
 * @return string
 */
function validateCapa($sCapa)
{
	$sCapa = trim($sCapa);
	if ('' !== $sCapa)
	{
		$sCapa = strtoupper($sCapa);
		$aCapa = explode(' ', $sCapa);
		$aCapa = array_filter($aCapa, function ($sItem) {
			return in_array($sItem, array('NO', 'HELPDESK', 'FILES'));
		});
		
		$sCapa = implode(' ', $aCapa);
	}

	return $sCapa;
}

/**
 * @param array $aData
 */
function output($aData)
{
	if (is_array($aData) && 0 < count($aData) && isset($aData['result']) &&
		true === $aData['result'])
	{
		if (!empty($aData['data']['type']) && 'tenant-resource' === $aData['data']['type'] &&
			isset($aData['data']['resources']) && is_array($aData['data']['resources']) && 0 < count($aData['data']['resources']))
		{
			$oXml = new XMLWriter();
			$oXml->openMemory();
			$oXml->setIndent(true);
			$oXml->setIndentString(' ');
			$oXml->startDocument('1.0');
			$oXml->startElement('resources');
			$oXml->writeAttribute('xmlns', 'http://apstandard.com/ns/1/resource-output');

			foreach ($aData['data']['resources'] as $sKey => $sValue)
			{
				$oXml->startElement('resource');
				$oXml->writeAttribute('id', $sKey);
				$oXml->writeAttribute('value', $sValue);
				$oXml->endElement();
			}

			$oXml->endElement();
			$oXml->endDocument();

			return array(
				'code' => 0,
				'response' => $oXml->outputMemory()
			);
		}

		return array(
			'code' => 0,
			'response' => ''
		);
	}
	else
	{
		$sSettingId = isset($aData['message-settings-id']) ? \trim($aData['message-settings-id']) : '';
		$sMessage = !empty($aData['message']) ? \trim($aData['message']) : '';
		$sSystem = !empty($aData['message-system']) ? \trim($aData['message-system']) : '';
		$sMessage = empty($sMessage) ? 'Empty error' : $sMessage;

		$oXml = new XMLWriter();
		$oXml->openMemory();
		$oXml->setIndent(true);
		$oXml->setIndentString(' ');
		$oXml->startDocument('1.0');
		$oXml->startElement('output');
		$oXml->writeAttribute('xmlns', 'http://apstandard.com/ns/1/configure-output');

		$oXml->startElement('errors');

		$oXml->startElement('error');
		$oXml->writeAttribute('id', 0);
		$oXml->writeAttribute('setting-id', $sSettingId);
		$oXml->writeElement('message', $sMessage);
		$oXml->writeElement('system', $sSystem);
		$oXml->endElement();

		$oXml->endElement();

		$oXml->endElement();
		$oXml->endDocument();

		return array(
			'code' => 1,
			'response' => $oXml->outputMemory()
		);
	}
}
