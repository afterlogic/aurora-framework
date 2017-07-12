<?php

namespace Aurora;

use Composer\Script\Event;

class Installer
{
    public static function postUpdate(Event $event)
    {
	    var_dump($event->getName());
		var_dump($event->getArguments());
	    
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
