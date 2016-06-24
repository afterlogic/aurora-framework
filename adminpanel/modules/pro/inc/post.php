<?php

/* -AFTERLOGIC LICENSE HEADER- */

class CProPostAction extends ap_CoreModuleHelper
{
	public function CommonTenant()
	{
		if ($this->oAdminPanel->IsTenantAuthType())
		{
			$oTenant = $this->oModule->GetTenantAdminObject();
			/* @var $oTenant CTenant */
			if (CApi::getCsrfToken('p7admToken') === CPost::get('txtToken') && $oTenant)
			{
				if ($oTenant->AllowChangeAdminEmail)
				{
					$oTenant->Email = CPost::get('txtTenantAdminEmail');
				}
				$oTenant->InviteNotificationEmailAccount = CPost::get('txtTenantInviteEmail');

				if ($oTenant->AllowChangeAdminPassword && API_DUMMY !== CPost::get('txtTenantPassword') && 0 < strlen(trim(CPost::get('txtTenantPassword'))))
				{
					$oTenant->setPassword(CPost::get('txtTenantPassword'));
				}

				if ($oTenant && $this->oModule->UpdateTenantAdminObject($oTenant))
				{
					$this->LastMessage = AP_LANG_SAVESUCCESSFUL;
					$this->LastError = '';
				}
				else
				{
					$this->LastMessage = '';
					$this->LastError = AP_LANG_SAVEUNSUCCESSFUL;
				}
			}
			else
			{
				$this->LastError = CApi::I18N('API/INVALID_TOKEN');
			}
		}
	}

	public function CommonBranding()
	{
		$oApiCapa = CApi::GetSystemManager('capability');
		/* @var $oApiCapa CApiCapabilityManager */

		$oTenant = /* @var $oTenant CTenant */  $this->oModule->GetTenantAdminObject();
		if ($oTenant)
		{
			if (!$oApiCapa->isTenantsSupported())
			{
				$oTenant->LoginStyleImage = CPost::get('txtLoginStyleImage');
				$oTenant->AppStyleImage = CPost::get('txtAppStyleImage');
			}
			else if (!$this->oAdminPanel->IsTenantAuthType() &&
				($this->oAdminPanel->IsSuperAdminAuthType() || $this->oAdminPanel->IsOnlyReadAuthType()))
			{
				$oTenant->LoginStyleImage = CPost::get('txtLoginStyleImage');
			}
			else if ($this->oAdminPanel->IsTenantAuthType() && $oTenant)
			{
				$oTenant->AppStyleImage = CPost::get('txtAppStyleImage');
			}

			if ($this->oModule->UpdateTenantAdminObject($oTenant))
			{
				$this->LastMessage = AP_LANG_SAVESUCCESSFUL;
				$this->LastError = '';
			}
			else
			{
				$this->LastMessage = '';
				$this->LastError = AP_LANG_SAVEUNSUCCESSFUL;
			}
		}
	}

	public function CommonInvitations()
	{
		$oTenant = /* @var $oTenant CTenant */  $this->oModule->GetTenantAdminObject();
		if ($oTenant)
		{
			$oTenant->InviteNotificationEmailAccount = CPost::get('txtTenantInviteEmail');
			
			if ($this->oModule->UpdateTenantAdminObject($oTenant))
			{
				$this->LastMessage = AP_LANG_SAVESUCCESSFUL;
				$this->LastError = '';
			}
			else
			{
				$this->LastMessage = '';
				$this->LastError = AP_LANG_SAVEUNSUCCESSFUL;
			}
		}
	}

