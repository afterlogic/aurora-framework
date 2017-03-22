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

namespace Aurora\System\Managers\Integrator;

/**
 * \Aurora\System\Managers\Integrator\Manager class summary
 *
 * @package Integrator
 */
class Manager extends \Aurora\System\Managers\AbstractManager
{
	/**
	 * @type string
	 */
	const MOBILE_KEY = 'aurora-mobile';

	/**
	 * @type string
	 */
	const AUTH_HD_KEY = 'aurora-hd-auth';

	/**
	 * @type string
	 */
	const TOKEN_KEY = 'aurora-token';

	/**
	 * @type string
	 */
	const TOKEN_LAST_CODE = 'aurora-last-code';

	/**
	 * @type string
	 */
	const TOKEN_LANGUAGE = 'aurora-lang';

	/**
	 * @type string
	 */
	const TOKEN_HD_THREAD_ID = 'aurora-hd-thread';

	/**
	 * @type string
	 */
	const TOKEN_HD_ACTIVATED = 'aurora-hd-activated';

	/**
	 * @type string
	 */
	const TOKEN_SKIP_MOBILE_CHECK = 'aurora-skip-mobile';

	/**
	 * @var $bCache bool
	 */
	private $bCache;

	/**
	 * Creates a new instance of the object.
	 *
	 * @param &$oManager
	 */
	public function __construct(\Aurora\System\Managers\GlobalManager &$oManager)
	{
		$this->bCache = false;
		parent::__construct('Integrator', $oManager);
	}

	/**
	 * @param string $sDir
	 * @param string $sType
	 *
	 * @return array
	 */
	private function folderFiles($sDir, $sType)
	{
		$aResult = array();
		if (is_dir($sDir))
		{
			$aFiles = \Aurora\System\Utils::GlobRecursive($sDir.'/*'.$sType);
			foreach ($aFiles as $sFile)
			{
				if ((empty($sType) || $sType === substr($sFile, -strlen($sType))) && is_file($sFile))
				{
					$aResult[] = $sFile;
				}
			}
		}

		return $aResult;
	}

	/**
	 * @TODO use tenants modules if exist
	 * 
	 * @return string
	 */
	public function compileTemplates()
	{
		$sHash = \Aurora\System\Api::GetModuleManager()->GetModulesHash();
		
		$sCacheFileName = '';
		$oSettings =& \Aurora\System\Api::GetSettings();
		if ($oSettings->GetConf('CacheTemplates', $this->bCache))
		{
			$sCacheFileName = 'templates-'.md5(\Aurora\System\Api::Version().$sHash).'.cache';
			$sCacheFullFileName = \Aurora\System\Api::DataPath().'/cache/'.$sCacheFileName;
			if (file_exists($sCacheFullFileName))
			{
				return file_get_contents($sCacheFullFileName);
			}
		}

		$sResult = '';
		$sPath =\Aurora\System\Api::WebMailPath().'modules';
		
		$aModuleNames = \Aurora\System\Api::GetModuleManager()->GetAllowedModulesName();

		foreach ($aModuleNames as $sModuleName)
		{
			$sDirName = $sPath . '/' . $sModuleName . '/templates';
			$iDirNameLen = strlen($sDirName);
			if (is_dir($sDirName))
			{
				$aList = $this->folderFiles($sDirName, '.html');
				foreach ($aList as $sFileName)
				{
					$sName = '';
					$iPos = strpos($sFileName, $sDirName);
					if ($iPos === 0)
					{
						$sName = substr($sFileName, $iDirNameLen + 1);
					}
					else
					{
						$sName = '@errorName'.md5(rand(10000, 20000));
					}

					$sTemplateID = $sModuleName.'_'.preg_replace('/[^a-zA-Z0-9_]/', '', str_replace(array('/', '\\'), '_', substr($sName, 0, -5)));
					$sTemplateHtml = file_get_contents($sFileName);

					$sTemplateHtml = \Aurora\System\Api::GetModuleManager()->ParseTemplate($sTemplateID, $sTemplateHtml);
					$sTemplateHtml = preg_replace('/\{%INCLUDE-START\/[a-zA-Z\-_]+\/INCLUDE-END%\}/', '', $sTemplateHtml);
					$sTemplateHtml = str_replace('%ModuleName%', $sModuleName, $sTemplateHtml);
					$sTemplateHtml = str_replace('%MODULENAME%', strtoupper($sModuleName), $sTemplateHtml);

					$sTemplateHtml = preg_replace('/<script([^>]*)>/', '&lt;script$1&gt;', $sTemplateHtml);
					$sTemplateHtml = preg_replace('/<\/script>/', '&lt;/script&gt;', $sTemplateHtml);

					$sResult .= '<script id="'.$sTemplateID.'" type="text/html">'.
						preg_replace('/[\r\n\t]+/', ' ', $sTemplateHtml).'</script>';
				}
			}
		}

		$sResult = trim($sResult);
		$oSettings =& \Aurora\System\Api::GetSettings();
		if ($oSettings->GetConf('CacheTemplates', $this->bCache))
		{
			if (!is_dir(dirname($sCacheFullFileName)))
			{
				mkdir(dirname($sCacheFullFileName), 0777, true);
			}
			
			$sResult = '<!-- '.$sCacheFileName.' -->'.$sResult;
			file_put_contents($sCacheFullFileName, $sResult);
		}

		return $sResult;
	}

	/**
	 * @param string $sTheme
	 *
	 * @return string
	 */
	private function validatedThemeValue($sTheme)
	{
		if ('' === $sTheme || !in_array($sTheme, $this->getThemeList()))
		{
			$sTheme = 'Default';
		}

		return $sTheme;
	}

	/**
	 * @param string $sLanguage
	 *
	 * @return string
	 */
	private function validatedLanguageValue($sLanguage)
	{
		if ('' === $sLanguage || !in_array($sLanguage, $this->getLanguageList()))
		{
			$sLanguage = 'English';
		}

		return $sLanguage;
	}

	private function getMomentLanguageString($sLanguage)
	{
		$sMomentLanguage = \Aurora\System\Utils::ConvertLanguageNameToShort($sLanguage);
		if ($sLanguage === 'Arabic' || $sLanguage === 'Persian')
		{
			$sMoment = 'window.moment && window.moment.locale && window.moment.locale(\'en\');';
		}
		else
		{
			$sMoment = 'window.moment && window.moment.locale && window.moment.locale(\'' . $sMomentLanguage . '\');';
		}
	}
	
