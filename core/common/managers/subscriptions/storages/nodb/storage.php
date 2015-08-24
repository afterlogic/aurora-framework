<?php

/* -AFTERLOGIC LICENSE HEADER- */

/**
 * @package Subscriptions
 * @subpackage Storages
 */
class CApiSubscriptionsNodbStorage extends CApiSubscriptionsStorage
{
	/**
	 * @param CApiGlobalManager &$oManager
	 */
	public function __construct(CApiGlobalManager &$oManager)
	{
		parent::__construct('nodb', $oManager);
	}
}
