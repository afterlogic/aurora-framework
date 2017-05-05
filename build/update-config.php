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
 */

include_once '../system/autoload.php';


//$aArguments = getopt("m:n:v:");

$sPreConfig = file_get_contents('wml-pre-config.json');

$oPreConfig = json_decode($sPreConfig, true);

if ($oPreConfig)
{
	\Aurora\System\Api::Init();
	
	//system config is not included in the package
	// if ($oPreConfig['system'])
	// {
		// $oSystemSettings = \Aurora\System\Api::GetSettings();
		
		// foreach ($oPreConfig['system'] as $sConfigName => $mConfigValue)
		// {
			// $oSystemSettings->SetConf($sConfigName, $mConfigValue);
		// }
		
		// $oSystemSettings->Save();
	// }
	
	if ($oPreConfig['modules'])
	{
		foreach ($oPreConfig['modules'] as $sModuleName => $oModuleConfig)
		{
			foreach ($oModuleConfig as $sConfigName => $mConfigValue)
			{
				$oModuleManager = \Aurora\System\Api::GetModuleManager();

				$mValue = $oModuleManager->getModuleConfigValue($sModuleName, $sConfigName, null);
				if ($mValue !== null)
				{
					$oModuleManager->setModuleConfigValue($sModuleName, $sConfigName, $mConfigValue);
					$oModuleManager->saveModuleConfigValue($sModuleName);
				}
				else
				{
					echo 'Invalid setting \'' . $sConfigName . '\' in module \''.$sModuleName.'\'';
				}
			}
		}
	}
}
else
{
	echo "Invalid config file";
}
