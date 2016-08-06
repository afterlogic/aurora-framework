<?php

/**
 * @package Api
 */
class CApiModuleManager
{
    /**
     * This array contains a list of modules
     *
     * @var array
     */
	protected $_aModules = array();
	
	/**
     * This array contains a list of callbacks we should call when certain events are triggered
     *
     * @var array
     */
    protected $_aEventSubscriptions = array();
	
    /**
     * @var array
     */    
	protected $_aObjects = array();
	
	/**
	 * @var array
	 */
	private $_aTemplates;
	
	public function __construct()
	{
	}
	
	public static function createInstance()
	{
		return new self();
	}
	
	public function init()
	{
		$sModulesPath = $this->GetModulesPath();
		
		$oCoreModule = $this->loadModule('Core', $sModulesPath);
		if ($oCoreModule !== false)
		{
			$oCoreModule->initialize();
			$sTenant = $oCoreModule->GetTenantName();
			$aModulePath = array(
				$sModulesPath
			);
			if (!empty(trim($sTenant)))
			{
				$sTenantModulesPath = $this->GetTenantModulesPath($sTenant);
				array_unshift($aModulePath, $sTenantModulesPath);
			}
			$aModulesPath = array();
			foreach ($aModulePath as $sModulesPath)
			{
				if (@is_dir($sModulesPath))
				{
					if (false !== ($rDirHandle = @opendir($sModulesPath)))
					{
						while (false !== ($sFileItem = @readdir($rDirHandle)))
						{
							if (0 < strlen($sFileItem) && '.' !== $sFileItem{0} && preg_match('/^[a-zA-Z0-9\-]+$/', $sFileItem))
							{
								$aModulesPath[$sModulesPath][] = $sFileItem;
							}
						}

						@closedir($rDirHandle);
					}
				}
			}
			foreach ($aModulesPath as $sModulePath => $aModulePath)
			{
				foreach ($aModulePath as $sModuleName)
				{
					$this->loadModule($sModuleName, $sModulesPath);
				}
			}
			foreach ($this->_aModules as $oModule)
			{
				if ($oModule instanceof \AApiModule)
				{
					$oModule->initialize();
				}
			}
		}
		else
		{
			echo 'Can\'t load \'Core\' Module';
			return '';
		}
	}
	
	public function isModuleLoaded($sModuleName)
	{
		return array_key_exists(strtolower($sModuleName), $this->_aModules);
	}

	public function getModuleConfig($sModuleName, $sConfigName, $sDefaultValue = null)
	{
		$mResult = $sDefaultValue;
		$oModule = $this->GetModule($sModuleName);
		if ($oModule)
		{
			$mResult = $oModule->getConfig($sConfigName, $sDefaultValue);
		}
		
		return $mResult;
	}

	public function loadModule($sModuleName, $sModulePath)
	{
		$mResult = false;
		
		$this->broadcastEvent($sModuleName, 'loadModule' . \AApiModule::$Delimiter . 'before', array(&$sModuleName, &$sModulePath));
		
		$sModuleFilePath = $sModulePath.$sModuleName.'/module.php';
		if (@file_exists($sModuleFilePath) && !$this->isModuleLoaded($sModuleName))
		{
		   include_once $sModuleFilePath;
		   $sModuleClassName = $sModuleName . 'Module';
		   if (class_exists($sModuleClassName))
		   {
			   $oModule = call_user_func(array($sModuleClassName, 'createInstance'), $sModuleName, $sModulePath);
			   if ($oModule instanceof \AApiModule)
			   {
				   $oModule->loadModuleConfig();
				   if (!$oModule->getConfig('Disabled', false))
				    {
						$this->_aModules[strtolower($sModuleName)] = $oModule;
						$mResult = $oModule;
					}
			   }
		   }
		}

		$this->broadcastEvent($sModuleName, 'loadModule' . \AApiModule::$Delimiter . 'after', array(&$mResult));
		
		return $mResult;
	}

