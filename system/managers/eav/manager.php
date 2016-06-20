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
		
		$this->inc('classes.attribute');
	}
	
	/**
	 * @return bool
	 */
	public function isEntityExists($iId)
	{
		$bResult = false;
		try
		{
			$bResult = $this->oStorage->isEntityExists($iId);
		}
		catch (CApiBaseException $oException)
		{
			$bResult = false;
			$this->setLastException($oException);
		}

		return $bResult;		
	}	
	
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

	protected function createEntity(\AEntity &$oEntity)
	{
		$mResult = $this->oStorage->createEntity($oEntity->sModuleName, $oEntity->sClassName);
		if (!$mResult)
		{
			throw new CApiManagerException(Errs::Main_UnknownError);
		}
		else if (0 < count($oEntity->getAttributes()))
		{
			$oEntity->iId = $mResult;
			$aEntityAttributes = $oEntity->getAttributes();
			if (0 < count($aEntityAttributes))
			{
				$aProperties = array();
				foreach ($aEntityAttributes as $sKey => $mValue)
				{
					$aProperties[] = new \CAttribute($sKey, $oEntity->{$sKey}, $oEntity->getAttributeType($sKey), $oEntity->isEncryptedAttribute($sKey));
				}
				$this->setAttributes($mResult, $aProperties);
			}
		}
		
		return $mResult;
	}
	
	protected function updateEntity(\AEntity $oEntity)
	{
		$mResult = false;
		$aEntityAttributes = $oEntity->getAttributes();
		if (0 < count($aEntityAttributes))
		{
			$aAttributes = array();
			foreach ($aEntityAttributes as $sKey => $mValue)
			{
				$aAttributes[] = new \CAttribute($sKey, $oEntity->{$sKey}, $oEntity->getAttributeType($sKey), $oEntity->isEncryptedAttribute($sKey));
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
	
	public function deleteEntity($iId)
	{
		$bResult = true;
		try
		{
			$bResult = $this->oStorage->deleteEntity($iId);
		}
		catch (CApiBaseException $oException)
		{
			$this->setLastException($oException);
		}

		return $bResult;
	}
	
	public function getTypes()
	{
		$aTypes = null;
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

	public function getEntitiesCount($sType, $aSearchAttributes = array())
	{
		$iCount = 0;
		try
		{
			$iCount = $this->oStorage->getEntitiesCount($sType, $aSearchAttributes);
		}
		catch (CApiBaseException $oException)
		{
			$this->setLastException($oException);
		}
		return $iCount;		
	}
	
	public function getEntities($sType, $aViewAttributes = array(), $iOffset = 0, $iLimit = 0, $aSearchAttributes = array(), $sOrderBy = '', $iSortOrder = \ESortOrder::ASC)
	{
		$aEntities = null;
		try
		{
			$aEntities = $this->oStorage->getEntities($sType, $aViewAttributes, $iOffset, $iLimit, $aSearchAttributes, $sOrderBy, $iSortOrder);
		}
		catch (CApiBaseException $oException)
		{
			$this->setLastException($oException);
		}
		return $aEntities;		
	}
	
	public function getEntitiesByModule($sModule)
	{
		// TODO:
		return false;
	}
	
	public  function geEntitiesByModuleAndType($sModule, $sType)
	{
		// TODO:
		return false;
	}
	
	public function getEntityById($iId)
	{
		$oEntity = null;
		try
		{
			$oEntity = $this->oStorage->getEntityById($iId);
		}
		catch (CApiBaseException $oException)
		{
			$this->setLastException($oException);
		}
		return $oEntity;
	}
	
	/**
	 * @param int|array $mEntityId
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
	 * @return bool
	 */
	public function deleteAttribute(CAttribute $oAttribute)
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
	 * @return bool
	 */
	public function deleteAttributes($iEntityId)
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
}
