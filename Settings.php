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
			'LicenseKey' => new SettingsProperty('LicenseKey', '', 'string'),

			'AdminLogin' =>  new SettingsProperty('AdminLogin', 'superadmin', 'string'),
			'AdminPassword' => new SettingsProperty('AdminPassword', '', 'string'),
			'AdminLanguage' => new SettingsProperty('AdminLanguage', 'English', 'string'),

			'DBType' => new SettingsProperty('DBType', Enums\DbType::MySQL, 'spec', '\Aurora\System\Enums\DbType'),
			'DBPrefix' => new SettingsProperty('DBPrefix', 'au_', 'string'),
			'DBHost' => new SettingsProperty('DBHost', '127.0.0.1', 'string'),
			'DBName' => new SettingsProperty('DBName', '', 'string'),
			'DBLogin' => new SettingsProperty('DBLogin', 'root', 'string'),
			'DBPassword' => new SettingsProperty('DBPassword', '', 'string'),

			'UseSlaveConnection' => new SettingsProperty('UseSlaveConnection', false, 'bool'),
			'DBSlaveHost' => new SettingsProperty('DBSlaveHost', '127.0.0.1', 'string'),
			'DBSlaveName' => new SettingsProperty('DBSlaveName', '', 'string'),
			'DBSlaveLogin' => new SettingsProperty('DBSlaveLogin', 'root', 'string'),
			'DBSlavePassword' => new SettingsProperty('DBSlavePassword', '', 'string'),
			'DBUseExplain' => new SettingsProperty('DBUseExplain', false, 'bool'),
			'DBUseExplainExtended' => new SettingsProperty('DBUseExplainExtended', false, 'bool'),
			'DBLogQueryParams' => new SettingsProperty('DBLogQueryParams', false, 'bool'),
			'DBDebugBacktraceLimit' => new SettingsProperty('DBDebugBacktraceLimit', false, 'bool'),

			'EnableLogging' => new SettingsProperty('EnableLogging', false, 'bool'),
			'EnableEventLogging' => new SettingsProperty('EnableEventLogging', false, 'bool'),
			'LoggingLevel' => new SettingsProperty('LoggingLevel', Enums\LogLevel::Full, 'spec', '\Aurora\System\Enums\LogLevel'),
			'LogFileName' => new SettingsProperty('LogFileName', 'log-{Y-m-d}.txt', 'string'),
			'LogCustomFullPath' => new SettingsProperty('LogCustomFullPath', '', 'string'),
			'LogPostView' => new SettingsProperty('LogPostView', false, 'bool'),

			'EnableMultiChannel' => new SettingsProperty('EnableMultiChannel', false, 'bool'),
			'EnableMultiTenant' => new SettingsProperty('EnableMultiTenant', false, 'bool'),
			'TenantGlobalCapa' => new SettingsProperty('TenantGlobalCapa', '', 'string'),

			'AllowThumbnail' => new SettingsProperty('AllowThumbnail', true, 'bool'),
			'ThumbnailMaxFileSizeMb' => new SettingsProperty('ThumbnailMaxFileSizeMb', 5, 'int'),
			'CacheCtrl' => new SettingsProperty('CacheCtrl', true, 'bool'),
			'CacheLangs' => new SettingsProperty('CacheLangs', true, 'bool'),
			'CacheTemplates' => new SettingsProperty('CacheTemplates', true, 'bool'),
			'DisplayServerErrorInformation' => new SettingsProperty('DisplayServerErrorInformation', true, 'bool'),
			'EnableImap4PlainAuth' => new SettingsProperty('EnableImap4PlainAuth', false, 'bool'),
			'RedirectToHttps' => new SettingsProperty('RedirectToHttps', false, 'bool'),
			'SocketConnectTimeoutSeconds' => new SettingsProperty('SocketConnectTimeoutSeconds', 20, 'int'),
			'SocketGetTimeoutSeconds' => new SettingsProperty('SocketGetTimeoutSeconds', 20, 'int'),
			'SocketVerifySsl' => new SettingsProperty('SocketVerifySsl', false, 'bool'),
			'UseAppMinJs' => new SettingsProperty('UseAppMinJs', true, 'bool'),
			'XFrameOptions' => new SettingsProperty('XFrameOptions', '', 'string'),
			'RemoveOldLogs' => new SettingsProperty('RemoveOldLogs', true, 'bool'),
			'LogStackTrace' => new SettingsProperty('LogStackTrace', false, 'bool'),
			'ExpireUserSessionsBefore' => new SettingsProperty('ExpireUserSessionsBefore', 0, 'int'),

			'PasswordMinLength' => new SettingsProperty('PasswordMinLength', 0, 'int'),
			'PasswordMustBeComplex' => new SettingsProperty('PasswordMustBeComplex', false, 'bool'),

			'StoreAuthTokenInDB' => new SettingsProperty('StoreAuthTokenInDB', false, 'bool')
		);
	}

	/**
	 * @return bool
	 */
	public function Load()
	{
		$this->initDefaults();
		if (!\file_exists($this->sPath))
		{
			$this->Save();
		}

		return parent::Load();
	}
}
