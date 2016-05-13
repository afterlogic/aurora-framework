<?php

/* -AFTERLOGIC LICENSE HEADER- */

class CWebMailAjaxAction extends ap_CoreModuleHelper
{

	public function DomainsNew()
	{
		/* @var $oDomain CDomain */
		$oDomain =& $this->oAdminPanel->GetMainObject('domain_new');
		if ($oDomain)
		{
			$this->initNewDomainByPost($oDomain);
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
		$oDomain->OverrideSettings = CPost::GetCheckBox('chOverrideSettings');

		if (CPost::Has('txtIncomingMailHost') && CPost::Has('txtOutgoingMailHost')
			&& CPost::Has('txtIncomingMailPort') && CPost::Has('txtOutgoingMailPort'))
		{
			$oDomain->IncomingMailServer = CPost::get('txtIncomingMailHost');
			$oDomain->IncomingMailPort = CPost::get('txtIncomingMailPort');
			$oDomain->IncomingMailUseSSL = CPost::GetCheckBox('chIncomingUseSSL');

			$oDomain->OutgoingMailServer = CPost::get('txtOutgoingMailHost');
			$oDomain->OutgoingMailPort = CPost::get('txtOutgoingMailPort');
			$oDomain->OutgoingMailUseSSL = CPost::GetCheckBox('chOutgoingUseSSL');
		}

		if (CPost::Has('radioAuthType'))
		{
			$oDomain->OutgoingMailAuth =
				EnumConvert::FromPost(CPost::get('radioAuthType'), 'ESMTPAuthType');
		}

		if (CPost::Has('txtOutgoingMailLogin') && CPost::Has('txtOutgoingMailPassword'))
		{
			$oDomain->OutgoingMailLogin = CPost::get('txtOutgoingMailLogin');
			if ((string) AP_DUMMYPASSWORD !== (string) CPost::get('txtOutgoingMailPassword'))
			{
				$oDomain->OutgoingMailPassword = CPost::get('txtOutgoingMailPassword', '');
			}
		}

		if (CPost::Has('selIncomingMailProtocol'))
		{
			$oDomain->IncomingMailProtocol = EnumConvert::FromPost(
				CPost::get('selIncomingMailProtocol'), 'EMailProtocol');
		}

//		if ($oDomain->OverrideSettings || $oDomain->IsDefault)
//		{
//			$oDomain->ExternalHostNameOfDAVServer = CPost::Get('txtExternalHostNameOfDAVServer', $oDomain->ExternalHostNameOfDAVServer);
//			$oDomain->ExternalHostNameOfLocalImap = CPost::Get('txtExternalHostNameOfLocalImap', $oDomain->ExternalHostNameOfLocalImap);
//			$oDomain->ExternalHostNameOfLocalSmtp = CPost::Get('txtExternalHostNameOfLocalSmtp', $oDomain->ExternalHostNameOfLocalSmtp);
//		}

		if ($oDomain->OverrideSettings)
		{
			// General
			$oDomain->Url = (string) CPost::get('txtWebDomain', $oDomain->Url);
			$oDomain->AllowUsersChangeEmailSettings = CPost::GetCheckBox('chAllowUsersAccessAccountsSettings');
			$oDomain->AllowNewUsersRegister = !CPost::GetCheckBox('chAllowNewUsersRegister');

			// Webmail
			$oDomain->AllowWebMail = CPost::GetCheckBox('chEnableWebmail');

			$oDomain->MailsPerPage = CPost::get('selMessagesPerPage', $oDomain->MailsPerPage);
			$oDomain->AutoRefreshInterval = CPost::get('selAutocheckMail', $oDomain->AutoRefreshInterval);

			// Address Book
			$oDomain->AllowContacts = CPost::GetCheckBox('chEnableAddressBook');

			$oDomain->ContactsPerPage = CPost::get('selContactsPerPage', $oDomain->ContactsPerPage);

		}
	}

	protected function initNewDomainByPost(CDomain &$oDomain)
	{
		/* @var $oApiDomainsManager CApiDomainsManager */
		$oApiDomainsManager = CApi::GetCoreManager('domains');

		/* @var $oDefaultDomain CDomain */
		$oDefaultDomain = $oApiDomainsManager->getDefaultDomain();

		$oDomain->IncomingMailProtocol = $oDefaultDomain->IncomingMailProtocol;
		$oDomain->IncomingMailServer = $oDefaultDomain->IncomingMailServer;
		$oDomain->IncomingMailPort = $oDefaultDomain->IncomingMailPort;
		$oDomain->OutgoingMailServer = $oDefaultDomain->OutgoingMailServer;
		$oDomain->OutgoingMailPort = $oDefaultDomain->OutgoingMailPort;

		$oDomain->ExternalHostNameOfDAVServer = $oDefaultDomain->ExternalHostNameOfDAVServer;
		$oDomain->ExternalHostNameOfLocalImap = $oDefaultDomain->ExternalHostNameOfLocalImap;
		$oDomain->ExternalHostNameOfLocalSmtp = $oDefaultDomain->ExternalHostNameOfLocalSmtp;
	}
}
