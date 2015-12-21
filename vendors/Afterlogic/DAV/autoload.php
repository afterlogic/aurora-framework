<?php

/* -AFTERLOGIC LICENSE HEADER- */

function DAVLibrariesAutoload($sClassName)
{
	$aClasses = array(
		'afterlogic',
		'Sabre'
	);
	foreach ($aClasses as $sClass)
	{
		if (0 === strpos($sClassName, $sClass) && false !== strpos($sClassName, '\\'))
		{
			$sFileName = CApi::LibrariesPath().$sClass.'/'.str_replace('\\', '/', substr($sClassName, strlen($sClass) + 1)).'.php';
			if (file_exists($sFileName))
			{
				return include $sFileName;
			}
		}
	}	
}

spl_autoload_register('DAVLibrariesAutoload');
