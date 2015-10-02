<?php

/**
 * @package Api
 */
class CApiModuleManager
{
	public function __construct()
	{
		$sModulesPath = $this->GetModulesPath();
		if (@is_dir($sModulesPath))
		{
			if (false !== ($rDirHandle = @opendir($sModulesPath)))
			{
				while (false !== ($sFileItem = @readdir($rDirHandle)))
				{
					if (0 < strlen($sFileItem) && '.' !== $sFileItem{0} && preg_match('/^[a-zA-Z0-9\-]+$/', $sFileItem) &&
						@file_exists($sModulesPath.$sFileItem.'/index.php'))
					{
						$oModule = include_once $sModulesPath.$sFileItem.'/index.php';
						if ($oModule instanceof AApiModule)
						{
							$oModule->SetName($sFileItem);
							$oModule->SetPath($sModulesPath.$sFileItem);
							$oModule->init();
							$this->_aModules[$sFileItem] = $oModule;
						}
					}
				}

				@closedir($rDirHandle);
			}
		}
	}

	/**
	 * @return string
	 */
	public function GetModulesPath()
	{
		return PSEVEN_APP_ROOT_PATH.'modules/';
	}

	/**
	 * @return array
	 */
	public function GetModules()
	{
		return $this->_aModules;
	}
	
	/**
	 * @param string $sModuleName
	 * @return AApiModule
	 */
	public function GetModule($sModuleName)
	{
		return (isset($this->_aModules[$sModuleName]) &&  $this->_aModules[$sModuleName] instanceof AApiModule) ? $this->_aModules[$sModuleName] : false;
	}
	
	/**
	 * @param string $sModuleName
	 * @return bool
	 */
	public function ModuleExists($sModuleName)
	{
		return ($this->GetModule($sModuleName)) ? true  : false;
	}
	
	public function ExecuteMethod($sModuleName, $sMethodName, $aArguments)
	{
		$mResult = false;
		$oModule = $this->GetModule($sModuleName);
		if ($oModule)
		{
			$mResult = $oModule->ExecuteMethod($sMethodName, $aArguments);
		}
		
		return $mResult;
	}

	/**
	 * @return string
	 */
	public function Hash()
	{
		$sResult = md5(CApi::Version());
		foreach ($this->_aModules as $oModule)
		{
			$sResult = md5($sResult.$oModule->GetPath().$oModule->GetName().$oModule->GetHash());
		}

		return $sResult;
	}
}

/**
 * @package Api
 */
class CApiModuleMethod
{
	/**
	 * @var object
	 */
	protected $oClass;

	/**
	 * @var string
	 */
	protected $sMethodName;

	/**
	 * @var array
	 */
	protected $aParameters;
	
	/**
	 * @param string $sVersion
	 */
	public function __construct($oClass, $sMethodName, $aParameters = array())
	{
		$this->oClass = $oClass;
		$this->sMethodName = $sMethodName;
		$this->aParameters = $aParameters;
	}
	
	public static function createInstance($oClass, $sMethodName, $aParameters = array())
	{
		return new CApiModuleMethod($oClass, $sMethodName, $aParameters);
	}
	
	public function Exists()
	{
		return method_exists($this->oClass, $this->sMethodName);
	}	
	
	public function Execute()
	{
		$mResult = false;
		if ($this->Exists())
		{
			$mResult = call_user_func(array($this->oClass, $this->sMethodName));
		}
		return $mResult;
	}
}

/**
 * @package Api
 */
abstract class AApiModule
{
	/**
	 * @var string
	 */
	protected $sName;

	/**
	 * @var string
	 */
	protected $sPath;

	/**
	 * @var string
	 */
	protected $sVersion;
	
	/**
	 * @var array
	 */
	protected $aManagersCache = array();	

	/**
	 * @var array
	 */
	protected $aParameters;

	/**
	 * @param string $sVersion
	 */
	public function __construct($sVersion)
	{
		$this->sVersion = (string) $sVersion;

		$this->sName = '';
		$this->sPath = '';
		$this->aParameters = array();
	}

