<?php

class MailModule extends AApiModule
{
	public $oApiMailManager = null;
	public $oApiFetchersManager = null;
	public $oApiSieveManager = null;
	
	public function init() 
	{
		$this->oApiMailManager = $this->GetManager('main', 'db');
		$this->oApiFetchersManager = $this->GetManager('fetchers', 'db');
	}
	
	/**
	 * @param \CAccount $oAccount
	 * @param \CFetcher $oFetcher = null
	 * @param bool $bWithDraftInfo = true
	 * @param \CIdentity $oIdentity = null
	 *
	 * @return \MailSo\Mime\Message
	 */
	private function buildMessage($oAccount, $oFetcher = null, $bWithDraftInfo = true, $oIdentity = null)
	{
		$sTo = $this->getParamValue('To', '');
		$sCc = $this->getParamValue('Cc', '');
		$sBcc = $this->getParamValue('Bcc', '');
		$sSubject = $this->getParamValue('Subject', '');
		$bTextIsHtml = '1' === $this->getParamValue('IsHtml', '0');
		$sText = $this->getParamValue('Text', '');
		$aAttachments = $this->getParamValue('Attachments', null);

		$aDraftInfo = $this->getParamValue('DraftInfo', null);
		$sInReplyTo = $this->getParamValue('InReplyTo', '');
		$sReferences = $this->getParamValue('References', '');

		$sImportance = $this->getParamValue('Importance', ''); // 1 3 5
		$sSensitivity = $this->getParamValue('Sensitivity', ''); // 0 1 2 3 4
		$bReadingConfirmation = '1' === $this->getParamValue('ReadingConfirmation', '0');

		$oMessage = \MailSo\Mime\Message::NewInstance();
		$oMessage->RegenerateMessageId();

		$sXMailer = \CApi::GetConf('webmail.xmailer-value', '');
		if (0 < strlen($sXMailer))
		{
			$oMessage->SetXMailer($sXMailer);
		}

		if ($oIdentity)
		{
			$oFrom = \MailSo\Mime\Email::NewInstance($oIdentity->Email, $oIdentity->FriendlyName);
		}
		else
		{
			$oFrom = $oFetcher
				? \MailSo\Mime\Email::NewInstance($oFetcher->Email, $oFetcher->Name)
				: \MailSo\Mime\Email::NewInstance($oAccount->Email, $oAccount->FriendlyName);
		}

		$oMessage
			->SetFrom($oFrom)
			->SetSubject($sSubject)
		;

		$oToEmails = \MailSo\Mime\EmailCollection::NewInstance($sTo);
		if ($oToEmails && $oToEmails->Count())
		{
			$oMessage->SetTo($oToEmails);
		}

		$oCcEmails = \MailSo\Mime\EmailCollection::NewInstance($sCc);
		if ($oCcEmails && $oCcEmails->Count())
		{
			$oMessage->SetCc($oCcEmails);
		}

		$oBccEmails = \MailSo\Mime\EmailCollection::NewInstance($sBcc);
		if ($oBccEmails && $oBccEmails->Count())
		{
			$oMessage->SetBcc($oBccEmails);
		}

		if ($bWithDraftInfo && is_array($aDraftInfo) && !empty($aDraftInfo[0]) && !empty($aDraftInfo[1]) && !empty($aDraftInfo[2]))
		{
			$oMessage->SetDraftInfo($aDraftInfo[0], $aDraftInfo[1], $aDraftInfo[2]);
		}

		if (0 < strlen($sInReplyTo))
		{
			$oMessage->SetInReplyTo($sInReplyTo);
		}

		if (0 < strlen($sReferences))
		{
			$oMessage->SetReferences($sReferences);
		}
		
		if (0 < strlen($sImportance) && in_array((int) $sImportance, array(
			\MailSo\Mime\Enumerations\MessagePriority::HIGH,
			\MailSo\Mime\Enumerations\MessagePriority::NORMAL,
			\MailSo\Mime\Enumerations\MessagePriority::LOW
		)))
		{
			$oMessage->SetPriority((int) $sImportance);
		}

		if (0 < strlen($sSensitivity) && in_array((int) $sSensitivity, array(
			\MailSo\Mime\Enumerations\Sensitivity::NOTHING,
			\MailSo\Mime\Enumerations\Sensitivity::CONFIDENTIAL,
			\MailSo\Mime\Enumerations\Sensitivity::PRIVATE_,
			\MailSo\Mime\Enumerations\Sensitivity::PERSONAL,
		)))
		{
			$oMessage->SetSensitivity((int) $sSensitivity);
		}

		if ($bReadingConfirmation)
		{
			$oMessage->SetReadConfirmation($oFetcher ? $oFetcher->Email : $oAccount->Email);
		}

		$aFoundCids = array();

		\CApi::Plugin()->RunHook('webmail.message-text-html-raw', array($oAccount, &$oMessage, &$sText, &$bTextIsHtml));

		if ($bTextIsHtml)
		{
			$sTextConverted = \MailSo\Base\HtmlUtils::ConvertHtmlToPlain($sText);
			\CApi::Plugin()->RunHook('webmail.message-plain-part', array($oAccount, &$oMessage, &$sTextConverted));
			$oMessage->AddText($sTextConverted, false);
		}

		$mFoundDataURL = array();
		$aFoundedContentLocationUrls = array();

		$sTextConverted = $bTextIsHtml ? 
			\MailSo\Base\HtmlUtils::BuildHtml($sText, $aFoundCids, $mFoundDataURL, $aFoundedContentLocationUrls) : $sText;
		
		\CApi::Plugin()->RunHook($bTextIsHtml ? 'webmail.message-html-part' : 'webmail.message-plain-part',
			array($oAccount, &$oMessage, &$sTextConverted));

		$oMessage->AddText($sTextConverted, $bTextIsHtml);

		if (is_array($aAttachments))
		{
			foreach ($aAttachments as $sTempName => $aData)
			{
				if (is_array($aData) && isset($aData[0], $aData[1], $aData[2], $aData[3]))
				{
					$sFileName = (string) $aData[0];
					$sCID = (string) $aData[1];
					$bIsInline = '1' === (string) $aData[2];
					$bIsLinked = '1' === (string) $aData[3];
					$sContentLocation = isset($aData[4]) ? (string) $aData[4] : '';

					$oApiFileCache = \CApi::GetCoreManager('filecache');
					$rResource = $oApiFileCache->getFile($oAccount, $sTempName);
					if (is_resource($rResource))
					{
						$iFileSize = $oApiFileCache->fileSize($oAccount, $sTempName);

						$sCID = trim(trim($sCID), '<>');
						$bIsFounded = 0 < strlen($sCID) ? in_array($sCID, $aFoundCids) : false;

						if (!$bIsLinked || $bIsFounded)
						{
							$oMessage->Attachments()->Add(
								\MailSo\Mime\Attachment::NewInstance($rResource, $sFileName, $iFileSize, $bIsInline,
									$bIsLinked, $bIsLinked ? '<'.$sCID.'>' : '', array(), $sContentLocation)
							);
						}
					}
				}
			}
		}

		if ($mFoundDataURL && \is_array($mFoundDataURL) && 0 < \count($mFoundDataURL))
		{
			foreach ($mFoundDataURL as $sCidHash => $sDataUrlString)
			{
				$aMatch = array();
				$sCID = '<'.$sCidHash.'>';
				if (\preg_match('/^data:(image\/[a-zA-Z0-9]+\+?[a-zA-Z0-9]+);base64,(.+)$/i', $sDataUrlString, $aMatch) &&
					!empty($aMatch[1]) && !empty($aMatch[2]))
				{
					$sRaw = \MailSo\Base\Utils::Base64Decode($aMatch[2]);
					$iFileSize = \strlen($sRaw);
					if (0 < $iFileSize)
					{
						$sFileName = \preg_replace('/[^a-z0-9]+/i', '.', \MailSo\Base\Utils::NormalizeContentType($aMatch[1]));
						
						// fix bug #68532 php < 5.5.21 or php < 5.6.5
						$sRaw = $this->FixBase64EncodeOmitsPaddingBytes($sRaw);
						
						$rResource = \MailSo\Base\ResourceRegistry::CreateMemoryResourceFromString($sRaw);

						$sRaw = '';
						unset($sRaw);
						unset($aMatch);

						$oMessage->Attachments()->Add(
							\MailSo\Mime\Attachment::NewInstance($rResource, $sFileName, $iFileSize, true, true, $sCID)
						);
					}
				}
			}
		}

		\CApi::Plugin()->RunHook('webmail.build-message', array(&$oMessage));

		return $oMessage;
	}	
	
