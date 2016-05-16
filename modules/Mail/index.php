<?php

class MailModule extends AApiModule
{
	public $oApiMainManager = null;
	public $oApiAccountsManager = null;
	
	public function init() 
	{
		$this->oApiAccountsManager = $this->GetManager('accounts', 'db');
		$this->oApiMainManager = $this->GetManager('main', 'db');
		
		$this->setObjectMap('CUser', array(
				'AllowAutosaveInDrafts'		=> array('bool', true), //'allow_autosave_in_drafts'),
				'AllowChangeInputDirection'	=> array('bool', false), //'allow_change_input_direction'),
				'MailsPerPage'				=> array('int', 20), //'msgs_per_page'),
				'SaveRepliesToCurrFolder'	=> array('bool', false), //'save_replied_messages_to_current_folder'),
				'UseThreads'				=> array('bool', true), //'use_threads'),
			)
		);
		
		$this->subscribeEvent('Login', array($this, 'checkAuth'));
	}
	
	/**
	 * 
	 * @return boolean
	 */
	public function SetInheritedUserSettings($oUser, $oDomain)
	{
		$oUser->{'Mail::AllowAutosaveInDrafts'}		= $oDomain->AllowAutosaveInDrafts;
		$oUser->{'Mail::AllowChangeInputDirection'}	= $oDomain->AllowChangeInputDirection;
		$oUser->{'Mail::MailsPerPage'}				= $oDomain->MailsPerPage;
		$oUser->{'Mail::SaveRepliesToCurrFolder'}	= $oDomain->SaveRepliesToCurrFolder;
		$oUser->{'Mail::UseThreads'}				= $oDomain->UseThreads;
	}
	
	public function GetAppData($oUser = null)
	{
		$aAcc = $this->oApiAccountsManager->getUserAccounts($oUser->iObjectId);
		return array(
			'Accounts' => array_values($aAcc),
			'AllowAutosaveInDrafts' => $oUser->{'Mail::AllowAutosaveInDrafts'},
			'AllowChangeInputDirection' => $oUser->{'Mail::AllowChangeInputDirection'},
			'MailsPerPage' => $oUser->{'Mail::MailsPerPage'},
			'SaveRepliesToCurrFolder' => $oUser->{'Mail::SaveRepliesToCurrFolder'},
			'UseThreads' => $oUser->{'Mail::UseThreads'}
		);
	}
	/**
	 * 
	 * @return boolean
	 */
	public function CreateAccount($iUserId = 0, $sEmail = '', $sPassword = '', $sServer = '')
	{
		$oEventResult = null;
		$this->broadcastEvent('CreateAccount', array(
			'IdTenant' => null,
			'IdUser' => $iUserId,
			'email' => $sEmail,
			'password' => $sPassword,
			'result' => &$oEventResult
		));
		
		if ($oEventResult instanceOf \CUser)
		{
			$oAccount = \CMailAccount::createInstance();
			
			$oAccount->IdUser = $oEventResult->iObjectId;
			$oAccount->Email = $sEmail;
			$oAccount->IncomingMailLogin = $sEmail;
			$oAccount->IncomingMailPassword = $sPassword;
			$oAccount->IncomingMailServer = $sServer;
			if (!$this->oApiAccountsManager->isDefaultUserAccountExists($iUserId))
			{
				$oAccount->IsDefaultAccount = true;
			}

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
	public function UpdateAccount($iAccountId = 0, $sEmail = '', $sPassword = '', $sServer = '')
	{
		if ($iAccountId > 0)
		{
			$oAccount = $this->oApiAccountsManager->getAccountById($iAccountId);
			
			if ($oAccount)
			{
				if ($sEmail)
				{
					$oAccount->Email = $sEmail;
				}
				if ($sPassword)
				{
					$oAccount->IncomingMailPassword = $sPassword;
				}
				if ($sServer)
				{
					$oAccount->IncomingMailServer = $sServer;
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
	
	public function checkAuth($sEmail, $sPassword, &$mResult)
	{
		$oAccount = $this->oApiAccountsManager->getAccountByCredentials($sEmail, $sPassword);

		if ($oAccount)
		{
			$this->oApiMainManager->validateAccountConnection($oAccount);
			$mResult = array(
				'token' => 'auth',
				'sign-me' => true,
				'id' => $oAccount->IdUser,
				'email' => $oAccount->Email
			);
		}
	}
	
	public function GetExtensions($iAccountID, $sClientTimeZone)
	{
	
		$mResult = false;
		$oAccount = $this->oApiAccountsManager->getAccountById($iAccountID);
//		$oAccount = $this->getAccountFromParam(false);
		if ($oAccount)
		{
//			$sClientTimeZone = trim($this->getParamValue('ClientTimeZone', ''));
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
//			if ($oAccount->isExtensionEnabled(\CAccount::IgnoreSubscribeStatus) &&
//				!$oAccount->isExtensionEnabled(\CAccount::DisableManageSubscribe))
//			{
//				$oAccount->enableExtension(\CAccount::DisableManageSubscribe);
//			}
//
//			$aExtensions = $oAccount->getExtensionList();
//			foreach ($aExtensions as $sExtensionName)
//			{
//				if ($oAccount->isExtensionEnabled($sExtensionName))
//				{
//					$mResult['Extensions'][] = $sExtensionName;
//				}
//			}
		}

		return $mResult;
	}
	
	/**
	 * @return array
	 */
	public function GetFolders($iAccountID)
	{
		$oAccount = $this->oApiAccountsManager->getAccountById($iAccountID);
//		$oAccount = $this->getAccountFromParam();
		$oFolderCollection = $this->oApiMainManager->getFolders($oAccount);
		return array(
			'Folders' => $oFolderCollection, 
			'Namespace' => $oFolderCollection->GetNamespace()
		);
	}
	
	/**
	 * @return array
	 */
	public function GetMessages($sFolderFullNameRaw, $iOffset, $iLimit, $sSearch, $sFilters, $sUseThreads, $iAccountID)
	{
		$sOffset = trim((string) $iOffset);
		$sLimit = trim((string) $iLimit);
		$sSearch = trim((string) $sSearch);
		$bUseThreads = '1' === trim((string) $sUseThreads);
		$sInboxUidnext = '';//$this->getParamValue('InboxUidnext', '');
		
		$aFilters = array();
		$sFilters = strtolower(trim((string) $sFilters));
		if (0 < strlen($sFilters))
		{
			$aFilters = array_filter(explode(',', $sFilters), function ($sValue) {
				return '' !== trim($sValue);
			});
		}

		$iOffset = 0 < strlen($sOffset) && is_numeric($sOffset) ? (int) $sOffset : 0;
		$iLimit = 0 < strlen($sLimit) && is_numeric($sLimit) ? (int) $sLimit : 0;

		if (0 === strlen(trim($sFolderFullNameRaw)) || 0 > $iOffset || 0 >= $iLimit || 200 < $sLimit)
		{
			throw new \Core\Exceptions\ClientException(\Core\Notifications::InvalidInputParameter);
		}

		$oAccount = $this->oApiAccountsManager->getAccountById($iAccountID);
//		$oAccount = $this->getAccountFromParam();

		return $this->oApiMainManager->getMessageList(
			$oAccount, $sFolderFullNameRaw, $iOffset, $iLimit, $sSearch, $bUseThreads, $aFilters, $sInboxUidnext);
	}	
}

return new MailModule('1.0');
