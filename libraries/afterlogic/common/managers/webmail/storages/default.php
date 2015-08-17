<?php

/* -AFTERLOGIC LICENSE HEADER- */

/**
 * @package WebMail
 * @subpackage Storages
 */
class CApiWebmailStorage extends AApiManagerStorage
{
	/**
	 * @param CApiGlobalManager &$oManager
	 */
	public function __construct($sStorageName, CApiGlobalManager &$oManager)
	{
		parent::__construct('webmail', $sStorageName, $oManager);
	}
}