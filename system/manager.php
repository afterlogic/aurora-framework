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
 * @package Api
 */

namespace Aurora\System;

class GlobalManager
{
	/**
	 * @var CApiSettings
	 */
	protected $oSettings;

	/**
	 * @var CDbStorage
	 */
	protected $oConnection;

	/**
	 * @var IDbHelper
	 */
	protected $oSqlHelper;

	/**
	 * @var array
	 */
	protected $aManagers;

	/**
	 * @var array
	 */
	protected $aStorageMap;

	public function __construct()
	{
		$this->oSettings = null;
		$this->oConnection = null;
		$this->oSqlHelper = null;
		$this->aManagers = array();
		$this->aStorageMap = array(
			'db' => 'db',
			'filecache' => 'file'
		);

		if (\Aurora\System\Api::GetConf('gcontacts.ldap', false))
		{
			$this->aStorageMap['gcontacts'] = 'ldap';
		}

		if (\Aurora\System\Api::GetConf('contacts.ldap', false))
		{
			$this->aStorageMap['contactsmain'] = 'ldap';
		}
	}

	/**
	 * @return CApiSettings
	 */
	public function &GetSettings()
	{
		if (null === $this->oSettings)
		{
			\Aurora\System\Api::Inc('settings');
			try
			{
				$sSettingsPath = \Aurora\System\Api::DataPath() . '/settings/';
				if (!file_exists($sSettingsPath))
				{
					mkdir(dirname($sSettingsPath), 0777);
				}
				
				$this->oSettings = new SystemSettings($sSettingsPath . 'config.json');
			}
			catch (BaseException $oException)
			{
				$this->oSettings = false;
			}
		}

		return $this->oSettings;
	}

	/**
	 * @param string $sManagerName
	 * @return string
	 */
	public function GetStorageByType($sManagerName)
	{
		$sManagerName = strtolower($sManagerName);
		return isset($this->aStorageMap[$sManagerName]) ? $this->aStorageMap[$sManagerName] : '';
	}

	/**
	 * @return CDbStorage
	 */
	public function &GetConnection()
	{
		if (null === $this->oConnection)
		{
			$oSettings =& $this->GetSettings();

			if ($oSettings)
			{
				$this->oConnection = new Db\Storage($oSettings);
			}
			else
			{
				$this->oConnection = false;
			}
		}

		return $this->oConnection;
	}

	/**
	 * @return CDbStorage
	 */
	public function &GetSqlHelper()
	{
		if (null === $this->oSqlHelper)
		{
			$oSettings =& $this->GetSettings();

			if ($oSettings)
			{
				$this->oSqlHelper = Db\Creator::CreateCommandCreatorHelper($oSettings);
			}
			else
			{
				$this->oSqlHelper = false;
			}
		}

		return $this->oSqlHelper;
	}

	/**
	 * @param bool $iMailProtocol
	 * @return CApiImap4MailProtocol
	 */
	public function GetSimpleMailProtocol($sHost, $iPort, $bUseSsl = false)
	{
		\Aurora\System\Api::Inc('net.protocols.imap4');
		return new \CApiImap4MailProtocol($sHost, $iPort, $bUseSsl);
	}

	public function &GetCommandCreator(\Aurora\System\AbstractManagerStorage &$oStorage, $aCommandCreatorsNames)
	{
		$oSettings =& $oStorage->GetSettings();
		$oCommandCreatorHelper =& $this->GetSqlHelper();

		$oCommandCreator = null;

		if ($oSettings)
		{
			$sDbType = $oSettings->GetConf('DBType');
			$sDbPrefix = $oSettings->GetConf('DBPrefix');

			if (isset($aCommandCreatorsNames[$sDbType]))
			{
				\Aurora\System\Api::Inc('db.command_creator');
				\Aurora\System\Api::StorageInc($oStorage->GetManagerName(), $oStorage->GetStorageName(), 'command_creator');

				$oCommandCreator =
					new $aCommandCreatorsNames[$sDbType]($oCommandCreatorHelper, $sDbPrefix);
			}
		}

		return $oCommandCreator;
	}

	/**
	 * @param string $sManagerType
	 * @param string $sForcedStorage = ''
	 */
	public function GetByType($sManagerType, $sForcedStorage = '')
	{
		$oResult = null;
		if (\Aurora\System\Api::IsValid())
		{
			$sManagerKey = empty($sForcedStorage) ? $sManagerType : $sManagerType.'/'.$sForcedStorage;
			if (isset($this->aManagers[$sManagerKey]))
			{
				$oResult =& $this->aManagers[$sManagerKey];
			}
			else
			{
				$sManagerType = strtolower($sManagerType);
				$sClassName = 'CApi'.ucfirst($sManagerType).'Manager';
				if (!class_exists($sClassName))
				{
					\Aurora\System\Api::Inc('managers.'.$sManagerType.'.manager', false);
				}
				if (class_exists($sClassName))
				{
					$oMan = new $sClassName($this, $sForcedStorage);
					$sCurrentStorageName = $oMan->GetStorageName();

					$sManagerKey = empty($sCurrentStorageName) ? $sManagerType : $sManagerType.'/'.$sCurrentStorageName;
					$this->aManagers[$sManagerKey] = $oMan;
					$oResult =& $this->aManagers[$sManagerKey];
				}
			}
		}

		return $oResult;
	}
}

/**
 * @package Api
 */
class GlobalManagerException extends BaseException {}

