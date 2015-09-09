<?php

/* -AFTERLOGIC LICENSE HEADER- */

/**
 * @package Contacts
 * @subpackage Storages
 */
class CApiContactsmainNodbStorage extends CApiContactsmainStorage
{

	/**
	 * @param CApiGlobalManager &$oManager
	 */
	public function __construct(CApiGlobalManager &$oManager)
	{
		parent::__construct('nodb', $oManager);
	}
}
