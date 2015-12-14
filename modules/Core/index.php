<?php

class CoreModule extends AApiModule
{
	public function init() {
		parent::init();
		
		$this->AddEntry('ping', 'EntryPing');
		$this->AddEntry('pull', 'EntryPull');
		$this->AddEntry('plugins', 'EntryPlugins');
		$this->AddEntry('mobile', 'EntryMobile');
		$this->AddEntry('speclogon', 'EntrySpeclogon');
		$this->AddEntry('speclogoff', 'EntrySpeclogoff');
		$this->AddEntry('sso', 'EntrySso');
		$this->AddEntry('autodiscover', 'EntryAutodiscover');
		$this->AddEntry('postlogin', 'EntryPostlogin');
	}
	
	/**
	 * @return array
	 */
	public function DoServerInitializations()
	{
		$oAccount = $this->getAccountFromParam();

		$bResult = false;

		$oApiIntegrator = \CApi::GetCoreManager('integrator');

		if ($oAccount && $oApiIntegrator)
		{
			$oApiIntegrator->resetCookies();
		}

		if ($this->oApiCapabilityManager->isGlobalContactsSupported($oAccount, true))
		{
			$bResult = \CApi::GetModuleManager()->Execute('Contacts', 'SynchronizeExternalContacts', array('Account' => $oAccount));
		}

		$oCacher = \CApi::Cacher();

		$bDoGC = false;
		$bDoHepdeskClear = false;
		if ($oCacher && $oCacher->IsInited())
		{
			$iTime = $oCacher->GetTimer('Cache/ClearFileCache');
			if (0 === $iTime || $iTime + 60 * 60 * 24 < time())
			{
				if ($oCacher->SetTimer('Cache/ClearFileCache'))
				{
					$bDoGC = true;
				}
			}

			if (\CApi::GetModuleManager()->ModuleExists('Helpdesk'))
			{
				$iTime = $oCacher->GetTimer('Cache/ClearHelpdeskUsers');
				if (0 === $iTime || $iTime + 60 * 60 * 24 < time())
				{
					if ($oCacher->SetTimer('Cache/ClearHelpdeskUsers'))
					{
						$bDoHepdeskClear = true;
					}
				}
			}
		}

		if ($bDoGC)
		{
			\CApi::Log('GC: FileCache / Start');
			$oApiFileCache = \Capi::GetCoreManager('filecache');
			$oApiFileCache->gc();
			$oCacher->gc();
			\CApi::Log('GC: FileCache / End');
		}

		if ($bDoHepdeskClear && \CApi::GetModuleManager()->ModuleExists('Helpdesk'))
		{
			\CApi::GetModuleManager()->ExecuteMethod('Helpdesk', 'ClearUnregistredUsers');
			\CApi::GetModuleManager()->ExecuteMethod('Helpdesk', 'ClearAllOnline');
		}

		return $bResult;
	}
	
	/**
	 * @return array
	 */
	public function Noop()
	{
		return true;
	}

	/**
	 * @return array
	 */
	public function Ping()
	{
		return 'Pong';
	}	
	
	/**
	 * @return array
	 */
	public function GetAppData()
	{
		$oApiIntegratorManager = \CApi::GetCoreManager('integrator');
		$sAuthToken = (string) $this->getParamValue('AuthToken', '');
		return $oApiIntegratorManager ? $oApiIntegratorManager->appData(false, '', '', '', $sAuthToken) : false;
	}	
	
	public function EntryPull()
	{
		if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN')
		{
			pclose(popen("start /B git pull", "r"));
		}
		else 
		{
			exec("git pull > /dev/null 2>&1 &");
		}
	}
	
	public function EntryPing()
	{
		@header('Content-Type: text/plain; charset=utf-8');
		return 'Pong';
	}
	
