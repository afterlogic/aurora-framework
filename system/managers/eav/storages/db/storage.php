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
	public function isObjectExists($iId)
	{
		$bResult = false;
		if ($this->oConnection->Execute($this->oCommandCreator->isObjectExists($iId)))
		{
			$oRow = $this->oConnection->GetNextRecord();
			if ($oRow)
			{
				$bResult = 0 < (int) $oRow->objects_count;
			}

			$this->oConnection->FreeResult();
		}
		$this->throwDbExceptionIfExist();
		return $bResult;
	}	
	
	public function createObject($sModule, $sObjectType)
	{
		$bResult = false;
		if ($this->oConnection->Execute($this->oCommandCreator->createObject($sModule, $sObjectType)))
		{
			$bResult = $this->oConnection->GetLastInsertId();
		}

		$this->throwDbExceptionIfExist();
		return $bResult;
	}
	
	protected function getObjectBySql($sSql)
	{
		$oObject = null;
		if ($this->oConnection->Execute($sSql))
		{
			while (false !== ($oRow = $this->oConnection->GetNextRecord()))
			{
				if (!isset($oObject))
				{
					$oObject = call_user_func($oRow->obj_type . '::createInstance', $oRow->obj_module);
				}

				if (isset($oObject))
				{
					$oObject->iObjectId = $oRow->obj_id;

					if (isset($oRow->prop_key) /*&& $oObject->IsProperty($oRow->prop_key)*/)
					{
						$mValue = $oRow->{'prop_value_' . $oRow->prop_type};
						if ($oObject->isEncryptedProperty($oRow->prop_type))
						{
							$mValue = \api_Utils::DecryptValue($mValue);
						}
						$oObject->{$oRow->prop_key} = $mValue;
					}
				}
			}			
			$this->oConnection->FreeResult();
		}

		$this->throwDbExceptionIfExist();
		return $oObject;
	}
	
	/**
	 */
	public function getObjectById($iId)
	{
		return $this->getObjectBySql($this->oCommandCreator->getObjectById($iId));
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
				$mResult[] = $oRow->object_type;
			}
		}
		$this->throwDbExceptionIfExist();
		return $mResult;
	}	
	
	public function getObjectsCount($sType, $aSearchProperties)
	{
		$mResult = 0;
		if ($this->oConnection->Execute($this->oCommandCreator->getObjectsCount($sType, $aSearchProperties)))
		{
			while (false !== ($oRow = $this->oConnection->GetNextRecord()))
			{
				$mResult = $oRow->objects_count;
			}			
			$this->oConnection->FreeResult();
		}

		$this->throwDbExceptionIfExist();
		return $mResult;
	}
	/**
	 */
	public function getObjects($sType, $aViewProperties = array(), $iOffset = 0, $iLimit = 20, $aSearchProperties = array(), $sOrderBy = '', $iSortOrder = \ESortOrder::ASC)
	{
		$mResult = false;
		if (class_exists($sType) && $this->oConnection->Execute($this->oCommandCreator->getObjects($sType, $aViewProperties, $iOffset, $iLimit, $aSearchProperties, $sOrderBy, $iSortOrder)))
		{
			$oRow = null;
			$mResult = array();
			while (false !== ($oRow = $this->oConnection->GetNextRecord()))
			{
				$oObject = call_user_func($sType . '::createInstance');
				$oObject->iObjectId = $oRow->obj_id;
				$oObject->sModuleName =  $oRow->obj_module;

				foreach (get_object_vars($oRow) as $sKey => $mValue)
				{
					$sPropertyPos = strrpos($sKey, 'prop_', -5);
					if ($sPropertyPos !== false)
					{
						$sPropertyKey = substr($sKey, 5);
						if ($oObject->isEncryptedProperty($sPropertyKey))
						{
							$mValue = \api_Utils::DecryptValue($mValue);
						}
						$oObject->{$sPropertyKey} = $mValue;
					}
				}
				
				$mResult[] = $oObject;
			}
		}
		$this->throwDbExceptionIfExist();
		return $mResult;
	}	

	public function getObjectsByModule($sModule)
	{
		return true;
	}	
	
	/**
	 * @return bool
	 */
	public function deleteObject($iId)
	{
		$bResult = $this->oConnection->Execute($this->oCommandCreator->deleteObject($iId));
		$this->throwDbExceptionIfExist();
		return $bResult;
	}

	/**
	 */
	public function isPropertyExists(CProperty $oProperty)
	{
		$bResult = false;
		if ($this->oConnection->Execute($this->oCommandCreator->isPropertyExists($oProperty->ObjectId, $oProperty->Name)))
		{
			$oRow = $this->oConnection->GetNextRecord();
			if ($oRow)
			{
				$bResult = 0 < (int) $oRow->properties_count;
			}

			$this->oConnection->FreeResult();
		}
		$this->throwDbExceptionIfExist();
		return $bResult;
	}
	
	/**
	 */
	public function setProperties($aObjectIds, $aProperties)
	{
		$bResult = $this->oConnection->Execute($this->oCommandCreator->setProperties($aObjectIds, $aProperties));
		$this->throwDbExceptionIfExist();
		return $bResult;
	}	

	/**
	 * @return bool
	 */
	public function deleteProperty($iId)
	{
		$bResult = $this->oConnection->Execute($this->oCommandCreator->deleteProperty($iId));
		$this->throwDbExceptionIfExist();
		return $bResult;
	}

	/**
	 * @return bool
	 */
	public function deleteProperties($iObjectId)
	{
		$bResult = $this->oConnection->Execute($this->oCommandCreator->deleteProperties($iObjectId));
		$this->throwDbExceptionIfExist();
		return $bResult;
	}
}
