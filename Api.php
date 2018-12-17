<?php
/*
 * This code is licensed under AGPLv3 license or Afterlogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

namespace Aurora\System;

/**
 * @license https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @license https://afterlogic.com/products/common-licensing AfterLogic Software License
 * @copyright Copyright (c) 2018, Afterlogic Corp.
 */
if (!defined('AU_APP_ROOT_PATH'))
{
	define('AU_APP_ROOT_PATH', rtrim(realpath(dirname(__DIR__)), '\\/').'/');
	define('AU_APP_START', microtime(true));
}

define('AU_API_PATH_TO_AURORA', '/../');

define('AU_API_CRLF', "\r\n");
define('AU_API_TAB', "\t");

define('AU_API_SESSION_WEBMAIL_NAME', 'PHPWEBMAILSESSID');

define('AU_API_HELPDESK_PUBLIC_NAME', '_helpdesk_');

// timezone fix
$sDefaultTimeZone = function_exists('date_default_timezone_get')
	? @date_default_timezone_get() : 'US/Pacific';

define('AU_API_SERVER_TIME_ZONE', ($sDefaultTimeZone && 0 < strlen($sDefaultTimeZone))
	? $sDefaultTimeZone : 'US/Pacific');

if (defined('AU_API_SERVER_TIME_ZONE') && function_exists('date_default_timezone_set'))
{
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
	static $oModuleManager;

	/**
	 * @var array
	 */
	static $aModuleDecorators;

	/**
	 * @var array
	 */
	static $aSecretWords;
	
	/**
	 * @var bool
	 */
	static $bIsValid;

	/**
	 * @var string
	 */
	static $sSalt;

	/**
	 * @var array
	 */
	static $aI18N;

	/**
	 * @var array
	 */
	static $aClientI18N;

	/**
	 * @var bool
	 */
	static $bUseDbLog;
	
	/**
	 * @var bool
	 */
	static $bDebug = false;
		
	
	/**
	 * @var string
	 */
	public static $sEventLogPrefix = 'event-';
	
	/**
	 * @var array
	 */
	protected static $aUserSession = array();
	
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
	 * @var \Aurora\System\Db\Storage
	 */
	protected static $oConnection;	
	
	/**
	 * 
	 * @return string
	 */
	public static function GetSaltPath()
	{
		return self::DataPath().'/salt8.php';
	}
	
	/**
	 * 
	 */
	public static function InitSalt()
	{
		$sSalt = '';
		$sSalt8File = self::GetSaltPath();
		$sSaltFile = self::DataPath().'/salt.php';

		if (!@file_exists($sSalt8File)) 
		{
			if (@file_exists($sSaltFile))
			{
				$sSalt = md5(@file_get_contents($sSaltFile));
				@unlink($sSaltFile);
			}
			else
			{
				$sSalt = base64_encode(microtime(true).rand(1000, 9999).microtime(true).rand(1000, 9999));
			}
			$sSalt = '<?php \\Aurora\\System\\Api::$sSalt = "'. $sSalt . '";';
			@file_put_contents($sSalt8File, $sSalt);
		}
		
		include_once $sSalt8File;
		self::$sSalt = '$2y$07$' . self::$sSalt . '$';
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
	 * @param type $bGrantAdminPrivileges
	 */
	public static function Init($bGrantAdminPrivileges = false)
	{
		include_once self::GetVendorPath().'autoload.php';
		
		if ($bGrantAdminPrivileges)
		{
			self::GrantAdminPrivileges();
		}

		self::$aI18N = null;
		self::$aClientI18N = array();
		self::$aSecretWords = array();
		self::$bUseDbLog = false;

		if (!is_object(self::$oModuleManager)) 
		{
			self::InitSalt();

			self::$bIsValid = self::validateApi();
			self::GetModuleManager()->init();
			self::$aModuleDecorators = array();
			
			self::removeOldLogs();
		}
	}

	/**
	 * 
	 * @param type $bSkip
	 * @return bool Previous state
	 */
	public static function skipCheckUserRole($bSkip)
	{
		$bReult = self::$__SKIP_CHECK_USER_ROLE__;
		self::$__SKIP_CHECK_USER_ROLE__ = $bSkip;
		return $bReult;
	}
	
	/**
	 * @param string $sWord
	 *
	 * @return bool
	 */
	public static function AddSecret($sWord)
	{
		if (0 < \strlen(\trim($sWord))) 
		{
			self::$aSecretWords[] = $sWord;
			self::$aSecretWords = \array_unique(self::$aSecretWords);
		}
	}
	
	/**
	 * @return string
	 */
	public static function EncodeKeyValues(array $aValues)
	{
		return Utils::UrlSafeBase64Encode(
			Utils\Crypt::XxteaEncrypt(
				@\serialize($aValues), 
				\md5(self::$sSalt)
			)
		);
	}

	/**
	 * @return array
	 */
	public static function DecodeKeyValues($sEncodedValues)
	{
		$aResult = @\unserialize(
			Utils\Crypt::XxteaDecrypt(
			Utils::UrlSafeBase64Decode($sEncodedValues), \md5(self::$sSalt))
		);

		return \is_array($aResult) ? $aResult : array();
	}

	/**
	 * 
	 * @return \Aurora\System\Module\Manager
	 */
	public static function GetModuleManager()
	{
		if (!isset(self::$oModuleManager))
		{
			self::$oModuleManager = Module\Manager::createInstance();
		}
		
		return self::$oModuleManager;
	}
	
	/**
	 * 
	 * @param string $sModuleName
	 * @param int $iUser
	 * @return type
	 */
	public static function GetModuleDecorator($sModuleName, $iUser = null)
	{
		if (!isset(self::$aModuleDecorators[$sModuleName]) && self::GetModule($sModuleName) !== false)
		{
			self::$aModuleDecorators[$sModuleName] = new Module\Decorator($sModuleName, $iUser);
		}
		
		return isset(self::$aModuleDecorators[$sModuleName]) ? self::$aModuleDecorators[$sModuleName] : false;
	}

	/**
	 * 
	 * @param type $sModuleName
	 * @return type
	 */
	public static function GetModule($sModuleName)
	{
		return self::GetModuleManager()->GetModule($sModuleName);
	}
	
	public static function GetModules()
	{
		return self::GetModuleManager()->GetModules();
	}	

	/**
	 * 
	 * @param type $sMethodName
	 * @param type $aParameters
	 * @return type
	 */
	public static function ExecuteMethod($sMethodName, $aParameters = array())
	{
		list($sModuleName, $sMethodName) = explode(Module\AbstractModule::$Delimiter, $sMethodName);
		$oModule = self::GetModule($sModuleName);
		if ($oModule instanceof Module\AbstractModule)
		{
			return $oModule->CallMethod($sModuleName, $sMethodName, $aParameters);
		}
	}

	/**
	 * @return \MailSo\Cache\CacheClient
	 */
	public static function Cacher()
	{
		static $oCacher = null;
		if (null === $oCacher)
		{
			$oCacher = \MailSo\Cache\CacheClient::NewInstance();
			$oCacher->SetDriver(\MailSo\Cache\Drivers\File::NewInstance(self::DataPath().'/cache'));
			$oCacher->SetCacheIndex(self::Version());
		}

		return $oCacher;
	}
	
	/**
	 * @return \MailSo\Cache\CacheClient
	 */
	public static function UserSession()
	{
		static $oSession = null;
		if (null === $oSession)
		{
			$oSession = new UserSession();
		}

		return $oSession;
	}	

	/**
	 * @return \Aurora\System\Settings
	 */
	public static function &GetSettings()
	{
		if (null === self::$oSettings)
		{
			try
			{
				$sSettingsPath = \Aurora\System\Api::DataPath() . '/settings/';
				if (!\file_exists($sSettingsPath))
				{
					set_error_handler(function() {});					
					mkdir($sSettingsPath, 0777);
					restore_error_handler();
					if (!file_exists($sSettingsPath))
					{
						self::$oSettings = false;
						return self::$oSettings;
					}
				}
				
				self::$oSettings = new \Aurora\System\Settings($sSettingsPath . 'config.json');
			}
			catch (\Aurora\System\Exceptions\BaseException $oException)
			{
				self::$oSettings = false;
			}
		}
		return self::$oSettings;		
	}
	
	public static function &GetConnection()
	{
		if (null === self::$oConnection)
		{
			$oSettings =& self::GetSettings();
			if ($oSettings)
			{
				self::$oConnection = new \Aurora\System\Db\Storage($oSettings);
			}
			else
			{
				self::$oConnection = false;
			}
		}
		return self::$oConnection;
	}	

	/**
	 * @return PDO|false
	 */
	public static function GetPDO()
	{
		static $oPdoCache = null;
		if (null !== $oPdoCache)
		{
			return $oPdoCache;
		}

		$oPdo = false;
		$oSettings = &self::GetSettings();
		if ($oSettings)
		{
			$sDbPort = '';
			$sUnixSocket = '';

			$iDbType = $oSettings->GetConf('DBType');
			$sDbHost = $oSettings->GetConf('DBHost');
			$sDbName = $oSettings->GetConf('DBName');
			$sDbLogin = $oSettings->GetConf('DBLogin');
			$sDbPassword = $oSettings->GetConf('DBPassword');

			$iPos = strpos($sDbHost, ':');
			if (false !== $iPos && 0 < $iPos) 
			{
				$sAfter = substr($sDbHost, $iPos + 1);
				$sDbHost = substr($sDbHost, 0, $iPos);

				if (is_numeric($sAfter)) 
				{
					$sDbPort = $sAfter;
				} 
				else 
				{
					$sUnixSocket = $sAfter;
				}
			}

			if (class_exists('PDO')) 
			{
				try
				{
					$oPdo = @new \PDO((Enums\DbType::PostgreSQL === $iDbType ? 'pgsql' : 'mysql').':dbname='.$sDbName.
						(empty($sDbHost) ? '' : ';host='.$sDbHost).
						(empty($sDbPort) ? '' : ';port='.$sDbPort).
						(empty($sUnixSocket) ? '' : ';unix_socket='.$sUnixSocket), $sDbLogin, $sDbPassword);

					if ($oPdo) 
					{
						$oPdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
					}
				}
				catch (\Exception $oException)
				{
					self::Log($oException->getMessage(), Enums\LogLevel::Error);
					self::Log($oException->getTraceAsString(), Enums\LogLevel::Error);
					$oPdo = false;
				}
			} 
			else 
			{
				self::Log('Class PDO dosn\'t exist', Enums\LogLevel::Error);
			}
		}

		if (false !== $oPdo) 
		{
			$oPdoCache = $oPdo;
		}

		return $oPdo;
	}

	/**
	 * @return bool
	 */
	public static function IsMobileApplication()
	{
		/* @var $oIntegrator \Aurora\Modules\Core\Managers\Integrator */
		$oIntegrator = \Aurora\Modules\Core\Managers\Integrator::getInstance();

		return (bool) $oIntegrator /*&& $oApiCapability->isNotLite()*/ && 1 === $oIntegrator->isMobile(); // todo
	}
	
	/**
	 * @param string $sNewLocation
	 */
	public static function Location($sNewLocation)
	{
		self::Log('Location: '.$sNewLocation);
		@header('Location: '.$sNewLocation);
	}
	
	/**
	 * @param string $sNewLocation
	 */
	public static function Location2($sNewLocation)
	{
		exit('<META HTTP-EQUIV="refresh" CONTENT="0; url='.$sNewLocation.'">');		
	}

	/**
	 * @param string $sDesc
	 * @param string $sModuleName
	 */
	public static function LogEvent($sDesc, $sModuleName = '')
	{
		$oSettings = &self::GetSettings();
		if ($oSettings && $oSettings->GetConf('EnableEventLogging')) 
		{
			$sDate = gmdate('H:i:s');
			$iIp = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'unknown';
			$sUserId = self::getAuthenticatedUserId();

			self::Log('Event: '.$sUserId.' > '.$sDesc);
			self::LogOnly('['.$sDate.']['.$iIp.']['.$sUserId.']['.$sModuleName.'] > '.$sDesc, self::GetLogFileDir().self::GetLogFileName(self::$sEventLogPrefix));
		}
	}

	/**
	 * @param mixed $mObject
	 * @param int $iLogLevel = \Aurora\System\Enums\LogLevel::Full
	 * @param string $sFilePrefix = ''
	 */
	public static function LogObject($mObject, $iLogLevel = Enums\LogLevel::Full, $sFilePrefix = '')
	{
		self::Log(print_r($mObject, true), $iLogLevel, $sFilePrefix);
	}

	/**
	 * @param Exception $mObject
	 * @param int $iLogLevel = \Aurora\System\Enums\LogLevel::Error
	 * @param string $sFilePrefix = ''
	 */
	public static function LogException($mObject, $iLogLevel = Enums\LogLevel::Error, $sFilePrefix = '')
	{
		$sMessage = '';

		$oSettings =& self::GetSettings();
		if ($oSettings && $oSettings->GetConf('LogStackTrace', false))
		{
			$sMessage = (string) $mObject;
		}
		else
		{
			$sMessage = $mObject->getMessage();
		}		
		
		if (0 < \count(self::$aSecretWords)) 
		{
			$sMessage = \str_replace(self::$aSecretWords, '*******', $sMessage);
		}
		
		self::Log($sMessage, $iLogLevel, $sFilePrefix);
	}

	/**
	 * @param string $sFilePrefix = ''
	 *
	 * @return string
	 */
	public static function GetLogFileName($sFilePrefix = '', $iTimestamp = 0)
	{
		$oSettings =& self::GetSettings();

		$sFileName = "log.txt";
		
		if ($oSettings && $oSettings->GetConf('LogFileName'))
		{
			$fCallback = ($iTimestamp === 0) 
					? function ($matches) {return date($matches[1]);} 
					: function ($matches) use ($iTimestamp) {return date($matches[1], $iTimestamp);};
			$sFileName = preg_replace_callback('/\{([\w|-]*)\}/', $fCallback, $oSettings->GetConf('LogFileName'));
		}
		
		return $sFilePrefix.$sFileName;
	}
	
	public static function GetLogFileDir()
	{
		static $bDir = null;
		static $sLogDir = null;

		if (null === $sLogDir) 
		{
			$oSettings =& self::GetSettings();
			if ($oSettings)
			{
				$sS = $oSettings->GetConf('LogCustomFullPath', '');
				$sLogDir = empty($sS) ?self::DataPath().'/logs/' : rtrim(trim($sS), '\\/').'/';
			}
		}
		
		if (null === $bDir) 
		{
			$bDir = true;
			if (!@is_dir($sLogDir)) 
			{
				@mkdir($sLogDir, 0777);
			}
		}
		
		return $sLogDir;
	}

	/**
	 * @return \MailSo\Log\Logger
	 */
	public static function SystemLogger()
	{
		static $oLogger = null;
		if (null === $oLogger) 
		{
			$oLogger = \MailSo\Log\Logger::NewInstance()
				->Add(
					\MailSo\Log\Drivers\Callback::NewInstance(function ($sDesc) {
						self::Log($sDesc);
					})->DisableTimePrefix()->DisableGuidPrefix()
				)
				->AddForbiddenType(\MailSo\Log\Enumerations\Type::TIME)
			;
		}

		return $oLogger;
	}

	/**
	 * @param string $sDesc
	 * @param string $sLogFile
	 */
	private static function dbDebugBacktrace($sDesc, $sLogFile)
	{
		static $iDbBacktraceCount = null;
		
		if (null === $iDbBacktraceCount) 
		{
			$oSettings =& Api::GetSettings();
			$iDbBacktraceCount = (int) $oSettings->GetConf('DBDebugBacktraceLimit', 0);
			if (!function_exists('debug_backtrace') || version_compare(PHP_VERSION, '5.4.0') < 0) 
			{
				$iDbBacktraceCount = 0;
			}
		}

		if (0 < $iDbBacktraceCount && is_string($sDesc) && 
				(false !== strpos($sDesc, 'DB[') || false !== strpos($sDesc, 'DB ERROR'))) 
		{
			$bSkip = true;
			$sLogData = '';
			$iCount = $iDbBacktraceCount;

			foreach (debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 20) as $aData) 
			{
				if ($aData && isset($aData['function']) && !in_array(strtolower($aData['function']), array(
					'log', 'logonly', 'logend', 'logevent', 'logexception', 'logobject', 'dbdebugbacktrace')))	
				{
					$bSkip = false;
				}

				if (!$bSkip) 
				{
					$iCount--;
					if (isset($aData['class'], $aData['type'], $aData['function'])) 
					{
						$sLogData .= $aData['class'].$aData['type'].$aData['function'];
					} 
					else if (isset($aData['function'])) 
					{
						$sLogData .= $aData['function'];
					}

					if (isset($aData['file'])) 
					{
						$sLogData .= ' ../'.basename($aData['file']);
					}
					if (isset($aData['line'])) 
					{
						$sLogData .= ' *'.$aData['line'];
					}

					$sLogData .= "\n";
				}

				if (0 === $iCount) 
				{
					break;
				}
			}

			if (0 < strlen($sLogData)) 
			{
				try
				{
					@error_log('['.\MailSo\Log\Logger::Guid().'][DB/backtrace]'.AU_API_CRLF.trim($sLogData).AU_API_CRLF, 3, $sLogFile);
				}
				catch (Exception $oE) {}
			}
		}
	}

	/**
	 * @param string $sDesc
	 * @param int $iLogLevel = \Aurora\System\Enums\LogLevel::Full
	 * @param string $sFilePrefix = ''
	 * @param bool $bIdDb = false
	 */
	public static function Log($sDesc, $iLogLevel = Enums\LogLevel::Full, $sFilePrefix = '')
	{
		static $bIsFirst = true;

		$oSettings = &self::GetSettings();

		if ($oSettings && $oSettings->GetConf('EnableLogging') && $iLogLevel <= $oSettings->GetConf('LoggingLevel')) 
		{
			try 
			{
				$oAuthenticatedUser = self::getAuthenticatedUser();
			}
			catch (\Exception $oEx)
			{
				$oAuthenticatedUser = false;
			}
			$sFirstPrefix = $oAuthenticatedUser && $oAuthenticatedUser->WriteSeparateLog ? $oAuthenticatedUser->PublicId . '-' : '';
			$sLogFile = self::GetLogFileDir() . self::GetLogFileName($sFirstPrefix . $sFilePrefix);

			$sGuid = \MailSo\Log\Logger::Guid();
			$aMicro = explode('.', microtime(true));
			$sDate = gmdate('H:i:s.').str_pad((isset($aMicro[1]) ? substr($aMicro[1], 0, 2) : '0'), 2, '0');
			if ($bIsFirst) 
			{
				$sUri = Utils::RequestUri();
				$bIsFirst = false;
				$sPost = (isset($_POST) && count($_POST) > 0) ? '[POST('.count($_POST).')]' : '[GET]';

				self::LogOnly(AU_API_CRLF.'['.$sDate.']['.$sGuid.'] '.$sPost.'[ip:'.(isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'unknown').'] '.$sUri, $sLogFile);
				if (!empty($sPost)) 
				{
					if ($oSettings->GetConf('LogPostView', false)) 
					{
						self::LogOnly('['.$sDate.']['.$sGuid.'] POST > '.print_r($_POST, true), $sLogFile);
					} 
					else 
					{
						self::LogOnly('['.$sDate.']['.$sGuid.'] POST > ['.implode(', ', array_keys($_POST)).']', $sLogFile);
					}
				}
				self::LogOnly('['.$sDate.']['.$sGuid.']', $sLogFile);
			}

			self::LogOnly('['.$sDate.']['.$sGuid.'] '.(is_string($sDesc) ? $sDesc : print_r($sDesc, true)), $sLogFile);
		}
	}

	/**
	 * @param string $sDesc
	 * @param string $sLogFile
	 */
	public static function LogOnly($sDesc, $sLogFile)
	{
		try
		{
			@error_log($sDesc.AU_API_CRLF, 3, $sLogFile);
		}
		catch (Exception $oE) {}

		self::dbDebugBacktrace($sDesc, $sLogFile);
	}

	public static function ClearLog($sFileFullPath)
	{
		return (@file_exists($sFileFullPath)) ? @unlink($sFileFullPath) : true;
	}
	
	public static function RemoveSeparateLogs()
	{
		$sLogDir = self::GetLogFileDir();
		$sLogFile = self::GetLogFileName();
		if (is_dir($sLogDir))
		{
			$aLogFiles = array_diff(scandir($sLogDir), array('..', '.'));
			foreach($aLogFiles as $sFileName)
			{
				if ($sFileName !== $sLogFile && $sFileName !== (self::$sEventLogPrefix . $sLogFile) && strpos($sFileName, $sLogFile) !== false)
				{
					unlink($sLogDir.$sFileName);
				}
			}
		}
	}

	private static function removeOldLogs()
	{
		$sLogDir = self::GetLogFileDir();
		$sLogFile = self::GetLogFileName();
		$oSettings = &self::GetSettings();
		
		if ($oSettings)
		{
			$bRemoveOldLogs = $oSettings->GetConf('RemoveOldLogs', true);

			if (is_dir($sLogDir) && $bRemoveOldLogs/* && !file_exists($sLogDir.$sLogFile)*/)
			{
				$sYesterdayLogFile = self::GetLogFileName('', time() - 60 * 60 * 24);
				$aLogFiles = array_diff(scandir($sLogDir), array('..', '.'));
				foreach($aLogFiles as $sFileName)
				{
					if (strpos($sFileName, $sLogFile) === false && strpos($sFileName, $sYesterdayLogFile) === false)
					{
						unlink($sLogDir.$sFileName);
					}
				}
			}
		}
	}

	/**
	 * @return string
	 */
	public static function RootPath()
	{
		defined('AU_API_ROOTPATH') || define('AU_API_ROOTPATH', rtrim(dirname(__FILE__), '/\\').'/');
		return AU_API_ROOTPATH;
	}

	/**
	 * @return string
	 */
	public static function WebMailPath()
	{
		return self::RootPath().ltrim(AU_API_PATH_TO_AURORA, '/');
	}

	/**
	 * @return string
	 */
	public static function GetVendorPath()
	{
		return self::RootPath().'../vendor/';
	}

	/**
	 * @return string
	 */
	public static function VersionFull()
	{
		static $sVersion = null;
		$sAppVersion = @file_get_contents(self::WebMailPath().'VERSION');

		$sVersion = (empty($sAppVersion)) ? '0.0.0' : $sAppVersion;

		return $sVersion;
	}

	/**
	 * @return string
	 */
	public static function Version()
	{
		static $sVersion = null;
		if (null === $sVersion) 
		{
			preg_match('/[\d\.]+/', @file_get_contents(self::WebMailPath().'VERSION'), $matches);

			if (isset($matches[0]))
			{
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
		$sAppVersion = @file_get_contents(self::WebMailPath().'VERSION');
		$sAppVersion = empty($sAppVersion) ? '0.0.0' : $sAppVersion;
		
		return preg_replace('/[^0-9]/', '',$sAppVersion);
	}

	/**
	 * @return string
	 */
	public static function DataPath()
	{
		$dataPath = 'data';
		if (!defined('AU_API_DATA_FOLDER') && @file_exists(self::WebMailPath().'inc_settings_path.php')) 
		{
			include self::WebMailPath().'inc_settings_path.php';
		}
		if (!defined('AU_API_DATA_FOLDER') && isset($dataPath) && null !== $dataPath) 
		{
			define('AU_API_DATA_FOLDER', Utils::GetFullPath($dataPath,self::WebMailPath()));
		}
		$sDataFullPath = defined('AU_API_DATA_FOLDER') ? AU_API_DATA_FOLDER : '';

/*
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

		return (bool) $iResult;
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
		$sSsoHash = \md5($sEmail.$sPassword.$sLogin.\microtime(true).\rand(10000, 99999));
		return self::Cacher()->Set('SSO:'.$sSsoHash, self::EncodeKeyValues(array(
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
		if (is_array($aLang)) 
		{
			$aResultLang = array();
			foreach ($aLang as $sKey => $mValue) 
			{
				if (\is_array($mValue)) 
				{
					foreach ($mValue as $sSecKey => $mSecValue) 
					{
						$aResultLang[$sKey.'/'.$sSecKey] = $mSecValue;
					}
				} 
				else 
				{
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
		if ($mLang && isset($mLang[$sData])) 
		{
			$sResult = $mLang[$sData];
		}

		if (isset($iPlural)) 
		{
			$aPluralParts = explode('|', $sResult);

			$sResult = ($aPluralParts && $aPluralParts[$iPlural]) ? $aPluralParts[$iPlural] : (
			$aPluralParts && $aPluralParts[0] ? $aPluralParts[0] : $sResult);
		}

		if (null !== $aParams && is_array($aParams)) 
		{
			foreach ($aParams as $sKey => $sValue) 
			{
				$sResult = str_replace('%'.$sKey.'%', $sValue, $sResult);
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
		if (isset(self::$sLanguage))
		{
			$sResult = self::$sLanguage;
		}
		else
		{
			$iAuthUserId = self::getAuthenticatedUserId();
			$bSuperAdmin = $iAuthUserId === -1;
			$oModuleManager = self::GetModuleManager();

			$sResult = $oModuleManager->getModuleConfigValue('Core', 'Language');
			if ($oModuleManager->getModuleConfigValue('Core', 'AutodetectLanguage', true))
			{
				$sResult = self::getBrowserLanguage();
			}

			if ($bSuperAdmin)
			{
				$oSettings = &self::GetSettings();
				$sResult = $oSettings->GetConf('AdminLanguage');
			}
			else if (!$bForNewUser)
			{
				$oUser = self::getAuthenticatedUser();
				if ($oUser)
				{
					$sResult = $oUser->Language;
				}
				else if (isset($_COOKIE['aurora-lang-on-login']))
				{
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
	 * @param \Aurora\Modules\StandardAuth\Classes\Account $oAccount
	 * @param array $aParams = null
	 *
	 * @return string
	 */
	public static function ClientI18N($sData, $oAccount = null, $aParams = null, $iPluralCount = null)
	{
		$sLanguage = self::GetLanguage();
		
		$aLang = null;
		if (isset(self::$aClientI18N[$sLanguage])) 
		{
			$aLang = self::$aClientI18N[$sLanguage];
		} 
		else 
		{
			self::$aClientI18N[$sLanguage] = false;
				
			$sLangFile = self::WebMailPath().'i18n/'.$sLanguage.'.ini';
			if (!@file_exists($sLangFile)) 
			{
				$sLangFile = self::WebMailPath().'i18n/English.ini';
				$sLangFile = @file_exists($sLangFile) ? $sLangFile : '';
			}

			if (0 < strlen($sLangFile)) 
			{
				$aLang = self::convertIniToLang($sLangFile);
				if (is_array($aLang)) 
				{
					self::$aClientI18N[$sLanguage] = $aLang;
				}
			}
		}

		//return self::processTranslateParams($aLang, $sData, $aParams);
		return isset($iPluralCount) ? self::processTranslateParams($aLang, $sData, $aParams, self::getPlural($sLanguage, $iPluralCount)) : self::processTranslateParams($aLang, $sData, $aParams);
	}

	public static function getPlural($sLang = '', $iNumber = 0)
	{
		$iResult = 0;
		$iNumber = (int) $iNumber;

		switch ($sLang)
		{
			case 'Arabic':
				$iResult = ($iNumber === 0 ? 0 : $iNumber === 1 ? 1 : ($iNumber === 2 ? 2 : ($iNumber % 100 >= 3 && $iNumber % 100 <= 10 ? 3 : ($iNumber % 100 >= 11 ? 4 : 5))));
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
		if (null === self::$aI18N) 
		{
			self::$aI18N = false;

			$sLangFile = '';
			if (0 < strlen($sForceCustomInitialisationLang))
			{
				$sLangFile = self::RootPath().'common/i18n/'.$sForceCustomInitialisationLang.'.ini';
			}

			if (0 === strlen($sLangFile) || !@file_exists($sLangFile))
			{
				$sLangFile = self::RootPath().'common/i18n/English.ini';
			}

			if (0 < strlen($sLangFile) && @file_exists($sLangFile))
			{
				$aResultLang = self::convertIniToLang($sLangFile);
				if (is_array($aResultLang))
				{
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
		if (!self::$__SKIP_CHECK_USER_ROLE__)
		{
			$oUser = self::getAuthenticatedUser();
			$bUserRoleIsAtLeast = empty($oUser) && $iRole === Enums\UserRole::Anonymous ||
				!empty($oUser) && $oUser->Role === Enums\UserRole::Customer && 
					($iRole === Enums\UserRole::Customer || $iRole === Enums\UserRole::Anonymous) ||
				!empty($oUser) && $oUser->Role === Enums\UserRole::NormalUser && 
					($iRole === Enums\UserRole::NormalUser || $iRole === Enums\UserRole::Customer || $iRole === Enums\UserRole::Anonymous) ||
				!empty($oUser) && $oUser->Role === Enums\UserRole::TenantAdmin && 
					($iRole === Enums\UserRole::TenantAdmin || $iRole === Enums\UserRole::NormalUser || $iRole === Enums\UserRole::Customer || $iRole === Enums\UserRole::Anonymous) ||
				!empty($oUser) && $oUser->Role === Enums\UserRole::SuperAdmin && 
					($iRole === Enums\UserRole::SuperAdmin || $iRole === Enums\UserRole::TenantAdmin || $iRole === Enums\UserRole::NormalUser || $iRole === Enums\UserRole::Customer || $iRole === Enums\UserRole::Anonymous);
			if (!$bUserRoleIsAtLeast)
			{
				throw new Exceptions\ApiException(Notifications::AccessDenied);
			}
		}
	}
	
	public static function getAuthTokenFromHeaders()
	{
		$sResult = false;
		$sAuthHeader =  \MailSo\Base\Http::SingletonInstance()->GetHeader('Authorization');
		if (!empty($sAuthHeader))
		{
			list($sAuthTypeFromHeader, $sAuthTokenFromHeader) = explode(' ', $sAuthHeader);
			if (strtolower($sAuthTypeFromHeader) === 'bearer' && !empty($sAuthTokenFromHeader))
			{
				$sResult = $sAuthTokenFromHeader;
			}
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
		if (!$sAuthToken)
		{
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
		if (isset($_COOKIE[Application::AUTH_TOKEN_KEY]))
		{
			$sAuthToken = self::getAuthTokenFromHeaders();

			$bResult = ($sAuthToken === $_COOKIE[Application::AUTH_TOKEN_KEY]);
		}
		
		return $bResult;
	}		

	/**
	 * 
	 * @return \Aurora\Modules\Core\Classes\User
	 */
	public static function authorise($sAuthToken = '')
	{
		$oUser = null;
		$mUserId = false;
		try
		{
			if (isset(self::$aUserSession['UserId']))
			{
				$mUserId = self::$aUserSession['UserId'];
			}
			else
			{
				$sAuthToken = empty($sAuthToken) ? self::getAuthToken() : $sAuthToken;
				$mUserId = self::getAuthenticatedUserId($sAuthToken);
			}
			$oUser = self::getUserById($mUserId);
		}
		catch (\Exception $oException) {}
		return $oUser;
	}	
	
	public static function getAuthenticatedUserInfo($sAuthToken = '')
	{
		$mResult = false;
		if (empty($sAuthToken))
		{
			if (is_array(self::$aUserSession) && isset(self::$aUserSession['AuthToken']))
			{
				$sAuthToken = self::$aUserSession['AuthToken'];
			}
		}
		/* @var $oIntegrator \Aurora\Modules\Core\Managers\Integrator */
		$oIntegrator = \Aurora\Modules\Core\Managers\Integrator::getInstance();
		if ($oIntegrator)
		{
			$mResult = $oIntegrator->getAuthenticatedUserInfo($sAuthToken);
		}
		
		return $mResult;
	}

	public static function validateAuthToken()
	{
		$bResult = false;
		/* @var $oIntegrator \Aurora\Modules\Core\Managers\Integrator */
		$oIntegrator = \Aurora\Modules\Core\Managers\Integrator::getInstance();
		if ($oIntegrator)
		{
			$bResult = $oIntegrator->validateAuthToken(self::getAuthToken());
		}
		
		return $bResult;
	}
	
	public static function getCookiePath()
	{
		static $sPath = false;
		
		if (false === $sPath)
		{
			$sScriptName = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '';
			$aPath = explode('/', $sScriptName);
			$sLastPathItem = count($aPath) > 0 ? $aPath[count($aPath) - 1] : '';
			if (count($aPath) > 0 && ($sLastPathItem !== '' || tolowercase(substr($sLastPathItem, -1)) === '.php'))
			{
				array_pop($aPath);
			}
			$sPath = implode('/', $aPath) . '/';
		}

		return $sPath;
	}
	
	public static function getAuthenticatedUserId($sAuthToken = '')
	{
		$mResult = false;
		if (!empty($sAuthToken))
		{
			if (!empty(self::$aUserSession['UserId']) && self::getAuthenticatedUserAuthToken() === $sAuthToken)
			{
				$mResult = (int) self::$aUserSession['UserId'];
			}
			else
			{
				/* @var $oIntegrator \Aurora\Modules\Core\Managers\Integrator */
				$oIntegrator = \Aurora\Modules\Core\Managers\Integrator::getInstance();
				if ($oIntegrator)
				{
					$aInfo = $oIntegrator->getAuthenticatedUserInfo($sAuthToken);
					$mResult = $aInfo['userId'];
					self::$aUserSession['UserId'] = (int) $mResult;
					self::$aUserSession['AuthToken'] = $sAuthToken;
				}
			}
		}
		else 
		{
			if (is_array(self::$aUserSession) && isset(self::$aUserSession['UserId']))
			{
				$mResult = self::$aUserSession['UserId'];
			}
			else
			{
				$mResult = 0;
			}
		}
		
		return $mResult;
	}
	
	public static function getAuthenticatedUser($sAuthToken = '')
	{
		static $oUser = null;
		$sStoredAuthToken = self::getAuthenticatedUserAuthToken();
		if ($oUser === null || (!empty($sAuthToken) && $sAuthToken !== $sStoredAuthToken))
		{
			$iUserId = 0;
			if (!empty($sAuthToken))
			{
				$iUserId = self::getAuthenticatedUserId($sAuthToken); // called for saving in session
			}
			else if (!empty(self::$aUserSession['UserId']))
			{
				$iUserId = self::$aUserSession['UserId'];
			}

			$oIntegrator = \Aurora\Modules\Core\Managers\Integrator::getInstance();
			if ($oIntegrator)
			{
				$oUser = $oIntegrator->getAuthenticatedUserByIdHelper($iUserId);
			}
		}
		return $oUser;
	}
	
	public static function getAuthenticatedUserAuthToken()
	{
		$mResult = false;
		
		if (is_array(self::$aUserSession) && isset(self::$aUserSession['AuthToken']))
		{
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
		static $aUUIDs = []; // cache
		
		if (\is_numeric($iUserId))
		{
			if (isset($aUUIDs[$iUserId]))
			{
				$sUUID = $aUUIDs[$iUserId];
			}
			else
			{
				$mUser = self::getUserById($iUserId);
				if ($mUser instanceof EAV\Entity)
				{
					$sUUID = $mUser->UUID;
					$aUUIDs[$iUserId] = $sUUID;
				}
			}
		}
		else 
		{
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
		
		if (\is_numeric($iUserId))
		{
			$mUser = self::getUserById($iUserId);
			if ($mUser instanceof EAV\Entity)
			{
				$sPublicId = $mUser->PublicId;
			}
		}
		else 
		{
			$sPublicId = $iUserId;
		}
		
		return $sPublicId;
	}	
	
	public static function getUserById($iUserId)
	{
		$oManagerApi = new Managers\Eav();
		$mUser = $oManagerApi->getEntity($iUserId, \Aurora\Modules\Core\Classes\User::class);
		if (!($mUser instanceof EAV\Entity))
		{
			$mUser = false;
		}
		
		return $mUser;
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

	/**
	 * 
	 * @return string
	 */
	public static function getTenantName()
	{
		$mResult = false;

		if (is_array(self::$aUserSession) && isset(self::$aUserSession['TenantName']))
		{
			$mResult = self::$aUserSession['TenantName'];
		}
		else
		{
			$mEventResult = null;
			self::GetModuleManager()->broadcastEvent('System', 'DetectTenant', array(
				array (
					'URL' => $_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']
				),
				&$mEventResult
			));
			
			if ($mEventResult)
			{
				$mResult = $mEventResult;
			}
		}
		
		return $mResult;
	}
}