	public function EntryPlugins()
	{
		$sResult = '';
		$aPaths = $this->oHttp->GetPath();
		$sType = !empty($aPaths[1]) ? trim($aPaths[1]) : '';
		if ('js' === $sType)
		{
			@header('Content-Type: application/javascript; charset=utf-8');
			$sResult = \CApi::Plugin()->CompileJs();
		}
		else if ('images' === $sType)
		{
			if (!empty($aPaths[2]) && !empty($aPaths[3]))
			{
				$oPlugin = \CApi::Plugin()->GetPluginByName($aPaths[2]);
				if ($oPlugin)
				{
					echo $oPlugin->GetImage($aPaths[3]);exit;
				}
			}
		}
		else if ('fonts' === $sType)
		{
			if (!empty($aPaths[2]) && !empty($aPaths[3]))
			{
				$oPlugin = \CApi::Plugin()->GetPluginByName($aPaths[2]);
				if ($oPlugin)
				{
					echo $oPlugin->GetFont($aPaths[3]);exit;
				}
			}
		}	
		
		return $sResult;
	}	
	
	public function EntryMobile()
	{
		if ($this->oApiCapabilityManager->isNotLite())
		{
			$oApiIntegrator = \CApi::GetCoreManager('integrator');
			$oApiIntegrator->setMobile(true);
		}

		\CApi::Location('./');
	}
	
	public function EntrySpeclogon()
	{
		\CApi::SpecifiedUserLogging(true);
		\CApi::Location('./');
	}
	
	public function EntrySpeclogoff()
	{
		\CApi::SpecifiedUserLogging(false);
		\CApi::Location('./');
	}

	public function EntrySso()
	{
		$oApiIntegratorManager = \CApi::GetCoreManager('integrator');

		try
		{
			$sHash = $this->oHttp->GetRequest('hash');
			if (!empty($sHash))
			{
				$sData = \CApi::Cacher()->get('SSO:'.$sHash, true);
				$aData = \CApi::DecodeKeyValues($sData);

				if (!empty($aData['Email']) && isset($aData['Password'], $aData['Login']))
				{
					$oAccount = $oApiIntegratorManager->loginToAccount($aData['Email'], $aData['Password'], $aData['Login']);
					if ($oAccount)
					{
						$oApiIntegratorManager->setAccountAsLoggedIn($oAccount);
					}
				}
			}
			else
			{
				$oApiIntegratorManager->logoutAccount();
			}
		}
		catch (\Exception $oExc)
		{
			\CApi::LogException($oExc);
		}

		\CApi::Location('./');		
	}	
	
