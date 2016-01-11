<?php

/* -AFTERLOGIC LICENSE HEADER- */

/**
 * @package Dav
 */
class CApiDavMainManager extends AApiManager
{
	/**
	 * @var array
	 */
	protected $aDavClients;

	/**
	 * 
	 * @param CApiGlobalManager $oManager
	 * @param type $sForcedStorage
	 */
	public function __construct(CApiGlobalManager &$oManager, $sForcedStorage = '', AApiModule $oModule = null)
	{
		parent::__construct('main', $oManager, $oModule);
		$this->incClass('dav-client');

		$this->aDavClients = array();
	}

	/**
	 * @param CAccount $oAccount
	 * @return CDAVClient|false
	 */
	public function &GetDAVClient($oAccount)
	{
		$mResult = false;
		if (!isset($this->aDavClients[$oAccount->Email]))
		{
			$this->aDavClients[$oAccount->Email] = new CDAVClient(
				$this->getServerUrl($oAccount), $oAccount->Email, $oAccount->IncomingMailPassword);
		}

		if (isset($this->aDavClients[$oAccount->Email]))
		{
			$mResult =& $this->aDavClients[$oAccount->Email];
		}

		return $mResult;
	}

	/**
	 * @param CAccount $oAccount Default null
	 * 
	 * @return string
	 */
	public function getServerUrl($oAccount = null)
	{
		return rtrim($oAccount
			? $oAccount->Domain->ExternalHostNameOfDAVServer
			: \CApi::GetSettingsConf('WebMail/ExternalHostNameOfDAVServer'), '/');
	}

	/**
	 * @return string
	 */
	public function getCalendarStorageType()
	{
		return $this->oManager->GetStorageByType('calendar');
	}

	/**
	 * @return string
	 */
	public function getContactsStorageType()
	{
		return $this->oManager->GetStorageByType('contactsmain');
	}

	/**
	 * @param CAccount $oAccount Default null
	 * 
	 * @return string
	 */
	public function getServerHost($oAccount = null)
	{
		$mResult = '';
		$sServerUrl = $this->getServerUrl($oAccount);
		if (!empty($sServerUrl))
		{
			$aUrlParts = parse_url($sServerUrl);
			if (!empty($aUrlParts['host']))
			{
				$mResult = $aUrlParts['host'];
			}
		}
		return $mResult;
	}

	/**
	 * @param CAccount $oAccount Default null
	 * 
	 * @return bool
	 */
	public function isUseSsl($oAccount = null)
	{
		$bResult = false;
		$sServerUrl = $this->getServerUrl($oAccount);
		if (!empty($sServerUrl))
		{
			$aUrlParts = parse_url($sServerUrl);
			if (!empty($aUrlParts['port']) && $aUrlParts['port'] === 443)
			{
				$bResult = true;
			}
			if (!empty($aUrlParts['scheme']) && $aUrlParts['scheme'] === 'https')
			{
				$bResult = true;
			}
		}
		return $bResult;
	}

	/**
	 * @param CAccount $oAccount Default null
	 * 
	 * @return int
	 */
	public function getServerPort($oAccount = null)
	{
		$iResult = 80;
		if ($this->isUseSsl($oAccount))
		{
			$iResult = 443;
		}
			
		$sServerUrl = $this->getServerUrl($oAccount);
		if (!empty($sServerUrl))
		{
			$aUrlParts = parse_url($sServerUrl);
			if (!empty($aUrlParts['port']))
			{
				$iResult = (int) $aUrlParts['port'];
			}
		}
		return $iResult;
	}

	/**
	 * @param CAccount $oAccount
	 * 
	 * @return string
	 */
	public function getPrincipalUrl($oAccount)
	{
		$mResult = false;
		try
		{
			$sServerUrl = $this->getServerUrl($oAccount);
			if (!empty($sServerUrl))
			{
				$aUrlParts = parse_url($sServerUrl);
				$sPort = $sPath = '';
				if (!empty($aUrlParts['port']) && (int)$aUrlParts['port'] !== 80)
				{
					$sPort = ':'.$aUrlParts['port'];
				}
				if (!empty($aUrlParts['path']))
				{
					$sPath = $aUrlParts['path'];
				}

				if (!empty($aUrlParts['scheme']) && !empty($aUrlParts['host']))
				{
					$sServerUrl = $aUrlParts['scheme'].'://'.$aUrlParts['host'].$sPort;

					if ($this->getCalendarStorageType() === 'caldav' || $this->getContactsStorageType() === 'carddav')
					{
						$oDav =& $this->GetDAVClient($oAccount);
						if ($oDav && $oDav->Connect())
						{
							$mResult = $sServerUrl.$oDav->GetCurrentPrincipal();
						}
					}
					else
					{
						$mResult = $sServerUrl . $sPath .'/principals/' . $oAccount->Email;
					}
				}
			}
		}
		catch (Exception $oException)
		{
			$mResult = false;
			$this->setLastException($oException);
		}
		return $mResult;
	}

	/**
	 * @param CAccount $oAccount
	 * 
	 * @return string
	 */
	public function getLogin($oAccount)
	{
		return $oAccount->Email;
	}

	/**
	 * @return bool
	 */
	public function isMobileSyncEnabled()
	{
		$oSettings =& CApi::GetSettings();
		return (bool) $oSettings->GetConf('Common/EnableMobileSync');
	}

	/**
	 * 
	 * @param bool $bMobileSyncEnable
	 * 
	 * @return bool
	 */
	public function setMobileSyncEnable($bMobileSyncEnable)
	{
		$oSettings =& CApi::GetSettings();
		$oSettings->SetConf('Common/EnableMobileSync', $bMobileSyncEnable);
		return (bool) $oSettings->SaveToXml();
	}

	/**
	 * @param CAccount $oAccount
	 * 
	 * @return bool
	 */
	public function testConnection($oAccount)
	{
		$bResult = false;
		$oDav =& $this->GetDAVClient($oAccount);
		if ($oDav && $oDav->Connect())
		{
			$bResult = true;
		}
		return $bResult;
	}

	/**
	 * @param CAccount $oAccount
	 */
	public function deletePrincipal($oAccount)
	{
		$oPrincipalBackend = \Afterlogic\DAV\Backend::Principal();
		$oPrincipalBackend->deletePrincipal(\Afterlogic\DAV\Constants::PRINCIPALS_PREFIX . '/' . $oAccount->Email);
	}

	/**
	 * @param string $sData
	 * @return mixed
	 */
	public function getVCardObject($sData)
	{
		return \Sabre\VObject\Reader::read($sData, \Sabre\VObject\Reader::OPTION_IGNORE_INVALID_LINES);
	}
}
