<?php

class MailModule extends AApiModule
{
	public $oApiMailManager = null;
	public $oApiAccountsManager = null;
	
	public function init() 
	{
		$this->oApiAccountsManager = $this->GetManager('accounts');
		$this->oApiMailManager = $this->GetManager('main');
		
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
			'AllowAddNewAccounts' => false, // AppData.App.AllowUsersAddNewAccounts
			'AllowAppRegisterMailto' => false, // AppData.App.AllowAppRegisterMailto
			'AllowAutosaveInDrafts' => $oUser->{'Mail::AllowAutosaveInDrafts'},
			'AllowChangeEmailSettings' => false, // AppData.App.AllowUsersChangeEmailSettings
			'AllowChangeInputDirection' => $oUser->{'Mail::AllowChangeInputDirection'},
			'AllowExpandFolders' => false, // AppData.MailExpandFolders
			'AllowFetchers' => false, // AppData.User.AllowFetcher
			'AllowIdentities' => false, // AppData.AllowIdentities
			'AllowInsertImage' => false, // AppData.App.AllowInsertImage
			'AllowSaveMessageAsPdf' => false, // AppData.AllowSaveAsPdf
			'AllowThreads' => false, // AppData.User.ThreadsEnabled
			'AllowZipAttachments' => false, // AppData.ZipAttachments
			'AutoSave' => false, // AppData.App.AutoSave ??? uses in OpenPgp
			'AutoSaveIntervalSeconds' => false, // add to settings
			'AutosignOutgoingEmails' => false, // AppData.User.AutosignOutgoingEmails
			'ComposeToolbarOrder' => array('back', 'send', 'save', 'importance', 'MailSensitivity', 'confirmation', 'OpenPgp'), // add to settings
			'DefaultFontName' => 'Tahoma', // AppData.HtmlEditorDefaultFontName
			'DefaultFontSize' => 3, // AppData.HtmlEditorDefaultFontSize
			'ImageUploadSizeLimit' => 0, // AppData.App.ImageUploadSizeLimit
			'JoinReplyPrefixes' => false, // AppData.App.JoinReplyPrefixes
			'MailsPerPage' => $oUser->{'Mail::MailsPerPage'},
			'MaxMessagesBodiesSizeToPrefetch' => 50000, // add to settings
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
			throw new \System\Exceptions\ClientException(\System\Notifications::NonUserPassed);
		}

		return false;
	}
	
	public function UpdateAccount($AccountID, $Email = null, $IncomingMailPassword = null, $IncomingMailServer = null, $FriendlyName = null, 
			$IncomingMailLogin = null, $IncomingMailPort = null, $IncomingMailSsl = null, $OutgoingMailLogin = null, $OutgoingMailServer = null, 
			$OutgoingMailPort = null, $OutgoingMailSsl = null, $OutgoingMailAuth = null)
	{
		if ($AccountID > 0)
		{
			$oAccount = $this->oApiAccountsManager->getAccountById($AccountID);
			
			if ($oAccount)
			{
				if ($Email !== null)
				{
					$oAccount->Email = $Email;
				}
				if ($IncomingMailPassword !== null)
				{
					$oAccount->IncomingMailPassword = $IncomingMailPassword;
				}
				if ($IncomingMailServer !== null)
				{
					$oAccount->IncomingMailServer = $IncomingMailServer;
				}
				if ($FriendlyName !== null)
				{
					$oAccount->FriendlyName = $FriendlyName;
				}
				if ($IncomingMailLogin !== null)
				{
					$oAccount->IncomingMailLogin = $IncomingMailLogin;
				}
				if ($IncomingMailPort !== null)
				{
					$oAccount->IncomingMailPort = $IncomingMailPort;
				}
				if ($IncomingMailSsl !== null)
				{
					$oAccount->IncomingMailUseSSL = $IncomingMailSsl;
				}
				if ($OutgoingMailLogin !== null)
				{
					$oAccount->OutgoingMailLogin = $OutgoingMailLogin;
				}
				if ($OutgoingMailServer !== null)
				{
					$oAccount->OutgoingMailServer = $OutgoingMailServer;
				}
				if ($OutgoingMailPort !== null)
				{
					$oAccount->OutgoingMailPort = $OutgoingMailPort;
				}
				if ($OutgoingMailSsl !== null)
				{
					$oAccount->OutgoingMailUseSSL = $OutgoingMailSsl;
				}
				if ($OutgoingMailAuth !== null)
				{
					$oAccount->OutgoingMailAuth = $OutgoingMailAuth;
				}
				
				$this->oApiAccountsManager->updateAccount($oAccount);
			}
			
			return $oAccount ? array(
				'iObjectId' => $oAccount->iObjectId
			) : false;
		}
		else
		{
			throw new \System\Exceptions\ClientException(\System\Notifications::UserNotAllowed);
		}

		return false;
	}
	
	/**
	 * @param int $AccountID
	 * @return boolean
	 * @throws \System\Exceptions\ClientException
	 */
	public function DeleteAccount($AccountID)
	{
		$bResult = false;

		if ($AccountID > 0)
		{
			$oAccount = $this->oApiAccountsManager->getAccountById($AccountID);
			
			if ($oAccount)
			{
				$bResult = $this->oApiAccountsManager->deleteAccount($oAccount);
			}
			
			return $bResult;
		}
		else
		{
			throw new \System\Exceptions\ClientException(\System\Notifications::UserNotAllowed);
		}
	}
	
	public function checkAuth($aParams, &$mResult)
	{
		$sLogin = $aParams['Login'];
		$sPassword = $aParams['Password'];
		$bSignMe = $aParams['SignMe'];
		
		$oAccount = $this->oApiAccountsManager->getAccountByCredentials($sLogin, $sPassword);

		if ($oAccount)
		{
			$this->oApiMailManager->validateAccountConnection($oAccount);
			$mResult = array(
				'token' => 'auth',
				'sign-me' => $bSignMe,
				'id' => $oAccount->IdUser
//				'email' => $oAccount->Email
			);
		}
	}
	
	
