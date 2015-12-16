<?php

/* -AFTERLOGIC LICENSE HEADER- */

/**
 * CApiChannelsManager class summary
 *
 * @package Channels
 */
class CApiChannelsManager extends AApiManagerWithStorage
{
	/**
	 * @param CApiGlobalManager &$oManager
	 */
	public function __construct(CApiGlobalManager &$oManager, $sForcedStorage = '')
	{
		parent::__construct('channels', $oManager, $sForcedStorage);

		$this->inc('classes.channel');
	}

	/**
	 * @return CChannel
	 */
	public function newChannel()
	{
		return new CChannel();
	}

	/**
	 * @param int $iPage
	 * @param int $iChannelsPerPage
	 * @param string $sOrderBy Default value is **Login**
	 * @param bool $bOrderType Default value is **true**
	 * @param string $sSearchDesc Default value is empty string
	 *
	 * @return array|false [Id => [Login, Description]]
	 */
	public function getChannelList($iPage, $iChannelsPerPage, $sOrderBy = 'Login', $bOrderType = true, $sSearchDesc = '')
	{
		$aResult = false;
		try
		{
			$aResult = $this->oStorage->getChannelList($iPage, $iChannelsPerPage, $sOrderBy, $bOrderType, $sSearchDesc);
		}
		catch (CApiBaseException $oException)
		{
			$this->setLastException($oException);
		}
		return $aResult;
	}

	/**
	 * @param string $sSearchDesc Default value is empty string
	 *
	 * @return int|false
	 */
	public function getChannelCount($sSearchDesc = '')
	{
		$iResult = false;
		try
		{
			$iResult = $this->oStorage->getChannelCount($sSearchDesc);
		}
		catch (CApiBaseException $oException)
		{
			$this->setLastException($oException);
		}
		return $iResult;
	}

	/**
	 * @param int $iChannelId
	 *
	 * @return CChannel
	 */
	public function getChannelById($iChannelId)
	{
		$oChannel = null;
		try
		{
			$oChannel = $this->oStorage->getChannelById($iChannelId);
		}
		catch (CApiBaseException $oException)
		{
			$this->setLastException($oException);
		}

		return $oChannel;
	}

	/**
	 * @param string $sChannelLogin
	 *
	 * @return int
	 */
	public function getChannelIdByLogin($sChannelLogin)
	{
		$iChannelId = 0;
		try
		{
			$iChannelId = $this->oStorage->getChannelIdByLogin($sChannelLogin);
		}
		catch (CApiBaseException $oException)
		{
			$this->setLastException($oException);
		}

		return $iChannelId;
	}

	/**
	 * @param CChannel $oChannel
	 *
	 * @return bool
	 */
	public function channelExists(CChannel $oChannel)
	{
		$bResult = false;
		try
		{
			$bResult = $this->oStorage->channelExists($oChannel);
		}
		catch (CApiBaseException $oException)
		{
			$this->setLastException($oException);
		}
		return $bResult;
	}

	/**
	 * @param CChannel $oChannel
	 *
	 * @return bool
	 */
	public function createChannel(CChannel &$oChannel)
	{
		$bResult = false;
		try
		{
			if ($oChannel->validate())
			{
				if (!$this->channelExists($oChannel))
				{
					$oChannel->Password = md5($oChannel->Login.mt_rand(1000, 9000).microtime(true));
					if (!$this->oStorage->createChannel($oChannel))
					{
						throw new CApiManagerException(Errs::ChannelsManager_ChannelCreateFailed);
					}
				}
				else
				{
					throw new CApiManagerException(Errs::ChannelsManager_ChannelAlreadyExists);
				}
			}

			$bResult = true;
		}
		catch (CApiBaseException $oException)
		{
			$bResult = false;
			$this->setLastException($oException);
		}

		return $bResult;
	}

	/**
	 * @param CChannel $oChannel
	 *
	 * @return bool
	 */
	public function updateChannel(CChannel $oChannel)
	{
		$bResult = false;
		try
		{
			if ($oChannel->validate())
			{
				if (!$this->channelExists($oChannel))
				{
					if (!$this->oStorage->updateChannel($oChannel))
					{
						throw new CApiManagerException(Errs::ChannelsManager_ChannelUpdateFailed);
					}
				}
				else
				{
					throw new CApiManagerException(Errs::ChannelsManager_ChannelDoesNotExist);
				}
			}

			$bResult = true;
		}
		catch (CApiBaseException $oException)
		{
			$bResult = false;
			$this->setLastException($oException);
		}
		return $bResult;
	}

	/**
	 * @param CChannel $oChannel
	 *
	 * @throws $oException
	 *
	 * @return bool
	 */
	public function deleteChannel(CChannel $oChannel)
	{
		$bResult = false;
		try
		{
			/* @var $oTenantsApi CApiTenantsManager */
			$oTenantsApi = CApi::GetCoreManager('tenants');
			if ($oTenantsApi && !$oTenantsApi->deleteTenantsByChannelId($oChannel->IdChannel, true))
			{
				$oException = $oTenantsApi->GetLastException();
				if ($oException)
				{
					throw $oException;
				}
			}

			$bResult = $this->oStorage->deleteChannel($oChannel->IdChannel);
		}
		catch (CApiBaseException $oException)
		{
			$this->setLastException($oException);
		}

		return $bResult;
	}
}