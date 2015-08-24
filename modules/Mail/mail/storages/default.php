<?php

/* -AFTERLOGIC LICENSE HEADER- */

/**
 * @internal
 * 
 * @package Users
 * @subpackage Storages
 */
class CApiMailStorage extends AApiManagerStorage
{
	/**
	 * Creates instance of the object.
	 * 
	 * @param string $sStorageName
	 * @param CApiGlobalManager &$oManager
	 */
	public function __construct($sStorageName, CApiGlobalManager &$oManager)
	{
		parent::__construct('mail', $sStorageName, $oManager);
	}
}