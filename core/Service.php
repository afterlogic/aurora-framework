<?php

/* -AFTERLOGIC LICENSE HEADER- */

namespace Core;

/**
 * @category Core
 */
class Service
{
	/**
	 * @type string
	 */
	const AUTH_TOKEN_KEY = 'AuthToken';
	
	/**
	 * @var CApiModuleManager
	 */
	protected $oModuleManager;

	/**
	 * @return void
	 */
	protected function __construct()
	{
		$this->oModuleManager = \CApi::GetModuleManager();

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
		$sVersion = @file_get_contents(PSEVEN_APP_ROOT_PATH.'VERSION');
		define('PSEVEN_APP_VERSION', $sVersion);
		return $sVersion;
	}
	
	public function CheckApi()
	{
		if (!class_exists('\\CApi') || !\CApi::IsValid()) {
			echo 'AfterLogic API';
			return '';
		}
	}
	
	public function RedirectToHttps()
	{
		$oSettings =& \CApi::GetSettings();
		$bRedirectToHttps = $oSettings->GetConf('Common/RedirectToHttps');
		
		$bHttps = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== "off") || 
				(isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == "443"));
		if ($bRedirectToHttps && !$bHttps) {
			header("Location: https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
		}
	}
	
	public static function GetPaths()
	{
		$aResult = array();
		$aQuery = array();
		
		$oHttp = \MailSo\Base\Http::NewInstance();
		$aPathInfo = array_filter(explode('/', \trim(\trim($oHttp->GetServer('PATH_INFO', ''), '/'))));
		if (0 < count($aPathInfo)) {
			$aQuery = $aPathInfo;
		} else {
			$sQuery = \trim(\trim($oHttp->GetQueryString()), ' /');

			\CApi::Plugin()->RunQueryHandle($sQuery);

			$iPos = \strpos($sQuery, '&');
			if (0 < $iPos) {
				$sQuery = \substr($sQuery, 0, $iPos);
			}
			$aQuery = explode('/', $sQuery);
		}
		foreach ($aQuery as $sQueryItem) {
			$iPos = \strpos($sQueryItem, '=');
			$aResult[] = (!$iPos) ? $sQueryItem : \substr($sQueryItem, 0, $iPos);
		}
		
		return $aResult;
	}
			
	/**
	 * @param string $sHelpdeskHash = ''
	 * @param string $sCalendarPubHash = ''
	 * @param string $sFileStoragePubHash = ''
	 * @return string
	 */
	private function generateHTML()
	{
		$sResult = '';
		
		$oApiIntegrator = \CApi::GetCoreManager('integrator');
		
		if ($oApiIntegrator) {
			
			@\header('Content-Type: text/html; charset=utf-8', true);
			
			if (!strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'firefox')) {
				@\header('Last-Modified: '.\gmdate('D, d M Y H:i:s').' GMT');
			}
			
			if ((\CApi::GetConf('labs.cache-ctrl', true) && isset($_COOKIE['aft-cache-ctrl']))) {
				setcookie('aft-cache-ctrl', '', time() - 3600);
				\MailSo\Base\Http::NewInstance()->StatusHeader(304);
				exit();
			}
			$oCoreModule = \CApi::GetModule('Core');
			if ($oCoreModule instanceof \AApiModule) {
				$sResult = file_get_contents($oCoreModule->GetPath().'/templates/Index.html');
				if (is_string($sResult)) {
					$sFrameOptions = \CApi::GetConf('labs.x-frame-options', '');
					if (0 < \strlen($sFrameOptions)) {
						@\header('X-Frame-Options: '.$sFrameOptions);
					}
					
//					$sAuthToken = isset($_COOKIE[self::AUTH_TOKEN_KEY]) ? $_COOKIE[self::AUTH_TOKEN_KEY] : '';
					
					$sResult = strtr($sResult, array(
						'{{AppVersion}}' => PSEVEN_APP_VERSION,
						'{{IntegratorDir}}' => $oApiIntegrator->isRtl() ? 'rtl' : 'ltr',
						'{{IntegratorLinks}}' => $oApiIntegrator->buildHeadersLink(),
						'{{IntegratorBody}}' => $oApiIntegrator->buildBody()
					));
//					var_dump($sResult);
//					exit;
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

		$this->GetVersion();
		$this->CheckApi();
		$this->RedirectToHttps();

		$aPaths = $this->GetPaths();

		if (0 < count($aPaths) && !empty($aPaths[0])) {
			
			$sEntryPart = strtolower($aPaths[0]);
			
			$mResult = $this->oModuleManager->RunEntry($sEntryPart);
			
			if ($mResult === false) {
				@ob_start();
				\CApi::Plugin()->RunServiceHandle($sEntryPart, $aPaths);
				$mResult = @ob_get_clean();

				if (0 === strlen($mResult)) {
					$mResult = $this->generateHTML();
				}
			}
		} else {
			$mResult = $this->generateHTML();
		}

		echo $mResult;
	}
}
