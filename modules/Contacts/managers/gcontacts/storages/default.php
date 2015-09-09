<?php

/* -AFTERLOGIC LICENSE HEADER- */

/**
 * @package GContacts
 * @subpackage Storages
 */
class CApiGcontactsStorage extends AApiManagerStorage
{
	/**
	 * @param CApiGlobalManager &$oManager
	 */
	public function __construct($sStorageName, CApiGlobalManager &$oManager)
	{
		parent::__construct('gcontacts', $sStorageName, $oManager);
	}

	/**
	 * @param CAccount $oAccount
	 * @param string $sSearch = ''
	 * 
	 * @return int
	 */
	public function getContactItemsCount($oAccount, $sSearch)
	{
		return 0;
	}

	/**
	 * @param CAccount $oAccount
	 * @param int $iSortField
	 * @param int $iSortOrder
	 * @param int $iOffset
	 * @param int $iRequestLimit
	 * @param string $sSearch
	 * 
	 * @return bool|array
	 */
	public function getContactItems($oAccount,
		$iSortField, $iSortOrder, $iOffset, $iRequestLimit, $sSearch)
	{
		return false;
	}

	/**
	 * @param CAccount $oAccount
	 * @param mixed $mContactId
	 * @param bool $bIgnoreHideInGab = false
	 * 
	 * @return CContact|bool
	 */
	public function getContactById($oAccount, $mContactId, $bIgnoreHideInGab = false)
	{
		return false;
	}

	/**
	 * @param CAccount $oAccount
	 * @param string $sEmail
	 * 
	 * @return CContact|bool
	 */
	public function getContactByEmail($oAccount, $sEmail)
	{
		return false;
	}

	/**
	 * @param mixed $iMailingListID
	 * 
	 * @return CContact|bool
	 */
	public function getContactByMailingListId($iMailingListID)
	{
		return false;
	}
}
