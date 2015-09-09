<?php

/* -AFTERLOGIC LICENSE HEADER- */

/**
 * @ignore
 * 
 * @internal
 * 
 * @package Mail
 * @subpackage Storages
 */
class CApiMailNodbStorage extends CApiMailStorage
{
	/**
	 * @param CApiGlobalManager &$oManager
	 */
	public function __construct(CApiGlobalManager &$oManager)
	{
		parent::__construct('nodb', $oManager);
	}
}
