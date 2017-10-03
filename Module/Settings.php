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


namespace Aurora\System\Module;

/**
 * @package Api
 */
class Settings extends \Aurora\System\AbstractSettings
{
	public $DefaultConfigFilePath;
	public $ModuleName;

	/**
	 * 
	 * @param string $sModuleName
	 */
	public function __construct($sModuleName)
	{
		$this->ModuleName = $sModuleName;
		$sModulesSettingsPath = \Aurora\System\Api::GetModuleManager()->GetModulesSettingsPath();
		$sConfigFilePath = $sModulesSettingsPath . $sModuleName . '.config.json';
		if (!\file_exists($sConfigFilePath))
		{
			$this->DefaultConfigFilePath = \Aurora\System\Api::GetModuleManager()->GetModulesPath() . '/' . $sModuleName . '/config.json';
			if (\file_exists($this->DefaultConfigFilePath))
			{
				if (!\file_exists($sModulesSettingsPath))
				{
					\mkdir($sModulesSettingsPath, 0777);
				}
				\copy($this->DefaultConfigFilePath, $sConfigFilePath);
			}
		}

		parent::__construct($sConfigFilePath);
	}
	
	/*
	 * 
	 */
	public function GetDefaultConfigValues()
	{
		return (new DefaultSettings($this->DefaultConfigFilePath))->GetConfigValues();
	}
}
