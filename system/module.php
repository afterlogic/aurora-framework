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
    protected $_aSubscriptions = array();
	
    /**
     * @var array
     */    
	protected $_aObjects = array();
	
	/**
	 * @var array
	 */
	private $_aTemplates;
	
	/**
	 * @var array
	 */
	private $_aResults;
	
	/**
	 * 
	 * @return \self
	 */
	public static function createInstance()
	{
		return new self();
	}
	
	/**
	 * 
	 * @return string
	 */
	public function init()
	{
		$sModulesPath = $this->GetModulesPath();
		
		$oCoreModule = $this->loadModule('Core', $sModulesPath);
		if ($oCoreModule !== false)
		{
			\CApi::authorise();
			
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
			foreach ($aModulesPath as $aModulePath)
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
	
	/**
	 * 
	 * @param string $sModuleName
	 * @return boolean
	 */
	public function isModuleLoaded($sModuleName)
	{
		return array_key_exists(strtolower($sModuleName), $this->_aModules);
	}

	/**
	 * 
	 * @param string $sModuleName
	 * @param string $sConfigName
	 * @param string $sDefaultValue
	 * @return mixed
	 */
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

	/**
	 * 
	 * @param string $sModuleName
	 * @param string $sModulePath
	 * @return \AApiModule
	 */
	public function loadModule($sModuleName, $sModulePath)
	{
		$mResult = false;
		$aArgs = array($sModuleName, $sModulePath);
		$this->broadcastEvent(
			$sModuleName, 
			'loadModule' . \AApiModule::$Delimiter . 'before', 
			$aArgs
		);
		
		$sModuleFilePath = $sModulePath.$sModuleName.'/module.php';
		if (@file_exists($sModuleFilePath) && !$this->isModuleLoaded($sModuleName))
		{
		   include_once $sModuleFilePath;
		   $sModuleClassName = $sModuleName . 'Module';
		   if (class_exists($sModuleClassName))
		   {
			   $oModule = call_user_func(
					array($sModuleClassName, 'createInstance'), 
					$sModuleName, 
					$sModulePath
				);
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

		$this->broadcastEvent(
			$sModuleName, 
			'loadModule' . \AApiModule::$Delimiter . 'after', 
			$aArgs,
			$mResult
		);
		
		return $mResult;
	}

    /**
	 * 
	 * @return array
	 */
	public function getEvents() 
	{
		return $this->_aSubscriptions;
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
        if (!isset($this->_aSubscriptions[$sEvent])) 
		{
            $this->_aSubscriptions[$sEvent] = array();
        }
        while(isset($this->_aSubscriptions[$sEvent][$iPriority]))	
		{
			$iPriority++;
		}
        $this->_aSubscriptions[$sEvent][$iPriority] = $fCallback;
        ksort($this->_aSubscriptions[$sEvent]);
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
     * @param mixed $mResult
     * @return boolean
     */
    public function broadcastEvent($sModule, $sEvent, &$aArguments = array(), &$mResult = null, &$bCountinue = true) 
	{
		$bResult = false;
		$aSubscriptions = array();
		if (isset($this->_aSubscriptions[$sEvent])) 
		{
			$aSubscriptions = array_merge(
				$aSubscriptions, 
				$this->_aSubscriptions[$sEvent]
			);
        }
		$sEvent = $sModule . \AApiModule::$Delimiter . $sEvent;
		if (isset($this->_aSubscriptions[$sEvent])) 
		{
			$aSubscriptions = array_merge(
				$aSubscriptions, 
				$this->_aSubscriptions[$sEvent]
			);
		}
		
		foreach($aSubscriptions as $fCallback) 
		{
			if (is_callable($fCallback))
			{
				\CApi::Log('Execute subscription: '. $fCallback[0]->GetName() . \AApiModule::$Delimiter . $fCallback[1]);
				$mCallBackResult = call_user_func_array(
					$fCallback, 
					array(
						&$aArguments,
						&$mResult
					)
				);

				\CApi::GetModuleManager()->AddResult(
					$fCallback[0]->GetName(), 
					$sEvent, 
					$mCallBackResult
				);

				if ($mCallBackResult) 
				{
					$bResult = $mCallBackResult;
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
	 * @param string $sModuleName
	 */
	public function includeTemplate($sParsedTemplateID, $sParsedPlace, $sTemplateFileName, $sModuleName = '')
	{
		if (!isset($this->_aTemplates[$sParsedTemplateID]))
		{
			$this->_aTemplates[$sParsedTemplateID] = array();
		}

		$this->_aTemplates[$sParsedTemplateID][] = array(
			$sParsedPlace, 
			$sTemplateFileName, 
			$sModuleName
		);
	}	
	
	/**
	 * 
	 * @param string $sTemplateID
	 * @param string $sTemplateSource
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
					$sTemplateHtml = file_get_contents($aItem[1]);
					if (!empty($aItem[2]))
					{
						$sTemplateHtml = str_replace('%ModuleName%', $aItem[2], $sTemplateHtml);
						$sTemplateHtml = str_replace('%MODULENAME%', strtoupper($aItem[2]), $sTemplateHtml);
					}
					$sTemplateSource = str_replace('{%INCLUDE-START/'.$aItem[0].'/INCLUDE-END%}',
						$sTemplateHtml.'{%INCLUDE-START/'.$aItem[0].'/INCLUDE-END%}', $sTemplateSource);
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
		return AURORA_APP_ROOT_PATH.'modules/';
	}

	/**
	 * @return string
	 */
	public function GetTenantModulesPath($sTenant)
	{
		return AURORA_APP_ROOT_PATH.'tenants/' . $sTenant . '/modules/';
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
		$oHttp = \MailSo\Base\Http::SingletonInstance();
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
	 * 
	 * @param string $sEntryName
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
	
	/**
	 * 
	 * @param string $sEntryName
	 * @return mixed
	 */
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
	
	/**
	 * 
	 * @param string $sModule
	 * @param string $sMethod
	 * @param mixed $mResult
	 */
	public function AddResult($sModule, $sMethod, &$mResult, $iErrorCode = 0)
	{
		$aResult = array(
			'Module' => $sModule,
			'Method' => $sMethod,
			'Result' => $mResult
		);
		
		if ($iErrorCode > 0)
		{
			$aResult['ErrorCode'] = $iErrorCode;
		}
		
		$this->_aResults[] = $aResult;
	}
	
	public function GetResults()
	{
		return $this->_aResults;
	}
	
	public function GetResult($sModule, $sMethod)
	{
		foreach($this->_aResults as $aResult)
		{
			if ($aResult['Module'] === $sModule && $aResult['Method'] === $sMethod)
			{
				return array($aResult);
			}
		}
	}	
}

/**
 * @package Api
 */
class CApiModuleDecorator
{
    /**
	 *
	 * @var \AApiModule
	 */
	protected $oModule;

    /**
	 * 
	 * @param string $sModuleName
	 */
	public function __construct($sModuleName) 
	{
		$this->oModule = \CApi::GetModule($sModuleName);
    }	
	
	/**
	 * 
	 * @param string $sMethodName
	 * @param array $aArguments
	 * @return mixed
	 */
	public function __call($sMethodName, $aArguments) 
	{
		$mResult = false;
		if ($this->oModule instanceof AApiModule)
		{
			$mResult = $this->oModule->CallMethod($sMethodName, $aArguments);
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
	public $oHttp;	
	
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
	 * @param string $sVersion
	 */
	public function __construct($sName, $sPath, $sVersion = '1.0')
	{
		$this->sVersion = (string) $sVersion;

		$this->sName = $sName;
		$this->sPath = $sPath.$sName;
		$this->aParameters = array();
		$this->oApiCapabilityManager = \CApi::GetSystemManager('capability');
		$this->oHttp = \MailSo\Base\Http::SingletonInstance();
		
		$this->aEntries = array(
			'api' => 'EntryApi',
			'download' => 'EntryDownload'
		);
	}
	
	/**
	 * 
	 * @param string $sName
	 * @param string $sPath
	 * @param string $sVersion
	 * @return \AApiModule
	 */
	public static function createInstance($sName, $sPath, $sVersion = '1.0')
	{
		return new static($sName, $sPath, $sVersion);
	}	

	/**
	 * 
	 * @return boolean
	 */
	public function isInitialized()
	{
		return (bool) $this->bInitialized;
	}

	
	/**
	 * 
	 * @return boolean
	 */
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
	
	/**
	 * 
	 */
	public function init() {}
	
	/**
	 * 
	 */
	public function loadModuleConfig()
	{
		$this->oModuleSettings = new \CApiBasicSettings(
			\CApi::DataPath() . '/settings/modules/' . $this->sName . '.config.json', 
			$this->aSettingsMap
		);
	}	

	/**
	 * Saves module settings to config.json file.
	 * 
	 * returns bool
	 */
	public function saveModuleConfig()
	{
		if (isset($this->oModuleSettings))
		{
			return $this->oModuleSettings->Save();
		}
	}	
	
	/**
	 * 
	 * @param string $sName
	 * @param mixed $mDefaultValue
	 * @return mixed
	 */
	public function getConfig($sName, $mDefaultValue = null)
	{
		$mResult = $mDefaultValue;
		if (isset($this->oModuleSettings))
		{
			$mResult = $this->oModuleSettings->GetConf($sName, $mDefaultValue);
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
	
	/**
	 * 
	 * @param string $sMethod
	 * @return boolean
	 */
	protected function isEventCallback($sMethod)
	{
		return in_array($sMethod, $this->getEventsCallbacks());
	}
	
	/**
	 * 
	 * @return array
	 */
	public function getEventsCallbacks()
	{
		$aEventsValues = array();
		$aEvents = \CApi::GetModuleManager()->getEvents();
		foreach(array_values($aEvents) as $aEvent)
		{
			foreach ($aEvent as $aEv)
			{
				if ($aEv[0]->GetName() === $this->GetName())
				{
					$aEventsValues[] = $aEv[1];
				}
			}
		}
		
		return $aEventsValues;
	}

	/**
	 * 
	 * @param string $sEvent
	 * @param callback $fCallback
	 * @param int $iPriority
	 */
	public function subscribeEvent($sEvent, $fCallback, $iPriority = 100)
	{
		\CApi::GetModuleManager()->subscribeEvent($sEvent, $fCallback, $iPriority);
	}

	/**
	 * 
	 * @param string $sEvent
	 * @param array $aArguments
	 */
	public function broadcastEvent($sEvent, &$aArguments = array(), &$mResult = null)
	{
		return \CApi::GetModuleManager()->broadcastEvent(
			$this->GetName(), 
			$sEvent, 
			$aArguments, 
			$mResult
		);
	}
	
	/**
	 * @param string $sParsedTemplateID
	 * @param string $sParsedPlace
	 * @param string $sTemplateFileName
	 * @param string $sModuleName
	 */
	public function includeTemplate($sParsedTemplateID, $sParsedPlace, $sTemplateFileName, $sModuleName = '')
	{
		if (0 < strlen($sParsedTemplateID) && 0 < strlen($sParsedPlace) && file_exists($this->GetPath().'/'.$sTemplateFileName))
		{
			\CApi::GetModuleManager()->includeTemplate(
				$sParsedTemplateID, 
				$sParsedPlace, 
				$this->GetPath().'/'.$sTemplateFileName, 
				$sModuleName
			);
		}
	}	
	
	/**
	 * 
	 * @param string $sType
	 * @param array $aMap
	 */
	public function setObjectMap($sType, $aMap)
	{
		$aResultMap = array();
		foreach ($aMap as $sKey => $aValue)
		{
			$aResultMap[$this->GetName() . \AApiModule::$Delimiter . $sKey] = $aValue;
		}
		$this->aObjects[$sType] = $aResultMap;
	}	
	
	/**
	 * 
	 * @param string $sType
	 * @return array
	 */
	public function getObjectMap($sType)
	{
		return isset($this->aObjects[$sType]) ? $this->aObjects[$sType] : array();
	}
	
	/**
	 * 
	 * @param string $sType
	 * @return boolean
	 */
	public function issetObject($sType)
	{
		return isset($this->aObjects[$sType]);
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
	
	/**
	 * 
	 * @param string $sManagerName
	 * @param string $sForcedStorage
	 * @return \AApiModule
	 */
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
	
	/**
	 * 
	 * @param string $sName
	 * @param callback $mCallbak
	 */
	public function AddEntry($sName, $mCallbak)
	{
		if (!isset($this->aEntries[$sName]))
		{
			$this->aEntries[$sName] = $mCallbak;
		}
	}
	
	/**
	 * 
	 * @param array $aEntries
	 */
	public function AddEntries($aEntries)
	{
		foreach ($aEntries as $sName => $mCallbak)
		{
			$this->AddEntry($sName, $mCallbak);
		}
	}
	
	/**
	 * 
	 * @param string $sName
	 * @return boolean
	 */
	public function HasEntry($sName)
	{
		return isset($this->aEntries[$sName]);
	}
	
	/**
	 * 
	 * @param callback $mCallbak
	 * @return boolean
	 */
	protected function isEntryCallback($mCallbak)
	{
		return in_array($mCallbak, array_values($this->aEntries));
	}

	/**
	 * 
	 * @param stranig $sName
	 * @return mixed
	 */
	public function GetEntryCallback($sName)
	{
		$mResult = false;
		if (isset($this->aEntries[$sName])) 
		{
			$mResult = $this->aEntries[$sName];
		}
		
		return $mResult;
	}	
	
	/**
	 * 
	 * @param string $sName
	 * @return mixed
	 */
	public function RunEntry($sName)
	{
		$mResult = false;
		$mMethod = $this->GetEntryCallback($sName);
		
		if ($mMethod) 
		{
			$mResult = call_user_func_array(
				array($this, $mMethod), 
				array()
			);
			
		}			
		return $mResult;
	}
	
	/**
	 * 
	 * @return mixed
	 */
	private function getUploadData()
	{
		$mResult = false;
		$sError = '';
		$sInputName = 'jua-uploader';

		$iError = UPLOAD_ERR_OK;
		$_FILES = isset($_FILES) ? $_FILES : null;
		if (isset($_FILES, 
			$_FILES[$sInputName], 
			$_FILES[$sInputName]['name'], 
			$_FILES[$sInputName]['tmp_name'], 
			$_FILES[$sInputName]['size'], 
			$_FILES[$sInputName]['type']))
		{
			$iError = (isset($_FILES[$sInputName]['error'])) ? 
					(int) $_FILES[$sInputName]['error'] : UPLOAD_ERR_OK;
			if (UPLOAD_ERR_OK === $iError)
			{
				$mResult = $_FILES[$sInputName];
			}
			else
			{
				$sError = 'unknown';
			}
		}
		
		return $mResult;
	}

	/**
	 * 
	 * @return string
	 * @throws \System\Exceptions\AuroraApiException
	 */
	public function EntryApi()
	{
		@ob_start();

		$aResponseItem = null;
		$sModule = $this->oHttp->GetPost('Module', null);
		$sMethod = $this->oHttp->GetPost('Method', null);
		$sParameters = $this->oHttp->GetPost('Parameters', null);
		$sFormat = $this->oHttp->GetPost('Format', null);

		try
		{
			if (isset($sModule, $sMethod))
			{
				if (strtolower($sModule) === strtolower($this->GetName())) 
				{
					\CApi::Log('API:');
					\CApi::Log('Module: '. $sModule);
					\CApi::Log('Method: '. $sMethod);

					if (strtolower($sModule) !== 'core' && 
						\CApi::GetConf('labs.webmail.csrftoken-protection', true) && !\CApi::validateAuthToken()) 
					{
						throw new \System\Exceptions\AuroraApiException(\System\Notifications::InvalidToken);
					} 
					else if (!empty($sModule) && !empty($sMethod)) 
					{
						$aParameters = isset($sParameters) &&  is_string($sParameters) ? 
							@json_decode($sParameters, true) : array();
						$sTenantName = $this->oHttp->GetPost('TenantName', '');

						\CApi::setTenantName($sTenantName);

						if (!is_array($aParameters))
						{
							$aParameters = array($aParameters);
						}
						$mUploadData = $this->getUploadData();
						if (is_array($mUploadData))
						{
							$aParameters['UploadData'] = $mUploadData;
						}

						$this->CallMethod(
							$sMethod, 
							$aParameters, 
							true
						);
						$aResponseItem = $this->DefaultResponse(
							$sMethod,
							\CApi::GetModuleManager()->GetResults()
						);
					}

					if (!is_array($aResponseItem)) 
					{
						throw new \System\Exceptions\AuroraApiException(\System\Notifications::UnknownError);
					}

					if ($sFormat !== 'Raw')
					{
						@header('Content-Type: application/json; charset=utf-8');
					}
				}
			}
			else
			{
				throw new \System\Exceptions\AuroraApiException(\System\Notifications::InvalidInputParameter);
			}
		}
		catch (\Exception $oException)
		{
			\CApi::LogException($oException);

			$aAdditionalParams = null;
			if ($oException instanceof \System\Exceptions\AuroraApiException) 
			{
				$aAdditionalParams = $oException->GetObjectParams();
			}

			$aResponseItem = $this->ExceptionResponse(
				$sMethod,
				$oException,
				$aAdditionalParams
			);
		}

		return \MailSo\Base\Utils::Php2js($aResponseItem, \CApi::MailSoLogger());		
	}

	/**
	 * 
	 * @return mixed
	 */
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
				$aParameters['SharedHash'] = empty($aPaths[5]) ? '' : $aPaths[5];

				$mResult = $this->CallMethod($sMethod, $aParameters, true);
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
	
	/**
	 * 
	 * @param array $aFileNames
	 * @param boolean $bDoExitOnError
	 */
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
		$aResult = array(
			'AuthenticatedUserId' => \CApi::getAuthenticatedUserId(),
			'@Time' => microtime(true) - AURORA_APP_START
		);
		if (is_array($mResult))
		{
			foreach ($mResult as $aValue)
			{
				$aResponseResult = \CApiResponseManager::GetResponseObject(
					$aValue, 
					array(
						'Module' => $aValue['Module'],
						'Method' => $aValue['Method'],
					)
				);
				if ($aValue['Module'] === $this->GetName() && $aValue['Method'] === $sMethod)
				{
					$aResult = array_merge($aResult, $aResponseResult);
				}
				else
				{
					$aResult['Stack'][] =  $aResponseResult;
				}
			}
		}
		
		return $aResult;
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
	public function FalseResponse($sMethod, $iErrorCode = null, $sErrorMessage = null, $aAdditionalParams = null, $sModule = null)
	{
		$aResponseItem = $this->DefaultResponse($sMethod, false);

		if (null !== $iErrorCode) 
		{
			$aResponseItem['ErrorCode'] = (int) $iErrorCode;
			if (null !== $sErrorMessage) 
			{
				
				$aResponseItem['ErrorMessage'] = null === $sErrorMessage ? '' : (string) $sErrorMessage;
			}
		}

		if (null !== $sModule) 
		{
			$aResponseItem['Module'] = $sModule;
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
		$sModule = '';

		$bShowError = \CApi::GetConf('labs.webmail.display-server-error-information', false);

		if ($oException instanceof \System\Exceptions\AuroraApiException) 
		{
			$iErrorCode = $oException->getCode();
			$sErrorMessage = null;
			if ($bShowError) 
			{
				$sErrorMessage = $oException->getMessage();
				if (empty($sErrorMessage) || 'AuroraApiException' === $sErrorMessage) 
				{
					$sErrorMessage = null;
				}
			}
				$sModule = $this->GetName();
		}
		else if ($bShowError && $oException instanceof \MailSo\Imap\Exceptions\ResponseException) 
		{
			$iErrorCode = \System\Notifications::MailServerError;
			
			$oResponse = /* @var $oResponse \MailSo\Imap\Response */ $oException->GetLastResponse();
			if ($oResponse instanceof \MailSo\Imap\Response) 
			{
				$sErrorMessage = $oResponse instanceof \MailSo\Imap\Response ?
					$oResponse->Tag.' '.$oResponse->StatusOrIndex.' '.$oResponse->HumanReadable : null;
			}
		} 
		else 
		{
			$iErrorCode = \System\Notifications::UnknownError;
//			$sErrorMessage = $oException->getCode().' - '.$oException->getMessage();
		}

		return $this->FalseResponse($sActionName, $iErrorCode, $sErrorMessage, $aAdditionalParams, $sModule);
	}	
	
	/**
	 * 
	 * @param string $sMethodName
	 * @param array $aArguments
	 * @param boolean $bWebApi
	 * @return array
	 */
	protected function getMethodArguments($sMethodName, &$aArguments, $bWebApi)
	{
		$aMethodArgs = array();
		$oReflector = new \ReflectionMethod($this, $sMethodName);
		$aReflectionParameters = $oReflector->getParameters();
		if ($bWebApi)
		{
			foreach ($aReflectionParameters as $oParam) 
			{
				$sParamName = $oParam->getName();
				$iParamPosition = $oParam->getPosition();
				$bIsArgumentGiven = array_key_exists($sParamName, $aArguments);
				if (!$bIsArgumentGiven && !$oParam->isDefaultValueAvailable()) 
				{
					$aMethodArgs[$iParamPosition] = null;
				}
				else
				{
					$aMethodArgs[$iParamPosition] = $bIsArgumentGiven ? 
						$aArguments[$sParamName] : $oParam->getDefaultValue();
				}		
			}
		}
		else
		{
			$aTempArguments = array();
			$aMethodArgs = $aArguments;
			foreach ($aReflectionParameters as $oParam) 
			{
				$sParamName = $oParam->getName();
				$iParamPosition = $oParam->getPosition();
				$mArgumentValue = null;
				if (isset($aArguments[$iParamPosition]))
				{
					$mArgumentValue = $aArguments[$iParamPosition];
				}
				else if ($oParam->isDefaultValueAvailable())
				{
					$mArgumentValue = $oParam->getDefaultValue();
				}
				$aTempArguments[$sParamName] = $mArgumentValue;
			}
			$aArguments = $aTempArguments;
		}
		
		return $aMethodArgs;
	}
	
	/**
	 * 
	 * @param string $sMethod
	 * @return boolean
	 */
	protected function isCallbackMethod($sMethod)
	{
		return ($this->isEntryCallback($sMethod) || $this->isEventCallback($sMethod));
	}
	
	/**
	 * 
	 * @param string $sMethod
	 * @param array $aArguments
	 * @param boolean $bWebApi
	 * @return mixed
	 */
	public function CallMethod($sMethod, $aArguments = array(), $bWebApi = false)
	{
		$mResult = false;
		try 
		{
			if (method_exists($this, $sMethod) &&  !($bWebApi && $this->isCallbackMethod($sMethod)))
			{
				if ($bWebApi && !isset($aArguments['UserId']))
				{
					$aArguments['UserId'] = \CApi::getAuthenticatedUserId();
				}

				$aMethodArgs = $this->getMethodArguments($sMethod, $aArguments, $bWebApi);

				$bEventResult = $this->broadcastEvent(
					$sMethod . \AApiModule::$Delimiter . 'before', 
					$aArguments, 
					$mResult
				);

				if (!$bEventResult)
				{
					try
					{
						$mMethodResult = call_user_func_array(
							array($this, $sMethod), 
							$aMethodArgs
						);
						if (is_array($mMethodResult) && is_array($mResult))
						{
							$mResult = array_merge($mMethodResult, $mResult);
						}
						else if ($mMethodResult !== null)
						{
							$mResult = $mMethodResult;
						}
					} 
					catch (\Exception $oException) 
					{
						\CApi::GetModuleManager()->AddResult(
							$this->GetName(), 
							$sMethod, 
							$mResult,
							$oException->getCode()
						);
						if (!($oException instanceof \System\Exceptions\AuroraApiException))
						{
							throw new \System\Exceptions\AuroraApiException(
								$oException->getCode(), 
								$oException, 
								$oException->getMessage()
							);
						}
						else
						{
							throw $oException;
						}
					}
				}
				
				$this->broadcastEvent(
					$sMethod . \AApiModule::$Delimiter . 'after', 
					$aArguments, 
					$mResult
				);
				
				\CApi::GetModuleManager()->AddResult(
					$this->GetName(), 
					$sMethod, 
					$mResult
				);
			}
		}
		catch (\Exception $oException)
		{
			if (!($oException instanceof \System\Exceptions\AuroraApiException))
			{
				throw new \System\Exceptions\AuroraApiException(
					$oException->getCode(), 
					$oException, 
					$oException->getMessage()
				);
			}
			else
			{
				throw $oException;
			}
		}
				
		return $mResult;
	}
	
	/**
	 * Obtaines list of module settings for authenticated user.
	 * 
	 * @return array
	 */
	public function GetSettings()
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

	/**
	 * 
	 * @param \AEntity $oEntity
	 */
	public function updateEnabledForEntity(&$oEntity, $bEnabled = true)
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
			if ($bEnabled)
			{
				if (in_array($this->GetName(), $aDisabledModules))
				{
					$aDisabledModules = array_diff($aDisabledModules, array($this->GetName()));
				}
			}
			else
			{
				if (!in_array($this->GetName(), $aDisabledModules))
				{
					$aDisabledModules[] = $this->GetName();
				}				
			}
			$sDisabledModules = implode('|', $aDisabledModules);
			$oEntity->{'@DisabledModules'} = $sDisabledModules;
			$oEavManager->setAttributes(
				array($oEntity->iId), 
				array(new \CAttribute('@DisabledModules', $sDisabledModules, 'string'))
			);
		}	
	}
	
	/**
	 * 
	 * @param AEntity $oEntity
	 * @return bool
	 */
	public function isEnabledForEntity(&$oEntity)
	{
		$sDisabledModules = isset($oEntity->{'@DisabledModules'}) ? $oEntity->{'@DisabledModules'} : '';
		$aDisabledModules =  !empty(trim($sDisabledModules)) ? array($sDisabledModules) : array();
		if (substr_count($sDisabledModules, "|") > 0)
		{
			$aDisabledModules = explode("|", $sDisabledModules);
		}
		
		return !in_array($this->GetName(), $aDisabledModules);
	}
}

