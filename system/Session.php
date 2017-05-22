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

namespace Aurora\System;

/**
 * @package Api
 */
class Session
{
	/**
	 * @var string
	 */
	static $sSessionName = '';

	/**
	 * @var bool
	 */
	static $bIsMagicQuotesOn = false;

	/**
	 * @var bool
	 */
	static $bFirstStarted = false;

	/**
	 * @var bool
	 */
	static $bStarted = false;

	private function __construct() {}

	/**
	 * @param string $sKey
	 * @return bool
	 */
	public static function Has($sKey)
	{
		if (!CSession::$bFirstStarted)
		{
			self::Start();
		}
		return (isset($_SESSION[$sKey]));
	}

	/**
	 * @param string $sKey
	 * @return void
	 */
	public static function clear($sKey)
	{
		self::Start();
		unset($_SESSION[$sKey]);
	}

	/**
	 * @return void
	 */
	public static function ClearAll()
	{
		self::Start();
		$_SESSION = array();
	}

	/**
	 * @return void
	 */
	public static function Destroy()
	{
		self::Start();
		self::$bStarted = false;
		@session_destroy();
	}

	/**
	 * @param string $sKey
	 * @param mixed $nmDefault = null
	 * @return mixed
	 */
	public static function get($sKey, $nmDefault = null)
	{
		if (!self::$bFirstStarted)
		{
			self::Start();
		}

		return (isset($_SESSION[$sKey])) ? self::stripSlashesValue($_SESSION[$sKey]) : $nmDefault;
	}

	/**
	 * @param string $sKey
	 * @param mixed $mValue
	 */
	public static function Set($sKey, $mValue)
	{
		self::Start();
		$_SESSION[$sKey] = $mValue;
	}

	/**
	 * @return string
	 */
	public static function Id()
	{
		self::Start();
		return @session_id();
	}

	/**
	 * @param string $sId
	 *
	 * @return string
	 */
	public static function SetId($sId)
	{
		self::Stop();
		@session_id($sId);
		self::Start();
		return @session_id();
	}

	/**
	 * @return string
	 */
	public static function DestroySessionById($sId)
	{
		self::Stop();
		@session_id($sId);
		self::Start();
		self::Destroy();
	}

	/**
	 * @return bool
	 */
	public static function Start()
	{
		if (@session_name() !== self::$sSessionName || !self::$bStarted || !self::$bFirstStarted)
		{
			if (@session_name())
			{
				@session_write_close();
				if (isset($GLOBALS['PROD_NAME']) && false !== strpos($GLOBALS['PROD_NAME'], 'Plesk')) // Plesk
				{
					@session_module_name('files');
				}
			}

			@session_set_cookie_params(0);
			if (!empty(self::$sSessionName))
			{
				@session_name(self::$sSessionName);
			}

			self::$bFirstStarted = true;
			self::$bStarted = true;

			return @session_start();
		}

		return true;
	}

	/**
	 * @return void
	 */
	public static function Stop()
	{
		if (self::$bStarted)
		{
			self::$bStarted = false;
			@session_write_close();
		}
	}

	/**
	 * @param mixed $mValue
	 * @return mixed
	 */
	private static function stripSlashesValue($mValue)
	{
		if (!self::$bIsMagicQuotesOn)
		{
			return $mValue;
		}

		$sType = gettype($mValue);
		if ($sType === 'string')
		{
			return stripslashes($mValue);
		}
		else if ($sType === 'array')
		{
			$aReturnValue = array();
			$mValueKeys = array_keys($mValue);
			foreach($mValueKeys as $sKey)
			{
				$aReturnValue[$sKey] = self::stripSlashesValue($mValue[$sKey]);
			}
			return $aReturnValue;
		}
		else
		{
			return $mValue;
		}
	}
}

self::$bIsMagicQuotesOn = (bool) ini_get('magic_quotes_gpc');
self::$sSessionName = API_SESSION_WEBMAIL_NAME;
