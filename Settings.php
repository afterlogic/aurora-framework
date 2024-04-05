<?php
/**
 * This code is licensed under AGPLv3 license or Afterlogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

namespace Aurora\System;

/**
 * @license https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @license https://afterlogic.com/products/common-licensing Afterlogic Software License
 * @copyright Copyright (c) 2019, Afterlogic Corp.
 *
 * @package Api
 */

/**
* @property string $LicenseKey
* @property string $AdminLogin
* @property string $AdminPassword
* @property string $AdminLanguage
*
* @property \Aurora\System\Enums\DbType $DBType
* @property string $DBPrefix
* @property string $DBHost
* @property string $DBName
* @property string $DBLogin
* @property string $DBPassword

* @property bool $UseSlaveConnection
* @property string $DBSlaveHost
* @property string $DBSlaveName
* @property string $DBSlaveLogin
* @property string $DBSlavePassword
* @property bool $DBUseExplain
* @property bool $DBUseExplainExtended
* @property bool $DBLogQueryParams
* @property bool $DBDebugBacktraceLimit

* @property bool $EnableLogging
* @property bool $EnableEventLogging
* @property int $LoggingLevel
* @property string $LogFileName
* @property string $LogCustomFullPath
* @property bool $LogPostView

* @property bool $EnableMultiChannel
* @property bool $EnableMultiTenant
* @property string $TenantGlobalCapa

* @property bool $AllowThumbnail
* @property int $ThumbnailMaxFileSizeMb
* @property bool $CacheCtrl
* @property bool $CacheLangs
* @property bool $CacheTemplates
* @property bool $DisplayServerErrorInformation
* @property bool $EnableImap4PlainAuth
* @property bool $RedirectToHttps
* @property int $SocketConnectTimeoutSeconds
* @property int $SocketGetTimeoutSeconds
* @property bool $SocketVerifySsl
* @property bool $UseAppMinJs
* @property string $XFrameOptions
* @property bool $RemoveOldLogs
* @property int $RemoveOldLogsDays
* @property bool $LogStackTrace
* @property int $ExpireUserSessionsBeforeTimestamp

* @property int $PasswordMinLength
* @property bool $PasswordMustBeComplex

* @property bool $StoreAuthTokenInDB
* @property int $AuthTokenExpirationLifetimeDays
*/

