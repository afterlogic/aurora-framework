<?php

/* -AFTERLOGIC LICENSE HEADER- */

/**
 * @package Channels
 * @subpackage Storages
 */
class CApiChannelsNodbStorage extends CApiChannelsStorage
{
	/**
	 * @param CApiGlobalManager &$oManager
	 */
	public function __construct(CApiGlobalManager &$oManager)
	{
		parent::__construct('nodb', $oManager);
	}
}