	public function CommonHelpdesk()
	{
		$oApiCapa = CApi::GetSystemManager('capability');
		if ($oApiCapa && $oApiCapa->isHelpdeskSupported() && (
			$this->oAdminPanel->IsTenantAuthType() ||
			($this->oAdminPanel->IsSuperAdminAuthType() && !$oApiCapa->isTenantsSupported())
		))
		{
			$oTenant = /* @var $oTenant CTenant */  $this->oModule->GetTenantAdminObject();
			if ($oTenant && $oTenant->isHelpdeskSupported())
			{
				$oTenant->HelpdeskAdminEmailAccount = CPost::get('txtAdminEmailAccount');
				$oTenant->HelpdeskAllowFetcher = CPost::GetCheckBox('chHelpdeskAllowFetcher');
				$oTenant->HelpdeskClientIframeUrl = CPost::get('txtClientIframeUrl');
				$oTenant->HelpdeskAgentIframeUrl = CPost::get('txtAgentIframeUrl');
				$oTenant->HelpdeskSiteName = CPost::get('txtHelpdeskSiteName');
				$oTenant->HelpdeskStyleAllow = CPost::get('chHelpdeskStyleAllow');
				$oTenant->HelpdeskStyleImage = CPost::get('txtHelpdeskStyleImage');
				$oTenant->setHelpdeskStyleText(CPost::get('txtHelpdeskStyleText'));

				$oTenant->HelpdeskFacebookAllow = CPost::get('chHelpdeskFacebookAllow');
				$oTenant->HelpdeskFacebookId = CPost::get('txtHelpdeskFacebookId');
				$oTenant->HelpdeskFacebookSecret = CPost::get('txtHelpdeskFacebookSecret');
				$oTenant->HelpdeskGoogleAllow = CPost::get('chHelpdeskGoogleAllow');
				$oTenant->HelpdeskGoogleId = CPost::get('txtHelpdeskGoogleId');
				$oTenant->HelpdeskGoogleSecret = CPost::get('txtHelpdeskGoogleSecret');
				$oTenant->HelpdeskTwitterAllow = CPost::get('chHelpdeskTwitterAllow');
				$oTenant->HelpdeskTwitterId = CPost::get('txtHelpdeskTwitterId');
				$oTenant->HelpdeskTwitterSecret = CPost::get('txtHelpdeskTwitterSecret');
				
				if (CPost::Has('radioHelpdeskFetcherType'))
				{
					$oTenant->HelpdeskFetcherType =
						EnumConvert::FromPost(CPost::get('radioHelpdeskFetcherType'), 'EHelpdeskFetcherType');
				}
			}

			if ($oTenant && $oTenant->isHelpdeskSupported() && $this->oModule->UpdateTenantAdminObject($oTenant))
			{
				$this->LastMessage = AP_LANG_SAVESUCCESSFUL;
				$this->LastError = '';
			}
			else
			{
				$this->LastMessage = '';
				$this->LastError = AP_LANG_SAVEUNSUCCESSFUL;
			}
		}
	}

	public function CommonTwilio()
	{
		$oApiCapa = CApi::GetSystemManager('capability');
		/* @var $oApiCapa CApiCapabilityManager */

		if ($oApiCapa && $oApiCapa->isTwilioSupported())
		{
			$oTenant = /* @var $oTenant CTenant */  $this->oModule->GetTenantAdminObject();

			if ($oTenant && $oTenant->isTwilioSupported())
			{
				$oTenant->TwilioAllow = !$oTenant->SipAllow ? CPost::GetCheckBox('chAllowTwilio') : 0;
				$oTenant->TwilioPhoneNumber = CPost::get('txtTwilioPhoneNumber');
				$oTenant->TwilioAccountSID = CPost::get('txtTwilioAccountSID');
				$oTenant->TwilioAuthToken = CPost::get('txtTwilioAuthToken');
				$oTenant->TwilioAppSID = CPost::get('txtTwilioAppSID');

				if ($this->oModule->UpdateTenantAdminObject($oTenant))
				{
					$this->LastMessage = !$oTenant->SipAllow ? AP_LANG_SAVESUCCESSFUL : CApi::I18N('ADMIN_PANEL/MSG_SAVESUCCESSFUL_TWILIO');
					$this->LastError = '';
				}
				else
				{
					$this->LastMessage = '';
					$this->LastError = AP_LANG_SAVEUNSUCCESSFUL;
				}
			} else
			{
				$this->LastMessage = '';
				$this->LastError = AP_LANG_SAVEUNSUCCESSFUL;
			}
		}
	}