	/**
	 * @TODO use tenants modules if exist
	 * @param string $sLanguage
	 *
	 * @return string
	 */
	public function compileLanguage($sLanguage)
	{
		$sLanguage = $this->validatedLanguageValue($sLanguage);
		$sResult = "";
		
		$sHash = \Aurora\System\Api::GetModuleManager()->GetModulesHash();

		$sCacheFileName = '';
		$oSettings =& \Aurora\System\Api::GetSettings();
		if ($oSettings->GetConf('CacheLangs', $this->bCache))
		{
			$sCacheFileName = 'langs-'.md5(\Aurora\System\Api::Version().$sHash).'.cache';
			$sCacheFullFileName = \Aurora\System\Api::DataPath().'/cache/'.$sCacheFileName;
			if (file_exists($sCacheFullFileName))
			{
				$sResult = file_get_contents($sCacheFullFileName);
			}
		}
		
		if ($sResult === "")
		{
			$aResult = array();
			$sPath =\Aurora\System\Api::WebMailPath().'modules';

			$aModuleNames = \Aurora\System\Api::GetModuleManager()->GetAllowedModulesName();

			foreach ($aModuleNames as $sModuleName)
			{
				$aLangContent = '';

				$sFileName = $sPath . '/' . $sModuleName . '/i18n/'.$sLanguage.'.ini';

				if (file_exists($sFileName))
				{
					$aLangContent = parse_ini_file($sFileName);
				} 
				else if (file_exists($sPath . '/' . $sModuleName . '/i18n/English.ini'))
				{
					$aLangContent = parse_ini_file($sPath . '/' . $sModuleName . '/i18n/English.ini');
				}
				else
				{
					continue;
				}

				if ($aLangContent)
				{
					foreach ($aLangContent as $sLangKey => $sLangValue)
					{
						$aResult[strtoupper($sModuleName)."/".$sLangKey] = $sLangValue;
					}
				}
			}
			
			$sResult .= json_encode($aResult);
			
			$oSettings =& \Aurora\System\Api::GetSettings();
			if ($oSettings->GetConf('CacheLangs', $this->bCache))
			{
				if (!is_dir(dirname($sCacheFullFileName)))
				{
					mkdir(dirname($sCacheFullFileName), 0777, true);
				}

				$sResult = '/* '.$sCacheFileName.' */'.$sResult;
				file_put_contents($sCacheFullFileName, $sResult);
			}
		}

//		return '<script>window.auroraI18n='.$this->getLanguageString($sLanguage).';'.$this->getMomentLanguageString($sLanguage).'</script>';
		return '<script>window.auroraI18n='.($sResult ? $sResult : '{}').';'.$this->getMomentLanguageString($sLanguage).'</script>';
	}

	/**
	 * @return string|null
	 */
	private function getCookiePath()
	{
		static $sPath = false;
		
		if (false === $sPath)
		{
			$oSettings =& \Aurora\System\Api::GetSettings();
			$sPath = $oSettings->GetConf('AppCookiePath', '/');
		}

		return $sPath;
	}

	/**
	 * @return string
	 */
	public function getLoginLanguage()
	{
		$sLanguage = empty($_COOKIE[self::TOKEN_LANGUAGE]) ? '' : $_COOKIE[self::TOKEN_LANGUAGE];
		return '' === $sLanguage ? '' : $this->validatedLanguageValue($sLanguage);
	}

	/**
	 * @param string $sLanguage
	 */
	public function setLoginLanguage($sLanguage)
	{
		$sLanguage = $this->validatedLanguageValue($sLanguage);
		@setcookie(self::TOKEN_LANGUAGE, $sLanguage, 0, $this->getCookiePath(), null, null, true);
	}

	/**
	 * @param string $sAuthToken Default value is empty string.
	 *
	 * @return \CUser
	 */
	public function getAuthenticatedUserHelper($sAuthToken = '')
	{
		$oCoreDecorator = \Aurora\System\Api::GetModuleDecorator('Core');
		$aUserInfo = $this->getAuthenticatedUserInfo($sAuthToken);
		$iUserId = $aUserInfo['userId'];
		$oUser = null;
		if (0 < $iUserId)
		{
			$oUser = $oCoreDecorator->GetUser($iUserId);
		}
		elseif ($aUserInfo['isAdmin'])
		{
			$oUser = $oCoreDecorator->GetAdminUser();
		}
		return $oUser;
	}
	
	/**
	 * @param int $iUserId Default value is empty string.
	 *
	 * @return \CUser
	 */
	public function getAuthenticatedUserByIdHelper($iUserId)
	{
		$oCoreDecorator = \Aurora\System\Api::GetModuleDecorator('Core');
		$oUser = null;
		if (0 < $iUserId)
		{
			$oUser = $oCoreDecorator->GetUser($iUserId);
		}
		elseif ($iUserId === -1)
		{
			$oUser = $oCoreDecorator->GetAdminUser();
		}
		return $oUser;
	}

	/**
	 * @param string $sAuthToken Default value is empty string.
	 *
	 * @return int
	 */
	public function getAuthenticatedUserInfo($sAuthToken = '')
	{
		$aInfo = array(
			'isAdmin' => false,
			'userId' => 0
		);
		$aAccountHashTable = \Aurora\System\Api::UserSession()->Get($sAuthToken);
		if (is_array($aAccountHashTable) && isset($aAccountHashTable['token']) &&
			'auth' === $aAccountHashTable['token'] && 0 < strlen($aAccountHashTable['id'])) {
			
			$aInfo = array(
				'isAdmin' => false,
				'userId' => (int) $aAccountHashTable['id'],
				'account' => isset($aAccountHashTable['account']) ? $aAccountHashTable['account'] : 0,
			);
		}
		elseif (is_array($aAccountHashTable) && isset($aAccountHashTable['token']) &&
			'admin' === $aAccountHashTable['token'])
		{
			$aInfo = array(
				'isAdmin' => true,
				'userId' => -1
			);
		}
		return $aInfo;
	}

	/**
	 * @return int
	 */
	public function getAuthenticatedHelpdeskUserId()
	{
		$iHdUserId = 0;
		$sKey = empty($_COOKIE[self::AUTH_HD_KEY]) ? '' : $_COOKIE[self::AUTH_HD_KEY];
		if (!empty($sKey) && is_string($sKey))
		{
			$aUserHashTable =\Aurora\System\Api::DecodeKeyValues($sKey);
			if (is_array($aUserHashTable) && isset($aUserHashTable['token']) &&
				'hd_auth' === $aUserHashTable['token'] && 0 < strlen($aUserHashTable['id']) && is_int($aUserHashTable['id']))
			{
				$iHdUserId = (int) $aUserHashTable['id'];
			}
		}

		return $iHdUserId;
	}

