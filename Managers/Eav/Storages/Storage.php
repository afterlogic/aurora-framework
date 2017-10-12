<?php
/*
 * @copyright Copyright (c) 2017, Afterlogic Corp.
 * @license AGPL-3.0 or Afterlogic Software License
 *
 * This code is licensed under AGPLv3 license or Afterlogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

/**
 * @internal
 * 
 * @package EAV
 * @subpackage Storages
 */

namespace Aurora\System\Managers\Eav\Storages;

class Storage extends \Aurora\System\Managers\AbstractStorage
{
	/**
	 * 
	 * @param type $mIdOrUUID
	 * @return type
	 */
	public function isEntityExists($mIdOrUUID)
	{
		return false;
	}	
	
	/**
	 * 
	 * @param type $sModule
	 * @param type $sType
	 * @param type $sUUID
	 * @return type
	 */
	public function createEntity($sModule, $sType, $sUUID)
	{
		return false;
	}
	
	/**
	 * 
	 * @param type $mIdOrUUID
	 * @return type
	 */
	public function getEntity($mIdOrUUID)
	{
		return null;
	}	

	public function getTypes()
	{
		return false;
	}	
	
	/**
	 * 
	 * @param type $sType
	 * @param type $aWhere
	 * @param type $aIds
	 * @return type
	 */
	public function getEntitiesCount($sType, $aWhere = array(), $aIds = array())
	{
		return 0;
	}
	
	/**
	 * 
	 * @param type $sType
	 * @param type $aViewAttrs
	 * @param type $iOffset
	 * @param type $iLimit
	 * @param type $aSearchAttrs
	 * @param type $mOrderBy
	 * @param type $iSortOrder
	 * @param type $aIdsOrUUIDs
	 * @return \Aurora\System\EAV\Entity
	 */
	public function getEntities($sType, $aViewAttrs = array(), $iOffset = 0, $iLimit = 20, $aSearchAttrs = array(), $mOrderBy = array(), $iSortOrder = \Aurora\System\Enums\SortOrder::ASC, $aIdsOrUUIDs = array())
	{
		return false;
	}	

	/**
	 * @return bool
	 */
	public function deleteEntity($mIdOrUUID)
	{
		return false;
	}

	/**
	 * @return bool
	 */
	public function deleteEntities($aIdsOrUUIDs)
	{
		return false;
	}

	/**
	 */
	public function setAttributes($aEntitiesIds, $aAttributes)
	{
		return true;
	}	
	
	/**
	 * @return bool
	 */
	public function getAttributesNamesByEntityType($sEntityTypes)
	{
		return false;
	}

	public function testConnection()
	{
		return false;
	}
}
