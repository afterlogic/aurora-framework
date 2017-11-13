<?php
/*
 * @copyright Copyright (c) 2017, Afterlogic Corp.
 * @license AGPL-3.0 or Afterlogic Software License
 *
 * This code is licensed under AGPLv3 license or Afterlogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */
 
namespace Aurora\System;

/**
 * @package Api
 */
class Settings extends AbstractSettings
{
	protected function initDefaults()
	{
		$this->aContainer = array(
			'SiteName' => new SettingsProperty('SiteName', 'AfterLogic', 'string'),
			'LicenseKey' => new SettingsProperty('LicenseKey', '', 'string'),
			
			'AdminLogin' =>  new SettingsProperty('AdminLogin', 'superadmin', 'string'),
			'AdminPassword' => new SettingsProperty('AdminPassword', '', 'string'),
			'AdminLanguage' => new SettingsProperty('AdminLanguage', 'English', 'string'),
			
			'DBType' => new SettingsProperty('DBType', Enums\DbType::MySQL, 'spec', 'EDbType'),
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
			'LogPostView' => new SettingsProperty('LogPostView', '', 'string'),
			
			'EnableMultiChannel' => new SettingsProperty('EnableMultiChannel', false, 'bool'),
			'EnableMultiTenant' => new SettingsProperty('EnableMultiTenant', false, 'bool'),
			'TenantGlobalCapa' => new SettingsProperty('TenantGlobalCapa', '', 'string'),

			'AllowThumbnail' => new SettingsProperty('AllowThumbnail', true, 'bool'),
			'ThumbnailMaxFileSizeMb' => new SettingsProperty('ThumbnailMaxFileSizeMb', 5, 'int'),
			'AppCookiePath' => new SettingsProperty('AppCookiePath', '/', 'string'),
			'CacheCtrl' => new SettingsProperty('CacheCtrl', true, 'bool'),
			'CacheLangs' => new SettingsProperty('CacheLangs', true, 'bool'),
			'CacheStatic' => new SettingsProperty('CacheStatic', true, 'bool'),
			'CacheTemplates' => new SettingsProperty('CacheTemplates', true, 'bool'),
			'DisplayServerErrorInformation' => new SettingsProperty('DisplayServerErrorInformation', true, 'bool'),
			'EnableImap4PlainAuth' => new SettingsProperty('EnableImap4PlainAuth', false, 'bool'),
			'PreferStarttls' => new SettingsProperty('PreferStarttls', true, 'bool'),
			'RedirectToHttps' => new SettingsProperty('RedirectToHttps', false, 'bool'),
			'SieveUseStarttls' => new SettingsProperty('SieveUseStarttls', false, 'bool'),
			'SocketConnectTimeoutSeconds' => new SettingsProperty('SocketConnectTimeoutSeconds', 20, 'int'),
			'SocketGetTimeoutSeconds' => new SettingsProperty('SocketGetTimeoutSeconds', 20, 'int'),
			'SocketVerifySsl' => new SettingsProperty('SocketVerifySsl', false, 'bool'),
			'UseAppMinJs' => new SettingsProperty('UseAppMinJs', true, 'bool'),
			'XFrameOptions' => new SettingsProperty('XFrameOptions', '', 'string'),
		);		
	}

	/**
	 * @param string $sJsonFile
	 *
	 * @return bool
	 */
	public function Load($sJsonFile)
	{
		$this->initDefaults();
		if (!\file_exists($sJsonFile))
		{
			$this->Save();
		}
		
		return parent::Load($sJsonFile);
	}
}
