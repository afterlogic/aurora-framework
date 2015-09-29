<?php

/* -AFTERLOGIC LICENSE HEADER- */

/**
 * @package ContactsGlobal
 * @subpackage Storages
 */
class CApiGcontactsGlobalNodbStorage extends CApiContactsGlobalStorage
{
	/**
	 * @param CApiGlobalManager &$oManager
	 */
	public function __construct(AApiManager &$oManager)
	{
		parent::__construct('nodb', $oManager);
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
		return array();
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
		return null;
	}
}
