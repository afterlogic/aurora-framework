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
class Settings extends AbstractSettings
{
    protected function initDefaults()
    {
        $this->aContainer = array(
            'LicenseKey' => new SettingsProperty('', 'string'),

            'AdminLogin' =>  new SettingsProperty('superadmin', 'string'),
            'AdminPassword' => new SettingsProperty('', 'string'),
            'AdminLanguage' => new SettingsProperty('English', 'string'),

            'DBType' => new SettingsProperty(Enums\DbType::MySQL, 'spec', '\Aurora\System\Enums\DbType'),
            'DBPrefix' => new SettingsProperty('au_', 'string'),
            'DBHost' => new SettingsProperty('127.0.0.1', 'string'),
            'DBName' => new SettingsProperty('', 'string'),
            'DBLogin' => new SettingsProperty('root', 'string'),
            'DBPassword' => new SettingsProperty('', 'string'),

            'UseSlaveConnection' => new SettingsProperty(false, 'bool'),
            'DBSlaveHost' => new SettingsProperty('127.0.0.1', 'string'),
            'DBSlaveName' => new SettingsProperty('', 'string'),
            'DBSlaveLogin' => new SettingsProperty('root', 'string'),
            'DBSlavePassword' => new SettingsProperty('', 'string'),
            'DBUseExplain' => new SettingsProperty(false, 'bool'),
            'DBUseExplainExtended' => new SettingsProperty(false, 'bool'),
            'DBLogQueryParams' => new SettingsProperty(false, 'bool'),
            'DBDebugBacktraceLimit' => new SettingsProperty(false, 'bool'),

            'EnableLogging' => new SettingsProperty(false, 'bool'),
            'EnableEventLogging' => new SettingsProperty(false, 'bool'),
            'LoggingLevel' => new SettingsProperty(Enums\LogLevel::Full, 'spec', '\Aurora\System\Enums\LogLevel'),
            'LogFileName' => new SettingsProperty('log-{Y-m-d}.txt', 'string'),
            'LogCustomFullPath' => new SettingsProperty('', 'string'),
            'LogPostView' => new SettingsProperty(false, 'bool'),

            'EnableMultiChannel' => new SettingsProperty(false, 'bool'),
            'EnableMultiTenant' => new SettingsProperty(false, 'bool'),
            'TenantGlobalCapa' => new SettingsProperty('', 'string'),

            'AllowThumbnail' => new SettingsProperty(true, 'bool'),
            'ThumbnailMaxFileSizeMb' => new SettingsProperty(5, 'int'),
            'CacheCtrl' => new SettingsProperty(true, 'bool'),
            'CacheLangs' => new SettingsProperty(true, 'bool'),
            'CacheTemplates' => new SettingsProperty(true, 'bool'),
            'DisplayServerErrorInformation' => new SettingsProperty(true, 'bool'),
            'EnableImap4PlainAuth' => new SettingsProperty(false, 'bool'),
            'RedirectToHttps' => new SettingsProperty(false, 'bool'),
            'SocketConnectTimeoutSeconds' => new SettingsProperty(20, 'int'),
            'SocketGetTimeoutSeconds' => new SettingsProperty(20, 'int'),
            'SocketVerifySsl' => new SettingsProperty(false, 'bool'),
            'UseAppMinJs' => new SettingsProperty(true, 'bool'),
            'XFrameOptions' => new SettingsProperty('', 'string'),
            'RemoveOldLogs' => new SettingsProperty(true, 'bool'),
            'RemoveOldLogsDays' => new SettingsProperty(2, 'int'),
            'LogStackTrace' => new SettingsProperty(false, 'bool'),
            'ExpireUserSessionsBeforeTimestamp' => new SettingsProperty(0, 'int'),

            'PasswordMinLength' => new SettingsProperty(0, 'int'),
            'PasswordMustBeComplex' => new SettingsProperty(false, 'bool'),

            'StoreAuthTokenInDB' => new SettingsProperty(false, 'bool'),
            'AuthTokenExpirationLifetimeDays' => new SettingsProperty(0, 'int'),
        );
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
        $this->aContainer = \array_merge($aContainer, $this->aContainer);
        $this->Save();
    }
}
