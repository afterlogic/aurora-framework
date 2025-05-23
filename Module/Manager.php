<?php
/**
 * This code is licensed under AGPLv3 license or Afterlogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

namespace Aurora\System\Module;

use Aurora\Modules\Core\Models\User;
use Aurora\System\Exceptions\ApiException;
use Aurora\System\Managers\Response;

/**
 * @license https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @license https://afterlogic.com/products/common-licensing Afterlogic Software License
 * @copyright Copyright (c) 2019, Afterlogic Corp.
 *
 * @package Api
 */
class Manager
{
    /**
     * This array contains a list of modules
     *
     * @var array
     */
    protected $_aModules = array();

    /**
     * This array contains a list of modules paths
     *
     * @var array
     */
    protected $_aModulesPaths = null;

    /**
     * This array contains a list of modules
     *
     * @var array
     */
    protected $_aAllowedModulesName = array(
        'core' => 'Core'
    );

    /**
     * @var array
     */
    private $_aTemplates;

    /**
     * @var array
     */
    private $_aResults;

    /**
     * @var \Aurora\System\EventEmitter
     */
    private $oEventEmitter;

    /**
     * @var \Aurora\System\ObjectExtender
     */
    private $oObjectExtender;

    /**
     * @var \Aurora\System\Exceptions\Exception
     */
    private $oLastException;

    /**
     * @var array
     */
    private $aModulesSettings;

    /**
     *
     */
    public function __construct()
    {
        $this->oEventEmitter = \Aurora\System\EventEmitter::getInstance();
        $this->oObjectExtender = \Aurora\System\ObjectExtender::getInstance();
    }

    /**
     *
     * @return self
     */
    public static function createInstance()
    {
        return new self();
    }

    /**
     *
     * @return void
     */
    public function loadModules()
    {
        $oUser = \Aurora\System\Api::authorise();
        $oCoreModule = $this->loadModule('Core');

        if ($oCoreModule instanceof AbstractModule) {
            $oTenant = null;
            if ($oUser instanceof User && $oUser->Role !== \Aurora\System\Enums\UserRole::SuperAdmin) {
                $oTenant = \Aurora\System\Api::getTenantById($oUser->IdTenant);
            }
            foreach ($this->GetModulesPaths() as $sModuleName => $sModulePath) {
                $bIsModuleDisabledForTenant = $this->isModuleDisabledForObject($oTenant, $sModuleName);
                $bIsModuleDisabledForUser = $this->isModuleDisabledForObject($oUser, $sModuleName);
                $bModuleIsDisabled = $this->getModuleConfigValue($sModuleName, 'Disabled', false);
                if (!($bIsModuleDisabledForUser || $bIsModuleDisabledForTenant) && !$bModuleIsDisabled) {
                    $oLoadedModule = $this->loadModule($sModuleName, $sModulePath);
                    $bClientModule = $this->isClientModule($sModuleName);
                    if ($oLoadedModule instanceof AbstractModule || $bClientModule) {
                        $this->_aAllowedModulesName[\strtolower($sModuleName)] = $sModuleName;
                    } else {
                        //						\Aurora\System\Api::Log('Module ' . $sModuleName . ' is not allowed. $bModuleLoaded = ' . $oLoadedModule . '. $bClientModule = ' . $bClientModule . '.');
                    }
                } else {
                    $this->FlushModuleSettings($sModuleName);
                    //					\Aurora\System\Api::Log('Module ' . $sModuleName . ' is not allowed. $bIsModuleDisabledForUser = ' . $bIsModuleDisabledForUser . '. $bModuleIsDisabled = ' . $bModuleIsDisabled . '.');
                }
            }
        } else {
            echo "Can't load 'Core' Module";
            exit;
        }
    }

    /**
     *
     */
    protected function isModuleDisabledForObject($oObject, $sModuleName)
    {
        return ($oObject instanceof \Aurora\System\Classes\Model) ? $oObject->isModuleDisabled($sModuleName) : false;
    }

    /**
     *
     * @param string $sModuleName
     * @return boolean
     */
    protected function isClientModule($sModuleName)
    {
        $sModulePath = $this->GetModulePath($sModuleName);
        return \file_exists($sModulePath . $sModuleName . '/js/manager.js') || \file_exists($sModulePath . $sModuleName . '/vue/manager.js');
    }