	/**
	 * @return CAccount|null
	 */
	public function getAuthenticatedDefaultAccount($sAuthToken = '')
	{
		$oResult = null;
		$iUserId = \Aurora\System\Api::getAuthenticatedUserId($sAuthToken);
		if (0 < $iUserId)
		{
//			$oApiUsers =\Aurora\System\Api::GetSystemManager('users');
			if ($oApiUsers)
			{
				$iAccountId = $oApiUsers->getDefaultAccountId($iUserId);
				if (0 < $iAccountId)
				{
					$oAccount = $oApiUsers->getAccountById($iAccountId);
					$oResult = $oAccount instanceof \CAccount ? $oAccount : null;
				}
			}
		}
		else 
		{
			$this->logoutAccount();
		}

		return $oResult;
	}

	/**
	 * @param int $iCode
	 */
	public function setLastErrorCode($iCode)
	{
		@setcookie(self::TOKEN_LAST_CODE, $iCode, 0, $this->getCookiePath(), null, null, true);
	}

	/**
	 * @return int
	 */
	public function getLastErrorCode()
	{
		return isset($_COOKIE[self::TOKEN_LAST_CODE]) ? (int) $_COOKIE[self::TOKEN_LAST_CODE] : 0;
	}

	public function clearLastErrorCode()
	{
		if (isset($_COOKIE[self::TOKEN_LAST_CODE]))
		{
			unset($_COOKIE[self::TOKEN_LAST_CODE]);
		}
		
		@setcookie(self::TOKEN_LAST_CODE, '', time() - 60 * 60 * 24 * 30, $this->getCookiePath());
	}

	/**
	 * @param string $sAuthToken Default value is empty string.
	 * 
 	 * @return bool
	 */
	public function logoutAccount($sAuthToken = '')
	{
		if (strlen($sAuthToken) !== 0)
		{
			$sKey = \Aurora\System\Api::UserSession()->Delete($sAuthToken);
		}
		
		@setcookie(\Aurora\System\Application::AUTH_TOKEN_KEY, '', time() - 60 * 60 * 24 * 30, $this->getCookiePath());
		@setcookie(self::TOKEN_LANGUAGE, '', 0, $this->getCookiePath());
		return true;
	}

	/**
	 * @param int $iThreadID
	 * @param string $sThreadAction Default value is empty string.
	 */
	public function setThreadIdFromRequest($iThreadID, $sThreadAction = '')
	{
		$aHashTable = array(
			'token' => 'thread_id',
			'id' => (int) $iThreadID,
			'action' => (string) $sThreadAction
		);

		\Aurora\System\Api::LogObject($aHashTable);

		$_COOKIE[self::TOKEN_HD_THREAD_ID] =\Aurora\System\Api::EncodeKeyValues($aHashTable);
		@setcookie(self::TOKEN_HD_THREAD_ID,\Aurora\System\Api::EncodeKeyValues($aHashTable), 0, $this->getCookiePath(), null, null, true);
	}

	/**
	 * @return array
	 */
	public function getThreadIdFromRequestAndClear()
	{
		$aHdThreadId = array();
		$sKey = empty($_COOKIE[self::TOKEN_HD_THREAD_ID]) ? '' : $_COOKIE[self::TOKEN_HD_THREAD_ID];
		if (!empty($sKey) && is_string($sKey))
		{
			$aUserHashTable =\Aurora\System\Api::DecodeKeyValues($sKey);
			if (is_array($aUserHashTable) && isset($aUserHashTable['token'], $aUserHashTable['id']) &&
				'thread_id' === $aUserHashTable['token'] && 0 < strlen($aUserHashTable['id']) && is_int($aUserHashTable['id']))
			{
				$aHdThreadId['id'] = (int) $aUserHashTable['id'];
				$aHdThreadId['action'] = isset($aUserHashTable['action']) ? (string) $aUserHashTable['action'] : '';
			}
		}

		if (0 < strlen($sKey))
		{
			if (isset($_COOKIE[self::TOKEN_HD_THREAD_ID]))
			{
				unset($_COOKIE[self::TOKEN_HD_THREAD_ID]);
			}

			@setcookie(self::TOKEN_HD_THREAD_ID, '', time() - 60 * 60 * 24 * 30, $this->getCookiePath());
		}

		return $aHdThreadId;
	}

	public function removeUserAsActivated()
	{
		if (isset($_COOKIE[self::TOKEN_HD_ACTIVATED]))
		{
			$_COOKIE[self::TOKEN_HD_ACTIVATED] = '';
			unset($_COOKIE[self::TOKEN_HD_ACTIVATED]);
			@setcookie(self::TOKEN_HD_ACTIVATED, '', time() - 60 * 60 * 24 * 30, $this->getCookiePath());
		}
	}

	/**
	 * @param CHelpdeskUser $oHelpdeskUser
	 * @param bool $bForgot Default value is **false**.
	 *
	 * @return void
	 */
	public function setUserAsActivated($oHelpdeskUser, $bForgot = false)
	{
		$aHashTable = array(
			'token' => 'hd_activated_email',
			'forgot' => $bForgot,
			'email' => $oHelpdeskUser->Email
		);

		$_COOKIE[self::TOKEN_HD_ACTIVATED] =\Aurora\System\Api::EncodeKeyValues($aHashTable);
		@setcookie(self::TOKEN_HD_ACTIVATED,\Aurora\System\Api::EncodeKeyValues($aHashTable), 0, $this->getCookiePath(), null, null, true);
	}

	/**
	 * @return int
	 */
	public function getActivatedUserEmailAndClear()
	{
		$sEmail = '';
		$sKey = empty($_COOKIE[self::TOKEN_HD_ACTIVATED]) ? '' : $_COOKIE[self::TOKEN_HD_ACTIVATED];
		if (!empty($sKey) && is_string($sKey))
		{
			$aUserHashTable =\Aurora\System\Api::DecodeKeyValues($sKey);
			if (is_array($aUserHashTable) && isset($aUserHashTable['token']) &&
				'hd_activated_email' === $aUserHashTable['token'] && 0 < strlen($aUserHashTable['email']))
			{
				$sEmail = $aUserHashTable['email'];
			}
		}

		if (0 < strlen($sKey))
		{
			if (isset($_COOKIE[self::TOKEN_HD_ACTIVATED]))
			{
				unset($_COOKIE[self::TOKEN_HD_ACTIVATED]);
			}

			@setcookie(self::TOKEN_HD_THREAD_ID, '', time() - 60 * 60 * 24 * 30, $this->getCookiePath());
		}

		return $sEmail;
	}

