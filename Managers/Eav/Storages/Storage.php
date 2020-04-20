<?php
/**
 * This code is licensed under AGPLv3 license or Afterlogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

namespace Aurora\System\Managers\Eav\Storages;

/**
 * @license https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @license https://afterlogic.com/products/common-licensing Afterlogic Software License
 * @copyright Copyright (c) 2019, Afterlogic Corp.
 *
 * @internal
 *
 * @package EAV
 * @subpackage Storages
 */
class Storage
{
	protected $oManager = null;

	public function __construct(\Aurora\System\Managers\Eav &$oManager)
	{
		$this->oManager = $oManager;
	}

	/**
	 *
	 * @param type $mIdOrUUID
	 * @param type $sType
	 * @return type
	 */
	public function isEntityExists($mIdOrUUID, $sType)
	{
		return false;
	}

	/**
	 *
	 * @param \Aurora\System\EAV\Entity $oEntity
	 * @return bool
	 */
	public function createEntity($oEntity)
	{
		return false;
	}

	/**
	 *
	 * @param \Aurora\System\EAV\Entity $oEntity
	 * @return bool
	 */
	public function updateEntity($oEntity)
	{
		return false;
	}

	/**
	 *
	 * @param mixed $mIdOrUUID
	 * @param string $sType
	 * @return type
	 */
	public function getEntity($mIdOrUUID, $sType)
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
	public function getEntities($sType, $aViewAttrs = array(), $iOffset = 0, $iLimit = 20, $aSearchAttrs = array(), $mOrderBy = array(), $iSortOrder = \Aurora\System\Enums\SortOrder::ASC, $aIdsOrUUIDs = array(), $sCustomViewSql = '')
	{
		return false;
	}

	/**
	 * @param mixed $mIdOrUUID
	 * @param string $sType
	 * @return bool
	 */
	public function deleteEntity($mIdOrUUID, $sType)
	{
		return false;
	}

	/**
	 * @param mixed $aIdsOrUUIDs
	 * @param string $sType
	 * @return bool
	 */
	public function deleteEntities($aIdsOrUUIDs, $sType)
	{
		return false;
	}

	/**
	 *
	 * @param type $aEntitiesIds
	 * @param type $aAttributes
	 * @return boolean
	 */
	public function setAttributes($aEntitiesIds, $aAttributes)
	{
		return true;
	}

	/**
	 *
	 * @param type $sType
	 * @param type $iEntityId
	 * @param type $sAttribute
	 * @return boolean
	 */
	public function deleteAttribute($sType, $iEntityId, $sAttribute)
	{
		return true;
	}

	/**
	 *
	 * @param type $sEntityTypes
	 * @return boolean
	 */
	public function getAttributesNamesByEntityType($sEntityTypes)
	{
		return false;
	}

	/**
	 *
	 * @return boolean
	 */
	public function testConnection()
	{
		return false;
	}
}
