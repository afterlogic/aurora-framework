<?php

/* -AFTERLOGIC LICENSE HEADER- */

/**
 * @package Fetchers
 * @subpackage Storages
 */
class CApiMailFetchersStorage extends AApiManagerStorage
{
	/**
	 * @param CApiGlobalManager &$oManager
	 */
	public function __construct($sStorageName, AApiManager &$oManager)
	{
		parent::__construct('fetchers', $sStorageName, $oManager);
	}
}