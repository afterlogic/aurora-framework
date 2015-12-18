<?php

class AuthModule extends AApiModule
{
	/**
	 * @return array
	 */
	public function IsAuth()
	{
		$mResult = false;
		$oAccount = $this->getAccountFromParam(false);
		if ($oAccount)
		{
			$sClientTimeZone = trim($this->getParamValue('ClientTimeZone', ''));
			if ('' !== $sClientTimeZone)
			{
				$oAccount->User->ClientTimeZone = $sClientTimeZone;
				$oApiUsers = \CApi::GetCoreManager('users');
				if ($oApiUsers)
				{
					$oApiUsers->updateAccount($oAccount);
				}
			}

			$mResult = array();
			$mResult['Extensions'] = array();

			// extensions
			if ($oAccount->isExtensionEnabled(\CAccount::IgnoreSubscribeStatus) &&
				!$oAccount->isExtensionEnabled(\CAccount::DisableManageSubscribe))
			{
				$oAccount->enableExtension(\CAccount::DisableManageSubscribe);
			}

			$aExtensions = $oAccount->getExtensionList();
			foreach ($aExtensions as $sExtensionName)
			{
				if ($oAccount->isExtensionEnabled($sExtensionName))
				{
					$mResult['Extensions'][] = $sExtensionName;
				}
			}
		}

		return $mResult;
	}	
	
	/**
	 * @return array
	 */
	public function Login()
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
			\CApi::Plugin()->RunHook('webmail-login-custom-data', array($this->getParamValue('CustomRequestData', null)));
		}
		catch (\Exception $oException)
		{
			\CApi::LogEvent(\EEvents::LoginFailed, $sEmail);
			throw $oException;
		}

		$sAtDomain = trim(\CApi::GetSettingsConf('WebMail/LoginAtDomainValue'));
		if ((\ELoginFormType::Email === (int) \CApi::GetSettingsConf('WebMail/LoginFormType') || \ELoginFormType::Both === (int) \CApi::GetSettingsConf('WebMail/LoginFormType')) && 0 === strlen($sAtDomain) && 0 < strlen($sEmail) && !\MailSo\Base\Validator::EmailString($sEmail))
		{
			throw new \Core\Exceptions\ClientException(\Core\Notifications::AuthError);
		}

		if (\ELoginFormType::Login === (int) \CApi::GetSettingsConf('WebMail/LoginFormType') && 0 < strlen($sAtDomain))
		{
			$sEmail = \api_Utils::GetAccountNameFromEmail($sIncLogin).'@'.$sAtDomain;
			$sIncLogin = $sEmail;
		}

		if (0 === strlen($sIncPassword) || 0 === strlen($sEmail.$sIncLogin))
		{
			\CApi::LogEvent(\EEvents::LoginFailed, $sEmail);
			
			\CApi::Log($sEmail, \ELogLevel::Full, 'a-');
			\CApi::Log($sIncLogin, \ELogLevel::Full, 'a-');
			\CApi::Log($sIncPassword, \ELogLevel::Full, 'a-');
			
			throw new \Core\Exceptions\ClientException(\Core\Notifications::InvalidInputParameter);
		}

		try
		{
			if (0 === strlen($sLanguage))
			{
				$sLanguage = $oApiIntegrator->getLoginLanguage();
			}

			$oAccount = $oApiIntegrator->loginToAccount($sEmail, $sIncPassword, $sIncLogin, $sLanguage);
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
			\CApi::Plugin()->RunHook('api-integrator-set-account-as-logged-in', array(&$bSetAccountAsLoggedIn));

			if ($bSetAccountAsLoggedIn)
			{
				\CApi::LogEvent(\EEvents::LoginSuccess, $oAccount);
				$sAuthToken = $oApiIntegrator->setAccountAsLoggedIn($oAccount, $bSignMe);
			}
			
			return array(
				'AuthToken' => $sAuthToken
			);
		}

		\CApi::LogEvent(\EEvents::LoginFailed, $oAccount);
		throw new \Core\Exceptions\ClientException(\Core\Notifications::AuthError);
	}
	
	/**
	 * @return array
	 */
	public function Logout()
	{
		setcookie('aft-cache-ctrl', '', time() - 3600);
		$sAuthToken = (string) $this->getParamValue('AuthToken', '');
		$oAccount = $this->getAccountFromParam(false);

		$oApiIntegrator = \CApi::GetCoreManager('integrator');

		if ($oAccount && $oAccount->User && 0 < $oAccount->User->IdHelpdeskUser &&
			$this->oApiCapabilityManager->isHelpdeskSupported($oAccount))
		{
			$oApiIntegrator->logoutHelpdeskUser();
		}

		$sLastErrorCode = $this->getParamValue('LastErrorCode');
		if (0 < strlen($sLastErrorCode) && $oApiIntegrator && 0 < (int) $sLastErrorCode)
		{
			$oApiIntegrator->setLastErrorCode((int) $sLastErrorCode);
		}

		\CApi::LogEvent(\EEvents::Logout, $oAccount);
		return $oApiIntegrator->logoutAccount($sAuthToken);
	}	
}

return new AuthModule('1.0');
