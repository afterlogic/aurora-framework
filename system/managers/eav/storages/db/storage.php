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
class CApiEavDbStorage extends CApiEavStorage
{
	/**
	 * @var CDbStorage $oConnection
	 */
	protected $oConnection;

	/**
	 * @var CApiDomainsCommandCreator
	 */
	protected $oCommandCreator;

	/**
	 * @param CApiGlobalManager &$oManager
	 */
	public function __construct(AApiManager &$oManager)
	{
		parent::__construct('db', $oManager);

		$this->oConnection =& $oManager->GetConnection();
		$this->oCommandCreator =& $oManager->GetCommandCreator(
			$this, array(
				EDbType::MySQL => 'CApiEavCommandCreatorMySQL',
				EDbType::PostgreSQL => 'CApiEavCommandCreatorPostgreSQL'
			)
		);
	}

	/**
	 * 
	 * @param type $mIdOrUUID
	 * @return type
	 */
	public function isEntityExists($mIdOrUUID)
	{
		$bResult = false;
		
		if ($this->oConnection->Execute(
				$this->oCommandCreator->isEntityExists($mIdOrUUID)
			)
		)
		{
			$oRow = $this->oConnection->GetNextRecord();
			if ($oRow)
			{
				$bResult = 0 < (int) $oRow->entities_count;
			}

			$this->oConnection->FreeResult();
		}
		$this->throwDbExceptionIfExist();
		return $bResult;
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
		$bResult = false;
		if ($this->oConnection->Execute(
				$this->oCommandCreator->createEntity($sModule, $sType, $sUUID)
			)
		)
		{
			$bResult = $this->oConnection->GetLastInsertId();
		}

		$this->throwDbExceptionIfExist();
		return $bResult;
	}
	
	/**
	 * 
	 * @param type $mIdOrUUID
	 * @return type
	 */
	public function getEntity($mIdOrUUID)
	{
		$oEntity = null;
		if ($this->oConnection->Execute(
				$this->oCommandCreator->getEntity($mIdOrUUID)
			)
		)
		{
			while (false !== ($oRow = $this->oConnection->GetNextRecord()))
			{
				if (!isset($oEntity))
				{
					$oEntity = call_user_func(
						$oRow->entity_type . '::createInstance', 
						$oRow->entity_module
					);
				}

				if (isset($oEntity))
				{
					$oEntity->EntityId = (int) $oRow->entity_id;
					$oEntity->UUID = isset($oRow->entity_uuid) ? $oRow->entity_uuid : '';

					if (isset($oRow->attr_name))
					{
						$mValue = $oRow->attr_value;
						$bEncrypt = $oEntity->isEncryptedAttribute($oRow->attr_name);
						$oAttribute = \CAttribute::createInstance(
							$oRow->attr_name, 
							$mValue, 
							$oRow->attr_type, 
							$bEncrypt, 
							$oEntity->EntityId
						);
						$oAttribute->Encrypted = $bEncrypt;
						$oEntity->{$oRow->attr_name} = $oAttribute;
					}
				}
			}			
			$this->oConnection->FreeResult();
		}

		$this->throwDbExceptionIfExist();
		return $oEntity;
	}	

