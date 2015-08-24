<?php

/* -AFTERLOGIC LICENSE HEADER- */

/**
 * @package Channels
 * @subpackage Storages
 */
class CApiChannelsCommandCreator extends api_CommandCreator
{
	/**
	 * @param string $sSearchDesc Default value is empty string
	 *
	 * @return string
	 */
	public function getChannelCount($sSearchDesc = '')
	{
		$sWhere = '';
		if (!empty($sSearchDesc))
		{
			$sSearchDescEsc = '\'%'.$this->escapeString($sSearchDesc, true, true).'%\'';
			$sWhere = ' WHERE login LIKE '.$sSearchDescEsc.
				' OR description LIKE '.$sSearchDescEsc;
		}

		$sSql = 'SELECT COUNT(id_channel) as channels_count FROM %sawm_channels%s';

		return sprintf($sSql, $this->prefix(), $sWhere);
	}

	/**
	 * @param string $sLogin
	 *
	 * @return string
	 */
	public function getChannelIdByLogin($sLogin)
	{
		$sSql = 'SELECT id_channel FROM %sawm_channels WHERE login = %s';
		return sprintf($sSql, $this->prefix(), $this->escapeString($sLogin));
	}

	/**
	 * @param string $sWhere
	 *
	 * @return string
	 */
	protected function getChannelByWhere($sWhere)
	{
		return api_AContainer::DbGetObjectSqlString(
			$sWhere, $this->prefix().'awm_channels', CChannel::getStaticMap(), $this->oHelper);
	}

	/**
	 * @param int $iChannelId
	 *
	 * @return string
	 */
	public function getChannelById($iChannelId)
	{
		return $this->getChannelByWhere(sprintf('%s = %d',
			$this->escapeColumn('id_channel'), $iChannelId));
	}

	/**
	 * @param CChannel $oChannel
	 *
	 * @return string
	 */
	function createChannel(CChannel $oChannel)
	{
		return api_AContainer::DbCreateObjectSqlString($this->prefix().'awm_channels', $oChannel, $this->oHelper);
	}

	/**
	 * @param CChannel $oChannel
	 *
	 * @return string
	 */
	function updateChannel(CChannel $oChannel)
	{
		$aResult = api_AContainer::DbUpdateArray($oChannel, $this->oHelper);

		$sSql = 'UPDATE %sawm_channels SET %s WHERE id_channel = %d';
		return sprintf($sSql, $this->prefix(), implode(', ', $aResult), $oChannel->IdChannel);
	}

	/**
	 * @param string $sLogin
	 * @param int $niExceptTenantId Default value is **null**
	 *
	 * @return string
	 */
	public function channelExists($sLogin, $niExceptTenantId = null)
	{
		$sAddWhere = (is_integer($niExceptTenantId)) ? ' AND id_channel <> '.$niExceptTenantId : '';

		$sSql = 'SELECT COUNT(id_channel) as channels_count FROM %sawm_channels WHERE login = %s%s';

		return sprintf($sSql, $this->prefix(), $this->escapeString(strtolower($sLogin)), $sAddWhere);
	}

	/**
	 * @param array $aChannelsIds
	 *
	 * @return string
	 */
	function deleteChannels($aChannelsIds)
	{
		$aIds = api_Utils::SetTypeArrayValue($aChannelsIds, 'int');

		$sSql = 'DELETE FROM %sawm_channels WHERE id_channel in (%s)';
		return sprintf($sSql, $this->prefix(), implode(',', $aIds));
	}
}

/**
 * @package Channels
 * @subpackage Storages
 */
class CApiChannelsCommandCreatorMySQL extends CApiChannelsCommandCreator
{
	/**
	 * @param int $iPage
	 * @param int $iChannelsPerPage
	 * @param string $sOrderBy Default value is **login**
	 * @param bool $bOrderType Default value is **true**
	 * @param string $sSearchDesc Default value is empty string
	 *
	 * @return string
	 */
	public function getChannelList($iPage, $iChannelsPerPage, $sOrderBy = 'login', $bOrderType = true, $sSearchDesc = '')
	{
		$sWhere = '';
		if (!empty($sSearchDesc))
		{
			$sSearchDescEsc = '\'%'.$this->escapeString($sSearchDesc, true, true).'%\'';
			$sWhere = ' WHERE login LIKE '.$sSearchDescEsc.
				' OR description LIKE '.$sSearchDescEsc;
		}

		$sOrderBy = empty($sOrderBy) ? 'login' : $sOrderBy;

		$sSql = 'SELECT id_channel, login, description FROM %sawm_channels %s ORDER BY %s %s LIMIT %d OFFSET %d';

		$sSql = sprintf($sSql, $this->prefix(), $sWhere, $sOrderBy,
			((bool) $bOrderType) ? 'ASC' : 'DESC',
			$iChannelsPerPage,
			($iPage > 0) ? ($iPage - 1) * $iChannelsPerPage : 0
		);

		return $sSql;
	}
}

/**
 * @package Channels
 * @subpackage Storages
 */
class CApiChannelsCommandCreatorPostgreSQL extends CApiChannelsCommandCreatorMySQL
{
	// TODO
}
