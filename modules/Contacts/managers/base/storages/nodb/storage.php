<?php

/* -AFTERLOGIC LICENSE HEADER- */

/**
 * @package ContactsBase
 * @subpackage Storages
 */
class CApiContactsBaseNodbStorage extends CApiContactsBaseStorage
{

	/**
	 * @param CApiGlobalManager &$oManager
	 */
	public function __construct(CApiGlobalManager &$oManager)
	{
		parent::__construct('nodb', $oManager);
	}
}
