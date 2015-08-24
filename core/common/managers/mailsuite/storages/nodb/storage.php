<?php

/* -AFTERLOGIC LICENSE HEADER- */

/**
 * @package Mailsuite
 * @subpackage Storages
 */
class CApiMailsuiteNodbStorage extends CApiMailsuiteStorage
{
	/**
	 * @param CApiGlobalManager &$oManager
	 */
	public function __construct(CApiGlobalManager &$oManager)
	{
		parent::__construct('nodb', $oManager);
	}
}
