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
		$this->oActions->SetHttp($this->oHttp);
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

		$oHttp = \MailSo\Base\Http::NewInstance();
		$aPathInfo = array_filter(explode('/', \trim(\trim($oHttp->GetServer('PATH_INFO', ''), '/'))));
		if (0 < count($aPathInfo))
		{
			$aResult = $aPathInfo;
		}
		else 
		{
			$sQuery = \trim(\trim($oHttp->GetServer('QUERY_STRING', '')), ' /');

			\CApi::Plugin()->RunQueryHandle($sQuery);

			$iPos = \strpos($sQuery, '&');
			if (0 < $iPos)
			{
				$sQuery = \substr($sQuery, 0, $iPos);
			}
			$aResult = explode('/', $sQuery);
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
			if ($bHelpdesk)
			{
//				$oApiHelpdesk = \CApi::Manager('helpdesk');
				if ($oApiHelpdesk)
				{
					$oLogginedAccount = $this->oActions->GetDefaultAccount();

					$oApiCapability = \CApi::GetCoreManager('capability');

					$mHelpdeskIdTenant = $oApiIntegrator->getTenantIdByHash($sHelpdeskHash);
					if (!is_int($mHelpdeskIdTenant))
					{
						\CApi::Location('./');
						return '';
					}

					$bDoId = false;
					$sThread = $this->oHttp->GetQuery('thread');
					$sThreadAction = $this->oHttp->GetQuery('action');
					if (0 < strlen($sThread))
					{
						if ($oApiHelpdesk)
						{
							$iThreadID = $oApiHelpdesk->getThreadIdByHash($mHelpdeskIdTenant, $sThread);
							if (0 < $iThreadID)
							{
								$oApiIntegrator->setThreadIdFromRequest($iThreadID, $sThreadAction);
								$bDoId = true;
							}
						}
					}

					$sActivateHash = $this->oHttp->GetQuery('activate');
					if (0 < strlen($sActivateHash) && !$this->oHttp->HasQuery('forgot'))
					{
						$bRemove = true;
						$oUser = $oApiHelpdesk->getUserByActivateHash($mHelpdeskIdTenant, $sActivateHash);
						/* @var $oUser \CHelpdeskUser */
						if ($oUser)
						{
							if (!$oUser->Activated)
							{
								$oUser->Activated = true;
								$oUser->regenerateActivateHash();

								if ($oApiHelpdesk->updateUser($oUser))
								{
									$bRemove = false;
									$oApiIntegrator->setUserAsActivated($oUser);
								}
							}
						}

						if ($bRemove)
						{
							$oApiIntegrator->removeUserAsActivated();
						}
					}
					
					if ($oLogginedAccount && $oApiCapability && $oApiCapability->isHelpdeskSupported($oLogginedAccount) &&
						$oLogginedAccount->IdTenant === $mHelpdeskIdTenant)
					{
						if (!$bDoId)
						{
							$oApiIntegrator->setThreadIdFromRequest(0);
						}

						$oApiIntegrator->skipMobileCheck();
						\CApi::Location('./');
						return '';
					}
				}
				else
				{
					\CApi::Location('./');
					return '';
				}
			}

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
				else if ('min' === $sFirstPart)
				{
					$sResult = $this->TargetMin();
				}
				else if ('window' === $sFirstPart)
				{
					$sResult = $this->TargetWindow();
				}
				else if ('helpdesk' === $sFirstPart)
				{
					$sResult = $this->indexHTML(true, $this->oHttp->GetQuery('helpdesk'));
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
	
	private function TargetMin()
	{
		$sResult = '';
		$sAction = empty($aPaths[1]) ? '' : $aPaths[1];
		try
		{
			if (!empty($sAction))
			{
				$sMethodName =  $aPaths[0].$sAction;
				if (method_exists($this->oActions, $sMethodName))
				{
					if ('Min' === $aPaths[0])
					{
						$oMinManager = /* @var $oMinManager \CApiMinManager */ \CApi::Manager('min');
						$mHashResult = $oMinManager->getMinByHash(empty($aPaths[2]) ? '' : $aPaths[2]);

						$this->oActions->SetActionParams(array(
							'Result' => $mHashResult,
							'Hash' => empty($aPaths[2]) ? '' : $aPaths[2],
						));
					}
					else
					{
						$this->oActions->SetActionParams(array(
							'AccountID' => empty($aPaths[2]) || '0' === (string) $aPaths[2] ? '' : $aPaths[2],
							'RawKey' => empty($aPaths[3]) ? '' : $aPaths[3]
						));
					}

					$mResult = call_user_func(array($this->oActions, $sMethodName));
					$sTemplate = isset($mResult['Template']) && !empty($mResult['Template']) &&
						is_string($mResult['Template']) ? $mResult['Template'] : null;

					if (!empty($sTemplate) && is_array($mResult) && file_exists(PSEVEN_APP_ROOT_PATH.$sTemplate))
					{
						$sResult = file_get_contents(PSEVEN_APP_ROOT_PATH.$sTemplate);
						if (is_string($sResult))
						{
							$sResult = strtr($sResult, $mResult);
						}
						else
						{
							\CApi::Log('Empty template.', \ELogLevel::Error);
						}
					}
					else if (!empty($sTemplate))
					{
						\CApi::Log('Empty template.', \ELogLevel::Error);
					}
					else if (true === $mResult)
					{
						$sResult = '';
					}
					else
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
			\CApi::LogException($oException);
		}		
		
		return $sResult;
	}
	
	private function TargetWindow()
	{
		return $this->TargetMin();
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
