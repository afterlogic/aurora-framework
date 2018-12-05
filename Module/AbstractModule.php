<?php
/*
 * This code is licensed under AGPLv3 license or Afterlogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

namespace Aurora\System\Module;

/**
 * @license https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @license https://afterlogic.com/products/common-licensing AfterLogic Software License
 * @copyright Copyright (c) 2018, Afterlogic Corp.
 *
 * @package Api
 */
abstract class AbstractModule
{
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
	 * @var array
	 */
	protected $aObjects = array();	
	
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
     * @var \Aurora\System\Module\Settings
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
	protected $aSkipedEvents = array();

    /**
     *
     * @var array
     */
	public $aErrors = array();
    /**
     *
     * @var Manager
     */	
	protected $oModuleManager = null;
	
	protected $aDeniedMethodsByWebApi =  [
		'init',
		'initialize',
		'loadModuleSettings',
		'saveModuleConfig',
		'getConfig',
		'CallMethod'
	];

	/**
	 * @param string $sVersion
	 */
	public function __construct($sPath, $sVersion = '1.0')
	{
		$this->sVersion = (string) $sVersion;

		$this->sPath = $sPath . self::GetName();
		$this->aParameters = [];
		$this->oHttp = \MailSo\Base\Http::SingletonInstance();
		$this->oModuleManager = \Aurora\System\Api::GetModuleManager();
	}

	abstract public function init();
	
	/**
	 * 
	 * @param string $sName
	 * @param string $sPath
	 * @param string $sVersion
	 * @return \Aurora\System\Module\AbstractModule
	 */
	final public static function createInstance($sPath, $sVersion = '1.0')
	{
		return new static($sPath, $sVersion);
	}	
	
	public static function Decorator()
	{
		return \Aurora\System\Api::GetModuleDecorator(self::GetName());
	}

	public function __invoke()
	{
		return \Aurora\System\Api::GetModuleDecorator(self::GetName());
	}
	
	/**
	 * 
	 * @param \Aurora\System\Module\Manager $oModuleManager
	 * @return type
	 */
	protected function SetModuleManager(Manager $oModuleManager)
	{
		return $this->oModuleManager = $oModuleManager;
	}
	
	/**
	 * 
	 * @return \Aurora\System\Module\Manager
	 */
	protected function GetModuleManager()
	{
		return $this->oModuleManager;
	}

	/**
	 * 
	 */
	public function RequireModule($sModule)
	{
		if (!in_array($sModule, $this->aRequireModules))
		{
			$this->aRequireModules[] = $sModule;
		}
	}
	
	/**
	 * 
	 * @return array
	 */
	public function GetRequireModules()
	{
		return $this->aRequireModules;
	}
	
