<?php

/* -AFTERLOGIC LICENSE HEADER- */

class CBundleAjaxAction extends ap_CoreModuleHelper
{
	public function UsersList_Pre()
	{
		if (CPost::Has('hiddenDomainId') && is_numeric(CPost::get('hiddenDomainId')))
		{
			$oDomain = $this->oModule->getDomain((int) CPost::get('hiddenDomainId', 0));
			if ($oDomain)
			{
				
				$oMailingList = new CMailingList($oDomain);
				$this->oAdminPanel->SetMainObject('mailing_list_new', $oMailingList);
			}
		}
	}
	
	public function UsersList()
	{
		/* @var $oMailingList CMailingList */
		$oMailingList =& $this->oAdminPanel->GetMainObject('mailing_list_new');
		if ($oMailingList)
		{
			$this->initNewMailingListByPost($oMailingList);
		}
	}
	
	public function UsersList_Post()
	{
		/* @var $oMailingList CMailingList */
		$oMailingList =& $this->oAdminPanel->GetMainObject('mailing_list_new');
		
		if ($oMailingList)
		{
			$this->oAdminPanel->DeleteMainObject('mailing_list_new');
			if ($this->oModule->createMailingList($oMailingList))
			{
				$this->checkBolleanWithMessage(true);
				$this->Ref = '?edit&tab=users&uid='.$oMailingList->IdMailingList;
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
	
	public function UsersEdit_Pre()
	{
		$iMailingListId = CPost::get('hiddenMailingListId');
		$oMailingList =& $this->oAdminPanel->GetMainObject('mailinglist_edit');
		if (!$oMailingList && null !== $iMailingListId && 0 < $iMailingListId)
		{
			$oMailingList = $this->oModule->GetMailingList($iMailingListId);
			if ($oMailingList)
			{
				$this->oAdminPanel->SetMainObject('mailinglist_edit', $oMailingList);
			}			
		}
		
		/* @var $oAccount CAccount */
		$oAccount =& $this->oAdminPanel->GetMainObject('account_edit');
		if ($oAccount)
		{
			$oMailAliases =& $this->oAdminPanel->GetMainObject('aliases_edit');
			if (!$oMailAliases)
			{
				$oMailAliases = $this->oModule->GetMailAliases($oAccount);
				if($oMailAliases)
				{
					$this->oAdminPanel->SetMainObject('aliases_edit', $oMailAliases);
				}
			}			

			$oMailForwards =& $this->oAdminPanel->GetMainObject('forwards_edit');
			if (!$oMailForwards)
			{
				$oMailForwards = $this->oModule->GetMailForwards($oAccount);
				if($oMailForwards)
				{
					$this->oAdminPanel->SetMainObject('forwards_edit', $oMailForwards);
				}
			}		
		}
 	}
	
	public function UsersEdit_Post()
	{
		/* @var $oMailingList CMailingList */
		$oMailingList =& $this->oAdminPanel->GetMainObject('mailinglist_edit');
		
		if ($oMailingList)
		{
			$this->oAdminPanel->DeleteMainObject('mailinglist_edit');
			if ($this->oModule->updateMailingList($oMailingList))
			{
				$this->checkBolleanWithMessage(true);
				$this->Ref = '?edit&tab=users&uid='.$oMailingList->IdMailingList;
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
		
		/* @var $oMailAliases CMailAliases */
		$oMailAliases =& $this->oAdminPanel->GetMainObject('aliases_edit');
		
		if ($oMailAliases)
		{
			$this->oAdminPanel->DeleteMainObject('aliases_edit');
			if ($this->oModule->updateMailAliases($oMailAliases))
			{
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
		
		/* @var $oMailForwards CMailForwards */
		$oMailForwards =& $this->oAdminPanel->GetMainObject('forwards_edit');
		
		if ($oMailForwards)
		{
			$this->oAdminPanel->DeleteMainObject('forwards_edit');
			if ($this->oModule->updateMailForwards($oMailForwards))
			{
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

	public function UsersEdit()
	{
		/* @var $oMailingList CMailingList */
		$oMailingList =& $this->oAdminPanel->GetMainObject('mailinglist_edit');
		if ($oMailingList)
		{
			$this->initUpdateMailingListByPost($oMailingList);
		}

		/* @var $oAccount CAccount */
		$oAccount =& $this->oAdminPanel->GetMainObject('account_edit');
		if ($oAccount)
		{
			$this->initEditAccountByPost($oAccount);
		}
		
		/* @var $oMailAliases CMailAliases */
		$oMailAliases =& $this->oAdminPanel->GetMainObject('aliases_edit');
		if ($oMailAliases)
		{
			$this->initUpdateMailAliasesByPost($oMailAliases);
		}
		
		/* @var $oMailForwards CMailForwards */
//		$oMailForwards =& $this->oAdminPanel->GetMainObject('forwards_edit');
//		if ($oMailForwards)
//		{
//			$this->initUpdateMailForwardsByPost($oMailForwards);
//		}
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
	
	public function DomainsEdit()
	{
		/* @var $oDomain CDomain */
		$oDomain =& $this->oAdminPanel->GetMainObject('domain_edit');
		if ($oDomain)
		{
			$this->initUpdateDomainByPost($oDomain);
		}
	}
	
	/**
	 * @param CMailingList &$oMailingList
	 */
	protected function initUpdateMailingListByPost(CMailingList &$oMailingList)
	{
		$oMailingList->Members = array();
		if (CPost::Has('selListMembersDDL'))
		{
			$oMailingList->Members = CPost::get('selListMembersDDL');
		}

		if (CPost::Has('txtMailingListFriendlyName'))
		{
			$oMailingList->Name = CPost::get('txtMailingListFriendlyName');
		}
	}
	
	/**
	 * @param CMailAliases &$oMailAliases
	 */
	protected function initUpdateMailAliasesByPost(CMailAliases &$oMailAliases)
	{
		$oMailAliases->Aliases = array();
		if (CPost::Has('selAliasesDDL'))
		{
			$oMailAliases->Aliases = CPost::get('selAliasesDDL');
		}
	}

	/**
	 * @param CMailForwards &$oMailForwards
	 */
	protected function initUpdateMailForwardsByPost(CMailForwards &$oMailForwards)
	{
		$oMailForwards->Forwards = array();
		if (CPost::Has('selForwardsDDL'))
		{
			$oMailForwards->Forwards = CPost::get('selForwardsDDL');
		}
	}

	/**
	 * @param CAccount $oAccount
	 */
	protected function initEditAccountByPost(CAccount &$oAccount)
	{
		if (CPost::Has('txtEditPassword'))
		{
			if ((string) AP_DUMMYPASSWORD !== (string) CPost::get('txtEditPassword'))
			{
				$oAccount->IncomingMailPassword = CPost::get('txtEditPassword');
			}
		}
		
		if (CPost::Has('txtEditStorageQuota'))
		{
			$oAccount->StorageQuota = ((int) substr(CPost::get('txtEditStorageQuota'), 0, 9) * 1024);
		}
	}
	
	/**
	 * @param CAccount $oAccount
	 */
	protected function initNewAccountByPost(CAccount &$oAccount)
	{
		if (CPost::Has('txtEditStorageQuota'))
		{
			$oAccount->StorageQuota = ((int) substr(CPost::get('txtEditStorageQuota'), 0, 9) * 1024);
		}
	}
	
	/**
	 * @param CAccount $oAccount 
	 */
	protected function initNewMailingListByPost(CMailingList &$oMailingList)
	{
		if (CPost::Has('txtMailingListUserName'))
		{
			$sMailingListUserName = trim(CPost::get('txtMailingListUserName'));
			if (!empty($sMailingListUserName))
			{
				$oMailingList->initLoginAndEmail(CPost::get('txtMailingListUserName'));

				if (CPost::Has('txtMailingListFriendlyName'))
				{
					$oMailingList->Name = CPost::get('txtMailingListFriendlyName');
				}
			}
		}
	}
	
	/**
	 * @param CDomain &$oDomain
	 */
	protected function initNewDomainByPost(CDomain &$oDomain)
	{
		if (strpos($oDomain->Name, '*') !== false)
		{
			$oDomain->IsInternal = false;
		}
		else
		{
			$oDomain->IsInternal = true;
		}
		$oDomain->IncomingMailProtocol = EMailProtocol::IMAP4;
		$oDomain->IncomingMailServer = '127.0.0.1';
		$oDomain->IncomingMailPort = 143;
	}
	
	/**
	 * @param CDomain &$oDomain
	 */
	protected function initUpdateDomainByPost(CDomain &$oDomain)
	{
		if ($oDomain->IsInternal && $oDomain->OverrideSettings && !$oDomain->IsDefault)
		{
			$oDomain->AllowRegistration = CPost::GetCheckBox('chEnableSignUp');
			$oDomain->AllowPasswordReset = CPost::GetCheckBox('chAllowUsersResetPassword');
		}
	}
}