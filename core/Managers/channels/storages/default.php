<?php

/* -AFTERLOGIC LICENSE HEADER- */

/**
 * @package Channels
 * @subpackage Storages
 */
class CApiChannelsStorage extends AApiManagerStorage
{
	/**
	 * @param CApiGlobalManager &$oManager
	 */
	public function __construct($sStorageName, AApiManager &$oManager)
	{
		parent::__construct('channels', $sStorageName, $oManager);
	}
}