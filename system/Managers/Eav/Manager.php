<?php
/*
 * @copyright Copyright (c) 2017, Afterlogic Corp.
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

namespace Aurora\System\Managers\Eav;


/**
 * CApiEAVManager class summary
 *
 * @package EAV
 */
class Manager extends \Aurora\System\AbstractManagerWithStorage
{
	/**
	 * 
	 * @param \Aurora\System\GlobalManager $oManager
	 * @param string $sForcedStorage
	 */
	public function __construct(\Aurora\System\GlobalManager &$oManager, $sForcedStorage = 'db')
	{
		parent::__construct('Eav', $oManager, $sForcedStorage);
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
		catch (\Aurora\System\Exceptions\BaseException $oException)
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
				$this->setAttributes($mResult, $oEntity->getAttributes());
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
					$oEntity->EntityId, 
					$oEntity->getAttributes()
				);
				$mResult = true;
			}
			catch (Exception $ex)
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
		catch (\Aurora\System\Exceptions\BaseException $oException)
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
			catch (\Aurora\System\Exceptions\BaseException $oException)
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
		catch (\Aurora\System\Exceptions\BaseException $oException)
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
		catch (\Aurora\System\Exceptions\BaseException $oException)
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
	public function getEntities($sType, $aViewAttributes = array(), $iOffset = 0, $iLimit = 0, $aWhere = array(), $mOrderBy = array(), $iSortOrder = \ESortOrder::ASC, $aIdsOrUUIDs = array())
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
		catch (\Aurora\System\Exceptions\BaseException $oException)
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
		catch (\Aurora\System\Exceptions\BaseException $oException)
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
			throw new \Aurora\System\Exceptions\ManagerException(Errs::Main_UnknownError);
		}
	}

	/**
	 * 
	 * @param \Aurora\System\EAV\Attribute $oAttribute
	 * @return boolean
	 * @throws \Aurora\System\Exceptions\ManagerException
	 */
	public function setAttribute(\Aurora\System\EAV\Attribute $oAttribute)
	{
		$bResult = false;
		try
		{
			if ($oAttribute->validate())
			{
				if (!$this->oStorage->setAttributes(array($oAttribute->EntityId), array($oAttribute)))
				{
					throw new \Aurora\System\Exceptions\ManagerException(Errs::Main_UnknownError);
				}
			}
		}
		catch (\Aurora\System\Exceptions\BaseException $oException)
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
				dirname(__FILE__) . '/storages/db/sql/create.sql'
			);
		}
		catch (\Aurora\System\Exceptions\BaseException $oException)
		{
			$this->setLastException($oException);
		}

		return $bResult;
	}
}