    /**
     * Subscribe to an event.
     *
     * When the event is triggered, we'll call all the specified callbacks.
     * It is possible to control the order of the callbacks through the
     * priority argument.
     *
     * This is for example used to make sure that the authentication plugin
     * is triggered before anything else. If it's not needed to change this
     * number, it is recommended to ommit.
     *
     * @param string $sEvent
     * @param callback $fCallback
     * @param int $iPriority
     * @return void
     */
    public function subscribeEvent($sEvent, $fCallback, $iPriority = 100) 
	{
        if (!isset($this->_aEventSubscriptions[$sEvent])) {
            $this->_aEventSubscriptions[$sEvent] = array();
        }
        while(isset($this->_aEventSubscriptions[$sEvent][$iPriority]))	{
			$iPriority++;
		}
        $this->_aEventSubscriptions[$sEvent][$iPriority] = $fCallback;
        ksort($this->_aEventSubscriptions[$sEvent]);
    }	
	
    /**
     * Broadcasts an event
     *
     * This method will call all subscribers. If one of the subscribers returns false, the process stops.
     *
     * The arguments parameter will be sent to all subscribers
     *
     * @param string $sEvent
     * @param array $aArguments
     * @return bool
     */
    public function broadcastEvent($sModule, $sEvent, $aArguments = array()) 
	{
        \CApi::Log('broadcastEvent: ' . $sModule . \AApiModule::$Delimiter . $sEvent);
		
		$bResult = true;
		$aEventSubscriptions = array();
		if (isset($this->_aEventSubscriptions[$sModule . \AApiModule::$Delimiter . $sEvent])) {
			$aEventSubscriptions = array_merge($aEventSubscriptions, $this->_aEventSubscriptions[$sModule . \AApiModule::$Delimiter . $sEvent]);
		}
		if (isset($this->_aEventSubscriptions[$sEvent])) {
			$aEventSubscriptions = array_merge($aEventSubscriptions, $this->_aEventSubscriptions[$sEvent]);
        }
		
		foreach($aEventSubscriptions as $fCallback) {
			if (is_callable($fCallback))
			{
				\CApi::Log('Execute subscription: '. $fCallback[0]->GetName() . \AApiModule::$Delimiter . $fCallback[1]);
				$result = call_user_func_array($fCallback, $aArguments);
				if ($result === false) {
					$bResult = false;
					break;
				}
			}
		}

        return $bResult;
    }	
	
	/**
	 * @param string $sParsedTemplateID
	 * @param string $sParsedPlace
	 * @param string $sTemplateFileName
	 */
	public function includeTemplate($sParsedTemplateID, $sParsedPlace, $sTemplateFileName)
	{
		if (!isset($this->_aTemplates[$sParsedTemplateID]))
		{
			$this->_aTemplates[$sParsedTemplateID] = array();
		}

		$this->_aTemplates[$sParsedTemplateID][] = array(
			$sParsedPlace, $sTemplateFileName
		);
	}	
	
	/**
	 * @return string
	 */
	public function ParseTemplate($sTemplateID, $sTemplateSource)
	{
		if (isset($this->_aTemplates[$sTemplateID]) && is_array($this->_aTemplates[$sTemplateID]))
		{
			foreach ($this->_aTemplates[$sTemplateID] as $aItem)
			{
				if (!empty($aItem[0]) && !empty($aItem[1]) && file_exists($aItem[1]))
				{
					$sTemplateSource = str_replace('{%INCLUDE-START/'.$aItem[0].'/INCLUDE-END%}',
						file_get_contents($aItem[1]).'{%INCLUDE-START/'.$aItem[0].'/INCLUDE-END%}', $sTemplateSource);
				}
			}
		}

		return $sTemplateSource;
	}	
	
	public function setObjectMap()
	{
		
	}

	/**
	 * @return string
	 */
	public function GetModulesPath()
	{
		return PSEVEN_APP_ROOT_PATH.'modules/';
	}

	/**
	 * @return string
	 */
	public function GetTenantModulesPath($sTenant)
	{
		return PSEVEN_APP_ROOT_PATH.'tenants/' . $sTenant . '/modules/';
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
		$sModuleName = strtolower($sModuleName);
		return (isset($this->_aModules[$sModuleName]) &&  $this->_aModules[$sModuleName] instanceof \AApiModule) ? $this->_aModules[$sModuleName] : false;
	}
	
	
	/**
	 * @return \AApiModule
	 */
	public function GetModuleFromRequest()
	{
		$sModule = '';
		$oHttp = \MailSo\Base\Http::NewInstance();
		if ($oHttp->IsPost()) 
		{
			$sModule = $oHttp->GetPost('Module', null);
		} 
		else 
		{
			$aPath = \System\Service::GetPaths();
			$sModule = (isset($aPath[1])) ? $aPath[1] : '';
		}
			
		return $this->GetModule($sModule);
	}
	
