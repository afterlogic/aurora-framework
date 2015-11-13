<?php

/* -AFTERLOGIC LICENSE HEADER- */

/**
 * @package Db
 * @subpackage Storages
 */
class CApiDbNodbStorage extends CApiDbStorage
{
	/**
	 * @param CApiGlobalManager &$oManager
	 */
	public function __construct(CApiGlobalManager &$oManager)
	{
		parent::__construct('nodb', $oManager);
	}
}
