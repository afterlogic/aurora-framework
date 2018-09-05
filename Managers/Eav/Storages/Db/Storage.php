<?php
/*
 * This code is licensed under AGPLv3 license or Afterlogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

namespace Aurora\System\Managers\Eav\Storages\Db;

/**
 * @license https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @license https://afterlogic.com/products/common-licensing AfterLogic Software License
 * @copyright Copyright (c) 2018, Afterlogic Corp.
 *
 * @internal
 * 
 * @package EAV
 * @subpackage Storages
 */
class Storage extends \Aurora\System\Managers\Eav\Storages\Storage
{
	/**
	 * @var CDbStorage $oConnection
	 */
	protected $oConnection;

	/**
	 * @var CApiEavCommandCreatorMySQL|CApiEavCommandCreatorPostgreSQL
	 */
	protected $oCommandCreator;

	/**
	 * 
	 * @param \Aurora\System\Managers\AbstractManager $oManager
	 */
	public function __construct(\Aurora\System\Managers\Eav &$oManager)
	{
		parent::__construct($oManager);

		$this->oConnection =& \Aurora\System\Api::GetConnection();
		$this->oCommandCreator = new CommandCreator\MySQL();
	}

	/**
	 * 
	 * @param type $mIdOrUUID
	 * @return type
	 */
	public function isEntityExists($mIdOrUUID, $sType = null)
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
		return $bResult;
	}	
	
	/**
	 * 
	 * @param type $oEntity
	 * @return type
	 */
	public function createEntity($oEntity)
	{
		$bResult = false;
		if ($this->oConnection->Execute(
				$this->oCommandCreator->createEntity($oEntity->getModule(), $oEntity->getName(), $oEntity->UUID, $oEntity->ParentUUID)
			)
		)
		{
			$bResult = $this->oConnection->GetLastInsertId();
		}
		if ($bResult !== false)
		{
			$oEntity->EntityId = $bResult;
			if (0 < $oEntity->countAttributes())
			{
				try 
				{
					$this->setAttributes(array($oEntity), $oEntity->getAttributes());
				}
				catch (\Exception $oEx)
				{
					$this->deleteEntity($bResult);
					throw $oEx;
				}
			}
		}
		else
		{
			throw new \Aurora\System\Exceptions\ManagerException(Errs::Main_UnknownError);
		}		

		return $bResult;
	}
	
	/**
	 * 
	 * @param type $oEntity
	 * @param bool $bOnlyOverrided
	 * @return type
	 */
	public function updateEntity($oEntity, $bOnlyOverrided = false) 
	{
		$mResult = false;
		if (0 < $oEntity->countAttributes())
		{
			try
			{
				$this->setAttributes(
					array($oEntity), 
					$oEntity->getAttributes($bOnlyOverrided)
				);
				$mResult = true;
			}
			catch (\Aurora\System\Exceptions\DbException $oException)
			{
				$mResult = false;
				throw new \Aurora\System\Exceptions\ManagerException(\Aurora\System\Exceptions\Errs::Main_UnknownError);
			}
		}

		return $mResult;		
	}
	
	/**
	 * 
	 * @param type $mIdOrUUID
	 * @return type
	 */
	public function getEntity($mIdOrUUID, $sType)
	{
		$oEntity = null;
		if ($this->oConnection && $this->oConnection->Execute(
				$this->oCommandCreator->getEntity($mIdOrUUID)
			)
		)
		{
			while (false !== ($oRow = $this->oConnection->GetNextRecord()))
			{
				if (!isset($oEntity))
				{
					$oEntity = \Aurora\System\EAV\Entity::createInstance(
						$oRow->entity_type, 
						$oRow->entity_module
					);
				}

				if (isset($oEntity))
				{
					$oEntity->EntityId = (int) $oRow->entity_id;
					$oEntity->UUID = isset($oRow->entity_uuid) ? $oRow->entity_uuid : '';
					$oEntity->ParentUUID = isset($oRow->parent_uuid) ? $oRow->parent_uuid : '';
					$oEntity->ModuleName = isset($oRow->entity_module) ? $oRow->entity_module : '';

					if (isset($oRow->attr_name) && !$oEntity->isSystemAttribute($oRow->attr_name))
					{
						$mValue = $oRow->attr_value;
						$bEncrypt = $oEntity->isEncryptedAttribute($oRow->attr_name);
						$oAttribute = \Aurora\System\EAV\Attribute::createInstance(
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

		return ((isset($oEntity) && get_class($oEntity) ===  ltrim($sType, '\\')) || ($sType === null)) ? $oEntity : null;
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
				$mResult = (int) $oRow->entities_count;
			}			
			$this->oConnection->FreeResult();
		}

		return $mResult;
	}
	
	/**
	 * 
	 * @param type $sType
	 * @param type $iOffset
	 * @param type $iLimit
	 * @param type $aSearchAttrs
	 * @return array
	 */
	protected function getEntitiesUids($sType, $iOffset = 0, $iLimit = 20, $aSearchAttrs = array(), $mSortAttributes = array(), $iSortOrder = \Aurora\System\Enums\SortOrder::ASC)
	{
		$aUids = array();
		if ($this->oConnection->Execute(
				$this->oCommandCreator->getEntities(
					$sType, 
					array('UUID'), 
					$iOffset, 
					$iLimit, 
					$aSearchAttrs,
					$mSortAttributes,
					$iSortOrder
				)
			)
		)
		{
			$oRow = null;
			while (false !== ($oRow = $this->oConnection->GetNextRecord()))
			{
				$aUids[] = $oRow->attr_UUID;
			}
		}
		$this->oConnection->FreeResult();
		
		return $aUids; 
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
		$mResult = array();
		
		$aIdsOrUUIDs = array_merge(
			$aIdsOrUUIDs, 
			$this->getEntitiesUids($sType, $iOffset, $iLimit, $aSearchAttrs, $mOrderBy, $iSortOrder)
		);
		
		if ($aViewAttrs === null) {
			$aViewAttrs = array();
		}
		else if (count($aViewAttrs) === 0) {
			$aViewAttrs = \Aurora\System\EAV\Entity::createInstance($sType)->getAttributesKeys();
		}		
		
		// request for \Aurora\Modules\Contacts\Classes\Contact objects were failed with "Memory allocation error: 1038 Out of sort memory, consider increasing server sort buffer size"
		$this->oConnection->Execute("set sort_buffer_size=1024*1024"); 
		
		if (count($aIdsOrUUIDs) > 0)
		{
			if ($this->oConnection->Execute(
					
					$this->oCommandCreator->getEntities(
						$sType, 
						$aViewAttrs, 
						0, 
						0, 
						array(), 
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
					$oEntity = \Aurora\System\EAV\Entity::createInstance($sType);
					foreach (get_object_vars($oRow) as $sKey => $mValue)
					{
						if (strrpos($sKey, 'attr_', -5) !== false && isset($mValue))
						{
							$sAttrKey = substr($sKey, 5);
							if (!$oEntity->isSystemAttribute($sAttrKey))
							{
								$bIsEncrypted = $oEntity->isEncryptedAttribute($sAttrKey);
								$oAttribute = \Aurora\System\EAV\Attribute::createInstance(
									$sAttrKey, 
									$mValue, 
									$oEntity->getType($sAttrKey), 
									$bIsEncrypted, 
									$oEntity->EntityId
								);
								$oAttribute->Encrypted = $bIsEncrypted;
								$oEntity->{$sAttrKey} = $oAttribute;
							}
							else
							{
								settype($mValue, $oEntity->getType($sAttrKey));
								$oEntity->{$sAttrKey} = $mValue;
							}
						}
					}
					$mResult[] = $oEntity;
				}
				$this->oConnection->FreeResult();
			}
		}
		return $mResult;
	}	

	/**
	 * @return bool
	 */
	public function deleteEntity($mIdOrUUID, $sType = null)
	{
		$bResult = $this->oConnection->Execute(
			$this->oCommandCreator->deleteEntity($mIdOrUUID)
		);
		return $bResult;
	}

	/**
	 * @return bool
	 */
	public function deleteEntities($aIdsOrUUIDs, $sType = null)
	{
		$bResult = $this->oConnection->Execute(
			$this->oCommandCreator->deleteEntities($aIdsOrUUIDs)
		);
		return $bResult;
	}

	/**
	 */
	public function setAttributes($aEntities, $aAttributes)
	{
		$aAttributesByTypes = array();
		foreach ($aAttributes as $oAttribute)
		{
			$aAttributesByTypes[$oAttribute->Type][] = $oAttribute;
		}
		
		foreach ($aAttributesByTypes as $sType => $aAttributes)
		{
			$mSql = $this->oCommandCreator->setAttributes($aEntities, $aAttributes, $sType);
			if (!is_array($mSql))
			{
				$mSql = array($mSql);
			}
			foreach ($mSql as $sSql)
			{
				$this->oConnection->Execute(
					$sSql
				);
			}
			
		}
		return true;
	}	
	
	/**
	 * @return bool
	 */
	public function deleteAttribute($sType, $iEntityId, $sAttribute)
	{
		$bResult = $this->oConnection->Execute(
			$this->oCommandCreator->deleteAttribute($sType, $iEntityId, $sAttribute)
		);
		return $bResult;
	}
	
	
	/**
	 * @return array
	 */
	public function getAttributesNamesByEntityType($sEntityTypes)
	{
		$aResult = [];
		if ($this->oConnection->Execute(
			$this->oCommandCreator->getAttributesNamesByEntityType($sEntityTypes)))
		{
			while (false !== ($oRow = $this->oConnection->GetNextRecord()))
			{
				$aResult[] = $oRow->name;
			}

		}
		
		return $aResult;
	}

	public function testConnection()
	{
		return $this->oConnection->Connect();
	}
}
