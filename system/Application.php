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
		$this->oModuleManager = \Aurora\System\Api::GetModuleManager();

//		\MailSo\Config::$FixIconvByMbstring = false;
		\MailSo\Config::$SystemLogger = \Aurora\System\Api::MailSoLogger();
		\MailSo\Config::$PreferStartTlsIfAutoDetect = !!\Aurora\System\Api::GetConf('labs.prefer-starttls', true);
	}

	/**
	 * @return \Aurora\System\Application
	 */
	public static function NewInstance()
	{
		return new self();
	}
	
	public static function SingletonInstance()
	{
		static $oInstance = null;
		if (null === $oInstance)
		{
			$oInstance = self::NewInstance();
		}

		return $oInstance;
	}
	
	public static function Start()
	{
		\Aurora\System\Api::Init();
		self::SingletonInstance()->Handle();
	}

	
	public function GetVersion()
	{
		$sVersion = @file_get_contents(AURORA_APP_ROOT_PATH.'VERSION');
		define('AURORA_APP_VERSION', $sVersion);
		return $sVersion;
	}
	
	public function CheckApi()
	{
		if (!class_exists('\Aurora\\System\\Api') || !\Aurora\System\Api::IsValid()) 
		{
			echo 'Aurora API not found';
			return '';
		}
	}
	
	public function RedirectToHttps()
	{
		$oSettings =& \Aurora\System\Api::GetSettings();
		$bRedirectToHttps = $oSettings->GetConf('RedirectToHttps');
		
		$bHttps = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== "off") || 
				(isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == "443"));
		if ($bRedirectToHttps && !$bHttps) 
		{
			header("Location: https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
		}
	}
	
	public static function GetPaths()
	{
		$aResult = array();
		$aQuery = array();
		
		$oHttp = \MailSo\Base\Http::SingletonInstance();
		$aPathInfo = array_filter(explode('/', \trim(\trim($oHttp->GetServer('PATH_INFO', ''), '/'))));
		if (0 < count($aPathInfo)) 
		{
			$aQuery = $aPathInfo;
		} 
		else 
		{
			$sQuery = \trim(\trim($oHttp->GetQueryString()), ' /');

			$iPos = \strpos($sQuery, '&');
			if (0 < $iPos) 
			{
				$sQuery = \substr($sQuery, 0, $iPos);
			}
			$aQuery = explode('/', $sQuery);
		}
		foreach ($aQuery as $sQueryItem) 
		{
			$iPos = \strpos($sQueryItem, '=');
			$aResult[] = (!$iPos) ? $sQueryItem : \substr($sQueryItem, 0, $iPos);
		}
		
		return $aResult;
	}
			
	/**
	 * @return string
	 */
	private function generateHTML()
	{
		$sResult = '';
		
		$oApiIntegrator = \Aurora\System\Api::GetSystemManager('integrator');
		
		if ($oApiIntegrator) 
		{
			$sModuleHash = '';
			$aArgs = array();
			$this->oModuleManager->broadcastEvent(
				'System', 
				'GenerateHTML', 
				$aArgs,
				$sModuleHash
			);
					
			@\header('Content-Type: text/html; charset=utf-8', true);
			
			$sUserAgent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
			if (!\strpos(\strtolower($sUserAgent), 'firefox')) 
			{
				@\header('Last-Modified: '.\gmdate('D, d M Y H:i:s').' GMT');
			}
			
			if ((\Aurora\System\Api::GetConf('labs.cache-ctrl', true) && isset($_COOKIE['aft-cache-ctrl']))) 
			{
				\setcookie('aft-cache-ctrl', '', time() - 3600);
				\MailSo\Base\Http::SingletonInstance()->StatusHeader(304);
				exit();
			}
			
			$oCoreWebclientModule = \Aurora\System\Api::GetModule('CoreWebclient');
			if ($oCoreWebclientModule instanceof \Aurora\System\Module\AbstractModule) 
			{
				$sResult = \file_get_contents($oCoreWebclientModule->GetPath().'/templates/Index.html');
				if (\is_string($sResult)) 
				{
					$sFrameOptions = \Aurora\System\Api::GetConf('labs.x-frame-options', '');
					if (0 < \strlen($sFrameOptions)) 
					{
						@\header('X-Frame-Options: '.$sFrameOptions);
					}
					
					$sResult = strtr($sResult, array(
						'{{AppVersion}}' => AURORA_APP_VERSION,
						'{{IntegratorDir}}' => $oApiIntegrator->isRtl() ? 'rtl' : 'ltr',
						'{{IntegratorLinks}}' => $oApiIntegrator->buildHeadersLink(),
						'{{IntegratorBody}}' => $oApiIntegrator->buildBody($sModuleHash)
					));
				}
			}
		}

		return $sResult;
	}

	/**
	 * @return void
	 */
	public function Handle()
	{
		$mResult = '';
		$bError = false;
		$bIsHtml = false;
		
		$this->GetVersion();
		$this->CheckApi();
		$this->RedirectToHttps();

		$aPaths = self::GetPaths();

		$aModules = array();
		if (0 < \count($aPaths) && !empty($aPaths[0])) 
		{
			$sEntry = \strtolower($aPaths[0]);
			$oModule = $this->oModuleManager->GetModuleFromRequest();
			if ($oModule instanceof \Aurora\System\Module\AbstractModule) 
			{
				if ($oModule->HasEntry($sEntry))
				{
					$aModules[] = $oModule;
				}
				else 
				{
					$mResult = '\'' . $sEntry . '\' entry not found in \'' . $oModule->GetName() . '\' module.';
					$bError = true;
				}
			}
			else
			{
				 if ($sEntry === 'api')
				 {
					 $oCoreModule = \Aurora\System\Api::GetModule('Core');
					 if ($oCoreModule instanceof \Aurora\System\Module\AbstractModule)
					 {
						 $aModules[] = $oCoreModule;
					 }
				 }
				else 
				{
					$aModules = $this->oModuleManager->GetModulesByEntry($sEntry);
				 }
			}
			if (!$bError)
			{
				if (count($aModules) > 0)
				{
					foreach ($aModules as $oModule)
					{
						$mEntryResult = $oModule->RunEntry($sEntry);
						if ($mEntryResult !== 'null')
						{
							$mResult .= $mEntryResult;
						}
					}
				}
				else 
				{
					$bIsHtml = true;
				}
			}
		} 
		else 
		{
			$bIsHtml = true;
		}
		if ($bIsHtml)
		{
			$mResult = $this->generateHTML();	
		}
		$oHttp = \MailSo\Base\Http::SingletonInstance();
		if ($oHttp->GetRequest('Format') !== 'Raw')
		{
			echo $mResult;
		}
	}
}
