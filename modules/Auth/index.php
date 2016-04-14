<?php

class AuthModule extends AApiModule
{
	public $oApiAccountsManager = null;
	
	/**
	 * @return array
	 */
	public function init()
	{
		parent::init();
		
		$this->oApiAccountsManager = $this->GetManager('accounts', 'db');
		
		$this->subscribeEvent('Auth::Login', array($this, 'checkAuth'));
	}
	
	public function IsAuth()
	{
		$mResult = false;
		$oAccount = $this->getDefaultAccountFromParam(false);
		if ($oAccount) {
			
			$sClientTimeZone = trim($this->getParamValue('ClientTimeZone', ''));
			if ('' !== $sClientTimeZone) {
				
				$oAccount->User->ClientTimeZone = $sClientTimeZone;
				$oApiUsers = \CApi::GetCoreManager('users');
				if ($oApiUsers) {
					
					$oApiUsers->updateAccount($oAccount);
				}
			}

			$mResult = array();
			$mResult['Extensions'] = array();

			// extensions
			if ($oAccount->isExtensionEnabled(\CAccount::IgnoreSubscribeStatus) &&
				!$oAccount->isExtensionEnabled(\CAccount::DisableManageSubscribe)) {
				
				$oAccount->enableExtension(\CAccount::DisableManageSubscribe);
			}

			$aExtensions = $oAccount->getExtensionList();
			foreach ($aExtensions as $sExtensionName) {
				
				if ($oAccount->isExtensionEnabled($sExtensionName)) {
					
					$mResult['Extensions'][] = $sExtensionName;
				}
			}
		}

		return $mResult;
	}	
	
	/**
	 * @return array
	 */
	/*public function Login()
	{
		setcookie('aft-cache-ctrl', '', time() - 3600);
		$sEmail = trim((string) $this->getParamValue('Email', ''));
		$sIncLogin = (string) $this->getParamValue('IncLogin', '');
		$sIncPassword = (string) $this->getParamValue('IncPassword', '');
		$sLanguage = (string) $this->getParamValue('Language', '');

		$bSignMe = '1' === (string) $this->getParamValue('SignMe', '0');

		$oApiIntegrator = \CApi::GetCoreManager('integrator');
		try
		{
			\CApi::Plugin()->RunHook(
					'webmail-login-custom-data', 
					array($this->getParamValue('CustomRequestData', null))
			);
		}
		catch (\Exception $oException)
		{
			\CApi::LogEvent(\EEvents::LoginFailed, $sEmail);
			throw $oException;
		}

		$sAtDomain = trim(\CApi::GetSettingsConf('WebMail/LoginAtDomainValue'));
		if ((\ELoginFormType::Email === (int) \CApi::GetSettingsConf('WebMail/LoginFormType') || 
				\ELoginFormType::Both === (int) \CApi::GetSettingsConf('WebMail/LoginFormType')) && 
				0 === strlen($sAtDomain) && 0 < strlen($sEmail) && !\MailSo\Base\Validator::EmailString($sEmail))
		{
			throw new \Core\Exceptions\ClientException(\Core\Notifications::AuthError);
		}

		if (\ELoginFormType::Login === (int) \CApi::GetSettingsConf('WebMail/LoginFormType') && 0 < strlen($sAtDomain))
		{
			$sEmail = \api_Utils::GetAccountNameFromEmail($sIncLogin).'@'.$sAtDomain;
			$sIncLogin = $sEmail;
		}

		if (0 === strlen($sIncPassword) || 0 === strlen($sEmail.$sIncLogin)) {
			
			throw new \Core\Exceptions\ClientException(\Core\Notifications::InvalidInputParameter);
		}

		try
		{
			if (0 === strlen($sLanguage)) {
				
				$sLanguage = $oApiIntegrator->getLoginLanguage();
			}

			$oAccount = $oApiIntegrator->loginToAccount(
					$sEmail, 
					$sIncPassword, 
					$sIncLogin, 
					$sLanguage
			);
		}
		catch (\Exception $oException)
		{
			$iErrorCode = \Core\Notifications::UnknownError;
			if ($oException instanceof \CApiManagerException)
			{
				switch ($oException->getCode())
				{
					case \Errs::WebMailManager_AccountDisabled:
					case \Errs::WebMailManager_AccountWebmailDisabled:
						$iErrorCode = \Core\Notifications::AuthError;
						break;
					case \Errs::UserManager_AccountAuthenticationFailed:
					case \Errs::WebMailManager_AccountAuthentication:
					case \Errs::WebMailManager_NewUserRegistrationDisabled:
					case \Errs::WebMailManager_AccountCreateOnLogin:
					case \Errs::Mail_AccountAuthentication:
					case \Errs::Mail_AccountLoginFailed:
						$iErrorCode = \Core\Notifications::AuthError;
						break;
					case \Errs::UserManager_AccountConnectToMailServerFailed:
					case \Errs::WebMailManager_AccountConnectToMailServerFailed:
					case \Errs::Mail_AccountConnectToMailServerFailed:
						$iErrorCode = \Core\Notifications::MailServerError;
						break;
					case \Errs::UserManager_LicenseKeyInvalid:
					case \Errs::UserManager_AccountCreateUserLimitReached:
					case \Errs::UserManager_LicenseKeyIsOutdated:
					case \Errs::TenantsManager_AccountCreateUserLimitReached:
						$iErrorCode = \Core\Notifications::LicenseProblem;
						break;
					case \Errs::Db_ExceptionError:
						$iErrorCode = \Core\Notifications::DataBaseError;
						break;
				}
			}

			\CApi::LogEvent(\EEvents::LoginFailed, $sEmail);
			throw new \Core\Exceptions\ClientException($iErrorCode, $oException,
				$oException instanceof \CApiBaseException ? $oException->GetPreviousMessage() :
				($oException ? $oException->getMessage() : ''));
		}

		if ($oAccount instanceof \CAccount)
		{
			$sAuthToken = '';
			$bSetAccountAsLoggedIn = true;
			\CApi::Plugin()->RunHook(
					'api-integrator-set-account-as-logged-in', 
					array(&$bSetAccountAsLoggedIn)
			);

			if ($bSetAccountAsLoggedIn) {
				
				\CApi::LogEvent(\EEvents::LoginSuccess, $oAccount);
				$sAuthToken = $oApiIntegrator->setAccountAsLoggedIn($oAccount, $bSignMe);
			}
			
			return array(
				'AuthToken' => $sAuthToken
			);
		}

		\CApi::LogEvent(\EEvents::LoginFailed, $oAccount);
		throw new \Core\Exceptions\ClientException(\Core\Notifications::AuthError);
	}*/
	
