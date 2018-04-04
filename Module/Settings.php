<?php
/*
 * @copyright Copyright (c) 2017, Afterlogic Corp.
 * @license AGPL-3.0 or Afterlogic Software License
 *
 * This code is licensed under AGPLv3 license or Afterlogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
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
		$this->DefaultConfigFilePath = \Aurora\System\Api::GetModuleManager()->GetModulesPath() . '/' . $sModuleName . '/config.json';
		$sModulesSettingsPath = \Aurora\System\Api::GetModuleManager()->GetModulesSettingsPath();
		$sConfigFilePath = $sModulesSettingsPath . $sModuleName . '.config.json';
		if (!\file_exists($sConfigFilePath))
		{
			if (\file_exists($this->DefaultConfigFilePath))
			{
				if (!\file_exists($sModulesSettingsPath))
				{
					set_error_handler(function() {});					
					\mkdir($sModulesSettingsPath, 0777);
					restore_error_handler();
					if (!file_exists($sModulesSettingsPath))
					{
						return;
					}
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
