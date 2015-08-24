<?php

/* -AFTERLOGIC LICENSE HEADER- */

/**
 * @package Tenants
 * @subpackage Storages
 */
class CApiTenantsStorage extends AApiManagerStorage
{
	/**
	 * @param CApiGlobalManager &$oManager
	 */
	public function __construct($sStorageName, CApiGlobalManager &$oManager)
	{
		parent::__construct('tenants', $sStorageName, $oManager);
	}
}