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

if (!defined('AURORA_APP_ROOT_PATH'))
{
	$sV = PHP_VERSION;
	if (-1 === version_compare($sV, '5.3.0') || !function_exists('spl_autoload_register'))
	{
		echo
			'PHP '.$sV.' detected, 5.3.0 or above required.
			<br />
			<br />
			You need to upgrade PHP engine installed on your server.
			If it\'s a dedicated or your local server, you can download the latest version of PHP from its
			<a href="http://php.net/downloads.php" target="_blank">official site</a> and install it yourself.
			In case of a shared hosting, you need to ask your hosting provider to perform the upgrade.';
		
		exit(0);
	}

	define('AURORA_APP_ROOT_PATH', rtrim(realpath(__DIR__), '\\/').'/');
	define('AURORA_APP_START', microtime(true));
	
	
	/**
	 * @param string $sClassName
	 *
	 * @return mixed
	 */
	function CoreSplAutoLoad($sClassName)
	{
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
					$sFileName = AURORA_APP_ROOT_PATH.$sFolder.'/'.str_replace('\\', '/', substr($sClassName, strlen($sClass) + 1)).'.php';
					if (file_exists($sFileName))
					{
						return include_once $sFileName;
					}
				}
			}
		}
		
		if (substr($sClassName, -6) === 'Module')
		{
			$sModuleName = substr($sClassName, 0, -6);
			$sFileName = AURORA_APP_ROOT_PATH.'modules/'.$sModuleName.'/module.php';
			if (file_exists($sFileName))
			{
				return include_once $sFileName;
			}
		}

		return false;
	}

	spl_autoload_register('CoreSplAutoLoad');
}