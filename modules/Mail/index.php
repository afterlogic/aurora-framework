<?php

class MailModule extends AApiModule
{
	public $oApiMailManager = null;
	
	public function init() 
	{
		$this->oApiMailManager = $this->GetManager('main', 'db');
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
		$aUids = \Core\Base\Utils::ExplodeIntUids((string) $this->getParamValue('Uids', ''));

		if (0 === strlen(trim($sFolderFullNameRaw)) || !is_array($aUids) || 0 === count($aUids))
		{
			throw new \Core\Exceptions\ClientException(\Core\Notifications::InvalidInputParameter);
		}

		$oAccount = $this->getAccountFromParam();

		$this->oApiMailManager->setMessageFlag($oAccount, $sFolderFullNameRaw, $aUids, $sFlagName,
			$bSetAction ? \EMailMessageStoreAction::Add : \EMailMessageStoreAction::Remove);

		return $this->TrueResponse($oAccount, $sFunctionName);
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

		return $this->DefaultResponse(null, __FUNCTION__, $mResult);
	}
	
	/**
	 * @return array
	 */
	public function GetFolders()
	{
		$oAccount = $this->getAccountFromParam();
		$oFolderCollection = $this->oApiMailManager->getFolders($oAccount);
		$aResponse = $this->DefaultResponse($oAccount, __FUNCTION__, $oFolderCollection);
		$aResponse['Result']['Namespace'] = $oFolderCollection->GetNamespace();
		return $aResponse;
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
			$oReturnInboxNewData = \Core\Base\DataByRef::createInstance(array());
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

		return $this->DefaultResponse($oAccount, __FUNCTION__, array(
			'Counts' => $aResult,
			'New' => $oReturnInboxNewData->GetData()
		));
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

		return $this->TrueResponse($oAccount, __FUNCTION__);
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

		return $this->DefaultResponse($oAccount, __FUNCTION__, 0 < strlen($mResult) ? array(
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

		return $this->TrueResponse($oAccount, __FUNCTION__);
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
			return $this->TrueResponse($oAccount, __FUNCTION__);
		}

		return $this->FalseResponse($oAccount, __FUNCTION__);
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
			return $this->FalseResponse($oAccount, __FUNCTION__);
		}

		return $this->DefaultResponse($oAccount, __FUNCTION__,
			$this->oApiMailManager->updateFoldersOrder($oAccount, $aFolderList));
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

		return $this->TrueResponse($oAccount, __FUNCTION__);
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

		$oMessageList = $this->oApiMailManager->getMessageList(
			$oAccount, $sFolderFullNameRaw, $iOffset, $iLimit, $sSearch, $bUseThreads, $aFilters, $sInboxUidnext);

		return $this->DefaultResponse($oAccount, __FUNCTION__, $oMessageList);
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

		$oMessageList = $this->oApiMailManager->getMessageListByUids($oAccount, $sFolderFullNameRaw, $aUids);

		return $this->DefaultResponse($oAccount, __FUNCTION__, $oMessageList);
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

		$aMessageFlags = $this->oApiMailManager->getMessagesFlags($oAccount, $sFolderFullNameRaw, $aUids);

		return $this->DefaultResponse($oAccount, __FUNCTION__, $aMessageFlags);
	}	
	
	/**
	 * @return array
	 */
	public function MoveMessages()
	{
		$sFromFolderFullNameRaw = (string) $this->getParamValue('Folder', '');
		$sToFolderFullNameRaw = (string) $this->getParamValue('ToFolder', '');
		$aUids = \Core\Base\Utils::ExplodeIntUids((string) $this->getParamValue('Uids', ''));

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

		return $this->TrueResponse($oAccount, __FUNCTION__);
	}

	public function CopyMessages()
	{
		$sFromFolderFullNameRaw = (string) $this->getParamValue('Folder', '');
		$sToFolderFullNameRaw = (string) $this->getParamValue('ToFolder', '');
		$aUids = \Core\Base\Utils::ExplodeIntUids((string) $this->getParamValue('Uids', ''));

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

		return $this->TrueResponse($oAccount, __FUNCTION__);
	}

