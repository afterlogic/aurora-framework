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
	public function __construct($sStorageName, CApiGlobalManager &$oManager)
	{
		parent::__construct('min', $sStorageName, $oManager);
	}
}