    /**
     *
     * @param string $sModuleName
     * @return boolean
     */
    public function isModuleLoaded($sModuleName)
    {
        return \array_key_exists(\strtolower($sModuleName), $this->_aModules);
    }

    /**
     *
     * @param string $sModuleName
     * @param string $sConfigName
     * @param string $sDefaultValue
     * @return mixed
     */
    public function getModuleConfigValue($sModuleName, $sConfigName, $sDefaultValue = null)
    {
        $mResult = $sDefaultValue;
        $oModuleConfig = $this->getModuleSettings($sModuleName);

        if ($oModuleConfig) {
            $mResult = $oModuleConfig->GetValue($sConfigName, $sDefaultValue);
        }

        return $mResult;
    }

    /**
     *
     * @param string $sModuleName
     * @param string $sConfigName
     * @param string $sValue
     * @return mixed
     */
    public function setModuleConfigValue($sModuleName, $sConfigName, $sValue)
    {
        $oModuleConfig = $this->getModuleSettings($sModuleName);
        if ($oModuleConfig) {
            $oModuleConfig->SetValue($sConfigName, $sValue);
        }
    }

    /**
     *
     * @param string $sModuleName
     * @return mixed
     */
    public function saveModuleConfigValue($sModuleName)
    {
        $oModuleConfig = $this->getModuleSettings($sModuleName);
        if ($oModuleConfig) {
            $oModuleConfig->Save();
        }
    }

    /**
     *
     */
    public function SyncModulesConfigs()
    {
        $sConfigFilename = 'pre-config.json';
        $sConfigPath = AU_APP_ROOT_PATH . $sConfigFilename;
        $aModulesPreconfig = [];
        //getting modules pre-configuration data
        if (file_exists($sConfigPath)) {
            $sPreConfig = file_get_contents($sConfigPath);

            $aPreConfig = json_decode($sPreConfig, true);

            if (is_array($aPreConfig) && isset($aPreConfig['modules'])) {
                $aModulesPreconfig = $aPreConfig['modules'];
            }
        }

        foreach ($this->GetModulesPaths() as $sModuleName => $sModulePath) {
            if (!empty($sModuleName)) {
                $oSettings = $this->getModuleSettings($sModuleName);
                if ($oSettings instanceof Settings) {
                    $oSettings->Load();
                    //overriding modules default configuration with pre-configuration data
                    if (isset($aModulesPreconfig[$sModuleName])) {
                        $aModulePreconfig = $aModulesPreconfig[$sModuleName];
                        foreach ($aModulePreconfig as $key => $val) {
                            $oProp = $oSettings->GetSettingsProperty($key);
                            if ($oProp && $oProp->IsDefault) {
                                if (!empty($oProp->SpecType)) {
                                    $val = \Aurora\System\Enums\EnumConvert::FromXml($val, $oProp->SpecType);
                                }
                                $oSettings->SetValue($key, $val);
                            }
                        }
                    }
                    $oSettings->Save();
                }
            }
        }
    }

    /**
     *
     * @param string $sModuleName
     * @return \Aurora\System\Module\AbstractModule
     */
    protected function loadModule($sModuleName, $sModulePath = null)
    {
        $mResult = false;
        if (!isset($sModulePath)) {
            $sModulePath = $this->GetModulePath($sModuleName);
        }

        if ($sModulePath) {
            if (!$this->isModuleLoaded($sModuleName)) {
                $aArgs = array($sModuleName, $sModulePath);

                $this->broadcastEvent(
                    $sModuleName,
                    'loadModule' . AbstractModule::$Delimiter . 'before',
                    $aArgs
                );

                if (@\file_exists($sModulePath . $sModuleName . '/Module.php')) {
                    $sModuleClassName = '\\Aurora\\Modules\\' . $sModuleName . '\\Module';
                    $oModule = new $sModuleClassName($sModulePath);
                    if ($oModule instanceof AbstractModule) {
                        foreach ($oModule->GetRequireModules() as $sModule) {
                            if (!$this->loadModule($sModule, $sModulePath)) {
                                break;
                            }
                        }

                        if ($oModule->initialize() && $oModule->isValid()) {
                            $this->_aModules[\strtolower($sModuleName)] = $oModule;
                            $mResult = $oModule;
                        }
                    }
                }

                $this->broadcastEvent(
                    $sModuleName,
                    'loadModule' . AbstractModule::$Delimiter . 'after',
                    $aArgs,
                    $mResult
                );
            } else {
                $mResult = $this->GetModule($sModuleName);
            }
        }
        return $mResult;
    }

