<?php

/* -AFTERLOGIC LICENSE HEADER- */

/**
 * @package Mailsuite
 * @subpackage Storages
 */
class CApiMailsuiteStorage extends AApiManagerStorage
{
	/**
	 * @param CApiGlobalManager &$oManager
	 */
	public function __construct($sStorageName, CApiGlobalManager &$oManager)
	{
		parent::__construct('mailsuite', $sStorageName, $oManager);
	}

	/**
	 * @param int $iMailingListId
	 *
	 * @return CMailingList
	 */
	public function getMailingListById($iMailingListId)
	{
		return null;
	}

	/**
	 * @param CMailingList $oMailingList
	 *
	 * @return false
	 */
	public function createMailingList(CMailingList &$oMailingList)
	{
		return false;
	}

	/**
	 * @param CMailingList $oMailingList
	 *
	 * @return false
	 */
	public function updateMailingList(CMailingList $oMailingList)
	{
		return false;
	}

	/**
	 * @param int $iMailingListId
	 *
	 * @return false
	 */
	public function deleteMailingListById($iMailingListId)
	{
		return false;
	}

	/**
	 * @param CMailingList $oMailingList
	 *
	 * @return false
	 */
	public function deleteMailingList(CMailingList $oMailingList)
	{
		return false;
	}

	/**
	 * @param CMailAliases $oMailAliases
	 */
	public function initMailAliases(CMailAliases &$oMailAliases)
	{
	}

	/**
	 * @param CMailAliases $oMailAliases
	 *
	 * @return false
	 */
	public function updateMailAliases(CMailAliases $oMailAliases)
	{
		return false;
	}

	/**
	 * @param CMailForwards $oMailForwards
	 */
	public function initMailForwards(CMailForwards &$oMailForwards)
	{
	}

	/**
	 * @param CMailForwards $oMailForwards
	 *
	 * @return false
	 */
	public function updateMailForwards(CMailForwards $oMailForwards)
	{
		return false;
	}
}