	/**
	 * @param \CAccount $oAccount
	 *
	 * @return \MailSo\Mime\Message
	 *
	 * @throws \MailSo\Base\Exceptions\InvalidArgumentException
	 */
	private function buildConfirmationMessage($oAccount)
	{
		$sConfirmation = $this->getParamValue('Confirmation', '');
		$sSubject = $this->getParamValue('Subject', '');
		$sText = $this->getParamValue('Text', '');

		if (0 === strlen($sConfirmation) || 0 === strlen($sSubject) || 0 === strlen($sText))
		{
			throw new \MailSo\Base\Exceptions\InvalidArgumentException();
		}

		$oMessage = \MailSo\Mime\Message::NewInstance();
		$oMessage->RegenerateMessageId();

		$sXMailer = \CApi::GetConf('webmail.xmailer-value', '');
		if (0 < strlen($sXMailer))
		{
			$oMessage->SetXMailer($sXMailer);
		}

		$oTo = \MailSo\Mime\EmailCollection::Parse($sConfirmation);
		if (!$oTo || 0 === $oTo->Count())
		{
			throw new \MailSo\Base\Exceptions\InvalidArgumentException();
		}

		$sFrom = 0 < strlen($oAccount->FriendlyName) ? '"'.$oAccount->FriendlyName.'"' : '';
		if (0 < strlen($sFrom))
		{
			$sFrom .= ' <'.$oAccount->Email.'>';
		}
		else
		{
			$sFrom .= $oAccount->Email;
		}
		
		$oMessage
			->SetFrom(\MailSo\Mime\Email::NewInstance($sFrom))
			->SetTo($oTo)
			->SetSubject($sSubject)
		;

		$oMessage->AddText($sText, false);

		return $oMessage;
	}	
	
	/**
	 * @return array
	 */
	private function messageFlagSet($sFlagName, $sFunctionName)
	{
		$sFolderFullNameRaw = (string) $this->getParamValue('Folder', '');
		$bSetAction = '1' === (string) $this->getParamValue('SetAction', '0');
		$aUids = \api_Utils::ExplodeIntUids((string) $this->getParamValue('Uids', ''));

		if (0 === strlen(trim($sFolderFullNameRaw)) || !is_array($aUids) || 0 === count($aUids))
		{
			throw new \Core\Exceptions\ClientException(\Core\Notifications::InvalidInputParameter);
		}

		$oAccount = $this->getAccountFromParam();

		$this->oApiMailManager->setMessageFlag($oAccount, $sFolderFullNameRaw, $aUids, $sFlagName,
			$bSetAction ? \EMailMessageStoreAction::Add : \EMailMessageStoreAction::Remove);

		return $this->TrueResponse($oAccount, $sFunctionName);
	}	
	
	
	private function GetSieveManager()
	{
		if (null === $this->oApiSieveManager)
		{
			$this->oApiSieveManager = $this->GetManager('sieve');
		}
		
		return $this->oApiSieveManager;
	}
	
	public function GetExtensions()
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
	public function GetFolders()
	{
		$oAccount = $this->getAccountFromParam();
		$oFolderCollection = $this->oApiMailManager->getFolders($oAccount);
		return array(
			'Folders' => $oFolderCollection, 
			'Namespace' => $oFolderCollection->GetNamespace()
		);
	}	
	
