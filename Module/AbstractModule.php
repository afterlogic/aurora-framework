<?php
/**
 * This code is licensed under AGPLv3 license or Afterlogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

namespace Aurora\System\Module;

use Aurora\Api;
use Illuminate\Support\Str;

/**
 * @license https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @license https://afterlogic.com/products/common-licensing Afterlogic Software License
 * @copyright Copyright (c) 2019, Afterlogic Corp.
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
    protected $aManagersCache = [];

    /**
     * @var array
     */
    protected $aParameters;

    /**
     * @var array
     */
    protected $aObjects = [];

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
    public $oModuleSettings = null;

    /**
     *
     * @var array
     */
    protected $aSettingsMap = [];

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
    protected $aRequireModules = [];

    /**
     *
     * @var array
     */
    protected $aSkipedEvents = [];

    /**
     *
     * @var array
     */
    public $aErrors = [];

    /**
     *
     * @var array
     */
    public $aAdditionalEntityFieldsToEdit = [];

    /**
     *
     * @var Manager
     */
    protected $oModuleManager = null;

    /**
     *
     * @var boolean
     */
    protected $bIsPermanent = false;

    protected $aDeniedMethodsByWebApi =  [
        '__construct',
        'init',
        'initialize',
        'createInstance',
        'getInstance',
        'Decorator',
        'RequireModule',
        'GetRequireModules',
        'isPermanent',
        'isValid',
        'getNamespace',
        'getModuleSettings',
        'loadModuleSettings',
        'saveModuleConfig',
        'getConfig',
        'setConfig',
        'denyMethodsCallByWebApi',
        'denyMethodCallByWebApi',
        'subscribeEvent',
        'broadcastEvent',
        'skipEvent',
        'removeEventFromSkiped',
        'includeTemplate',
        'extendObject',
        'getExtendedObject',
        'issetObject',
        'SetPath',
        'GetHash',
        'GetName',
        'GetPath',
        'GetVersion',
        'GetFullName',
        'AddEntry',
        'AddEntries',
        'HasEntry',
        'RemoveEntry',
        'RemoveEntries',
        'GetEntryCallback',
        'DefaultResponse',
        'TrueResponse',
        'FalseResponse',
        'ExceptionResponse',
        'CallMethod',
        'i18N',
        'GetErrors',
        'GetErrorMessageByCode',
        'GetAdditionalEntityFieldsToEdit',
    ];

    /**
     *
     * @var array
     */
    protected $aLang;

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

    /**
     *
     * @return void
     */
    abstract public function init();

    /**
     *
     * @param string $sPath
     * @param string $sVersion
     * @return \Aurora\System\Module\AbstractModule
     */
    public static function createInstance($sPath, $sVersion = '1.0')
    {
        /* @phpstan-ignore-next-line */
        return new static($sPath, $sVersion);
    }

    /**
     *
     * @return \Aurora\System\Module\AbstractModule
     */
    public static function getInstance()
    {
        return \Aurora\System\Api::GetModule(self::GetName());
    }

    /**
     *
     * @return \Aurora\System\Module\Decorator
     */
    public static function Decorator()
    {
        return \Aurora\System\Api::GetModuleDecorator(self::GetName());
    }

    /**
     *
     * @param \Aurora\System\Module\Manager $oModuleManager
     * @return  \Aurora\System\Module\Manager
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
     * @param string $sModule
     */
    public function RequireModule($sModule)
    {
        if (!in_array($sModule, $this->aRequireModules)) {
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
    public function isPermanent()
    {
        return $this->bIsPermanent;
    }

    /**
     *
     * @return boolean
     */
    public function isValid()
    {
        return true;
    }

    /**
     *
     * @return boolean
     */
    protected function isAllowedModule()
    {
        return $this->isPermanent() || $this->oModuleManager->IsAllowedModule(self::GetName());
    }

    /**
     *
     * @return boolean
     */
    protected function isInitialized()
    {
        return (bool) $this->bInitialized;
    }

    protected function getNamespaceName()
    {
        $className = get_class($this);
        return str_contains($className, '\\') 
            ? substr($className, 0, strrpos($className, '\\'))
            : null;
    }

    protected function initSubscriptions()
    {
        $subscriptionsClassName = $this->getNamespaceName() . "\\Subscriptions";
        if (class_exists($subscriptionsClassName)) {
            $subscriptions = new $subscriptionsClassName($this);
            $subscriptions->init();
        }
    }

    protected function initEntries()
    {
        $entitiesClassName = $this->getNamespaceName() . "\\Entries";
        if (class_exists($entitiesClassName)) {
            $entities = new $entitiesClassName($this);
            $entities->init();
        }
    }

    /**
     *
     * @return boolean
     */
    public function initialize()
    {
        $mResult = true;
        if (!$this->isInitialized()) {
            $this->bInitialized = true;
            $this->loadModuleSettings();
            $this->init();
            $this->initSubscriptions();
            $this->initEntries();
        }

        return $mResult;
    }

    /**
     *
     * @return string
     */
    final public static function getNamespace()
    {
        return \substr_replace(static::class, '', -7);
    }

    /**
     *
     * @return \Aurora\System\Module\Settings
     */
    public function getModuleSettings()
    {
        return $this->oModuleSettings;
    }

    /**
     *
     * @return \Aurora\System\Module\Settings
     */
    public function loadModuleSettings()
    {
        if (!isset($this->oModuleSettings)) {
            $this->oModuleSettings = & $this->GetModuleManager()->getModuleSettings(self::GetName());
            $this->oModuleSettings->Load();
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

        if (isset($this->oModuleSettings)) {
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
        if (isset($this->oModuleSettings)) {
            $mResult = $this->oModuleSettings->GetValue($sName, $mDefaultValue);
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

        if (isset($this->oModuleSettings)) {
            $bResult = $this->oModuleSettings->SetValue($sName, $sValue);
        }

        return $bResult;
    }

    /**
     *
     * @param array $aMethods
     *
     */
    public function denyMethodsCallByWebApi($aMethods)
    {
        foreach ($aMethods as $sMethodName) {
            $this->denyMethodCallByWebApi($sMethodName);
        }
    }

    /**
     *
     * @param string $sMethodName
     *
     */
    public function denyMethodCallByWebApi($sMethodName)
    {
        if (!in_array($sMethodName, $this->aDeniedMethodsByWebApi)) {
            $this->aDeniedMethodsByWebApi[] = $sMethodName;
        }
    }

    /**
     *
     * @param string $sMethodName
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
        foreach (array_values($aEvents) as $aEvent) {
            foreach ($aEvent as $aEv) {
                if ($aEv[0]::GetName() === self::GetName()) {
                    $aEventsValues[] = $aEv[1];
                }
            }
        }

        return $aEventsValues;
    }

    /**
     *
     * @param string $sEvent
     * @param callable $fCallback
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
    public function broadcastEvent($sEvent, &$aArguments = [], &$mResult = null)
    {
        if (!in_array($sEvent, $this->aSkipedEvents)) {
            return $this->GetModuleManager()->broadcastEvent(
                self::GetName(),
                $sEvent,
                $aArguments,
                $mResult
            );
        } else {
            $this->removeEventFromSkiped($sEvent);
        }
    }

    /**
     *
     * @param string $sEvent
     */
    public function skipEvent($sEvent)
    {
        if (!in_array($sEvent, $this->aSkipedEvents)) {
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
        if (0 < strlen($sParsedTemplateID) && 0 < strlen($sParsedPlace) && file_exists($this->GetPath() . '/' . $sTemplateFileName)) {
            $this->GetModuleManager()->includeTemplate(
                $sParsedTemplateID,
                $sParsedPlace,
                $this->GetPath() . '/' . $sTemplateFileName,
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

    public function getModuleName()
    {
        return static::GetName();
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
        return self::GetName() . '-' . $this->sVersion;
    }

    /**
     *
     * @param string $sName
     * @param callable $mCallbak
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
        foreach ($aEntries as $sName => $mCallbak) {
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
        foreach ($aEntries as $sName) {
            $this->RemoveEntry($sName);
        }
    }

    /**
     *
     * @param callable $mCallbak
     * @return boolean
     */
    protected function isEntryCallback($mCallbak)
    {
        return \Aurora\System\Router::getInstance()->hasCallback($mCallbak);
    }

    /**
     *
     * @param string $sName
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
        return \Aurora\System\Managers\Response::DefaultResponse(self::GetName(), $sMethod, $mResult);
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
        return \Aurora\System\Managers\Response::FalseResponse($sMethod, $iErrorCode, $sErrorMessage, $aAdditionalParams, $sModule);
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
        return \Aurora\System\Managers\Response::ExceptionResponse($sActionName, $oException, $aAdditionalParams);
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
        if ($bWebApi) {
            foreach ($aReflectionParameters as $oParam) {
                $sParamName = $oParam->getName();
                $iParamPosition = $oParam->getPosition();
                $bIsArgumentGiven = array_key_exists($sParamName, $aArguments);
                if (!$bIsArgumentGiven && !$oParam->isDefaultValueAvailable()) {
                    $aMethodArgs[$iParamPosition] = null;
                } else {
                    $aMethodArgs[$iParamPosition] = $bIsArgumentGiven ?
                        $aArguments[$sParamName] : $oParam->getDefaultValue();
                }
            }
        } else {
            $aTempArguments = array();
            $aMethodArgs = $aArguments;
            foreach ($aReflectionParameters as $oParam) {
                $sParamName = $oParam->getName();
                $iParamPosition = $oParam->getPosition();
                $mArgumentValue = null;
                if (isset($aArguments[$iParamPosition])) {
                    $mArgumentValue = $aArguments[$iParamPosition];
                } elseif ($oParam->isDefaultValueAvailable()) {
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
        try {
            if (!method_exists($this, $sMethod)) {
                throw new \Aurora\System\Exceptions\ApiException(
                    \Aurora\System\Notifications::MethodNotFound
                );
            } elseif (!$this->canCallMethod($sMethod, $bWebApi)) {
                throw new \Aurora\System\Exceptions\ApiException(
                    \Aurora\System\Notifications::MethodAccessDenied
                );
            } else {
                if ($bWebApi && !isset($aArguments['UserId'])) {
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

                if (!$bEventResult) {
                    try {
                        $oReflector = new \ReflectionMethod($this, $sMethod);
                        if (!$oReflector->isPublic()) {
                            throw new \Aurora\System\Exceptions\ApiException(
                                \Aurora\System\Notifications::MethodAccessDenied
                            );
                        }
                        $mMethodResult = $this->$sMethod(...$aMethodArgs);

                        if (is_array($mMethodResult) && is_array($mResult)) {
                            $mResult = array_merge($mMethodResult, $mResult);
                        } elseif ($mMethodResult !== null) {
                            $mResult = $mMethodResult;
                        }
                    } catch (\Exception $oException) {
                        if ($oException instanceof \Illuminate\Database\QueryException) {
                            // throw new \Aurora\System\Exceptions\ApiException(
                            // 	$oException->getCode(),
                            // 	$oException,
                            // 	'Database is not configured'
                            // );
                            Api::LogException($oException);
                            $mResult = false;
                        } elseif (!($oException instanceof \Aurora\System\Exceptions\ApiException)) {
                            throw new \Aurora\System\Exceptions\ApiException(
                                $oException->getCode(),
                                $oException,
                                $oException->getMessage()
                            );
                        } else {
                            throw $oException;
                        }

                        $this->GetModuleManager()->AddResult(
                            self::GetName(),
                            $sMethod,
                            $aArguments,
                            $mResult,
                            $oException->getCode()
                        );
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
        } catch (\Exception $oException) {
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
     * @return array|null
     */
    public function GetSettings()
    {
        return null;
    }

    protected function getLangsData($sLang)
    {
        $mResult = false;
        $sLangFile = $this->GetPath() . '/i18n/' . $sLang . '.ini';
        $sLangFile = @\file_exists($sLangFile) ? $sLangFile : '';

        if (0 < \strlen($sLangFile)) {
            $aLang = \Aurora\System\Api::convertIniToLang($sLangFile);
            if (\is_array($aLang)) {
                $mResult = $aLang;
            }
        }
        return $mResult;
    }

    /**
     * @param string $sData
     * @param array $aParams = null
     * @param int $iPluralCount = null
     * @param int $iUserId = 0
     *
     * @return string
     */
    public function i18N($sData, $aParams = null, $iPluralCount = null, $iUserId = 0)
    {
        static $sLanguage = null;
        if (is_null($sLanguage)) {
            if ($iUserId > 0) {

                $oUser = Api::getUserById($iUserId);
                if ($oUser instanceof \Aurora\Modules\Core\Models\User) {
                    $sLanguage = $oUser->Language;
                }
            }
            if (empty($sLanguage)) {
                $sLanguage = \Aurora\System\Api::GetLanguage();
            }
        }

        if (is_null($this->aLang)) {
            if (isset(\Aurora\System\Api::$aClientI18N[self::GetName()][$sLanguage])) {
                $this->aLang = \Aurora\System\Api::$aClientI18N[self::GetName()][$sLanguage];
            } else {
                \Aurora\System\Api::$aClientI18N[self::GetName()][$sLanguage] = false;

                $this->aLang = $this->getLangsData($sLanguage);
                if (!$this->aLang) {
                    $this->aLang = $this->getLangsData('English');
                }

                if (\is_array($this->aLang)) {
                    \Aurora\System\Api::$aClientI18N[self::GetName()][$sLanguage] = $this->aLang;
                }
            }
            if (!isset($this->aLang[$sData])) {
                $this->aLang = $this->getLangsData('English');
            }
        }

        return isset($iPluralCount) ? \Aurora\System\Api::processTranslateParams($this->aLang, $sData, $aParams, \Aurora\System\Api::getPlural($sLanguage, $iPluralCount)) :
            \Aurora\System\Api::processTranslateParams($this->aLang, $sData, $aParams);
    }

    /**
     *
     * @param \Aurora\System\Classes\Model $oEntity
     */
    protected function updateEnabledForEntity(&$oEntity, $bEnabled = true)
    {
        if ($bEnabled) {
            $oEntity->enableModule(self::GetName());
        } else {
            $oEntity->disableModule(self::GetName());
        }
    }

    /**
     *
     * @param \Aurora\System\Classes\Model $oEntity
     * @return bool
     */
    protected function isEnabledForEntity(&$oEntity)
    {
        return !$oEntity->isModuleDisabled(self::GetName());
    }

    /**
     *
     * @return array
     */
    public function GetErrors()
    {
        return is_array($this->aErrors) ? (object) $this->aErrors : [];
    }

    /**
     * @param int $iErrorCode
     * @return string
     */
    public function GetErrorMessageByCode($iErrorCode)
    {
        return is_array($this->aErrors) && isset($this->aErrors[(int) $iErrorCode]) ? $this->aErrors[(int) $iErrorCode] : '';
    }

    /**
     *
     * @return array
     */
    public function GetAdditionalEntityFieldsToEdit()
    {
        return is_array($this->aAdditionalEntityFieldsToEdit) ? $this->aAdditionalEntityFieldsToEdit : [];
    }
}
