<?php

/* -AFTERLOGIC LICENSE HEADER- */

class CProAjaxAction extends ap_CoreModuleHelper
{
	public function UsersNew_Pre()
	{
		/* @var $oAccount CAccount */
		$oAccount =& $this->oAdminPanel->GetMainObject('account_new');
		if (!$oAccount)
		{
			$iDomainId = (int) CPost::get('hiddenDomainId', 0);
			if ($this->oAdminPanel->HasAccessDomain($iDomainId))
			{
				$oDomain = $this->oModule->getDomain($iDomainId);
				if ($oDomain)
				{
					$oAccount = new CAccount($oDomain);
					$this->oAdminPanel->SetMainObject('account_new', $oAccount);
				}
			}
		}
	}

	public function UsersNew()
	{
		/* @var $oAccount CAccount */
		$oAccount =& $this->oAdminPanel->GetMainObject('account_new');
		if ($oAccount)
		{
			$this->initNewAccountByPost($oAccount);
		}
	}
	
	public function UsersNew_Post()
	{
		/* @var $oAccount CAccount */
		$oAccount =& $this->oAdminPanel->GetMainObject('account_new');
		if ($oAccount)
		{
			if ($oAccount->Domain->IsDefaultTenantDomain && strlen(trim($oAccount->Email)) === 0)
			{
				$this->LastError = \CApi::I18N('ADMIN_PANEL/MISSING_DOMAIN_PART');
			}
			else
			{
				$this->oAdminPanel->DeleteMainObject('account_new');
				if ($this->oModule->CreateAccount($oAccount))
				{
					$this->checkBolleanWithMessage(true);
					$this->Ref = '?edit&tab=users&uid='.$oAccount->IdAccount;
				}
				else
				{
					if (0 < $this->oModule->getLastErrorCode())
					{
						$this->LastError = $this->oModule->GetLastErrorMessage();
					}
					else
					{
						$this->checkBolleanWithMessage(false);
					}
				}
			}
		}
	}
	

	public function UsersInvite_Pre()
	{
		$this->UsersNew_Pre();
	}

	public function UsersInvite()
	{
		/* @var $oAccount CAccount */
		$oAccount =& $this->oAdminPanel->GetMainObject('account_new');
		if ($oAccount)
		{
			$this->initInviteAccountByPost($oAccount);
		}
	}

	public function UsersInvite_Post()
	{
		/* @var $oAccount CAccount */
		$oAccount =& $this->oAdminPanel->GetMainObject('account_new');
		if ($oAccount)
		{
			if ($oAccount->Domain->IsDefaultTenantDomain && strlen(trim($oAccount->Email)) === 0)
			{
				$this->LastError = \CApi::I18N('ADMIN_PANEL/MISSING_DOMAIN_PART');
			}
			else
			{
				$oAccount->AllowMail = false;
				$oTenant = $this->oModule->getTenantById($oAccount->Domain->IdTenant);	

				if ($oTenant && trim($oTenant->InviteNotificationEmailAccount) !== '')
				{
					$oNotificationAccount = $this->oModule->GetAccountByEmail($oTenant->InviteNotificationEmailAccount);
					if ($oNotificationAccount)
					{
						$this->oAdminPanel->DeleteMainObject('account_new');
						if ($this->oModule->CreateAccount($oAccount))
						{
							$this->sendInviteToAccount($oAccount);
							$this->checkBolleanWithMessage(true);
							$this->Ref = '?edit&tab=users&uid='.$oAccount->IdAccount;
						}
						else
						{
							if (0 < $this->oModule->getLastErrorCode())
							{
								$this->LastError = $this->oModule->GetLastErrorMessage();
							}
							else
							{
								$this->checkBolleanWithMessage(false);
							}
						}
					}
					else
					{
						$this->LastError = CApi::I18N('The e-mail address specified for invitations does not exist in the system.');
					}
				}
				else
				{
					$this->LastError = CApi::I18N('The account for invitations is not specified.');
				}
			}
		}
	}

	public function UsersEdit_Pre()
	{
		/* @var $oAccount CAccount */
		$oAccount =& $this->oAdminPanel->GetMainObject('account_edit');
		if (!$oAccount)
		{
			$iDomainId = (int) CPost::get('hiddenDomainId', 0);
			if (CPost::Has('hiddenAccountId') && is_numeric(CPost::get('hiddenAccountId', false))
			&& 	$this->oAdminPanel->HasAccessDomain($iDomainId))
			{
				$oAccount = $this->oModule->getAccount((int) CPost::get('hiddenAccountId', 0));
				$this->oAdminPanel->SetMainObject('account_edit', $oAccount);
			}
		}
	}

