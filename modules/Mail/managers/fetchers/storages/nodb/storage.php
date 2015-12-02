<?php

/* -AFTERLOGIC LICENSE HEADER- */

/**
 * @package Fetchers
 * @subpackage Storages
 */
class CApiMailFetchersNodbStorage extends CApiFetchersStorage
{
	/**
	 * @param CApiGlobalManager &$oManager
	 */
	public function __construct(AApiManager &$oManager)
	{
		parent::__construct('nodb', $oManager);
	}
}
