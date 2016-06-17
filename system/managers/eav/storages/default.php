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
	public function existAttribute(CAttribute $oAttribute)
	{
		return true;
	}

	/**
	 */
	public function createAttribute(CAttribute $oAttribute)
	{
		return true;
	}

	/**
	 */
	public function updateProperty(CAttribute &$oAttribute)
	{
		return true;
	}

	/**
	 * @return bool
	 */
	public function deleteAttribute($iId)
	{
		return true;
	}

	/**
	 * @return bool
	 */
	public function deleteAttributes($iEntityId)
	{
		return true;
	}

	/**
	 */
	public function getAttributes($iEntityId, $sValue)
	{
		return true;
	}	
	
	/**
	 */
	public function getAttribute(CAttribute $oAttribute)
	{
		return true;
	}	
	
	public function getTypes()
	{
		return true;
	}	
	
	/**
	 */
	public function getEntities($sType)
	{
		return true;
	}	
	
}