	/**
	 * @return array
	 */
	public function GetModulesByEntry($sEntryName)
	{
		$aModules = array();
		$oResult = $this->GetModuleFromRequest();
		
		if ($oResult && !$oResult->HasEntry($sEntryName)) 
		{
			$oResult = false;
		}
		if ($oResult === false) 
		{
			foreach ($this->_aModules as $oModule) 
			{
				if ($oModule instanceof AApiModule && $oModule->HasEntry($sEntryName)) 
				{
					$aModules[] = $oModule;
				}
			}
		}
		else
		{
			$aModules = array(
				$oResult
			);
		}
		
		return $aModules;
	}
	
	/**
	 * @param string $sModuleName
	 * @return bool
	 */
	public function ModuleExists($sModuleName)
	{
		return ($this->GetModule($sModuleName)) ? true  : false;
	}
	
	public function RunEntry($sEntryName)
	{
		$mResult = false;
		$oModule = $this->GetModuleFromRequest();
		if ($oModule instanceof \AApiModule && $oModule->HasEntry($sEntryName)) 
		{
			$mResult = $oModule->RunEntry($sEntryName);
		}
		
		return $mResult;
	}

	public function ExecuteMethod($sModuleName, $sMethod, $aParameters = array())
	{
		$mResult = false;
		$oModule = $this->GetModule($sModuleName);
		if ($oModule instanceof AApiModule) 
		{
			
			$mResult = $oModule->ExecuteMethod($sMethod, $aParameters);
		}
		
		return $mResult;
	}

	/**
	 * @return string
	 */
	public function Hash()
	{
		$sResult = md5(CApi::Version());
		foreach ($this->_aModules as $oModule) {
			
			$sResult = md5($sResult.$oModule->GetPath().$oModule->GetName().$oModule->GetHash());
		}

		return $sResult;
	}
}

/**
 * @package Api
 */
class CApiModuleDecorator
{
    protected $oModule;

    public function __construct($sModuleName) 
	{
       $this->oModule = \CApi::GetModule($sModuleName);
    }	
	
