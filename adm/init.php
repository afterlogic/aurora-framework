<?php
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

	if (!defined('PSEVEN_APP_ROOT_PATH'))
	{
		define('PSEVEN_APP_ROOT_PATH', dirname(rtrim(realpath(__DIR__), '\\/')).'/');
	}

	define('PSEVEN_APP_START', microtime(true));

	/**
	 * @param string $sClassName
	 *
	 * @return mixed
	 */
	function CoreSplAutoLoad($sClassName)
	{
		$aClassesTree = array(
			'core' => array(
				'Core'
			)
		);
		foreach ($aClassesTree as $sFolder => $aClasses)
		{
			foreach ($aClasses as $sClass)
			{
				if (0 === strpos($sClassName, $sClass) && false !== strpos($sClassName, '\\'))
				{
					$sClassPath = (strtolower($sClass) === strtolower($sFolder)) ? '' : $sClass . '/';
					$sFileName = PSEVEN_APP_ROOT_PATH.$sFolder.'/'.$sClassPath.str_replace('\\', '/', substr($sClassName, strlen($sClass) + 1)).'.php';
					if (file_exists($sFileName))
					{
						return include_once $sFileName;
					}
				}
			}
		}

		return false;
	}

	spl_autoload_register('CoreSplAutoLoad');

	if (class_exists('Core\Service'))
	{
		include PSEVEN_APP_ROOT_PATH.'core/api.php';
	//		\Core\Service::NewInstance()->Handle();	
	}
	else
	{
		spl_autoload_unregister('ProjectCoreSplAutoLoad');
	}