	/**
	 * @param CAccount $oAccount
	 * @param bool $bSignMe Default value is **false**.
	 *
	 * @return string
	 */
	public function setAccountAsLoggedIn(CAccount $oAccount, $bSignMe = false)
	{
		$aAccountHashTable = array(
			'token' => 'auth',
			'sign-me' => $bSignMe,
			'id' => $oAccount->IdUser,
			'email' => $oAccount->Email
		);
		
		$iTime = $bSignMe ? time() + 60 * 60 * 24 * 30 : 0;
		$sAccountHashTable = \Aurora\System\Api::EncodeKeyValues($aAccountHashTable);
		
		$sAuthToken = \md5($oAccount->IdUser.$oAccount->IncomingLogin.\microtime(true).\rand(10000, 99999));
		
		return \Aurora\System\Api::Cacher()->Set('AUTHTOKEN:'.$sAuthToken, $sAccountHashTable) ? $sAuthToken : '';
	}

	/**
	 * @param CHelpdeskUser $oUser
	 * @param bool $bSignMe Default value is **false**.
	 */
	public function setHelpdeskUserAsLoggedIn(CHelpdeskUser $oUser, $bSignMe = false)
	{
		$aUserHashTable = array(
			'token' => 'hd_auth',
			'sign-me' => $bSignMe,
			'id' => $oUser->IdHelpdeskUser
		);

		$iTime = $bSignMe ? time() + 60 * 60 * 24 * 30 : 0;
		$_COOKIE[self::AUTH_HD_KEY] =\Aurora\System\Api::EncodeKeyValues($aUserHashTable);
		@setcookie(self::AUTH_HD_KEY,\Aurora\System\Api::EncodeKeyValues($aUserHashTable), $iTime, $this->getCookiePath(), null, null, true);
	}

	/**
	 * @return bool
	 */
	public function logoutHelpdeskUser()
	{
		@setcookie(self::AUTH_HD_KEY, '', time() - 60 * 60 * 24 * 30, $this->getCookiePath());
		return true;
	}

	public function skipMobileCheck()
	{
		@setcookie(self::TOKEN_SKIP_MOBILE_CHECK, '1', 0, $this->getCookiePath(), null, null, true);
	}

	/**
	 * @return int
	 */
	public function isMobile()
	{
		if (isset($_COOKIE[self::TOKEN_SKIP_MOBILE_CHECK]) && '1' === (string) $_COOKIE[self::TOKEN_SKIP_MOBILE_CHECK])
		{
			@setcookie(self::TOKEN_SKIP_MOBILE_CHECK, '', time() - 60 * 60 * 24 * 30, $this->getCookiePath());
			return 0;
		}

		return isset($_COOKIE[self::MOBILE_KEY]) ? ('1' === (string) $_COOKIE[self::MOBILE_KEY] ? 1 : 0) : -1;
	}

	/**
	 * @param bool $bMobile
	 *
	 * @return bool
	 */
	public function setMobile($bMobile)
	{
		@setcookie(self::MOBILE_KEY, $bMobile ? '1' : '0', time() + 60 * 60 * 24 * 200, $this->getCookiePath());
		return true;
	}

	public function resetCookies()
	{
		$sHelpdeskHash = !empty($_COOKIE[self::AUTH_HD_KEY]) ? $_COOKIE[self::AUTH_HD_KEY] : '';
		if (0 < strlen($sHelpdeskHash))
		{
			$aHelpdeskHashTable =\Aurora\System\Api::DecodeKeyValues($sHelpdeskHash);
			if (isset($aHelpdeskHashTable['sign-me']) && $aHelpdeskHashTable['sign-me'])
			{
				@setcookie(self::AUTH_HD_KEY,\Aurora\System\Api::EncodeKeyValues($aHelpdeskHashTable),
					time() + 60 * 60 * 24 * 30, $this->getCookiePath(), null, null, true);
			}
		}
	}

	/**
	 * @param string $sEmail
	 * @param string $sIncPassword
	 * @param string $sIncLogin Default value is empty string.
	 * @param string $sLanguage Default value is empty string.
	 *
	 * @throws \Aurora\System\Exceptions\ManagerException(Errs::WebMailManager_AccountDisabled) 1501
	 * @throws \Aurora\System\Exceptions\ManagerException(Errs::Mail_AccountAuthentication) 4002
	 * @throws \Aurora\System\Exceptions\ManagerException(Errs::WebMailManager_AccountCreateOnLogin) 1503
	 *
	 * @return CAccount|null|bool
	 */
	public function loginToAccount($sEmail, $sIncPassword, $sIncLogin = '', $sLanguage = '')
	{
		$oResult = null;
		
		\Aurora\System\Api::AddSecret($sIncPassword);

		/* @var $oApiUsersManager CApiUsersManager */
//		$oApiUsersManager =\Aurora\System\Api::GetSystemManager('users');

		$bAuthResult = false;
		$oAccount = $oApiUsersManager->getAccountByEmail($sEmail);
		if ($oAccount instanceof CAccount)
		{
			if ($oAccount->IsDisabled)
			{
				throw new \Aurora\System\Exceptions\ManagerException(Errs::WebMailManager_AccountDisabled);
			}

			if (0 < $oAccount->IdTenant)
			{
//				$oApiTenantsManager = /* @var $oApiTenantsManager CApiTenantsManager */\Aurora\System\Api::GetSystemManager('tenants');
				if ($oApiTenantsManager)
				{
					$oTenant = $oApiTenantsManager->getTenantById($oAccount->IdTenant);
					if ($oTenant && ($oTenant->IsDisabled || (0 < $oTenant->Expared && $oTenant->Expared < \time())))
					{
						throw new \Aurora\System\Exceptions\ManagerException(Errs::WebMailManager_AccountDisabled);
					}
				}
			}

			if (0 < strlen($sLanguage) && $sLanguage !== $oAccount->User->Language)
			{
				$oAccount->User->Language = $sLanguage;
			}

			$bAllowWebMail = true;
			if ($bAllowWebMail)
			{
				if ($sIncPassword !== $oAccount->IncomingPassword)
				{
					$oAccount->IncomingPassword = $sIncPassword;
				}
				try
				{
					\Aurora\System\Api::ExecuteMethod('Mail::ValidateAccountConnection', array('Account' => $oAccount));
				}
				catch (Exception $oException)
				{
					throw $oException;
				}
			}
			else if ($sIncPassword !== $oAccount->IncomingPassword)
			{
				throw new \Aurora\System\Exceptions\ManagerException(Errs::Mail_AccountAuthentication);
			}

			$sObsoleteIncPassword = $oAccount->GetObsoleteValue('IncomingPassword');
			$sObsoleteLanguage = $oAccount->User->GetObsoleteValue('Language');
			if (null !== $sObsoleteIncPassword && $sObsoleteIncPassword !== $oAccount->IncomingPassword ||
				null !== $sObsoleteLanguage && $sObsoleteLanguage !== $oAccount->User->Language ||
				$oAccount->ForceSaveOnLogin)
			{
				$oApiUsersManager->updateAccount($oAccount);
			}

			$oApiUsersManager->updateAccountLastLoginAndCount($oAccount->IdUser);

			$oResult = $oAccount;
		}
		else if (null === $oAccount)
		{
			$aExtValues = array();
			if (0 < strlen($sIncLogin))
			{
				$aExtValues['Login'] = $sIncLogin;
			}
			$aExtValues['ApiIntegratorLoginToAccountResult'] = $bAuthResult;

			$oAccount = \Aurora\System\Api::ExecuteMethod('Core::CreateAccount', array(
				'Email' => $sEmail, 
				'Password' => $sIncPassword, 
				'Language' => $sLanguage, 
				'ExtValues' => $aExtValues
			));
			if ($oAccount instanceof CAccount)
			{
				$oResult = $oAccount;
			}
			else
			{
				throw new \Aurora\System\Exceptions\ManagerException(Errs::WebMailManager_AccountCreateOnLogin);
			}
		}
		else
		{
			$oException = $oApiUsersManager->GetLastException();

			throw (is_object($oException))
				? $oException
				: new \Aurora\System\Exceptions\ManagerException(Errs::WebMailManager_AccountCreateOnLogin);
		}

		return $oResult;
	}

