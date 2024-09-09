<?php
/**
 * This code is licensed under AGPLv3 license or Afterlogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

namespace Aurora\System;

use Aurora\Modules\Core\Models\User;
use Aurora\Modules\Core\Models\Tenant;
use Aurora\System\Enums\DbType;
use Aurora\System\Console\Commands;
use Aurora\System\Exceptions\ApiException;
use Illuminate\Container\Container;

/**
 * @license https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @license https://afterlogic.com/products/common-licensing Afterlogic Software License
 * @copyright Copyright (c) 2019, Afterlogic Corp.
 */
if (!defined('AU_APP_ROOT_PATH')) {
    define('AU_APP_ROOT_PATH', rtrim(realpath(dirname(__DIR__)), '\\/') . '/');
}

define('AU_API_PATH_TO_AURORA', '/../');

define('AU_API_CRLF', PHP_EOL);
define('AU_API_TAB', "\t");

define('AU_API_SESSION_WEBMAIL_NAME', 'PHPWEBMAILSESSID');

define('AU_API_HELPDESK_PUBLIC_NAME', '_helpdesk_');

// timezone fix
$sDefaultTimeZone = function_exists('date_default_timezone_get')
    ? @date_default_timezone_get() : 'US/Pacific';

define('AU_API_SERVER_TIME_ZONE', ($sDefaultTimeZone && 0 < strlen($sDefaultTimeZone))
    ? $sDefaultTimeZone : 'US/Pacific');

if (defined('AU_API_SERVER_TIME_ZONE') && function_exists('date_default_timezone_set')) {
    @date_default_timezone_set(AU_API_SERVER_TIME_ZONE);
}

unset($sDefaultTimeZone);

/**
 * @package Api
 */
class Api
{
    /**
     * @var \Aurora\System\Module\Manager
     */
    public static $oModuleManager;

    /**
     * @var array
     */
    public static $aModuleDecorators;

    /**
     * @var array
     */
    public static $aSecretWords = [];

    /**
     * @var bool
     */
    public static $bIsValid;

    /**
     * @deprecated
     * @var string
     */
    public static $sSalt;

    /**
     * @var string
     */
    public static $sEncryptionKey;

    /**
     * @var array
     */
    public static $aI18N = null;

    /**
     * @var array
     */
    public static $aClientI18N = [];

    /**
     * @var bool
     */
    public static $bUseDbLog = false;

    /**
     * @var bool
     */
    public static $bDebug = false;

    /**
     * @var array
     */
    protected static $aUserSession = [];

    /**
     * @var bool
     */
    protected static $__SKIP_CHECK_USER_ROLE__ = false;

    /**
     * @var string
     */
    protected static $sLanguage = null;

    /**
     * @var \Aurora\System\Settings
     */
    protected static $oSettings;

    /**
     * @var boolean
     */
    protected static $bInitialized = false;

    /**
     *
     */
    protected static $oAuthenticatedUser = null;

    /**
     * @var \Illuminate\Container\Container
     */
    public static $oContainer = null;

    /**
     * @var array
     */
    protected static $usersCache = [];

    /**
     * @var array
     */
    protected static $tenantsCache = [];

    /**
     * @deprecated
     * @return string
     */
    public static function GetSaltPath()
    {
        return self::DataPath() . '/salt8.php';
    }

    /**
     *
     * @return string
     */
    public static function GetEncryptionKeyPath()
    {
        return self::DataPath() . '/encryption_key.php';
    }

    /**
     *
     */
    public static function InitEncryptionKey()
    {
        $sEncryptionKey = '';
        $sEncryptionKeyPath = self::GetEncryptionKeyPath();

        if (!@file_exists($sEncryptionKeyPath)) {
            if (@file_exists(self::GetSaltPath())) {
                include self::GetSaltPath();
                $sEncryptionKey = self::$sSalt;
            } else {
                $sEncryptionKey = bin2hex(random_bytes(16));
            }

            $sEncryptionKey = '<?php \\Aurora\\System\\Api::$sEncryptionKey = "' . $sEncryptionKey . '";';
            if (@file_put_contents($sEncryptionKeyPath, $sEncryptionKey) && @file_exists(self::GetSaltPath())) {
                @unlink(self::GetSaltPath());
            }
        }

        if (is_readable($sEncryptionKeyPath)) {
            include_once $sEncryptionKeyPath;
        } else {
            throw new ApiException(Notifications::SystemNotConfigured, null, 'Check the read permission of the encryption key file');
        }
    }

    /**
     *
     */
    public static function GetUserSession()
    {
        return self::$aUserSession;
    }

    /**
     *
     */
    public static function SetUserSession($aUserSession)
    {
        self::$oAuthenticatedUser = null;
        return self::$aUserSession = $aUserSession;
    }

    /**
     *
     */
    public static function GrantAdminPrivileges()
    {
        self::$aUserSession['UserId'] = -1;
        self::$aUserSession['AuthToken'] = '';
    }

    public static function UseDbLogs($bUseDbLogs = false)
    {
        self::$bUseDbLog = $bUseDbLogs;
    }

    /**
     *
     * @param bool $bGrantAdminPrivileges
     */
    public static function Init($bGrantAdminPrivileges = false)
    {
        if (!defined('AU_API_INIT')) {
            $apiInitTimeStart = \microtime(true);

            include_once self::GetVendorPath() . 'autoload.php';
            include_once 'bootstrap.php';

            if ($bGrantAdminPrivileges) {
                self::GrantAdminPrivileges();
            }

            self::InitEncryptionKey();
            self::validateApi();
            self::GetModuleManager()->loadModules();

            define('AU_API_INIT', microtime(true) - $apiInitTimeStart);
        }
    }

    /**
     *
     * @param bool $bSkip
     * @return bool Previous state
     */
    public static function skipCheckUserRole($bSkip)
    {
        $bResult = self::$__SKIP_CHECK_USER_ROLE__;
        self::$__SKIP_CHECK_USER_ROLE__ = $bSkip;
        return $bResult;
    }

    /**
     *
     * @return bool
     */
    public static function accessCheckIsSkipped()
    {
        return self::$__SKIP_CHECK_USER_ROLE__;
    }

    public static function checkUserAccess($oUser)
    {
        if ($oUser) {
            $oAuthUser = Api::getAuthenticatedUser();
            switch ($oAuthUser->Role) {
                case \Aurora\System\Enums\UserRole::TenantAdmin:
                    if ($oUser->IdTenant !== $oAuthUser->IdTenant) {
                        throw new ApiException(Notifications::AccessDenied, null, 'AccessDenied');
                    }
                    break;
                case \Aurora\System\Enums\UserRole::NormalUser:
                    if ($oUser->Id !== $oAuthUser->Id) {
                        throw new ApiException(Notifications::AccessDenied, null, 'AccessDenied');
                    }
                    break;
            }
        }
    }

    /**
     * @param string $sWord
     *
     * @return void
     */
    public static function AddSecret($sWord)
    {
        if (0 < \strlen(\trim($sWord))) {
            self::$aSecretWords[] = $sWord;
            self::$aSecretWords = \array_unique(self::$aSecretWords);
        }
    }

