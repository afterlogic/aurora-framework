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
 * CApiEAVManager class summary
 *
 * @package EAV
 */
class CApiEavManager extends AApiManagerWithStorage
{
	/**
	 * 
	 * @param CApiGlobalManager $oManager
	 * @param string $sForcedStorage
	 */
	public function __construct(CApiGlobalManager &$oManager, $sForcedStorage = 'db')
	{
		parent::__construct('eav', $oManager, $sForcedStorage);
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
		catch (CApiBaseException $oException)
		{
			$bResult = false;
			$this->setLastException($oException);
		}

		return $bResult;
	}

	/**
	 * 
	 * @param \AEntity $oEntity
	 * @return bool
	 */
	public function saveEntity(\AEntity &$oEntity)
	{
		$mResult = false;
		if (isset($oEntity->iId) && $this->isEntityExists($oEntity->iId))
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
	 * @param \AEntity $oEntity
	 * @return type
	 * @throws CApiManagerException
	 */
	private function createEntity(\AEntity &$oEntity)
	{
		$mResult = $this->oStorage->createEntity($oEntity->sModuleName, $oEntity->sClassName, $oEntity->sUUID);
		if (!$mResult)
		{
			throw new CApiManagerException(Errs::Main_UnknownError);
		}
		else if (0 < $oEntity->countAttributes())
		{
			$oEntity->iId = $mResult;
			$aAttributes = array();
			foreach ($oEntity->getAttributesKeys() as $sKey)
			{
				$aAttributes[] = new \CAttribute(
					$sKey, 
					$oEntity->{$sKey}, 
					$oEntity->getType($sKey), 
					$oEntity->isEncryptedAttribute($sKey)
				);
			}
			$this->setAttributes($mResult, $aAttributes);
		}

		return $mResult;
	}
	
	/**
	 * 
	 * @param \AEntity $oEntity
	 * @return boolean
	 * @throws type
	 */
	protected function updateEntity(\AEntity $oEntity)
	{
		$mResult = false;
		$aEntityAttributes = $oEntity->getAttributesKeys();
		if (0 < count($aEntityAttributes))
		{
			$aAttributes = array();
			foreach ($aEntityAttributes as $sKey)
			{
				$aAttributes[] = new \CAttribute(
					$sKey, 
					$oEntity->{$sKey}, 
					$oEntity->getType($sKey),
					$oEntity->isEncryptedAttribute($sKey)
				);
			}
			try
			{
				$this->setAttributes($oEntity->iId, $aAttributes);
				$mResult = true;
			}
			catch (Exception $ex)
			{
				$mResult = false;
				throw CApiManagerException(Errs::Main_UnknownError);
			}
		}

		return $mResult;
	}

	/**
	 * 
	 * @param int|string $mIdOrUUID
	 * @return type
	 */
	public function deleteEntity($mIdOrUUID)
	{
		$bResult = true;
		try
		{
			$bResult = $this->oStorage->deleteEntity($mIdOrUUID);
		}
		catch (CApiBaseException $oException)
		{
			$this->setLastException($oException);
		}

		return $bResult;
	}
	
	/**
	 * 
	 * @param array $aIdsOrUUIDs
	 * @return type
	 */
	public function deleteEntities($aIdsOrUUIDs)
	{
		$bResult = true;
		
		if (!empty($aIdsOrUUIDs))
		{
			try
			{
				$bResult = $this->oStorage->deleteEntities($aIdsOrUUIDs);
			}
			catch (CApiBaseException $oException)
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
		catch (CApiBaseException $oException)
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
		catch (CApiBaseException $oException)
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
	 * @param string $sOrderBy
	 * @param int $iSortOrder
	 * @param array $aIdsOrUUIDs
	 * @return array
	 */
	public function getEntities($sType, $aViewAttributes = array(), $iOffset = 0, $iLimit = 0, $aWhere = array(), $sOrderBy = '', $iSortOrder = \ESortOrder::ASC, $aIdsOrUUIDs = array())
	{
		$aEntities = null;
		try
		{
			$aEntities = $this->oStorage->getEntities(
				$sType, 
				$aViewAttributes, 
				$iOffset, 
				$iLimit, 
				$aWhere, 
				$sOrderBy, 
				$iSortOrder, 
				$aIdsOrUUIDs
			);
		}
		catch (CApiBaseException $oException)
		{
			$this->setLastException($oException);
		}
		return $aEntities;
	}

	/**
	 * 
	 * @param string $sModule
	 * @return boolean
	 */
	public function getEntitiesByModule($sModule)
	{
		// TODO:
		return false;
	}

	/**
	 * 
	 * @param string $sModule
	 * @param string $sType
	 * @return boolean
	 */
	public  function geEntitiesByModuleAndType($sModule, $sType)
	{
		// TODO:
		return false;
	}

	/**
	 * 
	 * @param int|string $mIdOrUUID
	 * @return \AEntity
	 */
	public function getEntity($mIdOrUUID)
	{
		$oEntity = null;
		try
		{
			$oEntity = $this->oStorage->getEntity($mIdOrUUID);
		}
		catch (CApiBaseException $oException)
		{
			$this->setLastException($oException);
		}
		return $oEntity;
	}

	/**
	 * @param int|array $mEntityId
	 * @param array $aAttributes
	 */
	public function setAttributes($mEntityId, $aAttributes)
	{
		if (!is_array($mEntityId))
		{
			$mEntityId = array($mEntityId);
		}
		if (!$this->oStorage->setAttributes($mEntityId, $aAttributes))
		{
			throw new CApiManagerException(Errs::Main_UnknownError);
		}
	}

	/**
	 * 
	 * @param CAttribute $oAttribute
	 * @return boolean
	 * @throws CApiManagerException
	 */
	public function setAttribute(CAttribute $oAttribute)
	{
		$bResult = false;
		try
		{
			if ($oAttribute->validate())
			{
				if (!$this->oStorage->setAttributes(array($oAttribute->EntityId), array($oAttribute)))
				{
					throw new CApiManagerException(Errs::Main_UnknownError);
				}
			}
		}
		catch (CApiBaseException $oException)
		{
			$bResult = false;
			$this->setLastException($oException);
		}

		return $bResult;
	}

	/**
	 * 
	 * @param CAttribute $oAttribute
	 * @return bool
	 */
	private function deleteAttribute(CAttribute $oAttribute)
	{
		$bResult = true;
		try
		{
			$bResult = $this->oStorage->deleteAttribute($oAttribute);
		}
		catch (CApiBaseException $oException)
		{
			$this->setLastException($oException);
		}

		return $bResult;
	}

	/**
	 * 
	 * @param int $iEntityId
	 * @return bool
	 */
	private function deleteAttributes($iEntityId)
	{
		$bResult = true;
		try
		{
			$bResult = $this->oStorage->deleteAttributes($iEntityId);
		}
		catch (CApiBaseException $oException)
		{
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
		$bResult = true;
		
		try
		{
			$sFilePath = dirname(__FILE__) . '/storages/db/sql/create.sql';
			$bResult = $this->oStorage->executeSqlFile($sFilePath);
		}
		catch (CApiBaseException $oException)
		{
			$this->setLastException($oException);
			$bResult = false;
		}

		return $bResult;
	}
}