	public function UsersEdit()
	{
		/* @var $oAccount CAccount */
		$oAccount =& $this->oAdminPanel->GetMainObject('account_edit');
		if ($oAccount)
		{
			$this->initEditAccountByPost($oAccount);
		}
	}

	public function UsersEdit_Post()
	{
		/* @var $oAccount CAccount */
		$oAccount =& $this->oAdminPanel->GetMainObject('account_edit');
		if ($oAccount && $this->oAdminPanel->HasAccessDomain($oAccount->Domain->IdDomain))
		{
			$this->oAdminPanel->DeleteMainObject('account_edit');
			if ($this->oModule->UpdateAccount($oAccount))
			{
				$this->checkBolleanWithMessage(true);
				$this->Ref = '?edit&tab=users&uid='.$oAccount->IdAccount;
			}
			else
			{
				if (0 < $this->oModule->getLastErrorCode())
				{
					$this->LastError = $this->oModule->GetLastErrorMessage();
				}
				else
				{
					$this->checkBolleanWithMessage(false);
				}
			}
		}
	}

	/**
	 * @param CAccount $oAccount
	 */
	protected function initEditAccountByPost(CAccount &$oAccount)
	{
		$oAccount->IsDisabled = !CPost::GetCheckBox('chEnableUser');
		if (CPost::Has('txtFullName'))
		{
			$oAccount->FriendlyName = (string) CPost::get('txtFullName', '');
		}

		$oAccount->HideInGAB = CPost::GetCheckBox('chHideInGAB');
		
		if (CPost::Has('txtEditStorageQuota'))
		{
			$oAccount->StorageQuota = ((int) substr(CPost::get('txtEditStorageQuota'), 0, 9) * 1024);
		}

		if (0 < $oAccount->Domain->IdTenant && CApi::GetConf('capa', false))
		{
			$oAccount->User->IdSubscription = (int) CPost::get('selSubscribtions');

			$oTenantsApi = CApi::GetCoreManager('tenants');
			/* @var $oTenantsApi CApiTenantsManager */

			if ($oTenantsApi)
			{
				$oTenant = $oTenantsApi->getTenantById($oAccount->Domain->IdTenant);
				if ($oTenant)
				{
					$oAccount->User->SetCapa($oTenant, ECapa::GAB, CPost::GetCheckBox('chExtGAB') && $oAccount->Domain->AllowContacts);
					$oAccount->User->SetCapa($oTenant, ECapa::FILES, CPost::GetCheckBox('chExtFiles') && $oAccount->Domain->AllowFiles);
					$oAccount->User->SetCapa($oTenant, ECapa::HELPDESK, CPost::GetCheckBox('chExtHelpdesk') && $oAccount->Domain->AllowHelpdesk);
				}
			}
		}

		$oCapabylity = CApi::GetCoreManager('capability');
		/* @var $oCapabylity CApiCapabilityManager */
		if ($oAccount && $oCapabylity)
		{
			if ($oCapabylity->isSipSupported($oAccount))
			{
				$oAccount->User->SipEnable = CPost::GetCheckBox('chSipEnable', $oAccount->User->SipEnable);
				$oAccount->User->SipImpi = trim(CPost::get('txtSipImpi', $oAccount->User->SipImpi));
				$sSipPassword = trim(CPost::get('txtSipPassword', API_DUMMY));
				if (API_DUMMY !== $sSipPassword && 0 < strlen($sSipPassword))
				{
					$oAccount->User->SipPassword = $sSipPassword;
				}
			}

			if ($oCapabylity->isTwilioSupported($oAccount))
			{
				$oAccount->User->TwilioNumber = trim(CPost::get('txtTwilioNumber', $oAccount->User->TwilioNumber));
				$oAccount->User->TwilioEnable = CPost::GetCheckBox('chTwilioEnable', $oAccount->User->TwilioEnable);
				$oAccount->User->TwilioDefaultNumber = CPost::GetCheckBox('chTwilioDefaultNumber', $oAccount->User->TwilioDefaultNumber);
			}
		}
	}