    /**
     * @param array $aValues
     *
     * @return string
     */
    public static function EncodeKeyValues(array $aValues)
    {
        return Utils::UrlSafeBase64Encode(
            Utils::EncryptValue(@\json_encode($aValues))
        );
    }

    /**
     * Decrypts a string that is encrypted serialized array data
     *
     * @param string $sEncryptedValues
     *
     * @return array
     */
    public static function DecodeKeyValues(string $sEncryptedValues)
    {
        $sEncryptedValues = Utils::UrlSafeBase64Decode(trim($sEncryptedValues));

        $sValue = Utils::DecryptValue($sEncryptedValues);

        $aResult = @\json_decode($sValue, true);

        return \is_array($aResult) ? $aResult : array();
    }

    /**
     *
     * @return \Aurora\System\Module\Manager
     */
    public static function GetModuleManager()
    {
        if (!isset(self::$oModuleManager)) {
            self::$oModuleManager = Module\Manager::createInstance();
            self::$aModuleDecorators = [];
        }

        return self::$oModuleManager;
    }

    /**
     *
     * @param string $sModuleName
     * @return \Aurora\System\Module\Decorator
     */
    public static function GetModuleDecorator($sModuleName)
    {
        if (!isset(self::$aModuleDecorators[$sModuleName]) && self::GetModule($sModuleName) !== false) {
            self::$aModuleDecorators[$sModuleName] = new Module\Decorator($sModuleName);
        }

        return isset(self::$aModuleDecorators[$sModuleName]) ? self::$aModuleDecorators[$sModuleName] : false;
    }

    /**
     *
     * @param string $sModuleName
     * @return \Aurora\System\Module\AbstractModule
     */
    public static function GetModule($sModuleName)
    {
        return self::GetModuleManager()->GetModule($sModuleName);
    }

    /**
     *
     * @param string $sModuleName
     * @return bool
     */
    public static function IsModuleLoaded($sModuleName)
    {
        return self::GetModuleManager()->isModuleLoaded($sModuleName);
    }

    /**
     *
     * @return array
     */
    public static function GetModules()
    {
        return self::GetModuleManager()->GetModules();
    }

    /**
     *
     * @param string $sMethodName
     * @param array $aParameters
     * @return mixed
     */
    public static function ExecuteMethod($sMethodName, $aParameters = array())
    {
        list($sModuleName, $sMethodName) = explode(Module\AbstractModule::$Delimiter, $sMethodName);
        $oModule = self::GetModule($sModuleName);
        if ($oModule instanceof Module\AbstractModule) {
            return $oModule->CallMethod($sModuleName, $sMethodName, $aParameters);
        }
    }

    /**
     * @return \MailSo\Cache\CacheClient
     */
    public static function Cacher()
    {
        static $oCacher = null;
        if (null === $oCacher) {
            $oCacher = \MailSo\Cache\CacheClient::NewInstance();
            $oCacher->SetDriver(\MailSo\Cache\Drivers\File::NewInstance(self::DataPath() . '/cache'));
            $oCacher->SetCacheIndex(self::Version());
        }

        return $oCacher;
    }

    /**
     * @return UserSession
     */
    public static function UserSession()
    {
        static $oSession = null;
        if (null === $oSession) {
            $oSession = new UserSession();
        }

        return $oSession;
    }

    /**
     * @return \Aurora\System\Settings
     */
    public static function &GetSettings($force = false)
    {
        if (null === self::$oSettings || $force) {
            try {
                $sSettingsPath = \Aurora\System\Api::DataPath() . '/settings/';
                if (!\file_exists($sSettingsPath)) {
                    set_error_handler(function () {});
                    mkdir($sSettingsPath, 0777);
                    restore_error_handler();
                    if (!file_exists($sSettingsPath)) {
                        self::$oSettings = false;
                        return self::$oSettings;
                    }
                }

                self::$oSettings = new \Aurora\System\Settings($sSettingsPath . 'config.json');
                self::$oSettings->Load();
            } catch (\Aurora\System\Exceptions\BaseException $oException) {
                self::$oSettings = false;
            }
        }
        return self::$oSettings;
    }

    /**
     * @return bool
     */
    public static function UpdateSettings()
    {
        $bResult = true;
        try {
            Api::Init();
            Api::GetModuleManager()->SyncModulesConfigs();
            Api::GetSettings()->SyncConfigs();
        } catch (\Exception $e) {
            $bResult = false;
        }

        return $bResult;
    }

    /**
     * @return \PDO|false
     */
    public static function GetPDO()
    {
        static $oPdoCache = null;
        if (null !== $oPdoCache) {
            return $oPdoCache;
        }

        $oPdo = false;
        $oSettings = &self::GetSettings();
        if ($oSettings) {
            $sDbPort = '';
            $sUnixSocket = '';

            $iDbType = $oSettings->DBType;
            $sDbHost = $oSettings->DBHost;
            $sDbName = $oSettings->DBName;
            $sDbLogin = $oSettings->DBLogin;
            $sDbPassword = $oSettings->DBPassword;

            $iPos = strpos($sDbHost, ':');
            if (false !== $iPos && 0 < $iPos) {
                $sAfter = substr($sDbHost, $iPos + 1);
                $sDbHost = substr($sDbHost, 0, $iPos);

                if (is_numeric($sAfter)) {
                    $sDbPort = $sAfter;
                } else {
                    $sUnixSocket = $sAfter;
                }
            }

            if (class_exists('PDO')) {
                try {
                    $oPdo = @new \PDO((Enums\DbType::PostgreSQL === $iDbType ? 'pgsql' : 'mysql') . ':dbname=' . $sDbName .
                        (empty($sDbHost) ? '' : ';host=' . $sDbHost) .
                        (empty($sDbPort) ? '' : ';port=' . $sDbPort) .
                        (empty($sUnixSocket) ? '' : ';unix_socket=' . $sUnixSocket) . ';charset=utf8', $sDbLogin, $sDbPassword);

                    if ($oPdo) {
                        $oPdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
                        $oPdo->setAttribute(\PDO::MYSQL_ATTR_INIT_COMMAND, "SET NAMES utf8");
                    }
                } catch (\Exception $oException) {
                    self::Log($oException->getMessage(), Enums\LogLevel::Error);
                    self::Log($oException->getTraceAsString(), Enums\LogLevel::Error);
                    $oPdo = false;
                }
            } else {
                self::Log('Class PDO dosn\'t exist', Enums\LogLevel::Error);
            }
        }

        if (false !== $oPdo) {
            $oPdoCache = $oPdo;
        }

        return $oPdo;
    }

    /**
     * @return bool
     */
    public static function IsMobileApplication()
    {
        /* @var $oIntegrator \Aurora\System\Managers\Integrator */
        $oIntegrator = \Aurora\System\Managers\Integrator::getInstance();

        return (bool) $oIntegrator /*&& $oApiCapability->isNotLite()*/ && 1 === $oIntegrator->isMobile(); // todo
    }