	/**
	 * @return array
	 */
	public function DeleteMessages()
	{
		$sFolderFullNameRaw = (string) $this->getParamValue('Folder', '');
		
		$aUids = \Core\Base\Utils::ExplodeIntUids((string) $this->getParamValue('Uids', ''));

		if (0 === strlen(trim($sFolderFullNameRaw)) || !is_array($aUids) || 0 === count($aUids))
		{
			throw new \Core\Exceptions\ClientException(\Core\Notifications::InvalidInputParameter);
		}

		$oAccount = $this->getAccountFromParam();

		$this->oApiMailManager->deleteMessage($oAccount, $sFolderFullNameRaw, $aUids);

		return $this->TrueResponse($oAccount, __FUNCTION__);
	}	
	
	/**
	 * @return array
	 */
	public function GetMessage()
	{
		$sFolderFullNameRaw = (string) $this->getParamValue('Folder', '');
		$sUid = trim((string) $this->getParamValue('Uid', ''));
		$sRfc822SubMimeIndex = trim((string) $this->getParamValue('Rfc822MimeIndex', ''));

		$iUid = 0 < strlen($sUid) && is_numeric($sUid) ? (int) $sUid : 0;

		if (0 === strlen(trim($sFolderFullNameRaw)) || 0 >= $iUid)
		{
			throw new \Core\Exceptions\ClientException(\Core\Notifications::InvalidInputParameter);
		}

		$oAccount = $this->getAccountFromParam();

		$oMessage = $this->oApiMailManager->getMessage($oAccount, $sFolderFullNameRaw, $iUid, $sRfc822SubMimeIndex, true, true, 600000);
		if (!($oMessage instanceof \CApiMailMessage))
		{
			throw new \Core\Exceptions\ClientException(\Core\Notifications::CanNotGetMessage);
		}

		return $this->DefaultResponse($oAccount, __FUNCTION__, $oMessage);
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

		$oAccount = $this->getAccountFromParam();

		$aList = array();
		foreach ($aUids as $iUid)
		{
			if (is_numeric($iUid))
			{
				$oMessage = $this->oApiMailManager->getMessage($oAccount, $sFolderFullNameRaw, (int) $iUid, '', true, true, 600000);
				if ($oMessage instanceof \CApiMailMessage)
				{
					$aList[] = $oMessage;
				}

				unset($oMessage);
			}
		}

		return $this->DefaultResponse($oAccount, __FUNCTION__, $aList);
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
			$oApiUsers = $this->GetManager('users');
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

		return $this->DefaultResponse($oAccount, __FUNCTION__, $mResult);
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

			$oApiFetchers = $this->ApiFetchers();
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
		$oApiUsers = $this->GetManager('users');
		if (!empty($sIdIdentity) && is_numeric($sIdIdentity) && 0 < (int) $sIdIdentity)
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

				$oModuleManager = \CApi::GetModuleManager();
				$aCollection = $oMessage->GetRcpt();

				$aEmails = array();
				$aCollection->ForeachList(function ($oEmail) use (&$aEmails) {
					$aEmails[strtolower($oEmail->GetEmail())] = trim($oEmail->GetDisplayName());
				});

				if (is_array($aEmails))
				{
					\CApi::Plugin()->RunHook('webmail.message-suggest-email', array(&$oAccount, &$aEmails));

					$oModuleManager->ExecuteMethod('Contacs', 'updateSuggestTable', array('Emails' => $aEmails));
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
		return $this->DefaultResponse($oAccount, __FUNCTION__, $mResult);
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

		return $this->DefaultResponse($oAccount, __FUNCTION__, $mResult);
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

		return $this->TrueResponse($oAccount, 'SetAllMessagesSeen');
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

		return $this->DefaultResponse($oAccount, __FUNCTION__, $this->oApiMailManager->setSystemFolderNames($oAccount, $aData));
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

				return $this->DefaultResponse($oAccount, __FUNCTION__, array(
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
				));
			}
		}

		return $this->FalseResponse($oAccount, __FUNCTION__);
	}
	
}

return new MailModule('1.0');
