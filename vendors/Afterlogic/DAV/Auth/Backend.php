<?php

/* -AFTERLOGIC LICENSE HEADER- */

namespace Afterlogic\DAV\Auth;

class Backend
{
	protected static $instance;
	
	public static function getInstance()
	{
        if(null === self::$instance) 
		{
            self::$instance = (\CApi::GetConf('labs.dav.use-digest-auth', false)) ? new Backend\Digest() : new Backend\Basic();
        }
        return self::$instance;		
	}
}