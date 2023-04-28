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
* @property \Aurora\System\Enums\LogLevel $LoggingLevel
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
                ''
            ),

            'AdminLogin' =>  new SettingsProperty(
                'superadmin',
                'string',
                null,
                ''
            ),
            'AdminPassword' => new SettingsProperty(
                '',
                'string',
                null,
                ''
            ),
            'AdminLanguage' => new SettingsProperty(
                'English',
                'string',
                null,
                ''
            ),

            'DBType' => new SettingsProperty(
                Enums\DbType::MySQL,
                'spec',
                Enums\DbType::class,
                ''
            ),
            'DBPrefix' => new SettingsProperty(
                'au_',
                'string',
                null,
                ''
            ),
            'DBHost' => new SettingsProperty(
                '127.0.0.1',
                'string',
                null,
                ''
            ),
            'DBName' => new SettingsProperty(
                '',
                'string',
                null,
                ''
            ),
            'DBLogin' => new SettingsProperty(
                'root',
                'string',
                null,
                ''
            ),
            'DBPassword' => new SettingsProperty(
                '',
                'string',
                null,
                ''
            ),

            'UseSlaveConnection' => new SettingsProperty(
                false,
                'bool',
                null,
                ''
            ),
            'DBSlaveHost' => new SettingsProperty(
                '127.0.0.1',
                'string',
                null,
                ''
            ),
            'DBSlaveName' => new SettingsProperty(
                '',
                'string',
                null,
                ''
            ),
            'DBSlaveLogin' => new SettingsProperty(
                'root',
                'string',
                null,
                ''
            ),
            'DBSlavePassword' => new SettingsProperty(
                '',
                'string',
                null,
                ''
            ),
            'DBUseExplain' => new SettingsProperty(
                false,
                'bool',
                null,
                ''
            ),
            'DBUseExplainExtended' => new SettingsProperty(
                false,
                'bool',
                null,
                ''
            ),
            'DBLogQueryParams' => new SettingsProperty(
                false,
                'bool',
                null,
                ''
            ),
            'DBDebugBacktraceLimit' => new SettingsProperty(
                false,
                'bool',
                null,
                ''
            ),

            'EnableLogging' => new SettingsProperty(
                false,
                'bool',
                null,
                ''
            ),
            'EnableEventLogging' => new SettingsProperty(
                false,
                'bool',
                null,
                ''
            ),
            'LoggingLevel' => new SettingsProperty(
                Enums\LogLevel::Full,
                'spec',
                Enums\LogLevel::class,
                ''
            ),
            'LogFileName' => new SettingsProperty(
                'log-{Y-m-d}.txt',
                'string',
                null,
                ''
            ),
            'LogCustomFullPath' => new SettingsProperty(
                '',
                'string',
                null,
                ''
            ),
            'LogPostView' => new SettingsProperty(
                false,
                'bool',
                null,
                ''
            ),

            'EnableMultiChannel' => new SettingsProperty(
                false,
                'bool',
                null,
                ''
            ),
            'EnableMultiTenant' => new SettingsProperty(
                false,
                'bool',
                null,
                ''
            ),
            'TenantGlobalCapa' => new SettingsProperty(
                '',
                'string',
                null,
                ''
            ),

            'AllowThumbnail' => new SettingsProperty(
                true,
                'bool',
                null,
                ''
            ),
            'ThumbnailMaxFileSizeMb' => new SettingsProperty(
                5,
                'int',
                null,
                ''
            ),
            'CacheCtrl' => new SettingsProperty(
                true,
                'bool',
                null,
                'If true, then opening a mail message in a new browser tab, the page content will be getting from the browser cache'
            ),
            'CacheLangs' => new SettingsProperty(
                true,
                'bool',
                null,
                ''
            ),
            'CacheTemplates' => new SettingsProperty(
                true,
                'bool',
                null,
                ''
            ),
            'DisplayServerErrorInformation' => new SettingsProperty(
                true,
                'bool',
                null,
                ''
            ),
            'EnableImap4PlainAuth' => new SettingsProperty(
                false,
                'bool',
                null,
                ''
            ),
            'RedirectToHttps' => new SettingsProperty(
                false,
                'bool',
                null,
                ''
            ),
            'SocketConnectTimeoutSeconds' => new SettingsProperty(
                20,
                'int',
                null,
                ''
            ),
            'SocketGetTimeoutSeconds' => new SettingsProperty(
                20,
                'int',
                null,
                ''
            ),
            'SocketVerifySsl' => new SettingsProperty(
                false,
                'bool',
                null,
                ''
            ),
            'UseAppMinJs' => new SettingsProperty(
                true,
                'bool',
                null,
                ''
            ),
            'XFrameOptions' => new SettingsProperty(
                '',
                'string',
                null,
                ''
            ),
            'RemoveOldLogs' => new SettingsProperty(
                true,
                'bool',
                null,
                ''
            ),
            'RemoveOldLogsDays' => new SettingsProperty(
                2,
                'int',
                null,
                ''
            ),
            'LogStackTrace' => new SettingsProperty(
                false,
                'bool',
                null,
                ''
            ),
            'ExpireUserSessionsBeforeTimestamp' => new SettingsProperty(
                0,
                'int',
                null,
                ''
            ),

            'PasswordMinLength' => new SettingsProperty(
                0,
                'int',
                null,
                ''
            ),
            'PasswordMustBeComplex' => new SettingsProperty(
                false,
                'bool',
                null,
                ''
            ),

            'StoreAuthTokenInDB' => new SettingsProperty(
                false,
                'bool',
                null,
                ''
            ),
            'AuthTokenExpirationLifetimeDays' => new SettingsProperty(
                0,
                'int',
                null,
                ''
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
        $this->aContainer = \array_merge($aContainer,
$this->aContainer);
        $this->Save();
    }
}
