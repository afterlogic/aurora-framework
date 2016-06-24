<?php

/**
 *
 * @param CApiDomainsManager $oApiDomainsManager
 * @param string $sName
 * @param array $aData
 * @return CDomain|false
 */
function getDomainOrCreate($oApiDomainsManager, $sName, $aData)
{
	$oDomain = $oApiDomainsManager->getDomainByName($sName);
	if (!$oDomain)
	{
		$oDomain = new CDomain($sName);
		$oDomain->IncomingMailServer = $aData['account_imap_host'];
		$oDomain->AllowNewUsersRegister = false;
		
		$iPort = 0;
		if (isset($aData['account_imap_port']) && is_numeric($aData['account_imap_port']))
		{
			$iPort = (int) $aData['account_imap_port'];
		}

		if (0 == $iPort && isset($aData['account_imap_port_ssl']) && is_numeric($aData['account_imap_port_ssl']))
		{
			$iPort = (int) $aData['account_imap_port_ssl'];
			$oDomain->IncomingMailUseSSL = true;
		}

		if (0 < $iPort)
		{
			$oDomain->IncomingMailPort = $iPort;
		}

		$oDomain->OutgoingMailServer = $aData['account_smtp_host'];

		$iPort = 0;
		if (isset($aData['account_smtp_port']) && is_numeric($aData['account_smtp_port']))
		{
			$iPort = (int) $aData['account_smtp_port'];
		}

		if (0 == $iPort && isset($aData['account_smtp_port_ssl']) && is_numeric($aData['account_smtp_port_ssl']))
		{
			$iPort = (int) $aData['account_smtp_port_ssl'];
			$oDomain->OutgoingMailUseSSL = true;
		}

		if (0 < $iPort)
		{
			$oDomain->OutgoingMailPort = $iPort;
		}

		if (!$oApiDomainsManager->createDomain($oDomain))
		{
			$oDomain = false;
		}
		else
		{
			$oDomain = $oApiDomainsManager->getDomainById($oDomain->IdDomain);
		}
	}

	return $oDomain ? $oDomain : false;
}

function script_simple_task(&$aResult, $aData)
{
	$sCommand = $aResult['command'];
	if (!in_array($sCommand, array('install', 'disable', 'enable', 'configure', 'remove')))
	{
		$aResult['message'] = 0 < \strlen($sCommand) ? 'Unknown command "'.$sCommand.'".' : 'Empty command.';
		$aResult['message'] .= ' Usage: install | disable | enable | configure | remove';
		return true;
	}

	$sKey = CApi::GetConf('labs.simple-saas-api-key', '');
	if (0 === strlen($sKey) || !isset($aData['api_key']) || $aData['api_key'] !== $sKey)
	{
		$aResult['message'] = 'Invalid API KEY';
		return true;
	}

//	foreach (array('account_email', 'account_login', 'account_password', 'account_imap_host', 'account_imap_port', 'account_imap_port_ssl',
//		'account_smtp_host', 'account_smtp_port', 'account_smtp_port_ssl') as $sProp)
//	{
//		if (!isset($aData[$sProp]))
//		{
//			$aResult['message'] = $sProp.' - Invalid value';
//			$aResult['message-settings-id'] = $sProp;
//			return true;
//		}
//	}

	$sEmail = $aData['account_email'];
	$sDomain = \MailSo\Base\Utils::GetDomainFromEmail($sEmail);

	if (0 < strlen($sEmail) && 0 < strlen($sDomain))
	{
		/* @var $oApiUsersManager CApiUsersManager */
		$oApiUsersManager = CApi::GetSystemManager('users');

		/* @var $oApiDomainsManager CApiDomainsManager */
		$oApiDomainsManager = CApi::GetSystemManager('domains');

		switch ($sCommand)
		{
			default:
				$aResult['message'] = 'Usage: install | disable | enable | configure | remove';
				break;

			case 'install':
				$oDomain = getDomainOrCreate($oApiDomainsManager, $sDomain, $aData);
				if ($oDomain)
				{
					$oAccount = new CAccount($oDomain);
					$oAccount->Email = $sEmail;
					$oAccount->IncomingMailLogin = $aData['account_login'];
					$oAccount->IncomingMailPassword = $aData['account_password'];

					$aResult['result'] = $oApiUsersManager->createAccount($oAccount);
					if (!$aResult['result'])
					{
						$aResult['message'] = $sEmail.' - Can\'t create account';
						$aResult['message-settings-id'] = 'account_email';
						$aResult['message-system'] = $oApiUsersManager->GetLastErrorMessage();
					}
				}
				else
				{
					$aResult['result'] = false;
					$aResult['message'] = $sDomain.' - Can\'t get/create domain';
					$aResult['message-settings-id'] = 'account_email';
					$aResult['message-system'] = $oApiDomainsManager->GetLastErrorMessage();
				}
				break;

			case 'disable':
			case 'enable':
				$oAccount = $oApiUsersManager->getAccountByEmail($sEmail);
				if ($oAccount)
				{
					$aResult['result'] = $oApiUsersManager->enableAccounts(array($oAccount->IdAccount), 'enable' === $sCommand);
					if (!$aResult['result'])
					{
						$aResult['message'] = $sEmail.' - Can\'t enable/disable account';
						$aResult['message-settings-id'] = 'account_email';
						$aResult['message-system'] = $oApiUsersManager->GetLastErrorMessage();
					}
				}
				else
				{
					$aResult['result'] = false;
					$aResult['message'] = $sEmail.' - Can\'t find account';
					$aResult['message-settings-id'] = 'account_email';
					$aResult['message-system'] = $oApiUsersManager->GetLastErrorMessage();
				}
				break;

			case 'configure':
				$oAccount = $oApiUsersManager->getAccountByEmail($sEmail);
				if ($oAccount)
				{
					$oAccount->Email = $sEmail;
					$oAccount->IncomingMailLogin = $aData['account_login'];
					$oAccount->IncomingMailPassword = $aData['account_password'];

					$aResult['result'] = $oApiUsersManager->updateAccount($oAccount);
					if (!$aResult['result'])
					{
						$aResult['message'] = $sEmail.' - Can\'t configure account';
						$aResult['message-settings-id'] = 'account_email';
						$aResult['message-system'] = $oApiUsersManager->GetLastErrorMessage();
					}
				}
				else
				{
					$aResult['result'] = false;
					$aResult['message'] = $sEmail.' - Can\'t find account';
					$aResult['message-settings-id'] = 'account_email';
					$aResult['message-system'] = $oApiUsersManager->GetLastErrorMessage();
				}
				break;

			case 'remove':
				$aResult['result'] = $oApiUsersManager->deleteAccountByEmail($sEmail);
				if (!$aResult['result'])
				{
					$aResult['message'] = $sEmail.' - Can\'t remove account';
					$aResult['message-settings-id'] = 'account_email';
					$aResult['message-system'] = $oApiUsersManager->GetLastErrorMessage();
				}
				break;
		}
	}
	else
	{
		$aResult['message'] = 'account_email - Invalid value';
		$aResult['message-settings-id'] = 'account_email';
	}
	
	return true;
}
