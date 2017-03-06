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

/**
 * @package Api
 */

namespace Aurora\System;

/**
 * @package Api
 */
abstract class AbstractManager
{
	/**
	 * @var \Aurora\System\Exceptions\ManagerException
	 */
	protected $oLastException;

	/**
	 * @var string
	 */
	protected $sManagerName;

	/**
	 * @var \Aurora\System\GlobalManager
	 */
	protected $oManager;

	/**
	 * @var \Aurora\System\Module\AbstractModule
	 */
	protected $oModule;	
	
	/**
	 * @var CApiSettings
	 */
	protected $oSettings;

	public function __construct($sManagerName, GlobalManager &$oManager, \Aurora\System\Module\AbstractModule $oModule = null)
	{
		$this->sManagerName = strtolower($sManagerName);
		$this->oSettings =& $oManager->GetSettings();
		$this->oManager =& $oManager;
		$this->oLastException = null;
		$this->oModule = $oModule;
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
		return '';
	}

	/**
	 * @return \Aurora\System\Module\AbstractModule
	 */
	public function GetModule()
	{
		return $this->oModule;
	}
	
	/**
	 * @return &CApiSettings
	 */
	public function GetGlobalManager()
	{
		return $this->oManager;
	}

	/**
	 * @return &CApiSettings
	 */
	public function &GetSettings()
	{
		return $this->oSettings;
	}

	/**
	 * @param string $sInclude
	 * @return void
	 */
	protected function inc($sInclude, $bDoExitOnError = true)
	{
		\Aurora\System\Api::ManagerInc(ucfirst($this->GetManagerName()), $sInclude, $bDoExitOnError);
	}

	/**
	 * @param string $sFileName
	 * @return void
	 */
	public function incClass($sFileName, $bDoExitOnError = true)
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
			$sFileFullPath = $this->oModule->GetPath().'/managers/'.ucfirst($this->GetManagerName()).'/classes/'.$sFileName.'.php';
			if (@file_exists($sFileFullPath))
			{
				$aCache[$sFileName] = true;
				include_once $sFileFullPath;
				return true;
			}
		}

		if ($bDoExitOnError)
		{
			exit('FILE NOT EXISTS = '.$sFileFullPath.' File: '.__FILE__.' Line: '.__LINE__.' Method: '.__METHOD__);
		}
		
		return false;			
	
	}

	/**
	 * @param string $sInclude
	 * @return bool
	 */
	public function incDefaultStorage()
	{
		return $this->incStorage('default');
	}
	
	/**
	 * @return string
	 */
	public function getPath()
	{
		return $this->oModule->GetPath().'/managers/'.ucfirst($this->GetManagerName());
	}	
	
	/**
	 * @param string $sInclude
	 * @return bool
	 */
	public function incStorage($sFileName, $bDoExitOnError = true)
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
			$sFileFullPath = $this->oModule->GetPath().'/managers/'.ucfirst($this->GetManagerName()).'/storages/'.$sFileName.'.php';
			if (@file_exists($sFileFullPath))
			{
				$aCache[$sFileName] = true;
				include_once $sFileFullPath;
				return true;
			}
		}

		if ($bDoExitOnError)
		{
			exit('FILE NOT EXISTS = '.$sFileFullPath.' File: '.__FILE__.' Line: '.__LINE__.' Method: '.__METHOD__);
		}
		
		return false;		
	}
	
	public function &GetConnection()
	{
		return $this->oManager->GetConnection();
	}
	
	public function &GetCommandCreator(\Aurora\System\AbstractManagerStorage &$oStorage, $aCommandCreatorsNames)
	{
		$oSettings =& $oStorage->GetSettings();
		$oCommandCreatorHelper =& $this->oManager->GetSqlHelper();

		$oCommandCreator = null;

		if ($oSettings)
		{
			$sDbType = $oSettings->GetConf('DBType');
			$sDbPrefix = $oSettings->GetConf('DBPrefix');

			if (isset($aCommandCreatorsNames[$sDbType]))
			{
				\Aurora\System\Api::Inc('db.command_creator');
				$oStorage->inc('command_creator');
//				$this->incStorage('db.command_creator');

				$oCommandCreator =
					new $aCommandCreatorsNames[$sDbType]($oCommandCreatorHelper, $sDbPrefix);
			}
		}

		return $oCommandCreator;
	}	

	/**
	 * @param string $sInclude
	 * @return string
	 */
	public function path($sInclude)
	{
		return\Aurora\System\Api::ManagerPath(ucfirst($this->GetManagerName()), $sInclude);
	}

	/**
	 * @param Exception $oException
	 * @param bool $bLog = true
	 */
	protected function setLastException(\Exception $oException, $bLog = true)
	{
		$this->oLastException = $oException;

		if ($bLog)
		{
			$sFile = str_replace(
				str_replace('\\', '/', strtolower(realpath(\Aurora\System\Api::WebMailPath()))), '~ ',
				str_replace('\\', '/', strtolower($oException->getFile())));

			\Aurora\System\Api::Log('Exception['.$oException->getCode().']: '.$oException->getMessage().
				API_CRLF.$sFile.' ('.$oException->getLine().')'.
				API_CRLF.'----------------------------------------------------------------------'.
				API_CRLF.$oException->getTraceAsString(), ELogLevel::Error);
		}
	}

	/**
	 * @return Exception
	 */
	public function GetLastException()
	{
		return $this->oLastException;
	}

	/**
	 * @return int
	 */
	public function getLastErrorCode()
	{
		$iResult = 0;
		if (null !== $this->oLastException)
		{
			$iResult = $this->oLastException->getCode();
		}
		return $iResult;
	}

	/**
	 * @return string
	 */
	public function GetLastErrorMessage()
	{
		$sResult = '';
		if (null !== $this->oLastException)
		{
			$sResult = $this->oLastException->getMessage();
		}
		return $sResult;
	}
}