	public function init()
	{
	}

	/**
	 * @param string $sName
	 */
	final public function SetName($sName)
	{
		$this->sName = $sName;
	}

	/**
	 * @param string $sPath
	 */
	final public function SetPath($sPath)
	{
		$this->sPath = $sPath;
	}

	/**
	 * @param array $aParameters
	 */
	final public function SetParameters($aParameters)
	{
		$this->aParameters = $aParameters;
	}

	/**
	 * @return string
	 */
	public function GetHash()
	{
		return '';
	}

	/**
	 * @return string
	 */
	public function GetName()
	{
		return $this->sName;
	}

	/**
	 * @return string
	 */
	public function GetPath()
	{
		return $this->sPath;
	}

	/**
	 * @return string
	 */
	public function GetVersion()
	{
		return $this->sVersion;
	}

	/**
	 * @return string
	 */
	public function GetFullName()
	{
		return $this->sName.'-'.$this->sVersion;
	}
	
	public function GetManagerPath($sManagerName)
	{
		return $this->GetPath().'/managers/'.$sManagerName.'/manager.php';
	}

	public function GetManager($sManagerName, $sForcedStorage = '')
	{
		$mResult = false;
		$sFileFullPath = '';
		if (!isset($this->aManagersCache[$sManagerName]))
		{
			$sFileFullPath = $this->GetManagerPath($sManagerName);
			if (@file_exists($sFileFullPath))
			{
				if (include_once $sFileFullPath)
				{
					$this->aManagersCache[$sManagerName] = true;
				}
			}
		}
		if (isset($this->aManagersCache[$sManagerName]))
		{
			$sClassName = 'CApi'.ucfirst($this->GetName()).ucfirst($sManagerName).'Manager';
			if (class_exists($sClassName))
			{
				$mResult = new $sClassName(\CApi::$oManager, $sForcedStorage, $this);
			}
		}
		
		return $mResult;
	}
	