	public function __call($sMethodName, $aArguments) 
	{
		$mResult = false;
		if ($this->oModule instanceof AApiModule)
		{
			$mResult = $this->oModule->ExecuteMethod($sMethodName, $aArguments);
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
	protected $aEntries = array();	

	/**
	 * @var array
	 */
	protected $aParameters;

	/**
	 * @var array
	 */
	protected $aObjects = array();	

	/**
	 * @var \CApiCapabilityManager
	 */
	public $oApiCapabilityManager = null;
	
	/**
	 * @var \MailSo\Base\Http
	 */
	protected $oHttp;	
	
	/**
	 * @var array
	 */
	protected $aConfig;
	
    /**
     *
     * @var CApiBasicSettings
     */
	protected $oModuleSettings = null;	
	
    /**
     *
     * @var array
     */
	protected $aSettingsMap = array();		
	
    /**
     *
     * @var string
     */
	public static $Delimiter = '::';
	
    /**
     *
     * @var bool
     */
	protected $bInitialized = false;
	
    /**
     *
     * @var array
     */	
	protected $aRequireModules = array();
	
    /**
     *
     * @var array
     */
	protected $aNonAuthorizedMethods = array();
	
	
	/**
	 * @param string $sVersion
	 */
	public function __construct($sName, $sPath, $sVersion = '1.0')
	{
		$this->sVersion = (string) $sVersion;

		$this->sName = $sName;
		$this->sPath = $sPath.$sName;
		$this->aParameters = array();
		$this->oApiCapabilityManager = \CApi::GetSystemManager('capability');
		$this->oHttp = \MailSo\Base\Http::NewInstance();
		
		$this->aEntries = array(
			'api' => 'EntryApi',
			'upload' => 'EntryUpload',
			'download' => 'EntryDownload'
		);
	}
	
	public static function createInstance($sName, $sPath, $sVersion = '1.0')
	{
		return new static($sName, $sPath, $sVersion);
	}	

	public function isInitialized()
	{
		return (bool) $this->bInitialized;
	}

	public function initialize()
	{
		$mResult = true;
		if (!$this->isInitialized())
		{
			foreach ($this->aRequireModules as $sModule)
			{
				$mResult = false;
				$oModule = \CApi::GetModule($sModule);
				if ($oModule)
				{
					if (!$oModule->isInitialized())
					{
						$mResult = $oModule->initialize();
					}
					else 
					{
						$mResult = true;
					}
				}
				if (!$mResult)
				{
					break;
				}
			}
			if ($mResult)
			{
				$this->bInitialized = true;
				$this->init();
			}
		}
		
		return $mResult;
	}
	
	public function init() {}
	
	protected function setNonAuthorizedMethods($aMethods)
	{
		$this->aNonAuthorizedMethods = $aMethods;
	}

	public function loadModuleConfig()
	{
		$this->oModuleSettings = new \CApiBasicSettings(
				\CApi::DataPath() . '/settings/modules/' . $this->sName . '.config.json', 
				$this->aSettingsMap
		);
	}	

	/**
	 * Saves module settings to config.json file.
	 */
	public function saveModuleConfig()
	{
		if (isset($this->oModuleSettings))
		{
			$this->oModuleSettings->Save();
		}
	}	
	
	public function getConfig($sName, $sDefaultValue = null)
	{
		$mResult = $sDefaultValue;
		if (isset($this->oModuleSettings))
		{
			$mResult = $this->oModuleSettings->GetConf($sName, $sDefaultValue);
		}
		
		return $mResult;
	}
	
	/**
	 * Sets new value of module setting.
	 * 
	 * @param string $sName Name of module setting.
	 * @param string $sValue New value of module setting.
	 * 
	 * @return boolean
	 */
	public function setConfig($sName, $sValue = null)
	{
		$bResult = false;
		
		if (isset($this->oModuleSettings))
		{
			$bResult = $this->oModuleSettings->SetConf($sName, $sValue);
		}
		
		return $bResult;
	}	
	
	public function subscribeEvent($sEvent, $fCallback, $iPriority = 100)
	{
		\CApi::GetModuleManager()->subscribeEvent($sEvent, $fCallback, $iPriority);
	}

	public function broadcastEvent($sEvent, $aArguments = array())
	{
		\CApi::GetModuleManager()->broadcastEvent($this->GetName(), $sEvent, $aArguments);
	}
	
	/**
	 * @param string $sParsedTemplateID
	 * @param string $sParsedPlace
	 * @param string $sTemplateFileName
	 */
	public function includeTemplate($sParsedTemplateID, $sParsedPlace, $sTemplateFileName)
	{
		if (0 < strlen($sParsedTemplateID) && 0 < strlen($sParsedPlace) && file_exists($this->GetPath().'/'.$sTemplateFileName))
		{
			\CApi::GetModuleManager()->includeTemplate($sParsedTemplateID, $sParsedPlace, $this->GetPath().'/'.$sTemplateFileName);
		}
	}	
	
	public function setObjectMap($sType, $aMap)
	{
		$aResultMap = array();
		foreach ($aMap as $sKey => $aValue)
		{
			$aResultMap[$this->GetName() . \AApiModule::$Delimiter . $sKey] = $aValue;
		}
		$this->aObjects[$sType] = $aResultMap;
	}	
	
	public function getObjectMap($sType)
	{
		return isset($this->aObjects[$sType]) ? $this->aObjects[$sType] : array();
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
	
	public function GetManager($sManagerName = '', $sForcedStorage = 'db')
	{
		$mResult = false;
		$sFileFullPath = '';
		if (!isset($this->aManagersCache[$sManagerName]))
		{
			$sFileFullPath = $this->GetPath().'/managers/'.$sManagerName.'/manager.php';
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
	
	public function AddEntry($sName, $mCallbak)
	{
		if (!isset($this->aEntries[$sName]))
		{
			$this->aEntries[$sName] = $mCallbak;
		}
	}
	
	public function AddEntries($aEntries)
	{
		foreach ($aEntries as $sName => $mCallbak)
		{
			$this->AddEntry($sName, $mCallbak);
		}
	}
	
	public function HasEntry($sName)
	{
		return isset($this->aEntries[$sName]);
	}
	
	public function GetEntry($sName)
	{
		$mResult = false;
		if (isset($this->aEntries[$sName])) 
		{
			$mResult = $this->aEntries[$sName];
		}
		
		return $mResult;
	}	
	
	public function RunEntry($sName)
	{
		$mResult = false;
		$mMethod = $this->GetEntry($sName);
		
		if ($mMethod) 
		{
			$mResult = $this->ExecuteMethod($mMethod, array(), true);
		}			
		
		return $mResult;
	}

	public function EntryApi()
	{
		@ob_start();

		$aResponseItem = null;
		$sModule = $this->oHttp->GetPost('Module', null);

		if (strtolower($sModule) === strtolower($this->GetName())) {
			
			$sMethod = $this->oHttp->GetPost('Method', null);
			$sParameters = $this->oHttp->GetPost('Parameters', null);
			try
			{
				\CApi::Log('API:');
				\CApi::Log('Module: '. $sModule);
				\CApi::Log('Method: '. $sMethod);

				if (strtolower($sModule) !== 'core' && strtolower($sMethod) !== 'SystemGetAppData' &&
					\CApi::GetConf('labs.webmail.csrftoken-protection', true) && !\System\Service::validateToken()) {
					
					throw new \System\Exceptions\ClientException(\System\Notifications::InvalidToken);
				} else if (!empty($sModule) && !empty($sMethod)) {
					
					$aParameters = isset($sParameters) &&  is_string($sParameters) ? @json_decode($sParameters, true) : array();
					$sAuthToken = $this->oHttp->GetPost('AuthToken', '');
					
					if (!$this->CheckNonAuthorizedMethodAllowed($sMethod))
					{
						if (!\CApi::getAuthenticatedUserId($sAuthToken))
						{
							throw new \System\Exceptions\ClientException(\System\Notifications::UnknownError);
						}
					}
					
					$sTenantName = $this->oHttp->GetPost('TenantName', '');
					
					\CApi::setTenantName($sTenantName);
					
					if (!is_array($aParameters))
					{
						$aParameters = array($aParameters);
					}
					$mResult = $this->ExecuteMethod($sMethod, $aParameters, true);

					$aResponseItem = $this->DefaultResponse($sMethod, $mResult);
				}

				if (!is_array($aResponseItem)) {
					
					throw new \System\Exceptions\ClientException(\System\Notifications::UnknownError);
				}
			}
			catch (\Exception $oException)
			{
				//if ($oException instanceof \System\Exceptions\ClientException &&
				//	\System\Notifications::AuthError === $oException->getCode())
				//{
				//	$oApiIntegrator = /* @var $oApiIntegrator \CApiIntegratorManager */ \CApi::GetCoreManager('integrator');
				//	$oApiIntegrator->setLastErrorCode(\System\Notifications::AuthError);
				//	$oApiIntegrator->logoutAccount();
				//}

				\CApi::LogException($oException);

				$aAdditionalParams = null;
				if ($oException instanceof \System\Exceptions\ClientException) {
					
					$aAdditionalParams = $oException->GetObjectParams();
				}

				$aResponseItem = $this->ExceptionResponse($sMethod, $oException, $aAdditionalParams);
			}

			@header('Content-Type: application/json; charset=utf-8');

			\CApi::Plugin()->RunHook('api.response-result', array($sMethod, &$aResponseItem));
		}

		return \MailSo\Base\Utils::Php2js($aResponseItem, \CApi::MailSoLogger());		
	}

	public function EntryUpload()
	{
		@ob_start();
		$aResponseItem = null;
		$sModule = $this->oHttp->GetPost('Module', null);
		$sMethod = $this->oHttp->GetPost('Method', null);
		$sParameters = $this->oHttp->GetPost('Parameters', null);
		try
		{
			if (!empty($sModule) && !empty($sMethod))
			{
				$aParameters = isset($sParameters) ? @json_decode($sParameters, true) : array();
				$sError = '';
				$sInputName = 'jua-uploader';

				$iError = UPLOAD_ERR_OK;
				$_FILES = isset($_FILES) ? $_FILES : null;
				if (isset($_FILES, $_FILES[$sInputName], $_FILES[$sInputName]['name'], $_FILES[$sInputName]['tmp_name'], $_FILES[$sInputName]['size'], $_FILES[$sInputName]['type']))
				{
					$iError = (isset($_FILES[$sInputName]['error'])) ? (int) $_FILES[$sInputName]['error'] : UPLOAD_ERR_OK;
					if (UPLOAD_ERR_OK === $iError)
					{
						$aParameters = array_merge($aParameters, 
							array(
								'FileData' => $_FILES[$sInputName],
								'IsExt' => '1' === (string) $this->oHttp->GetPost('IsExt', '0') ? '1' : '0',
								'TenantName' => (string) $this->oHttp->GetPost('TenantName', ''),
								'Token' => $this->oHttp->GetPost('Token', ''),
								'AuthToken' => $this->oHttp->GetPost('AuthToken', '')
							)
						);


						$aResponseItem = $this->ExecuteMethod($sMethod, $aParameters, true);
					}
					else
					{
						$sError = 'unknown';
//								$sError = $this->oActions->convertUploadErrorToString($iError);
					}
				}
				else if (!isset($_FILES) || !is_array($_FILES) || 0 === count($_FILES))
				{
					$sError = 'size';
				}
				else
				{
					$sError = 'unknown';
				}
			}					

			if (!is_array($aResponseItem) && empty($sError))
			{
				throw new \System\Exceptions\ClientException(\System\Notifications::UnknownError);
			}
		}
		catch (\Exception $oException)
		{
			\CApi::LogException($oException);
			$aResponseItem = $this->ExceptionResponse($sMethod, $oException);
			$sError = 'exception';
		}

		if (0 < strlen($sError))
		{
			$aResponseItem['Error'] = $sError;
		}
		else 
		{
			$aResponseItem = $this->DefaultResponse($sMethod, $aResponseItem);
		}

		@ob_get_clean();
		@header('Content-Type: text/html; charset=utf-8');
//				if ('iframe' === $this->oHttp->GetPost('jua-post-type', ''))
//				{
//					@header('Content-Type: text/html; charset=utf-8');
//				}
//				else
//				{
//					@header('Content-Type: application/json; charset=utf-8');
//				}

		return \MailSo\Base\Utils::Php2js($aResponseItem);		
	}

	public function EntryDownload()
	{
		$mResult = false;
		
		$aPaths = \System\Service::GetPaths();
		$sMethod = empty($aPaths[2]) ? '' : $aPaths[2];

		try
		{
			if (!empty($sMethod)) {
				
				$sRawKey = empty($aPaths[3]) ? '' : $aPaths[3];
				$aParameters = CApi::DecodeKeyValues($sRawKey);				
				$aParameters['AuthToken'] = empty($aPaths[4]) ? '' : $aPaths[4];

				$mResult = $this->ExecuteMethod($sMethod, $aParameters, true);
			}
		}
		catch (\Exception $oException)
		{
			\CApi::LogException($oException);
			$this->oHttp->StatusHeader(404);
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
		if (isset($aCache[$sFileName])) {
			return true;
		} else {
			$sFileFullPath = $this->GetPath().'/managers/'.$sFileName.'.php';
			if (@file_exists($sFileFullPath)) {
				$aCache[$sFileName] = true;
				include_once $sFileFullPath;
				return true;
			}
		}

		if ($bDoExitOnError) {
			exit('FILE NOT EXISTS = '.$sFileFullPath.' File: '.__FILE__.' Line: '.__LINE__.' Method: '.__METHOD__.'<br />');
		}
		
		return false;
	}	
	
	/**
	 * @param string $sFileName
	 * @return void
	 */
	public function incClass($sFileName, $bDoExitOnError = true)
	{
		static $aCache = array();

		$sFileFullPath = '';
//		$sFileName = preg_replace('/[^a-z0-9\._\-]/', '', strtolower($sFileName));
//		$sFileName = preg_replace('/[\.]+/', '.', $sFileName);
//		$sFileName = str_replace('.', '/', $sFileName);
		if (isset($aCache[$sFileName]))
		{
			return true;
		}
		else
		{
			$sFileFullPath = $this->GetPath().'/classes/'.$sFileName.'.php';
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
	
	public function incClasses($aFileNames, $bDoExitOnError = true)
	{
		if (is_array($aFileNames))
		{
			foreach($aFileNames as $sFileName)
			{
				$this->incClass($sFileName, $bDoExitOnError);
			}
		}
	}

	/**
	 * @param string $sMethod
	 * @param mixed $mResult = false
	 *
	 * @return array
	 */
	public function DefaultResponse($sMethod, $mResult = false)
	{
		return array(
			'Module' => $this->GetName(),
			'Method' => $sMethod,
			'Result' => \CApiResponseManager::GetResponseObject($mResult, array(
				'Module' => $this->GetName(),
				'Method' => $sMethod,
				'Parameters' => $this->aParameters
			)),
			'@Time' => microtime(true) - PSEVEN_APP_START
		);
	}	
	
	/**
	 * @param string $sMethod
	 *
	 * @return array
	 */
	public function TrueResponse($sMethod)
	{
		return $this->DefaultResponse($sMethod, true);
	}

	/**
	 * @param string $sMethod
	 * @param int $iErrorCode
	 * @param string $sErrorMessage
	 * @param array $aAdditionalParams = null
	 *
	 * @return array
	 */
	public function FalseResponse($sMethod, $iErrorCode = null, $sErrorMessage = null, $aAdditionalParams = null)
	{
		$aResponseItem = $this->DefaultResponse($sMethod, false);

		if (null !== $iErrorCode) {
			
			$aResponseItem['ErrorCode'] = (int) $iErrorCode;
			if (null !== $sErrorMessage) {
				
				$aResponseItem['ErrorMessage'] = null === $sErrorMessage ? '' : (string) $sErrorMessage;
			}
		}

		if (is_array($aAdditionalParams)) {
			
			foreach ($aAdditionalParams as $sKey => $mValue) {
				$aResponseItem[$sKey] = $mValue;
			}
		}

		return $aResponseItem;
	}	
	
	/**
	 * @param string $sActionName
	 * @param \Exception $oException
	 * @param array $aAdditionalParams = null
	 *
	 * @return array
	 */
	public function ExceptionResponse($sActionName, $oException, $aAdditionalParams = null)
	{
		$iErrorCode = null;
		$sErrorMessage = null;

		$bShowError = \CApi::GetConf('labs.webmail.display-server-error-information', false);

		if ($oException instanceof \System\Exceptions\ClientException) {
			$iErrorCode = $oException->getCode();
			$sErrorMessage = null;
			if ($bShowError) {
				$sErrorMessage = $oException->getMessage();
				if (empty($sErrorMessage) || 'ClientException' === $sErrorMessage) {
					$sErrorMessage = null;
				}
			}
		}
		else if ($bShowError && $oException instanceof \MailSo\Imap\Exceptions\ResponseException) {
			$iErrorCode = \System\Notifications::MailServerError;
			
			$oResponse = /* @var $oResponse \MailSo\Imap\Response */ $oException->GetLastResponse();
			if ($oResponse instanceof \MailSo\Imap\Response) {
				$sErrorMessage = $oResponse instanceof \MailSo\Imap\Response ?
					$oResponse->Tag.' '.$oResponse->StatusOrIndex.' '.$oResponse->HumanReadable : null;
			}
		} else {
			$iErrorCode = \System\Notifications::UnknownError;
//			$sErrorMessage = $oException->getCode().' - '.$oException->getMessage();
		}

		return $this->FalseResponse($sActionName, $iErrorCode, $sErrorMessage, $aAdditionalParams);
	}	
	
	public function ExecuteMethod($sMethodName, $aArguments = array(), $bReflection = false)
	{
		$mResult = false;
		if (method_exists($this, $sMethodName))
		{
			$this->broadcastEvent($sMethodName . \AApiModule::$Delimiter . 'before', array(&$aArguments));

			$aValues = array();

			if ($bReflection)
			{
				$oReflector = new \ReflectionMethod($this, $sMethodName);
				foreach ($oReflector->getParameters() as $oParam) 
				{
					$sParamName = $oParam->getName();
					$bIsArgumentGiven = array_key_exists($sParamName, $aArguments);
					if (!$bIsArgumentGiven && !$oParam->isDefaultValueAvailable()) 
					{
						$aValues[$oParam->getPosition()] = null;
					}
					else
					{
						$aValues[$oParam->getPosition()] = $bIsArgumentGiven ? 
							$aArguments[$sParamName] : $oParam->getDefaultValue();
					}		
				}
			}
			else
			{
				$aValues = $aArguments;
			}
			
			$mResult = call_user_func_array(array($this, $sMethodName), $aValues);

			$aArguments['@Result'] = $mResult;
			$this->broadcastEvent($sMethodName . \AApiModule::$Delimiter . 'after', array(&$aArguments));
			$mResult = $aArguments['@Result'];
		}
				
		return $mResult;
	}
	
	public function CheckNonAuthorizedMethodAllowed($sMethodName = '')
	{
		return !!in_array($sMethodName, $this->aNonAuthorizedMethods);
	}
	
	public function GetAppData($oUser = null)
	{
		return null;
	}
	
	/**
	 * @param string $sData
	 * @param array $aParams = null
	 *
	 * @return string
	 */
	public function i18N($sData, $iUserId = null, $aParams = null, $iPluralCount = null)
	{
		
		// TODO:
		$sLanguage = /*$oAccount ? $oAccount->User->DefaultLanguage :*/ '';
		
		if (empty($sLanguage)) {
			$oSettings =& \CApi::GetSettings();
			$sLanguage = $oSettings->GetConf('DefaultLanguage');
		}

		$aLang = null;
		if (isset(\CApi::$aClientI18N[$this->GetName()][$sLanguage])) {
			$aLang = \CApi::$aClientI18N[$this->GetName()][$sLanguage];
		} else {
			\CApi::$aClientI18N[$this->GetName()][$sLanguage] = false;
				
			$sLangFile = $this->GetPath().'/i18n/'.$sLanguage.'.ini';
			if (!@file_exists($sLangFile)) {
				$sLangFile = $this->GetPath().'/i18n/English.ini';
				$sLangFile = @file_exists($sLangFile) ? $sLangFile : '';
			}

			if (0 < strlen($sLangFile)) {
				$aLang = \CApi::convertIniToLang($sLangFile);
				if (is_array($aLang)) {
					\CApi::$aClientI18N[$this->GetName()][$sLanguage] = $aLang;
				}
			}
		}

		//return self::processTranslateParams($aLang, $sData, $aParams);
		return isset($iPluralCount) ? \CApi::processTranslateParams($aLang, $sData, $aParams, \CApi::getPlural($sLanguage, $iPluralCount)) : 
			\CApi::processTranslateParams($aLang, $sData, $aParams);
	}
	
	public function setDisabledForEntity(&$oEntity)
	{
		$oEavManager = \CApi::GetSystemManager('eav');
		if ($oEavManager)
		{
			$sDisabledModules = isset($oEntity->{'@DisabledModules'}) ? $oEntity->{'@DisabledModules'} : '';
			$aDisabledModules =  !empty(trim($sDisabledModules)) ? array($sDisabledModules) : array();
			if($i = substr_count($sDisabledModules, "|"))
			{
				$aDisabledModules = explode("|", $sDisabledModules);
			}
				
			if (!in_array($this->GetName(), $aDisabledModules))
			{
				$aDisabledModules[] = $this->GetName();
			}
			$sDisabledModules = implode('|', $aDisabledModules);
			$oEntity->{'@DisabledModules'} = $sDisabledModules;
			$oEavManager->setAttributes(
					array($oEntity->iId), 
					array(new \CAttribute('@DisabledModules', $sDisabledModules, 'string')));
		}	
	}
	
	public function setEnabledForEntity(&$oEntity)
	{
		$oEavManager = \CApi::GetSystemManager('eav');
		if ($oEavManager)
		{
			$sDisabledModules = isset($oEntity->{'@DisabledModules'}) ? $oEntity->{'@DisabledModules'} : '';
			$aDisabledModules =  !empty(trim($sDisabledModules)) ? array($sDisabledModules) : array();
			if($i = substr_count($sDisabledModules, "|"))
			{
				$aDisabledModules = explode("|", $sDisabledModules);
			}

			if (in_array($this->GetName(), $aDisabledModules))
			{
				$aDisabledModules = array_diff($aDisabledModules, array($this->GetName()));
			}
			$sDisabledModules = implode('|', $aDisabledModules);
			$oEntity->{'@DisabledModules'} = $sDisabledModules;
			$oEavManager->setAttributes(
					array($oEntity->iId), 
					array(new \CAttribute('@DisabledModules', implode('|', $aDisabledModules), 'string')));
		}	
	}
}

