<?php

/* -AFTERLOGIC LICENSE HEADER- */

/**
 * @package Channels
 * @subpackage Storages
 */
class CApiChannelsDbStorage extends CApiChannelsStorage
{
	/**
	 * @var CDbStorage $oConnection
	 */
	protected $oConnection;

	/**
	 * @var CApiChannelsCommandCreatorMySQL
	 */
	protected $oCommandCreator;

	/**
	 * @param CApiGlobalManager &$oManager
	 */
	public function __construct(AApiManager &$oManager)
	{
		parent::__construct('db', $oManager);

		$this->oConnection =& $oManager->GetConnection();
		$this->oCommandCreator =& $oManager->GetCommandCreator(
			$this, array(
				EDbType::MySQL => 'CApiChannelsCommandCreatorMySQL',
				EDbType::PostgreSQL => 'CApiChannelsCommandCreatorPostgreSQL'
			)
		);
	}

	/**
	 * @param string $sOrderBy
	 *
	 * @return string
	 */
	protected function _dbOrderBy($sOrderBy)
	{
		$sResult = $sOrderBy;
		switch ($sOrderBy)
		{
			case 'Description':
				$sResult = 'description';
				break;
			case 'Login':
				$sResult = 'login';
				break;
		}
		return $sResult;
	}

	/**
	 * @param int $iPage
	 * @param int $iChannelsPerPage
	 * @param string $sOrderBy Default value is **login**
	 * @param bool $bOrderType Default value is **true**
	 * @param string $sSearchDesc Default value is empty string
	 *
	 * @return array|false [Id => [Login, Description]]
	 */
	public function getChannelList($iPage, $iChannelsPerPage, $sOrderBy = 'Login', $bOrderType = true, $sSearchDesc = '')
	{
		$aChannels = false;
		if ($this->oConnection->Execute(
			$this->oCommandCreator->getChannelList($iPage, $iChannelsPerPage,
				$this->_dbOrderBy($sOrderBy), $bOrderType, $sSearchDesc)))
		{
			$oRow = null;
			$aChannels = array();
			while (false !== ($oRow = $this->oConnection->GetNextRecord()))
			{
				$aChannels[$oRow->id_channel] = array($oRow->login, $oRow->description);
			}
		}

		$this->throwDbExceptionIfExist();
		return $aChannels;
	}

	/**
	 * @param string $sSearchDesc Default value is empty string
	 *
	 * @return int | false
	 */
	public function getChannelCount($sSearchDesc = '')
	{
		$iResultCount = false;
		if ($this->oConnection->Execute($this->oCommandCreator->getChannelCount($sSearchDesc)))
		{
			$oRow = $this->oConnection->GetNextRecord();
			if ($oRow)
			{
				$iResultCount = (int) $oRow->channels_count;
			}

			$this->oConnection->FreeResult();
		}

		$this->throwDbExceptionIfExist();
		return $iResultCount;
	}

	/**
	 * @param int $iChannelId
	 *
	 * @return CChannel|null
	 */
	public function getChannelById($iChannelId)
	{
		$oChannel = null;
		if ($this->oConnection->Execute(
			$this->oCommandCreator->getChannelById($iChannelId)))
		{
			$oRow = $this->oConnection->GetNextRecord();
			if ($oRow)
			{
				$oChannel = new CChannel();
				$oChannel->InitByDbRow($oRow);
			}

			$this->oConnection->FreeResult();
		}

		$this->throwDbExceptionIfExist();
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
		if ($this->oConnection->Execute(
			$this->oCommandCreator->getChannelIdByLogin($sChannelLogin)))
		{
			$oRow = $this->oConnection->GetNextRecord();
			if ($oRow)
			{
				$iChannelId = $oRow->id_channel;
			}

			$this->oConnection->FreeResult();
		}

		$this->throwDbExceptionIfExist();
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
		$niExceptTenantId = (0 < $oChannel->IdChannel) ? $oChannel->IdChannel : null;

		if ($this->oConnection->Execute(
			$this->oCommandCreator->channelExists($oChannel->Login, $niExceptTenantId)))
		{
			$oRow = $this->oConnection->GetNextRecord();
			if ($oRow && 0 < (int) $oRow->channels_count)
			{
				$bResult = true;
			}

			$this->oConnection->FreeResult();
		}

		$this->throwDbExceptionIfExist();
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
		if ($this->oConnection->Execute($this->oCommandCreator->createChannel($oChannel)))
		{
			$bResult = true;
			$oChannel->IdChannel = $this->oConnection->GetLastInsertId('awm_channels', 'id_channel');
		}

		$this->throwDbExceptionIfExist();
		return $bResult;
	}

	/**
	 * @param CChannel $oChannel
	 *
	 * @return bool
	 */
	public function updateChannel(CChannel $oChannel)
	{
		$bResult = $this->oConnection->Execute($this->oCommandCreator->updateChannel($oChannel));

		$this->throwDbExceptionIfExist();
		return $bResult;
	}

	/**
	 * @param int $iChannelId
	 *
	 * @return bool
	 */
	public function deleteChannel($iChannelId)
	{
		return $this->deleteChannels(array($iChannelId));
	}

	/**
	 * @param array $aChannelsIds
	 *
	 * @return bool
	 */
	public function deleteChannels(array $aChannelsIds)
	{
		$bResult = $this->oConnection->Execute(
			$this->oCommandCreator->deleteChannels($aChannelsIds));

		$this->throwDbExceptionIfExist();
		return $bResult;
	}
}