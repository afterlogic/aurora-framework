<?php
/*
 * This code is licensed under AGPLv3 license or Afterlogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

namespace Aurora\System;

/**
 * @license https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @license https://afterlogic.com/products/common-licensing AfterLogic Software License
 * @copyright Copyright (c) 2018, Afterlogic Corp.
 *
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

		return (isset($_SESSION[$sKey])) ? $_SESSION[$sKey] : $nmDefault;
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
}

Session::$sSessionName = AU_API_SESSION_WEBMAIL_NAME;