	/**
	 * @param string $sFileName
	 * @param bool $bDoExitOnError = true
	 * @return bool
	 */
	public function Inc($sFileName, $bDoExitOnError = true)
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
			$sFileFullPath = $this->GetPath().'/managers/'.$sFileName.'.php';
			if (@file_exists($sFileFullPath))
			{
				$aCache[$sFileName] = true;
				include_once $sFileFullPath;
				return true;
			}
		}

		if ($bDoExitOnError)
		{
			exit('FILE NOT EXISTS = '.$sFileFullPath.' File: '.__FILE__.' Line: '.__LINE__.' Method: '.__METHOD__.'<br />');
		}
		
		return false;
	}	

	public function MethodExists($sMethod)
	{
		return method_exists($this, $sMethod);
	}
	
	public function getParamValue($sKey, $mDefault = null)
	{
		return is_array($this->aParameters) && isset($this->aParameters[$sKey])
			? $this->aParameters[$sKey] : $mDefault;
	}
	
	/**
	 * @param string $sParamName
	 * @param mixed $oObject
	 *
	 * @return void
	 */
	protected function paramToObject($sParamName, &$oObject, $sType = 'string')
	{
		switch ($sType)
		{
			default:
			case 'string':
				$oObject->{$sParamName} = (string) $this->getParamValue($sParamName, $oObject->{$sParamName});
				break;
			case 'int':
				$oObject->{$sParamName} = (int) $this->getParamValue($sParamName, $oObject->{$sParamName});
				break;
			case 'bool':
				$oObject->{$sParamName} = '1' === (string) $this->getParamValue($sParamName, $oObject->{$sParamName} ? '1' : '0');
				break;
		}
	}
	
	/**
	 * @param mixed $oObject
	 * @param array $aParamsNames
	 */
	protected function paramsStrToObjectHelper(&$oObject, $aParamsNames)
	{
		foreach ($aParamsNames as $sName)
		{
			$this->paramToObject($sName, $oObject);
		}
	}	
	
	/**
	 * @param string $sObjectName
	 *
	 * @return string
	 */
	protected function objectNames($sObjectName)
	{
		$aList = array(
			'CApiMailMessageCollection' => 'MessageCollection',
			'CApiMailMessage' => 'Message',
			'CApiMailFolderCollection' => 'FolderCollection',
			'CApiMailFolder' => 'Folder',
			'Email' => 'Email'
		);

		return !empty($aList[$sObjectName]) ? $aList[$sObjectName] : $sObjectName;
	}
	
	/**
	 * @param \CAccount $oAccount
	 * @param object $oData
	 * @param string $sParent
	 *
	 * @return array | false
	 */
	protected function objectWrapper($oAccount, $oData, $sParent)
	{
		$mResult = false;
		if (is_object($oData))
		{
			$aNames = explode('\\', get_class($oData));
			$sObjectName = end($aNames);

			$mResult = array(
				'@Object' => $this->objectNames($sObjectName)
			);

			if ($oData instanceof \MailSo\Base\Collection)
			{
				$mResult['@Object'] = 'Collection/'.$mResult['@Object'];
				$mResult['@Count'] = $oData->Count();
				$mResult['@Collection'] = $this->responseObject($oAccount, $oData->CloneAsArray(), $sParent, $aParameters);
			}
			else
			{
				$mResult['@Object'] = 'Object/'.$mResult['@Object'];
			}
		}

		return $mResult;
	}
		
	/**
	 * @param \CAccount $oAccount
	 * @param mixed $mResponse
	 * @param string $sParent
	 *
	 * @return mixed
	 */
	protected function responseObject($oAccount, $mResponse, $sParent)
	{
		$mResult = $mResponse;

		if (is_object($mResult))
		{
			if (method_exists($mResult, 'toArray'))	
			{
				$mResult = array_merge($this->objectWrapper($oAccount, $mResponse, $sParent), $mResponse->toArray());
			}
		}
		else if (is_array($mResponse))
		{
			foreach ($mResponse as $iKey => $oItem)
			{
				$mResponse[$iKey] = $this->responseObject($oAccount, $oItem, $sParent);
			}

			$mResult = $mResponse;
		}

		unset($mResponse);
		return $mResult;
	}
	
	/**
	 * @param \CAccount $oAccount
	 * @param string $sMethod
	 * @param mixed $mResult = false
	 *
	 * @return array
	 */
	public function DefaultResponse($oAccount, $sMethod, $mResult = false)
	{
		$aResult = array(
			'Module' => $this->GetName(),
			'Method' => $sMethod
		);
		if ($oAccount instanceof \CAccount)
		{
			$aResult['AccountID'] = $oAccount->IdAccount;
		}

		$aResult['Result'] = $this->responseObject($oAccount, $mResult, $sMethod);
		$aResult['@Time'] = microtime(true) - PSEVEN_APP_START;
		return $aResult;
	}	
	
	/**
	 * @param \CAccount $oAccount
	 * @param string $sMethod
	 *
	 * @return array
	 */
	public function TrueResponse($oAccount, $sMethod)
	{
		return $this->DefaultResponse($oAccount, $sMethod, true);
	}

	/**
	 * @param \CAccount $oAccount
	 * @param string $sMethod
	 * @param int $iErrorCode
	 * @param string $sErrorMessage
	 * @param array $aAdditionalParams = null
	 *
	 * @return array
	 */
	public function FalseResponse($oAccount, $sMethod, $iErrorCode = null, $sErrorMessage = null, $aAdditionalParams = null)
	{
		$aResponseItem = $this->DefaultResponse($oAccount, $sMethod, false);

		if (null !== $iErrorCode)
		{
			$aResponseItem['ErrorCode'] = (int) $iErrorCode;
			if (null !== $sErrorMessage)
			{
				$aResponseItem['ErrorMessage'] = null === $sErrorMessage ? '' : (string) $sErrorMessage;
			}
		}

		if (is_array($aAdditionalParams))
		{
			foreach ($aAdditionalParams as $sKey => $mValue)
			{
				$aResponseItem[$sKey] = $mValue;
			}
		}

		return $aResponseItem;
	}	
	
	/**
	 * @param string $sAuthToken = ''
	 * @return \CAccount | null
	 */
	public function GetDefaultAccount($sAuthToken = '')
	{
		$oResult = null;
		$oApiIntegrator = \CApi::GetCoreManager('integrator');

		$iUserId = $oApiIntegrator->getLogginedUserId($sAuthToken);
		if (0 < $iUserId)
		{
			$oApiUsers = \CApi::GetCoreManager('users');
			$iAccountId = $oApiUsers->getDefaultAccountId($iUserId);
			if (0 < $iAccountId)
			{
				$oAccount = $oApiUsers->getAccountById($iAccountId);
				if ($oAccount instanceof \CAccount && !$oAccount->IsDisabled)
				{
					$oResult = $oAccount;
				}
			}
		}

		return $oResult;
	}
	
	/**
	 * @param int $iAccountId
	 * @param bool $bVerifyLogginedUserId = true
	 * @param string $sAuthToken = ''
	 * @return CAccount | null
	 */
	public function getAccount($iAccountId, $bVerifyLogginedUserId = true, $sAuthToken = '')
	{
		$oResult = null;
		$oApiIntegrator = \CApi::GetCoreManager('integrator');
		
		$iUserId = $bVerifyLogginedUserId ? $oApiIntegrator->getLogginedUserId($sAuthToken) : 1;
		if (0 < $iUserId)
		{
			$oApiUsers = \CApi::GetCoreManager('users');
			
			$oAccount = $oApiUsers->getAccountById($iAccountId);
			if ($oAccount instanceof \CAccount && 
				($bVerifyLogginedUserId && $oAccount->IdUser === $iUserId || !$bVerifyLogginedUserId) && !$oAccount->IsDisabled)
			{
				$oResult = $oAccount;
			}
		}

		return $oResult;
	}	
	
	/**
	 * @param bool $bThrowAuthExceptionOnFalse Default value is **true**.
	 *
	 * @return \CAccount|null
	 */
	protected function getDefaultAccountFromParam($bThrowAuthExceptionOnFalse = true)
	{
		$sAuthToken = (string) $this->getParamValue('AuthToken', '');
		$oResult = $this->GetDefaultAccount($sAuthToken);
		if ($bThrowAuthExceptionOnFalse && !($oResult instanceof \CAccount))
		{
			throw new \Core\Exceptions\ClientException(\Core\Notifications::AuthError);
		}

		return $oResult;
	}	
	
	/**
	 * @param bool $bThrowAuthExceptionOnFalse Default value is **true**.
	 * @param bool $bVerifyLogginedUserId Default value is **true**.
	 *
	 * @return \CAccount|null
	 */
	protected function getAccountFromParam($bThrowAuthExceptionOnFalse = true, $bVerifyLogginedUserId = true)
	{
		$oResult = null;
		$sAuthToken = (string) $this->getParamValue('AuthToken', '');
		$sAccountID = (string) $this->getParamValue('AccountID', '');
		if (0 === strlen($sAccountID) || !is_numeric($sAccountID))
		{
			throw new \Core\Exceptions\ClientException(\Core\Notifications::InvalidInputParameter);
		}

		$oResult = $this->getAccount((int) $sAccountID, $bVerifyLogginedUserId, $sAuthToken);

		if ($bThrowAuthExceptionOnFalse && !($oResult instanceof \CAccount))
		{
			$oExc = $this->oApiUsers->GetLastException();
			throw new \Core\Exceptions\ClientException(\Core\Notifications::AuthError,
				$oExc ? $oExc : null, $oExc ? $oExc->getMessage() : '');
		}

		return $oResult;
	}	

	public function ExecuteMethod($sMethod, $aParameters)
	{
		$this->SetParameters($aParameters);
		$mResult = CApiModuleMethod::createInstance($this, $sMethod, $this->aParameters)->Execute();
		$this->aParameters = array();
		return $mResult;
	}
}