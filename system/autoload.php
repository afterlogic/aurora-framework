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