    /**
     * @param string $sParsedTemplateID
     * @param string $sParsedPlace
     * @param string $sTemplateFileName
     * @param string $sModuleName
     */
    public function includeTemplate($sParsedTemplateID, $sParsedPlace, $sTemplateFileName, $sModuleName = '')
    {
        if (!isset($this->_aTemplates[$sParsedTemplateID])) {
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
        if (isset($this->_aTemplates[$sTemplateID]) && \is_array($this->_aTemplates[$sTemplateID])) {
            foreach ($this->_aTemplates[$sTemplateID] as $aItem) {
                if (!empty($aItem[0]) && !empty($aItem[1]) && \file_exists($aItem[1])) {
                    $sTemplateHtml = \file_get_contents($aItem[1]);
                    if (!empty($aItem[2])) {
                        $sTemplateHtml = \str_replace('%ModuleName%', $aItem[2], $sTemplateHtml);
                        $sTemplateHtml = \str_replace('%MODULENAME%', \strtoupper($aItem[2]), $sTemplateHtml);
                    }
                    $sTemplateSource = \str_replace(
                        '{%INCLUDE-START/' . $aItem[0] . '/INCLUDE-END%}',
                        $sTemplateHtml . '{%INCLUDE-START/' . $aItem[0] . '/INCLUDE-END%}',
                        $sTemplateSource
                    );
                }
            }
        }

        return $sTemplateSource;
    }

    /**
     *
     * @param string $sModule
     * @param string $sType
     * @param array $aMap
     */
    public function extendObject($sModule, $sType, $aMap)
    {
        $this->oObjectExtender->extend($sModule, $sType, $aMap);
    }

    /**
     *
     * @param string $sType
     * @return array
     */
    public function getExtendedObject($sType)
    {
        return $this->oObjectExtender->getObject($sType);
    }

    /**
     *
     * @param string $sType
     * @return boolean
     */
    public function issetObject($sType)
    {
        return $this->oObjectExtender->issetObject($sType);
    }

    /**
     * @todo return correct path according to curent tenant
     *
     * @return string
     */
    public function GetModulesRootPath()
    {
        return AU_APP_ROOT_PATH . 'modules/';
    }

    /**
     * @todo return correct path according to curent tenant
     *
     * @return array
     */
    public function GetModulesPaths()
    {
        if (!isset($this->_aModulesPaths)) {
            $sModulesPath = $this->GetModulesRootPath();
            $aModulePath = [
                $sModulesPath
            ];
            $oCoreModule = $this->loadModule('Core', $sModulesPath);
            if ($oCoreModule instanceof \Aurora\Modules\Core\Module) {
                $sTenant = \trim($oCoreModule->GetTenantName());
                if (!empty($sTenant)) {
                    $sTenantModulesPath = $this->GetTenantModulesPath($sTenant);
                    \array_unshift($aModulePath, $sTenantModulesPath);
                }
            }
            $this->_aModulesPaths = [];
            foreach ($aModulePath as $sModulesPath) {
                if (@\is_dir($sModulesPath)) {
                    if (false !== ($rDirHandle = @\opendir($sModulesPath))) {
                        while (false !== ($sFileItem = @\readdir($rDirHandle))) {
                            if (0 < \strlen($sFileItem) && '.' !== $sFileItem[0] && \preg_match('/^[a-zA-Z0-9\-]+$/', $sFileItem)) {
                                $this->_aModulesPaths[$sFileItem] = $sModulesPath;
                            }
                        }

                        @\closedir($rDirHandle);
                    }
                }
            }
        }

        return $this->_aModulesPaths;
    }

    /**
     * @todo return correct path according to curent tenant
     *
     * @return string
     */
    public function GetModulePath($sModuleName)
    {
        $aModulesPaths = $this->GetModulesPaths();
        return isset($aModulesPaths[$sModuleName]) ? $aModulesPaths[$sModuleName] : false;
    }

    /**
     * @todo return correct path according to curent tenant
     *
     * @return string
     */
    public function GetModulesSettingsPath()
    {
        return \Aurora\System\Api::DataPath() . '/settings/modules/';
    }

    /**
     * @return string
     */
    public function GetTenantModulesPath($sTenant)
    {
        return AU_APP_ROOT_PATH . 'tenants/' . $sTenant . '/modules/';
    }

    /**
     * @return array
     */
    public function GetAllowedModulesName()
    {
        $aArgs = [];
        $mResult = $this->_aAllowedModulesName;

        $this->broadcastEvent('System', 'GetAllowedModulesName', $aArgs, $mResult, true);

        return $mResult;
    }

    /**
     * @param string $sModuleName
     * @return array
     */
    public function IsAllowedModule($sModuleName)
    {
        $aArgs = [
            'ModuleName' => $sModuleName
        ];
        $mResult = array_key_exists(\strtolower($sModuleName), $this->_aAllowedModulesName);

        $this->broadcastEvent('System', 'IsAllowedModule', $aArgs, $mResult, true);

        return $mResult;
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
     * @return \Aurora\System\Module\Settings
     */
    public function &getModuleSettings($sModuleName)
    {
        if (!isset($this->aModulesSettings[strtolower($sModuleName)])) {
            $sSettingsClassName = '\\Aurora\\Modules\\' . $sModuleName . '\\Settings';
            if (class_exists($sSettingsClassName)) {
                $this->aModulesSettings[strtolower($sModuleName)] = new $sSettingsClassName($sModuleName);
            } else {
                $this->aModulesSettings[strtolower($sModuleName)] = new Settings($sModuleName);
            }
        }

        return $this->aModulesSettings[strtolower($sModuleName)];
    }

    /**
     * @param string $sModuleName
     * @return void
     */
    public function FlushModuleSettings($sModuleName)
    {
        if (isset($this->aModulesSettings[strtolower($sModuleName)])) {
            unset($this->aModulesSettings[strtolower($sModuleName)]);
        }
    }

    /**
     * @param string $sModuleName
     * @return \Aurora\System\Module\AbstractModule
     */
    public function GetModule($sModuleName)
    {
        $mResult = false;

        $sModuleNameLower = strtolower($sModuleName);
        if ($this->isModuleLoaded($sModuleName)) {
            $mResult = $this->_aModules[$sModuleNameLower];
        }

        return $mResult;
    }


    /**
     * @return \Aurora\System\Module\AbstractModule
     */
    public function GetModuleFromRequest()
    {
        $sModule = '';
        $oHttp = \MailSo\Base\Http::SingletonInstance();
        if ($oHttp->IsPost()) {
            $sModule = $oHttp->GetPost('Module', null);
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

        if ($oResult && !$oResult->HasEntry($sEntryName)) {
            $oResult = false;
        }
        if ($oResult === false) {
            foreach ($this->_aModules as $oModule) {
                if ($oModule instanceof AbstractModule && $oModule->HasEntry($sEntryName)) {
                    $aModules[] = $oModule;
                }
            }
        } else {
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
        return ($this->GetModule($sModuleName)) ? true : false;
    }

    /**
     *
     * @param string $sEntryName
     * @return mixed
     */
    public function RunEntry($sEntryName)
    {
        $oHttp = \MailSo\Base\Http::SingletonInstance();

        $aArguments = [
            'EntryName' => $sEntryName,
            'Module' => $oHttp->GetPost('Module', null),
            'Method' => $oHttp->GetPost('Method', null),
            'Parameters' => \json_decode($oHttp->GetPost('Parameters', ''), true)
        ];
        $mResult = false;

        try {
            $bEventResult = $this->broadcastEvent('System', 'RunEntry' . AbstractModule::$Delimiter . 'before', $aArguments, $mResult);

            if ($bEventResult !== true) {
                if (!\Aurora\System\Router::getInstance()->hasRoute($sEntryName)) {
                    $sEntryName = 'default';
                }

                $mResult = \Aurora\System\Router::getInstance()->route(
                    $sEntryName
                );
            }
        } catch(\Exception $oException) {
            $mResult = \Aurora\System\Managers\Response::GetJsonFromObject(
                'Json',
                \Aurora\System\Managers\Response::ExceptionResponse("System", $oException)
            );
            \Aurora\System\Api::LogException($oException);
        } finally {
            $this->broadcastEvent('System', 'RunEntry' . AbstractModule::$Delimiter . 'after', $aArguments, $mResult);
        }

        return $mResult;
    }

    /**
     * @return string
     */
    public function GetModulesHash()
    {
        $sResult = md5(\Aurora\System\Api::Version());
        $aModuleNames = $this->GetAllowedModulesName();
        foreach ($aModuleNames as $sModuleName) {
            $sResult = md5($sResult . $this->GetModuleHashByName($sModuleName));
        }

        return $sResult;
    }

    /**
     * @toto need to add module version to information string
     * @param string $sModuleName
     *
     * @return string
     */
    public function GetModuleHashByName($sModuleName)
    {
        $sResult = '';
        $sTenantName = \Aurora\System\Api::getTenantName();

        $sResult .= $sTenantName !== 'Default' ? $this->GetModulesRootPath() : $this->GetTenantModulesPath($sTenantName);
        $sResult .= $sModuleName;

        return md5($sResult);
    }

    /**
     * @param string $oExcetpion
     */
    public function SetLastException($oExcetpion)
    {
        $this->oLastException = $oExcetpion;
    }

    /**
     *
     */
    public function GetLastException()
    {
        return $this->oLastException;
    }

    /**
     *
     * @param string $sModule
     * @param string $sMethod
     * @param mixed $mResult
     */
    public function AddResult($sModule, $sMethod, $aParameters, $mResult, $iErrorCode = 0)
    {
        if (is_string($mResult)) {
            $mResult = \str_replace(\Aurora\System\Api::$aSecretWords, '*******', $mResult);
        }

        $aMapParameters = array();
        if (is_array($aParameters)) {
            foreach ($aParameters as $sKey => $mParameter) {
                if (!is_resource($mParameter) && gettype($mParameter) !== 'unknown type') {
                    $aMapParameters[$sKey] = $mParameter;
                }
            }
        }

        $aResult = array(
            'Module' => $sModule,
            'Method' => $sMethod,
            'Parameters' => $aMapParameters,
            'Result' => $mResult
        );

        if ($iErrorCode > 0) {
            $aResult['ErrorCode'] = $iErrorCode;
        }

        $this->_aResults[] = $aResult;
    }

    /**
     * @return array
     */
    public function GetResults()
    {
        return $this->_aResults;
    }

    /**
     * @param string $sModule
     * @param string $sMethod
     * @return array
     */
    public function GetResult($sModule, $sMethod)
    {
        foreach ($this->_aResults as $aResult) {
            if ($aResult['Module'] === $sModule && $aResult['Method'] === $sMethod) {
                return [$aResult];
            }
        }

        return [];
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
    public function broadcastEvent($sModule, $sEvent, &$aArguments = [], &$mResult = null, $bSkipIsAllowedModuleCheck = false)
    {
        return $this->oEventEmitter->emit(
            $sModule,
            $sEvent,
            $aArguments,
            $mResult,
            function ($sModule, $aArguments, $mResult) use ($sEvent) {
                $this->AddResult($sModule, $sEvent, $aArguments, $mResult);
            },
            $bSkipIsAllowedModuleCheck
        );
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
     * @param callable $fCallback
     * @param int $iPriority
     * @return void
     */
    public function subscribeEvent($sEvent, $fCallback, $iPriority = 100)
    {
        $this->oEventEmitter->on($sEvent, $fCallback, $iPriority);
    }

    public function getEvents()
    {
        return $this->oEventEmitter->getListeners();
    }

    public function GetSubscriptionsResult()
    {
        return $this->oEventEmitter->getListenersResult();
    }
}
