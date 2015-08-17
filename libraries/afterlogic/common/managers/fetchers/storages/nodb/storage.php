<?php

/* -AFTERLOGIC LICENSE HEADER- */

/**
 * @package Fetchers
 * @subpackage Storages
 */
class CApiFetchersNodbStorage extends CApiFetchersStorage
{
	/**
	 * @param CApiGlobalManager &$oManager
	 */
	public function __construct(CApiGlobalManager &$oManager)
	{
		parent::__construct('nodb', $oManager);
	}
}