	/**
	 * @param int $iIdTenant
	 * @param string $sEmail
	 * @param string $sPassword
	 *
	 * @throws \Aurora\System\Exceptions\ManagerException(Errs::HelpdeskManager_AccountSystemAuthentication) 6008
	 * @throws \Aurora\System\Exceptions\ManagerException(Errs::HelpdeskManager_UnactivatedUser) 6010
	 * @throws \Aurora\System\Exceptions\ManagerException(Errs::HelpdeskManager_AccountAuthentication) 6004
	 *
	 * @return CHelpdeskUser|null|bool
	 */
	public function loginToHelpdeskAccount($iIdTenant, $sEmail, $sPassword)
	{
		$oResult = null;

//		$oApiHelpdeskManager = /* @var $oApiHelpdeskManager CApiHelpdeskManager */\Aurora\System\Api::Manager('helpdesk');
//		$oApiUsersManager = /* @var $oApiUsersManager CApiUsersManager */\Aurora\System\Api::GetSystemManager('users');
		$oApiCapabilityManager = /* @var $oApiCapabilityManager \Aurora\System\Managers\Capability\Manager */\Aurora\System\Api::GetSystemManager('capability');
		if (!$oApiHelpdeskManager || !$oApiUsersManager || !$oApiCapabilityManager ||
			!$oApiCapabilityManager->isHelpdeskSupported())
		{
			return false;
		}

		$oAccount = $oApiUsersManager->getAccountByEmail($sEmail);
		if ($oAccount && $oAccount->IdTenant === $iIdTenant && $oApiCapabilityManager->isHelpdeskSupported($oAccount) &&
			$oAccount->IncomingPassword === $sPassword)
		{
			$this->setAccountAsLoggedIn($oAccount);
			$this->setThreadIdFromRequest(0);
			throw new \Aurora\System\Exceptions\ManagerException(Errs::HelpdeskManager_AccountSystemAuthentication);
		}

		$oUser = /* @var $oUser CHelpdeskUser */ $oApiHelpdeskManager->getUserByEmail($iIdTenant, $sEmail);
		if ($oUser instanceof CHelpdeskUser && $oUser->validatePassword($sPassword) && $iIdTenant === $oUser->IdTenant)
		{
			if (!$oUser->Activated)
			{
				throw new \Aurora\System\Exceptions\ManagerException(Errs::HelpdeskManager_UnactivatedUser);
			}

			$oResult = $oUser;
		}
		else
		{
			throw new \Aurora\System\Exceptions\ManagerException(Errs::HelpdeskManager_AccountAuthentication);
		}

		return $oResult;
	}

	/**
	 * @param int $iIdTenant
	 * @param string $sEmail
	 * @param string $sName
	 * @param string $sPassword
	 * @param bool $bCreateFromFetcher Default value is **false**.
	 *
	 * @throws \Aurora\System\Exceptions\ManagerException(Errs::HelpdeskManager_UserAlreadyExists) 6001
	 * @throws \Aurora\System\Exceptions\ManagerException(Errs::HelpdeskManager_UserCreateFailed) 6002
	 *
	 * @return CHelpdeskUser|bool
	 */
	public function registerHelpdeskAccount($iIdTenant, $sEmail, $sName, $sPassword, $bCreateFromFetcher = false)
	{
		$mResult = false;

		$oApiHelpdeskManager = /* @var $oApiHelpdeskManager CApiHelpdeskManager */\Aurora\System\Api::Manager('helpdesk');
//		$oApiUsersManager = /* @var $oApiUsersManager CApiUsersManager */\Aurora\System\Api::GetSystemManager('users');
		$oApiCapabilityManager = /* @var $oApiCapabilityManager \Aurora\System\Managers\Capability\Manager */\Aurora\System\Api::GetSystemManager('capability');
		if (!$oApiHelpdeskManager || !$oApiUsersManager || !$oApiCapabilityManager ||
			!$oApiCapabilityManager->isHelpdeskSupported())
		{
			return $mResult;
		}

		$oUser = /* @var $oUser CHelpdeskUser */ $oApiHelpdeskManager->getUserByEmail($iIdTenant, $sEmail);
		if (!$oUser)
		{
			$oAccount = $oApiUsersManager->getAccountByEmail($sEmail);
			if ($oAccount && $oAccount->IdTenant === $iIdTenant && $oApiCapabilityManager->isHelpdeskSupported($oAccount))
			{
				throw new \Aurora\System\Exceptions\ManagerException(Errs::HelpdeskManager_UserAlreadyExists);
			}

			$oUser = new CHelpdeskUser();
			$oUser->Activated = false;
			$oUser->Email = $sEmail;
			$oUser->Name = $sName;
			$oUser->IdTenant = $iIdTenant;
			$oUser->IsAgent = false;

			$oUser->setPassword($sPassword, $bCreateFromFetcher);

			$oApiHelpdeskManager->createUser($oUser, $bCreateFromFetcher);
			if (!$oUser || 0 === $oUser->IdHelpdeskUser)
			{
				throw new \Aurora\System\Exceptions\ManagerException(Errs::HelpdeskManager_UserCreateFailed);
			}
			else
			{
				$mResult = $oUser;
			}
		}
		else
		{
			throw new \Aurora\System\Exceptions\ManagerException(Errs::HelpdeskManager_UserAlreadyExists);
		}

		return $mResult;
	}

