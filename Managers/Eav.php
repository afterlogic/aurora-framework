<?php
/**
 * This code is licensed under AGPLv3 license or Afterlogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

namespace Aurora\System\Managers;

use Aurora\System\Exceptions\Exception;

/**
 * Eav Manager
 *
 * @license https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @license https://afterlogic.com/products/common-licensing Afterlogic Software License
 * @copyright Copyright (c) 2019, Afterlogic Corp.
 *
 * @package EAV
 */
class Eav
{
	/**
	 * @var \Aurora\System\Managers\AbstractStorage
	 */
	public $oStorage;

	public function __construct()
	{
		$sForcedStorage = 'Db';
		$oSettings = \Aurora\Api::getSettings();
		if($oSettings)
		{
			$sForcedStorage = $oSettings->getConf('EavStorageType', 'Db');
		}
		$oForcedStorage = __NAMESPACE__ . '\\Eav\\Storages\\' . $sForcedStorage . '\\Storage';

		$this->oStorage = new $oForcedStorage($this);
	}

	public static function getInstance()
	{
		static $oInstance = null;
		if(is_null($oInstance))
		{
			$oInstance = new self();
		}
		return $oInstance;
	}


	/**
	 *
	 * @param int|string $mIdOrUUID
	 * @param string $sType
	 * @return boolean
	 */
	public function isEntityExists($mIdOrUUID, $sType = null)
	{
		$bResult = false;
		try
		{
			$bResult = $this->oStorage->isEntityExists($mIdOrUUID, $sType);
		}
		catch (\Aurora\System\Exceptions\DbException $oException)
		{
			$bResult = false;
			throw $oException;
		}

		return $bResult;
	}

	/**
	 *
	 * @param \Aurora\System\EAV\Entity $oEntity
	 * @return bool
	 */
	public function saveEntity(\Aurora\System\EAV\Entity &$oEntity)
	{
		$mResult = false;

		if (!empty($oEntity->EntityId) && $this->isEntityExists($oEntity->EntityId, $oEntity->getName()) ||
			isset($oEntity->UUID) && $this->isEntityExists($oEntity->UUID, $oEntity->getName()))
		{
			$mResult = $this->updateEntity($oEntity);
		}
		else
		{
			$mResult = $this->createEntity($oEntity);
		}

		return $mResult;
	}

	/**
	 *
	 * @param \Aurora\System\EAV\Entity $oEntity
	 * @return type
	 * @throws \Aurora\System\Exceptions\ManagerException
	 */
	public function createEntity(\Aurora\System\EAV\Entity &$oEntity)
	{
		return $this->oStorage->createEntity($oEntity);
	}

	/**
	 *
	 * @param \Aurora\System\EAV\Entity $oEntity
	 * @return boolean
	 * @throws type
	 */
	public function updateEntity(\Aurora\System\EAV\Entity $oEntity, $bOnlyOverrided = false)
	{
		return $this->oStorage->updateEntity($oEntity, $bOnlyOverrided);
	}

	/**
	 *
	 * @param int|string $mIdOrUUID
	 * @return bool
	 */
	public function deleteEntity($mIdOrUUID, $sType = null)
	{
		$bResult = false;
		try
		{
			$bResult = $this->oStorage->deleteEntity($mIdOrUUID, $sType);
		}
		catch (\Aurora\System\Exceptions\DbException $oException)
		{
			throw $oException;
		}

		return $bResult;
	}

	/**
	 *
	 * @param array $aIdsOrUUIDs
	 * @return bool
	 */
	public function deleteEntities($aIdsOrUUIDs, $sType = null)
	{
		$bResult = false;

		if (!empty($aIdsOrUUIDs))
		{
			try
			{
				$bResult = $this->oStorage->deleteEntities($aIdsOrUUIDs, $sType);
			}
			catch (\Aurora\System\Exceptions\DbException $oException)
			{
				throw $oException;
			}
		}

		return $bResult;
	}

	/**
	 *
	 * @return array
	 */
	public function getTypes()
	{
		$aTypes = array();
		try
		{
			$aTypes = $this->oStorage->getTypes();
		}
		catch (\Exception $oException)
		{
			throw $oException;
		}
		return $aTypes;
	}


	public function getAttributesNamesByEntityType($sType)
	{
		return $this->oStorage->getAttributesNamesByEntityType($sType);
	}

	/**
	 *
	 * @param string $sType
	 * @param array $aWhere
	 * @return int
	 */
	public function getEntitiesCount($sType, $aWhere = array(), $aIdsOrUUIDs = array())
	{
		$iCount = 0;
		try
		{
			$iCount = $this->oStorage->getEntitiesCount($sType, $aWhere, $aIdsOrUUIDs);
		}
		catch (\Aurora\System\Exceptions\DbException $oException)
		{
			throw $oException;
		}
		return $iCount;
	}

