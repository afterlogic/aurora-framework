<?php

/* -AFTERLOGIC LICENSE HEADER- */

/**
 * @package Min
 * @subpackage Storages
 */
class CApiMinStorage extends AApiManagerStorage
{
	/**
	 * @param CApiGlobalManager &$oManager
	 */
	public function __construct($sStorageName, AApiManager &$oManager)
	{
		parent::__construct('min', $sStorageName, $oManager);
	}
}