	/**
	 * @return array
	 */
	/*public function Logout()
	{
		setcookie('aft-cache-ctrl', '', time() - 3600);
		$sAuthToken = (string) $this->getParamValue('AuthToken', '');
		$oAccount = $this->getDefaultAccountFromParam(false);

		$oApiIntegrator = \CApi::GetCoreManager('integrator');

		if ($oAccount && $oAccount->User && 0 < $oAccount->User->IdHelpdeskUser &&
			$this->oApiCapabilityManager->isHelpdeskSupported($oAccount)) {
			
			$oApiIntegrator->logoutHelpdeskUser();
		}

		$sLastErrorCode = $this->getParamValue('LastErrorCode');
		if (0 < strlen($sLastErrorCode) && $oApiIntegrator && 0 < (int) $sLastErrorCode)
		{
			$oApiIntegrator->setLastErrorCode((int) $sLastErrorCode);
		}

		\CApi::LogEvent(\EEvents::Logout, $oAccount);
		return $oApiIntegrator->logoutAccount($sAuthToken);
	}*/
	
	public function Login($sLogin, $sPassword, $sPassword1 = null)
	{
		$mResult = false;

		if ($sPassword1)
		{
			$this->broadcastEvent('Login', array(
				'login' => $sLogin,
				'password' => $sPassword1,
				'result' => &$mResult
			));
		}
		else
		{
			$this->broadcastEvent('Login', array(
				'login' => $sLogin,
				'password' => $sPassword,
				'result' => &$mResult
			));
		}

		if ($mResult instanceOf CAccount)
		{
			$bSignMe = true;
			
			$aAccountHashTable = array(
				'token' => 'auth',
				'sign-me' => $bSignMe,
				'id' => $mResult->IdUser, //$oAccount->IdUser,
				'email' => 'vasil@afterlogic.com' //$oAccount->Email
			);

			$iTime = $bSignMe ? time() + 60 * 60 * 24 * 30 : 0;
			$sAccountHashTable = \CApi::EncodeKeyValues($aAccountHashTable);

			$sAuthToken = \md5(\microtime(true).\rand(10000, 99999));

			$sAuthToken = \CApi::Cacher()->Set('AUTHTOKEN:'.$sAuthToken, $sAccountHashTable) ? $sAuthToken : '';
			
			return array(
				'AuthToken' => $sAuthToken
			);
		}
		
//		\CApi::LogEvent(\EEvents::LoginFailed, $oAccount);
		throw new \Core\Exceptions\ClientException(\Core\Notifications::AuthError);
	}
	
