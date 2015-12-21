<?php

/* -AFTERLOGIC LICENSE HEADER- */

namespace Afterlogic\DAV;

class Backends
{
	public static $aBackends = array();

	public static function getBackend($sName)
	{
		if (!isset(self::$aBackends[$sName]))
		{
			$oBackend = null;
			switch ($sName) {
				case 'auth':
					$oBackend = \Afterlogic\DAV\Auth\Backend::getInstance();
					break;
				case 'principal':
					$oBackend = new \Afterlogic\DAV\Principal\Backend\PDO();
					break;
				case 'caldav':
					$oBackend = new \Afterlogic\DAV\CalDAV\Backend\PDO();
					break;
				case 'carddav':
					$oBackend = new \Afterlogic\DAV\CardDAV\Backend\PDO();
					break;
				case 'carddav-owncloud':
					$oBackend = new \Afterlogic\DAV\CardDAV\Backend\OwnCloudPDO();
					break;
				case 'lock':
					$oBackend = new \Afterlogic\DAV\Locks\Backend\PDO();
					break;
				case 'reminders':
					$oBackend = new \Afterlogic\DAV\Reminders\Backend\PDO();
					break;
			}
			if (isset($oBackend))
			{
				self::$aBackends[$sName] = $oBackend;
			}
		}
		return self::$aBackends[$sName];
	}
}