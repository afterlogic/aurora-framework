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

					if (isset($oRow->prop_key) && $oObject->IsProperty($oRow->prop_key))
					{
						$oObject->{$oRow->prop_key} = $oRow->{'prop_value_' . $oRow->prop_type};
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
	public function getObjects($sType, $aViewProperties = array(), $iPage = 0, $iPerPage = 20, $aSearchProperties = array(), $sOrderBy = '', $iSortOrder = \ESortOrder::ASC)
	{
		$mResult = false;
		if ($this->oConnection->Execute($this->oCommandCreator->getObjects($sType, $aViewProperties, $iPage, $iPerPage, $aSearchProperties, $sOrderBy, $iSortOrder)))
		{
			$oRow = null;
			$mResult = array();
			while (false !== ($oRow = $this->oConnection->GetNextRecord()))
			{
				$oObject = call_user_func($sType . '::createInstance');

				$oObject->iObjectId = $oRow->obj_id;
				$oObject->sModuleName =  $oRow->obj_module;
				
				$aMap = $oObject->getMap();
				foreach($aMap as $sKey => $aMapItem)
				{
					if (isset($oRow->{'prop_' . $sKey}))
					{
						$oObject->{$sKey} = $oRow->{'prop_' . $sKey};
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
	public function createProperty(CProperty $oProperty)
	{
		$bResult = false;
		if ($this->oConnection->Execute($this->oCommandCreator->createProperty($oProperty)))
		{
			$oProperty->Id = $this->oConnection->GetLastInsertId('eav_properties', 'id');
			$bResult = true;
		}

		$this->throwDbExceptionIfExist();
		return $bResult;
	}

	/**
	 */
	public function updateProperty(CProperty &$oProperty)
	{
		$bResult = $this->oConnection->Execute($this->oCommandCreator->updateProperty($oProperty));
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

	/**
	 */
	public function getProperties($iObjectId, $sValue)
	{
		$aProperties = false;
		if ($this->oConnection->Execute(
			$this->oCommandCreator->getProperties($iObjectId, $sValue)))
		{
			$oRow = null;
			$aProperties = array();
			while (false !== ($oRow = $this->oConnection->GetNextRecord()))
			{
				$oProperty = new \CProperty();
				$oProperty->InitByDbRow($oRow);
				$aProperties[] = $oProperty;
			}
		}
		$this->throwDbExceptionIfExist();
		return $aProperties;
	}	
	
	/**
	 */
	public function getProperty(CProperty $oProperty)
	{
		return $this->getPropertyBySql($this->oCommandCreator->getProperty($oProperty->Id));
	}
	
	/**
	 * @param string $sSql
	 *
	 * @return \CProperty
	 */
	protected function getPropertyBySql($sSql)
	{
		$oProperty = null;
		if ($this->oConnection->Execute($sSql))
		{
			$oRow = $this->oConnection->GetNextRecord();
			if ($oRow)
			{
				$oProperty = new \CProperty();
				$oProperty->InitByDbRow($oRow);
			}
			$this->oConnection->FreeResult();
		}

		$this->throwDbExceptionIfExist();
		return $oProperty;
	}	
}