/**** Ajax methods ****/
	public function GetAccountSettings($AccountID)
	{
		$oAccount = $this->oApiAccountsManager->getAccountById($AccountID);
		return array(
			'Id' => $oAccount->iObjectId,
			'IsDefault' => $oAccount->IsDefaultAccount,
			'Email' => $oAccount->Email,
			'FriendlyName' => $oAccount->FriendlyName,
			'IncomingMailProtocol' => $oAccount->IncomingMailProtocol,
			'IncomingMailServer' => $oAccount->IncomingMailServer,
			'IncomingMailPort' => $oAccount->IncomingMailPort,
			'IncomingMailLogin' => $oAccount->IncomingMailLogin,
			'IncomingMailPassword' => $oAccount->IncomingMailPassword,
			'IncomingMailUseSSL' => $oAccount->IncomingMailUseSSL,
			'OutgoingMailServer' => $oAccount->OutgoingMailServer,
			'OutgoingMailPort' => $oAccount->OutgoingMailPort,
			'OutgoingMailLogin' => $oAccount->OutgoingMailLogin,
			'OutgoingMailPassword' => $oAccount->OutgoingMailPassword,
			'OutgoingMailAuth' => $oAccount->OutgoingMailAuth,
			'OutgoingMailUseSSL' => $oAccount->OutgoingMailUseSSL
		);
	}
	
	/**
	 * @param int $AccountID
	 * @param string $ClientTimeZone
	 * @return array | boolean
	 */
	public function GetExtensions($AccountID, $ClientTimeZone = '')
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
		$oFolderCollection = $this->oApiMailManager->getFolders($oAccount);
		return array(
			'Folders' => $oFolderCollection, 
			'Namespace' => $oFolderCollection->GetNamespace()
		);
	}
	
	/**
	 * @param int $AccountID
	 * @param string $Folder
	 * @param int $Offset
	 * @param int $Limit
	 * @param string $Search
	 * @param string $Filters
	 * @param int $UseThreads
	 * @param string $InboxUidnext
	 * @return array
	 * @throws \System\Exceptions\ClientException
	 */
	public function GetMessages($AccountID, $Folder, $Offset = 0, $Limit = 20, $Search = '', $Filters = '', $UseThreads = 0, $InboxUidnext = '')
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
			throw new \System\Exceptions\ClientException(\System\Notifications::InvalidInputParameter);
		}

		$oAccount = $this->oApiAccountsManager->getAccountById($AccountID);

		return $this->oApiMailManager->getMessageList(
			$oAccount, $Folder, $iOffset, $iLimit, $sSearch, $bUseThreads, $aFilters, $sInboxUidnext);
	}

	/**
	 * @param int $AccountID
	 * @param array $Folders
	 * @param string $InboxUidnext
	 * @return array
	 * @throws \ProjectSystem\Exceptions\ClientException
	 * @throws \MailSo\Net\Exceptions\ConnectionException
	 */
	public function GetRelevantFoldersInformation($AccountID, $Folders, $InboxUidnext = '')
	{
		if (!is_array($Folders) || 0 === count($Folders))
		{
			throw new \ProjectSystem\Exceptions\ClientException(\ProjectSystem\Notifications::InvalidInputParameter);
		}

		$aResult = array();
		$oAccount = null;

		try
		{
			$oAccount = $this->oApiAccountsManager->getAccountById($AccountID);
			$oReturnInboxNewData = \System\DataByRef::createInstance(array());
			$aResult = $this->oApiMailManager->getFolderListInformation($oAccount, $Folders, $InboxUidnext, $oReturnInboxNewData);
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
		return $this->oApiMailManager->getQuota($oAccount);
	}

	/**
	 * @param int $AccountID
	 * @param string $Folder
	 * @param array $Uids
	 * @return array
	 * @throws \System\Exceptions\ClientException
	 */
	public function GetMessagesBodies($AccountID, $Folder, $Uids)
	{
		if (0 === strlen(trim($Folder)) || !is_array($Uids) || 0 === count($Uids))
		{
			throw new \System\Exceptions\ClientException(\System\Notifications::InvalidInputParameter);
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
	 * @throws \System\Exceptions\ClientException
	 * @throws CApiInvalidArgumentException
	 */
	public function GetMessage($AccountID, $Folder, $Uid, $Rfc822MimeIndex = '')
	{
		$iBodyTextLimit = 600000;
		
		$iUid = 0 < strlen($Uid) && is_numeric($Uid) ? (int) $Uid : 0;

		if (0 === strlen(trim($Folder)) || 0 >= $iUid)
		{
			throw new \System\Exceptions\ClientException(\System\Notifications::InvalidInputParameter);
		}

		$oAccount = $this->oApiAccountsManager->getAccountById($AccountID);

		if (0 === strlen($Folder) || !is_numeric($iUid) || 0 >= (int) $iUid)
		{
			throw new CApiInvalidArgumentException();
		}

		$oImapClient =& $this->oApiMailManager->_getImapClient($oAccount);

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
			throw new \System\Exceptions\ClientException(\System\Notifications::CanNotGetMessage);
		}

		return $oMessage;
	}

	/**
	 * @param int $AccountID
	 * @param string $Folder
	 * @param string $Uids
	 * @param int $SetAction
	 * @return boolean
	 */
	public function SetMessagesSeen($AccountID, $Folder, $Uids, $SetAction)
	{
		return $this->setMessageFlag($AccountID, $Folder, $Uids, $SetAction, \MailSo\Imap\Enumerations\MessageFlag::SEEN);
	}	
	
	/**
	 * @param int $AccountID
	 * @param string $Folder
	 * @param string $Uids
	 * @param int $SetAction
	 * @return boolean
	 */
	public function SetMessageFlagged($AccountID, $Folder, $Uids, $SetAction)
	{
		return $this->setMessageFlag($AccountID, $Folder, $Uids, $SetAction, \MailSo\Imap\Enumerations\MessageFlag::FLAGGED);
	}
	
	/**
	 * @param int $AccountID
	 * @param string $sFolderFullNameRaw
	 * @param string $sUids
	 * @param int $iSetAction
	 * @param string $sFlagName
	 * @return boolean
	 * @throws \System\Exceptions\ClientException
	 */
	private function setMessageFlag($AccountID, $sFolderFullNameRaw, $sUids, $iSetAction, $sFlagName)
	{
		$bSetAction = 1 === $iSetAction;
		$aUids = \api_Utils::ExplodeIntUids((string) $sUids);

		if (0 === strlen(trim($sFolderFullNameRaw)) || !is_array($aUids) || 0 === count($aUids))
		{
			throw new \System\Exceptions\ClientException(\System\Notifications::InvalidInputParameter);
		}

		$oAccount = $this->oApiAccountsManager->getAccountById($AccountID);

		return $this->oApiMailManager->setMessageFlag($oAccount, $sFolderFullNameRaw, $aUids, $sFlagName,
			$bSetAction ? \EMailMessageStoreAction::Add : \EMailMessageStoreAction::Remove);
	}
	
	/**
	 * @param int $AccountID
	 * @param string $Folder
	 * @return boolean
	 * @throws \System\Exceptions\ClientException
	 */
	public function SetAllMessagesSeen($AccountID, $Folder)
	{
		if (0 === strlen(trim($Folder)))
		{
			throw new \System\Exceptions\ClientException(\System\Notifications::InvalidInputParameter);
		}

		$oAccount = $this->oApiAccountsManager->getAccountById($AccountID);

		return $this->oApiMailManager->setMessageFlag($oAccount, $Folder, array('1'),
			\MailSo\Imap\Enumerations\MessageFlag::SEEN, \EMailMessageStoreAction::Add, true);
	}

	/**
	 * @param int $AccountID
	 * @param string $Folder
	 * @param string $ToFolder
	 * @param string $Uids
	 * @return boolean
	 * @throws \System\Exceptions\ClientException
	 */
	public function MoveMessages($AccountID, $Folder, $ToFolder, $Uids)
	{
		$aUids = \api_Utils::ExplodeIntUids((string) $Uids);

		if (0 === strlen(trim($Folder)) || 0 === strlen(trim($ToFolder)) || !is_array($aUids) || 0 === count($aUids))
		{
			throw new \System\Exceptions\ClientException(\System\Notifications::InvalidInputParameter);
		}

		$oAccount = $this->oApiAccountsManager->getAccountById($AccountID);

		try
		{
			$this->oApiMailManager->moveMessage($oAccount, $Folder, $ToFolder, $aUids);
		}
		catch (\MailSo\Imap\Exceptions\NegativeResponseException $oException)
		{
			$oResponse = /* @var $oResponse \MailSo\Imap\Response */ $oException->GetLastResponse();
			throw new \System\Exceptions\ClientException(\System\Notifications::CanNotMoveMessageQuota, $oException,
				$oResponse instanceof \MailSo\Imap\Response ? $oResponse->Tag.' '.$oResponse->StatusOrIndex.' '.$oResponse->HumanReadable : '');
		}
		catch (\Exception $oException)
		{
			throw new \System\Exceptions\ClientException(\System\Notifications::CanNotMoveMessage, $oException,
				$oException->getMessage());
		}

		return true;
	}
	
	/**
	 * @param int $AccountID
	 * @param string $Folder
	 * @param string $Uids
	 * @return boolean
	 * @throws \System\Exceptions\ClientException
	 */
	public function DeleteMessages($AccountID, $Folder, $Uids)
	{
		$aUids = \api_Utils::ExplodeIntUids((string) $Uids);

		if (0 === strlen(trim($Folder)) || !is_array($aUids) || 0 === count($aUids))
		{
			throw new \System\Exceptions\ClientException(\System\Notifications::InvalidInputParameter);
		}

		$oAccount = $this->oApiAccountsManager->getAccountById($AccountID);

		$this->oApiMailManager->deleteMessage($oAccount, $Folder, $aUids);

		return true;
	}
	
	/**** functions below have not been tested ***/
	/**
	 * @param int $AccountID
	 * @param string $FolderNameInUtf8
	 * @param string $FolderParentFullNameRaw
	 * @param string $Delimiter
	 * @return boolean
	 * @throws \System\Exceptions\ClientException
	 */
	public function CreateFolder($AccountID, $FolderNameInUtf8, $FolderParentFullNameRaw, $Delimiter)
	{
		if (0 === strlen($FolderNameInUtf8) || 1 !== strlen($Delimiter))
		{
			throw new \System\Exceptions\ClientException(\System\Notifications::InvalidInputParameter);
		}

		$oAccount = $this->oApiAccountsManager->getAccountById($AccountID);

		$this->oApiMailManager->createFolder($oAccount, $FolderNameInUtf8, $Delimiter, $FolderParentFullNameRaw);

		if (!$oAccount->isExtensionEnabled(\CAccount::DisableFoldersManualSort))
		{
			$aFoldersOrderList = $this->oApiMailManager->getFoldersOrder($oAccount);
			if (is_array($aFoldersOrderList) && 0 < count($aFoldersOrderList))
			{
				$aFoldersOrderListNew = $aFoldersOrderList;

				$sFolderNameInUtf7Imap = \MailSo\Base\Utils::ConvertEncoding($FolderNameInUtf8,
					\MailSo\Base\Enumerations\Charset::UTF_8,
					\MailSo\Base\Enumerations\Charset::UTF_7_IMAP);

				$sFolderFullNameRaw = (0 < strlen($FolderParentFullNameRaw) ? $FolderParentFullNameRaw.$Delimiter : '').
					$sFolderNameInUtf7Imap;

				$sFolderFullNameUtf8 = \MailSo\Base\Utils::ConvertEncoding($sFolderFullNameRaw,
					\MailSo\Base\Enumerations\Charset::UTF_7_IMAP,
					\MailSo\Base\Enumerations\Charset::UTF_8);

				$aFoldersOrderListNew[] = $sFolderFullNameRaw;

				$aFoldersOrderListUtf8 = array_map(function ($sValue) {
					return \MailSo\Base\Utils::ConvertEncoding($sValue,
						\MailSo\Base\Enumerations\Charset::UTF_7_IMAP,
						\MailSo\Base\Enumerations\Charset::UTF_8);
				}, $aFoldersOrderListNew);

				usort($aFoldersOrderListUtf8, 'strnatcasecmp');
				
				$iKey = array_search($sFolderFullNameUtf8, $aFoldersOrderListUtf8, true);
				if (is_int($iKey) && 0 < $iKey && isset($aFoldersOrderListUtf8[$iKey - 1]))
				{
					$sUpperName = $aFoldersOrderListUtf8[$iKey - 1];

					$iUpperKey = array_search(\MailSo\Base\Utils::ConvertEncoding($sUpperName,
						\MailSo\Base\Enumerations\Charset::UTF_8,
						\MailSo\Base\Enumerations\Charset::UTF_7_IMAP), $aFoldersOrderList, true);

					if (is_int($iUpperKey) && isset($aFoldersOrderList[$iUpperKey]))
					{
						\CApi::Log('insert order index:'.$iUpperKey);
						array_splice($aFoldersOrderList, $iUpperKey + 1, 0, $sFolderFullNameRaw);
						$this->oApiMailManager->updateFoldersOrder($oAccount, $aFoldersOrderList);
					}
				}
			}
		}

		return true;
	}
	
	/**
	 * @param int $AccountID
	 * @param string $PrevFolderFullNameRaw
	 * @param string $NewFolderNameInUtf8
	 * @return array | boolean
	 * @throws \System\Exceptions\ClientException
	 */
	public function RenameFolder($AccountID, $PrevFolderFullNameRaw, $NewFolderNameInUtf8)
	{
		if (0 === strlen($PrevFolderFullNameRaw) || 0 === strlen($NewFolderNameInUtf8))
		{
			throw new \System\Exceptions\ClientException(\System\Notifications::InvalidInputParameter);
		}

		$oAccount = $this->oApiAccountsManager->getAccountById($AccountID);

		$mResult = $this->oApiMailManager->renameFolder($oAccount, $PrevFolderFullNameRaw, $NewFolderNameInUtf8);

		return (0 < strlen($mResult) ? array(
			'FullName' => $mResult,
			'FullNameHash' => md5($mResult)
		) : false);
	}

	/**
	 * @param int $AccountID
	 * @param string $Folder
	 * @return boolean
	 * @throws \System\Exceptions\ClientException
	 */
	public function DeleteFolder($AccountID, $Folder)
	{
		if (0 === strlen(trim($Folder)))
		{
			throw new \System\Exceptions\ClientException(\System\Notifications::InvalidInputParameter);
		}

		$oAccount = $this->oApiAccountsManager->getAccountById($AccountID);

		$this->oApiMailManager->deleteFolder($oAccount, $Folder);

		return true;
	}	

	/**
	 * @param int $AccountID
	 * @param string $Folder
	 * @param type $SetAction
	 * @return int
	 * @throws \System\Exceptions\ClientException
	 */
	public function SubscribeFolder($AccountID, $Folder, $SetAction)
	{
		$bSetAction = 1 === $SetAction;

		if (0 === strlen(trim($Folder)))
		{
			throw new \System\Exceptions\ClientException(\System\Notifications::InvalidInputParameter);
		}

		$oAccount = $this->oApiAccountsManager->getAccountById($AccountID);

		if (!$oAccount->isExtensionEnabled(\CAccount::DisableManageSubscribe))
		{
			$this->oApiMailManager->subscribeFolder($oAccount, $Folder, $bSetAction);
			return true;
		}

		return false;
	}	
	
	/**
	 * @param int $AccountID
	 * @param array $FolderList
	 * @return boolean
	 * @throws \System\Exceptions\ClientException
	 */
	public function UpdateFoldersOrder($AccountID, $FolderList)
	{
		if (!is_array($FolderList))
		{
			throw new \System\Exceptions\ClientException(\System\Notifications::InvalidInputParameter);
		}

		$oAccount = $this->oApiAccountsManager->getAccountById($AccountID);
		if ($oAccount->isExtensionEnabled(\CAccount::DisableFoldersManualSort))
		{
			return false;
		}

		return $this->oApiMailManager->updateFoldersOrder($oAccount, $FolderList);
	}
	
	/**
	 * @param int $AccountID
	 * @param string $Folder
	 * @return boolean
	 * @throws \System\Exceptions\ClientException
	 */
	public function ClearFolder($AccountID, $Folder)
	{
		if (0 === strlen(trim($Folder)))
		{
			throw new \System\Exceptions\ClientException(\System\Notifications::InvalidInputParameter);
		}

		$oAccount = $this->oApiAccountsManager->getAccountById($AccountID);

		$this->oApiMailManager->clearFolder($oAccount, $Folder);

		return true;
	}
	
	/**
	 * @param int $AccountID
	 * @param string $Folder
	 * @param array $Uids
	 * @return array
	 * @throws \System\Exceptions\ClientException
	 */
	public function GetMessagesByUids($AccountID, $Folder, $Uids)
	{
		if (0 === strlen(trim($Folder)) || !is_array($Uids))
		{
			throw new \System\Exceptions\ClientException(\System\Notifications::InvalidInputParameter);
		}

		$oAccount = $this->oApiAccountsManager->getAccountById($AccountID);

		return $this->oApiMailManager->getMessageListByUids($oAccount, $Folder, $Uids);
	}
	
	/**
	 * @param int $AccountID
	 * @param string $Folder
	 * @param array $Uids
	 * @return array
	 * @throws \System\Exceptions\ClientException
	 */
	public function GetMessagesFlags($AccountID, $Folder, $Uids)
	{
		if (0 === strlen(trim($Folder)) || !is_array($Uids))
		{
			throw new \System\Exceptions\ClientException(\System\Notifications::InvalidInputParameter);
		}

		$oAccount = $this->oApiAccountsManager->getAccountById($AccountID);

		return $this->oApiMailManager->getMessagesFlags($oAccount, $Folder, $Uids);
	}
	
	/**
	 * @param int $AccountID
	 * @param string $DraftFolder
	 * @param string $DraftUid
	 * @param string $FetcherID
	 * @param string $IdentityID
	 * @return array
	 * @throws \System\Exceptions\ClientException
	 */
	public function SaveMessage($AccountID, $DraftFolder, $DraftUid, $FetcherID, $IdentityID)
	{
		$mResult = false;

		$oAccount = $this->oApiAccountsManager->getAccountById($AccountID);

		if (0 === strlen($DraftFolder))
		{
			throw new \System\Exceptions\ClientException(\System\Notifications::InvalidInputParameter);
		}

		$oFetcher = null;
		if (!empty($FetcherID) && is_numeric($FetcherID) && 0 < (int) $FetcherID)
		{
			$iFetcherID = (int) $FetcherID;

			$oApiFetchers = $this->GetManager('fetchers');
			$aFetchers = $oApiFetchers->getFetchers($oAccount);
			if (is_array($aFetchers) && 0 < count($aFetchers))
			{
				foreach ($aFetchers as /* @var $oFetcherItem \CFetcher */ $oFetcherItem)
				{
					if ($oFetcherItem && $iFetcherID === $oFetcherItem->IdFetcher && $oAccount->IdUser === $oFetcherItem->IdUser)
					{
						$oFetcher = $oFetcherItem;
						break;
					}
				}
			}
		}

		$oIdentity = null;
		if (!empty($IdentityID) && is_numeric($IdentityID) && 0 < (int) $IdentityID)
		{
			$oApiUsers = \CApi::GetCoreManager('users');
			$oIdentity = $oApiUsers->getIdentity((int) $IdentityID);
		}

		$oMessage = $this->buildMessage($oAccount, $oFetcher, true, $oIdentity);
		if ($oMessage)
		{
			try
			{
				\CApi::Plugin()->RunHook('webmail.build-message-for-save', array(&$oMessage));
				
				$mResult = $this->oApiMailManager->saveMessage($oAccount, $oMessage, $DraftFolder, $DraftUid);
			}
			catch (\CApiManagerException $oException)
			{
				$iCode = \System\Notifications::CanNotSaveMessage;
				throw new \System\Exceptions\ClientException($iCode, $oException);
			}
		}

		return $mResult;
	}	
	
	/**
	 * @return array
	 */
//	public function SendMessageObject()
//	{
//		$oAccount = $this->getParamValue('Account', null);
//		$oMessage = $this->getParamValue('Message', null);
//		
//		return $this->oApiMailManager->sendMessage($oAccount, $oMessage);
//	}
	
	/**
	 * 
	 * @param int $AccountID
	 * @param string $SentFolder
	 * @param string $DraftFolder
	 * @param string $DraftUid
	 * @param array $DraftInfo
	 * @param string $FetcherID
	 * @param string $IdentityID
	 * @return array
	 * @throws \System\Exceptions\ClientException
	 */
	public function SendMessage($AccountID, $SentFolder, $DraftFolder, $DraftUid, $DraftInfo, $FetcherID, $IdentityID)
	{
		$oAccount = $this->oApiAccountsManager->getAccountById($AccountID);

		$oFetcher = null;
		if (!empty($FetcherID) && is_numeric($FetcherID) && 0 < (int) $FetcherID)
		{
			$iFetcherID = (int) $FetcherID;

			$aFetchers = $this->oApiFetchersManager->getFetchers($oAccount);
			if (is_array($aFetchers) && 0 < count($aFetchers))
			{
				foreach ($aFetchers as /* @var $oFetcherItem \CFetcher */ $oFetcherItem)
				{
					if ($oFetcherItem && $iFetcherID === $oFetcherItem->IdFetcher && $oAccount->IdUser === $oFetcherItem->IdUser)
					{
						$oFetcher = $oFetcherItem;
						break;
					}
				}
			}
		}

		$oIdentity = null;
		$oApiUsers = CApi::GetCoreManager('users');
		if ($oApiUsers && !empty($IdentityID) && is_numeric($IdentityID) && 0 < (int) $IdentityID)
		{
			$oIdentity = $oApiUsers->getIdentity((int) $IdentityID);
		}

		$oMessage = $this->buildMessage($oAccount, $oFetcher, false, $oIdentity);
		if ($oMessage)
		{
			\CApi::Plugin()->RunHook('webmail.validate-message-for-send', array(&$oAccount, &$oMessage));

			try
			{
				\CApi::Plugin()->RunHook('webmail.build-message-for-send', array(&$oMessage));

				$mResult = $this->oApiMailManager->sendMessage($oAccount, $oMessage, $oFetcher, $SentFolder, $DraftFolder, $DraftUid);
			}
			catch (\CApiManagerException $oException)
			{
				$iCode = \System\Notifications::CanNotSendMessage;
				switch ($oException->getCode())
				{
					case \Errs::Mail_InvalidRecipients:
						$iCode = \System\Notifications::InvalidRecipients;
						break;
					case \Errs::Mail_CannotSendMessage:
						$iCode = \System\Notifications::CanNotSendMessage;
						break;
					case \Errs::Mail_CannotSaveMessageInSentItems:
						$iCode = \System\Notifications::CannotSaveMessageInSentItems;
						break;
					case \Errs::Mail_MailboxUnavailable:
						$iCode = \System\Notifications::MailboxUnavailable;
						break;
				}

				throw new \System\Exceptions\ClientException($iCode, $oException, $oException->GetPreviousMessage(), $oException->GetObjectParams());
			}

			if ($mResult)
			{
				\CApi::Plugin()->RunHook('webmail.message-success-send', array(&$oAccount, &$oMessage));

				$aCollection = $oMessage->GetRcpt();

				$aEmails = array();
				$aCollection->ForeachList(function ($oEmail) use (&$aEmails) {
					$aEmails[strtolower($oEmail->GetEmail())] = trim($oEmail->GetDisplayName());
				});

				if (is_array($aEmails))
				{
					\CApi::Plugin()->RunHook('webmail.message-suggest-email', array(&$oAccount, &$aEmails));

					\CApi::ExecuteMethod('Contacs::updateSuggestTable', array('Emails' => $aEmails));
				}
			}

			if (is_array($DraftInfo) && 3 === count($DraftInfo))
			{
				$sDraftInfoType = $DraftInfo[0];
				$sDraftInfoUid = $DraftInfo[1];
				$sDraftInfoFolder = $DraftInfo[2];

				try
				{
					switch (strtolower($sDraftInfoType))
					{
						case 'reply':
						case 'reply-all':
							$this->oApiMailManager->setMessageFlag($oAccount,
								$sDraftInfoFolder, array($sDraftInfoUid),
								\MailSo\Imap\Enumerations\MessageFlag::ANSWERED,
								\EMailMessageStoreAction::Add);
							break;
						case 'forward':
							$this->oApiMailManager->setMessageFlag($oAccount,
								$sDraftInfoFolder, array($sDraftInfoUid),
								'$Forwarded',
								\EMailMessageStoreAction::Add);
							break;
					}
				}
				catch (\Exception $oException) {}
			}
		}

		\CApi::LogEvent(\EEvents::MessageSend, $oAccount);
		return $mResult;
	}
	
	/**
	 * @param int $AccountID
	 * @param string $ConfirmFolder
	 * @param string $ConfirmUid
	 * @return array
	 * @throws \System\Exceptions\ClientException
	 */
	public function SendConfirmationMessage($AccountID, $ConfirmFolder, $ConfirmUid)
	{
		$oAccount = $this->oApiAccountsManager->getAccountById($AccountID);
		
		$oMessage = $this->buildConfirmationMessage($oAccount);
		if ($oMessage)
		{
			try
			{
				$mResult = $this->oApiMailManager->sendMessage($oAccount, $oMessage);
			}
			catch (\CApiManagerException $oException)
			{
				$iCode = \System\Notifications::CanNotSendMessage;
				switch ($oException->getCode())
				{
					case \Errs::Mail_InvalidRecipients:
						$iCode = \System\Notifications::InvalidRecipients;
						break;
					case \Errs::Mail_CannotSendMessage:
						$iCode = \System\Notifications::CanNotSendMessage;
						break;
				}

				throw new \System\Exceptions\ClientException($iCode, $oException);
			}

			if (0 < \strlen($ConfirmFolder) && 0 < \strlen($ConfirmUid))
			{
				try
				{
					$mResult = $this->oApiMailManager->setMessageFlag($oAccount, $ConfirmFolder, array($ConfirmUid), '$ReadConfirm', 
						\EMailMessageStoreAction::Add, false, true);
				}
				catch (\Exception $oException) {}
			}
		}

		return $mResult;
	}
	
	/**
	 * @param int $AccountID
	 * @param string $Sent
	 * @param string $Drafts
	 * @param string $Trash
	 * @param string $Spam
	 * @return array
	 */
	public function SetupSystemFolders($AccountID, $Sent, $Drafts, $Trash, $Spam)
	{
		$oAccount = $this->oApiAccountsManager->getAccountById($AccountID);
		
		$aData = array();
		if (0 < strlen(trim($Sent)))
		{
			$aData[$Sent] = \EFolderType::Sent;
		}
		if (0 < strlen(trim($Drafts)))
		{
			$aData[$Drafts] = \EFolderType::Drafts;
		}
		if (0 < strlen(trim($Trash)))
		{
			$aData[$Trash] = \EFolderType::Trash;
		}
		if (0 < strlen(trim($Spam)))
		{
			$aData[$Spam] = \EFolderType::Spam;
		}

		return $this->oApiMailManager->setSystemFolderNames($oAccount, $aData);
	}	
	
	/**
	 * @param int $AccountID
	 * @param string $FileName
	 * @param string $Html
	 * @return boolean
	 */
	public function GeneratePdfFile($AccountID, $FileName, $Html)
	{
		$oAccount = $this->oApiAccountsManager->getAccountById($AccountID);
		if ($oAccount)
		{
			$sFileName = $FileName.'.pdf';
			$sMimeType = 'application/pdf';

			$sSavedName = 'pdf-'.$oAccount->IdAccount.'-'.md5($sFileName.microtime(true)).'.pdf';
			
			include_once PSEVEN_APP_ROOT_PATH.'vendors/other/CssToInlineStyles.php';

			$oCssToInlineStyles = new \TijsVerkoyen\CssToInlineStyles\CssToInlineStyles($Html);
			$oCssToInlineStyles->setEncoding('utf-8');
			$oCssToInlineStyles->setUseInlineStylesBlock(true);

			$sExec = \CApi::DataPath().'/system/wkhtmltopdf/linux/wkhtmltopdf';
			if (!file_exists($sExec))
			{
				$sExec = \CApi::DataPath().'/system/wkhtmltopdf/win/wkhtmltopdf.exe';
				if (!file_exists($sExec))
				{
					$sExec = '';
				}
			}

			if (0 < strlen($sExec))
			{
				$oSnappy = new \Knp\Snappy\Pdf($sExec);
				$oSnappy->setOption('quiet', true);
				$oSnappy->setOption('disable-javascript', true);

				$oApiFileCache = \CApi::GetCoreManager('filecache');
				$oSnappy->generateFromHtml($oCssToInlineStyles->convert(),
					$oApiFileCache->generateFullFilePath($oAccount, $sSavedName), array(), true);

				return array(
					'Name' => $sFileName,
					'TempName' => $sSavedName,
					'MimeType' => $sMimeType,
					'Size' =>  (int) $oApiFileCache->fileSize($oAccount, $sSavedName),
					'Hash' => \CApi::EncodeKeyValues(array(
						'TempFile' => true,
						'AccountID' => $oAccount->IdAccount,
						'Name' => $sFileName,
						'TempName' => $sSavedName
					))
				);
			}
		}

		return false;
	}
	
	/**
	 * @param int $AccountID
	 * @param string $Email
	 * @return boolean
	 * @throws \System\Exceptions\ClientException
	 */
	public function SetEmailSafety($AccountID, $Email)
	{
		if (0 === strlen(trim($Email)))
		{
			throw new \System\Exceptions\ClientException(\System\Notifications::InvalidInputParameter);
		}

		$oAccount = $this->oApiAccountsManager->getAccountById($AccountID);

		$oApiUsers = \CApi::GetCoreManager('users');
		$oApiUsers->setSafetySender($oAccount->IdUser, $Email);

		return true;
	}	
	
//	public function GetFetchers()
//	{
//		$oAccount = $this->getParamValue('Account', null);
//		return $this->oApiFetchersManager->getFetchers($oAccount);
//	}
	
	/**
	 * 
	 * @param int $AccountID
	 * @return array | boolean
	 */
	public function GetIdentities($AccountID)
	{
		$mResult = false;
		$oAccount = $this->oApiAccountsManager->getAccountById($AccountID);
		if ($oAccount)
		{
			$oApiUsersManager = \CApi::GetCoreManager('users');
			$mResult = $oApiUsersManager->getUserIdentities($oAccount->IdUser);
		}
		
		return $mResult;
	}
}