    /**
     * @param string $sNewLocation
     */
    public static function Location($sNewLocation)
    {
        self::Log('Location: ' . $sNewLocation);
        @header('Location: ' . $sNewLocation);
    }

    /**
     * @param string $sNewLocation
     */
    public static function Location2($sNewLocation)
    {
        exit('<META HTTP-EQUIV="refresh" CONTENT="0; url=' . $sNewLocation . '">');
    }

    /**
     * @param string $sDesc
     * @param string $sModuleName
     */
    public static function LogEvent($sDesc, $sModuleName = '')
    {
        Logger::LogEvent($sDesc, $sModuleName);
    }

    /**
     * @param mixed $mObject
     * @param int $iLogLevel = \Aurora\System\Enums\LogLevel::Full
     * @param string $sFilePrefix = ''
     */
    public static function LogObject($mObject, $iLogLevel = Enums\LogLevel::Full, $sFilePrefix = '')
    {
        Logger::LogObject($mObject, $iLogLevel, $sFilePrefix);
    }

    /**
     * @param Exceptions\Exception $mObject
     * @param int $iLogLevel = \Aurora\System\Enums\LogLevel::Error
     * @param string|null $sFilePrefix = null
     */
    public static function LogException($mObject, $iLogLevel = Enums\LogLevel::Error, $sFilePrefix = null)
    {
        $sFilePrefix = $sFilePrefix ?: Logger::$sErrorLogPrefix;
        Logger::LogException($mObject, $iLogLevel, $sFilePrefix);
    }

    /**
     * @param string $sFilePrefix = ''
     *
     * @return string
     */
    public static function GetLogFileName($sFilePrefix = '', $iTimestamp = 0)
    {
        return Logger::GetLogFileName($sFilePrefix, $iTimestamp);
    }

    public static function GetLogFileDir()
    {
        return Logger::GetLogFileDir();
    }

    /**
     * @return \MailSo\Log\Logger
     */
    public static function SystemLogger()
    {
        return Logger::SystemLogger();
    }

    /**
     * @param string $sDesc
     * @param int $iLogLevel = \Aurora\System\Enums\LogLevel::Full
     * @param string $sFilePrefix = ''
     */
    public static function Log($sDesc, $iLogLevel = Enums\LogLevel::Full, $sFilePrefix = '')
    {
        Logger::Log($sDesc, $iLogLevel, $sFilePrefix);
    }

    /**
     * @param string $sDesc
     * @param string $sLogFile
     */
    public static function LogOnly($sDesc, $sLogFile)
    {
        Logger::LogOnly($sDesc, $sLogFile);
    }

    public static function LogSql($query)
    {
        $sql = $query->toSql();
        foreach($query->getBindings() as $binding) {
            $value = is_numeric($binding) ? $binding : "'" . $binding . "'";
            $sql = preg_replace('/\?/', $value, $sql, 1);
        }

        Api::Log($sql, \Aurora\System\Enums\LogLevel::Full, 'sql-');
    }

    public static function ClearLog($sFileFullPath)
    {
        return Logger::ClearLog($sFileFullPath);
    }

    public static function RemoveSeparateLogs()
    {
        Logger::RemoveSeparateLogs();
    }

    public static function removeOldLogs()
    {
        Logger::RemoveOldLogs();
    }

    public static function GetLoggerGuid()
    {
        return Logger::GetLoggerGuid();
    }

    /**
     * @return string
     */
    public static function RootPath()
    {
        defined('AU_API_ROOTPATH') || define('AU_API_ROOTPATH', rtrim(dirname(__FILE__), '/\\') . '/');
        return AU_API_ROOTPATH;
    }

    /**
     * @return string
     */
    public static function WebMailPath()
    {
        return self::RootPath() . ltrim(AU_API_PATH_TO_AURORA, '/');
    }

    /**
     * @return string
     */
    public static function GetVendorPath()
    {
        return self::RootPath() . '../vendor/';
    }

    /**
     * @return string
     */
    public static function VersionFull()
    {
        static $sVersion = null;
        $sAppVersion = @file_get_contents(self::WebMailPath() . 'VERSION');

        $sVersion = (empty($sAppVersion)) ? '0.0.0' : $sAppVersion;

        return $sVersion;
    }

    /**
     * @return string
     */
    public static function Version()
    {
        static $sVersion = null;
        if (null === $sVersion) {
            preg_match('/[\d\.]+/', @file_get_contents(self::WebMailPath() . 'VERSION'), $matches);

            if (isset($matches[0])) {
                $sAppVersion = preg_replace('/[^0-9]/', '', $matches[0]);
            }

            $sVersion = (empty($sAppVersion)) ? '0.0.0' : $sAppVersion;
        }
        return $sVersion;
    }

    /**
     * @return string
     */
    public static function VersionJs()
    {
        $oSettings = &self::GetSettings();
        $sAppVersion = @file_get_contents(self::WebMailPath() . 'VERSION');
        $sAppVersion = empty($sAppVersion) ? '0.0.0' : $sAppVersion;

        return preg_replace('/[^0-9]/', '', $sAppVersion);
    }

    /**
     * @return string
     */
    public static function DataPath()
    {
        $dataPath = 'data';
        if (!defined('AU_API_DATA_FOLDER') && @file_exists(self::WebMailPath() . 'inc_settings_path.php')) {
            include self::WebMailPath() . 'inc_settings_path.php';
        }
        if (!defined('AU_API_DATA_FOLDER')) {
            define('AU_API_DATA_FOLDER', Utils::GetFullPath($dataPath, self::WebMailPath()));
        }
        $sDataFullPath = defined('AU_API_DATA_FOLDER') ? AU_API_DATA_FOLDER : '';

        /**
                if (!\file_exists($sDataFullPath))
                {
                    \mkdir($sDataFullPath, 0777);
                }
        */
        return $sDataFullPath;
    }

    /**
     * @return bool
     */
    protected static function validateApi()
    {
        $iResult = 1;

        $oSettings = &self::GetSettings();
        $iResult &= $oSettings && ($oSettings instanceof AbstractSettings);

        self::$bIsValid = (bool) $iResult;

        return self::$bIsValid;
    }

    /**
     * @return bool
     */
    public static function IsValid()
    {
        return (bool)self::$bIsValid;
    }

    /**
     * @param string $sEmail
     * @param string $sPassword
     * @param string $sLogin = ''
     * @return string
     */
    public static function GenerateSsoToken($sEmail, $sPassword, $sLogin = '')
    {
        $sSsoHash = \md5($sEmail . $sPassword . $sLogin . \microtime(true) . \rand(10000, 99999));
        return self::Cacher()->Set('SSO:' . $sSsoHash, self::EncodeKeyValues(array(
            'Email' => $sEmail,
            'Password' => $sPassword,
            'Login' => $sLogin
        ))) ? $sSsoHash : '';
    }

