<?php
/*
 * @copyright Copyright (c) 2017, Afterlogic Corp.
 * @license AGPL-3.0 or Afterlogic Software License
 *
 * This code is licensed under AGPLv3 license or Afterlogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

/**
 * @param string $sClassName
 *
 * @return mixed
 */

spl_autoload_register(function ($sClassName) {
	
	$aClassesTree = array(
		'system' => array(
			'Aurora\\System'
		),
		'modules' => array(
			'Aurora\\Modules'
		)
	);
	foreach ($aClassesTree as $sFolder => $aClasses)
	{
		foreach ($aClasses as $sClass)
		{
			if (0 === strpos($sClassName, $sClass) && false !== strpos($sClassName, '\\'))
			{
				$sFileName = dirname(__DIR__) . '/' .$sFolder.'/'.str_replace('\\', '/', substr($sClassName, strlen($sClass) + 1)).'.php';
				if (file_exists($sFileName))
				{
					include_once $sFileName;
				}
			}
		}
	}

	if (strpos($sClassName, 'Aurora\\Modules') !== false)
	{
		$sModuleClassName = substr($sClassName, strlen('Aurora\\Modules\\'));
		$sModuleName = substr($sModuleClassName, 0, -7);
		$sFileName = dirname(__DIR__) . '/modules/'.$sModuleName.'/Module.php';
		if (file_exists($sFileName))
		{
			include_once $sFileName;
		}
	}
});