	/**
	 * @param CAccount $oAccount
	 */
	protected function initNewAccountByPost(CAccount &$oAccount)
	{
		if (CPost::Has('txtNewPassword'))
		{
			$oAccount->IsDefaultAccount = true;
			$oAccount->initLoginAndEmail(CPost::get('txtNewLogin'));
			$oAccount->IncomingMailPassword = CPost::get('txtNewPassword');

			if ($oAccount->Domain)
			{
				if ($oAccount->Domain->IsDefaultDomain && CPost::get('chAllowMail'))
				{
					$oAccount->Email = CPost::get('txtNewEmail');

					$oAccount->IncomingMailProtocol = EnumConvert::FromPost(
					CPost::get('selIncomingMailProtocol'), 'EMailProtocol');

					$oAccount->IncomingMailLogin = CPost::get('txtIncomingMailLogin');
					$oAccount->IncomingMailServer = CPost::get('txtIncomingMailHost');
					$oAccount->IncomingMailPort = (int) CPost::get('txtIncomingMailPort');
					$oAccount->IncomingMailUseSSL = CPost::GetCheckBox('chIncomingUseSSL');

					$oAccount->OutgoingMailLogin = CPost::get('txtOutgoingMailLogin');
					//$oAccount->OutgoingMailPassword = CPost::get('txtOutgoingMailPassword');
					$oAccount->OutgoingMailPassword = '';
					$oAccount->OutgoingMailServer = CPost::get('txtOutgoingMailHost');
					$oAccount->OutgoingMailPort = (int) CPost::get('txtOutgoingMailPort');
					$oAccount->OutgoingMailUseSSL = CPost::GetCheckBox('chOutgoingUseSSL');

					$oAccount->OutgoingMailAuth = CPost::GetCheckBox('chOutgoingAuth')
						? ESMTPAuthType::AuthCurrentUser : ESMTPAuthType::NoAuth;
				}
				if ($oAccount->Domain->IsDefaultTenantDomain)
				{
					$oAccount->AllowMail = false;
				}
				else if (!CPost::get('chAllowMail') && !$this->oAdminPanel->XType)
				{
					$oAccount->AllowMail = false;
					$sDomainPart = api_Utils::GetDomainFromEmail(CPost::get('txtNewEmail'));
					if (empty($sDomainPart))
					{
						$this->LastError = \CApi::I18N('ADMIN_PANEL/MISSING_DOMAIN_PART');
					}
					else
					{
						$oAccount->initLoginAndEmail(CPost::get('txtNewEmail'));
					}
				}
			}
		}

		if (0 < $oAccount->Domain->IdTenant && CApi::GetConf('capa', false))
		{
			$oAccount->User->IdSubscription = (int) CPost::get('selSubscribtions');
		}

		if (CPost::Has('txtEditStorageQuota'))
		{
			$oAccount->StorageQuota = ((int) substr(CPost::get('txtEditStorageQuota'), 0, 9) * 1024);
		}
	}
	
	/**
	 * @param CAccount $oAccount
	 */
	protected function initInviteAccountByPost(CAccount &$oAccount)
	{
		$oAccount->IsDefaultAccount = true;

		if ($oAccount->Domain && ($oAccount->Domain->IsDefaultTenantDomain || $oAccount->Domain->IsDefaultDomain))
		{
			$oAccount->initLoginAndEmail(CPost::get('txtInviteLogin'));
			$oAccount->IncomingMailPassword = md5(CPost::get('txtInviteLogin').time().rand(1000, 9999));
			$oAccount->IsPasswordSpecified = false;
			
		}
	}	
	
