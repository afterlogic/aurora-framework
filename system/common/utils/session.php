<?php
/*
 * @copyright Copyright (c) 2016, Afterlogic Corp.
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
 * @package Api
 */
class CSession
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
			CSession::Start();
		}
		return (isset($_SESSION[$sKey]));
	}

	/**
	 * @param string $sKey
	 * @return void
	 */
	public static function clear($sKey)
	{
		CSession::Start();
		unset($_SESSION[$sKey]);
	}

	/**
	 * @return void
	 */
	public static function ClearAll()
	{
		CSession::Start();
		$_SESSION = array();
	}

	/**
	 * @return void
	 */
	public static function Destroy()
	{
		CSession::Start();
		CSession::$bStarted = false;
		@session_destroy();
	}

	/**
	 * @param string $sKey
	 * @param mixed $nmDefault = null
	 * @return mixed
	 */
	public static function get($sKey, $nmDefault = null)
	{
		if (!CSession::$bFirstStarted)
		{
			CSession::Start();
		}

		return (isset($_SESSION[$sKey])) ? CSession::stripSlashesValue($_SESSION[$sKey]) : $nmDefault;
	}

	/**
	 * @param string $sKey
	 * @param mixed $mValue
	 */
	public static function Set($sKey, $mValue)
	{
		CSession::Start();
		$_SESSION[$sKey] = $mValue;
	}

	/**
	 * @return string
	 */
	public static function Id()
	{
		CSession::Start();
		return @session_id();
	}

	/**
	 * @param string $sId
	 *
	 * @return string
	 */
	public static function SetId($sId)
	{
		CSession::Stop();
		@session_id($sId);
		CSession::Start();
		return @session_id();
	}

	/**
	 * @return string
	 */
	public static function DestroySessionById($sId)
	{
		CSession::Stop();
		@session_id($sId);
		CSession::Start();
		CSession::Destroy();
	}

	/**
	 * @return bool
	 */
	public static function Start()
	{
		if (@session_name() !== CSession::$sSessionName || !CSession::$bStarted || !CSession::$bFirstStarted)
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
			if (!empty(CSession::$sSessionName))
			{
				@session_name(CSession::$sSessionName);
			}

			CSession::$bFirstStarted = true;
			CSession::$bStarted = true;

			return @session_start();
		}

		return true;
	}

	/**
	 * @return void
	 */
	public static function Stop()
	{
		if (CSession::$bStarted)
		{
			CSession::$bStarted = false;
			@session_write_close();
		}
	}

	/**
	 * @param mixed $mValue
	 * @return mixed
	 */
	private static function stripSlashesValue($mValue)
	{
		if (!CSession::$bIsMagicQuotesOn)
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
				$aReturnValue[$sKey] = CSession::stripSlashesValue($mValue[$sKey]);
			}
			return $aReturnValue;
		}
		else
		{
			return $mValue;
		}
	}
}

CSession::$bIsMagicQuotesOn = (bool) ini_get('magic_quotes_gpc');
CSession::$sSessionName = API_SESSION_WEBMAIL_NAME;