	/**
	 * 
	 * @return boolean
	 */
	public function isValid()
	{
		return true;
	}	

	
	protected function isAllowedModule()
	{
		return $this->oModuleManager->IsAllowedModule(self::GetName());
	}
	
	
	/**
	 * 
	 * @return boolean
	 */
	protected function isInitialized()
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
			$this->bInitialized = true;
			$this->loadModuleSettings();
			$this->init();
		}
		
		return $mResult;
	}
	
	/**
	 * 
	 */
	final public static function getNamespace()
	{
		return (new \ReflectionClass(static::class))->getNamespaceName();
//		return \dirname(static::class);
	}
	
	/**
	 * 
	 */
	public function loadModuleSettings()
	{
		if (!isset($this->oModuleSettings))
		{
			$this->oModuleSettings = $this->GetModuleManager()->GetModuleSettings(self::GetName());
		}
		return $this->oModuleSettings;
	}	

	/**
	 * Saves module settings to config.json file.
	 * 
	 * returns bool
	 */
	public function saveModuleConfig()
	{
		$bResult = false;

		if (isset($this->oModuleSettings))
		{
			$bResult = $this->oModuleSettings->Save();
		}

		return $bResult;
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
	
	public function denyMethodsCallByWebApi($aMethods)
	{
		foreach ($aMethods as $sMethodName)
		{
			$this->denyMethodCallByWebApi($sMethodName);
		}
	}
	
	public function denyMethodCallByWebApi($sMethodName)
	{
		if(!in_array($sMethodName, $this->aDeniedMethodsByWebApi))
		{
			$this->aDeniedMethodsByWebApi[] = $sMethodName;
		}
	}
		
	/**
	 * 
	 * @param callback $mCallbak
	 * @return boolean
	 */
	protected function isDeniedMethodByWebApi($sMethodName)
	{
		return in_array($sMethodName, array_values($this->aDeniedMethodsByWebApi));
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
	protected function getEventsCallbacks()
	{
		$aEventsValues = array();
		$aEvents = $this->GetModuleManager()->getEvents();
		foreach(array_values($aEvents) as $aEvent)
		{
			foreach ($aEvent as $aEv)
			{
				if ($aEv[0]::GetName() === self::GetName())
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
		$this->GetModuleManager()->subscribeEvent($sEvent, $fCallback, $iPriority);
	}

	/**
	 * 
	 * @param string $sEvent
	 * @param array $aArguments
	 */
	public function broadcastEvent($sEvent, &$aArguments = array(), &$mResult = null)
	{
		if (!in_array($sEvent, $this->aSkipedEvents))
		{
			return $this->GetModuleManager()->broadcastEvent(
				self::GetName(), 
				$sEvent, 
				$aArguments, 
				$mResult
			);
		}
		else
		{
			$this->removeEventFromSkiped($sEvent);
		}
	}
	
	/**
	 * 
	 * @param string $sEvent
	 */
	public function skipEvent($sEvent)
	{
		if (!in_array($sEvent, $this->aSkipedEvents))
		{
			$this->aSkipedEvents[] = $sEvent;
		}
	}
	
	/**
	 * 
	 * @param string $sEvent
	 */
	public function removeEventFromSkiped($sEvent)
	{
		$this->aSkipedEvents = array_diff(
			$this->aSkipedEvents, 
			array($sEvent)
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
			$this->GetModuleManager()->includeTemplate(
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
	public function extendObject($sType, $aMap)
	{
		$this->GetModuleManager()->extendObject(self::GetName(), $sType, $aMap);
	}	
	
	/**
	 * 
	 * @param string $sType
	 * @return array
	 */
	public function getExtendedObject($sType)
	{
		return $this->GetModuleManager()->getExtendedObject($sType);
	}
	
	/**
	 * 
	 * @param string $sType
	 * @return boolean
	 */
	public function issetObject($sType)
	{
		return $this->GetModuleManager()->issetObject($sType);
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
	final public function GetHash()
	{
		return '';
	}

	/**
	 * @return string
	 */
	final public static function GetName()
	{
		return substr(strrchr(static::getNamespace(), "\\"), 1);
	}

	/**
	 * @return string
	 */
	final public function GetPath()
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
	final public function GetFullName()
	{
		return self::GetName().'-'.$this->sVersion;
	}
	
	/**
	 * 
	 * @param string $sName
	 * @param callback $mCallbak
	 */
	final public function AddEntry($sName, $mCallbak)
	{
		\Aurora\System\Router::getInstance()->register(
			self::GetName(),
			$sName,
			[$this, $mCallbak]
		);
	}
	
	/**
	 * 
	 * @param array $aEntries
	 */
	final public function AddEntries($aEntries)
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
	final public function HasEntry($sName)
	{
		return \Aurora\System\Router::getInstance()->hasRoute($sName);
	}
	
	/**
	 * 
	 * @param string $sName
	 */
	final public function RemoveEntry($sName)
	{
		return \Aurora\System\Router::getInstance()->removeRoute($sName);
	}	
	
	/**
	 * 
	 * @param array $aEntries
	 */
	final public function RemoveEntries($aEntries)
	{
		foreach ($aEntries as $sName)
		{
			$this->RemoveEntry($sName);
		}
	}	
	
	/**
	 * 
	 * @param callback $mCallbak
	 * @return boolean
	 */
	protected function isEntryCallback($mCallbak)
	{
		return \Aurora\System\Router::getInstance()->hasCallback($mCallbak);
	}

	/**
	 * 
	 * @param stranig $sName
	 * @return mixed
	 */
	final public function GetEntryCallback($sName)
	{
		return \Aurora\System\Router::getInstance()->getCallback($sName);
	}	

	/**
	 * @param string $sMethod
	 * @param mixed $mResult = false
	 *
	 * @return array
	 */
	final public function DefaultResponse($sMethod, $mResult = false)
	{
		$aResult = array(
			'AuthenticatedUserId' => \Aurora\System\Api::getAuthenticatedUserId(),
			'@Time' => microtime(true) - AU_APP_START
		);
		if (is_array($mResult))
		{
			foreach ($mResult as $aValue)
			{
				$aResponseResult = \Aurora\System\Managers\Response::GetResponseObject(
					$aValue, 
					array(
						'Module' => $aValue['Module'],
						'Method' => $aValue['Method'],
						'Parameters' => $aValue['Parameters']
					)
				);
				if ($aValue['Module'] === self::GetName() && $aValue['Method'] === $sMethod)
				{
					$aResult = array_merge($aResult, $aResponseResult);
				}
				else if (\Aurora\System\Api::$bDebug)
				{
					$aResult['Stack'][] =  $aResponseResult;
				}
			}
		}
		$aResult['SubscriptionsResult'] = \Aurora\System\Api::GetModuleManager()->GetSubscriptionsResult();
		
		return $aResult;
	}	
	
	/**
	 * @param string $sMethod
	 *
	 * @return array
	 */
	final public function TrueResponse($sMethod)
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
	final public function FalseResponse($sMethod, $iErrorCode = null, $sErrorMessage = null, $aAdditionalParams = null, $sModule = null)
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
	final public function ExceptionResponse($sActionName, $oException, $aAdditionalParams = null)
	{
		$iErrorCode = null;
		$sErrorMessage = null;
		$sModule = '';

		$oSettings =& \Aurora\System\Api::GetSettings();
		$bShowError = $oSettings->GetConf('DisplayServerErrorInformation', false);

		if ($oException instanceof \Aurora\System\Exceptions\ApiException) 
		{
			$iErrorCode = $oException->getCode();
			$sErrorMessage = null;
			if ($bShowError) 
			{
				$sErrorMessage = $oException->getMessage();
				if (empty($sErrorMessage) || 'ApiException' === $sErrorMessage) 
				{
					$sErrorMessage = null;
				}
			}
			$oModule = $oException->GetModule();
			if ($oModule)
			{
				$sModule = $oModule::GetName();
			}
		}
		else if ($bShowError && $oException instanceof \MailSo\Imap\Exceptions\ResponseException) 
		{
			$iErrorCode = \Aurora\System\Notifications::MailServerError;
			
			$oResponse = /* @var $oResponse \MailSo\Imap\Response */ $oException->GetLastResponse();
			if ($oResponse instanceof \MailSo\Imap\Response) 
			{
				$sErrorMessage = $oResponse instanceof \MailSo\Imap\Response ?
					$oResponse->Tag.' '.$oResponse->StatusOrIndex.' '.$oResponse->HumanReadable : null;
			}
		} 
		else 
		{
			$iErrorCode = \Aurora\System\Notifications::UnknownError;
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
	protected function prepareMethodArguments($sMethodName, &$aArguments, $bWebApi)
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

	protected function canCallMethod($sMethod, $bWebApi)
	{
		return !($bWebApi && ($this->isCallbackMethod($sMethod) || $this->isDeniedMethodByWebApi($sMethod))) && $this->isAllowedModule();
	}
	
	/**
	 * 
	 * @param string $sMethod
	 * @param array $aArguments
	 * @param boolean $bWebApi
	 * @return mixed
	 */
	final public function CallMethod($sMethod, $aArguments = array(), $bWebApi = false)
	{
		$mResult = false;
		try 
		{
			if (method_exists($this, $sMethod) &&  $this->canCallMethod($sMethod, $bWebApi))
			{
				if ($bWebApi && !isset($aArguments['UserId']))
				{
					$aArguments['UserId'] = \Aurora\System\Api::getAuthenticatedUserId();
				}

				// prepare arguments for before event
				$aMethodArgs = $this->prepareMethodArguments($sMethod, $aArguments, $bWebApi);

				$bEventResult = $this->broadcastEvent(
					$sMethod . AbstractModule::$Delimiter . 'before', 
					$aArguments, 
					$mResult
				);
				
				// prepare arguments for main action after event
				$aMethodArgs = $this->prepareMethodArguments($sMethod, $aArguments, true);

				if (!$bEventResult)
				{
					try
					{
						$oReflector = new \ReflectionMethod($this, $sMethod);
						if (!$oReflector->isPublic())
						{
							throw new \Aurora\System\Exceptions\ApiException(
								\Aurora\System\Notifications::MethodNotFound
							);
						}
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
						$this->GetModuleManager()->AddResult(
							self::GetName(), 
							$sMethod, 
							$aArguments,
							$mResult,
							$oException->getCode()
						);
						if (!($oException instanceof \Aurora\System\Exceptions\ApiException))
						{
							throw new \Aurora\System\Exceptions\ApiException(
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
					$sMethod . AbstractModule::$Delimiter . 'after', 
					$aArguments, 
					$mResult
				);
				
				$this->GetModuleManager()->AddResult(
					self::GetName(), 
					$sMethod, 
					$aArguments,
					$mResult
				);
			}
			else
			{
				throw new \Aurora\System\Exceptions\ApiException(
					\Aurora\System\Notifications::MethodNotFound
				);
			}
		}
		catch (\Exception $oException)
		{
			throw new \Aurora\System\Exceptions\ApiException(
				$oException->getCode(), 
				$oException, 
				$oException->getMessage(),
				[],
				$this
			);
		}
				
		return $mResult;
	}
	
	/**
	 * Obtains list of module settings for authenticated user.
	 * 
	 * @return array
	 */
	public function GetSettings()
	{
		return null;
	}
	
	protected function getLangsData($sLang)
	{
		$mResult = false;
		$sLangFile = $this->GetPath().'/i18n/' . $sLang . '.ini';
		$sLangFile = @\file_exists($sLangFile) ? $sLangFile : '';

		if (0 < \strlen($sLangFile)) 
		{
			$aLang = \Aurora\System\Api::convertIniToLang($sLangFile);
			if (\is_array($aLang)) 
			{
				$mResult = $aLang;
			}
		}
		return $mResult;
	}

	/**
	 * @param string $sData
	 * @param array $aParams = null
	 * @param int $iPluralCount = null
	 * @param string $sUUID = null
	 *
	 * @return string
	 */
	public function i18N($sData, $aParams = null, $iPluralCount = null, $sUUID = null)
	{
		$sLanguage = '';
		if ($sUUID)
		{
			$oCoreDecorator = \Aurora\Modules\Core\Module::Decorator();
			$oUser = $oCoreDecorator ? $oCoreDecorator->GetUserByUUID($sUUID) : null;
			if ($oUser instanceof \Aurora\Modules\Core\Classes\User)
			{
				$sLanguage = $oUser->Language;
			}
		}
		if (empty($sLanguage))
		{
			$sLanguage = \Aurora\System\Api::GetLanguage();
		}
		
		$aLang = null;
		if (isset(\Aurora\System\Api::$aClientI18N[self::GetName()][$sLanguage])) 
		{
			$aLang = \Aurora\System\Api::$aClientI18N[self::GetName()][$sLanguage];
		} 
		else 
		{
			\Aurora\System\Api::$aClientI18N[self::GetName()][$sLanguage] = false;
				
			$aLang = $this->getLangsData($sLanguage);
			if (!$aLang)
			{
				$aLang = $this->getLangsData('English');
			}
			
			if (\is_array($aLang)) 
			{
				\Aurora\System\Api::$aClientI18N[self::GetName()][$sLanguage] = $aLang;
			}
		}
		if (!isset($aLang[$sData])) 
		{
			$aLang = $this->getLangsData('English');
		}
		
		return isset($iPluralCount) ? \Aurora\System\Api::processTranslateParams($aLang, $sData, $aParams, \Aurora\System\Api::getPlural($sLanguage, $iPluralCount)) : 
			\Aurora\System\Api::processTranslateParams($aLang, $sData, $aParams);
	}

	/**
	 * 
	 * @param \Aurora\System\EAV\Entity $oEntity
	 */
	protected function updateEnabledForEntity(&$oEntity, $bEnabled = true)
	{
		$oEavManager = new \Aurora\System\Managers\Eav();
		if ($oEavManager)
		{
			$sDisabledModules = isset($oEntity->{'@DisabledModules'}) ? \trim($oEntity->{'@DisabledModules'}) : '';
			$aDisabledModules =  !empty($sDisabledModules) ? array($sDisabledModules) : array();
			if($i = \substr_count($sDisabledModules, "|"))
			{
				$aDisabledModules = \explode("|", $sDisabledModules);
			}
			if ($bEnabled)
			{
				if (\in_array(self::GetName(), $aDisabledModules))
				{
					$aDisabledModules = \array_diff($aDisabledModules, array(self::GetName()));
				}
			}
			else
			{
				if (!\in_array(self::GetName(), $aDisabledModules))
				{
					$aDisabledModules[] = self::GetName();
				}				
			}
			$oEntity->{'@DisabledModules'} = \implode('|', $aDisabledModules);
			$oEavManager->saveEntity($oEntity);
		}	
	}
	
	/**
	 * 
	 * @param \Aurora\System\EAV\Entity $oEntity
	 * @return bool
	 */
	protected function isEnabledForEntity(&$oEntity)
	{
		$sDisabledModules = isset($oEntity->{'@DisabledModules'}) ? \trim($oEntity->{'@DisabledModules'}) : '';
		$aDisabledModules =  !empty($sDisabledModules) ? array($sDisabledModules) : array();
		if (\substr_count($sDisabledModules, "|") > 0)
		{
			$aDisabledModules = \explode("|", $sDisabledModules);
		}
		
		return !\in_array(self::GetName(), $aDisabledModules);
	}

	/**
	 *
	 * @return array
	 */
	public function GetErrors()
	{
		return is_array($this->aErrors) ? $this->aErrors : [];
	}

	/**
	 * @param int
	 * @return string
	 */
	public function GetErrorMessageByCode($iErrorCode)
	{
		return is_array($this->aErrors) && isset($this->aErrors[(int) $iErrorCode]) ? $this->aErrors[(int) $iErrorCode] : '';
	}
}