    /**
     * @param string $sLangFile
     * @return array
     */
    public static function convertIniToLang($sLangFile)
    {
        $aResultLang = false;

        $aLang = @\parse_ini_string(file_get_contents($sLangFile), true);
        if (is_array($aLang)) {
            $aResultLang = array();
            foreach ($aLang as $sKey => $mValue) {
                if (\is_array($mValue)) {
                    foreach ($mValue as $sSecKey => $mSecValue) {
                        $aResultLang[$sKey . '/' . $sSecKey] = $mSecValue;
                    }
                } else {
                    $aResultLang[$sKey] = $mValue;
                }
            }
        }

        return $aResultLang;
    }

    /**
     * @param mixed $mLang
     * @param string $sData
     * @param array|null $aParams = null
     * @return array
     */
    public static function processTranslateParams($mLang, $sData, $aParams = null, $iPlural = null)
    {
        $sResult = $sData;
        if ($mLang && isset($mLang[$sData])) {
            $sResult = $mLang[$sData];
        }

        if (isset($iPlural)) {
            $aPluralParts = explode('|', $sResult);

            $sResult = ($aPluralParts && $aPluralParts[$iPlural]) ? $aPluralParts[$iPlural] : (
                $aPluralParts && $aPluralParts[0] ? $aPluralParts[0] : $sResult
            );
        }

        if (null !== $aParams && is_array($aParams)) {
            foreach ($aParams as $sKey => $sValue) {
                $sResult = str_replace('%' . $sKey . '%', $sValue, $sResult);
            }
        }

        return $sResult;
    }

    /**
     *
     * @param string $sLanguage
     */
    public static function SetLanguage($sLanguage)
    {
        self::$sLanguage = $sLanguage;
    }

    /**
     *
     * @param bool $bForNewUser
     * @return string
     */
    public static function GetLanguage($bForNewUser = false)
    {
        $sResult = null;
        if (isset(self::$sLanguage)) {
            $sResult = self::$sLanguage;
        } else {
            $iAuthUserId = self::getAuthenticatedUserId();
            $bSuperAdmin = $iAuthUserId === -1;
            $oModuleManager = self::GetModuleManager();

            $sResult = $oModuleManager->getModuleConfigValue('Core', 'Language');
            if ($oModuleManager->getModuleConfigValue('Core', 'AutodetectLanguage', true)) {
                $sResult = self::getBrowserLanguage();
            }

            if ($bSuperAdmin) {
                $oSettings = &self::GetSettings();
                $sResult = $oSettings->AdminLanguage;
            } elseif (!$bForNewUser) {
                $oUser = self::getAuthenticatedUser();
                if ($oUser) {
                    $sResult = $oUser->Language;
                } elseif (isset($_COOKIE['aurora-lang-on-login'])) {
                    $sResult = $_COOKIE['aurora-lang-on-login'];
                }
            }
        }

        return $sResult;
    }

