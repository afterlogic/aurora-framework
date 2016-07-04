<?php

/* -AFTERLOGIC LICENSE HEADER- */

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
	 */
	public function isEntityExists($iId)
	{
		$bResult = false;
		if ($this->oConnection->Execute($this->oCommandCreator->isEntityExists($iId)))
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
	
	public function createEntity($sModule, $sType)
	{
		$bResult = false;
		if ($this->oConnection->Execute($this->oCommandCreator->createEntity($sModule, $sType)))
		{
			$bResult = $this->oConnection->GetLastInsertId();
		}

		$this->throwDbExceptionIfExist();
		return $bResult;
	}
	
	protected function getEntityBySql($sSql)
	{
		$oEntity = null;
		if ($this->oConnection->Execute($sSql))
		{
			while (false !== ($oRow = $this->oConnection->GetNextRecord()))
			{
				if (!isset($oEntity))
				{
					$oEntity = call_user_func($oRow->entity_type . '::createInstance', $oRow->entity_module);
				}

				if (isset($oEntity))
				{
					$oEntity->iId = (int) $oRow->entity_id;

					if (isset($oRow->attr_name) /*&& $oObject->IsProperty($oRow->prop_key)*/)
					{
						$mValue = $oRow->{'attr_value'};
						if ($oEntity->isEncryptedAttribute($oRow->attr_type))
						{
							$mValue = \api_Utils::DecryptValue($mValue);
						}
						$oEntity->{$oRow->attr_name} = $mValue;
					}
				}
			}			
			$this->oConnection->FreeResult();
		}

		$this->throwDbExceptionIfExist();
		return $oEntity;
	}
	
	/**
	 */
	public function getEntityById($iId)
	{
		return $this->getEntityBySql($this->oCommandCreator->getEntityById($iId));
	}	

	public function getTypes()
	{
		$mResult = false;
		if ($this->oConnection->Execute($this->oCommandCreator->getTypes()))
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
	
	public function getEntitiesCount($sType, $aSearchAttrs)
	{
		$mResult = 0;
		if ($this->oConnection->Execute($this->oCommandCreator->getEntitiesCount($sType, $aSearchAttrs)))
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
	 */
	public function getEntities($sType, $aViewAttrs = array(), $iOffset = 0, $iLimit = 20, $aSearchAttrs = array(), $sOrderBy = '', $iSortOrder = \ESortOrder::ASC)
	{
		$mResult = false;
		
		if ($aViewAttrs === null)
		{
			$aViewAttrs = array();
		}
		else if (count($aViewAttrs) === 0)
		{
			$this->oConnection->Execute($this->oCommandCreator->getAttributesNamesByEntityType($sType));
			while (false !== ($oRow = $this->oConnection->GetNextRecord()))
			{
				$aViewAttrs[] = $oRow->name;
			}
			$this->oConnection->FreeResult();
		}		
		
		if ($this->oConnection->Execute($this->oCommandCreator->getEntities($sType, $aViewAttrs, $iOffset, $iLimit, $aSearchAttrs, $sOrderBy, $iSortOrder)))
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
				$oEntity->iId = (int) $oRow->entity_id;
				$oEntity->sModuleName =  $oRow->entity_module;

				foreach (get_object_vars($oRow) as $sKey => $mValue)
				{
					$sAttrPos = strrpos($sKey, 'attr_', -5);
					if ($sAttrPos !== false)
					{
						$sAttrKey = substr($sKey, 5);
						if ($oEntity->isEncryptedAttribute($sAttrKey))
						{
							$mValue = \api_Utils::DecryptValue($mValue);
						}
						$oEntity->{$sAttrKey} = $mValue;
					}
				}
				$mResult[] = $oEntity;
			}
			$this->oConnection->FreeResult();
		}
		$this->throwDbExceptionIfExist();
		return $mResult;
	}	

	public function getEntitiesByModule($sModule)
	{
		return true;
	}	
	
	/**
	 * @return bool
	 */
	public function deleteEntity($iId)
	{
		$bResult = $this->oConnection->Execute($this->oCommandCreator->deleteEntity($iId));
		if ($bResult)
		{
			$bResult = $this->deleteAttributes($iId);
		}
		
		$this->throwDbExceptionIfExist();
		return $bResult;
	}

	/**
	 */
	public function isAttributeExists(\CAttribute $oAttribute)
	{
		$bResult = false;
		if ($this->oConnection->Execute($this->oCommandCreator->isAttributeExists($oAttribute->EntityId, $oAttribute->Name, $oAttribute->Type)))
		{
			$oRow = $this->oConnection->GetNextRecord();
			if ($oRow)
			{
				$bResult = 0 < (int) $oRow->attrs_count;
			}

			$this->oConnection->FreeResult();
		}
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
			$this->oConnection->Execute($this->oCommandCreator->setAttributes($aEntitiesIds, $aAttributes, $sType));
		}
		$this->throwDbExceptionIfExist();
		return true;
	}	

	/**
	 * @return bool
	 */
	public function deleteAttribute($iId)
	{
		$bResult = $this->oConnection->Execute($this->oCommandCreator->deleteAttribute($iId));
		$this->throwDbExceptionIfExist();
		return $bResult;
	}

	/**
	 * @return bool
	 */
	public function deleteAttributes($iEntityId)
	{
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

	/**
	 * @param $sql
	 * @return bool
	 * @throws CApiBaseException
	 */
	public function execute($sql) {
		$bResult = $this->oConnection->Execute($sql);
		$this->throwDbExceptionIfExist();

		return $bResult;
	}
}
