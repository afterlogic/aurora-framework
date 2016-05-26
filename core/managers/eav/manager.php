<?php

/* -AFTERLOGIC LICENSE HEADER- */

/**
 * CApiEAVManager class summary
 *
 * @package EAV
 */
class CApiEavManager extends AApiManagerWithStorage
{
	/**
	 * @param CApiGlobalManager &$oManager
	 */
	public function __construct(CApiGlobalManager &$oManager, $sForcedStorage = 'db')
	{
		parent::__construct('eav', $oManager, $sForcedStorage);
		
		$this->inc('classes.property');
	}
	
	/**
	 * @return bool
	 */
	public function isObjectExists($iObjectId)
	{
		$bResult = false;
		try
		{
			$bResult = $this->oStorage->isObjectExists($iObjectId);
		}
		catch (CApiBaseException $oException)
		{
			$bResult = false;
			$this->setLastException($oException);
		}

		return $bResult;		
	}	
	
	public function saveObject(\api_APropertyBag &$oObject)
	{
		$mResult = false;
		if (isset($oObject->iObjectId) && $this->isObjectExists($oObject->iObjectId))
		{
			$mResult = $this->updateObject($oObject);
		}
		else
		{
			$mResult = $this->createObject($oObject);
		}
		
		return $mResult;
	}

	protected function createObject(\api_APropertyBag &$oObject)
	{
		$mResult = $this->oStorage->createObject($oObject->sModuleName, $oObject->sClassName);
		if (!$mResult)
		{
			throw new CApiManagerException(Errs::Main_UnknownError);
		}
		else if (0 < count($oObject->getMap()))
		{
			$oObject->iObjectId = $mResult;
			$aMap = $oObject->getMap();
			$aProperties = array();
			foreach (array_keys($aMap) as $sKey)
			{
				$aProperties[] = new \CProperty($sKey, $oObject->{$sKey}, $oObject->getPropertyType($sKey), $oObject->isEncryptedProperty($sKey));
			}
			$this->setProperties($mResult, $aProperties);
		}
		
		return $mResult;
	}
	
	protected function updateObject(\api_APropertyBag $oObject)
	{
		$mResult = false;
		$aMap = $oObject->getMap();
		if (0 < count($aMap))
		{
			$aProperties = array();
			foreach (array_keys($aMap) as $sKey)
			{
				$aProperties[] = new \CProperty($sKey, $oObject->{$sKey}, $oObject->getPropertyType($sKey), $oObject->isEncryptedProperty($sKey));
			}
			try 
			{
				$this->setProperties($oObject->iObjectId, $aProperties);
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
	
	public function deleteObject($iObjectId)
	{
		$bResult = true;
		try
		{
			if ($this->oStorage->deleteObject($iObjectId))
			{
				$bResult = $this->deleteProperties($iObjectId);
			}
		}
		catch (CApiBaseException $oException)
		{
			$this->setLastException($oException);
		}

		return $bResult;
	}
	
	public function getObjectsCount($sType, $aSearchProperties = array())
	{
		$iCount = 0;
		try
		{
			$iCount = $this->oStorage->getObjectsCount($sType, $aSearchProperties);
		}
		catch (CApiBaseException $oException)
		{
			$this->setLastException($oException);
		}
		return $iCount;		
	}
	
	public function getObjects($sType, $aViewProperties = array(), $iOffset = 0, $iLimit = 0, $aSearchProperties = array(), $sOrderBy = '', $iSortOrder = \ESortOrder::ASC)
	{
		$aObjects = null;
		try
		{
			$aObjects = $this->oStorage->getObjects($sType, $aViewProperties, $iOffset, $iLimit, $aSearchProperties, $sOrderBy, $iSortOrder);
		}
		catch (CApiBaseException $oException)
		{
			$this->setLastException($oException);
		}
		return $aObjects;		
	}
	
	public function getObjectsByModule($sModule)
	{
		// TODO:
		return false;
	}
	
	public  function getObjectsByModuleAndType($sModule, $sType)
	{
		// TODO:
		return false;
	}
	
	public function getObjectById($iId)
	{
		$oObject = null;
		try
		{
			$oObject = $this->oStorage->getObjectById($iId);
		}
		catch (CApiBaseException $oException)
		{
			$this->setLastException($oException);
		}
		return $oObject;
	}
	
	/**
	 * @param int|array $mObjectId
	 */
	public function setProperties($mObjectId, $aProperties)
	{
		if (!is_array($mObjectId))
		{
			$mObjectId = array($mObjectId);
		}
		if (!$this->oStorage->setProperties($mObjectId, $aProperties))
		{
			throw new CApiManagerException(Errs::Main_UnknownError);
		}
	}

	/**
	 */
	public function setProperty(CProperty $oProperty)
	{
		$bResult = false;
		try
		{
			if ($oProperty->validate())
			{
				if (!$this->oStorage->setProperties(array($oProperty->ObjectId), array($oProperty)))
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
	 * @return bool
	 */
	public function deleteProperty(CProperty $oProperty)
	{
		$bResult = true;
		try
		{
			$bResult = $this->oStorage->deleteProperty($oProperty);
		}
		catch (CApiBaseException $oException)
		{
			$this->setLastException($oException);
		}

		return $bResult;
	}

	/**
	 * @return bool
	 */
	public function deleteProperties($iObjectId)
	{
		$bResult = true;
		try
		{
			$bResult = $this->oStorage->deleteProperties($iObjectId);
		}
		catch (CApiBaseException $oException)
		{
			$this->setLastException($oException);
		}

		return $bResult;
	}
}