	public function sendInviteToAccount(CAccount $oAccount)
	{
		$oTenant = $this->oModule->getTenantById($oAccount->Domain->IdTenant);
		if ($oTenant && trim($oTenant->InviteNotificationEmailAccount) !== '')
		{
			$oNotificationAccount = $this->oModule->GetAccountByEmail($oTenant->InviteNotificationEmailAccount);

			if ($oNotificationAccount)
			{
				$sInvitationUrl = \str_replace('adminpanel', '', rtrim(\api_Utils::GetAppUrl(), '/'));
				
				$aNetworks = array();
				$oPlugin = \CApi::Plugin()->GetPluginByName('external-services');
				if ($oPlugin)
				{
					$aEnableConnectors = $oPlugin->GetEnabledConnectors();
					$aSocials = $oTenant->getSocials();

					foreach($aSocials as $sKey => $oSocial)
					{
						if (in_array($sKey, $aEnableConnectors) && $oSocial->issetScope('auth'))
						{
							$aNetworks[] = $oSocial->SocialName;
						}
					}
				}

				$sSubject = \CApi::I18N('INVITE_EXTERNAL_USER/SUBJECT', array('SITE_NAME' => $oAccount->Domain->SiteName));
				$sBody = \CApi::I18N('INVITE_EXTERNAL_USER/BODY', 
						array(
							'SITE_NAME' => $oAccount->Domain->SiteName,
							'INVITATION_URL' => $sInvitationUrl . '?invite-auth=' . substr(md5($oAccount->IdAccount.CApi::$sSalt), 0, 8),
							'EMAIL' => $oAccount->Email,
							'NETWORKS' => implode(', ', $aNetworks)
						)
				);

				$oMessage = \MailSo\Mime\Message::NewInstance();
				$oMessage->RegenerateMessageId();
				$oMessage->DoesNotCreateEmptyTextPart();

				$sXMailer = \CApi::GetConf('webmail.xmailer-value', '');
				if (0 < strlen($sXMailer))
				{
					$oMessage->SetXMailer($sXMailer);
				}

				$oMessage
					->SetFrom(\MailSo\Mime\Email::NewInstance($oTenant->InviteNotificationEmailAccount))
					->SetSubject($sSubject)
					->AddText($sBody, true)
				;

				$oToEmails = \MailSo\Mime\EmailCollection::NewInstance($oAccount->Email);
				if ($oToEmails && $oToEmails->Count())
				{
					$oMessage->SetTo($oToEmails);
				}

				if ($oMessage)
				{
					try
					{
						$oApiMail = CApi::Manager('mail');
						if ($oApiMail)
						{
							$oApiMail->sendMessage($oNotificationAccount, $oMessage);
						}
					}
					catch (\CApiManagerException $oException)
					{
					}
				}
			}
		}
	}

	public function DomainsEdit()
	{
		/* @var $oDomain CDomain */
		$oDomain =& $this->oAdminPanel->GetMainObject('domain_edit');
		if ($oDomain)
		{
			$this->initUpdateDomainByPost($oDomain);
		}
	}

	protected function initUpdateDomainByPost(CDomain &$oDomain)
	{
		$oDomain->OverrideSettings = 0 < $oDomain->IdTenant ? true : CPost::GetCheckBox('chOverrideSettings');

		if ($oDomain->OverrideSettings)
		{
			$oDomain->AllowCalendar = CPost::GetCheckBox('chEnableCalendar');
			$oDomain->AllowFiles = CPost::GetCheckBox('chEnableFiles');
			$oDomain->AllowHelpdesk = CPost::GetCheckBox('chEnableHelpdesk');

			if (CPost::Has('selWeekStartsOn'))
			{
				$oDomain->CalendarWeekStartsOn =
					EnumConvert::FromPost(CPost::get('selWeekStartsOn'), 'ECalendarWeekStartOn');
			}

			$oDomain->CalendarShowWeekEnds = CPost::GetCheckBox('chShowWeekends');
			$oDomain->CalendarShowWorkDay = CPost::GetCheckBox('chShowWorkday');

			if (CPost::Has('selWorkdayStarts'))
			{
				$oDomain->CalendarWorkdayStarts = (int) CPost::get('selWorkdayStarts');
			}

			if (CPost::Has('selWorkdayEnds'))
			{
				$oDomain->CalendarWorkdayEnds = (int) CPost::get('selWorkdayEnds');
			}

			if (CPost::Has('radioDefaultTab'))
			{
				$oDomain->CalendarDefaultTab =
					EnumConvert::FromPost(CPost::get('radioDefaultTab'), 'ECalendarDefaultTab');
			}

			$oDomain->UseThreads = CPost::GetCheckBox('chUseThreads');
			$oDomain->AllowUsersAddNewAccounts = CPost::GetCheckBox('chAllowUsersAddNewAccounts');
			$oDomain->AllowOpenPGP = CPost::GetCheckBox('chAllowOpenPGP');

			if (CPost::Has('selGlobalAddressBook'))
			{
				$oDomain->GlobalAddressBook =
					EnumConvert::FromPost(CPost::get('selGlobalAddressBook'), 'EContactsGABVisibility');

				if (!$this->oAdminPanel->RType() && EContactsGABVisibility::TenantWide === $oDomain->GlobalAddressBook)
				{
					$oDomain->GlobalAddressBook	= EContactsGABVisibility::SystemWide;
				}
			}
		}
	}

