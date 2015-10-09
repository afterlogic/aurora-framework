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
class CApiMailMainNodbStorage extends CApiMailStorage
{
	/**
	 * @param CApiGlobalManager &$oManager
	 */
	public function __construct(AApiManager &$oManager)
	{
		parent::__construct('nodb', $oManager);
	}
}
