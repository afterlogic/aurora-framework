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
	
	
/**** Ajax methods ****/
	/**
	 * @param int $AccountID
	 * @param string $ClientTimeZone
	 * @return array | boolean
	 */
	public function GetExtensions($AccountID, $ClientTimeZone)
	{
		$mResult = false;
		$oAccount = $this->oApiAccountsManager->getAccountById($AccountID);
		if ($oAccount)
		{
			if ('' !== $ClientTimeZone)
			{
				$oAccount->User->ClientTimeZone = $ClientTimeZone;
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
	 * @param int $AccountID
	 * @return array
	 */
	public function GetFolders($AccountID)
	{
		$oAccount = $this->oApiAccountsManager->getAccountById($AccountID);
		$oFolderCollection = $this->oApiMainManager->getFolders($oAccount);
		return array(
			'Folders' => $oFolderCollection, 
			'Namespace' => $oFolderCollection->GetNamespace()
		);
	}
	
	/**
	 * 
	 * @param string $Folder
	 * @param int $Offset
	 * @param int $Limit
	 * @param string $Search
	 * @param string $Filters
	 * @param int $UseThreads
	 * @param string $InboxUidnext
	 * @param int $AccountID
	 * @return array
	 * @throws \Core\Exceptions\ClientException
	 */
	public function GetMessages($Folder, $Offset, $Limit, $Search, $Filters, $UseThreads, $InboxUidnext, $AccountID)
	{
		$sOffset = trim((string) $Offset);
		$sLimit = trim((string) $Limit);
		$sSearch = trim((string) $Search);
		$bUseThreads = '1' === trim((string) $UseThreads);
		$sInboxUidnext = $InboxUidnext;
		
		$aFilters = array();
		$sFilters = strtolower(trim((string) $Filters));
		if (0 < strlen($sFilters))
		{
			$aFilters = array_filter(explode(',', $sFilters), function ($sValue) {
				return '' !== trim($sValue);
			});
		}

		$iOffset = 0 < strlen($sOffset) && is_numeric($sOffset) ? (int) $sOffset : 0;
		$iLimit = 0 < strlen($sLimit) && is_numeric($sLimit) ? (int) $sLimit : 0;

		if (0 === strlen(trim($Folder)) || 0 > $iOffset || 0 >= $iLimit || 200 < $sLimit)
		{
			throw new \Core\Exceptions\ClientException(\Core\Notifications::InvalidInputParameter);
		}

		$oAccount = $this->oApiAccountsManager->getAccountById($AccountID);

		return $this->oApiMainManager->getMessageList(
			$oAccount, $Folder, $iOffset, $iLimit, $sSearch, $bUseThreads, $aFilters, $sInboxUidnext);
	}

	/**
	 * @param array $Folders
	 * @param int $AccountID
	 * @param string $InboxUidnext
	 * @return array
	 * @throws \ProjectCore\Exceptions\ClientException
	 * @throws \MailSo\Net\Exceptions\ConnectionException
	 */
	public function GetRelevantFoldersInformation($Folders, $AccountID, $InboxUidnext = '')
	{
		if (!is_array($Folders) || 0 === count($Folders))
		{
			throw new \ProjectCore\Exceptions\ClientException(\ProjectCore\Notifications::InvalidInputParameter);
		}

		$aResult = array();
		$oAccount = null;

		try
		{
			$oAccount = $this->oApiAccountsManager->getAccountById($AccountID);
			$oReturnInboxNewData = \Core\DataByRef::createInstance(array());
			$aResult = $this->oApiMainManager->getFolderListInformation($oAccount, $Folders, $InboxUidnext, $oReturnInboxNewData);
		}
		catch (\MailSo\Net\Exceptions\ConnectionException $oException)
		{
			throw $oException;
		}
		catch (\MailSo\Imap\Exceptions\LoginException $oException)
		{
			throw $oException;
		}
		catch (\Exception $oException)
		{
			\CApi::Log((string) $oException);
		}

		return array(
			'Counts' => $aResult,
			'New' => isset($oReturnInboxNewData) ? $oReturnInboxNewData->GetData() : ''
		);
	}	
	
	/**
	 * @param int $AccountID
	 * @return array
	 */
	public function GetQuota($AccountID)
	{
		$oAccount = $this->oApiAccountsManager->getAccountById($AccountID);
		return $this->oApiMainManager->getQuota($oAccount);
	}

	/**
	 * @param int $AccountID
	 * @param string $Folder
	 * @param array $Uids
	 * @return array
	 * @throws \Core\Exceptions\ClientException
	 */
	public function GetMessagesBodies($AccountID, $Folder, $Uids)
	{
		if (0 === strlen(trim($Folder)) || !is_array($Uids) || 0 === count($Uids))
		{
			throw new \Core\Exceptions\ClientException(\Core\Notifications::InvalidInputParameter);
		}

		$aList = array();
		foreach ($Uids as $iUid)
		{
			if (is_numeric($iUid))
			{
				$oMessage = $this->GetMessage($AccountID, $Folder, (string) $iUid);
				if ($oMessage instanceof \CApiMailMessage)
				{
					$aList[] = $oMessage;
				}

				unset($oMessage);
			}
		}

		return $aList;
	}

	/**
	 * @param int $AccountID
	 * @param string $Folder
	 * @param string $Uid
	 * @param string $Rfc822MimeIndex
	 * @return \CApiMailMessage
	 * @throws \Core\Exceptions\ClientException
	 * @throws CApiInvalidArgumentException
	 */
	public function GetMessage($AccountID, $Folder, $Uid, $Rfc822MimeIndex = '')
	{
		$iBodyTextLimit = 600000;
		
		$iUid = 0 < strlen($Uid) && is_numeric($Uid) ? (int) $Uid : 0;

		if (0 === strlen(trim($Folder)) || 0 >= $iUid)
		{
			throw new \Core\Exceptions\ClientException(\Core\Notifications::InvalidInputParameter);
		}

		$oAccount = $this->oApiAccountsManager->getAccountById($AccountID);

		if (0 === strlen($Folder) || !is_numeric($iUid) || 0 >= (int) $iUid)
		{
			throw new CApiInvalidArgumentException();
		}

		$oImapClient =& $this->oApiMainManager->_getImapClient($oAccount);

		$oImapClient->FolderExamine($Folder);

		$oMessage = false;

		$aTextMimeIndexes = array();
		$aAscPartsIds = array();

		$aFetchResponse = $oImapClient->Fetch(array(
			\MailSo\Imap\Enumerations\FetchType::BODYSTRUCTURE), $iUid, true);

		$oBodyStructure = (0 < count($aFetchResponse)) ? $aFetchResponse[0]->GetFetchBodyStructure($Rfc822MimeIndex) : null;
		
		$aCustomParts = array();
		if ($oBodyStructure)
		{
			$aTextParts = $oBodyStructure->SearchHtmlOrPlainParts();
			if (is_array($aTextParts) && 0 < count($aTextParts))
			{
				foreach ($aTextParts as $oPart)
				{
					$aTextMimeIndexes[] = array($oPart->PartID(), $oPart->Size());
				}
			}

			$aParts = $oBodyStructure->GetAllParts();
					
			$this->broadcastEvent('GetBodyStructureParts', array($aParts, &$aCustomParts));
			
			$bParseAsc = true;
			if ($bParseAsc)
			{
				$aAscParts = $oBodyStructure->SearchByCallback(function (/* @var $oPart \MailSo\Imap\BodyStructure */ $oPart) {
					return '.asc' === \strtolower(\substr(\trim($oPart->FileName()), -4));
				});

				if (is_array($aAscParts) && 0 < count($aAscParts))
				{
					foreach ($aAscParts as $oPart)
					{
						$aAscPartsIds[] = $oPart->PartID();
					}
				}
			}
		}

		$aFetchItems = array(
			\MailSo\Imap\Enumerations\FetchType::INDEX,
			\MailSo\Imap\Enumerations\FetchType::UID,
			\MailSo\Imap\Enumerations\FetchType::RFC822_SIZE,
			\MailSo\Imap\Enumerations\FetchType::INTERNALDATE,
			\MailSo\Imap\Enumerations\FetchType::FLAGS,
			0 < strlen($Rfc822MimeIndex)
				? \MailSo\Imap\Enumerations\FetchType::BODY_PEEK.'['.$Rfc822MimeIndex.'.HEADER]'
				: \MailSo\Imap\Enumerations\FetchType::BODY_HEADER_PEEK
		);

		if (0 < count($aTextMimeIndexes))
		{
			if (0 < strlen($Rfc822MimeIndex) && is_numeric($Rfc822MimeIndex))
			{
				$sLine = \MailSo\Imap\Enumerations\FetchType::BODY_PEEK.'['.$aTextMimeIndexes[0][0].'.1]';
				if (\is_numeric($iBodyTextLimit) && 0 < $iBodyTextLimit && $iBodyTextLimit < $aTextMimeIndexes[0][1])
				{
					$sLine .= '<0.'.((int) $iBodyTextLimit).'>';
				}

				$aFetchItems[] = $sLine;
			}
			else
			{
				foreach ($aTextMimeIndexes as $aTextMimeIndex)
				{
					$sLine = \MailSo\Imap\Enumerations\FetchType::BODY_PEEK.'['.$aTextMimeIndex[0].']';
					if (\is_numeric($iBodyTextLimit) && 0 < $iBodyTextLimit && $iBodyTextLimit < $aTextMimeIndex[1])
					{
						$sLine .= '<0.'.((int) $iBodyTextLimit).'>';
					}
					
					$aFetchItems[] = $sLine;
				}
			}
		}
		
		foreach ($aCustomParts as $oCustomPart)
		{
			$aFetchItems[] = \MailSo\Imap\Enumerations\FetchType::BODY_PEEK.'['.$oCustomPart->PartID().']';
		}

		if (0 < count($aAscPartsIds))
		{
			foreach ($aAscPartsIds as $sPartID)
			{
				$aFetchItems[] = \MailSo\Imap\Enumerations\FetchType::BODY_PEEK.'['.$sPartID.']';
			}
		}

		if (!$oBodyStructure)
		{
			$aFetchItems[] = \MailSo\Imap\Enumerations\FetchType::BODYSTRUCTURE;
		}

		$aFetchResponse = $oImapClient->Fetch($aFetchItems, $iUid, true);
		if (0 < count($aFetchResponse))
		{
			$oMessage = CApiMailMessage::createInstance($Folder, $aFetchResponse[0], $oBodyStructure, $Rfc822MimeIndex, $aAscPartsIds);
		}

		if ($oMessage)
		{
			$sFromEmail = '';
			$oFromCollection = $oMessage->getFrom();
			if ($oFromCollection && 0 < $oFromCollection->Count())
			{
				$oFrom =& $oFromCollection->GetByIndex(0);
				if ($oFrom)
				{
					$sFromEmail = trim($oFrom->GetEmail());
				}
			}

			if (0 < strlen($sFromEmail))
			{
				$oApiUsersManager = /* @var CApiUsersManager */ CApi::GetCoreManager('users');
				$bAlwaysShowImagesInMessage = !!\CApi::GetSettingsConf('WebMail/AlwaysShowImagesInMessage');
				$oMessage->setSafety($bAlwaysShowImagesInMessage ? true : 
						$oApiUsersManager->getSafetySender($oAccount->IdUser, $sFromEmail, true));
			}
			
			$aData = array();
			foreach ($aCustomParts as $oCustomPart)
			{
				$sData = $aFetchResponse[0]->GetFetchValue(\MailSo\Imap\Enumerations\FetchType::BODY.'['.$oCustomPart->PartID().']');
				if (!empty($sData))
				{
					$sData = \MailSo\Base\Utils::DecodeEncodingValue($sData, $oCustomPart->MailEncodingName());
					$sData = \MailSo\Base\Utils::ConvertEncoding($sData,
						\MailSo\Base\Utils::NormalizeCharset($oCustomPart->Charset(), true),
						\MailSo\Base\Enumerations\Charset::UTF_8);
				}
				$aData[] = array(
					'Data' => $sData,
					'Part' => $oCustomPart
				);
			}
			
			$this->broadcastEvent('ExtendMessageData', array($oAccount, &$oMessage, $aData));
		}

		if (!($oMessage instanceof \CApiMailMessage))
		{
			throw new \Core\Exceptions\ClientException(\Core\Notifications::CanNotGetMessage);
		}

		return $oMessage;
	}

	/**
	 * @param string $Folder
	 * @param string $Uids
	 * @param int $SetAction
	 * @param int $AccountID
	 * @return boolean
	 */
	public function SetMessagesSeen($Folder, $Uids, $SetAction, $AccountID)
	{
		return $this->setMessageFlag($Folder, $Uids, $SetAction, $AccountID, \MailSo\Imap\Enumerations\MessageFlag::SEEN, 'SetMessageSeen');
	}	
	
	/**
	 * @param string $sFolderFullNameRaw
	 * @param string $sUids
	 * @param int $iSetAction
	 * @param int $AccountID
	 * @param string $sFlagName
	 * @param string $sFunctionName
	 * @return boolean
	 * @throws \Core\Exceptions\ClientException
	 */
	private function setMessageFlag($sFolderFullNameRaw, $sUids, $iSetAction, $AccountID, $sFlagName, $sFunctionName)
	{
		$bSetAction = 1 === $iSetAction;
		$aUids = \api_Utils::ExplodeIntUids((string) $sUids);

		if (0 === strlen(trim($sFolderFullNameRaw)) || !is_array($aUids) || 0 === count($aUids))
		{
			throw new \Core\Exceptions\ClientException(\Core\Notifications::InvalidInputParameter);
		}

		$oAccount = $this->oApiAccountsManager->getAccountById($AccountID);

		return $this->oApiMainManager->setMessageFlag($oAccount, $sFolderFullNameRaw, $aUids, $sFlagName,
			$bSetAction ? \EMailMessageStoreAction::Add : \EMailMessageStoreAction::Remove);
	}	
}

return new MailModule('1.0');
