<?php

/* -AFTERLOGIC LICENSE HEADER- */

namespace Core;

/**
 * @category Core
 */
class Service
{
	/**
	 * @var \MailSo\Base\Http
	 */
	protected $oHttp;

	/**
	 * @var \Core\Actions
	 */
	protected $oActions;
	
	
	/**
	 * @var CApiModuleManager
	 */
	protected $oModuleManager;

	/**
	 * @return void
	 */
	protected function __construct()
	{
		$this->oHttp = \MailSo\Base\Http::NewInstance();
		$this->oActions = Actions::NewInstance();
		$this->oModuleManager = \CApi::GetModuleManager();

		\CApi::Plugin()->SetActions($this->oActions);
		
//		\MailSo\Config::$FixIconvByMbstring = false;
		\MailSo\Config::$SystemLogger = \CApi::MailSoLogger();
		\MailSo\Config::$PreferStartTlsIfAutoDetect = !!\CApi::GetConf('labs.prefer-starttls', true);
	}

	/**
	 * @return \Core\Service
	 */
	public static function NewInstance()
	{
		return new self();
	}

	/**
	 * @return bool
	 */
	public static function validateToken()
	{
		$oHttp = \MailSo\Base\Http::NewInstance();
		$oIntegrator = \CApi::GetCoreManager('integrator');
		return $oHttp->IsPost() ? $oIntegrator->validateCsrfToken($oHttp->GetPost('Token')) : true;
	}
	
	public function GetVersion()
	{
		$sVersion = file_get_contents(PSEVEN_APP_ROOT_PATH.'VERSION');
		define('PSEVEN_APP_VERSION', $sVersion);
		return $sVersion;
	}
	
	public function CheckApi()
	{
		if (!class_exists('\\CApi') || !\CApi::IsValid())
		{
			echo 'AfterLogic API';
			return '';
		}
	}
	
	public function RedirectToHttps()
	{
		$oSettings =& \CApi::GetSettings();
		$bRedirectToHttps = $oSettings->GetConf('Common/RedirectToHttps');
		
		$bHttps = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== "off") || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == "443"));
		if ($bRedirectToHttps && !$bHttps)
		{
			header("Location: https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
		}
	}
	
	public static function GetPaths()
	{
		$aResult = array();
		$aQuery = array();
		
		$oHttp = \MailSo\Base\Http::NewInstance();
		$aPathInfo = array_filter(explode('/', \trim(\trim($oHttp->GetServer('PATH_INFO', ''), '/'))));
		if (0 < count($aPathInfo))
		{
			$aQuery = $aPathInfo;
		}
		else 
		{
			$sQuery = \trim(\trim($oHttp->GetQueryString()), ' /');

			\CApi::Plugin()->RunQueryHandle($sQuery);

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
	 * @param bool $bHelpdesk = false
	 * @param string $sHelpdeskHash = ''
	 * @param string $sCalendarPubHash = ''
	 * @param string $sFileStoragePubHash = ''
	 * @param bool $bMobile = false
	 * @return string
	 */
	private function indexHTML($bHelpdesk = false, $sHelpdeskHash = '', $sCalendarPubHash = '', $sFileStoragePubHash = '', $bMobile = false)
	{
		$sResult = '';
		$mHelpdeskIdTenant = false;
		
		$oApiIntegrator = \CApi::GetCoreManager('integrator');
		
		if ($oApiIntegrator)
		{
			@\header('Content-Type: text/html; charset=utf-8', true);
			
			if (!strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'firefox'))
			{
				@\header('Last-Modified: '.\gmdate('D, d M Y H:i:s').' GMT');
			}
			
			if ((\CApi::GetConf('labs.cache-ctrl', true) && isset($_COOKIE['aft-cache-ctrl'])))
			{
				setcookie('aft-cache-ctrl', '', time() - 3600);
				$this->oHttp->StatusHeader(304);
				exit();
			}
			else
			{
				$sResult = file_get_contents(PSEVEN_APP_ROOT_PATH.'templates/Index.html');
				if (is_string($sResult))
				{
					$sFrameOptions = \CApi::GetConf('labs.x-frame-options', '');
					if (0 < \strlen($sFrameOptions))
					{
						@\header('X-Frame-Options: '.$sFrameOptions);
					}

					$sResult = strtr($sResult, array(
						'{{AppVersion}}' => PSEVEN_APP_VERSION,
						'{{IntegratorDir}}' => $oApiIntegrator->getAppDirValue(),
						'{{IntegratorLinks}}' => $oApiIntegrator->buildHeadersLink('.', $bHelpdesk,
							$mHelpdeskIdTenant, $sHelpdeskHash, $sCalendarPubHash, $sFileStoragePubHash, $bMobile),
						'{{IntegratorBody}}' => $oApiIntegrator->buildBody('.', $bHelpdesk,
							$mHelpdeskIdTenant, $sHelpdeskHash, $sCalendarPubHash, $sFileStoragePubHash, $bMobile)
					));
				}
			}
		}
		else
		{
			$sResult = '';
		}

		return $sResult;
	}

	/**
	 * @return void
	 */
	public function Handle()
	{
		$sResult = '';

		$this->GetVersion();
		$this->CheckApi();
		$this->RedirectToHttps();

		$aPaths = $this->GetPaths();
		
		if (0 < count($aPaths) && !empty($aPaths[0]))
		{
			$sFirstPart = strtolower($aPaths[0]);
			
			$sResult = $this->oModuleManager->RunEntry($sFirstPart);
			
			if (!$sResult)
			{
				if ('post' === $sFirstPart)
				{
					$this->TargetPost();
				}
				else if ('calendar-pub' === $sFirstPart)
				{
					$sResult = $this->indexHTML(false, '', $this->oHttp->GetQuery('calendar-pub'));
				}
				else if ('files-pub' === $sFirstPart)
				{
					$sResult = $this->indexHTML(false, '', '', $this->oHttp->GetQuery('files-pub'));
				}
				else
				{
					@ob_start();
					\CApi::Plugin()->RunServiceHandle($sFirstPart, $aPaths);
					$sResult = @ob_get_clean();

					if (0 === strlen($sResult))
					{
						$sResult = $this->getIndexHTML();
					}
				}
			}
		}
		else
		{
			$sResult = $this->getIndexHTML();
		}

		// Output result
		echo $sResult;
	}
	
	private function TargetPost()
	{
		$sAction = $this->oHttp->GetPost('Action');
		try
		{
			if (!empty($sAction))
			{
				$sMethodName =  'Post'.$sAction;
				if (method_exists($this->oActions, $sMethodName) &&
					is_callable(array($this->oActions, $sMethodName)))
				{
					$this->oActions->SetActionParams($this->oHttp->GetPostAsArray());
					if (!call_user_func(array($this->oActions, $sMethodName)))
					{
						\CApi::Log('False result.', \ELogLevel::Error);
					}
				}
				else
				{
					\CApi::Log('Invalid action.', \ELogLevel::Error);
				}
			}
			else
			{
				\CApi::Log('Empty action.', \ELogLevel::Error);
			}
		}
		catch (\Exception $oException)
		{
			\CApi::LogException($oException, \ELogLevel::Error);
		}		
	}
	
	/**
	 * @return string
	 */
	private function getIndexHTML()
	{
		if (\CApi::IsMobileApplication())
		{
			return $this->indexHTML(false, '', '', '', true);
		}
		else
		{
			return $this->indexHTML();
		}
	}
}