	public function EntryAutodiscover()
	{
		$oSettings =& \CApi::GetSettings();

		$sInput = \file_get_contents('php://input');

		\CApi::Log('#autodiscover:');
		\CApi::LogObject($sInput);

		$aMatches = array();
		$aEmailAddress = array();
		\preg_match("/\<AcceptableResponseSchema\>(.*?)\<\/AcceptableResponseSchema\>/i", $sInput, $aMatches);
		\preg_match("/\<EMailAddress\>(.*?)\<\/EMailAddress\>/", $sInput, $aEmailAddress);
		if (!empty($aMatches[1]) && !empty($aEmailAddress[1]))
		{
			$sIncMailServer = trim($oSettings->GetConf('WebMail/ExternalHostNameOfLocalImap'));
			$sOutMailServer = trim($oSettings->GetConf('WebMail/ExternalHostNameOfLocalSmtp'));

			if (0 < \strlen($sIncMailServer) && 0 < \strlen($sOutMailServer))
			{
				$iIncMailPort = 143;
				$iOutMailPort = 25;

				$aMatch = array();
				if (\preg_match('/:([\d]+)$/', $sIncMailServer, $aMatch) && !empty($aMatch[1]) && is_numeric($aMatch[1]))
				{
					$sIncMailServer = preg_replace('/:[\d]+$/', $sIncMailServer, '');
					$iIncMailPort = (int) $aMatch[1];
				}

				$aMatch = array();
				if (\preg_match('/:([\d]+)$/', $sOutMailServer, $aMatch) && !empty($aMatch[1]) && is_numeric($aMatch[1]))
				{
					$sOutMailServer = preg_replace('/:[\d]+$/', $sOutMailServer, '');
					$iOutMailPort = (int) $aMatch[1];
				}

				$sResult = \implode("\n", array(
'<Autodiscover xmlns="http://schemas.microsoft.com/exchange/autodiscover/responseschema/2006">',
'	<Response xmlns="'.$aMatches[1].'">',
'		<Account>',
'			<AccountType>email</AccountType>',
'			<Action>settings</Action>',
'			<Protocol>',
'				<Type>IMAP</Type>',
'				<Server>'.$sIncMailServer.'</Server>',
'				<LoginName>'.$aEmailAddress[1].'</LoginName>',
'				<Port>'.$iIncMailPort.'</Port>',
'				<SSL>'.(993 === $iIncMailPort ? 'on' : 'off').'</SSL>',
'				<SPA>off</SPA>',
'				<AuthRequired>on</AuthRequired>',
'			</Protocol>',
'			<Protocol>',
'				<Type>SMTP</Type>',
'				<Server>'.$sOutMailServer.'</Server>',
'				<LoginName>'.$aEmailAddress[1].'</LoginName>',
'				<Port>'.$iOutMailPort.'</Port>',
'				<SSL>'.(465 === $iOutMailPort ? 'on' : 'off').'</SSL>',
'				<SPA>off</SPA>',
'				<AuthRequired>on</AuthRequired>',
'			</Protocol>',
'		</Account>',
'	</Response>',
'</Autodiscover>'));
			}
		}

		if (empty($sResult))
		{
			$usec = $sec = 0;
			list($usec, $sec) = \explode(' ', microtime());
			$sResult = \implode("\n", array('<Autodiscover xmlns="http://schemas.microsoft.com/exchange/autodiscover/responseschema/2006">',
(empty($aMatches[1]) ?
'	<Response>' :
'	<Response xmlns="'.$aMatches[1].'">'
),
'		<Error Time="'.\gmdate('H:i:s', $sec).\substr($usec, 0, \strlen($usec) - 2).'" Id="2477272013">',
'			<ErrorCode>600</ErrorCode>',
'			<Message>Invalid Request</Message>',
'			<DebugData />',
'		</Error>',
'	</Response>',
'</Autodiscover>'));
		}

		header('Content-Type: text/xml');
		$sResult = '<'.'?xml version="1.0" encoding="utf-8"?'.'>'."\n".$sResult;

		\CApi::Log('');
		\CApi::Log($sResult);		
	}	
	
	public function EntryPostlogin()
	{
		if (\CApi::GetConf('labs.allow-post-login', false))
		{
			$oSettings =& \CApi::GetSettings();
			$oApiIntegrator = \CApi::GetCoreManager('integrator');
					
			$sEmail = trim((string) $this->oHttp->GetRequest('Email', ''));
			$sLogin = (string) $this->oHttp->GetRequest('Login', '');
			$sPassword = (string) $this->oHttp->GetRequest('Password', '');

			$sAtDomain = trim($oSettings->GetConf('WebMail/LoginAtDomainValue'));
			if (\ELoginFormType::Login === (int) $oSettings->GetConf('WebMail/LoginFormType') && 0 < strlen($sAtDomain))
			{
				$sEmail = \api_Utils::GetAccountNameFromEmail($sLogin).'@'.$sAtDomain;
				$sLogin = $sEmail;
			}

			if (0 !== strlen($sPassword) && 0 !== strlen($sEmail.$sLogin))
			{
				try
				{
					$oAccount = $oApiIntegrator->loginToAccount($sEmail, $sPassword, $sLogin);
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
					$sReditectUrl = \CApi::GetConf('labs.post-login-error-redirect-url', './');
					\CApi::Location($sReditectUrl . '?error=' . $iErrorCode);
					exit;
				}

				if ($oAccount instanceof \CAccount)
				{
					$oApiIntegrator->setAccountAsLoggedIn($oAccount);
				}
			}

			\CApi::Location('./');
		}
	}	
	
	
	/**
	 * @return array
	 */
	public function SetMobile()
	{
		$oApiIntegratorManager = \CApi::GetCoreManager('integrator');
		return $oApiIntegratorManager ?
			$oApiIntegratorManager->setMobile('1' === (string) $this->getParamValue('Mobile', '0')) : false;
	}	
	
}

return new CoreModule('1.0');