	public function CommonSip()
	{
		$oApiCapa = CApi::GetSystemManager('capability');
		/* @var $oApiCapa CApiCapabilityManager */

		if ($oApiCapa && $oApiCapa->isSipSupported())
		{
			$oTenant = /* @var $oTenant CTenant */  $this->oModule->GetTenantAdminObject();

			if ($oTenant && $oTenant->isSipSupported())
			{
				$oTenant->SipAllow = !$oTenant->TwilioAllow ? CPost::GetCheckBox('chAllowSip') : 0;
				$oTenant->SipRealm = CPost::get('txtSipRealm');
				$oTenant->SipWebsocketProxyUrl = CPost::get('txtSipWebsocketProxyUrl');
				$oTenant->SipOutboundProxyUrl = CPost::get('txtSipOutboundProxyUrl');
				$oTenant->SipCallerID = CPost::get('txtSipCallerID');

				if ($this->oModule->UpdateTenantAdminObject($oTenant))
				{
					$this->LastMessage = !$oTenant->TwilioAllow ? AP_LANG_SAVESUCCESSFUL : CApi::I18N('ADMIN_PANEL/MSG_SAVESUCCESSFUL_SIP');
					$this->LastError = '';
				}
				else
				{
					$this->LastMessage = '';
					$this->LastError = AP_LANG_SAVEUNSUCCESSFUL;
				}
			} else
			{
				$this->LastMessage = '';
				$this->LastError = AP_LANG_SAVEUNSUCCESSFUL;
			}
		}
	}
	


	public function TenantsCollectionDelete()
	{
		$aCollection = CPost::get('chCollection', array());
		$aAccountsIds = is_array($aCollection) ? $aCollection : array();

		$this->checkBolleanDeleteWithMessage(
			(0 < count($aAccountsIds) && $this->oModule->deleteTenants($aAccountsIds))
		);
	}

	public function ChannelsCollectionDelete()
	{
		$aCollection = CPost::get('chCollection', array());
		$aAccountsIds = is_array($aCollection) ? $aCollection : array();

		$this->checkBolleanDeleteWithMessage(
			(0 < count($aAccountsIds) && $this->oModule->deleteChannels($aAccountsIds))
		);
	}

	public function UsersCollectionDelete()
	{
		$aCollection = CPost::get('chCollection', array());
		$aAccountsIds = is_array($aCollection) ? $aCollection : array();

		$this->checkBolleanDeleteWithMessage(
			(0 < count($aAccountsIds) && $this->oModule->DeleteAccounts($aAccountsIds))
		);
	}

	public function UsersCollectionEnable()
	{
		$aCollection = CPost::get('chCollection', array());
		$aAccountsIds = is_array($aCollection) ? $aCollection : array();
		if (0 < count($aAccountsIds))
		{
			$this->oModule->EnableAccounts($aAccountsIds, true);
		}
	}

	public function UsersCollectionDisable()
	{
		$aCollection = CPost::get('chCollection', array());
		$aAccountsIds = is_array($aCollection) ? $aCollection : array();
		if (0 < count($aAccountsIds))
		{
			$this->oModule->EnableAccounts($aAccountsIds, false);
		}
	}

	public function DomainsCollectionDelete()
	{
		$aCollection = CPost::get('chCollection', array());
		$aDomainsIds = is_array($aCollection) ? $aCollection : array();

		if (0 < count($aDomainsIds))
		{
			if (!$this->oModule->deleteDomains($aDomainsIds))
			{
				$this->LastError = $this->oModule->GetLastErrorMessage();
			}
			else
			{
				$this->checkBolleanDeleteWithMessage(true);
			}
		}
		else
		{
			$this->checkBolleanDeleteWithMessage(false);
		}
	}

	public function SystemLicensing()
	{
		$sKey = CPost::get('txtLicenseKey', null);
		if (null !== $sKey)
		{
			$this->checkBolleanWithMessage($this->oModule->UpdateLicenseKey(CPost::get('txtLicenseKey')));
		}
	}

	public function CommonDav()
	{
		$bResult = false;

		$this->oSettings->SetConf('WebMail/ExternalHostNameOfDAVServer', CPost::get('text_DAVUrl'));
		$this->oSettings->SetConf('WebMail/ExternalHostNameOfLocalImap', CPost::get('text_IMAPHostName'));
		$this->oSettings->SetConf('WebMail/ExternalHostNameOfLocalSmtp', CPost::get('text_SMTPHostName'));

		/* @var $oApiDavManager CApiDavManager */
		$oApiDavManager = CApi::Manager('dav');
		if ($oApiDavManager)
		{
			$bResult = $oApiDavManager->setMobileSyncEnable(CPost::GetCheckBox('ch_EnableMobileSync'));
			$bResult &= $this->oSettings->SaveToXml();
		}

		$this->checkBolleanWithMessage((bool) $bResult);
	}
}
