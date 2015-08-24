<?php

/* -AFTERLOGIC LICENSE HEADER- */

/**
 * @package Subscriptions
 * @subpackage Storages
 */
class CApiSubscriptionsStorage extends AApiManagerStorage
{
	/**
	 * @param CApiGlobalManager &$oManager
	 */
	public function __construct($sStorageName, CApiGlobalManager &$oManager)
	{
		parent::__construct('subscriptions', $sStorageName, $oManager);
	}
}