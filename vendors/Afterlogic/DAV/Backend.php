<?php

/* -AFTERLOGIC LICENSE HEADER- */

namespace Afterlogic\DAV;

class Backend
{
	public static function __callStatic($sMethod, $aArgs)
	{
		return Backends::getBackend(strtolower($sMethod));
	}	
}