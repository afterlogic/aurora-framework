<?php

/* -AFTERLOGIC LICENSE HEADER- */

/**
 * @package Fetchers
 * @subpackage Storages
 */
class CApiFetchersStorage extends AApiManagerStorage
{
	/**
	 * @param CApiGlobalManager &$oManager
	 */
	public function __construct($sStorageName, CApiGlobalManager &$oManager)
	{
		parent::__construct('fetchers', $sStorageName, $oManager);
	}
}