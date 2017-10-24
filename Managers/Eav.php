<?php
/*
 * @copyright Copyright (c) 2017, Afterlogic Corp.
 * @license AGPL-3.0 or Afterlogic Software License
 *
 * This code is licensed under AGPLv3 license or Afterlogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

namespace Aurora\System\Managers;


/**
 * CApiEAVManager class summary
 *
 * @package EAV
 */
class Eav extends \Aurora\System\Managers\AbstractManagerWithStorage
{
	/**
	 * 
	 * @param string $sForcedStorage
	 */
	public function __construct()
	{
		parent::__construct(null, new Eav\Storages\Db\Storage($this));
	}

	/**
	 * 
	 * @param int|string $mIdOrUUID
	 * @return boolean
	 */
	public function isEntityExists($mIdOrUUID)
	{
		$bResult = false;
		try
		{
			$bResult = $this->oStorage->isEntityExists($mIdOrUUID);
		}
		catch (\Aurora\System\Exceptions\DbException $oException)
		{
			$bResult = false;
			$this->setLastException($oException);
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
		if (isset($oEntity->EntityId) && $this->isEntityExists($oEntity->EntityId) ||
			isset($oEntity->UUID) && $this->isEntityExists($oEntity->UUID))
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
	private function createEntity(\Aurora\System\EAV\Entity &$oEntity)
	{
		$mResult = $this->oStorage->createEntity($oEntity->getModule(), $oEntity->getName(), $oEntity->UUID);
		if ($mResult !== false)
		{
			$oEntity->EntityId = $mResult;
			if (0 < $oEntity->countAttributes())
			{
				$this->setAttributes($oEntity, $oEntity->getAttributes());
			}
		}
		else
		{
			throw new \Aurora\System\Exceptions\ManagerException(Errs::Main_UnknownError);
		}

		return $mResult;
	}
	
	/**
	 * 
	 * @param \Aurora\System\EAV\Entity $oEntity
	 * @return boolean
	 * @throws type
	 */
	protected function updateEntity(\Aurora\System\EAV\Entity $oEntity)
	{
		$mResult = false;
		if (0 < $oEntity->countAttributes())
		{
			try
			{
				$this->setAttributes(
					$oEntity, 
					$oEntity->getAttributes()
				);
				$mResult = true;
			}
			catch (\Aurora\System\Exceptions\DbException $oException)
			{
				$mResult = false;
				throw \Aurora\System\Exceptions\ManagerException(Errs::Main_UnknownError);
			}
		}

		return $mResult;
	}

	/**
	 * 
	 * @param int|string $mIdOrUUID
	 * @return bool
	 */
	public function deleteEntity($mIdOrUUID)
	{
		$bResult = false;
		try
		{
			$bResult = $this->oStorage->deleteEntity($mIdOrUUID);
		}
		catch (\Aurora\System\Exceptions\DbException $oException)
		{
			$this->setLastException($oException);
		}

		return $bResult;
	}
	
	/**
	 * 
	 * @param array $aIdsOrUUIDs
	 * @return bool
	 */
	public function deleteEntities($aIdsOrUUIDs)
	{
		$bResult = false;
		
		if (!empty($aIdsOrUUIDs))
		{
			try
			{
				$bResult = $this->oStorage->deleteEntities($aIdsOrUUIDs);
			}
			catch (\Aurora\System\Exceptions\DbException $oException)
			{
				$this->setLastException($oException);
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
		catch (\Aurora\System\Exceptions\DbException $oException)
		{
			$this->setLastException($oException);
		}
		return $aTypes;
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
			$this->setLastException($oException);
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
	public function getEntities($sType, $aViewAttributes = array(), $iOffset = 0, $iLimit = 0, $aWhere = array(), $mOrderBy = array(), $iSortOrder = \Aurora\System\Enums\SortOrder::ASC, $aIdsOrUUIDs = array())
	{
		$aEntities = array();
		try
		{
			$aEntities = $this->oStorage->getEntities(
				$sType, 
				$aViewAttributes, 
				$iOffset, 
				$iLimit, 
				$aWhere, 
				$mOrderBy, 
				$iSortOrder, 
				$aIdsOrUUIDs
			);
		}
		catch (\Aurora\System\Exceptions\DbException $oException)
		{
			$this->setLastException($oException);
		}
		return $aEntities;
	}

	/**
	 * 
	 * @param int|string $mIdOrUUID
	 * @return \Aurora\System\EAV\Entity
	 */
	public function getEntity($mIdOrUUID)
	{
		$oEntity = null;
		try
		{
			$oEntity = $this->oStorage->getEntity($mIdOrUUID);
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
			throw new \Aurora\System\Exceptions\ManagerException(Errs::Main_UnknownError);
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
				if (!$this->oStorage->setAttributes(array($mEntity), array($oAttribute)))
				{
					throw new \Aurora\System\Exceptions\ManagerException(Errs::Main_UnknownError);
				}
			}
		}
		catch (\Aurora\System\Exceptions\DbException $oException)
		{
			$bResult = false;
			$this->setLastException($oException);
		}

		return $bResult;
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
			$bResult = $this->oStorage->executeSqlFile(
				dirname(__FILE__) . '/Eav/Storages/Db/Sql/create.sql'
			);
		}
		catch (\Aurora\System\Exceptions\DbException $oException)
		{
			$this->setLastException($oException);
		}

		return $bResult;
	}
}
