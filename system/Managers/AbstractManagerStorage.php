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

namespace Aurora\System\Managers;

/**
 * @package Api
 */
abstract class AbstractManagerStorage
{
	/**
	 * @var string
	 */
	protected $sManagerName;

	/**
	 * @var string
	 */
	protected $sStorageName;
	
	/**
	 * @var \Aurora\System\Managers\AbstractManager
	 */
	protected $oManager;

	/**
	 * @var \Aurora\System\Settings
	 */
	protected $oSettings;

	/**
	 * @var \Aurora\System\Exceptions\BaseException
	 */
	protected $oLastException;

	public function __construct($sManagerName, $sStorageName, \Aurora\System\Managers\AbstractManager &$oManager)
	{
		$this->sManagerName = $sManagerName;
		$this->sStorageName = $sStorageName;
		$this->oManager = $oManager;
		$this->oSettings =& \Aurora\System\Api::GetSettings();
		$this->oLastException = null;
	}

	/**
	 * @return string
	 */
	public function GetManagerName()
	{
		return $this->sManagerName;
	}

	/**
	 * @return string
	 */
	public function GetStorageName()
	{
		return $this->sStorageName;
	}

	/**
	 * @return &\Aurora\System\Settings
	 */
	public function &GetSettings()
	{
		return $this->oSettings;
	}

	/**
	 * @return \Aurora\System\Exceptions\BaseException
	 */
	public function GetStorageException()
	{
		return $this->oLastException;
	}

	/**
	 * @param \Aurora\System\Exceptions\BaseException $oException
	 */
	public function SetStorageException($oException)
	{
		$this->oLastException = $oException;
	}

	/**
	 * @todo move to db storage
	 */
	protected function throwDbExceptionIfExist()
	{
		// connection in db storage
		if ($this->oConnection)
		{
			$oException = $this->oConnection->GetException();
			if ($oException instanceof \Aurora\System\Exceptions\DbException)
			{
				throw new \Aurora\System\Exceptions\BaseException(\Aurora\System\Exceptions\Errs::Db_ExceptionError, $oException);
			}
		}
	}
	
	public function getPath()
	{
		return $this->oManager->getPath().'/storages/'.$this->GetStorageName();
	}

	/**
	 * @param string $sFileName
	 * @return void
	 */
	public function inc($sFileName)
	{
		static $aCache = array();
		
		$sFileFullPath = '';
		$sFileName = preg_replace('/[^a-z0-9\._\-]/', '', strtolower($sFileName));
		$sFileName = preg_replace('/[\.]+/', '.', $sFileName);
		$sFileName = str_replace('.', '/', $sFileName);
		if (isset($aCache[$sFileName]))
		{
			return true;
		}
		else
		{
			$oModule = $this->oManager->GetModule();
			if (isset($oModule))
			{
				$sFileFullPath = $this->getPath().'/'.$sFileName.'.php';
				if (@file_exists($sFileFullPath))
				{
					$aCache[$sFileName] = true;
					include_once $sFileFullPath;
					return true;
				}
				
			}
			else
			{
				return\Aurora\System\Api::StorageInc(ucfirst($this->GetManagerName()), $this->GetStorageName(), $sFileName);
			}
		}

		exit('FILE NOT EXISTS = '.$sFileFullPath.' File: '.__FILE__.' Line: '.__LINE__.' Method: '.__METHOD__);
		
		return false;		
	}
	
	/**
	 * Executes queries from sql file.
	 * 
	 * @param string $sFilePath Path to sql file.
	 * 
	 * @return boolean
	 */
	public function executeSqlFile($sFilePath)
	{
		$bResult = false;
		
		$sDbPrefix = $this->oCommandCreator->prefix();
		
		$mFileContent = file_exists($sFilePath) ? file_get_contents($sFilePath) : false;

		if ($mFileContent && $this->oConnection)
		{
			$aSqlStrings = explode(';', $mFileContent);
			foreach ($aSqlStrings as $sSql)
			{
				$sPrepSql = trim(str_replace('%PREFIX%', $sDbPrefix, $sSql));
				if (!empty($sPrepSql))
				{
					$bResult = $this->oConnection->Execute($sPrepSql);
				}
				$this->throwDbExceptionIfExist();
			}
		}

		return $bResult;
	}
}