	public function Logout($sAuthToken = '')
	{	
		if (!empty($sAuthToken))
		{
			\CApi::Cacher()->Delete('AUTHTOKEN:'.$sAuthToken);
		}
		else
		{
			throw new \Core\Exceptions\ClientException(\Auth\Notifications::IncorrentAuthToken);
		}

		return true;
	}
	
	public function checkAuth($sLogin, $sPassword, &$mResult)
	{
		$oAccount = $this->oApiAccountsManager->getAccountByCredentials($sLogin, $sPassword);
	
		if ($oAccount)
		{
			$mResult = $oAccount;
		}
		
//		$mResult = ($sLogin === 'vasil' || $sLogin === 'sash') && $sPassword === 'p12345' ;
	}
	
	/**
	 * 
	 * @return boolean
	 */
	public function CreateAccount($iUserId = 0, $sLogin = '', $sPassword = '')
	{
//		$oAccount = $this->getDefaultAccountFromParam();

//		$oApiIntegrator = \CApi::GetCoreManager('integrator');
//		$iUserId = $oApiIntegrator->getLogginedUserId($this->getParamValue('AuthToken'));
		
		$oEventResult = null;
		$this->broadcastEvent('CreateAccount', array(
			'IdUser' => $iUserId,
			'login' => $sLogin,
			'password' => $sPassword,
			'result' => &$oEventResult
		));
		
		//	if ($this->oApiCapabilityManager->isPersonalContactsSupported($oAccount))
		if ($oEventResult instanceOf \CUser)
		{
			$oAccount = \CAccount::createInstance();
			
			$oAccount->IdUser = $oEventResult->iObjectId;
			$oAccount->Login = $sLogin;
			$oAccount->Password = $sPassword;

			$this->oApiAccountsManager->createAccount($oAccount);
			return $oAccount ? array(
				'iObjectId' => $oAccount->iObjectId
			) : false;
		}
		else
		{
			throw new \Core\Exceptions\ClientException(\Core\Notifications::NonUserPassed);
		}

		return false;
	}
	
	/**
	 * 
	 * @return boolean
	 */
	public function UpdateAccount($iAccountId = 0, $sLogin = '', $sPassword = '')
	{
//		$oAccount = $this->getDefaultAccountFromParam();
		
//		if ($this->oApiCapabilityManager->isPersonalContactsSupported($oAccount))
		
		if ($iAccountId > 0)
		{
			$oAccount = $this->oApiAccountsManager->getAccountById($iAccountId);
			
			if ($oAccount)
			{
				if ($sLogin) {
					$oAccount->Login = $sLogin;
				}
				if ($sPassword) {
					$oAccount->Password = $sPassword;
				}
				

				$this->oApiAccountsManager->updateAccount($oAccount);
			}
			
			return $oAccount ? array(
				'iObjectId' => $oAccount->iObjectId
			) : false;
		}
		else
		{
			throw new \Core\Exceptions\ClientException(\Core\Notifications::UserNotAllowed);
		}

		return false;
	}
	
	/**
	 * 
	 * @return boolean
	 */
	public function DeleteAccount($iAccountId = 0)
	{
		$bResult = false;

		if ($iAccountId > 0)
		{
			
			$oAccount = $this->oApiAccountsManager->getAccountById($iAccountId);
			
			if ($oAccount)
			{
				$bResult = $this->oApiAccountsManager->deleteAccount($oAccount);
			}
			
			return $bResult;
		}
		else
		{
			throw new \Core\Exceptions\ClientException(\Core\Notifications::UserNotAllowed);
		}
	}
}

return new AuthModule('1.0');
