<?php

/* -AFTERLOGIC LICENSE HEADER- */

/**
 * @package Api
 */
class CApiGlobalManager
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
			'mailsuite' => 'db',
			'min' => 'db',
			'fetchers' => 'db',
			'helpdesk' => 'db',
			'subscriptions' => 'db',
			'db' => 'db',
			'domains' => 'db',
			'tenants' => 'db',
			'channels' => 'db',
			'users' => 'db',
			'webmail' => 'db',
			'mail' => 'db',
			'gcontacts' => 'db',
			'contactsmain' => 'db',
			'filecache' => 'file',
			'calendar' => 'sabredav',
			'filestorage' => 'sabredav',
			'social' => 'db',
            'twofactorauth' => 'db'
		);

		if (CApi::GetConf('gcontacts.ldap', false))
		{
			$this->aStorageMap['gcontacts'] = 'ldap';
		}

		if (CApi::GetConf('contacts.ldap', false))
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
			CApi::Inc('common.settings');
			try
			{
				$this->oSettings = new CApiSettings(CApi::DataPath() . '/settings/');
			}
			catch (CApiBaseException $oException)
			{
				$this->oSettings = false;
			}
		}

		return $this->oSettings;
	}

	public function PrepareStorageMap()
	{
		CApi::Plugin()->RunHook('api-prepare-storage-map', array(&$this->aStorageMap));
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
				$this->oConnection = new CDbStorage($oSettings);
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
				$this->oSqlHelper = CDbCreator::CreateCommandCreatorHelper($oSettings);
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
		CApi::Inc('common.net.protocols.imap4');
		return new CApiImap4MailProtocol($sHost, $iPort, $bUseSsl);
	}

	public function &GetCommandCreator(AApiManagerStorage &$oStorage, $aCommandCreatorsNames)
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
				CApi::Inc('common.db.command_creator');
				CApi::StorageInc($oStorage->GetManagerName(), $oStorage->GetStorageName(), 'command_creator');

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
		if (CApi::IsValid())
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
					CApi::Inc('managers.'.$sManagerType.'.manager', false);
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
class CApiGlobalManagerException extends CApiBaseException {}

/**
 * @package Api
 */
abstract class AApiManager
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
	 * @var CApiGlobalManager
	 */
	protected $oManager;

	/**
	 * @var AApiModule
	 */
	protected $oModule;	
	
	/**
	 * @var CApiSettings
	 */
	protected $oSettings;

	public function __construct($sManagerName, CApiGlobalManager &$oManager, AApiModule $oModule = null)
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
	 * @return AApiModule
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
		CApi::ManagerInc($this->GetManagerName(), $sInclude, $bDoExitOnError);
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
	
	public function &GetCommandCreator(AApiManagerStorage &$oStorage, $aCommandCreatorsNames)
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
				CApi::Inc('common.db.command_creator');
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
		return CApi::ManagerPath($this->GetManagerName(), $sInclude);
	}

	/**
	 * @param Exception $oException
	 * @param bool $bLog = true
	 */
	protected function setLastException(Exception $oException, $bLog = true)
	{
		$this->oLastException = $oException;

		if ($bLog)
		{
			$sFile = str_replace(
				str_replace('\\', '/', strtolower(realpath(CApi::WebMailPath()))), '~ ',
				str_replace('\\', '/', strtolower($oException->getFile())));

			CApi::Log('Exception['.$oException->getCode().']: '.$oException->getMessage().
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
abstract class AApiManagerWithStorage extends AApiManager
{
	/**
	 * @var string
	 */
	protected $sStorageName;

	/**
	 * @var AApiManagerStorage
	 */
	protected $oStorage;

	/**
	 * @param string $sManagerName
	 * @param CApiGlobalManager &$oManager
	 * @param string $sForcedStorage
	 * @return AApiManager
	 */
	public function __construct($sManagerName, CApiGlobalManager &$oManager, $sForcedStorage = '', AApiModule $oModule = null)
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
			CApi::Inc('managers.'.$this->GetManagerName().'.storages.default');

			if (CApi::Inc('managers.'.$this->GetManagerName().'.storages.'.$this->GetStorageName().'.storage', false))
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
	 * @return AApiManagerStorage
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

class CApiCoreManagerWithStorage extends AApiManagerWithStorage
{
	
}

/**
 * @package Api
 */
class CApiManagerException extends CApiBaseException {}

/**
 * @package Api
 */
abstract class AApiManagerStorage
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
	 * @var string
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

	public function __construct($sManagerName, $sStorageName, AApiManager &$oManager)
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
				throw new CApiBaseException(Errs::Db_ExceptionError, $oException);
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
				return CApi::StorageInc($this->GetManagerName(), $this->GetStorageName(), $sFileName);
			}
		}

		if ($bDoExitOnError)
		{
			exit('FILE NOT EXISTS = '.$sFileFullPath.' File: '.__FILE__.' Line: '.__LINE__.' Method: '.__METHOD__);
		}
		
		return false;		
	}
}

/**
 * @package Api
 */
class CApiStorageException extends CApiBaseException {}