	/**
	 * @param int $iIdTenant
	 * @param string $sTenantName
	 * @param string $sNotificationEmail
	 * @param string $sSocialId
	 * @param string $sSocialType
	 * @param string $sSocialName
	 *
	 * @throws \Aurora\System\Exceptions\ManagerException(Errs::HelpdeskManager_UserAlreadyExists) 6001
	 * @throws \Aurora\System\Exceptions\ManagerException(Errs::HelpdeskManager_UserCreateFailed) 6002
	 *
	 * @return bool
	 */
	public function registerSocialAccount($iIdTenant, $sTenantName, $sNotificationEmail, $sSocialId, $sSocialType, $sSocialName)
	{
		$bResult = false;

		$oApiHelpdeskManager = /* @var $oApiHelpdeskManager CApiHelpdeskManager */\Aurora\System\Api::Manager('helpdesk');
//		$oApiUsersManager = /* @var $oApiUsersManager CApiUsersManager */\Aurora\System\Api::GetSystemManager('users');
		$oApiCapabilityManager = /* @var $oApiCapabilityManager \Aurora\System\Managers\Capability\Manager */\Aurora\System\Api::GetSystemManager('capability');
		if (!$oApiHelpdeskManager || !$oApiUsersManager || !$oApiCapabilityManager ||
			!$oApiCapabilityManager->isHelpdeskSupported())
		{
			return $bResult;
		}

		$oUser = /* @var $oUser CHelpdeskUser */ $oApiHelpdeskManager->getUserBySocialId($iIdTenant, $sSocialId);
		if (!$oUser)
		{
			$oAccount = $this->getAhdSocialUser($sTenantName, $sSocialId);
			if ($oAccount && $oAccount->IdTenant === $iIdTenant && $oApiCapabilityManager->isHelpdeskSupported($oAccount))
			{
				throw new \Aurora\System\Exceptions\ManagerException(Errs::HelpdeskManager_UserAlreadyExists);
			}

			$oUser = new CHelpdeskUser();
			$oUser->Activated = true;
			$oUser->Name = $sSocialName;
			$oUser->NotificationEmail = $sNotificationEmail;
			$oUser->SocialId = $sSocialId;
			$oUser->SocialType = $sSocialType;
			$oUser->IdTenant = $iIdTenant;
			$oUser->IsAgent = false;
			$oApiHelpdeskManager->createUser($oUser);
			if (!$oUser || 0 === $oUser->IdHelpdeskUser)
			{
				throw new \Aurora\System\Exceptions\ManagerException(Errs::HelpdeskManager_UserCreateFailed);
			}
			else
			{
				$bResult = true;
			}
		}
		else
		{
			throw new \Aurora\System\Exceptions\ManagerException(Errs::HelpdeskManager_UserAlreadyExists);
		}

		return $bResult;
	}

	/**
	 * @return array
	 */
	public function getLanguageList()
	{
		static $aList = null;
		
		if (null === $aList)
		{
			$aList = array();
			$sPath =\Aurora\System\Api::WebMailPath().'modules';

			$aModuleNames = \Aurora\System\Api::GetModuleManager()->GetAllowedModulesName();

			foreach ($aModuleNames as $sModuleName)
			{
				$sModuleLangsDir = $sPath . '/' . $sModuleName . '/i18n';

				if (@is_dir($sModuleLangsDir))
				{
					$rDirH = @opendir($sModuleLangsDir);
					if ($rDirH)
					{
						while (($sFile = @readdir($rDirH)) !== false)
						{
							$sLanguage = substr($sFile, 0, -4);
							if ('.' !== $sFile{0} && is_file($sModuleLangsDir.'/'.$sFile) && '.ini' === substr($sFile, -4))
							{
								if (0 < strlen($sLanguage) && !in_array($sLanguage, $aList))
								{
									if ('english' === strtolower($sLanguage))
									{
										array_unshift($aList, $sLanguage);
									}
									else
									{
										$aList[] = $sLanguage;
									}
								}
							}
						}
						@closedir($rDirH);
					}
				} 
			}
		}
		
		return $aList;
	}

	/**
	 * @return array
	 */
	public function getThemeList()
	{
		static $sList = null;
		if (null === $sList)
		{
			$sList = array();

			$oModuleManager = \Aurora\System\Api::GetModuleManager();
			$aThemes = $oModuleManager->getModuleConfigValue('CoreWebclient', 'ThemeList');
			$sDir =\Aurora\System\Api::WebMailPath().'static/styles/themes/';

			if (is_array($aThemes))
			{
				foreach ($aThemes as $sTheme)
				{
					if (file_exists($sDir.'/'.$sTheme.'/styles.css'))
					{
						$sList[] = $sTheme;
					}
				}
			}
		}

		return $sList;
	}

	/**
	 * @param string $sHelpdeskTenantHash Default value is empty string.
	 * @param string $sCalendarPubHash Default value is empty string.
	 * @param string $sFileStoragePubHash Default value is empty string.
	 * @param string $sAuthToken Default value is empty string.
	 *
	 * @return array
	 */
	public function appData()
	{
		$aAppData = array(
			'User' => array(
				'Id' => 0,
				'Role' => \EUserRole::Anonymous,
				'Name' => '',
				'PublicId' => '',
			)
		);
		
		// AuthToken reads from coockie for HTML
		$sAuthToken = isset($_COOKIE[\Aurora\System\Application::AUTH_TOKEN_KEY]) ? $_COOKIE[\Aurora\System\Application::AUTH_TOKEN_KEY] : '';
		
		$oUser = \Aurora\System\Api::getAuthenticatedUser($sAuthToken);

		$aModules = \Aurora\System\Api::GetModules();

		foreach ($aModules as $oModule)
		{
			try
			{
				$aModuleAppData = $oModule->GetSettings();
			}
			catch (\Aurora\System\Exceptions\ApiException $oEx)
			{
				$aModuleAppData = null;
			}
			
			if (is_array($aModuleAppData))
			{
				$aAppData[$oModule->GetName()] = $aModuleAppData;
			}
		}
		
		if ($oUser)
		{
			$aAppData['User'] = array(
				'Id' => $oUser->EntityId,
				'Role' => $oUser->Role,
				'Name' => $oUser->Name,
				'PublicId' => $oUser->PublicId,
			);
		}
		else
		{
			\Aurora\System\Api::UserSession()->Delete($sAuthToken);
		}
		
		return $aAppData;
	}

