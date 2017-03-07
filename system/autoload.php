<?php

/**
 * @param string $sClassName
 *
 * @return mixed
 */

spl_autoload_register(function ($sClassName) {
	
	$aClassesTree = array(
		'system' => array(
			'Aurora\\System'
		)
	);
	foreach ($aClassesTree as $sFolder => $aClasses)
	{
		foreach ($aClasses as $sClass)
		{
			if (0 === strpos($sClassName, $sClass) && false !== strpos($sClassName, '\\'))
			{
				$sFileName = realpath($sFolder.'/'.str_replace('\\', '/', substr($sClassName, strlen($sClass) + 1)).'.php');
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
		$sFileName = realpath('modules/'.$sModuleName.'/Module.php');
		if (file_exists($sFileName))
		{
			include_once $sFileName;
		}
	}
});