	public function getTypes()
	{
		$mResult = false;
		if ($this->oConnection->Execute(
				$this->oCommandCreator->getTypes()
			)
		)
		{
			$oRow = null;
			$mResult = array();
			while (false !== ($oRow = $this->oConnection->GetNextRecord()))
			{
				$mResult[] = $oRow->entity_type;
			}
		}
		$this->throwDbExceptionIfExist();
		return $mResult;
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
		$mResult = 0;
		if ($this->oConnection->Execute(
				$this->oCommandCreator->getEntitiesCount($sType, $aWhere, $aIds)
			)
		)
		{
			while (false !== ($oRow = $this->oConnection->GetNextRecord()))
			{
				$mResult = $oRow->entities_count;
			}			
			$this->oConnection->FreeResult();
		}

		$this->throwDbExceptionIfExist();
		return $mResult;
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
		$mResult = false;
		
		if ($aViewAttrs === null)
		{
			$aViewAttrs = array();
		}
		else if (count($aViewAttrs) === 0)
		{
			$this->oConnection->Execute(
				$this->oCommandCreator->getAttributesNamesByEntityType($sType)
			);
			while (false !== ($oRow = $this->oConnection->GetNextRecord()))
			{
				$aViewAttrs[] = $oRow->name;
			}
			$this->oConnection->FreeResult();
		}		
		
		if ($this->oConnection->Execute(
				$this->oCommandCreator->getEntities(
					$sType, 
					$aViewAttrs, 
					$iOffset, 
					$iLimit, 
					$aSearchAttrs, 
					$mOrderBy, 
					$iSortOrder, 
					$aIdsOrUUIDs
				)
			)
		)
		{
			$oRow = null;
			$mResult = array();
			while (false !== ($oRow = $this->oConnection->GetNextRecord()))
			{
				if (class_exists($sType))
				{
					$oEntity = call_user_func($sType . '::createInstance');
				}
				else
				{
					$oEntity = new \AEntity($sType);
				}
				$oEntity->EntityId = (int) $oRow->entity_id;
				$oEntity->UUID = $oRow->entity_uuid;
				$oEntity->sModuleName =  $oRow->entity_module;

				foreach (get_object_vars($oRow) as $sKey => $mValue)
				{
					if (strrpos($sKey, 'attr_', -5) !== false)
					{
						$sAttrKey = substr($sKey, 5);
						$bIsEncrypted = $oEntity->isEncryptedAttribute($sAttrKey);
						$oAttribute = \CAttribute::createInstance(
							$sAttrKey, 
							$mValue, 
							$oEntity->getType($sAttrKey), 
							$bIsEncrypted, 
							$oEntity->EntityId
						);
						$oAttribute->Encrypted = $bIsEncrypted;
						$oEntity->{$sAttrKey} = $oAttribute;
					}
				}
				$mResult[] = $oEntity;
			}
			$this->oConnection->FreeResult();
		}
		$this->throwDbExceptionIfExist();
		return $mResult;
	}	

	/**
	 * @return bool
	 */
	public function deleteEntity($mIdOrUUID)
	{
		$bResult = $this->oConnection->Execute(
			$this->oCommandCreator->deleteEntity($mIdOrUUID)
		);
		$this->throwDbExceptionIfExist();
		return $bResult;
	}

	/**
	 * @return bool
	 */
	public function deleteEntities($aIdsOrUUIDs)
	{
		$bResult = $this->oConnection->Execute(
			$this->oCommandCreator->deleteEntities($aIdsOrUUIDs)
		);
		$this->throwDbExceptionIfExist();
		return $bResult;
	}

	/**
	 */
	public function setAttributes($aEntitiesIds, $aAttributes)
	{
		$aAttributesByTypes = array();
		foreach ($aAttributes as $oAttribute)
		{
			$aAttributesByTypes[$oAttribute->Type][] = $oAttribute;
		}
		
		foreach ($aAttributesByTypes as $sType => $aAttributes)
		{
			$this->oConnection->Execute(
				$this->oCommandCreator->setAttributes($aEntitiesIds, $aAttributes, $sType)
			);
		}
		$this->throwDbExceptionIfExist();
		return true;
	}	
	
	/**
	 * @return bool
	 */
	public function getAttributesNamesByEntityType($sEntityTypes)
	{
		$bResult = $this->oConnection->Execute($this->oCommandCreator->getAttributesNamesByEntityType($sEntityTypes));
		$this->throwDbExceptionIfExist();
		return $bResult;
	}

	public function testConnection()
	{
		return $this->oConnection->Connect();
	}
}