	protected function initTenantByPost(CTenant &$oTenant)
	{
		if (CApi::getCsrfToken('p7admToken') === CPost::get('txtToken'))
		{
			$oTenant->Login = CPost::get('txtLogin', $oTenant->Login);
			$oTenant->Email = CPost::get('txtEmail', $oTenant->Email);

			$sChannel = CPost::get('txtChannel', '');
			if (0 < strlen($sChannel))
			{
				$oChannelsApi = CApi::GetCoreManager('channels');
				if ($oChannelsApi)
				{
					/* @var $oChannel CChannel */
					$iIdChannel = $oChannelsApi->getChannelIdByLogin($sChannel);
					if (0 < $iIdChannel)
					{
						$oTenant->IdChannel = $iIdChannel;
					}
					else
					{
						$this->oAdminPanel->DeleteMainObject('tenant_new');
						$this->oAdminPanel->DeleteMainObject('tenant_edit');
						$this->LastError = CApi::I18N('API/CHANNELSMANAGER_CHANNEL_DOES_NOT_EXIST');
					}
				}
			}

			if (CPost::Has('txtPassword') && (string) AP_DUMMYPASSWORD !== (string) CPost::get('txtPassword'))
			{
				$oTenant->SetPassword(CPost::get('txtPassword'));
			}

			$oTenant->QuotaInMB = (int) CPost::get('txtQuota', 0);
			$oTenant->UserCountLimit = (int) CPost::get('txtUserLimit', 0);
			$oTenant->IsEnableAdminPanelLogin = CPost::GetCheckBox('chEnableAdminLogin');

			$bIsDisabled = !CPost::GetCheckBox('chTenantEnabled');
			if ($bIsDisabled !== $oTenant->IsDisabled)
			{
				$oTenant->IsDisabled = $bIsDisabled;
			}

			$oTenant->SipAllowConfiguration = !!CPost::GetCheckBox('chTenantSipConfiguration');

			$oTenant->TwilioAllowConfiguration = !!CPost::GetCheckBox('chTenantTwilioConfiguration');

			$oTenant->Description = CPost::get('txtDescription', $oTenant->Description);

			if (CApi::GetConf('capa', false))
			{
				$oTenant->Capa = CPost::get('txtCapa', $oTenant->Capa);
			}
		}
		else
		{
			$this->LastError = CApi::I18N('API/INVALID_TOKEN');
		}
	}

	protected function initChannelByPost(CChannel &$oChannel)
	{
		$oChannel->Login = CPost::get('txtLogin', $oChannel->Login);
		$oChannel->Description = CPost::get('txtDescription', $oChannel->Description);
	}

	public function TenantsNew_Pre()
	{
		/* @var $oTenant CChannel */
		$oTenant =& $this->oAdminPanel->GetMainObject('tenant_new');
		if (!$oTenant)
		{
			$oTenant = new CTenant();
			$this->oAdminPanel->SetMainObject('tenant_new', $oTenant);
		}
	}

	public function TenantsNew()
	{
		$oTenant =& $this->oAdminPanel->GetMainObject('tenant_new');
		if ($oTenant)
		{
			$this->initTenantByPost($oTenant);
		}
	}

	public function TenantsNew_Post()
	{
		$oTenant =& $this->oAdminPanel->GetMainObject('tenant_new');
		if ($oTenant)
		{
			if ($this->oModule->createTenant($oTenant))
			{
				$this->checkBolleanWithMessage(true);
				$this->Ref = '?root';
			}
			else
			{
				if (0 < $this->oModule->getLastErrorCode())
				{
					$this->LastError = $this->oModule->GetLastErrorMessage();
				}
				else
				{
					$this->checkBolleanWithMessage(false);
				}
			}
		}
	}

	public function ChannelsNew()
	{
		$oChannel = new CChannel();
		$this->initChannelByPost($oChannel);

		if ($this->oModule->createChannel($oChannel))
		{
			$this->checkBolleanWithMessage(true);
			$this->Ref = '?root';
		}
		else
		{
			if (0 < $this->oModule->getLastErrorCode())
			{
				$this->LastError = $this->oModule->GetLastErrorMessage();
			}
			else
			{
				$this->checkBolleanWithMessage(false);
			}
		}
	}

	public function TenantsEdit()
	{
		$iTenantId = (int) CPost::get('intTenantId', -1);

		$oTenant = $this->oModule->getTenantById($iTenantId);
		if ($oTenant)
		{
			$this->initTenantByPost($oTenant);

			if ($this->oModule->updateTenant($oTenant))
			{
				$this->Ref = '?edit&tab='.AP_TAB_TENANTS.'&uid='.$iTenantId;
				$this->checkBolleanWithMessage(true);
			}
			else
			{
				if (0 < $this->oModule->getLastErrorCode())
				{
					$this->LastError = $this->oModule->GetLastErrorMessage();
				}
				else
				{
					$this->checkBolleanWithMessage(false);
				}
			}
		}
		else
		{
			$this->checkBolleanWithMessage(false);
		}
	}

