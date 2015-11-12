<?php

/* -AFTERLOGIC LICENSE HEADER- */

/**
 * @package WebMail
 * @subpackage Storages
 */
class CApiWebmailNodbStorage extends CApiWebmailStorage
{
	/**
	 * @param CApiGlobalManager &$oManager
	 */
	public function __construct(CApiGlobalManager &$oManager)
	{
		parent::__construct('nodb', $oManager);
	}
}