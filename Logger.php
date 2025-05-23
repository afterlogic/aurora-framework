<?php
/**
 * This code is licensed under AGPLv3 license or Afterlogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

namespace Aurora\System;

use DateTime;
use DateTimeImmutable;
use Illuminate\Support\Str;

/**
 * @license https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @license https://afterlogic.com/products/common-licensing Afterlogic Software License
 * @copyright Copyright (c) 2019, Afterlogic Corp.
 *
 * @category Core
 */
class Logger
{
    /**
     * @var string
     */
    public static $sEventLogPrefix = 'event-';

    /**
     * @var string
     */
    public static $sErrorLogPrefix = 'error-';

    /**
     * @param string $sDesc
     * @param string $sModuleName
     */
    public static function LogEvent($sDesc, $sModuleName = '')
    {
        $oSettings = &Api::GetSettings();
        if ($oSettings && $oSettings->EnableEventLogging) {
            $sDate = gmdate('H:i:s');
            $iIp = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'unknown';
            $sUserId = Api::getAuthenticatedUserId();

            self::Log('Event: ' . $sUserId . ' > ' . $sDesc);
            self::LogOnly('[' . $sDate . '][' . $iIp . '][' . $sUserId . '][' . $sModuleName . '] > ' . $sDesc, self::GetLogFileDir() . self::GetLogFileName(self::$sEventLogPrefix));
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
     * @param Exceptions\Exception $mObject
     * @param int $iLogLevel = \Aurora\System\Enums\LogLevel::Error
     * @param string $sFilePrefix = ''
     */
    public static function LogException($mObject, $iLogLevel = Enums\LogLevel::Error, $sFilePrefix = '')
    {
        $sMessage = '';

        $oSettings = & Api::GetSettings();
        // if ($oSettings && $oSettings->GetValue('LogStackTrace', false))
        // {
        // 	$sMessage = (string) $mObject;
        // }
        // else
        // {
        $sMessage = 'Exception: ' . (string) $mObject . ', Code: ' . $mObject->getCode() . ', Message: ' . $mObject->getMessage();
        // }

        if (0 < \count(Api::$aSecretWords)) {
            $sMessage = \str_replace(Api::$aSecretWords, '*******', $sMessage);
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
        $oSettings = & Api::GetSettings();

        $sFileName = "log.txt";

        if ($oSettings && $oSettings->LogFileName) {
            $fCallback = ($iTimestamp === 0)
                    ? function ($matches) {
                        return date($matches[1]);
                    }
                    : function ($matches) use ($iTimestamp) {
                        return date($matches[1], $iTimestamp);
                    };
            $sFileName = preg_replace_callback('/\{([\w|-]*)\}/', $fCallback, $oSettings->LogFileName);
        }

        return $sFilePrefix . $sFileName;
    }

    public static function getLogFileDateFormat()
    {
        $result = '';
        $oSettings = & Api::GetSettings();
        if ($oSettings && $oSettings->LogFileName) {
            preg_match('/\{([\w|-]*)\}/', $oSettings->LogFileName, $matches);
            if (isset($matches[1])) {
                $result = $matches[1];
            }
        }
        return $result;
    }

    public static function GetLogFileDir()
    {
        static $bDir = null;
        static $sLogDir = null;

        if (null === $sLogDir) {
            $oSettings = & Api::GetSettings();
            if ($oSettings) {
                $sS = $oSettings->GetValue('LogCustomFullPath', '');
                $sLogDir = empty($sS) ? Api::DataPath() . '/logs/' : rtrim(trim($sS), '\\/') . '/';
            }
        }

        if (null === $bDir) {
            $bDir = true;
            if (!@is_dir($sLogDir)) {
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
        if (null === $oLogger) {
            $oLogger = \MailSo\Log\Logger::NewInstance();
            $oLogger->Add(
                \MailSo\Log\Drivers\Callback::NewInstance(function ($sDesc) {
                    self::Log($sDesc);
                })->DisableTimePrefix()->DisableGuidPrefix()
            );
            $oLogger->AddForbiddenType(\MailSo\Log\Enumerations\Type::TIME);

            $oSettings = & Api::GetSettings();
            $oLogger->bLogStackTrace = ($oSettings && $oSettings->GetValue('LogStackTrace', false));
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

        if (null === $iDbBacktraceCount) {
            $oSettings = & Api::GetSettings();
            $iDbBacktraceCount = (int) $oSettings->GetValue('DBDebugBacktraceLimit', 0);
            if (!function_exists('debug_backtrace') || version_compare(PHP_VERSION, '5.4.0') < 0) {
                $iDbBacktraceCount = 0;
            }
        }

        if (0 < $iDbBacktraceCount && is_string($sDesc) &&
                (false !== strpos($sDesc, 'DB[') || false !== strpos($sDesc, 'DB ERROR'))) {
            $bSkip = true;
            $sLogData = '';
            $iCount = $iDbBacktraceCount;

            foreach (debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 20) as $aData) {
                if ($aData && isset($aData['function']) && !in_array(strtolower($aData['function']), array(
                    'log', 'logonly', 'logend', 'logevent', 'logexception', 'logobject', 'dbdebugbacktrace'))) {
                    $bSkip = false;
                }

                if (!$bSkip) {
                    $iCount--;
                    if (isset($aData['class'], $aData['type'], $aData['function'])) {
                        $sLogData .= $aData['class'] . $aData['type'] . $aData['function'];
                    } elseif (isset($aData['function'])) {
                        $sLogData .= $aData['function'];
                    }

                    if (isset($aData['file'])) {
                        $sLogData .= ' ../' . basename($aData['file']);
                    }
                    if (isset($aData['line'])) {
                        $sLogData .= ' *' . $aData['line'];
                    }

                    $sLogData .= "\n";
                }

                if (0 === $iCount) {
                    break;
                }
            }

            if (0 < strlen($sLogData)) {
                try {
                    @error_log('[' . \MailSo\Log\Logger::Guid() . '][DB/backtrace]' . AU_API_CRLF . trim($sLogData) . AU_API_CRLF, 3, $sLogFile);
                } catch (\Exception $oE) {
                }
            }
        }
    }

    /**
     * @param string $sDesc
     * @param int $iLogLevel = \Aurora\System\Enums\LogLevel::Full
     * @param string $sFilePrefix = ''
     */
    public static function Log($sDesc, $iLogLevel = Enums\LogLevel::Full, $sFilePrefix = '')
    {
        static $bIsFirst = true;

        $oSettings = &Api::GetSettings();

        if ($oSettings && $oSettings->EnableLogging && $iLogLevel <= $oSettings->LoggingLevel) {
            try {
                $oAuthenticatedUser = Api::getAuthenticatedUser();
            } catch (\Exception $oEx) {
                $oAuthenticatedUser = false;
            }
            $sFirstPrefix = "";
            if ($oAuthenticatedUser) {
                $sFirstPrefix = $oAuthenticatedUser->WriteSeparateLog ? $oAuthenticatedUser->PublicId . '-' : '';
            }
            $sLogFile = self::GetLogFileDir() . self::GetLogFileName($sFirstPrefix . $sFilePrefix);

            $sGuid = \MailSo\Log\Logger::Guid();
            $aMicro = explode('.', microtime(true));
            $sDate = gmdate('H:i:s.') . str_pad((isset($aMicro[1]) ? substr($aMicro[1], 0, 2) : '0'), 2, '0');
            if ($bIsFirst) {
                $sUri = Utils::RequestUri();
                $bIsFirst = false;
                $sPost = (is_array($_POST) && count($_POST) > 0) ? '[POST(' . count($_POST) . ')]' : '[GET]';

                self::LogOnly(AU_API_CRLF . '[' . $sDate . '][' . $sGuid . '] ' . $sPost . '[ip:' . (isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'unknown') . '] ' . $sUri, $sLogFile);

                if ($oSettings->GetValue('LogPostView', false)) {
                    self::LogOnly('[' . $sDate . '][' . $sGuid . '] POST > ' . print_r($_POST, true), $sLogFile);
                } else {
                    self::LogOnly('[' . $sDate . '][' . $sGuid . '] POST > [' . implode(', ', array_keys($_POST)) . ']', $sLogFile);
                }

                self::LogOnly('[' . $sDate . '][' . $sGuid . ']', $sLogFile);
            }

            self::LogOnly('[' . $sDate . '][' . $sGuid . '] ' . (is_string($sDesc) ? $sDesc : print_r($sDesc, true)), $sLogFile);
        }
    }

    /**
     * @param string $sDesc
     * @param string $sLogFile
     */
    public static function LogOnly($sDesc, $sLogFile)
    {
        if (0 < \count(Api::$aSecretWords)) {
            $sDesc = \str_replace(Api::$aSecretWords, '*******', $sDesc);
        }

        try {
            @error_log($sDesc . AU_API_CRLF, 3, $sLogFile);
        } catch (\Exception $oE) {
        }

        self::dbDebugBacktrace($sDesc, $sLogFile);
    }

    public static function LogSql($sDesc, $iLogLevel = Enums\LogLevel::Full)
    {
        if (Api::$bUseDbLog) {
            $oSettings = &Api::GetSettings();

            if ($oSettings && $oSettings->EnableLogging && $iLogLevel <= $oSettings->LoggingLevel) {
                $sLogFile = self::GetLogFileDir() . self::GetLogFileName('sql-');

                $sGuid = \MailSo\Log\Logger::Guid();
                $aMicro = explode('.', microtime(true));
                $sDate = gmdate('H:i:s.') . str_pad((isset($aMicro[1]) ? substr($aMicro[1], 0, 2) : '0'), 2, '0');

                self::LogOnly('[' . $sDate . '][' . $sGuid . '] ' . $sDesc, $sLogFile);
            }
        }
    }

    public static function ClearLog($sFileFullPath)
    {
        return (@file_exists($sFileFullPath)) ? @unlink($sFileFullPath) : true;
    }

    public static function RemoveSeparateLogs()
    {
        $sLogDir = self::GetLogFileDir();
        if (is_dir($sLogDir)) {
            $aLogFiles = array_diff(scandir($sLogDir), array('..', '.'));
            $aUsers = \Aurora\Modules\Core\Module::getInstance()->GetUsersWithSeparateLog();
            foreach ($aLogFiles as $sFileName) {
                foreach ($aUsers as $sUserPublicId) {
                    if (Str::startsWith($sFileName, $sUserPublicId)) {
                        self::ClearLog($sLogDir . $sFileName);
                    }
                }
            }
        }
    }

    public static function RemoveOldLogs()
    {
        $oSettings = &Api::GetSettings();

        if ($oSettings) {
            $bRemoveOldLogs = $oSettings->GetValue('RemoveOldLogs', true);
            $iRemoveOldLogsDays = (int) $oSettings->GetValue('RemoveOldLogsDays', 2);

            $sLogDir = self::GetLogFileDir();
            if (is_dir($sLogDir) && $bRemoveOldLogs) {
                $aLogFiles = glob($sLogDir . '*.txt');
                $nowDateTime = new \DateTimeImmutable(date(self::getLogFileDateFormat()));
                $dateTimeToRemove = $nowDateTime->sub(new \DateInterval(sprintf('P%dD', $iRemoveOldLogsDays)));

                foreach ($aLogFiles as $sFileName) {
                    $aPathInfo = pathinfo($sFileName);
                    preg_match('/\w+\-([\d\-]+)$/', $aPathInfo['filename'], $matches, 0);
                    if (isset($matches[1])) {
                        $fileDateTime = new \DateTimeImmutable($matches[1]);
                        if ($fileDateTime <= $dateTimeToRemove) {
                            if (file_exists($sFileName)) {
                                unlink($sFileName);
                            }
                        }
                    }
                }
            }
        }
    }

    public static function GetLoggerGuid()
    {
        $oSettings = &Api::GetSettings();

        if ($oSettings && $oSettings->EnableLogging) {
            return \MailSo\Log\Logger::Guid();
        }

        return '';
    }
}