    protected static function getBrowserLanguage()
    {
        $aLanguages = array(
            'ar-dz' => 'Arabic', 'ar-bh' => 'Arabic', 'ar-eg' => 'Arabic', 'ar-iq' => 'Arabic', 'ar-jo' => 'Arabic', 'ar-kw' => 'Arabic',
            'ar-lb' => 'Arabic', 'ar-ly' => 'Arabic', 'ar-ma' => 'Arabic', 'ar-om' => 'Arabic', 'ar-qa' => 'Arabic', 'ar-sa' => 'Arabic',
            'ar-sy' => 'Arabic', 'ar-tn' => 'Arabic', 'ar-ae' => 'Arabic', 'ar-ye' => 'Arabic', 'ar' => 'Arabic',
            'bg' => 'Bulgarian',
            'zh-cn' => 'Chinese-Simplified', 'zh-hk' => 'Chinese-Simplified', 'zh-mo' => 'Chinese-Simplified', 'zh-sg' => 'Chinese-Simplified',
            'zh-tw' => 'Chinese-Simplified', 'zh' => 'Chinese-Simplified',
            'cs' => 'Czech',
            'da' => 'Danish',
            'nl-be' => 'Dutch', 'nl' => 'Dutch',
            'en-au' => 'English', 'en-bz' => 'English ', 'en-ca' => 'English', 'en-ie' => 'English', 'en-jm' => 'English',
            'en-nz' => 'English', 'en-ph' => 'English', 'en-za' => 'English', 'en-tt' => 'English', 'en-gb' => 'English',
            'en-us' => 'English', 'en-zw' => 'English', 'en' => 'English', 'us' => 'English',
            'et' => 'Estonian', 'fi' => 'Finnish',
            'fr-be' => 'French', 'fr-ca' => 'French', 'fr-lu' => 'French', 'fr-mc' => 'French', 'fr-ch' => 'French', 'fr' => 'French',
            'de-at' => 'German', 'de-li' => 'German', 'de-lu' => 'German', 'de-ch' => 'German', 'de' => 'German',
            'el' => 'Greek', 'he' => 'Hebrew', 'hu' => 'Hungarian', 'it-ch' => 'Italian', 'it' => 'Italian',
            'ja' => 'Japanese', 'ko' => 'Korean', 'lv' => 'Latvian', 'lt' => 'Lithuanian',
            'nb-no' => 'Norwegian', 'nn-no' => 'Norwegian', 'no' => 'Norwegian', 'pl' => 'Polish',
            'pt-br' => 'Portuguese-Brazil', 'pt' => 'Portuguese-Portuguese', 'pt-pt' => 'Portuguese-Portuguese',
            'ro-md' => 'Romanian', 'ro' => 'Romanian',
            'ru-md' => 'Russian', 'ru' => 'Russian', 'sr' => 'Serbian',
            'es-ar' => 'Spanish', 'es-bo' => 'Spanish', 'es-cl' => 'Spanish', 'es-co' => 'Spanish', 'es-cr' => 'Spanish',
            'es-do' => 'Spanish', 'es-ec' => 'Spanish', 'es-sv' => 'Spanish', 'es-gt' => 'Spanish', 'es-hn' => 'Spanish)',
            'es-mx' => 'Spanish', 'es-ni' => 'Spanish', 'es-pa' => 'Spanish', 'es-py' => 'Spanish', 'es-pe' => 'Spanish',
            'es-pr' => 'Spanish', 'es-us' => 'Spanish ', 'es-uy' => 'Spanish', 'es-ve' => 'Spanish', 'es' => 'Spanish',
            'sv-fi' => 'Swedish', 'sv' => 'Swedish', 'th' => 'Thai', 'tr' => 'Turkish', 'uk' => 'Ukrainian', 'vi' => 'Vietnamese', 'sl' => 'Slovenian'
        );

        $sLanguage = !empty($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? strtolower($_SERVER['HTTP_ACCEPT_LANGUAGE']) : 'en';
        $aTempLanguages = preg_split('/[,;]+/', $sLanguage);
        $sLanguage = !empty($aTempLanguages[0]) ? $aTempLanguages[0] : 'en';

        $sLanguageShort = substr($sLanguage, 0, 2);

        return \array_key_exists($sLanguage, $aLanguages) ? $aLanguages[$sLanguage] :
            (\array_key_exists($sLanguageShort, $aLanguages) ? $aLanguages[$sLanguageShort] : '');
    }

    /**
     * @param string $sData
     * @param array $aParams = null
     * @param mixed $iPluralCount = null
     *
     * @return string
     */
    public static function ClientI18N($sData, $aParams = null, $iPluralCount = null)
    {
        $sLanguage = self::GetLanguage();

        $aLang = null;
        if (isset(self::$aClientI18N[$sLanguage])) {
            $aLang = self::$aClientI18N[$sLanguage];
        } else {
            self::$aClientI18N[$sLanguage] = false;

            $sLangFile = self::WebMailPath() . 'i18n/' . $sLanguage . '.ini';
            if (!@file_exists($sLangFile)) {
                $sLangFile = self::WebMailPath() . 'i18n/English.ini';
                $sLangFile = @file_exists($sLangFile) ? $sLangFile : '';
            }

            if (0 < strlen($sLangFile)) {
                $aLang = self::convertIniToLang($sLangFile);
                if (is_array($aLang)) {
                    self::$aClientI18N[$sLanguage] = $aLang;
                }
            }
        }

        return isset($iPluralCount) ? self::processTranslateParams($aLang, $sData, $aParams, self::getPlural($sLanguage, $iPluralCount)) : self::processTranslateParams($aLang, $sData, $aParams);
    }

    public static function getPlural($sLang = '', $iNumber = 0)
    {
        $iResult = 0;
        $iNumber = (int) $iNumber;

        switch ($sLang) {
            case 'Arabic':
                $iResult = ($iNumber === 0 ? 0 : ($iNumber === 1 ? 1 : ($iNumber === 2 ? 2 : ($iNumber % 100 >= 3 && $iNumber % 100 <= 10 ? 3 : ($iNumber % 100 >= 11 ? 4 : 5)))));
                break;
            case 'Bulgarian':
                $iResult = ($iNumber === 1 ? 0 : 1);
                break;
            case 'Chinese-Simplified':
                $iResult = 0;
                break;
            case 'Chinese-Traditional':
                $iResult = ($iNumber === 1 ? 0 : 1);
                break;
            case 'Czech':
                $iResult = ($iNumber === 1) ? 0 : (($iNumber >= 2 && $iNumber <= 4) ? 1 : 2);
                break;
            case 'Danish':
                $iResult = ($iNumber === 1 ? 0 : 1);
                break;
            case 'Dutch':
                $iResult = ($iNumber === 1 ? 0 : 1);
                break;
            case 'English':
                $iResult = ($iNumber === 1 ? 0 : 1);
                break;
            case 'Estonian':
                $iResult = ($iNumber === 1 ? 0 : 1);
                break;
            case 'Finnish':
                $iResult = ($iNumber === 1 ? 0 : 1);
                break;
            case 'French':
                $iResult = ($iNumber === 1 ? 0 : 1);
                break;
            case 'German':
                $iResult = ($iNumber === 1 ? 0 : 1);
                break;
            case 'Greek':
                $iResult = ($iNumber === 1 ? 0 : 1);
                break;
            case 'Hebrew':
                $iResult = ($iNumber === 1 ? 0 : 1);
                break;
            case 'Hungarian':
                $iResult = ($iNumber === 1 ? 0 : 1);
                break;
            case 'Italian':
                $iResult = ($iNumber === 1 ? 0 : 1);
                break;
            case 'Japanese':
                $iResult = 0;
                break;
            case 'Korean':
                $iResult = 0;
                break;
            case 'Latvian':
                $iResult = ($iNumber % 10 === 1 && $iNumber % 100 !== 11 ? 0 : ($iNumber !== 0 ? 1 : 2));
                break;
            case 'Lithuanian':
                $iResult = ($iNumber % 10 === 1 && $iNumber % 100 !== 11 ? 0 : ($iNumber % 10 >= 2 && ($iNumber % 100 < 10 || $iNumber % 100 >= 20) ? 1 : 2));
                break;
            case 'Norwegian':
                $iResult = ($iNumber === 1 ? 0 : 1);
                break;
            case 'Persian':
                $iResult = 0;
                break;
            case 'Polish':
                $iResult = ($iNumber === 1 ? 0 : ($iNumber % 10 >= 2 && $iNumber % 10 <= 4 && ($iNumber % 100 < 10 || $iNumber % 100 >= 20) ? 1 : 2));
                break;
            case 'Portuguese-Portuguese':
                $iResult = ($iNumber === 1 ? 0 : 1);
                break;
            case 'Portuguese-Brazil':
                $iResult = ($iNumber === 1 ? 0 : 1);
                break;
            case 'Romanian':
                $iResult = ($iNumber === 1 ? 0 : (($iNumber === 0 || ($iNumber % 100 > 0 && $iNumber % 100 < 20)) ? 1 : 2));
                break;
            case 'Russian':
                $iResult = ($iNumber % 10 === 1 && $iNumber % 100 !== 11 ? 0 : ($iNumber % 10 >= 2 && $iNumber % 10 <= 4 && ($iNumber % 100 < 10 || $iNumber % 100 >= 20) ? 1 : 2));
                break;
            case 'Serbian':
                $iResult = ($iNumber % 10 === 1 && $iNumber % 100 !== 11 ? 0 : ($iNumber % 10 >= 2 && $iNumber % 10 <= 4 && ($iNumber % 100 < 10 || $iNumber % 100 >= 20) ? 1 : 2));
                break;
            case 'Slovenian':
                $iResult = (($iNumber % 10 === 1 && $iNumber % 100 !== 11) ? 0 : (($iNumber % 10 === 2 && $iNumber % 100 !== 12) ? 1 : 2));
                break;
            case 'Spanish':
                $iResult = ($iNumber === 1 ? 0 : 1);
                break;
            case 'Swedish':
                $iResult = ($iNumber === 1 ? 0 : 1);
                break;
            case 'Thai':
                $iResult = 0;
                break;
            case 'Turkish':
                $iResult = ($iNumber === 1 ? 0 : 1);
                break;
            case 'Ukrainian':
                $iResult = ($iNumber % 10 === 1 && $iNumber % 100 !== 11 ? 0 : ($iNumber % 10 >= 2 && $iNumber % 10 <= 4 && ($iNumber % 100 < 10 || $iNumber % 100 >= 20) ? 1 : 2));
                break;
            case 'Vietnamese':
                $iResult = 0;
                break;
            default:
                $iResult = 0;
                break;
        }

        return $iResult;
    }

    /**
     * @param string $sData
     * @param array $aParams = null
     *
     * @return string
     */
    public static function I18N($sData, $aParams = null, $sForceCustomInitialisationLang = '')
    {
        if (null === self::$aI18N) {
            self::$aI18N = false;

            $sLangFile = '';
            if (0 < strlen($sForceCustomInitialisationLang)) {
                $sLangFile = self::RootPath() . 'common/i18n/' . $sForceCustomInitialisationLang . '.ini';
            }

            if (0 === strlen($sLangFile) || !@file_exists($sLangFile)) {
                $sLangFile = self::RootPath() . 'common/i18n/English.ini';
            }

            if (0 < strlen($sLangFile) && @file_exists($sLangFile)) {
                $aResultLang = self::convertIniToLang($sLangFile);
                if (is_array($aResultLang)) {
                    self::$aI18N = $aResultLang;
                }
            }
        }

        return self::processTranslateParams(self::$aI18N, $sData, $aParams);
    }

    /**
     * Checks if authenticated user has at least specified role.
     * @param int $iRole
     * @throws \Aurora\System\Exceptions\ApiException
     */
    public static function checkUserRoleIsAtLeast($iRole)
    {
        if (!self::$__SKIP_CHECK_USER_ROLE__) {
            $oUser = self::getAuthenticatedUser();
            $bUserRoleIsAtLeast = $oUser === null && $iRole === Enums\UserRole::Anonymous ||
                $oUser !== null && $oUser->Role === Enums\UserRole::Customer &&
                    ($iRole === Enums\UserRole::Customer || $iRole === Enums\UserRole::Anonymous) ||
                $oUser !== null && $oUser->Role === Enums\UserRole::NormalUser &&
                    ($iRole === Enums\UserRole::NormalUser || $iRole === Enums\UserRole::Customer || $iRole === Enums\UserRole::Anonymous) ||
                $oUser !== null && $oUser->Role === Enums\UserRole::TenantAdmin &&
                    ($iRole === Enums\UserRole::TenantAdmin || $iRole === Enums\UserRole::NormalUser || $iRole === Enums\UserRole::Customer || $iRole === Enums\UserRole::Anonymous) ||
                $oUser !== null && $oUser->Role === Enums\UserRole::SuperAdmin &&
                    ($iRole === Enums\UserRole::SuperAdmin || $iRole === Enums\UserRole::TenantAdmin || $iRole === Enums\UserRole::NormalUser || $iRole === Enums\UserRole::Customer || $iRole === Enums\UserRole::Anonymous);
            if (!$bUserRoleIsAtLeast) {
                throw new Exceptions\ApiException(Notifications::AccessDenied, null, 'AccessDenied');
            }
        }
    }

    public static function getAuthTokenFromHeaders()
    {
        $sResult = false;
        $sAuthHeader =  \MailSo\Base\Http::SingletonInstance()->GetHeader('Authorization');
        if (!empty($sAuthHeader)) {
            $authHeaderData = explode(' ', $sAuthHeader);

            if (isset($authHeaderData[0]) && strtolower($authHeaderData[0]) === 'bearer' && isset($authHeaderData[1]) && !empty($authHeaderData[1])) {
                $sResult = $authHeaderData[1];
            }
        }

        return $sResult;
    }

    public static function requireAdminAuth()
    {
        $mResult = false;
        $response = new \Sabre\HTTP\Response();
        $basicAuth = new \Sabre\HTTP\Auth\Basic("Locked down area", \Sabre\HTTP\Sapi::getRequest(), $response);
        if (!$userPass = $basicAuth->getCredentials()) {
            $basicAuth->requireLogin();
            \Sabre\HTTP\Sapi::sendResponse($response);
        } elseif (!\Aurora\Modules\AdminAuth\Module::getInstance()->Login($userPass[0], $userPass[1])) {
            $basicAuth->requireLogin();
            \Sabre\HTTP\Sapi::sendResponse($response);
        } else {
            $mResult = true;
        }

        if (!$mResult) {
            $response->setBody('Unauthorized');
            \Sabre\HTTP\Sapi::sendResponse($response);
            exit;
        }
    }

    public static function getDeviceIdFromHeaders()
    {
        $sResult = false;
        $sDeviceIdHeader =  \MailSo\Base\Http::SingletonInstance()->GetHeader('X-DeviceId');
        if (!empty($sDeviceIdHeader)) {
            $sResult = $sDeviceIdHeader;
        }

        return $sResult;
    }

    /**
     *
     * @return string
     */
    public static function getAuthToken()
    {
        $sAuthToken = self::getAuthTokenFromHeaders();
        if (!$sAuthToken) {
            $sAuthToken = isset($_COOKIE[Application::AUTH_TOKEN_KEY]) ?
                    $_COOKIE[Application::AUTH_TOKEN_KEY] : '';
        }

        return $sAuthToken;
    }

    /**
     *
     * @return bool
     */
    public static function validateCsrfToken()
    {
        $bResult = true;
        if (isset($_COOKIE[Application::AUTH_TOKEN_KEY])) {
            $sAuthToken = self::getAuthTokenFromHeaders();

            $bResult = ($sAuthToken === $_COOKIE[Application::AUTH_TOKEN_KEY]);
        }

        return $bResult;
    }

    /**
     *
     * @return \Aurora\Modules\Core\Models\User
     */
    public static function authorise($sAuthToken = '')
    {
        $oUser = null;
        $mUserId = false;
        try {
            if (isset(self::$aUserSession['UserId'])) {
                $mUserId = self::$aUserSession['UserId'];
            } else {
                $sAuthToken = empty($sAuthToken) ? self::getAuthToken() : $sAuthToken;
                $mUserId = self::getAuthenticatedUserId($sAuthToken);
            }
            $oUser = self::getUserById($mUserId);
        } catch (\Exception $oException) {
        }
        return $oUser;
    }

    public static function getAuthenticatedUserInfo($sAuthToken = '')
    {
        $mResult = false;
        if (empty($sAuthToken)) {
            if (is_array(self::$aUserSession) && isset(self::$aUserSession['AuthToken'])) {
                $sAuthToken = self::$aUserSession['AuthToken'];
            }
        }
        /* @var $oIntegrator \Aurora\System\Managers\Integrator */
        $oIntegrator = \Aurora\System\Managers\Integrator::getInstance();
        if ($oIntegrator) {
            $mResult = $oIntegrator->getAuthenticatedUserInfo($sAuthToken);
        }

        return $mResult;
    }

    public static function validateAuthToken($authToken = null)
    {
        $bResult = false;

        if ($authToken === null) {
            $authToken = self::getAuthToken();
        }
        /* @var $oIntegrator \Aurora\System\Managers\Integrator */
        $oIntegrator = \Aurora\System\Managers\Integrator::getInstance();
        if ($oIntegrator) {
            $bResult = $oIntegrator->validateAuthToken($authToken);
        }

        return $bResult;
    }

    public static function getCookiePath()
    {
        static $sPath = false;

        if (false === $sPath) {
            $sScriptName = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '';
            $aPath = explode('/', $sScriptName);
            $sLastPathItem = count($aPath) > 0 ? $aPath[count($aPath) - 1] : '';
            if (count($aPath) > 0 && ($sLastPathItem !== '' || strtolower(substr($sLastPathItem, -1)) === '.php')) {
                array_pop($aPath);
            }
            $sPath = implode('/', $aPath) . '/';
        }

        return $sPath;
    }

    public static function getCookieSecure()
    {
        return self::isHttps();
    }

    public static function isHttps()
    {
        return	(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
                (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == '443');
    }

    public static function getAuthenticatedUserId($sAuthToken = '')
    {
        $mResult = false;
        if (!empty($sAuthToken)) {
            $aInfo = \Aurora\System\Managers\Integrator::getInstance()->getAuthenticatedUserInfo($sAuthToken);
            if (!empty(self::$aUserSession['UserId']) && (int) $aInfo['userId'] === (int) self::$aUserSession['UserId']) {
                $mResult = (int) self::$aUserSession['UserId'];
            } else {
                $mResult = $aInfo['userId'];
                self::$aUserSession['UserId'] = (int) $mResult;
                self::$aUserSession['AuthToken'] = $sAuthToken;
            }
        } else {
            if (is_array(self::$aUserSession) && isset(self::$aUserSession['UserId'])) {
                $mResult = self::$aUserSession['UserId'];
            } else {
                $mResult = 0;
            }
        }

        return $mResult;
    }

    public static function getAuthenticatedUserPublicId($sAuthToken = '')
    {
        $iUserId = self::getAuthenticatedUserId($sAuthToken);
        return self::getUserPublicIdById($iUserId);
    }

    /**
     * @return void
     */
    public static function unsetAuthenticatedUser()
    {
        unset(self::$oAuthenticatedUser);
    }

    /**
     * @param string $sAuthToken
     * @param bool $bForce
     *
     * @return \Aurora\Modules\Core\Models\User
     */
    public static function getAuthenticatedUser($sAuthToken = '', $bForce = false)
    {
        $iUserId = 0;
        if (null === self::$oAuthenticatedUser || $bForce) {
            if (!empty($sAuthToken)) {
                $iUserId = self::getAuthenticatedUserId($sAuthToken); // called for saving in session
            } elseif (!empty(self::$aUserSession['UserId'])) {
                $iUserId = self::$aUserSession['UserId'];
            }

            self::$oAuthenticatedUser = self::getUserById($iUserId);
        }
        return self::$oAuthenticatedUser;
    }

    public static function getAuthenticatedUserAuthToken()
    {
        $mResult = false;

        if (is_array(self::$aUserSession) && isset(self::$aUserSession['AuthToken'])) {
            $mResult = self::$aUserSession['AuthToken'];
        }

        return $mResult;
    }

    /**
     * @param int $iUserId
     * @return string
     */
    public static function getUserUUIDById($iUserId)
    {
        $sUUID = '';

        if (\is_numeric($iUserId)) {
            $oUser = self::getUserById($iUserId);

            if ($oUser instanceof User) {
                $sUUID = $oUser->UUID;
            }
        } else {
            $sUUID = $iUserId;
        }

        return $sUUID;
    }

    /**
     * @param int $iUserId
     * @return string
     */
    public static function getUserPublicIdById($iUserId)
    {
        $sPublicId = '';

        if (\is_numeric($iUserId)) {
            $oUser = self::getUserById($iUserId);
            if ($oUser) {
                return $oUser->PublicId;
            }
            // @TODO: check if it's needed
        } else {
            $sPublicId = $iUserId;
        }

        return $sPublicId;
    }

    /**
     * @param string $sPublicId
     * @return int|false
     */
    public static function getUserIdByPublicId($sPublicId)
    {
        $iUserId = false;

        if (Api::GetSettings()->GetValue('AdminLogin') === $sPublicId) { // superadmin user
            return -1;
        }

        $user = self::getUserByPublicId($sPublicId);
        if ($user instanceof User) {
            $iUserId = $user->Id;
        }

        return $iUserId;
    }

    public static function getUserByPublicId($sPublicId, $bForce = false)
    {
        $result = null;
        if (!$bForce) {
            foreach (self::$usersCache as $user) {
                if ($user->PublicId === $sPublicId) {
                    $result = $user;
                    break;
                }
            }
        }
        if (!$result) {
            $result = User::where('PublicId', $sPublicId)->first();
            if ($result) {
                self::$usersCache[$result->Id] = $result;
            }
        }

        return $result;
    }

    public static function getUserById($iUserId, $bForce = false)
    {
        try {
            if (!isset(self::$usersCache[$iUserId]) || $bForce) {
                $oUser = Managers\Integrator::getUserByIdHelper($iUserId);
                if ($oUser) {
                    self::$usersCache[$iUserId] = $oUser;
                }
            }
        } catch (\Exception $oEx) {
            self::LogException($oEx);
        }

        return isset(self::$usersCache[$iUserId]) ? self::$usersCache[$iUserId] : null;
    }

    public static function removeUserFromCache($iUserId)
    {
        if (!isset(self::$usersCache[$iUserId])) {
            unset(self::$usersCache[$iUserId]);
        }
    }

    public static function getTenantById($iTenantId, $bForce = false)
    {
        try {
            if (!isset(self::$tenantsCache[$iTenantId]) || $bForce) {
                self::$tenantsCache[$iTenantId] = Tenant::find($iTenantId);
                ;
            }
        } catch (\Exception $oEx) {
            self::$tenantsCache[$iTenantId] = false;
        }

        return self::$tenantsCache[$iTenantId];
    }

    public static function getTenantByWebDomain()
    {
        static $bTenantInitialized = false;
        static $oTenant = null;

        if (!$bTenantInitialized) {
            if (!empty($_SERVER['SERVER_NAME'])) {

                foreach (self::$tenantsCache as $tenantCache) {
                    if ($tenantCache->WebDomain === $_SERVER['SERVER_NAME']) {
                        $oTenant = $tenantCache;
                        break;
                    }
                }

                if (!$oTenant) {
                    $oTenant = Tenant::firstWhere('WebDomain', $_SERVER['SERVER_NAME']);
                    if ($oTenant) {
                        self::$tenantsCache[$oTenant->Id] = $oTenant;
                    }
                }
            }
            $bTenantInitialized = true;
        }

        return $oTenant;
    }

    public static function removeTenantFromCache($iTenantId)
    {
        if (!isset(self::$tenantsCache[$iTenantId])) {
            unset(self::$tenantsCache[$iTenantId]);
        }
    }

    public static function setTenantName($sTenantName)
    {
        self::$aUserSession['TenantName'] = $sTenantName;
    }

    public static function setUserId($iUserId)
    {
        self::$aUserSession['UserId'] = (int) $iUserId;
    }

    public static function setAuthToken($sAuthToken)
    {
        self::$aUserSession['AuthToken'] = $sAuthToken;
    }

    public static function getCurrentTenant()
    {
        static $bTenantInitialized = false;
        static $oTenant = null;

        if (!$bTenantInitialized) {
            $oUser = self::getAuthenticatedUser();

            if ($oUser && !$oUser->isAdmin()) {
                $oTenant = self::getTenantById($oUser->IdTenant);
            }

            if (!$oUser && !$oTenant) {
                $oTenant = self::getTenantByWebDomain();
            }

            //			$bTenantInitialized = true;
        }

        return $oTenant;
    }

    /**
     *
     * @return string
     */
    public static function getTenantName()
    {
        static $mResult = null;

        if (!isset($mResult)) {
            if (is_array(self::$aUserSession) && !empty(self::$aUserSession['TenantName'])) {
                $mResult = self::$aUserSession['TenantName'];
            } else {
                try {
                    $oTenant = self::getCurrentTenant();
                    if ($oTenant) {
                        $mResult = $oTenant->Name;
                    }
                } catch (\Exception $oEx) {
                    $mResult = false;
                }
            }
            //			$bTenantInitialized = true;
        }

        return $mResult;
    }

    public static function GetDbConfig($DbType, $DbHost, $DbName, $DbPrefix, $DbLogin, $DbPassword)
    {
        $aDbHost = \explode(':', $DbHost);
        if (isset($aDbHost[0])) {
            $DbHost = $aDbHost[0];
        }
        $aDbConfig = [
            'driver' => DbType::PostgreSQL === $DbType ? 'pgsql' : 'mysql',
            'host' => $DbHost,
            'database' => $DbName,
            'username' => $DbLogin,
            'password' => $DbPassword,
            'charset'   => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => $DbPrefix,
        ];
        if (isset($aDbHost[1])) {
            $aDbConfig['port'] = $aDbHost[1];
        }

        return $aDbConfig;
    }

    public static function CreateContainer($force = false)
    {
        if (!isset(self::$oContainer) || $force) {
            // Instantiate the app container
            $appContainer = Container::getInstance();

            // Tell facade about the application instance
            \Illuminate\Support\Facades\Facade::setFacadeApplication($appContainer);

            $appContainer['app'] = $appContainer;

            $appContainer['config'] = new \Illuminate\Config\Repository();

            $oSettings = &Api::GetSettings();
            if ($oSettings) {
                $capsule = new \Illuminate\Database\Capsule\Manager();
                $appContainer['capsule'] = $capsule;
                $capsule->addConnection(
                    self::GetDbConfig(
                        $oSettings->DBType,
                        $oSettings->DBHost,
                        $oSettings->DBName,
                        $oSettings->DBPrefix,
                        $oSettings->DBLogin,
                        $oSettings->DBPassword
                    )
                );

                //Make this Capsule instance available globally.
                $capsule->setAsGlobal();

                // Setup the Eloquent ORM.
                $capsule->bootEloquent();

                $appContainer['connection'] = function ($ac) use ($capsule) {
                    return $capsule->getConnection('default');
                };

                $appContainer['migration-table'] = 'migrations';

                $appContainer['filesystem'] = function ($ac) {
                    return new \Illuminate\Filesystem\Filesystem();
                };

                $appContainer['resolver'] = function ($ac) {
                    $r = new \Illuminate\Database\ConnectionResolver(['default' => $ac['connection']]);
                    $r->setDefaultConnection('default');
                    return $r;
                };

                $appContainer['migration-repo'] = function ($ac) {
                    return new \Illuminate\Database\Migrations\DatabaseMigrationRepository($ac['resolver'], $ac['migration-table']);
                };

                $appContainer['migrator'] = function ($ac) {
                    return new \Illuminate\Database\Migrations\Migrator($ac['migration-repo'], $ac['resolver'], $ac['filesystem']);
                };

                $appContainer['migration-creator'] = function ($ac) {
                    return new \Illuminate\Database\Migrations\MigrationCreator($ac['filesystem'], \Aurora\Api::RootPath() . 'Console' . DIRECTORY_SEPARATOR . 'stubs');
                };

                $appContainer['composer'] = function ($ac) {
                    return new \Illuminate\Support\Composer($ac['filesystem']);
                };

                $appContainer['console'] = function ($ac) {
                    $consoleaApp = new \Symfony\Component\Console\Application();

                    $events = new \Illuminate\Events\Dispatcher($ac);

                    $consoleaApp = new \Illuminate\Console\Application($ac, $events, 'Version 1.0');
                    $consoleaApp->setName('Aurora console app');

                    $consoleaApp->add(new Commands\Migrations\InstallCommand($ac['migration-repo']));
                    $consoleaApp->add(new Commands\Migrations\MigrateCommand($ac['migrator']));
                    $consoleaApp->add(new Commands\Migrations\RollbackCommand($ac['migrator']));
                    $consoleaApp->add(new Commands\Migrations\MigrateMakeCommand($ac['migration-creator'], $ac['composer']));

                    $consoleaApp->add(new Commands\Seeds\SeedCommand($ac['resolver']));
                    $consoleaApp->add(new Commands\Seeds\SeederMakeCommand($ac['filesystem'], $ac['composer']));

                    $consoleaApp->add(new Commands\OrphansCommand());

                    $consoleaApp->add(new Commands\ModelsCommand($ac));

                    return $consoleaApp;
                };

                self::$oContainer = $appContainer;
            }
        }
    }

    /**
     * @return Container
     */
    public static function GetContainer($force = false)
    {
        self::CreateContainer($force);

        return self::$oContainer;
    }

    public static function CheckAccess(&$UserId)
    {
        if (self::accessCheckIsSkipped()) {
            return;
        }
        $bAccessDenied = true;

        $oAuthenticatedUser = self::getAuthenticatedUser();

        if ($UserId === null) {
            $iUserId = $oAuthenticatedUser->Id;
        } else {
            $iUserId = (int) $UserId;

            $iUserRole = $oAuthenticatedUser instanceof \Aurora\Modules\Core\Models\User ? $oAuthenticatedUser->Role : \Aurora\System\Enums\UserRole::Anonymous;
            switch ($iUserRole) {
                case (\Aurora\System\Enums\UserRole::SuperAdmin):
                    // everything is allowed for SuperAdmin
                    $UserId = $iUserId;
                    $bAccessDenied = false;
                    break;
                case (\Aurora\System\Enums\UserRole::TenantAdmin):
                    // everything is allowed for TenantAdmin
                    $oUser = \Aurora\Modules\Core\Module::getInstance()->GetUser($iUserId);
                    if ($oUser instanceof \Aurora\Modules\Core\Models\User) {
                        if ($oAuthenticatedUser->IdTenant === $oUser->IdTenant) {
                            $UserId = $iUserId;
                            $bAccessDenied = false;
                        }
                    }
                    break;
                case (\Aurora\System\Enums\UserRole::NormalUser):
                    // User identifier shoud be checked
                    if ($iUserId === $oAuthenticatedUser->Id) {
                        $UserId = $iUserId;
                        $bAccessDenied = false;
                    }
                    break;
                case (\Aurora\System\Enums\UserRole::Customer):
                case (\Aurora\System\Enums\UserRole::Anonymous):
                    // everything is forbidden for Customer and Anonymous users
                    break;
            }
            if ($bAccessDenied) {
                throw new ApiException(\Aurora\System\Notifications::AccessDenied, null, 'AccessDenied');
            }
        }
    }
}
