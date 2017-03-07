<?php
/*
 * @copyright Copyright (c) 2017, Afterlogic Corp.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 * 
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
			
			'DBType' => new SettingsProperty('DBType', \EDbType::MySQL, 'spec', 'EDbType'),
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

			'DefaultTimeZone' => new SettingsProperty('DefaultTimeZone', 0, 'int'),
			'AllowRegistration' => new SettingsProperty('AllowRegistration', false, 'bool'),
			'RegistrationDomains' => new SettingsProperty('RegistrationDomains', '', 'string'),
			'RegistrationQuestions' => new SettingsProperty('RegistrationQuestions', '', 'string'),
			'AllowPasswordReset' => new SettingsProperty('AllowPasswordReset', false, 'bool'),
			'EnableLogging' => new SettingsProperty('EnableLogging', false, 'bool'),
			'EnableEventLogging' => new SettingsProperty('EnableEventLogging', false, 'bool'),
			'LoggingLevel' => new SettingsProperty('LoggingLevel', \ELogLevel::Full, 'spec', 'ELogLevel'),
			'LogFileName' => new SettingsProperty('LogFileName', 'log-{Y-m-d}.txt', 'string'),
			'LogCustomFullPath' => new SettingsProperty('LogCustomFullPath', '', 'string'),
			'EnableMobileSync' => new SettingsProperty('EnableMobileSync', false, 'bool'),
			
			'EnableMultiChannel' => new SettingsProperty('EnableMultiChannel', false, 'bool'),
			'EnableMultiTenant' => new SettingsProperty('EnableMultiTenant', false, 'bool'),

			'TenantGlobalCapa' => new SettingsProperty('TenantGlobalCapa', '', 'string'),

			'LoginStyleImage' => new SettingsProperty('LoginStyleImage', '', 'string'),
			'InvitationEmail' => new SettingsProperty('InvitationEmail', '', 'string'),
			
			'DefaultTab' => new SettingsProperty('DefaultTab', '', 'string'),
			'RedirectToHttps' => new SettingsProperty('RedirectToHttps', false, 'bool'),

			'PasswordMinLength' => new SettingsProperty('PasswordMinLength', 0, 'int'),
			'PasswordMustBeComplex' => new SettingsProperty('PasswordMustBeComplex', false, 'bool')
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
		if (!file_exists($sJsonFile))
		{
			$this->Save();
		}
		
		return parent::Load($sJsonFile);
	}
}
