<?php
/**
 * This code is licensed under AGPLv3 license or Afterlogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

namespace Aurora\System;

/**
 * @license https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @license https://afterlogic.com/products/common-licensing Afterlogic Software License
 * @copyright Copyright (c) 2019, Afterlogic Corp.
 *
 * @category Core
 */
class Application
{
	/**
	 * @type string
	 */
	const AUTH_TOKEN_KEY = 'AuthToken';

	/**
	 * @var \Aurora\System\Module\Manager
	 */
	protected $oModuleManager;

	/**
	 * @return void
	 */
	protected function __construct()
	{
//		\MailSo\Config::$FixIconvByMbstring = false;
		\MailSo\Config::$SystemLogger = Api::SystemLogger();
		\register_shutdown_function([$this, '__ApplicationShutdown']);
	}

	public function __ApplicationShutdown()
	{
		$aStatistic = \MailSo\Base\Loader::Statistic();
		if (\is_array($aStatistic))
		{
			if (isset($aStatistic['php']['memory_get_peak_usage']))
			{
				\Aurora\Api::Log('INFO[MEMORY]: Memory peak usage: '.$aStatistic['php']['memory_get_peak_usage']);
			}

			if (isset($aStatistic['time']))
			{
				\Aurora\Api::Log('INFO[TIME]: Time delta: '.$aStatistic['time']);
			}
		}
	}

	/**
	 * @return \Aurora\System\Application
	 */
	public static function NewInstance()
	{
		return new self();
	}

	/**
	 * @return \Aurora\System\Application
	 */
	public static function SingletonInstance()
	{
		static $oInstance = null;
		if (null === $oInstance)
		{
			$oInstance = self::NewInstance();
		}

		return $oInstance;
	}

	public static function DebugMode($bDebug)
	{
		Api::$bDebug = $bDebug;
	}

	public static function UseDbLogs()
	{
		Api::$bUseDbLog = true;
	}

	public static function Start($sDefaultEntry = 'default')
	{
		if (!defined('AU_APP_START'))
		{
			define('AU_APP_START', microtime(true));
		}

		try
		{
			Api::Init();
		}
		catch (\Aurora\System\Exceptions\ApiException $oEx)
		{
			\Aurora\System\Api::LogException($oEx);
		}

		self::GetVersion();

		$mResult = self::SingletonInstance()->Route(
			\strtolower(
				Router::getItemByIndex(0, $sDefaultEntry)
			)
		);
		if (\MailSo\Base\Http::SingletonInstance()->GetRequest('Format') !== 'Raw')
		{
			echo $mResult;
		}
	}

	/**
	 * @return string
	 */
	public static function GetVersion()
	{
		$sVersion = @\file_get_contents(AU_APP_ROOT_PATH.'VERSION');
		\define('AU_APP_VERSION', $sVersion);
		return $sVersion;
	}

	/**
	 * @return array
	 */
	public static function GetPaths()
	{
		return Router::getItems();
	}

	public function Route($sRoute)
	{
		return Api::GetModuleManager()->RunEntry($sRoute);
	}
}