class Settings extends AbstractSettings
{
    protected function initDefaults()
    {
        $this->aContainer = [
            'LicenseKey' => new SettingsProperty(
                '',
                'string',
                null,
                'License key is supplied here'
            ),

            'AdminLogin' =>  new SettingsProperty(
                'superadmin',
                'string',
                null,
                'Administrative login'
            ),
            'AdminPassword' => new SettingsProperty(
                '',
                'string',
                null,
                'Administrative password (empty by default)'
            ),
            'AdminLanguage' => new SettingsProperty(
                'English',
                'string',
                null,
                'Admin interface language'
            ),

            'DBType' => new SettingsProperty(
                Enums\DbType::MySQL,
                'spec',
                Enums\DbType::class,
                'Database engine used. Currently, only MySQL is supported'
            ),
            'DBPrefix' => new SettingsProperty(
                'au_',
                'string',
                null,
                'Prefix used for database tables names'
            ),
            'DBHost' => new SettingsProperty(
                '127.0.0.1',
                'string',
                null,
                'Denotes hostname or socket path used for connecting to SQL database'
            ),
            'DBName' => new SettingsProperty(
                '',
                'string',
                null,
                'The name of database in SQL server used'
            ),
            'DBLogin' => new SettingsProperty(
                'root',
                'string',
                null,
                'Login for SQL user'
            ),
            'DBPassword' => new SettingsProperty(
                '',
                'string',
                null,
                'Password to access SQL database'
            ),

            'UseSlaveConnection' => new SettingsProperty(
                false,
                'bool',
                null,
                'Set of parameters for separate read/write access to the database. If set to true, the first set of credentials will be used to write to the database while Slave credentials - to read from it'
            ),
            'DBSlaveHost' => new SettingsProperty(
                '127.0.0.1',
                'string',
                null,
                'Slave database hostname or socket path'
            ),
            'DBSlaveName' => new SettingsProperty(
                '',
                'string',
                null,
                'Slave database name'
            ),
            'DBSlaveLogin' => new SettingsProperty(
                'root',
                'string',
                null,
                'Slave database login'
            ),
            'DBSlavePassword' => new SettingsProperty(
                '',
                'string',
                null,
                'Slave database password'
            ),
            'DBUseExplain' => new SettingsProperty(
                false,
                'bool',
                null,
                'Use EXPLAIN in SQL queries'
            ),
            'DBUseExplainExtended' => new SettingsProperty(
                false,
                'bool',
                null,
                'Use Extended EXPLAIN'
            ),
            'DBLogQueryParams' => new SettingsProperty(
                false,
                'bool',
                null,
                'If enabled, parameters values will be recorded in the logs'
            ),
            'DBDebugBacktraceLimit' => new SettingsProperty(
                false,
                'bool',
                null,
                'This parameter can be used to limit the number of stack frames returned'
            ),

            'EnableLogging' => new SettingsProperty(
                false,
                'bool',
                null,
                'Activates debug logging'
            ),
            'EnableEventLogging' => new SettingsProperty(
                false,
                'bool',
                null,
                'Activates user activity logging'
            ),
            'LoggingLevel' => new SettingsProperty(
                Enums\LogLevel::Full,
                'spec',
                Enums\LogLevel::class,
                'For debug logs, verbosity level can be set to Full, Warning or Error'
            ),
            'LogFileName' => new SettingsProperty(
                'log-{Y-m-d}.txt',
                'string',
                null,
                'Denotes log filename pattern'
            ),
            'LogCustomFullPath' => new SettingsProperty(
                '',
                'string',
                null,
                'Allows for overriding log files location'
            ),
            'LogPostView' => new SettingsProperty(
                false,
                'bool',
                null,
                'Determines whether to log full POST data or just key names'
            ),

            'EnableMultiChannel' => new SettingsProperty(
                false,
                'bool',
                null,
                'Reserved for future use'
            ),
            'EnableMultiTenant' => new SettingsProperty(
                false,
                'bool',
                null,
                'Enables multi tenant support'
            ),
            'TenantGlobalCapa' => new SettingsProperty(
                '',
                'string',
                null,
                'Reserved for future use'
            ),

            'AllowThumbnail' => new SettingsProperty(
                true,
                'bool',
                null,
                'If disabled, image thumbnails will not be generated'
            ),
            'ThumbnailMaxFileSizeMb' => new SettingsProperty(
                5,
                'int',
                null,
                'Denotes a max filesize of images thumbnails are generated for, in Mbytes'
            ),
            'CacheCtrl' => new SettingsProperty(
                true,
                'bool',
                null,
                'If true, content of mail message opened in a new browser tab will be retrieved from cache'
            ),
            'CacheLangs' => new SettingsProperty(
                true,
                'bool',
                null,
                'Enables caching language files'
            ),
            'CacheTemplates' => new SettingsProperty(
                true,
                'bool',
                null,
                'Enables caching template files'
            ),
            'DisplayServerErrorInformation' => new SettingsProperty(
                true,
                'bool',
                null,
                'If enabled, error messages will include texts returned from the server'
            ),
            'EnableImap4PlainAuth' => new SettingsProperty(
                false,
                'bool',
                null,
                'Reserved for future use'
            ),
            'RedirectToHttps' => new SettingsProperty(
                false,
                'bool',
                null,
                'If enabled, users will automatically be redirected from HTTP to HTTPS'
            ),
            'SocketConnectTimeoutSeconds' => new SettingsProperty(
                20,
                'int',
                null,
                'Socket connection timeout limit, in seconds'
            ),
            'SocketGetTimeoutSeconds' => new SettingsProperty(
                20,
                'int',
                null,
                'Socket stream access timeout, in seconds'
            ),
            'SocketVerifySsl' => new SettingsProperty(
                false,
                'bool',
                null,
                'Enables SSL certificate checks'
            ),
            'UseAppMinJs' => new SettingsProperty(
                true,
                'bool',
                null,
                'Enables loading minified JS files (default behavior)'
            ),
            'XFrameOptions' => new SettingsProperty(
                '',
                'string',
                null,
                'If set to SAMEORIGIN, disallows embedding product interface into IFrame to prevent from clickjacking attacks'
            ),
            'RemoveOldLogs' => new SettingsProperty(
                true,
                'bool',
                null,
                'If enabled, logs older than RemoveOldLogsDays days are automatically removed'
            ),
            'RemoveOldLogsDays' => new SettingsProperty(
                2,
                'int',
                null,
                'Value for use with RemoveOldLogs setting'
            ),
            'LogStackTrace' => new SettingsProperty(
                false,
                'bool',
                null,
                'If enabled, logs will contain full stack trace of exceptions; disabled by default to prevent logs from containing sensitive data'
            ),
            'ExpireUserSessionsBeforeTimestamp' => new SettingsProperty(
                0,
                'int',
                null,
                'If set, all user sessions prior to this timestamp will be considered expired'
            ),

            'PasswordMinLength' => new SettingsProperty(
                0,
                'int',
                null,
                'Used by password change modules, if set to non-zero, denotes minimal length of new password'
            ),
            'PasswordMustBeComplex' => new SettingsProperty(
                false,
                'bool',
                null,
                'Used by password change modules, if set to true, new password has to include at least one digit and at least one non-alphanumeric character'
            ),

            'StoreAuthTokenInDB' => new SettingsProperty(
                false,
                'bool',
                null,
                'If enabled, authentication tokens will be stored in the database and can be revoked'
            ),
            'AuthTokenExpirationLifetimeDays' => new SettingsProperty(
                0,
                'int',
                null,
                'If set to non-zero value, means auth tokens will expire after this number of days. 0 means the feature is disabled.'
            ),
        ];
    }

    /**
     * @return bool
     */
    public function Load($bForceLoad = false)
    {
        $this->initDefaults();
        if (!\file_exists($this->sPath)) {
            $this->Save();
        }

        return parent::Load($bForceLoad);
    }

    public function SyncConfigs()
    {
        $this->initDefaults();
        $aContainer = $this->aContainer;
        if (!\file_exists($this->sPath)) {
            $this->Save();
        }
        parent::Load(true);
        $this->aContainer = \array_merge(
            $aContainer,
            $this->aContainer
        );
        $this->Save();
    }

    public function Save($bBackupConfigFile = true)
    {
        $result = parent::Save($bBackupConfigFile);
        if ($result) {
            Api::CreateContainer(true);
        }
        return $result;
    }
}
