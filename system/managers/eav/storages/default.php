<?php
/*
 * @copyright Copyright (c) 2016, Afterlogic Corp.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 * 
 */

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
	 * @return \AEntity
	 */
	public function getEntities($sType, $aViewAttrs = array(), $iOffset = 0, $iLimit = 20, $aSearchAttrs = array(), $mOrderBy = array(), $iSortOrder = \ESortOrder::ASC, $aIdsOrUUIDs = array())
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