/**
 * @package Api
 */
abstract class AbstractManager
{
	/**
	 * @var CApiManagerException
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
	 * @var \Aurora\System\AbstractModule
	 */
	protected $oModule;	
	
	/**
	 * @var CApiSettings
	 */
	protected $oSettings;

	public function __construct($sManagerName, GlobalManager &$oManager, \Aurora\System\AbstractModule $oModule = null)
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
	 * @return \Aurora\System\AbstractModule
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
		\Aurora\System\Api::ManagerInc($this->GetManagerName(), $sInclude, $bDoExitOnError);
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
			$sFileFullPath = $this->oModule->GetPath().'/managers/'.$this->GetManagerName().'/classes/'.$sFileName.'.php';
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
		return $this->oModule->GetPath().'/managers/'.$this->GetManagerName();
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
			$sFileFullPath = $this->oModule->GetPath().'/managers/'.$this->GetManagerName().'/storages/'.$sFileName.'.php';
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
		return\Aurora\System\Api::ManagerPath($this->GetManagerName(), $sInclude);
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

/**
 * @package Api
 */
abstract class AbstractManagerWithStorage extends AbstractManager
{
	/**
	 * @var string
	 */
	protected $sStorageName;

	/**
	 * @var \Aurora\System\AbstractManagerStorage
	 */
	protected $oStorage;

	/**
	 * @param string $sManagerName
	 * @param \Aurora\System\GlobalManager &$oManager
	 * @param string $sForcedStorage
	 * @return \Aurora\System\AbstractManager
	 */
	public function __construct($sManagerName, \Aurora\System\GlobalManager &$oManager, $sForcedStorage = '', \Aurora\System\AbstractModule $oModule = null)
	{
		parent::__construct($sManagerName, $oManager, $oModule);

		$this->oStorage = null;
		$this->sStorageName = !empty($sForcedStorage)
			? strtolower(trim($sForcedStorage)) : strtolower($oManager->GetStorageByType($sManagerName));

		if (isset($this->oModule))
		{
			$this->incDefaultStorage();

			if ($this->incStorage($this->GetStorageName().'.storage', false))
			{
				$sClassName = 'CApi'.ucfirst($oModule->GetName()).ucfirst($this->GetManagerName()).ucfirst($this->GetStorageName()).'Storage';
				$this->oStorage = new $sClassName($this);
			}
			else
			{
				$sClassName = 'CApi'.ucfirst($oModule->GetName()).ucfirst($this->GetManagerName()).'Storage';
				$this->oStorage = new $sClassName($this->sStorageName, $this);
			}
		}
		else
		{
			\Aurora\System\Api::Inc('managers.'.$this->GetManagerName().'.storages.default');

			if (\Aurora\System\Api::Inc('managers.'.$this->GetManagerName().'.storages.'.$this->GetStorageName().'.storage', false))
			{
				$sClassName = 'CApi'.ucfirst($this->GetManagerName()).ucfirst($this->GetStorageName()).'Storage';
				$this->oStorage = new $sClassName($this);
			}
			else
			{
				$sClassName = 'CApi'.ucfirst($this->GetManagerName()).'Storage';
				if (class_exists($sClassName))
				{
					$this->oStorage = new $sClassName($this->sStorageName, $this);
				}
			}
		}
	}

	/**
	 * @return string
	 */
	public function GetStorageName()
	{
		return $this->sStorageName;
	}

	/**
	 * @return \Aurora\System\AbstractManagerStorage
	 */
	public function &GetStorage()
	{
		return $this->oStorage;
	}

	public function moveStorageExceptionToManager()
	{
		if ($this->oStorage)
		{
			$oException = $this->oStorage->GetStorageException();
			if ($oException)
			{
				$this->oLastException = $oException;
			}
		}
	}
}

class CoreManagerWithStorage extends AbstractManagerWithStorage
{
	
}

/**
 * @package Api
 */
class ManagerException extends BaseException {}

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
	 * @var \Aurora\System\AbstractManager
	 */
	protected $oManager;

	/**
	 * @var CApiSettings
	 */
	protected $oSettings;

	/**
	 * @var CApiBaseException
	 */
	protected $oLastException;

	public function __construct($sManagerName, $sStorageName, \Aurora\System\AbstractManager &$oManager)
	{
		$this->sManagerName = strtolower($sManagerName);
		$this->sStorageName = strtolower($sStorageName);
		$this->oManager = $oManager;
		$this->oSettings =& $oManager->GetGlobalManager()->GetSettings();
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
	 * @return &CApiSettings
	 */
	public function &GetSettings()
	{
		return $this->oSettings;
	}

	/**
	 * @return CApiBaseException
	 */
	public function GetStorageException()
	{
		return $this->oLastException;
	}

	/**
	 * @param CApiBaseException $oException
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
			if ($oException instanceof CApiDbException)
			{
				throw new \CApiBaseException(Errs::Db_ExceptionError, $oException);
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
				return\Aurora\System\Api::StorageInc($this->GetManagerName(), $this->GetStorageName(), $sFileName);
			}
		}

		if ($bDoExitOnError)
		{
			exit('FILE NOT EXISTS = '.$sFileFullPath.' File: '.__FILE__.' Line: '.__LINE__.' Method: '.__METHOD__);
		}
		
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
		$bResult = true;
		
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
		else
		{
			$bResult = false;
		}

		return $bResult;
	}
}

/**
 * @package Api
 */
class StorageException extends BaseException {}
