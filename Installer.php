<?php

namespace Aurora;

use Composer\Script\Event;

class Installer
{
	/**
	* This method should be run from composer.
	*/
    public static function updateConfigs()
	{
		$sBaseDir = dirname(__File__);
		$sMessage = "Configuration was updated successfully";
	    
		include_once $sBaseDir . '/autoload.php';
		
		\Aurora\System\Api::Init();
		
		$aModules = \Aurora\System\Api::GetModules();
		
		if (is_array($aModules))
		{
			foreach ($aModules as $oModule)
			{
				$oModule->saveModuleConfig();
			}
		}

		echo $sMessage;
	}
	
	/**
	* This method should be run from composer.
	*/
    public static function preConfigForce(Event $event)
	{
		self::preConfig($event);
	}
	
	/**
	* This method should be run from composer.
	*/
    public static function preConfigSafe(Event $event)
	{
		$sBaseDir = dirname(__File__);
		
		//Checking that configuration files already exist
		if (count(glob(dirname($sBaseDir)."/data/settings/modules/*")) !== 0)
		{
			echo "The config files are already exist";
			return;
		}
		else
		{
			self::preConfig($event);
		}
	}
	
    private static function preConfig(Event $event)
    {
		$sConfigFilename = 'pre-config.json';
		$sBaseDir = dirname(__File__);
		$sMessage = "Configuration was updated successfully";
	    
	    $oExtra = $event->getComposer()->getPackage()->getExtra();
		
		if ($oExtra && isset($oExtra['aurora-installer-pre-config']))
		{
			$sConfigFilename = $oExtra['aurora-installer-pre-config'];
		}
		
		$sConfigPath = dirname($sBaseDir) . '/' . $sConfigFilename;
				
		if (file_exists($sConfigPath))
		{
			$sPreConfig = file_get_contents($sConfigPath);

			$oPreConfig = json_decode($sPreConfig, true);

			if ($oPreConfig)
			{
				include_once $sBaseDir . '/autoload.php';
				
				\Aurora\System\Api::Init();
				
				if ($oPreConfig['modules'])
				{
					$oModuleManager = \Aurora\System\Api::GetModuleManager();
					
					foreach ($oPreConfig['modules'] as $sModuleName => $oModuleConfig)
					{
						foreach ($oModuleConfig as $sConfigName => $mConfigValue)
						{
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
				$sMessage = "Invalid config file";
			}
		}
		else
		{	
			$sMessage = "Config file didn't found";
		}

		echo $sMessage;
    }

}
