<?php

/* -AFTERLOGIC LICENSE HEADER- */

/**
 * @package Tenants
 * @subpackage Storages
 */
class CApiTenantsNodbStorage extends CApiTenantsStorage
{
	/**
	 * @param CApiGlobalManager &$oManager
	 */
	public function __construct(CApiGlobalManager &$oManager)
	{
		parent::__construct('nodb', $oManager);
	}
}