	/**
	 * @return array
	 */
	public function GetRelevantFoldersInformation()
	{
		$aFolders = $this->getParamValue('Folders', '');
		$sInboxUidnext = $this->getParamValue('InboxUidnext', '');
		
		if (!is_array($aFolders) || 0 === count($aFolders))
		{
			throw new \ProjectCore\Exceptions\ClientException(\ProjectCore\Notifications::InvalidInputParameter);
		}

		$aResult = array();
		$oAccount = null;

		try
		{
			$oAccount = $this->getAccountFromParam();
			$oReturnInboxNewData = \Core\DataByRef::createInstance(array());
			$aResult = $this->oApiMailManager->getFolderListInformation($oAccount, $aFolders, $sInboxUidnext, $oReturnInboxNewData);
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
	 * @return array
	 */
	public function CreateFolder()
	{
		$sFolderNameInUtf8 = trim((string) $this->getParamValue('FolderNameInUtf8', ''));
		$sDelimiter = trim((string) $this->getParamValue('Delimiter', ''));
		$sFolderParentFullNameRaw = (string) $this->getParamValue('FolderParentFullNameRaw', '');

		if (0 === strlen($sFolderNameInUtf8) || 1 !== strlen($sDelimiter))
		{
			throw new \Core\Exceptions\ClientException(\Core\Notifications::InvalidInputParameter);
		}

		$oAccount = $this->getAccountFromParam();

		$this->oApiMailManager->createFolder($oAccount, $sFolderNameInUtf8, $sDelimiter, $sFolderParentFullNameRaw);

		if (!$oAccount->isExtensionEnabled(\CAccount::DisableFoldersManualSort))
		{
			$aFoldersOrderList = $this->oApiMailManager->getFoldersOrder($oAccount);
			if (is_array($aFoldersOrderList) && 0 < count($aFoldersOrderList))
			{
				$aFoldersOrderListNew = $aFoldersOrderList;

				$sFolderNameInUtf7Imap = \MailSo\Base\Utils::ConvertEncoding($sFolderNameInUtf8,
					\MailSo\Base\Enumerations\Charset::UTF_8,
					\MailSo\Base\Enumerations\Charset::UTF_7_IMAP);

				$sFolderFullNameRaw = (0 < strlen($sFolderParentFullNameRaw) ? $sFolderParentFullNameRaw.$sDelimiter : '').
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
	 * @return array
	 */
	public function RenameFolder()
	{
		$sPrevFolderFullNameRaw = (string) $this->getParamValue('PrevFolderFullNameRaw', '');
		$sNewFolderNameInUtf8 = trim($this->getParamValue('NewFolderNameInUtf8', ''));
		
		if (0 === strlen($sPrevFolderFullNameRaw) || 0 === strlen($sNewFolderNameInUtf8))
		{
			throw new \Core\Exceptions\ClientException(\Core\Notifications::InvalidInputParameter);
		}

		$oAccount = $this->getAccountFromParam();

		$mResult = $this->oApiMailManager->renameFolder($oAccount, $sPrevFolderFullNameRaw, $sNewFolderNameInUtf8);

		return (0 < strlen($mResult) ? array(
			'FullName' => $mResult,
			'FullNameHash' => md5($mResult)
		) : false);
	}

	/**
	 * @return array
	 */
	public function DeleteFolder()
	{
		$sFolderFullNameRaw = (string) $this->getParamValue('Folder', '');

		if (0 === strlen(trim($sFolderFullNameRaw)))
		{
			throw new \Core\Exceptions\ClientException(\Core\Notifications::InvalidInputParameter);
		}

		$oAccount = $this->getAccountFromParam();

		$this->oApiMailManager->deleteFolder($oAccount, $sFolderFullNameRaw);

		return true;
	}	
	
	/**
	 * @return array
	 */
	public function SubscribeFolder()
	{
		$sFolderFullNameRaw = (string) $this->getParamValue('Folder', '');
		$bSetAction = '1' === (string) $this->getParamValue('SetAction', '0');

		if (0 === strlen(trim($sFolderFullNameRaw)))
		{
			throw new \Core\Exceptions\ClientException(\Core\Notifications::InvalidInputParameter);
		}

		$oAccount = $this->getAccountFromParam();

		if (!$oAccount->isExtensionEnabled(\CAccount::DisableManageSubscribe))
		{
			$this->oApiMailManager->subscribeFolder($oAccount, $sFolderFullNameRaw, $bSetAction);
			return true;
		}

		return false;
	}	
	
	/**
	 * @return array
	 */
	public function UpdateFoldersOrder()
	{
		$aFolderList = $this->getParamValue('FolderList', null);
		if (!is_array($aFolderList))
		{
			throw new \Core\Exceptions\ClientException(\Core\Notifications::InvalidInputParameter);
		}

		$oAccount = $this->getAccountFromParam();
		if ($oAccount->isExtensionEnabled(\CAccount::DisableFoldersManualSort))
		{
			return false;
		}

		return $this->oApiMailManager->updateFoldersOrder($oAccount, $aFolderList);
	}	
	
	/**
	 * @return array
	 */
	public function ClearFolder()
	{
		$sFolderFullNameRaw = (string) $this->getParamValue('Folder', '');

		if (0 === strlen(trim($sFolderFullNameRaw)))
		{
			throw new \Core\Exceptions\ClientException(\Core\Notifications::InvalidInputParameter);
		}

		$oAccount = $this->getAccountFromParam();

		$this->oApiMailManager->clearFolder($oAccount, $sFolderFullNameRaw);

		return true;
	}	
	
	/**
	 * @return array
	 */
	public function GetMessages()
	{
		$sFolderFullNameRaw = (string) $this->getParamValue('Folder', '');
		$sOffset = trim((string) $this->getParamValue('Offset', ''));
		$sLimit = trim((string) $this->getParamValue('Limit', ''));
		$sSearch = trim((string) $this->getParamValue('Search', ''));
		$bUseThreads = '1' === (string) $this->getParamValue('UseThreads', '0');
		$sInboxUidnext = $this->getParamValue('InboxUidnext', '');
		
		$aFilters = array();
		$sFilters = strtolower(trim((string) $this->getParamValue('Filters', '')));
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

		$oAccount = $this->getAccountFromParam();

		return $this->oApiMailManager->getMessageList(
			$oAccount, $sFolderFullNameRaw, $iOffset, $iLimit, $sSearch, $bUseThreads, $aFilters, $sInboxUidnext);
	}	
	
	/**
	 * @return array
	 */
	public function GetMessagesByUids()
	{
		$sFolderFullNameRaw = (string) $this->getParamValue('Folder', '');
		$aUids = $this->getParamValue('Uids', array());

		if (0 === strlen(trim($sFolderFullNameRaw)) || !is_array($aUids))
		{
			throw new \Core\Exceptions\ClientException(\Core\Notifications::InvalidInputParameter);
		}

		$oAccount = $this->getAccountFromParam();

		return $this->oApiMailManager->getMessageListByUids($oAccount, $sFolderFullNameRaw, $aUids);
	}	
	
	/**
	 * @return array
	 */
	public function GetMessagesFlags()
	{
		$sFolderFullNameRaw = (string) $this->getParamValue('Folder', '');
		$aUids = $this->getParamValue('Uids', array());

		if (0 === strlen(trim($sFolderFullNameRaw)) || !is_array($aUids))
		{
			throw new \Core\Exceptions\ClientException(\Core\Notifications::InvalidInputParameter);
		}

		$oAccount = $this->getAccountFromParam();

		return $this->oApiMailManager->getMessagesFlags($oAccount, $sFolderFullNameRaw, $aUids);
	}	
	
	/**
	 * @return array
	 */
	public function MoveMessages()
	{
		$sFromFolderFullNameRaw = (string) $this->getParamValue('Folder', '');
		$sToFolderFullNameRaw = (string) $this->getParamValue('ToFolder', '');
		$aUids = \api_Utils::ExplodeIntUids((string) $this->getParamValue('Uids', ''));

		if (0 === strlen(trim($sFromFolderFullNameRaw)) || 0 === strlen(trim($sToFolderFullNameRaw)) || !is_array($aUids) || 0 === count($aUids))
		{
			throw new \Core\Exceptions\ClientException(\Core\Notifications::InvalidInputParameter);
		}

		$oAccount = $this->getAccountFromParam();

		try
		{
			$this->oApiMailManager->moveMessage(
				$oAccount, $sFromFolderFullNameRaw, $sToFolderFullNameRaw, $aUids);
		}
		catch (\MailSo\Imap\Exceptions\NegativeResponseException $oException)
		{
			$oResponse = /* @var $oResponse \MailSo\Imap\Response */ $oException->GetLastResponse();
			throw new \Core\Exceptions\ClientException(\Core\Notifications::CanNotMoveMessageQuota, $oException,
				$oResponse instanceof \MailSo\Imap\Response ? $oResponse->Tag.' '.$oResponse->StatusOrIndex.' '.$oResponse->HumanReadable : '');
		}
		catch (\Exception $oException)
		{
			throw new \Core\Exceptions\ClientException(\Core\Notifications::CanNotMoveMessage, $oException,
				$oException->getMessage());
		}

		return true;
	}

	public function CopyMessages()
	{
		$sFromFolderFullNameRaw = (string) $this->getParamValue('Folder', '');
		$sToFolderFullNameRaw = (string) $this->getParamValue('ToFolder', '');
		$aUids = \api_Utils::ExplodeIntUids((string) $this->getParamValue('Uids', ''));

		if (0 === strlen(trim($sFromFolderFullNameRaw)) || 0 === strlen(trim($sToFolderFullNameRaw)) || !is_array($aUids) || 0 === count($aUids))
		{
			throw new \Core\Exceptions\ClientException(\Core\Notifications::InvalidInputParameter);
		}

		$oAccount = $this->getAccountFromParam();

		try
		{
			$this->oApiMailManager->copyMessage(
				$oAccount, $sFromFolderFullNameRaw, $sToFolderFullNameRaw, $aUids);
		}
		catch (\MailSo\Imap\Exceptions\NegativeResponseException $oException)
		{
			$oResponse = /* @var $oResponse \MailSo\Imap\Response */ $oException->GetLastResponse();
			throw new \Core\Exceptions\ClientException(\Core\Notifications::CanNotCopyMessageQuota, $oException,
				$oResponse instanceof \MailSo\Imap\Response ? $oResponse->Tag.' '.$oResponse->StatusOrIndex.' '.$oResponse->HumanReadable : '');
		}
		catch (\Exception $oException)
		{
			throw new \Core\Exceptions\ClientException(\Core\Notifications::CanNotCopyMessage, $oException,
				$oException->getMessage());
		}

		return true;
	}

	/**
	 * @return array
	 */
	public function DeleteMessages()
	{
		$sFolderFullNameRaw = (string) $this->getParamValue('Folder', '');
		
		$aUids = \api_Utils::ExplodeIntUids((string) $this->getParamValue('Uids', ''));

		if (0 === strlen(trim($sFolderFullNameRaw)) || !is_array($aUids) || 0 === count($aUids))
		{
			throw new \Core\Exceptions\ClientException(\Core\Notifications::InvalidInputParameter);
		}

		$oAccount = $this->getAccountFromParam();

		$this->oApiMailManager->deleteMessage($oAccount, $sFolderFullNameRaw, $aUids);

		return true;
	}	
	
	/**
	 * @return array
	 */
	public function GetMessage()
	{
		$sFolderFullNameRaw = (string) $this->getParamValue('Folder', '');
		$sUid = trim((string) $this->getParamValue('Uid', ''));
		$sRfc822SubMimeIndex = trim((string) $this->getParamValue('Rfc822MimeIndex', ''));
		$iBodyTextLimit = 600000;
		
		$iUid = 0 < strlen($sUid) && is_numeric($sUid) ? (int) $sUid : 0;

		if (0 === strlen(trim($sFolderFullNameRaw)) || 0 >= $iUid)
		{
			throw new \Core\Exceptions\ClientException(\Core\Notifications::InvalidInputParameter);
		}

		$oAccount = $this->getAccountFromParam();

		if (0 === strlen($sFolderFullNameRaw) || !is_numeric($iUid) || 0 >= (int) $iUid)
		{
			throw new CApiInvalidArgumentException();
		}

		$oImapClient =& $this->oApiMailManager->_getImapClient($oAccount);

		$oImapClient->FolderExamine($sFolderFullNameRaw);

		$oMessage = false;

		$aTextMimeIndexes = array();
		$aAscPartsIds = array();

		$aFetchResponse = $oImapClient->Fetch(array(
			\MailSo\Imap\Enumerations\FetchType::BODYSTRUCTURE), $iUid, true);

		$oBodyStructure = (0 < count($aFetchResponse)) ? $aFetchResponse[0]->GetFetchBodyStructure($sRfc822SubMimeIndex) : null;
		
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
					
			\CApi::GetModuleManager()->broadcastEvent('GetBodyStructureParts', array($aParts, &$aCustomParts));
			
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
			0 < strlen($sRfc822SubMimeIndex)
				? \MailSo\Imap\Enumerations\FetchType::BODY_PEEK.'['.$sRfc822SubMimeIndex.'.HEADER]'
				: \MailSo\Imap\Enumerations\FetchType::BODY_HEADER_PEEK
		);

		if (0 < count($aTextMimeIndexes))
		{
			if (0 < strlen($sRfc822SubMimeIndex) && is_numeric($sRfc822SubMimeIndex))
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
			$oMessage = CApiMailMessage::createInstance($sFolderFullNameRaw, $aFetchResponse[0], $oBodyStructure, $sRfc822SubMimeIndex, $aAscPartsIds);
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
			
			\CApi::GetModuleManager()->broadcastEvent('ExtendMessageData', array($oAccount, &$oMessage, $aData));
		}

		if (!($oMessage instanceof \CApiMailMessage))
		{
			throw new \Core\Exceptions\ClientException(\Core\Notifications::CanNotGetMessage);
		}

		return $oMessage;
	}

	/**
	 * @return array
	 */
	public function GetMessagesBodies()
	{
		$sFolderFullNameRaw = (string) $this->getParamValue('Folder', '');
		$aUids = $this->getParamValue('Uids', null);

		if (0 === strlen(trim($sFolderFullNameRaw)) || !is_array($aUids) || 0 === count($aUids))
		{
			throw new \Core\Exceptions\ClientException(\Core\Notifications::InvalidInputParameter);
		}

		$aList = array();
		foreach ($aUids as $iUid)
		{
			if (is_numeric($iUid))
			{
				$this->setParamValue('Uid', $iUid);
				$oMessage = $this->GetMessage();
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
	 * @return array
	 */
	public function SaveMessage()
	{
		$mResult = false;

		$oAccount = $this->getAccountFromParam();

		$sDraftFolder = $this->getParamValue('DraftFolder', '');
		$sDraftUid = $this->getParamValue('DraftUid', '');

		$sFetcherID = $this->getParamValue('FetcherID', '');
		$sIdIdentity = $this->getParamValue('IdentityID', '');

		if (0 === strlen($sDraftFolder))
		{
			throw new \Core\Exceptions\ClientException(\Core\Notifications::InvalidInputParameter);
		}

		$oFetcher = null;
		if (!empty($sFetcherID) && is_numeric($sFetcherID) && 0 < (int) $sFetcherID)
		{
			$iFetcherID = (int) $sFetcherID;

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
		if (!empty($sIdIdentity) && is_numeric($sIdIdentity) && 0 < (int) $sIdIdentity)
		{
			$oApiUsers = \CApi::GetCoreManager('users');
			$oIdentity = $oApiUsers->getIdentity((int) $sIdIdentity);
		}

		$oMessage = $this->buildMessage($oAccount, $oFetcher, true, $oIdentity);
		if ($oMessage)
		{
			try
			{
				\CApi::Plugin()->RunHook('webmail.build-message-for-save', array(&$oMessage));
				
				$mResult = $this->oApiMailManager->saveMessage($oAccount, $oMessage, $sDraftFolder, $sDraftUid);
			}
			catch (\CApiManagerException $oException)
			{
				$iCode = \Core\Notifications::CanNotSaveMessage;
				throw new \Core\Exceptions\ClientException($iCode, $oException);
			}
		}

		return $mResult;
	}	
	
	/**
	 * @return array
	 */
	public function SendMessageObject()
	{
		$oAccount = $this->getParamValue('Account', null);
		$oMessage = $this->getParamValue('Message', null);
		
		return $this->oApiMailManager->sendMessage($oAccount, $oMessage);
	}
	
	/**
	 * @return array
	 */
	public function SendMessage()
	{
		$oAccount = $this->getAccountFromParam();

		$sSentFolder = $this->getParamValue('SentFolder', '');
		$sDraftFolder = $this->getParamValue('DraftFolder', '');
		$sDraftUid = $this->getParamValue('DraftUid', '');
		$aDraftInfo = $this->getParamValue('DraftInfo', null);
		
		$sFetcherID = $this->getParamValue('FetcherID', '');
		$sIdIdentity = $this->getParamValue('IdentityID', '');

		$oFetcher = null;
		if (!empty($sFetcherID) && is_numeric($sFetcherID) && 0 < (int) $sFetcherID)
		{
			$iFetcherID = (int) $sFetcherID;

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
		if ($oApiUsers && !empty($sIdIdentity) && is_numeric($sIdIdentity) && 0 < (int) $sIdIdentity)
		{
			$oIdentity = $oApiUsers->getIdentity((int) $sIdIdentity);
		}

		$oMessage = $this->buildMessage($oAccount, $oFetcher, false, $oIdentity);
		if ($oMessage)
		{
			\CApi::Plugin()->RunHook('webmail.validate-message-for-send', array(&$oAccount, &$oMessage));

			try
			{
				\CApi::Plugin()->RunHook('webmail.build-message-for-send', array(&$oMessage));

				$mResult = $this->oApiMailManager->sendMessage($oAccount, $oMessage, $oFetcher, $sSentFolder, $sDraftFolder, $sDraftUid);
			}
			catch (\CApiManagerException $oException)
			{
				$iCode = \Core\Notifications::CanNotSendMessage;
				switch ($oException->getCode())
				{
					case \Errs::Mail_InvalidRecipients:
						$iCode = \Core\Notifications::InvalidRecipients;
						break;
					case \Errs::Mail_CannotSendMessage:
						$iCode = \Core\Notifications::CanNotSendMessage;
						break;
					case \Errs::Mail_CannotSaveMessageInSentItems:
						$iCode = \Core\Notifications::CannotSaveMessageInSentItems;
						break;
					case \Errs::Mail_MailboxUnavailable:
						$iCode = \Core\Notifications::MailboxUnavailable;
						break;
				}

				throw new \Core\Exceptions\ClientException($iCode, $oException, $oException->GetPreviousMessage(), $oException->GetObjectParams());
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

			if (is_array($aDraftInfo) && 3 === count($aDraftInfo))
			{
				$sDraftInfoType = $aDraftInfo[0];
				$sDraftInfoUid = $aDraftInfo[1];
				$sDraftInfoFolder = $aDraftInfo[2];

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
	 * @return array
	 */
	public function SendConfirmationMessage()
	{
		$oAccount = $this->getAccountFromParam();
		
		$oMessage = $this->buildConfirmationMessage($oAccount);
		if ($oMessage)
		{
			try
			{
				$mResult = $this->oApiMailManager->sendMessage($oAccount, $oMessage);
			}
			catch (\CApiManagerException $oException)
			{
				$iCode = \Core\Notifications::CanNotSendMessage;
				switch ($oException->getCode())
				{
					case \Errs::Mail_InvalidRecipients:
						$iCode = \Core\Notifications::InvalidRecipients;
						break;
					case \Errs::Mail_CannotSendMessage:
						$iCode = \Core\Notifications::CanNotSendMessage;
						break;
				}

				throw new \Core\Exceptions\ClientException($iCode, $oException);
			}

			$sConfirmFolderFullNameRaw = $this->getParamValue('ConfirmFolder', '');
			$sConfirmUid = $this->getParamValue('ConfirmUid', '');

			if (0 < \strlen($sConfirmFolderFullNameRaw) && 0 < \strlen($sConfirmUid))
			{
				try
				{
					$mResult = $this->oApiMailManager->setMessageFlag($oAccount, $sConfirmFolderFullNameRaw, array($sConfirmUid), '$ReadConfirm', 
						\EMailMessageStoreAction::Add, false, true);
				}
				catch (\Exception $oException) {}
			}
		}

		return $mResult;
	}	
	
	/**
	 * @return array
	 */
	public function SetAllMessagesSeen()
	{
		$sFolderFullNameRaw = (string) $this->getParamValue('Folder', '');
		$bSetAction = '1' === (string) $this->getParamValue('SetAction', '0');

		if (0 === strlen(trim($sFolderFullNameRaw)))
		{
			throw new \Core\Exceptions\ClientException(\Core\Notifications::InvalidInputParameter);
		}

		$oAccount = $this->getAccountFromParam();

		$this->oApiMailManager->setMessageFlag($oAccount, $sFolderFullNameRaw, array('1'),
			\MailSo\Imap\Enumerations\MessageFlag::SEEN,
			$bSetAction ? \EMailMessageStoreAction::Add : \EMailMessageStoreAction::Remove, true);

		return true;
	}	
	
	/**
	 * @return array
	 */
	public function SetMessageFlagged()
	{
		return $this->messageFlagSet(\MailSo\Imap\Enumerations\MessageFlag::FLAGGED, 'SetMessageFlagged');
	}

	/**
	 * @return array
	 */
	public function SetMessagesSeen()
	{
		return $this->messageFlagSet(\MailSo\Imap\Enumerations\MessageFlag::SEEN, 'SetMessageSeen');
	}	
	
	/**
	 * @return array
	 */
	public function SetupSystemFolders()
	{
		$oAccount = $this->getAccountFromParam();
		
		$sSent = (string) $this->getParamValue('Sent', '');
		$sDrafts = (string) $this->getParamValue('Drafts', '');
		$sTrash = (string) $this->getParamValue('Trash', '');
		$sSpam = (string) $this->getParamValue('Spam', '');

		$aData = array();
		if (0 < strlen(trim($sSent)))
		{
			$aData[$sSent] = \EFolderType::Sent;
		}
		if (0 < strlen(trim($sDrafts)))
		{
			$aData[$sDrafts] = \EFolderType::Drafts;
		}
		if (0 < strlen(trim($sTrash)))
		{
			$aData[$sTrash] = \EFolderType::Trash;
		}
		if (0 < strlen(trim($sSpam)))
		{
			$aData[$sSpam] = \EFolderType::Spam;
		}

		return $this->oApiMailManager->setSystemFolderNames($oAccount, $aData);
	}	
	
	/**
	 * @return bool
	 */
	public function GeneratePdfFile()
	{
		$oAccount = $this->getAccountFromParam();
		if ($oAccount)
		{
			$sSubject = (string) $this->getParamValue('FileName', '');
			$sHtml = (string) $this->getParamValue('Html', '');

			$sFileName = $sSubject.'.pdf';
			$sMimeType = 'application/pdf';

			$sSavedName = 'pdf-'.$oAccount->IdAccount.'-'.md5($sFileName.microtime(true)).'.pdf';
			
			include_once PSEVEN_APP_ROOT_PATH.'vendors/other/CssToInlineStyles.php';

			$oCssToInlineStyles = new \TijsVerkoyen\CssToInlineStyles\CssToInlineStyles($sHtml);
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
	 * @return array
	 */
	public function SetEmailSafety()
	{
		$sEmail = (string) $this->getParamValue('Email', '');
		if (0 === strlen(trim($sEmail)))
		{
			throw new \Core\Exceptions\ClientException(\Core\Notifications::InvalidInputParameter);
		}

		$oAccount = $this->getAccountFromParam();

		$oApiUsers = \CApi::GetCoreManager('users');
		$oApiUsers->setSafetySender($oAccount->IdUser, $sEmail);

		return true;
	}	
	
	public function GetFetchers()
	{
		$oAccount = $this->getParamValue('Account', null);
		return $this->oApiFetchersManager->getFetchers($oAccount);
	}
	
	public function GetIdentities()
	{
		$mResult = false;
		$oAccount = $this->getDefaultAccountFromParam();
		if ($oAccount)
		{
			$oApiUsersManager = \CApi::GetCoreManager('users');
			$mResult = $oApiUsersManager->getUserIdentities($oAccount->IdUser);
		}
		
		return $mResult;
	}
	
	public function GetQuota()
	{
		return $this->oApiMailManager->getQuota($this->getAccountFromParam());
	}
	
	public function ValidateAccountConnection()
	{
		return $this->oApiMailManager->ValidateAccountConnection(
				$this->getParamValue('Account')
		);
	}
	
	public function UploadMessage()
	{
		$aFileData = $this->getParamValue('FileData', null);
		$sAccountId = (int) $this->getParamValue('AccountID', '0');
		$sAdditionalData = $this->getParamValue('AdditionalData', '{}');
		$aAdditionalData = @json_decode($sAdditionalData, true);

		$oAccount = $sAccountId ? $this->getAccount($sAccountId) : $this->getDefaultAccountFromParam();

		$sError = '';
		$aResponse = array();

		if ($oAccount) {
			
			if (is_array($aFileData)) {
				
				$sUploadName = $aFileData['name'];
				$bIsEmlExtension  = strtolower(pathinfo($sUploadName, PATHINFO_EXTENSION)) === 'eml';

				if ($bIsEmlExtension) {
					$sFolder = isset($aAdditionalData['Folder']) ? $aAdditionalData['Folder'] : '';
					$sMimeType = \MailSo\Base\Utils::MimeContentType($sUploadName);

					$oApiFileCacheManager = \CApi::GetCoreManager('filecache');
					$sSavedName = 'upload-post-' . md5($aFileData['name'] . $aFileData['tmp_name']);
					if ($oApiFileCacheManager->moveUploadedFile($oAccount, $sSavedName, $aFileData['tmp_name'])) {
						$sSavedFullName = $oApiFileCacheManager->generateFullFilePath($oAccount, $sSavedName);
						$this->oApiMailManager->appendMessageFromFile($oAccount, $sSavedFullName, $sFolder);

						//$aResponse['File'] = $bIsMessage;
					} else {
						$sError = 'unknown';
					}
				}
				else
				{
					throw new \Core\Exceptions\ClientException(\Core\Notifications::IncorrectFileExtension);
				}
			}
		}
		else
		{
			$sError = 'auth';
		}

		if (0 < strlen($sError))
		{
			$aResponse['Error'] = $sError;
		}

		return $aResponse;
	}	
	
	/**
	 * @return array
	 */
	public function UploadAttachment()
	{
		$oAccount = $this->getAccountFromParam();

		$oSettings =& \CApi::GetSettings();
		$aFileData = $this->getParamValue('FileData', null);

		$iSizeLimit = !!$oSettings->GetConf('WebMail/EnableAttachmentSizeLimit', false) ?
			(int) $oSettings->GetConf('WebMail/AttachmentSizeLimit', 0) : 0;

		$sError = '';
		$aResponse = array();

		if ($oAccount)
		{
			if (is_array($aFileData))
			{
				if (0 < $iSizeLimit && $iSizeLimit < (int) $aFileData['size'])
				{
					$sError = 'size';
				}
				else
				{
					$oApiFileCacheManager = \CApi::GetCoreManager('filecache');

					$sSavedName = 'upload-post-'.md5($aFileData['name'].$aFileData['tmp_name']);
					if ($oApiFileCacheManager->moveUploadedFile($oAccount, $sSavedName, $aFileData['tmp_name']))
					{
						$sUploadName = $aFileData['name'];
						$iSize = $aFileData['size'];
						$sMimeType = \MailSo\Base\Utils::MimeContentType($sUploadName);

						$bIframed = \CApi::isIframedMimeTypeSupported($sMimeType, $sUploadName);
						$aResponse['Attachment'] = array(
							'Name' => $sUploadName,
							'TempName' => $sSavedName,
							'MimeType' => $sMimeType,
							'Size' =>  (int) $iSize,
							'Iframed' => $bIframed,
							'Hash' => \CApi::EncodeKeyValues(array(
								'TempFile' => true,
								'AccountID' => $oAccount->IdAccount,
								'Iframed' => $bIframed,
								'Name' => $sUploadName,
								'TempName' => $sSavedName
							))
						);
					}
					else
					{
						$sError = 'unknown';
					}
				}
			}
			else
			{
				$sError = 'unknown';
			}
		}
		else
		{
			$sError = 'auth';
		}

		if (0 < strlen($sError))
		{
			$aResponse['Error'] = $sError;
		}

		return $aResponse;
	}	
	
	public function GetAutoresponder()
	{
		$mResult = false;
		$oAccount = $this->getAccountFromParam();
		
		if ($oAccount && $oAccount->isExtensionEnabled(\CAccount::AutoresponderExtension)) {
			$aAutoResponderValue = $this->GetSieveManager()->getAutoresponder($oAccount);
			if (isset($aAutoResponderValue['subject'], 
					$aAutoResponderValue['body'], $aAutoResponderValue['enabled'])) {
				
				$mResult = array(
					'Enable' => (bool) $aAutoResponderValue['enabled'],
					'Subject' => (string) $aAutoResponderValue['subject'],
					'Message' => (string) $aAutoResponderValue['body']
				);
			}
		}

		return $mResult;
	}
	

	/**
	 * @return array
	 */
	public function UpdateAutoresponder()
	{
		$bIsDemo = false;
		$mResult = false;
		$oAccount = $this->getAccountFromParam();
		if ($oAccount && $oAccount->isExtensionEnabled(\CAccount::AutoresponderExtension))
		{
			\CApi::Plugin()->RunHook('plugin-is-demo-account', array(&$oAccount, &$bIsDemo));
			if (!$bIsDemo) {
				
				$bIsEnabled = '1' === $this->getParamValue('Enable', '0');
				$sSubject = (string) $this->getParamValue('Subject', '');
				$sMessage = (string) $this->getParamValue('Message', '');

				$mResult = $this->GetSieveManager()->setAutoresponder($oAccount, $sSubject, $sMessage, $bIsEnabled);
			} else {
				throw new \Core\Exceptions\ClientException(\Core\Notifications::DemoAccount);
			}
		}

		return $mResult;
	}	
	
	/**
	 * @return array
	 */
	public function GetForward()
	{
		$mResult = false;
		$oAccount = $this->getAccountFromParam();
		
		if ($oAccount && $oAccount->isExtensionEnabled(\CAccount::ForwardExtension)) {
			
			$aForwardValue = /* @var $aForwardValue array */  $this->GetSieveManager()->getForward($oAccount);
			if (isset($aForwardValue['email'], $aForwardValue['enabled'])) {
				
				$mResult = array(
					'Enable' => (bool) $aForwardValue['enabled'],
					'Email' => (string) $aForwardValue['email']
				);
			}
		}

		return $mResult;
	}	
	
	/**
	 * @return array
	 */
	public function UpdateForward()
	{
		$mResult = false;
		$bIsDemo = false;
		$oAccount = $this->getAccountFromParam();

		if ($oAccount && $oAccount->isExtensionEnabled(\CAccount::ForwardExtension)) {
			\CApi::Plugin()->RunHook('plugin-is-demo-account', array(&$oAccount, &$bIsDemo));
			if (!$bIsDemo) {
				
				$bIsEnabled = '1' === $this->getParamValue('Enable', '0');
				$sForwardEmail = (string) $this->getParamValue('Email', '');
		
				$mResult = $this->GetSieveManager()->setForward($oAccount, $sForwardEmail, $bIsEnabled);
			} else {
				
				throw new \Core\Exceptions\ClientException(\Core\Notifications::DemoAccount);
			}
		}

		return $mResult;
	}	
	

	/**
	 * @return array
	 */
	public function GetSieveFilters()
	{
		$mResult = false;
		$oAccount = $this->getAccountFromParam();

		if ($oAccount && $oAccount->isExtensionEnabled(\CAccount::SieveFiltersExtension)) {
			$mResult = $this->GetSieveManager()->getSieveFilters($oAccount);
		}

		return $mResult;
	}	
	
	/**
	 * @return array
	 */
	public function UpdateSieveFilters()
	{
		$mResult = false;
		$oAccount = $this->getAccountFromParam();

		if ($oAccount && $oAccount->isExtensionEnabled(\CAccount::SieveFiltersExtension)) {
			
			$aFilters = $this->getParamValue('Filters', array());
			$aFilters = is_array($aFilters) ? $aFilters : array();

			$mResult = array();
			foreach ($aFilters as $aItem) {
				
				$oFilter = new \CFilter($oAccount);
				$oFilter->Enable = '1' === (string) (isset($aItem['Enable']) ? $aItem['Enable'] : '1');
				$oFilter->Field = (int) (isset($aItem['Field']) ? $aItem['Field'] : \EFilterFiels::From);
				$oFilter->Filter = (string) (isset($aItem['Filter']) ? $aItem['Filter'] : '');
				$oFilter->Condition = (int) (isset($aItem['Condition']) ? $aItem['Condition'] : \EFilterCondition::ContainSubstring);
				$oFilter->Action = (int) (isset($aItem['Action']) ? $aItem['Action'] : \EFilterAction::DoNothing);
				$oFilter->FolderFullName = (string) (isset($aItem['FolderFullName']) ? $aItem['FolderFullName'] : '');

				$mResult[] = $oFilter;
			}

			$mResult = $this->GetSieveManager()->updateSieveFilters($oAccount, $mResult);
		}

		return $mResult;
	}	
	
	
	/**
	 * @return bool
	 */
	private function rawCallback($fCallback, $bCache = true, &$oAccount = null, &$oHelpdeskUser = null)
	{
		$sFolder = '';
		$iUid = 0;
		$sMimeIndex = '';

		$oAccount = null;
		$oHelpdeskUser = null;
		$oHelpdeskUserFromAttachment = null;

		
		$mAccountID = $this->getParamValue('AccountID');
		
		$mHelpdeskUserID = $this->getParamValue('HelpdeskUserID');
		$mHelpdeskTenantID = $this->getParamValue('HelpdeskTenantID');
		
		if (isset($mHelpdeskUserID, $mHelpdeskTenantID))
		{
			$oAccount = null;
			$oHelpdeskUser = $this->getHelpdeskAccountFromParam($oAccount);

			if ($oHelpdeskUser && $oHelpdeskUser->IdTenant === $mHelpdeskTenantID)
			{
				$oApiHelpdesk = $this->ApiHelpdesk();
				if ($oApiHelpdesk)
				{
					if ($oHelpdeskUser->IdHelpdeskUser === $mHelpdeskUserID)
					{
						$oHelpdeskUserFromAttachment = $oHelpdeskUser;
					}
					else if ($oHelpdeskUser->IsAgent)
					{
						$oHelpdeskUserFromAttachment = $oApiHelpdesk->getUserById($mHelpdeskTenantID, $mHelpdeskUserID);
					}
				}
			}
		}
		else if (isset($mAccountID))
		{
			$mIframed = $this->getParamValue('Iframed');
			$mTime = $this->getParamValue('Time');
			
			$oAccount = $this->getAccountFromParam(true,
				!(isset($mIframed, $mTime) && $mIframed && $mTime > \Core\Base\Utils::iframedTimestamp())
			);
			
			if (!$oAccount || $mAccountID !== $oAccount->IdAccount)
			{
				return false;
			}
		}

		$mFilestorageFile = $this->getParamValue('FilestorageFile');
		$mStorageType = $this->getParamValue('StorageType');
		$mPath = $this->getParamValue('Path');
		$mName = $this->getParamValue('Name');
		
		$mTempFile = $this->getParamValue('TempFile');
		$mTempName = $this->getParamValue('TempName');

		if ($oHelpdeskUserFromAttachment && isset($mFilestorageFile, $mStorageType, $mPath, $mName))
		{
			if ($bCache)
			{
//				$this->verifyCacheByKey($sRawKey); todo
			}
			
			$bResult = false;
			$mResult = false;
			
			if (is_numeric($mStorageType))
			{
				$iStorageType = (int) $mStorageType;
				switch ($iStorageType)
				{
					case \EFileStorageType::Personal: 
						$sStorageType = \EFileStorageTypeStr::Personal;
						break;
					case \EFileStorageType::Corporate: 
						$sStorageType = \EFileStorageTypeStr::Corporate;
						break;
					case \EFileStorageType::Shared: 
						$sStorageType = \EFileStorageTypeStr::Shared;
						break;
				}
			}
					
			if ($this->oApiFilestorage->isFileExists(
				$oHelpdeskUserFromAttachment,
				$sStorageType, $mPath, $mName
			))
			{
				$mResult = $this->oApiFilestorage->getFile(
					$oHelpdeskUserFromAttachment,
					$sStorageType, $mPath, $mName
				);
				if (is_resource($mResult))
				{
					if ($bCache)
					{
						// $this->cacheByKey($sRawKey); todo
					}

					$bResult = true;
					$sFileName = $mName;

					$sContentType = (empty($sFileName)) ? 'text/plain' : \MailSo\Base\Utils::MimeContentType($sFileName);
//					$sFileName = $this->clearFileName($sFileName, $sContentType);

					call_user_func_array($fCallback, array(
						$oAccount, $sContentType, $sFileName, $mResult, $oHelpdeskUser
					));
				}
			}
			else
			{
				$this->oHttp->StatusHeader(404);
				exit();
			}
			return $bResult;
		}
		else  if (isset($mTempFile, $mTempName, $mName) && ($oHelpdeskUserFromAttachment || $oAccount))
		{
			if ($bCache)
			{
//				$this->verifyCacheByKey($sRawKey); todo
			}

			$bResult = false;
			$mResult = $this->ApiFileCache()->getFile($oHelpdeskUserFromAttachment ? $oHelpdeskUserFromAttachment : $oAccount, $mTempName);

			if (is_resource($mResult))
			{
				if ($bCache)
				{
//					$this->cacheByKey($sRawKey); todo
				}

				$bResult = true;
				$sFileName = $mName;
				$sContentType = (empty($sFileName)) ? 'text/plain' : \MailSo\Base\Utils::MimeContentType($sFileName);
//				$sFileName = $this->clearFileName($sFileName, $sContentType);

				call_user_func_array($fCallback, array(
					$oAccount, $sContentType, $sFileName, $mResult, $oHelpdeskUser
				));
			}

			return $bResult;
		}
		else
		{
			$this->getParamValue('Folder', '');

			$sFolder = $this->getParamValue('Folder', '');
			$iUid = (int) $this->getParamValue('Uid', 0);
			$sMimeIndex = (string) $this->getParamValue('MimeIndex', '');
		}

		if ($bCache && 0 < strlen($sFolder) && 0 < $iUid)
		{
//			$this->verifyCacheByKey($sRawKey); todo
		}

		$sContentTypeIn = (string) $this->getParamValue('MimeType', '');
		$sFileNameIn = (string) $this->getParamValue('FileName', '');

		if (!$oAccount)
		{
			return false;
		}

		$self = $this;
		$oModuleManager = \CApi::GetModuleManager();
		$oMailModule = $oModuleManager->GetModule('Mail');
		$oMailManager = false;
		if ($oMailModule)
		{
			$oMailManager = $oMailModule->GetManager('main');
		}
		if (!$oMailManager)
		{
			return false;
		}
		return $oMailManager->directMessageToStream($oAccount,
			function($rResource, $sContentType, $sFileName, $sMimeIndex = '') use ($self, $oAccount, $fCallback, $bCache, $sContentTypeIn, $sFileNameIn) {
				if (is_resource($rResource)) {
					
					$sContentTypeOut = $sContentTypeIn;
					if (empty($sContentTypeOut)) {
						
						$sContentTypeOut = $sContentType;
						if (empty($sContentTypeOut)) {
							
							$sContentTypeOut = (empty($sFileName)) ? 'text/plain' : \MailSo\Base\Utils::MimeContentType($sFileName);
						}
					}

					$sFileNameOut = $sFileNameIn;
					if (empty($sFileNameOut) || '.' === $sFileNameOut{0}) {
						
						$sFileNameOut = $sFileName;
					}

//					$sFileNameOut = $self->clearFileName($sFileNameOut, $sContentType, $sMimeIndex);

					if ($bCache) {
						
//						$self->cacheByKey($sRawKey); todo
					}

					call_user_func_array($fCallback, array(
						$oAccount, $sContentTypeOut, $sFileNameOut, $rResource
					));
				}
			}, $sFolder, $iUid, $sMimeIndex);
	}	
	
	private function raw($bDownload = true, $bThumbnail = false)
	{
		$self = $this;
		return $this->rawCallback(
				function ($oAccount, $sContentType, $sFileName, $rResource, $oHelpdeskUser = null) use ($self, $bDownload, $bThumbnail) {
			
			\CApiResponseManager::OutputHeaders($bDownload, $sContentType, $sFileName);

			if (!$bDownload && 'text/html' === $sContentType) {
				
				$sHtml = stream_get_contents($rResource);
				if ($sHtml) {
					
					$sCharset = '';
					$aMacth = array();
					if (preg_match('/charset[\s]?=[\s]?([^\s"\']+)/i', $sHtml, $aMacth) && !empty($aMacth[1])) {
						
						$sCharset = $aMacth[1];
					}

					if ('' !== $sCharset && \MailSo\Base\Enumerations\Charset::UTF_8 !== $sCharset) {
						
						$sHtml = \MailSo\Base\Utils::ConvertEncoding($sHtml,
							\MailSo\Base\Utils::NormalizeCharset($sCharset, true), \MailSo\Base\Enumerations\Charset::UTF_8);
					}

					$oCssToInlineStyles = new \TijsVerkoyen\CssToInlineStyles\CssToInlineStyles($sHtml);
					$oCssToInlineStyles->setEncoding('utf-8');
					$oCssToInlineStyles->setUseInlineStylesBlock(true);

					echo '<html><head></head><body>'.
						\MailSo\Base\HtmlUtils::ClearHtmlSimple($oCssToInlineStyles->convert(), true, true).
						'</body></html>';
				}
			} else {
				
				if ($bThumbnail && !$bDownload) {
					
					\CApiResponseManager::GetThumbResource($oAccount ? $oAccount : $oHelpdeskUser, $rResource, $sFileName);
				} else {
					
					\MailSo\Base\Utils::FpassthruWithTimeLimitReset($rResource);
				}
			}
			
		}, !$bDownload);
	}
	
	public function DownloadFile()
	{
		$this->raw(true);
	}
	
	public function ViewFile()
	{
		$this->raw(false);
	}
}

return new MailModule('1.0');