	public function ChannelsEdit()
	{
		$iChannelId = (int) CPost::get('intChannelId', -1);

		$oChannel = $this->oModule->getChannelById($iChannelId);
		if ($oChannel)
		{
			$this->initChannelByPost($oChannel);

			if ($this->oModule->updateChannel($oChannel))
			{
				$this->Ref = '?edit&tab='.AP_TAB_CHANNELS.'&uid='.$iChannelId;
				$this->checkBolleanWithMessage(true);
			}
			else
			{
				if (0 < $this->oModule->getLastErrorCode())
				{
					$this->LastError = $this->oModule->GetLastErrorMessage();
				}
				else
				{
					$this->checkBolleanWithMessage(false);
				}
			}
		}
		else
		{
			$this->checkBolleanWithMessage(false);
		}
	}

	public function DomainsNew_Pre()
	{
		/* @var $oDomain CDomain */
		$oDomain =& $this->oAdminPanel->GetMainObject('domain_new');
		if (!$oDomain)
		{
			$oDomain = new CDomain();
			$this->oAdminPanel->SetMainObject('domain_new', $oDomain);
		}
	}

	public function DomainsNew()
	{
		/* @var $oDomain CDomain */
		$oDomain =& $this->oAdminPanel->GetMainObject('domain_new');
		if ($oDomain)
		{
			$this->initNewDomainByPost($oDomain);
		}
	}

	public function DomainsNew_Post()
	{
		/* @var $oDomain CDomain */
		$oDomain =& $this->oAdminPanel->GetMainObject('domain_new');
		if ($oDomain)
		{
			$this->oAdminPanel->DeleteMainObject('domain_new');

			if ($this->oModule->CreateDomain($oDomain))
			{
				$this->checkBolleanWithMessage(true);
				$this->Ref = ($oDomain->OverrideSettings)
					? '?edit&tab=domains&uid='.$oDomain->IdDomain : '?root';
			}
			else
			{
				if (0 < $this->oModule->getLastErrorCode())
				{
					$this->LastError = $this->oModule->GetLastErrorMessage();
				}
				else
				{
					$this->checkBolleanWithMessage(false);
				}
			}
		}
	}

	protected function initNewDomainByPost(CDomain &$oDomain)
	{
		$sDomainName = CPost::get('txtDomainName', '');

		$oDomain->IsDefaultDomain = false;
		$oDomain->OverrideSettings = CPost::GetCheckBox('chOverrideSettings');

		$oDomain->Name = $sDomainName;
		$oDomain->Url = '';

		if (0 < $this->oAdminPanel->TenantId())
		{
			$oDomain->IdTenant = $this->oAdminPanel->TenantId();
		}
		else
		{
			$sTenant = CPost::get('txtTenantName', '');
			if (0 < strlen($sTenant))
			{
				$iIdTenant = $this->oModule->GetTenantIdByName($sTenant);
				if (0 === $iIdTenant)
				{
					$this->oAdminPanel->DeleteMainObject('domain_new');
					$this->LastError = CApi::I18N('API/TENANTSMANAGER_TENANT_DOES_NOT_EXIST');
				}
				else
				{
					$oDomain->IdTenant = $iIdTenant;
				}
			}
		}
		
		if ($sDomainName === '*' && $oDomain->IdTenant !== 0)
		{
			/* @var \CApiDomainsManager $oApiDomainsManager */
			$oApiDomainsManager = \CApi::GetCoreManager('domains');
			$oDefaultDomainForTenant = $oApiDomainsManager->GetDefaultDomainByTenantId($oDomain->IdTenant);
			
			if ($oDefaultDomainForTenant !== null)
			{
				$this->oAdminPanel->DeleteMainObject('domain_new');
				$this->LastError = 'DEFAULT_DOMAIN_ALREADY_EXIST_IN_THIS_TENANT';
			}
			else 
			{
				$oTenant = $this->oModule->getTenantById($oDomain->IdTenant);
				$oDomain->Name = '*' .$oTenant->Login . '*';
				$oDomain->IsDefaultTenantDomain = true;
			}
		}
	}
}