	/**
	 * @depricated
	 * @param string $sHelpdeskTenantHash Default value is empty string.
	 * @param string $sUserId Default value is empty string.
	 *
	 * @throws \Aurora\System\Exceptions\ApiException(\Aurora\System\Notifications::InvalidInputParameter) 103
	 *
	 * @return CUser|bool
	 */
	public function getAhdSocialUser($sHelpdeskTenantHash = '', $sUserId = '')
	{
//		$sTenantHash = $sHelpdeskTenantHash;
//		$oApiTenant = \Aurora\System\Api::GetCoreManager('tenants');
//		$iIdTenant = $oApiTenant->getTenantIdByName($sTenantHash);
//		if (!is_int($iIdTenant))
//		{
//			throw new \Aurora\System\Exceptions\ApiException(\Aurora\System\Notifications::InvalidInputParameter);
//		}
////		$oApiHelpdeskManager =\Aurora\System\Api::Manager('helpdesk'); // TODO:
//		$oUser = $oApiHelpdeskManager->getUserBySocialId($iIdTenant, $sUserId);
//
//		return $oUser;
	}

	/**
	 * @param string $sHelpdeskHash Default value is empty string.
	 * @param string $sCalendarPubHash Default value is empty string.
	 * @param string $sFileStoragePubHash Default value is empty string.
	 *
	 * @return string
	 */
//	public function compileAppData($sHelpdeskHash = '', $sCalendarPubHash = '', $sFileStoragePubHash = '', $sAccessToken = '')
	public function compileAppData()
	{
//		return '<script>window.auroraAppData='.@json_encode($this->appData($sHelpdeskHash, $sCalendarPubHash, $sFileStoragePubHash, $sAccessToken)).';</script>';
		return '<script>window.auroraAppData='.@json_encode($this->appData()).';</script>';
	}

	/**
	 * @return array
	 */
	public function getThemeAndLanguage()
	{
		static $sLanguage = false;
		static $sTheme = false;
		static $sSiteName = false;

		if (false === $sLanguage && false === $sTheme && false === $sSiteName)
		{
			$oSettings =&\Aurora\System\Api::GetSettings();
			$oUser = \Aurora\System\Api::getAuthenticatedUser();
			$oModuleManager = \Aurora\System\Api::GetModuleManager();
			
			$sSiteName = $oSettings->GetConf('SiteName');
			$sLanguage = $oUser ? $oUser->Language : $oModuleManager->getModuleConfigValue('Core', 'Language');
			$sTheme = $oUser ? $oUser->{'CoreWebclient::Theme'} : $oModuleManager->getModuleConfigValue('CoreWebclient', 'Theme');

			$oUser = \Aurora\System\Api::getAuthenticatedUser();

			if ($oUser)
			{
				$sSiteName = '';
			}
			else
			{
			}

			$sLanguage = $this->validatedLanguageValue($sLanguage);
            $this->setLoginLanguage($sLanguage); // todo: sash
			$sTheme = $this->validatedThemeValue($sTheme);
		}
		
		/*** temporary fix to the problems in mobile version in rtl mode ***/
		
		/* @var $oApiCapability \Aurora\System\Managers\Capability\Manager */
		$oApiCapability = \Aurora\System\Api::GetSystemManager('capability');
		
		if (in_array($sLanguage, array('Arabic', 'Hebrew', 'Persian')) && $oApiCapability && $oApiCapability->isNotLite() && 1 === $this->isMobile())
		{
			$sLanguage = 'English';
		}
		
		/*** end of temporary fix to the problems in mobile version in rtl mode ***/

		return array($sLanguage, $sTheme, $sSiteName);
	}