	/**
	 *
	 * @param string $sType
	 * @param array $aViewAttributes
	 * @param int $iOffset
	 * @param int $iLimit
	 * @param array $aWhere
	 * @param string|array $mOrderBy
	 * @param int $iSortOrder
	 * @param array $aIdsOrUUIDs
	 * @return array
	 */
	public function getEntities($sType, $aViewAttributes = [], $iOffset = 0, $iLimit = 0, $aWhere = [], $mOrderBy = [],
		$iSortOrder = \Aurora\System\Enums\SortOrder::ASC, $aIdsOrUUIDs = [], $sCustomViewSql = '')
	{
		$aEntities = array();
		try
		{
			if (is_array($aViewAttributes) && count($aViewAttributes) === 0)
			{
				$aViewAttributes = \Aurora\System\EAV\Entity::createInstance($sType)->getAttributesKeys();
			}

			$aEntities = $this->oStorage->getEntities(
				$sType,
				$aViewAttributes,
				$iOffset,
				$iLimit,
				$aWhere,
				$mOrderBy,
				$iSortOrder,
				$aIdsOrUUIDs,
				$sCustomViewSql
			);
		}
		catch (\Aurora\System\Exceptions\DbException $oException)
		{
			throw $oException;
		}
		return $aEntities;
	}

	public function getEntitiesUids($sType, $iOffset = 0, $iLimit = 20, $aSearchAttrs = [], $mSortAttributes = [],
		$iSortOrder = \Aurora\System\Enums\SortOrder::ASC, $sCustomViewSql = '')
	{
		return  $this->oStorage->getEntitiesUids($sType, $iOffset, $iLimit, $aSearchAttrs, $mSortAttributes, $iSortOrder, $sCustomViewSql);
	}

	/**
	 *
	 * @param int|string $mIdOrUUID
	 * @return string
	 */
	public function getEntityType($mIdOrUUID)
	{
		$sEntityType = null;
		try
		{
			$sEntityType = $this->oStorage->getEntityType($mIdOrUUID);
		}
		catch (\Aurora\System\Exceptions\DbException $oException)
		{
			throw new \Aurora\System\Exceptions\ApiException(0, $oException);
		}
		return $sEntityType;
	}

	/**
	 *
	 * @param int|string $mIdOrUUID
	 * @return \Aurora\System\EAV\Entity
	 */
	public function getEntity($mIdOrUUID, $sType = null)
	{
		$oEntity = null;
		try
		{
			$oEntity = $this->oStorage->getEntity($mIdOrUUID, $sType);
		}
		catch (\Aurora\System\Exceptions\DbException $oException)
		{
			throw new \Aurora\System\Exceptions\ApiException(0, $oException);
		}
		return $oEntity;
	}

	/**
	 * @param \Aurora\System\EAV\Entity |array $mEntity
	 * @param array $aAttributes
	 */
	public function setAttributes($mEntity, $aAttributes)
	{
		if (!is_array($mEntity))
		{
			$mEntity = array($mEntity);
		}
		if (!$this->oStorage->setAttributes($mEntity, $aAttributes))
		{
			throw new \Aurora\System\Exceptions\ManagerException(\Aurora\System\Exceptions\Errs::Main_UnknownError);
		}
	}

	/**
	 *
	 * @param \Aurora\System\EAV\Entity |array $mEntity
	 * @param \Aurora\System\EAV\Attribute $oAttribute
	 * @return boolean
	 * @throws \Aurora\System\Exceptions\ManagerException
	 */
	public function setAttribute($mEntity, \Aurora\System\EAV\Attribute $oAttribute)
	{
		$bResult = false;
		try
		{
			if ($oAttribute->validate())
			{
				if (!$this->oStorage->setAttributes([$mEntity], [$oAttribute]))
				{
					throw new \Aurora\System\Exceptions\ManagerException(\Aurora\System\Exceptions\Errs::Main_UnknownError);
				}
			}
		}
		catch (\Aurora\System\Exceptions\DbException $oException)
		{
			$bResult = false;
			throw $oException;
		}

		return $bResult;
	}

	/**
	 * @return bool
	 */
	public function deleteAttribute($sType, $iEntityId, $sAttribute)
	{
		return $this->oStorage->deleteAttribute($sType, $iEntityId, $sAttribute);
	}

	public function resetOverridedAttributes($sType = null)
	{
		$iPageSize = 20;

		$aTypes = [];
		if (empty($sType))
		{
			$aTypes = $this->getTypes();
		}
		else
		{
			$aTypes = [$sType];
		}
		foreach ($aTypes as $sType)
		{
			$iPage = 0;
			$oEntity = \Aurora\System\EAV\Entity::createInstance($sType);
			if ($oEntity instanceof \Aurora\System\EAV\Entity)
			{
				$aAttributes = array_map(function ($oAttribute) {
						return $oAttribute->Name;
					},
					$oEntity->getOverridedAttributes()
				);
				$iCount = $this->getEntitiesCount($sType);
				$iNumPages = ceil($iCount/$iPageSize);
				while ($iPage <= $iNumPages)
				{
					$aEntities = $this->getEntities(
						$sType,
						$aAttributes,
						abs($iPage * $iPageSize),
						$iPageSize
					);
					foreach($aEntities as $oRealEntity)
					{
						$this->updateEntity($oRealEntity, true);
					}
					$iPage++;
				}
			}
		}
	}



	/**
	 * Tests if there is connection to storage with current settings values.
	 *
	 * @return boolean
	 */
	public function testStorageConnection()
	{
		return $this->oStorage->testConnection();
	}

	/**
	 * Creates tables required for module work by executing create.sql file.
	 *
	 * @return boolean
	 */
	public function createTablesFromFile()
	{
		$bResult = false;

		try
		{
			$bResult = \Aurora\System\Managers\Db::getInstance()->executeSqlFile(
				dirname(__FILE__) . '/Eav/Storages/Db/Sql/create.sql'
			);
		}
		catch (\Aurora\System\Exceptions\DbException $oException)
		{
			throw $oException;
		}

		return $bResult;
	}
}
