<?php

/* -AFTERLOGIC LICENSE HEADER- */

/**
 * @internal
 * 
 * @package EAV
 * @subpackage Storages
 */
class CApiEavStorage extends AApiManagerStorage
{
	/**
	 * @param CApiGlobalManager &$oManager
	 */
	public function __construct($sStorageName, AApiManager &$oManager)
	{
		parent::__construct('eav', $sStorageName, $oManager);
	}
	
	/**
	 */
	public function existProperty(CProperty $oProperty)
	{
		return true;
	}

	/**
	 */
	public function createProperty(CProperty $oProperty)
	{
		return true;
	}

	/**
	 */
	public function updateProperty(CProperty &$oProperty)
	{
		return true;
	}

	/**
	 * @return bool
	 */
	public function deleteProperty($iId)
	{
		return true;
	}

	/**
	 * @return bool
	 */
	public function deleteProperties($iObjectId)
	{
		return true;
	}

	/**
	 */
	public function getProperties($iObjectId, $sValue)
	{
		return true;
	}	
	
	/**
	 */
	public function getProperty(CProperty $oProperty)
	{
		return true;
	}	
	
	/**
	 */
	public function getObjects($sType)
	{
		return true;
	}	
	
}
