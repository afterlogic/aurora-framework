<?php
/*
 * This code is licensed under AGPLv3 license or Afterlogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

/**
 * @license https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @license https://afterlogic.com/products/common-licensing AfterLogic Software License
 * @copyright Copyright (c) 2018, Afterlogic Corp.
 *
 * @param string $sClassName
 *
 * @return mixed
 */

spl_autoload_register(function ($sClassName) {
	
	$aClassesTree = array(
		'system' . DIRECTORY_SEPARATOR => array(
			'Aurora\\System',
		),
		'modules' . DIRECTORY_SEPARATOR => array(
			'Aurora\\Modules'
		)
	);
	foreach ($aClassesTree as $sFolder => $aClasses)
	{
		foreach ($aClasses as $sClass)
		{
			if (0 === strpos($sClassName, $sClass) && false !== strpos($sClassName, '\\'))
			{
				$sFileName = dirname(__DIR__) . DIRECTORY_SEPARATOR .$sFolder.str_replace('\\', DIRECTORY_SEPARATOR, substr($sClassName, strlen($sClass) + 1)).'.php';
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
		$sFileName = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . $sModuleName . DIRECTORY_SEPARATOR . 'Module.php';
		if (file_exists($sFileName))
		{
			include_once $sFileName;
		}
	}
});