	private function getBrowserLanguage()
	{
		$aLanguages = array(
			'ar-dz' => 'Arabic', 'ar-bh' => 'Arabic', 'ar-eg' => 'Arabic', 'ar-iq' => 'Arabic', 'ar-jo' => 'Arabic', 'ar-kw' => 'Arabic',
			'ar-lb' => 'Arabic', 'ar-ly' => 'Arabic', 'ar-ma' => 'Arabic', 'ar-om' => 'Arabic', 'ar-qa' => 'Arabic', 'ar-sa' => 'Arabic',
			'ar-sy' => 'Arabic', 'ar-tn' => 'Arabic', 'ar-ae' => 'Arabic', 'ar-ye' => 'Arabic', 'ar' => 'Arabic',
			'bg' => 'Bulgarian',
			'zh-cn' => 'Chinese-Simplified', 'zh-hk' => 'Chinese-Simplified', 'zh-mo' => 'Chinese-Simplified', 'zh-sg' => 'Chinese-Simplified',
			'zh-tw' => 'Chinese-Simplified', 'zh' => 'Chinese-Simplified',
			'cs' => 'Czech',
			'da' => 'Danish',
			'nl-be' => 'Dutch', 'nl' => 'Dutch',
			'en-au' => 'English', 'en-bz' => 'English ', 'en-ca' => 'English', 'en-ie' => 'English', 'en-jm' => 'English',
			'en-nz' => 'English', 'en-ph' => 'English', 'en-za' => 'English', 'en-tt' => 'English', 'en-gb' => 'English',
			'en-us' => 'English', 'en-zw' => 'English', 'en' => 'English', 'us' => 'English',
			'et' => 'Estonian', 'fi' => 'Finnish',
			'fr-be' => 'French', 'fr-ca' => 'French', 'fr-lu' => 'French', 'fr-mc' => 'French', 'fr-ch' => 'French', 'fr' => 'French',
			'de-at' => 'German', 'de-li' => 'German', 'de-lu' => 'German', 'de-ch' => 'German', 'de' => 'German',
			'el' => 'Greek', 'he' => 'Hebrew', 'hu' => 'Hungarian', 'it-ch' => 'Italian', 'it' => 'Italian',
			'ja' => 'Japanese', 'ko' => 'Korean', 'lv' => 'Latvian', 'lt' => 'Lithuanian',
			'nb-no' => 'Norwegian', 'nn-no' => 'Norwegian', 'no' => 'Norwegian', 'pl' => 'Polish',
			'pt-br' => 'Portuguese-Brazil', 'pt' => 'Portuguese-Portuguese', 'pt-pt' => 'Portuguese-Portuguese',
			'ro-md' => 'Romanian', 'ro' => 'Romanian',
			'ru-md' => 'Russian', 'ru' => 'Russian', 'sr' => 'Serbian',
			'es-ar' => 'Spanish', 'es-bo' => 'Spanish', 'es-cl' => 'Spanish', 'es-co' => 'Spanish', 'es-cr' => 'Spanish',
			'es-do' => 'Spanish', 'es-ec' => 'Spanish', 'es-sv' => 'Spanish', 'es-gt' => 'Spanish', 'es-hn' => 'Spanish)',
			'es-mx' => 'Spanish', 'es-ni' => 'Spanish', 'es-pa' => 'Spanish', 'es-py' => 'Spanish', 'es-pe' => 'Spanish',
			'es-pr' => 'Spanish', 'es-us' => 'Spanish ', 'es-uy' => 'Spanish', 'es-ve' => 'Spanish', 'es' => 'Spanish',
			'sv-fi' => 'Swedish', 'sv' => 'Swedish', 'th' => 'Thai', 'tr' => 'Turkish', 'uk' => 'Ukrainian', 'vi' => 'Vietnamese', 'sl' => 'Slovenian'
		);
		
		$sLanguage = !empty($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? strtolower($_SERVER['HTTP_ACCEPT_LANGUAGE']) : 'en';
		$aTempLanguages = preg_split('/[,;]+/', $sLanguage);
		$sLanguage = !empty($aTempLanguages[0]) ? $aTempLanguages[0] : 'en';

		$sLanguageShort = substr($sLanguage, 0, 2);
		
		return \array_key_exists($sLanguage, $aLanguages) ? $aLanguages[$sLanguage] :
			(\array_key_exists($sLanguageShort, $aLanguages) ? $aLanguages[$sLanguageShort] : '');
	}

	/**
	 * Indicates if rtl interface should be turned on.
	 * 
	 * @return bool
	 */
	public function IsRtl()
	{
		list($sLanguage, $sTheme, $sSiteName) = $this->getThemeAndLanguage();
		return \in_array($sLanguage, array('Arabic', 'Hebrew', 'Persian'));
	}
	
	/**
	 * Returns css links for building in html.
	 * 
	 * @return string
	 */
	public function buildHeadersLink()
	{
		list($sLanguage, $sTheme, $sSiteName) = $this->getThemeAndLanguage();
		$sMobileSuffix = \Aurora\System\Api::IsMobileApplication() ? '-mobile' : '';
		$sTenantName = \Aurora\System\Api::getTenantName();
		$oSettings =&\Aurora\System\Api::GetSettings();
		
		if ($oSettings->GetConf('EnableMultiTenant') && $sTenantName)
		{
			$sS =
'<link type="text/css" rel="stylesheet" href="./static/styles/libs/libs.css'.'?'.\Aurora\System\Api::VersionJs().'" />'.
'<link type="text/css" rel="stylesheet" href="./tenants/'.$sTenantName.'/static/styles/themes/'.$sTheme.'/styles'.$sMobileSuffix.'.css'.'?'.\Aurora\System\Api::VersionJs().'" />';
		}
		else
		{
			$sS =
'<link type="text/css" rel="stylesheet" href="./static/styles/libs/libs.css'.'?'.\Aurora\System\Api::VersionJs().'" />'.
'<link type="text/css" rel="stylesheet" href="./static/styles/themes/'.$sTheme.'/styles'.$sMobileSuffix.'.css'.'?'.\Aurora\System\Api::VersionJs().'" />';
		}
		
		return $sS;
	}
	
	/**
	 * Returns css links for building in html.
	 * 
	 * @param string $sModuleHash
	 * @return string
	 */
//	public function getJsLinks($sModuleHash)
	public function getJsLinks($aConfig = array())
	{
		$oSettings =& \Aurora\System\Api::GetSettings();
		$sPostfix = '';
//		if ($sModuleHash !== '')
//		{
//			$sPostfix = $sModuleHash;
//		}
		
		if ($oSettings->GetConf('UseAppMinJs', false))
		{
//			$sPostfix = $sPostfix.'.min';
			$sPostfix .= '.min';
		}
		
		$sTenantName = \Aurora\System\Api::getTenantName();
		
		$sJsScriptPath = $oSettings->GetConf('EnableMultiTenant') && $sTenantName ? "./tenants/".$sTenantName."/" : "./";
		
		if (isset($aConfig['modules_list']))
		{
			$aClientModuleNames = $aConfig['modules_list'];
		}
		else
		{
			$aModuleNames = \Aurora\System\Api::GetModuleManager()->GetAllowedModulesName();
			$sModulesPath = \Aurora\System\Api::GetModuleManager()->GetModulesPath();
			foreach ($aModuleNames as $sModuleName)
			{
				if (file_exists($sModulesPath . $sModuleName . '/js/manager.js'))
				{
					$aClientModuleNames[] = $sModuleName;
				}
			}
		}
		
		$bIsPublic = isset($aConfig['public_app']) ? (bool)$aConfig['public_app'] : false;
		$bIsNewTab = isset($aConfig['new_tab']) ? (bool)$aConfig['new_tab'] : false;
		
		return '<script>window.isPublic = '.($bIsPublic ? 'true' : 'false').'; window.isNewTab = '.($bIsNewTab ? 'true' : 'false').'; window.aAvaliableModules = ["'.implode('","', $aClientModuleNames).'"];</script>
		<script src="'.$sJsScriptPath."static/js/app".$sPostfix.".js?".\Aurora\System\Api::VersionJs().'"></script>';
	}
	
	/**
	 * Returns application html.
	 * 
	 * @param string $sModuleHash
	 * @return string
	 */
//	public function buildBody($sModuleHash = '')
	public function buildBody($aConfig)
	{
		list($sLanguage, $sTheme, $sSiteName) = $this->getThemeAndLanguage();
		return
$this->compileTemplates()."\r\n".
$this->compileLanguage($sLanguage)."\r\n".
$this->compileAppData()."\r\n".
//$this->getJsLinks($sModuleHash).
$this->getJsLinks($aConfig).
"\r\n".'<!-- '.\Aurora\System\Api::Version().' -->'
		;
	}
}
