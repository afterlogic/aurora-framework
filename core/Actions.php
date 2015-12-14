<?php

/* -AFTERLOGIC LICENSE HEADER- */

namespace Core;

/**
 * @category Core
 */
class Actions
{
	/**
	 * @var \CApiUsersManager
	 */
	protected $oApiUsers;

	/**
	 * @var \CApiTenantsManager
	 */
	protected $oApiTenants;
	
	/**
	 * @var \CApiWebmailManager
	 */
	protected $oApiWebMail;

	/**
	 * @var \CApiIntegratorManager
	 */
	protected $oApiIntegrator;

	/**
	 * @var \CApiMailManager
	 */
	protected $oApiMail;

	/**
	 * @var \CApiFilecacheManager
	 */
	protected $oApiFileCache;

	/**
	 * @var \CApiSieveManager
	 */
	protected $oApiSieve;

	/**
	 * @var \CApiFilestorageManager
	 */
	protected $oApiFilestorage;

	/**
	 * @var \CApiFetchersManager
	 */
	protected $oApiFetchers;

	/**
	 * @var \CApiCalendarManager
	 */
	protected $oApiCalendar;

	/**
	 * @var \CApiCapabilityManager
	 */
	protected $oApiCapability;

	/**
	 * @var \CApiHelpdeskManager
	 */
	protected $oApiHelpdesk;

	/**
	 * @return void
	 */
	protected function __construct()
	{
		$this->oHttp = null;

		$this->oApiUsers = \CApi::Manager('users');
		$this->oApiTenants = \CApi::Manager('tenants');
		$this->oApiWebMail = \CApi::Manager('webmail');
		$this->oApiIntegrator = \CApi::Manager('integrator');
		$this->oApiMail = \CApi::Manager('mail');
		$this->oApiFileCache = \CApi::Manager('filecache');
		$this->oApiSieve = \CApi::Manager('sieve');
		$this->oApiCapability = \CApi::Manager('capability');

		$this->oApiFetchers = null;
		$this->oApiHelpdesk = null;
	}

	/**
	 * @return \Core\Actions
	 */
	public static function NewInstance()
	{
		return new self();
	}

	/**
	 * @return \CApiFetchersManager
	 */
	public function ApiFetchers()
	{
		if (null === $this->oApiFetchers)
		{
			$this->oApiFetchers = \CApi::Manager('fetchers');
		}
		
		return $this->oApiFetchers;
	}

	/**
	 * @return \CApiFilecacheManager
	 */
	public function ApiFileCache()
	{
		return $this->oApiFileCache;
	}

	/**
	 * @return \CApiHelpdeskManager
	 */
	public function ApiHelpdesk()
	{
		if (null === $this->oApiHelpdesk)
		{
//			$this->oApiHelpdesk = \CApi::Manager('helpdesk');
		}

		return $this->oApiHelpdesk;
	}

	/**
	 * @return \CApiSieveManager
	 */
	public function ApiSieve()
	{
		return $this->oApiSieve;
	}

	/**
	 * @param int $iAccountId
	 * @param bool $bVerifyLogginedUserId = true
	 * @param string $sAuthToken = ''
	 * @return CAccount | null
	 */
	public function getAccount($iAccountId, $bVerifyLogginedUserId = true, $sAuthToken = '')
	{
		$oResult = null;
		$iUserId = $bVerifyLogginedUserId ? $this->oApiIntegrator->getLogginedUserId($sAuthToken) : 1;
		if (0 < $iUserId)
		{
			$oAccount = $this->oApiUsers->getAccountById($iAccountId);
			if ($oAccount instanceof \CAccount && 
				($bVerifyLogginedUserId && $oAccount->IdUser === $iUserId || !$bVerifyLogginedUserId) && !$oAccount->IsDisabled)
			{
				$oResult = $oAccount;
			}
		}

		return $oResult;
	}

	/**
	 * @param string $sAuthToken = ''
	 * @return \CAccount | null
	 */
	public function GetDefaultAccount($sAuthToken = '')
	{
		$oResult = null;
		$iUserId = $this->oApiIntegrator->getLogginedUserId($sAuthToken);
		if (0 < $iUserId)
		{
			$iAccountId = $this->oApiUsers->getDefaultAccountId($iUserId);
			if (0 < $iAccountId)
			{
				$oAccount = $this->oApiUsers->getAccountById($iAccountId);
				if ($oAccount instanceof \CAccount && !$oAccount->IsDisabled)
				{
					$oResult = $oAccount;
				}
			}
		}

		return $oResult;
	}

	/**
	 * @return \CAccount|null
	 */
	public function GetCurrentAccount($bThrowAuthExceptionOnFalse = true)
	{
		return $this->getAccountFromParam($bThrowAuthExceptionOnFalse);
	}

	/**
	 * @param \CAccount $oAccount
	 * 
	 * @return \CHelpdeskUser|null
	 */
	public function GetHelpdeskAccountFromMainAccount(&$oAccount)
	{
		$oResult = null;
		$oApiHelpdesk = $this->ApiHelpdesk();
		if ($oAccount && $oAccount->IsDefaultAccount && $oApiHelpdesk && $this->oApiCapability->isHelpdeskSupported($oAccount))
		{
			if (0 < $oAccount->User->IdHelpdeskUser)
			{
				$oHelpdeskUser = $oApiHelpdesk->getUserById($oAccount->IdTenant, $oAccount->User->IdHelpdeskUser);
				$oResult = $oHelpdeskUser instanceof \CHelpdeskUser ? $oHelpdeskUser : null;
			}

			if (!($oResult instanceof \CHelpdeskUser))
			{
				$oHelpdeskUser = $oApiHelpdesk->getUserByEmail($oAccount->IdTenant, $oAccount->Email);
				$oResult = $oHelpdeskUser instanceof \CHelpdeskUser ? $oHelpdeskUser : null;
				
				if ($oResult instanceof \CHelpdeskUser)
				{
					$oAccount->User->IdHelpdeskUser = $oHelpdeskUser->IdHelpdeskUser;
					$this->oApiUsers->updateAccount($oAccount);
				}
			}

			if (!($oResult instanceof \CHelpdeskUser))
			{
				$oHelpdeskUser = new \CHelpdeskUser();
				$oHelpdeskUser->Email = $oAccount->Email;
				$oHelpdeskUser->Name = $oAccount->FriendlyName;
				$oHelpdeskUser->IdSystemUser = $oAccount->IdUser;
				$oHelpdeskUser->IdTenant = $oAccount->IdTenant;
				$oHelpdeskUser->Activated = true;
				$oHelpdeskUser->IsAgent = true;
				$oHelpdeskUser->Language = $oAccount->User->DefaultLanguage;
				$oHelpdeskUser->DateFormat = $oAccount->User->DefaultDateFormat;
				$oHelpdeskUser->TimeFormat = $oAccount->User->DefaultTimeFormat;

				$oHelpdeskUser->setPassword($oAccount->IncomingMailPassword);

				if ($oApiHelpdesk->createUser($oHelpdeskUser))
				{
					$oAccount->User->IdHelpdeskUser = $oHelpdeskUser->IdHelpdeskUser;
					$this->oApiUsers->updateAccount($oAccount);

					$oResult = $oHelpdeskUser;
				}
			}
		}

		return $oResult;
	}


	/**
	 * @param bool $bThrowAuthExceptionOnFalse Default value is **true**.
	 * @param bool $bVerifyLogginedUserId Default value is **true**.
	 *
	 * @return \CAccount|null
	 */
	protected function getAccountFromParam($bThrowAuthExceptionOnFalse = true, $bVerifyLogginedUserId = true)
	{
		$oResult = null;
		$sAuthToken = (string) $this->getParamValue('AuthToken', '');
		$sAccountID = (string) $this->getParamValue('AccountID', '');
		if (0 === strlen($sAccountID) || !is_numeric($sAccountID))
		{
			throw new \Core\Exceptions\ClientException(\Core\Notifications::InvalidInputParameter);
		}

		$oResult = $this->getAccount((int) $sAccountID, $bVerifyLogginedUserId, $sAuthToken);

		if ($bThrowAuthExceptionOnFalse && !($oResult instanceof \CAccount))
		{
			$oExc = $this->oApiUsers->GetLastException();
			throw new \Core\Exceptions\ClientException(\Core\Notifications::AuthError,
				$oExc ? $oExc : null, $oExc ? $oExc->getMessage() : '');
		}

		return $oResult;
	}
	
	/**
	 * @param bool $bThrowAuthExceptionOnFalse Default value is **true**.
	 *
	 * @return \CAccount|null
	 */
	protected function getDefaultAccountFromParam($bThrowAuthExceptionOnFalse = true)
	{
		$sAuthToken = (string) $this->getParamValue('AuthToken', '');
		$oResult = $this->GetDefaultAccount($sAuthToken);
		if ($bThrowAuthExceptionOnFalse && !($oResult instanceof \CAccount))
		{
			throw new \Core\Exceptions\ClientException(\Core\Notifications::AuthError);
		}

		return $oResult;
	}

	/**
	 * @param CAccount $oAccount
	 * @param bool $bThrowAuthExceptionOnFalse Default value is **true**.
	 *
	 * @return \CHelpdeskUser|null
	 */
	protected function getHelpdeskAccountFromParam($oAccount, $bThrowAuthExceptionOnFalse = true)
	{
		$oResult = null;
		$oAccount = null;

		if ('0' === (string) $this->getParamValue('IsExt', '1'))
		{
			$oAccount = $this->getDefaultAccountFromParam($bThrowAuthExceptionOnFalse);
			if ($oAccount && $this->oApiCapability->isHelpdeskSupported($oAccount))
			{
				$oResult = $this->GetHelpdeskAccountFromMainAccount($oAccount);
			}
		}
		else
		{
			$mTenantID = $this->oApiTenants->getTenantIdByHash($this->getParamValue('TenantHash', ''));
			if (is_int($mTenantID))
			{
				$oResult = \api_Utils::GetHelpdeskAccount($mTenantID);
			}
		}

		if (!$oResult && $bThrowAuthExceptionOnFalse)
		{
			throw new \Core\Exceptions\ClientException(\Core\Notifications::UnknownError);
		}

		return $oResult;
	}

	/**
	 * @return \CHelpdeskUser|null
	 */
	protected function getExtHelpdeskAccountFromParam($bThrowAuthExceptionOnFalse = true)
	{
		$oResult = $this->GetExtHelpdeskAccount();
		if (!$oResult)
		{
			$oResult = null;
			if ($bThrowAuthExceptionOnFalse)
			{
				throw new \Core\Exceptions\ClientException(\Core\Notifications::AuthError);
			}
		}

		return $oResult;
	}

	/**
	 * @param \CAccount $oAccount
	 * @param \CFetcher $oFetcher
	 * @param bool $bUpdate = false
	 */
	private function populateFetcherFromHttpPost($oAccount, &$oFetcher, $bUpdate = false)
	{
		if ($oFetcher)
		{
			$oFetcher->IdAccount = $oAccount->IdAccount;
			$oFetcher->IdUser = $oAccount->IdUser;
			$oFetcher->IdDomain = $oAccount->IdDomain;
			$oFetcher->IdTenant = $oAccount->IdTenant;

			if (!$bUpdate)
			{
				$oFetcher->IncomingMailServer = (string) $this->oHttp->GetPost('IncomingMailServer', $oFetcher->IncomingMailServer);
				$oFetcher->IncomingMailPort = (int) $this->oHttp->GetPost('IncomingMailPort', $oFetcher->IncomingMailPort);
				$oFetcher->IncomingMailLogin = (string) $this->oHttp->GetPost('IncomingMailLogin', $oFetcher->IncomingMailLogin);
			
				$oFetcher->IncomingMailSecurity = ('1' === (string) $this->oHttp->GetPost('IncomingMailSsl', $oFetcher->IncomingMailSecurity === \MailSo\Net\Enumerations\ConnectionSecurityType::SSL ? '1' : '0')) ?
						\MailSo\Net\Enumerations\ConnectionSecurityType::SSL : \MailSo\Net\Enumerations\ConnectionSecurityType::NONE;
			}

			$sIncomingMailPassword = (string) $this->oHttp->GetPost('IncomingMailPassword', $oFetcher->IncomingMailPassword);
			if ('******' !== $sIncomingMailPassword)
			{
				$oFetcher->IncomingMailPassword = $sIncomingMailPassword;
			}

			$oFetcher->Folder = (string) $this->oHttp->GetPost('Folder', $oFetcher->Folder);
			
			$oFetcher->IsEnabled = '1' === (string) $this->oHttp->GetPost('IsEnabled', $oFetcher->IsEnabled ? '1' : '0');

			$oFetcher->LeaveMessagesOnServer = '1' === (string) $this->oHttp->GetPost('LeaveMessagesOnServer', $oFetcher->LeaveMessagesOnServer ? '1' : '0');
			$oFetcher->Name = (string) $this->oHttp->GetPost('Name', $oFetcher->Name);
			$oFetcher->Email = (string) $this->oHttp->GetPost('Email', $oFetcher->Email);
			$oFetcher->Signature = (string) $this->oHttp->GetPost('Signature', $oFetcher->Signature);
			$oFetcher->SignatureOptions = (string) $this->oHttp->GetPost('SignatureOptions', $oFetcher->SignatureOptions);

			$oFetcher->IsOutgoingEnabled = '1' === (string) $this->oHttp->GetPost('IsOutgoingEnabled', $oFetcher->IsOutgoingEnabled ? '1' : '0');
			$oFetcher->OutgoingMailServer = (string) $this->oHttp->GetPost('OutgoingMailServer', $oFetcher->OutgoingMailServer);
			$oFetcher->OutgoingMailPort = (int) $this->oHttp->GetPost('OutgoingMailPort', $oFetcher->OutgoingMailPort);
			$oFetcher->OutgoingMailAuth = '1' === (string) $this->oHttp->GetPost('OutgoingMailAuth', $oFetcher->OutgoingMailAuth ? '1' : '0');
			
			$oFetcher->OutgoingMailSecurity = ('1' === (string) $this->oHttp->GetPost('OutgoingMailSsl', $oFetcher->OutgoingMailSecurity === \MailSo\Net\Enumerations\ConnectionSecurityType::SSL ? '1' : '0')) ?
					\MailSo\Net\Enumerations\ConnectionSecurityType::SSL : (587 === $oFetcher->OutgoingMailPort ?
						\MailSo\Net\Enumerations\ConnectionSecurityType::STARTTLS : \MailSo\Net\Enumerations\ConnectionSecurityType::NONE);
		}
	}

	/**
	 * @return array
	 */
	public function AjaxAccountFetcherGetList()
	{
		$oAccount = $this->getAccountFromParam();
		return $this->DefaultResponse($oAccount, __FUNCTION__, $this->ApiFetchers()->getFetchers($oAccount));
	}

	/**
	 * @return array
	 */
	public function AjaxAccountFetcherCreate()
	{
		$oAccount = $this->getAccountFromParam();
		$oFetcher = null;

		$this->ApiFetchers();

		$oFetcher = new \CFetcher($oAccount);
		$this->populateFetcherFromHttpPost($oAccount, $oFetcher);

		$bResult = $this->ApiFetchers()->createFetcher($oAccount, $oFetcher);
		if ($bResult)
		{
			$sStartScript = '/opt/afterlogic/scripts/webshell-mailfetch-on-create.sh'; // TODO
			if (@\file_exists($sStartScript))
			{
				@\shell_exec($sStartScript.' '.$oAccount->Email.' '.$oFetcher->IdFetcher);
			}

			return $this->TrueResponse($oAccount, __FUNCTION__);
		}

		$oExc = $this->ApiFetchers()->GetLastException();
		if ($oExc && $oExc instanceof \CApiBaseException)
		{
			switch ($oExc->getCode())
			{
				case \CApiErrorCodes::Fetcher_ConnectToMailServerFailed:
					throw new \Core\Exceptions\ClientException(\Core\Notifications::FetcherConnectError);
				case \CApiErrorCodes::Fetcher_AuthError:
					throw new \Core\Exceptions\ClientException(\Core\Notifications::FetcherAuthError);
			}

			return $this->ExceptionResponse($oAccount, __FUNCTION__, $oExc);
		}

		return $this->FalseResponse($oAccount, __FUNCTION__);

	}
	
	/**
	 * @return array
	 */
	public function AjaxAccountFetcherUpdate()
	{
		$oAccount = $this->getAccountFromParam();
		$oFetcher = null;

		$this->ApiFetchers();

		$iFetcherID = (int) $this->getParamValue('FetcherID', 0);
		if (0 < $iFetcherID)
		{
			$aFetchers = $this->ApiFetchers()->getFetchers($oAccount);
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

		if ($oFetcher && $iFetcherID === $oFetcher->IdFetcher)
		{
			$this->populateFetcherFromHttpPost($oAccount, $oFetcher, false);
		}

		$bResult = $oFetcher ? $this->ApiFetchers()->updateFetcher($oAccount, $oFetcher) : false;
		if ($bResult || !$oFetcher)
		{
			return $this->DefaultResponse($oAccount, __FUNCTION__, $bResult);
		}

		$oExc = $this->ApiFetchers()->GetLastException();
		if ($oExc && $oExc instanceof \CApiBaseException)
		{
			switch ($oExc->getCode())
			{
				case \CApiErrorCodes::Fetcher_ConnectToMailServerFailed:
					throw new \Core\Exceptions\ClientException(\Core\Notifications::FetcherConnectError);
				case \CApiErrorCodes::Fetcher_AuthError:
					throw new \Core\Exceptions\ClientException(\Core\Notifications::FetcherAuthError);
			}

			return $this->ExceptionResponse($oAccount, __FUNCTION__, $oExc);
		}

		return $this->FalseResponse($oAccount, __FUNCTION__);
	}
	
	/**
	 * @return array
	 */
	public function AjaxAccountFetcherDelete()
	{
		$oAccount = $this->getAccountFromParam();

		$iFetcherID = (int) $this->getParamValue('FetcherID', 0);
		return $this->DefaultResponse($oAccount, __FUNCTION__, $this->ApiFetchers()->deleteFetcher($oAccount, $iFetcherID));
	}
	
	/**
	 * @return array
	 */
	public function AjaxDomainGetDataByEmail()
	{
		$oAccount = $this->getAccountFromParam();

		$sEmail = (string) $this->getParamValue('Email', '');
		$sDomainName = \MailSo\Base\Utils::GetDomainFromEmail($sEmail);
		if (empty($sEmail) || empty($sDomainName))
		{
			throw new \Core\Exceptions\ClientException(\Core\Notifications::InvalidInputParameter);
		}

		$oApiDomains = /* @var $oApiDomains \CApiDomainsManager */ \CApi::Manager('domains');
		$oDomain = $oApiDomains->getDomainByName($sDomainName);

		return $this->DefaultResponse($oAccount, __FUNCTION__, $oDomain ? array(
			'IsInternal' => $oDomain->IsInternal,
			'IncomingMailServer' => $oDomain->IncomingMailServer,
			'IncomingMailPort' => $oDomain->IncomingMailPort,
			'OutgoingMailServer' => $oDomain->OutgoingMailServer,
			'OutgoingMailPort' => $oDomain->OutgoingMailPort,
			'OutgoingMailAuth' => $oDomain->OutgoingMailAuth,
			'IncomingMailSsl' => $oDomain->IncomingMailUseSSL,
			'OutgoingMailSsl' => $oDomain->OutgoingMailUseSSL,
			
		) : false);
	}

	/**
	 * @return array
	 */
	public function AjaxFileExpand()
	{
		$self = $this;
		$mResult = false;
		$oAccount = null;
		
		$sHash = \md5(\microtime(true).\rand(1000, 9999));
		$this->rawCallback((string) $this->getParamValue('RawKey', ''), function ($oAccount, $sContentType, $sFileName, $rResource) use ($self, $sHash, &$mResult) {

			if ($self->ApiFileCache()->putFile($oAccount, $sHash, $rResource))
			{
				$sFullFilePath = $self->ApiFileCache()->generateFullFilePath($oAccount, $sHash);

				$aExpand = array();
				\CApi::Plugin()->RunHook('webmail.expand-attachment',
					array($oAccount, $sContentType, $sFileName, $sFullFilePath, &$aExpand, $self->ApiFileCache()));

				if (is_array($aExpand) && 0 < \count($aExpand))
				{
					foreach ($aExpand as $aItem)
					{
						if ($aItem && isset($aItem['FileName'], $aItem['MimeType'], $aItem['Size'], $aItem['TempName']))
						{
							$mResult[] = array(
								'FileName' => $aItem['FileName'],
								'MimeType' => $aItem['MimeType'],
								'EstimatedSize' => $aItem['Size'],
								'CID' => '',
								'Thumb' => \CApi::GetConf('labs.allow-thumbnail', true) &&
									\api_Utils::IsGDImageMimeTypeSuppoted($aItem['MimeType'], $aItem['FileName']),
								'Expand' => \CApi::isExpandMimeTypeSupported($aItem['MimeType'], $aItem['FileName']),
								'Iframed' =>\CApi::isIframedMimeTypeSupported($aItem['MimeType'], $aItem['FileName']),
								'IsInline' => false,
								'IsLinked' => false,
								'Hash' => \CApi::EncodeKeyValues(array(
									'TempFile' => true,
									'Iframed' =>\CApi::isIframedMimeTypeSupported($aItem['MimeType'], $aItem['FileName']),
									'AccountID' => $oAccount->IdAccount,
									'Name' => $aItem['FileName'],
									'TempName' => $aItem['TempName']
								))
							);
						}
					}
				}
				
				$self->ApiFileCache()->clear($oAccount, $sHash);
			}

		}, false, $oAccount);

		return $this->DefaultResponse($oAccount, __FUNCTION__, $mResult);
	}

	/**
	 * @return array
	 */
	public function AjaxMessageAttachmentsZip()
	{
		$aHashes = $this->getParamValue('Hashes', null);
		if (!is_array($aHashes) || 0 === count($aHashes) || !class_exists('ZipArchive'))
		{
			throw new \Core\Exceptions\ClientException(\Core\Notifications::InvalidInputParameter);
		}

		$mResult = false;
		$oAccount = $this->getAccountFromParam();

		$self = $this;
		$aAdd = array();
		foreach ($aHashes as $sHash)
		{
			$this->rawCallback($sHash, function ($oAccount, $sContentType, $sFileName, $rResource) use ($self, $sHash, &$aAdd) {

				$sHash = md5($sHash.rand(1000, 9999));
				if ($self->ApiFileCache()->putFile($oAccount, $sHash, $rResource))
				{
					$sFullFilePath = $self->ApiFileCache()->generateFullFilePath($oAccount, $sHash);
					$aAdd[] = array($sFullFilePath, $sFileName, $sContentType);
				}

			}, false, $oAccount);
		}

		if (0 < count($aAdd))
		{
			include_once PSEVEN_APP_ROOT_PATH.'libraries/other/Zip.php';

			$oZip = new \Zip();
			
			$sZipHash = md5(implode(',', $aHashes).rand(1000, 9999));
			foreach ($aAdd as $aItem)
			{
				$oZip->addFile(fopen($aItem[0], 'r'), $aItem[1]);
			}

			$self->ApiFileCache()->putFile($oAccount, $sZipHash, $oZip->getZipFile());
			$mResult = \CApi::EncodeKeyValues(array(
				'TempFile' => true,
				'AccountID' => $oAccount->IdAccount,
				'Name' => 'attachments.zip',
				'TempName' => $sZipHash
			));
		}

		return $this->DefaultResponse($oAccount, __FUNCTION__, $mResult);
	}

	/**
	 * When using a memory stream and the read
	 * filter "convert.base64-encode" the last 
	 * character is missing from the output if 
	 * the base64 conversion needs padding bytes. 
	 * 
	 * @return bool
	 */
	private function FixBase64EncodeOmitsPaddingBytes($sRaw)
	{
		$rStream = fopen('php://memory','r+');
		fwrite($rStream, '0');
		rewind($rStream);
		$rFilter = stream_filter_append($rStream, 'convert.base64-encode');
		
		if (0 === strlen(stream_get_contents($rStream)))
		{
			$iFileSize = \strlen($sRaw);
			$sRaw = str_pad($sRaw, $iFileSize + ($iFileSize % 3));
		}
		
		return $sRaw;
	}
	
	/**
	 * @return array
	 */
	public function AjaxMessageAttachmentsSaveToFiles()
	{
		$oDefAccount = null;
		$oAccount = $this->getAccountFromParam();

		if ($oAccount && $oAccount->IsDefaultAccount)
		{
			$oDefAccount = $oAccount;
		}
		else
		{
			$oDefAccount = $this->getDefaultAccountFromParam();
		}

		if (!$this->oApiCapability->isFilesSupported($oDefAccount))
		{
			throw new \Core\Exceptions\ClientException(\Core\Notifications::FilesNotAllowed);
		}

		$mResult = false;
		$self = $this;

		$oApiFilestorage = $self->oApiFilestorage;

		try
		{
			$aAttachments = $this->getParamValue('Attachments', array());
			if (is_array($aAttachments) && 0 < count($aAttachments) && $oApiFilestorage)
			{
				$mResult = array();
				foreach ($aAttachments as $sAttachment)
				{
					$aValues = \CApi::DecodeKeyValues($sAttachment);
					if (is_array($aValues))
					{
						$sFolder = isset($aValues['Folder']) ? $aValues['Folder'] : '';
						$iUid = (int) isset($aValues['Uid']) ? $aValues['Uid'] : 0;
						$sMimeIndex = (string) isset($aValues['MimeIndex']) ? $aValues['MimeIndex'] : '';

						$this->oApiMail->directMessageToStream($oAccount,
							function($rResource, $sContentType, $sFileName, $sMimeIndex = '') use ($oDefAccount, &$mResult, $sAttachment, $self, $oApiFilestorage) {

								$sTempName = \md5(\time().\rand(1000, 9999).$sFileName);

								if (is_resource($rResource) &&
									$self->ApiFileCache()->putFile($oDefAccount, $sTempName, $rResource))
								{
									$sContentType = (empty($sFileName)) ? 'text/plain' : \MailSo\Base\Utils::MimeContentType($sFileName);
									$sFileName = $self->clearFileName($sFileName, $sContentType, $sMimeIndex);

									$rSubResource = $self->ApiFileCache()->getFile($oDefAccount, $sTempName);
									if (is_resource($rSubResource))
									{
										$mResult[$sAttachment] = $oApiFilestorage->createFile(
											$oDefAccount, \EFileStorageTypeStr::Personal, '', $sFileName, $rSubResource, false);
									}

									$self->ApiFileCache()->clear($oDefAccount, $sTempName);
								}
							}, $sFolder, $iUid, $sMimeIndex);
					}
				}
			}
		}
		catch (\Exception $oException)
		{
			throw new \Core\Exceptions\ClientException(\Core\Notifications::MailServerError, $oException);
		}

		return $this->DefaultResponse($oAccount, __FUNCTION__, $mResult);
	}

	/**
	 * @return array
	 */
	public function AjaxMessageAttachmentsUpload()
	{
		$oAccount = $this->getAccountFromParam();

		$mResult = false;
		$self = $this;

		try
		{
			$aAttachments = $this->getParamValue('Attachments', array());
			if (is_array($aAttachments) && 0 < count($aAttachments))
			{
				$mResult = array();
				foreach ($aAttachments as $sAttachment)
				{
					$aValues = \CApi::DecodeKeyValues($sAttachment);
					if (is_array($aValues))
					{
						$sFolder = isset($aValues['Folder']) ? $aValues['Folder'] : '';
						$iUid = (int) isset($aValues['Uid']) ? $aValues['Uid'] : 0;
						$sMimeIndex = (string) isset($aValues['MimeIndex']) ? $aValues['MimeIndex'] : '';

						$sTempName = md5($sAttachment);
						if (!$this->ApiFileCache()->isFileExists($oAccount, $sTempName))
						{
							$this->oApiMail->directMessageToStream($oAccount,
								function($rResource, $sContentType, $sFileName, $sMimeIndex = '') use ($oAccount, &$mResult, $sTempName, $sAttachment, $self) {
									if (is_resource($rResource))
									{
										$sContentType = (empty($sFileName)) ? 'text/plain' : \MailSo\Base\Utils::MimeContentType($sFileName);
										$sFileName = $self->clearFileName($sFileName, $sContentType, $sMimeIndex);

										if ($self->ApiFileCache()->putFile($oAccount, $sTempName, $rResource))
										{
											$mResult[$sTempName] = $sAttachment;
										}
									}
								}, $sFolder, $iUid, $sMimeIndex);
						}
						else
						{
							$mResult[$sTempName] = $sAttachment;
						}
					}
				}
			}
		}
		catch (\Exception $oException)
		{
			throw new \Core\Exceptions\ClientException(\Core\Notifications::MailServerError, $oException);
		}

		return $this->DefaultResponse($oAccount, __FUNCTION__, $mResult);
	}

	/**
	 * @return array
	 */
	public function AjaxSystemUpdateLanguageOnLogin()
	{
		setcookie('aft-cache-ctrl', '', time() - 3600);
		$bResult = false;
		
		$sLanguage = (string) $this->getParamValue('Language', '');
		if (!empty($sLanguage))
		{
			$oApiIntegrator = \CApi::Manager('integrator');
			if ($oApiIntegrator)
			{
				$oApiIntegrator->setLoginLanguage($sLanguage);
				$bResult = true;
			}
		}
		
		return $this->DefaultResponse(null, __FUNCTION__, $bResult);
	}

	/**
	 * @return array
	 */
	public function AjaxAccountSignatureGet()
	{
		$oAccount = $this->getAccountFromParam();
		return $this->DefaultResponse($oAccount, __FUNCTION__, array(
			'Type' => $oAccount->SignatureType,
			'Options' => $oAccount->SignatureOptions,
			'Signature' => $oAccount->Signature
		));
	}

	/**
	 * @return array
	 */
	public function AjaxAccountSignatureUpdate()
	{
		$oAccount = $this->getAccountFromParam();

		$oAccount->Signature = (string) $this->oHttp->GetPost('Signature', $oAccount->Signature);
		$oAccount->SignatureType = (string) $this->oHttp->GetPost('Type', $oAccount->SignatureType);
		$oAccount->SignatureOptions = (string) $this->oHttp->GetPost('Options', $oAccount->SignatureOptions);

		return $this->DefaultResponse($oAccount, __FUNCTION__, $this->oApiUsers->updateAccount($oAccount));
	}

	/**
	 * @return array
	 */
	public function AjaxAccountIdentityLoyalUpdate()
	{
		$oAccount = $this->getAccountFromParam();

		$oAccount->FriendlyName = (string) $this->oHttp->GetPost('FriendlyName', $oAccount->FriendlyName);
		$oAccount->Signature = (string) $this->oHttp->GetPost('Signature', $oAccount->Signature);
		$oAccount->SignatureType = (string) $this->oHttp->GetPost('Type', $oAccount->SignatureType);
		$oAccount->SignatureOptions = (string) $this->oHttp->GetPost('Options', $oAccount->SignatureOptions);

		return $this->DefaultResponse($oAccount, __FUNCTION__, $this->oApiUsers->updateAccount($oAccount, ('1' === (string) $this->oHttp->GetPost('Default', '0'))));
	}

	/**
	 * @return array
	 */
	public function AjaxAccountDelete()
	{
		$bResult = false;
		$oAccount = $this->getDefaultAccountFromParam();

		$iAccountIDToDelete = (int) $this->oHttp->GetPost('AccountIDToDelete', 0);
		if (0 < $iAccountIDToDelete)
		{
			$oAccountToDelete = null;
			if ($oAccount->IdAccount === $iAccountIDToDelete)
			{
				$oAccountToDelete = $oAccount;
			}
			else
			{
				$oAccountToDelete = $this->oApiUsers->getAccountById($iAccountIDToDelete);
			}

			if ($oAccountToDelete instanceof \CAccount &&
				$oAccountToDelete->IdUser === $oAccount->IdUser &&
				!$oAccountToDelete->IsInternal &&
				((0 < $oAccount->IdDomain && $oAccount->Domain->AllowUsersChangeEmailSettings) || !$oAccountToDelete->IsDefaultAccount || 0 === $oAccount->IdDomain || -1 === $oAccount->IdDomain)
			)
			{
				$bResult = $this->oApiUsers->deleteAccount($oAccountToDelete);
			}
		}

		return $this->DefaultResponse($oAccount, __FUNCTION__, $bResult);
	}

	/**
	 * @param bool $bIsUpdate
	 * @param \CAccount $oAccount
	 */
	private function populateAccountFromHttpPost($bIsUpdate, &$oAccount)
	{
		if ($bIsUpdate && $oAccount->IsDefaultAccount && !$oAccount->Domain->AllowUsersChangeEmailSettings)
		{
			$oAccount->FriendlyName = (string) $this->oHttp->GetPost('FriendlyName', $oAccount->FriendlyName);
		}
		else
		{
			$oAccount->FriendlyName = (string) $this->oHttp->GetPost('FriendlyName', $oAccount->FriendlyName);

			if (!$oAccount->IsInternal)
			{
				$oAccount->IncomingMailPort = (int) $this->oHttp->GetPost('IncomingMailPort');
				$oAccount->OutgoingMailPort = (int) $this->oHttp->GetPost('OutgoingMailPort');
				$oAccount->OutgoingMailAuth = ('2' === (string) $this->oHttp->GetPost('OutgoingMailAuth', '2'))
					? \ESMTPAuthType::AuthCurrentUser : \ESMTPAuthType::NoAuth;

				$oAccount->IncomingMailServer = (string) $this->oHttp->GetPost('IncomingMailServer', '');
				$oAccount->IncomingMailLogin = (string) $this->oHttp->GetPost('IncomingMailLogin', '');

				$oAccount->OutgoingMailServer = (string) $this->oHttp->GetPost('OutgoingMailServer', '');

				$sIncomingMailPassword = (string) $this->oHttp->GetPost('IncomingMailPassword', '');
				$sIncomingMailPassword = trim($sIncomingMailPassword);
				if (API_DUMMY !== $sIncomingMailPassword && !empty($sIncomingMailPassword))
				{
					$oAccount->IncomingMailPassword = $sIncomingMailPassword;
				}
				if (!$bIsUpdate)
				{
					$oAccount->IncomingMailProtocol = \EMailProtocol::IMAP4;

					$oAccount->OutgoingMailLogin = (string) $this->oHttp->GetPost('OutgoingMailLogin', '');
					$sOutgoingMailPassword = (string) $this->oHttp->GetPost('OutgoingMailPassword', '');
					if (API_DUMMY !== $sOutgoingMailPassword)
					{
						$oAccount->OutgoingMailPassword = $sOutgoingMailPassword;
					}
				}
				
				$oAccount->IncomingMailUseSSL = '1' === (string) $this->oHttp->GetPost('IncomingMailSsl', '0');
				$oAccount->OutgoingMailUseSSL = '1' === (string) $this->oHttp->GetPost('OutgoingMailSsl', '0');
			}

			if (!$bIsUpdate)
			{
				$oAccount->Email = (string) $this->oHttp->GetPost('Email', '');
			}
		}
	}

	/**
	 * @return array
	 */
	public function AjaxAccountUpdatePassword()
	{
		$bResult = false;
		$oAccount = $this->getAccountFromParam();
		$sResetPasswordHash = $this->getParamValue('Hash', '');
		
		if (!empty($sResetPasswordHash))
		{
			if ($sResetPasswordHash !== $oAccount->User->PasswordResetHash)
			{
				throw new \Core\Exceptions\ClientException(\Core\Notifications::CanNotChangePassword);
			}
			else
			{
				$oAccount->User->PasswordResetHash = '';
			}
		}

		$sCurrentIncomingMailPassword = (string) $this->oHttp->GetPost('CurrentIncomingMailPassword', '');
		$sNewIncomingMailPassword = (string) $this->oHttp->GetPost('NewIncomingMailPassword', '');

		$bAllowChangePassword = $oAccount->isExtensionEnabled(\CAccount::ChangePasswordExtension) || !$oAccount->IsPasswordSpecified || !$oAccount->AllowMail;

		\CApi::Plugin()->RunHook('account-update-password', array(&$bAllowChangePassword));

		if ($bAllowChangePassword && 0 < strlen($sNewIncomingMailPassword) &&
			($sCurrentIncomingMailPassword == $oAccount->IncomingMailPassword || !$oAccount->IsPasswordSpecified))
		{
			$oAccount->PreviousMailPassword = $oAccount->IncomingMailPassword;
			$oAccount->IncomingMailPassword = $sNewIncomingMailPassword;
			$oAccount->IsPasswordSpecified = true;

			try
			{
				$bResult = $this->oApiUsers->updateAccount($oAccount);
			}
			catch (\Exception $oException)
			{
				if ($oException && $oException instanceof \CApiErrorCodes &&
					\CApiErrorCodes::UserManager_AccountOldPasswordNotCorrect === $oException->getCode())
				{
					throw new \Core\Exceptions\ClientException(\Core\Notifications::AccountOldPasswordNotCorrect, $oException);
				}
				
				throw new \Core\Exceptions\ClientException(\Core\Notifications::CanNotChangePassword, $oException);
			}
		}

		return $this->DefaultResponse($oAccount, __FUNCTION__, $bResult);
	}

	/**
	 * @return array
	 */
	public function AjaxAccountCreate()
	{
		$mResult = false;
		$oNewAccount = null;
		$oAccount = $this->getDefaultAccountFromParam();

		$oApiDomains = \CApi::Manager('domains');
		$oDomain = $oApiDomains->getDefaultDomain();
		if ($oDomain)
		{
			$oNewAccount = new \CAccount($oDomain);
			$oNewAccount->IdUser = $oAccount->IdUser;
			$oNewAccount->IsDefaultAccount = false;

			$this->populateAccountFromHttpPost(false, $oNewAccount);
			
			if ($this->oApiUsers->createAccount($oNewAccount))
			{
				$mResult = true;
			}
			else
			{
				$iClientErrorCode = \Core\Notifications::CanNotCreateAccount;
				$oException = $this->oApiUsers->GetLastException();
				if ($oException)
				{
					switch ($oException->getCode())
					{
						case \Errs::WebMailManager_AccountDisabled:
						case \Errs::UserManager_AccountAuthenticationFailed:
						case \Errs::WebMailManager_AccountAuthentication:
						case \Errs::WebMailManager_NewUserRegistrationDisabled:
						case \Errs::WebMailManager_AccountWebmailDisabled:
							$iClientErrorCode = \Core\Notifications::AuthError;
							break;
						case \Errs::UserManager_AccountConnectToMailServerFailed:
						case \Errs::WebMailManager_AccountConnectToMailServerFailed:
							$iClientErrorCode = \Core\Notifications::MailServerError;
							break;
						case \Errs::UserManager_LicenseKeyInvalid:
						case \Errs::UserManager_AccountCreateUserLimitReached:
						case \Errs::UserManager_LicenseKeyIsOutdated:
							$iClientErrorCode = \Core\Notifications::LicenseProblem;
							break;
						case \Errs::Db_ExceptionError:
							$iClientErrorCode = \Core\Notifications::DataBaseError;
							break;
					}
				}

				return $this->FalseResponse($oAccount, __FUNCTION__, $iClientErrorCode);
			}
		}
		
		if ($mResult && $oNewAccount)
		{
			$aExtensions = $oAccount->getExtensionList();
			$mResult = array(
				'IdAccount' => $oNewAccount->IdAccount,
				'Extensions' => $aExtensions
			);
		}
		else
		{
			$mResult = false;
		}

		return $this->DefaultResponse($oAccount, __FUNCTION__, $mResult);
	}
	
	/**
	 * @return array
	 */
	public function AjaxAccountConfigureMail()
	{
		$mResult = false;
		$oAccount = $this->getDefaultAccountFromParam();

		$this->populateAccountFromHttpPost(true, $oAccount);

		$oAccount->AllowMail = true;
		
		$iConnectTimeOut = \CApi::GetConf('socket.connect-timeout', 10);
		$iSocketTimeOut = \CApi::GetConf('socket.get-timeout', 20);

		\CApi::Plugin()->RunHook('webmail-imap-update-socket-timeouts',
			array(&$iConnectTimeOut, &$iSocketTimeOut));

		$aConnectErrors = array(false, false);
		$bConnectValid = false;
		try
		{
			$oImapClient = \MailSo\Imap\ImapClient::NewInstance();
			$oImapClient->SetTimeOuts($iConnectTimeOut, $iSocketTimeOut);
			$oImapClient->SetLogger(\CApi::MailSoLogger());

			$oImapClient->Connect($oAccount->IncomingMailServer, $oAccount->IncomingMailPort,
				$oAccount->IncomingMailUseSSL
					? \MailSo\Net\Enumerations\ConnectionSecurityType::SSL
					: \MailSo\Net\Enumerations\ConnectionSecurityType::NONE);

			$aConnectErrors[0] = true;

			$sProxyAuthUser = !empty($oAccount->CustomFields['ProxyAuthUser'])
				? $oAccount->CustomFields['ProxyAuthUser'] : '';

			$oImapClient->Login($oAccount->IncomingMailLogin, $oAccount->IncomingMailPassword, $sProxyAuthUser);

			$aConnectErrors[1] = true;
			$bConnectValid = true;

			$oImapClient->LogoutAndDisconnect();
		}
		catch (\Exception $oExceprion) {}
		
		if ($bConnectValid)
		{
			if ($this->oApiUsers->updateAccount($oAccount))
			{
				$mResult = true;
			}
			else
			{
				$iClientErrorCode = \Core\Notifications::CanNotCreateAccount;
				$oException = $this->oApiUsers->GetLastException();
				if ($oException)
				{
					switch ($oException->getCode())
					{
						case \Errs::WebMailManager_AccountDisabled:
						case \Errs::UserManager_AccountAuthenticationFailed:
						case \Errs::WebMailManager_AccountAuthentication:
						case \Errs::WebMailManager_NewUserRegistrationDisabled:
						case \Errs::WebMailManager_AccountWebmailDisabled:
							$iClientErrorCode = \Core\Notifications::AuthError;
							break;
						case \Errs::UserManager_AccountConnectToMailServerFailed:
						case \Errs::WebMailManager_AccountConnectToMailServerFailed:
							$iClientErrorCode = \Core\Notifications::MailServerError;
							break;
						case \Errs::UserManager_LicenseKeyInvalid:
						case \Errs::UserManager_AccountCreateUserLimitReached:
						case \Errs::UserManager_LicenseKeyIsOutdated:
							$iClientErrorCode = \Core\Notifications::LicenseProblem;
							break;
						case \Errs::Db_ExceptionError:
							$iClientErrorCode = \Core\Notifications::DataBaseError;
							break;
					}
				}
				return $this->FalseResponse($oAccount, __FUNCTION__, $iClientErrorCode);
			}
		}
		else
		{
			if ($aConnectErrors[0])
			{
				throw new \CApiManagerException(\Errs::UserManager_AccountAuthenticationFailed);
			}
			else
			{
				throw new \CApiManagerException(\Errs::UserManager_AccountConnectToMailServerFailed);
			}
		}
					
		if ($mResult && $oAccount)
		{
			$aExtensions = $oAccount->getExtensionList();
			$mResult = array(
				'IdAccount' => $oAccount->IdAccount,
				'Extensions' => $aExtensions
			);
		}
		else
		{
			$mResult = false;
		}

		return $this->DefaultResponse($oAccount, __FUNCTION__, $mResult);		
	}	

	/**
	 * @return array
	 */
	public function AjaxAccountResetPassword()
	{
		$mResult = false;
		$oAccount = $this->getDefaultAccountFromParam();
		$sUrlHash = $this->getParamValue('UrlHash', '');
		
		$oTenant = null;
		if ($oAccount->Domain->IdTenant > 0)
		{
			$oTenant = $this->oApiTenants->getTenantById($oAccount->Domain->IdTenant);
		}
		else
		{
			$oTenant = $this->oApiTenants->getDefaultGlobalTenant();
		}
		
		if ($oTenant)
		{
			$oNotificationAccount = $this->oApiUsers->GetAccountByEmail($oTenant->InviteNotificationEmailAccount);
			if ($oNotificationAccount)
			{
				$sPasswordResetUrl = rtrim(\api_Utils::GetAppUrl(), '/');
				$sPasswordResetHash = \md5(\time().\rand(1000, 9999).\CApi::$sSalt);
				$oAccount->User->PasswordResetHash = $sPasswordResetHash;
				$this->oApiUsers->updateAccount($oAccount);
				$sSubject = \CApi::ClientI18N('ACCOUNT_PASSWORD_RESET/SUBJECT', $oAccount, array('SITE_NAME' => $oAccount->Domain->SiteName));
				$sBody = \CApi::ClientI18N('ACCOUNT_PASSWORD_RESET/BODY', $oAccount,
					array(
						'SITE_NAME' => $oAccount->Domain->SiteName,
						'PASSWORD_RESET_URL' => $sPasswordResetUrl . '/?reset-pass='.$sPasswordResetHash.'#'.$sUrlHash,
						'EMAIL' => $oAccount->Email
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
						$mResult = $this->oApiMail->sendMessage($oNotificationAccount, $oMessage);
					}
					catch (\CApiManagerException $oException)
					{
						throw $oException;
					}
				}
			}
		}
		return $this->DefaultResponse($oAccount, __FUNCTION__, $mResult);		
	}
	
	
	/**
	 * @return array
	 */
	public function AjaxAccountSettingsGet()
	{
		$oAccount = $this->getAccountFromParam();
		$aResult = array();

		$aResult['IsLinked'] = 0 < $oAccount->IdDomain;
		$aResult['IsInternal'] = (bool) $oAccount->IsInternal;
		$aResult['IsDefault'] = (bool) $oAccount->IsDefaultAccount;

		$aResult['FriendlyName'] = $oAccount->FriendlyName;
		$aResult['Email'] = $oAccount->Email;

		$aResult['IncomingMailServer'] = $oAccount->IncomingMailServer;
		$aResult['IncomingMailPort'] = $oAccount->IncomingMailPort;
		$aResult['IncomingMailLogin'] = $oAccount->IncomingMailLogin;

		$aResult['OutgoingMailServer'] = $oAccount->OutgoingMailServer;
		$aResult['OutgoingMailPort'] = $oAccount->OutgoingMailPort;
		$aResult['OutgoingMailLogin'] = $oAccount->OutgoingMailLogin;
		$aResult['OutgoingMailAuth'] = $oAccount->OutgoingMailAuth;

		$aResult['IncomingMailSsl'] = $oAccount->IncomingMailUseSSL;
		$aResult['OutgoingMailSsl'] = $oAccount->OutgoingMailUseSSL;

		$aResult['Extensions'] = array();

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
				$aResult['Extensions'][] = $sExtensionName;
			}
		}

		return $this->DefaultResponse($oAccount, __FUNCTION__, $aResult);
	}

	/**
	 * @return array
	 */
	public function AjaxSystemGetAppData()
	{
		$oApiIntegratorManager = \CApi::Manager('integrator');
		$sAuthToken = (string) $this->getParamValue('AuthToken', '');
		return $this->DefaultResponse(null, __FUNCTION__, 
				$oApiIntegratorManager ? $oApiIntegratorManager->appData(false, '', '', '', $sAuthToken) : false);
	}

	/**
	 * @return array
	 */
	public function AjaxUserSettingsGetSync()
	{
		$oAccount = $this->getDefaultAccountFromParam();
		$aResult = array(
			'Mobile' => $this->mobileSyncSettings($oAccount),
			'Outlook' => $this->outlookSyncSettings($oAccount)
		);

		return $this->DefaultResponse($oAccount, __FUNCTION__, $aResult);
	}

	/**
	 * @return array
	 */
	public function AjaxAccountSettingsUpdate()
	{
		setcookie('aft-cache-ctrl', '', time() - 3600);
		$oAccount = $this->getAccountFromParam();

		$this->populateAccountFromHttpPost(true, $oAccount);

		return $this->DefaultResponse($oAccount, __FUNCTION__, $this->oApiUsers->updateAccount($oAccount));
	}

	/**
	 * @return array
	 */
	public function AjaxUserSettingsUpdate()
	{
		setcookie('aft-cache-ctrl', '', time() - 3600);
		$oAccount = $this->getAccountFromParam();

		$iMailsPerPage = (int) $this->oHttp->GetPost('MailsPerPage', $oAccount->User->MailsPerPage);
		if ($iMailsPerPage < 1)
		{
			$iMailsPerPage = 1;
		}

		$iContactsPerPage = (int) $this->oHttp->GetPost('ContactsPerPage', $oAccount->User->ContactsPerPage);
		if ($iContactsPerPage < 1)
		{
			$iContactsPerPage = 1;
		}

		$iAutoCheckMailInterval = (int) $this->oHttp->GetPost('AutoCheckMailInterval', $oAccount->User->AutoCheckMailInterval);
		if (!in_array($iAutoCheckMailInterval, array(0, 1, 3, 5, 10, 15, 20, 30)))
		{
			$iAutoCheckMailInterval = 0;
		}

		$iLayout = (int) $this->oHttp->GetPost('Layout', $oAccount->User->Layout);
		$iDefaultEditor = (int) $this->oHttp->GetPost('DefaultEditor', $oAccount->User->DefaultEditor);
		$bUseThreads = '1' === (string) $this->oHttp->GetPost('UseThreads', $oAccount->User->UseThreads ? '1' : '0');
		$bSaveRepliedMessagesToCurrentFolder = '1' === (string) $this->oHttp->GetPost('SaveRepliedMessagesToCurrentFolder', $oAccount->User->SaveRepliedMessagesToCurrentFolder ? '1' : '0');
		$bDesktopNotifications = '1' === (string) $this->oHttp->GetPost('DesktopNotifications', $oAccount->User->DesktopNotifications ? '1' : '0');
		$bAllowChangeInputDirection = '1' === (string) $this->oHttp->GetPost('AllowChangeInputDirection', $oAccount->User->AllowChangeInputDirection ? '1' : '0');
		
		$bFilesEnable = '1' === (string) $this->oHttp->GetPost('FilesEnable', $oAccount->User->FilesEnable ? '1' : '0');

		$sTheme = (string) $this->oHttp->GetPost('DefaultTheme', $oAccount->User->DefaultSkin);
//		$sTheme = $this->validateTheme($sTheme);

		$sLang = (string) $this->oHttp->GetPost('DefaultLanguage', $oAccount->User->DefaultLanguage);
//		$sLang = $this->validateLang($sLang);

		$sDateFormat = (string) $this->oHttp->GetPost('DefaultDateFormat', $oAccount->User->DefaultDateFormat);
		$iTimeFormat = (int) $this->oHttp->GetPost('DefaultTimeFormat', $oAccount->User->DefaultTimeFormat);

		$sEmailNotification = (string) $this->oHttp->GetPost('EmailNotification', $oAccount->User->EmailNotification);

		$oAccount->User->MailsPerPage = $iMailsPerPage;
		$oAccount->User->ContactsPerPage = $iContactsPerPage;
		$oAccount->User->Layout = $iLayout;
		$oAccount->User->DefaultSkin = $sTheme;
		$oAccount->User->DefaultEditor = $iDefaultEditor;
		$oAccount->User->DefaultLanguage = $sLang;
		$oAccount->User->DefaultDateFormat = $sDateFormat;
		$oAccount->User->DefaultTimeFormat = $iTimeFormat;
		$oAccount->User->AutoCheckMailInterval = $iAutoCheckMailInterval;
		$oAccount->User->UseThreads = $bUseThreads;
		$oAccount->User->SaveRepliedMessagesToCurrentFolder = $bSaveRepliedMessagesToCurrentFolder;
		$oAccount->User->DesktopNotifications = $bDesktopNotifications;
		$oAccount->User->AllowChangeInputDirection = $bAllowChangeInputDirection;

		$oAccount->User->EnableOpenPgp = '1' === (string) $this->oHttp->GetPost('EnableOpenPgp', $oAccount->User->EnableOpenPgp ? '1' : '0');
		$oAccount->User->AllowAutosaveInDrafts = '1' === (string) $this->oHttp->GetPost('AllowAutosaveInDrafts', $oAccount->User->AllowAutosaveInDrafts ? '1' : '0');
		$oAccount->User->AutosignOutgoingEmails = '1' === (string) $this->oHttp->GetPost('AutosignOutgoingEmails', $oAccount->User->AutosignOutgoingEmails ? '1' : '0');
		$oAccount->User->FilesEnable = $bFilesEnable;
		
		$oAccount->User->EmailNotification = $sEmailNotification;

		// calendar
		$oCalUser = $this->oApiUsers->getOrCreateCalUser($oAccount->IdUser);
		if ($oCalUser)
		{
			$oCalUser->ShowWeekEnds = (bool) $this->oHttp->GetPost('ShowWeekEnds', $oCalUser->ShowWeekEnds);
			$oCalUser->ShowWorkDay = (bool) $this->oHttp->GetPost('ShowWorkDay', $oCalUser->ShowWorkDay);
			$oCalUser->WorkDayStarts = (int) $this->oHttp->GetPost('WorkDayStarts', $oCalUser->WorkDayStarts);
			$oCalUser->WorkDayEnds = (int) $this->oHttp->GetPost('WorkDayEnds', $oCalUser->WorkDayEnds);
			$oCalUser->WeekStartsOn = (int) $this->oHttp->GetPost('WeekStartsOn', $oCalUser->WeekStartsOn);
			$oCalUser->DefaultTab = (int) $this->oHttp->GetPost('DefaultTab', $oCalUser->DefaultTab);
		}

		return $this->DefaultResponse($oAccount, __FUNCTION__, $this->oApiUsers->updateAccount($oAccount) &&
			$oCalUser && $this->oApiUsers->updateCalUser($oCalUser));
	}

	/**
	 * @return array
	 */
	/*public function AjaxHelpdeskUserSettings()
	{
		$oAccount = $this->getAccountFromParam();
		$oHelpdeskUser = $this->GetHelpdeskAccountFromMainAccount($oAccount);

		$aResult = array(
			'Signature' => $oHelpdeskUser->Signature,
			'SignatureEnable' => $oHelpdeskUser->SignatureEnable
		);

		return $this->DefaultResponse($oAccount, __FUNCTION__, $aResult);
	}*/

	/**
	 * @return array
	 */
	public function AjaxAccountRegister()
	{
		$sName = trim((string) $this->getParamValue('Name', ''));
		$sEmail = trim((string) $this->getParamValue('Email', ''));
		$sPassword =  trim((string) $this->getParamValue('Password', ''));

		$sQuestion =  trim((string) $this->getParamValue('Question', ''));
		$sAnswer =  trim((string) $this->getParamValue('Answer', ''));

		\CApi::Plugin()->RunHook('webmail-register-custom-data', array($this->getParamValue('CustomRequestData', null)));

		$oSettings =& \CApi::GetSettings();
		if (!$oSettings || !$oSettings->GetConf('Common/AllowRegistration'))
		{
			throw new \Core\Exceptions\ClientException(\Core\Notifications::InvalidInputParameter);
		}

		if (0 === strlen($sPassword) || 0 === strlen($sEmail) || 0 === strlen($sQuestion) || 0 === strlen($sAnswer))
		{
			throw new \Core\Exceptions\ClientException(\Core\Notifications::InvalidInputParameter);
		}

		$oAccount = $this->oApiUsers->getAccountByEmail($sEmail);
		if ($oAccount instanceof \CAccount)
		{
			throw new \Core\Exceptions\ClientException(\Core\Notifications::InvalidInputParameter); // TODO
		}

		try
		{
			$oAccount = $this->oApiWebMail->CreateAccountProcess($sEmail, $sPassword, '', array(
				'FriendlyName' => $sName,
				'Question1' => $sQuestion,
				'Answer1' => $sAnswer
			), true);
			
			if ($oAccount instanceof \CAccount)
			{
				\CApi::Plugin()->RunHook('api-integrator-login-success-post-create-account-call', array(&$oAccount));
			}
			else
			{
				$oException = $this->oApiWebMail->GetLastException();

				\CApi::Plugin()->RunHook('api-integrator-login-error-post-create-account-call', array(&$oException));

				throw (is_object($oException))
					? $oException
					: new \CApiManagerException(Errs::WebMailManager_AccountCreateOnLogin);
			}
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

			throw new \Core\Exceptions\ClientException($iErrorCode, $oException,
				$oException instanceof \CApiBaseException ? $oException->GetPreviousMessage() :
				($oException ? $oException->getMessage() : ''));
		}

		if ($oAccount instanceof \CAccount)
		{
			$this->oApiIntegrator->setAccountAsLoggedIn($oAccount);
			return $this->TrueResponse($oAccount, __FUNCTION__);
		}

		throw new \Core\Exceptions\ClientException(\Core\Notifications::AuthError);
	}

	/**
	 * @return array
	 */
	public function AjaxAccountGetForgotQuestion()
	{
		$sEmail = trim((string) $this->getParamValue('Email', ''));

		\CApi::Plugin()->RunHook('webmail-forgot-custom-data', array($this->getParamValue('CustomRequestData', null)));

		$oSettings =& \CApi::GetSettings();
		if (!$oSettings || !$oSettings->GetConf('Common/AllowPasswordReset') || 0 === strlen($sEmail))
		{
			throw new \Core\Exceptions\ClientException(\Core\Notifications::InvalidInputParameter);
		}

		$oAccount = $this->oApiUsers->getAccountByEmail($sEmail);
		if (!($oAccount instanceof \CAccount) || !$oAccount->IsInternal)
		{
			throw new \Core\Exceptions\ClientException(\Core\Notifications::InvalidInputParameter); // TODO
		}

		return $this->DefaultResponse($oAccount, __FUNCTION__, array(
			'Email' => $oAccount->Email,
			'Question' => $oAccount->User->Question1
		));
	}

	/**
	 * @return array
	 */
	public function AjaxAccountValidateForgotQuestion()
	{
		$sEmail = trim((string) $this->getParamValue('Email', ''));
		$sQuestion =  trim((string) $this->getParamValue('Question', ''));
		$sAnswer =  trim((string) $this->getParamValue('Answer', ''));

		$oSettings =& \CApi::GetSettings();
		if (!$oSettings || !$oSettings->GetConf('Common/AllowPasswordReset') ||
			0 === strlen($sEmail) || 0 === strlen($sAnswer) || 0 === strlen($sQuestion))
		{
			throw new \Core\Exceptions\ClientException(\Core\Notifications::InvalidInputParameter);
		}

		$oAccount = $this->oApiUsers->getAccountByEmail($sEmail);
		if (!($oAccount instanceof \CAccount) || !$oAccount->IsInternal)
		{
			throw new \Core\Exceptions\ClientException(\Core\Notifications::InvalidInputParameter); // TODO
		}

		return $this->DefaultResponse($oAccount, __FUNCTION__, 
			$oAccount->User->Question1 === $sQuestion && $oAccount->User->Answer1 === $sAnswer);
	}

	/**
	 * @return array
	 */
	public function AjaxAccountChangeForgotPassword()
	{
		$sEmail = trim((string) $this->getParamValue('Email', ''));
		$sQuestion =  trim((string) $this->getParamValue('Question', ''));
		$sAnswer =  trim((string) $this->getParamValue('Answer', ''));
		$sPassword =  trim((string) $this->getParamValue('Password', ''));

		$oSettings =& \CApi::GetSettings();
		if (!$oSettings || !$oSettings->GetConf('Common/AllowPasswordReset') ||
			0 === strlen($sEmail) || 0 === strlen($sAnswer) || 0 === strlen($sQuestion) || 0 === strlen($sPassword))
		{
			throw new \Core\Exceptions\ClientException(\Core\Notifications::InvalidInputParameter);
		}

		$oAccount = $this->oApiUsers->getAccountByEmail($sEmail);
		if (!($oAccount instanceof \CAccount) || !$oAccount->IsInternal ||
			$oAccount->User->Question1 !== $sQuestion || $oAccount->User->Answer1 !== $sAnswer)
		{
			throw new \Core\Exceptions\ClientException(\Core\Notifications::InvalidInputParameter); // TODO
		}

		$oAccount->PreviousMailPassword = $oAccount->IncomingMailPassword;
		$oAccount->IncomingMailPassword = $sPassword;

		return $this->DefaultResponse($oAccount, __FUNCTION__, $this->oApiUsers->updateAccount($oAccount));
	}

	/**
	 * @return array
	 */
	public function AjaxFilesUploadByLink()
	{
		$oAccount = $this->getDefaultAccountFromParam();
		$oTenant = null;
		if ($this->oApiTenants)
		{
			$oTenant = (0 < $oAccount->IdTenant) ? $this->oApiTenants->getTenantById($oAccount->IdTenant) :
				$this->oApiTenants->getDefaultGlobalTenant();
		}

		$mResult = false;
		$rFile = null;
		
		if ($oTenant)
		{
			$aLinks = $this->getParamValue('Links', null);
			$bLinksAsIds = $this->getParamValue('LinksAsIds', false);
			$sAccessToken = $this->getParamValue('AccessToken', '');
			if (is_array($aLinks) && 0 < count($aLinks))
			{
				$mResult = array();
				foreach ($aLinks as $sLink)
				{
					$bFileSaveResult = false;
					$sTempName = '';
					$aData = array(
						'Type' => 0,
						'Size' => 0,
						'Path' => '',
						'Name' => '',
						'Hash' => ''
					);
					if ($sLink)
					{
						$iLinkType = \api_Utils::GetLinkType($sLink);
						if (\EFileStorageLinkType::GoogleDrive === $iLinkType || $bLinksAsIds)
						{
							$oSocial = $oTenant->getSocialByName('google');
							if ($oSocial)
							{
								$oInfo = \api_Utils::GetGoogleDriveFileInfo($sLink, $oSocial->SocialApiKey, $sAccessToken, $bLinksAsIds);
								if ($oInfo)
								{
									$aData['Name'] = isset($oInfo->title) ? $oInfo->title : $aData['Name'];
									$aData['Size'] = isset($oInfo->fileSize) ? $oInfo->fileSize : $aData['Size'];
									$aData['Hash'] = isset($oInfo->id) ? $oInfo->id : $aData['Hash'];
									if (isset($oInfo->downloadUrl))
									{
										$sTempName = md5('Files/Tmp/'.$aData['Type'].$aData['Path'].$aData['Name'].microtime(true).rand(1000, 9999));
										$rFile = $this->ApiFileCache()->getTempFile($oAccount, $sTempName, 'wb+');

										$aHeaders = array();
										if ($sAccessToken)
										{
											$aHeaders = array(
												'Authorization: Bearer '. $sAccessToken	
											);
										}
										$sContentType = '';
										$iCode = 0;
										$bFileSaveResult = $this->oHttp->SaveUrlToFile($oInfo->downloadUrl, $rFile, '', $sContentType, $iCode,
										null, 10, '', '', $aHeaders);
										if (is_resource($rFile))
										{
											@fclose($rFile);
										}
										$aData['Size'] = $this->ApiFileCache()->fileSize($oAccount, $sTempName);
									}
								}
							}
						}
						else/* if (\EFileStorageLinkType::DropBox === $iLinkType)*/
						{
							$aData['Name'] = urldecode(basename($sLink));
							$aData['Hash'] = $sLink;

							$sTempName = md5('Files/Tmp/'.$aData['Type'].$aData['Path'].$aData['Name'].microtime(true).rand(1000, 9999));
							$rFile = $this->ApiFileCache()->getTempFile($oAccount, $sTempName, 'wb+');
							$bFileSaveResult = $this->oHttp->SaveUrlToFile($sLink, $rFile);
							if (is_resource($rFile))
							{
								@fclose($rFile);
							}
							$aData['Size'] = $this->ApiFileCache()->fileSize($oAccount, $sTempName);
						}
					}

					if ($bFileSaveResult)
					{
						$mResult[] = array(
							'Name' => $aData['Name'],
							'TempName' => $sTempName,
							'Size' => (int) $aData['Size'],
							'MimeType' => '',
							'Hash' => $aData['Hash'],
							'MimeType' => \MailSo\Base\Utils::MimeContentType($aData['Name']),
							'NewHash' => \CApi::EncodeKeyValues(array(
								'TempFile' => true,
								'AccountID' => $oAccount->IdAccount,
								'Name' => $aData['Name'],
								'TempName' => $sTempName
								))
						);
					}
				}
			}
			else
			{
				throw new \Core\Exceptions\ClientException(\Core\Notifications::InvalidInputParameter);
			}
		}
		else
		{
			throw new \Core\Exceptions\ClientException(\Core\Notifications::FilesNotAllowed);
		}

		return $this->DefaultResponse($oAccount, __FUNCTION__, $mResult);
	}
	
	/**
	 * @return array
	 */
	public function AjaxFilesUpload()
	{
		$oAccount = $this->getDefaultAccountFromParam();
		$oTenant = null;
		if ($this->oApiTenants)
		{
			$oTenant = (0 < $oAccount->IdTenant) ? $this->oApiTenants->getTenantById($oAccount->IdTenant) :
				$this->oApiTenants->getDefaultGlobalTenant();
		}

		$mResult = false;
		if ($this->oApiCapability->isFilesSupported($oAccount) && $oTenant)
		{
			$aFiles = $this->getParamValue('Hashes', null);
			if (is_array($aFiles) && 0 < count($aFiles))
			{
				$mResult = array();
				foreach ($aFiles as $sHash)
				{
					$aData = \CApi::DecodeKeyValues($sHash);
					if (\is_array($aData) && 0 < \count($aData))
					{
						$oFileInfo = $this->oApiFilestorage->getFileInfo($oAccount, $aData['Type'], $aData['Path'], $aData['Name']);
						$rFile = null;
						if ($oFileInfo->IsLink)
						{
							if (\EFileStorageLinkType::GoogleDrive === $oFileInfo->LinkType)
							{
								$oSocial = $oTenant->getSocialByName('google');
								if ($oSocial)
								{
									$oInfo = \api_Utils::GetGoogleDriveFileInfo($oFileInfo->LinkUrl, $oSocial->SocialApiKey);
									$aData['Name'] = isset($oInfo->title) ? $oInfo->title : $aData['Name'];
									$aData['Size'] = isset($oInfo->fileSize) ? $oInfo->fileSize : $aData['Size'];

									if (isset($oInfo->downloadUrl))
									{
										$rFile = \MailSo\Base\ResourceRegistry::CreateMemoryResource();
										$this->oHttp->SaveUrlToFile($oInfo->downloadUrl, $rFile);
										rewind($rFile);
									}
								}
							}
							else /*if (\EFileStorageLinkType::DropBox === (int)$aFileInfo['LinkType'])*/
							{
								$rFile = \MailSo\Base\ResourceRegistry::CreateMemoryResource();
								$aData['Name'] = basename($oFileInfo->LinkUrl);
                                                                $aRemoteFileInfo = \api_Utils::GetRemoteFileInfo($oFileInfo->LinkUrl);
								$aData['Size'] = $aRemoteFileInfo['size'];

								$this->oHttp->SaveUrlToFile($oFileInfo->LinkUrl, $rFile);
								rewind($rFile);
							}
						}
						else
						{
							$rFile = $this->oApiFilestorage->getFile($oAccount, $aData['Type'], $aData['Path'], $aData['Name']);
						}
						
						$sTempName = md5('Files/Tmp/'.$aData['Type'].$aData['Path'].$aData['Name'].microtime(true).rand(1000, 9999));

						if (is_resource($rFile) && $this->ApiFileCache()->putFile($oAccount, $sTempName, $rFile))
						{
							$aItem = array(
								'Name' => $oFileInfo->Name,
								'TempName' => $sTempName,
								'Size' => (int) $aData['Size'],
								'Hash' => $sHash,
								'MimeType' => ''
							);

							$aItem['MimeType'] = \MailSo\Base\Utils::MimeContentType($aItem['Name']);
							$aItem['NewHash'] = \CApi::EncodeKeyValues(array(
								'TempFile' => true,
								'AccountID' => $oAccount->IdAccount,
								'Name' => $aItem['Name'],
								'TempName' => $sTempName
							));

							$mResult[] = $aItem;

							if (is_resource($rFile))
							{
								@fclose($rFile);
							}
						}
					}
				}
			}
			else
			{
				throw new \Core\Exceptions\ClientException(\Core\Notifications::InvalidInputParameter);
			}
		}
		else
		{
			throw new \Core\Exceptions\ClientException(\Core\Notifications::FilesNotAllowed);
		}

		return $this->DefaultResponse($oAccount, __FUNCTION__, $mResult);
	}

	/**
	 * @return array
	 */
	public function AjaxContactVCardUpload()
	{
		$oAccount = $this->getDefaultAccountFromParam();

		$mResult = false;
		if ($this->oApiCapability->isContactsSupported($oAccount))
		{
			$bGlobal = '1' === (string) $this->getParamValue('Global', '0');
			$sContactId = (string) $this->getParamValue('ContactId', '');
			$bSharedWithAll = '1' === (string) $this->getParamValue('SharedWithAll', '0');
			$iTenantId = $bSharedWithAll ? $oAccount->IdTenant : null;

			if ($bGlobal)
			{
				if (!$this->oApiCapability->isGlobalContactsSupported($oAccount, true))
				{
					throw new \Core\Exceptions\ClientException(\Core\Notifications::ContactsNotAllowed);
				}
			}
			else
			{
				if (!$this->oApiCapability->isPersonalContactsSupported($oAccount))
				{
					throw new \Core\Exceptions\ClientException(\Core\Notifications::ContactsNotAllowed);
				}
			}

			$oApiContacts = $this->ApiContacts();
			$oApiGContacts = $this->ApiGContacts();

			$oContact = $bGlobal ?
				$oApiGContacts->getContactById($oAccount, $sContactId) :
				$oApiContacts->getContactById($oAccount->IdUser, $sContactId, false, $iTenantId);
			
			if ($oContact)
			{
				$sTempName = md5('VCARD/'.$oAccount->IdUser.'/'.$oContact->IdContact.'/'.($bGlobal ? '1' : '0').'/');

				$oVCard = new \Sabre\VObject\Component\VCard();
				\CApiContactsVCardHelper::UpdateVCardFromContact($oContact, $oVCard);
				$sData = $oVCard->serialize();

				if ($this->ApiFileCache()->put($oAccount, $sTempName, $sData))
				{
					$mResult = array(
						'Name' => 'contact-'.$oContact->IdContact.'.vcf',
						'TempName' => $sTempName,
						'MimeType' => 'text/vcard',
						'Size' => strlen($sData),
						'Hash' => ''
					);

					$mResult['MimeType'] = \MailSo\Base\Utils::MimeContentType($mResult['Name']);
					$mResult['Hash'] = \CApi::EncodeKeyValues(array(
						'TempFile' => true,
						'AccountID' => $oAccount->IdAccount,
						'Name' => $mResult['Name'],
						'TempName' => $sTempName
					));

					return $this->DefaultResponse($oAccount, __FUNCTION__, $mResult);
				}
			}

			throw new \Core\Exceptions\ClientException(\Core\Notifications::CanNotGetContact);
		}
		else
		{
			throw new \Core\Exceptions\ClientException(\Core\Notifications::ContactsNotAllowed);
		}

		return $this->DefaultResponse($oAccount, __FUNCTION__, $mResult);
	}

	/**
	 * @return array
	 */
	public function AjaxContactsPhoneNames()
	{
		$oAccount = $this->getDefaultAccountFromParam();

		$aPhones = $this->getParamValue('Phones', null);

		if ($oAccount && is_array($aPhones) && 0 < count($aPhones))
		{
			$oApiVoiceManager = CApi::Manager('voice');
			if ($oApiVoiceManager)
			{
				return $this->DefaultResponse($oAccount, __FUNCTION__,
					$oApiVoiceManager->getNamesByCallersNumbers($oAccount, $aPhones));
			}
		}

		return $this->FalseResponse($oAccount, __FUNCTION__);
	}

	/**
	 * @return array
	 */
	public function AjaxAccountIdentitiesGet()
	{
		$oAccount = $this->getAccountFromParam();
		return $this->DefaultResponse($oAccount, __FUNCTION__, $this->oApiUsers->getUserIdentities($oAccount->IdUser));
	}

	/**
	 * @return array
	 */
	public function AjaxAccountIdentityCreate()
	{
		$oAccount = $this->getAccountFromParam();
		$sEmail = trim((string) $this->getParamValue('Email', ''));

		if (empty($sEmail))
		{
			throw new \Core\Exceptions\ClientException(\Core\Notifications::InvalidInputParameter);
		}

		$oIdentity = new \CIdentity();
		$oIdentity->IdAccount = $oAccount->IdAccount;
		$oIdentity->IdUser  = $oAccount->IdUser;
		$oIdentity->Enabled = '1' === (string) $this->getParamValue('Enabled', '1');
		$oIdentity->Email = $sEmail;
		$oIdentity->Signature = (string) $this->getParamValue('Signature', '');
		$oIdentity->UseSignature = '1' === (string) $this->getParamValue('UseSignature', '0');
		$oIdentity->FriendlyName = (string) $this->getParamValue('FriendlyName', '');

		return $this->DefaultResponse($oAccount, __FUNCTION__, $this->oApiUsers->createIdentity($oIdentity));
	}

	/**
	 * @return array
	 */
	public function AjaxAccountIdentityUpdate()
	{
		$oAccount = $this->getAccountFromParam();

		$iIdentityId = (int)$this->getParamValue('IdIdentity', 0);

		$oIdentity = $this->oApiUsers->getIdentity($iIdentityId);
		if (0 >= $iIdentityId || !$oIdentity || $oIdentity->IdUser !== $oAccount->IdUser || $oIdentity->IdAccount !== $oAccount->IdAccount)
		{
			throw new \Core\Exceptions\ClientException(\Core\Notifications::InvalidInputParameter);
		}

		$oIdentity->Default = '1' === (string)$this->getParamValue('Default', '0');
		$oIdentity->Enabled = '1' === (string)$this->getParamValue('Enabled', '1');
		$oIdentity->Email = trim((string)$this->getParamValue('Email', ''));
		$oIdentity->Signature = (string)$this->getParamValue('Signature', '');
		$oIdentity->UseSignature = '1' === (string)$this->getParamValue('UseSignature', '0');
		$oIdentity->FriendlyName = (string)$this->getParamValue('FriendlyName', '');

		return $this->DefaultResponse($oAccount, __FUNCTION__, $this->oApiUsers->updateIdentity($oIdentity));
	}

	/**
	 * @return array
	 */
	public function AjaxAccountIdentityDelete()
	{
		$oAccount = $this->getAccountFromParam();

		$iIdentityId = (int) $this->getParamValue('IdIdentity', 0);
		
		$oIdentity = $this->oApiUsers->getIdentity($iIdentityId);
		if (0 >= $iIdentityId || !$oIdentity || $oIdentity->IdUser !== $oAccount->IdUser || $oIdentity->IdAccount !== $oAccount->IdAccount)
		{
			throw new \Core\Exceptions\ClientException(\Core\Notifications::InvalidInputParameter);
		}
		
		return $this->DefaultResponse($oAccount, __FUNCTION__, $this->oApiUsers->deleteIdentity($iIdentityId));
	}

	/**
	 * @return array
	 */
	public function AjaxAccountAutoresponderGet()
	{
		$mResult = false;
		$oAccount = $this->getAccountFromParam();
		
		if ($oAccount && $oAccount->isExtensionEnabled(\CAccount::AutoresponderExtension))
		{
			$aAutoResponderValue = $this->ApiSieve()->getAutoresponder($oAccount);
			if (isset($aAutoResponderValue['subject'], $aAutoResponderValue['body'], $aAutoResponderValue['enabled']))
			{
				$mResult = array(
					'Enable' => (bool) $aAutoResponderValue['enabled'],
					'Subject' => (string) $aAutoResponderValue['subject'],
					'Message' => (string) $aAutoResponderValue['body']
				);
			}
		}

		return $this->DefaultResponse($oAccount, __FUNCTION__, $mResult);
	}

	/**
	 * @return array
	 */
	public function AjaxAccountAutoresponderUpdate()
	{
		$bIsDemo = false;
		$mResult = false;
		$oAccount = $this->getAccountFromParam();
		if ($oAccount && $oAccount->isExtensionEnabled(\CAccount::AutoresponderExtension))
		{
			\CApi::Plugin()->RunHook('plugin-is-demo-account', array(&$oAccount, &$bIsDemo));
			if (!$bIsDemo)
			{
				$bIsEnabled = '1' === $this->getParamValue('Enable', '0');
				$sSubject = (string) $this->getParamValue('Subject', '');
				$sMessage = (string) $this->getParamValue('Message', '');

				$mResult = $this->ApiSieve()->setAutoresponder($oAccount, $sSubject, $sMessage, $bIsEnabled);
			}
			else
			{
				throw new \Core\Exceptions\ClientException(\Core\Notifications::DemoAccount);
			}
		}

		return $this->DefaultResponse($oAccount, __FUNCTION__, $mResult);
	}

	/**
	 * @return array
	 */
	public function AjaxAccountForwardGet()
	{
		$mResult = false;
		$oAccount = $this->getAccountFromParam();
		
		if ($oAccount && $oAccount->isExtensionEnabled(\CAccount::ForwardExtension))
		{
			$aForwardValue = /* @var $aForwardValue array */  $this->ApiSieve()->getForward($oAccount);
			if (isset($aForwardValue['email'], $aForwardValue['enabled']))
			{
				$mResult = array(
					'Enable' => (bool) $aForwardValue['enabled'],
					'Email' => (string) $aForwardValue['email']
				);
			}
		}

		return $this->DefaultResponse($oAccount, __FUNCTION__, $mResult);
	}

	/**
	 * @return array
	 */
	public function AjaxAccountForwardUpdate()
	{
		$mResult = false;
		$bIsDemo = false;
		$oAccount = $this->getAccountFromParam();

		if ($oAccount && $oAccount->isExtensionEnabled(\CAccount::ForwardExtension))
		{
			\CApi::Plugin()->RunHook('plugin-is-demo-account', array(&$oAccount, &$bIsDemo));
			if (!$bIsDemo)
			{
				$bIsEnabled = '1' === $this->getParamValue('Enable', '0');
				$sForwardEmail = (string) $this->getParamValue('Email', '');
		
				$mResult = $this->ApiSieve()->setForward($oAccount, $sForwardEmail, $bIsEnabled);
			}
			else
			{
				throw new \Core\Exceptions\ClientException(\Core\Notifications::DemoAccount);
			}
		}

		return $this->DefaultResponse($oAccount, __FUNCTION__, $mResult);
	}

	/**
	 * @return array
	 */
	public function AjaxAccountSieveFiltersGet()
	{
		$mResult = false;
		$oAccount = $this->getAccountFromParam();

		if ($oAccount && $oAccount->isExtensionEnabled(\CAccount::SieveFiltersExtension))
		{
			$mResult = $this->ApiSieve()->getSieveFilters($oAccount);
		}

		return $this->DefaultResponse($oAccount, __FUNCTION__, $mResult);
	}

	/**
	 * @return array
	 */
	public function AjaxAccountSieveFiltersUpdate()
	{
		$mResult = false;
		$oAccount = $this->getAccountFromParam();

		if ($oAccount && $oAccount->isExtensionEnabled(\CAccount::SieveFiltersExtension))
		{
			$aFilters = $this->getParamValue('Filters', array());
			$aFilters = is_array($aFilters) ? $aFilters : array();

			$mResult = array();
			foreach ($aFilters as $aItem)
			{
				$oFilter = new \CFilter($oAccount);
				$oFilter->Enable = '1' === (string) (isset($aItem['Enable']) ? $aItem['Enable'] : '1');
				$oFilter->Field = (int) (isset($aItem['Field']) ? $aItem['Field'] : \EFilterFiels::From);
				$oFilter->Filter = (string) (isset($aItem['Filter']) ? $aItem['Filter'] : '');
				$oFilter->Condition = (int) (isset($aItem['Condition']) ? $aItem['Condition'] : \EFilterCondition::ContainSubstring);
				$oFilter->Action = (int) (isset($aItem['Action']) ? $aItem['Action'] : \EFilterAction::DoNothing);
				$oFilter->FolderFullName = (string) (isset($aItem['FolderFullName']) ? $aItem['FolderFullName'] : '');

				$mResult[] = $oFilter;
			}

			$mResult = $this->ApiSieve()->updateSieveFilters($oAccount, $mResult);
		}

		return $this->DefaultResponse($oAccount, __FUNCTION__, $mResult);
	}

	/**
	 * @param string $iError
	 *
	 * @return string
	 */
	public function convertUploadErrorToString($iError)
	{
		$sError = 'unknown';
		switch ($iError)
		{
			case UPLOAD_ERR_INI_SIZE:
			case UPLOAD_ERR_FORM_SIZE:
				$sError = 'size';
				break;
		}

		return $sError;
	}
	
	/**
	 * @return array
	 */
	public function MinInfo()
	{
		$mData = $this->getParamValue('Result', false);

		var_dump($mData);
		return true;
	}

	public function MinDownload()
	{
		$mData = $this->getParamValue('Result', false);

		if (isset($mData['AccountType']) && 'wm' !== $mData['AccountType'])
		{
			return true;
		}

		$oAccount = $this->oApiUsers->getAccountById((int) $mData['Account']);

		$mResult = false;
		if ($oAccount && $this->oApiCapability->isFilesSupported($oAccount))
		{
			$mResult = $this->oApiFilestorage->getSharedFile($oAccount, $mData['Type'], $mData['Path'], $mData['Name']);
		}
		
		if (false !== $mResult)
		{
			if (is_resource($mResult))
			{
				$sFileName = $mData['Name'];
				$sContentType = (empty($sFileName)) ? 'text/plain' : \MailSo\Base\Utils::MimeContentType($sFileName);
				$sFileName = $this->clearFileName($sFileName, $sContentType);
				$this->RawOutputHeaders(true, $sContentType, $sFileName);

				\MailSo\Base\Utils::FpassthruWithTimeLimitReset($mResult);
				@fclose($mResult);
			}
		}
		
		return true;
	}

	public function PathInfoDav()
	{
		set_error_handler(function ($errno, $errstr, $errfile, $errline ) {
			throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
		});

		@set_time_limit(3000);

		$sBaseUri = '/';
		if (false !== \strpos($this->oHttp->GetUrl(), 'index.php/dav/'))
		{
			$aPath = \trim($this->oHttp->GetPath(), '/\\ ');
			$sBaseUri = (0 < \strlen($aPath) ? '/'.$aPath : '').'/index.php/dav/';
		}
		
		$server = \afterlogic\DAV\Server::NewInstance($sBaseUri);
		$server->exec();
	}

	/**
	 * @return array
	 */
	public function UploadContacts()
	{
		$oAccount = $this->getDefaultAccountFromParam();

		if (!$this->oApiCapability->isPersonalContactsSupported($oAccount))
		{
			throw new \Core\Exceptions\ClientException(\Core\Notifications::ContactsNotAllowed);
		}
		
		$aFileData = $this->getParamValue('FileData', null);
		$sAdditionalData = $this->getParamValue('AdditionalData', '{}');
		$aAdditionalData = @json_decode($sAdditionalData, true);

		$sError = '';
		$aResponse = array(
			'ImportedCount' => 0,
			'ParsedCount' => 0
		);

		if (is_array($aFileData))
		{
			$sFileType = strtolower(\api_Utils::GetFileExtension($aFileData['name']));
			$bIsCsvVcfExtension  = $sFileType === 'csv' || $sFileType === 'vcf';

			if ($bIsCsvVcfExtension)
			{
				$sSavedName = 'import-post-' . md5($aFileData['name'] . $aFileData['tmp_name']);
				if ($this->ApiFileCache()->moveUploadedFile($oAccount, $sSavedName, $aFileData['tmp_name'])) {
					$oApiContactsManager = $this->ApiContacts();
					if ($oApiContactsManager)
					{
						$iParsedCount = 0;

						$iImportedCount = $oApiContactsManager->import(
							$oAccount->IdUser,
							$sFileType,
							$this->ApiFileCache()->generateFullFilePath($oAccount, $sSavedName),
							$iParsedCount,
							$iGroupId = $aAdditionalData['GroupId'],
							$bIsShared= $aAdditionalData['IsShared']
						);
					}

					if (false !== $iImportedCount && -1 !== $iImportedCount)
					{
						$aResponse['ImportedCount'] = $iImportedCount;
						$aResponse['ParsedCount'] = $iParsedCount;
					}
					else
					{
						$sError = 'unknown';
					}

					$this->ApiFileCache()->clear($oAccount, $sSavedName);
				}
				else
				{
					$sError = 'unknown';
				}
			}
			else
			{
				throw new \Core\Exceptions\ClientException(\Core\Notifications::IncorrectFileExtension);
			}
		}
		else
		{
			$sError = 'unknown';
		}

		if (0 < strlen($sError))
		{
			$aResponse['Error'] = $sError;
		}

		return $this->DefaultResponse($oAccount, __FUNCTION__, $aResponse);
	}
	
	/**
	 * @return array
	 */
	public function UploadCalendars()
	{
		$oAccount = $this->getDefaultAccountFromParam();
		
		$aFileData = $this->getParamValue('FileData', null);
		$sAdditionalData = $this->getParamValue('AdditionalData', '{}');
		$aAdditionalData = @json_decode($sAdditionalData, true);
		
		$sCalendarId = isset($aAdditionalData['CalendarID']) ? $aAdditionalData['CalendarID'] : '';

		$sError = '';
		$aResponse = array(
			'ImportedCount' => 0
		);

		if (is_array($aFileData))
		{
			$bIsIcsExtension  = strtolower(pathinfo($aFileData['name'], PATHINFO_EXTENSION)) === 'ics';

			if ($bIsIcsExtension)
			{
				$sSavedName = 'import-post-' . md5($aFileData['name'] . $aFileData['tmp_name']);
				if ($this->ApiFileCache()->moveUploadedFile($oAccount, $sSavedName, $aFileData['tmp_name'])) {
					$oApiCalendarManager = $this->oApiCalendar;
					if ($oApiCalendarManager) {
						$iImportedCount = $oApiCalendarManager->importToCalendarFromIcs($oAccount, $sCalendarId, $this->ApiFileCache()->generateFullFilePath($oAccount, $sSavedName));
					}

					if (false !== $iImportedCount && -1 !== $iImportedCount) {
						$aResponse['ImportedCount'] = $iImportedCount;
					} else {
						$sError = 'unknown';
					}

					$this->ApiFileCache()->clear($oAccount, $sSavedName);
				}
				else
				{
					$sError = 'unknown';
				}
			}
			else
			{
				throw new \Core\Exceptions\ClientException(\Core\Notifications::IncorrectFileExtension);
			}
		}
		else
		{
			$sError = 'unknown';
		}

		if (0 < strlen($sError))
		{
			$aResponse['Error'] = $sError;
		}		
		
		return $this->DefaultResponse($oAccount, __FUNCTION__, $aResponse);
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
					$sSavedName = 'upload-post-'.md5($aFileData['name'].$aFileData['tmp_name']);
					if ($this->ApiFileCache()->moveUploadedFile($oAccount, $sSavedName, $aFileData['tmp_name']))
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

		return $this->DefaultResponse($oAccount, __FUNCTION__, $aResponse);
	}
	
	/**
	 * @return array
	 */
	public function UploadFile()
	{
		$oAccount = $this->getDefaultAccountFromParam();
		if (!$this->oApiCapability->isFilesSupported($oAccount))
		{
			throw new \Core\Exceptions\ClientException(\Core\Notifications::FilesNotAllowed);
		}

		$aFileData = $this->getParamValue('FileData', null);
		$sAdditionalData = $this->getParamValue('AdditionalData', '{}');
		$aAdditionalData = @json_decode($sAdditionalData, true);

		$sError = '';
		$aResponse = array();

		if ($oAccount)
		{
			if (is_array($aFileData))
			{
				$sUploadName = $aFileData['name'];
				$iSize = (int) $aFileData['size'];
				$sType = isset($aAdditionalData['Type']) ? $aAdditionalData['Type'] : 'personal';
				$sPath = isset($aAdditionalData['Path']) ? $aAdditionalData['Path'] : '';
				$sMimeType = \MailSo\Base\Utils::MimeContentType($sUploadName);

				$sSavedName = 'upload-post-'.md5($aFileData['name'].$aFileData['tmp_name']);
				if ($this->ApiFileCache()->moveUploadedFile($oAccount, $sSavedName, $aFileData['tmp_name']))
				{
					$rData = $this->ApiFileCache()->getFile($oAccount, $sSavedName);

					$this->oApiFilestorage->createFile($oAccount, $sType, $sPath, $sUploadName, $rData, false);

					$aResponse['File'] = array(
						'Name' => $sUploadName,
						'TempName' => $sSavedName,
						'MimeType' => $sMimeType,
						'Size' =>  (int) $iSize,
						'Hash' => \CApi::EncodeKeyValues(array(
							'TempFile' => true,
							'AccountID' => $oAccount->IdAccount,
							'Name' => $sUploadName,
							'TempName' => $sSavedName
						))
					);
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

		return $this->DefaultResponse($oAccount, __FUNCTION__, $aResponse);
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

		if ($oAccount)
		{
			if (is_array($aFileData))
			{
				$sUploadName = $aFileData['name'];
				$bIsEmlExtension  = strtolower(pathinfo($sUploadName, PATHINFO_EXTENSION)) === 'eml';

				if ($bIsEmlExtension) {
					$sFolder = isset($aAdditionalData['Folder']) ? $aAdditionalData['Folder'] : '';
					$sMimeType = \MailSo\Base\Utils::MimeContentType($sUploadName);

					$sSavedName = 'upload-post-' . md5($aFileData['name'] . $aFileData['tmp_name']);
					if ($this->ApiFileCache()->moveUploadedFile($oAccount, $sSavedName, $aFileData['tmp_name'])) {
						$sSavedFullName = $this->ApiFileCache()->generateFullFilePath($oAccount, $sSavedName);
						$this->oApiMail->appendMessageFromFile($oAccount, $sSavedFullName, $sFolder);

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

		return $this->DefaultResponse($oAccount, __FUNCTION__, $aResponse);
	}

	/**
	 * @return array
	 */
	public function AjaxDataAsAttachmentUpload()
	{
		$oAccount = $this->getAccountFromParam();
		$oSettings =& \CApi::GetSettings();
		
		$sData = $this->getParamValue('Data', '');
		$sFileName = $this->getParamValue('FileName', '');
		
		$sError = '';
		$aResponse = array();

		if ($oAccount)
		{
			$iSizeLimit = !!$oSettings->GetConf('WebMail/EnableAttachmentSizeLimit', false) ?
				(int) $oSettings->GetConf('WebMail/AttachmentSizeLimit', 0) : 0;

			$iSize = strlen($sData);
			if (0 < $iSizeLimit && $iSizeLimit < $iSize)
			{
				$sError = 'size';
			}
			else
			{
				$sSavedName = 'data-upload-'.md5($sFileName.microtime(true).rand(10000, 99999));
				if ($this->ApiFileCache()->put($oAccount, $sSavedName, $sData))
				{
					$aResponse['Attachment'] = array(
						'Name' => $sFileName,
						'TempName' => $sSavedName,
						'MimeType' => \MailSo\Base\Utils::MimeContentType($sFileName),
						'Size' =>  $iSize,
						'Hash' => \CApi::EncodeKeyValues(array(
							'TempFile' => true,
							'AccountID' => $oAccount->IdAccount,
							'Name' => $sFileName,
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
			$sError = 'auth';
		}

		if (0 < strlen($sError))
		{
			$aResponse['Error'] = $sError;
		}

		return $this->DefaultResponse($oAccount, __FUNCTION__, $aResponse);
	}

	/**
	 * @param bool $bDownload
	 * @param string $sContentType
	 * @param string $sFileName
	 *
	 * @return bool
	 */
	public function RawOutputHeaders($bDownload, $sContentType, $sFileName)
	{
		if ($bDownload)
		{
			header('Content-Type: '.$sContentType, true);
		}
		else
		{
			$aParts = explode('/', $sContentType, 2);
			if (in_array(strtolower($aParts[0]), array('image', 'video', 'audio')) ||
				in_array(strtolower($sContentType), array('application/pdf', 'application/x-pdf', 'text/html')))
			{
				header('Content-Type: '.$sContentType, true);
			}
			else
			{
				header('Content-Type: text/plain', true);
			}
		}

		header('Content-Disposition: '.($bDownload ? 'attachment' : 'inline' ).'; '.
			\trim(\MailSo\Base\Utils::EncodeHeaderUtf8AttributeValue('filename', $sFileName)), true);
		
		header('Accept-Ranges: none', true);
		header('Content-Transfer-Encoding: binary');
	}

	public function thumbResource($oAccount, $rResource, $sFileName)
	{
		$sMd5Hash = md5(rand(1000, 9999));

		$this->ApiFileCache()->putFile($oAccount, 'Raw/Thumbnail/'.$sMd5Hash, $rResource, '_'.$sFileName);
		if ($this->ApiFileCache()->isFileExists($oAccount, 'Raw/Thumbnail/'.$sMd5Hash, '_'.$sFileName))
		{
			try
			{
				$oThumb = new \PHPThumb\GD(
					$this->ApiFileCache()->generateFullFilePath($oAccount, 'Raw/Thumbnail/'.$sMd5Hash, '_'.$sFileName)
				);

				$oThumb->adaptiveResize(120, 100)->show();
			}
			catch (\Exception $oE) {}
		}

		$this->ApiFileCache()->clear($oAccount, 'Raw/Thumbnail/'.$sMd5Hash, '_'.$sFileName);
	}

	/**
	 * @return bool
	 */
	private function rawCallback($sRawKey, $fCallback, $bCache = true, &$oAccount = null, &$oHelpdeskUser = null)
	{
		$aValues = \CApi::DecodeKeyValues($sRawKey);
		
		$sFolder = '';
		$iUid = 0;
		$sMimeIndex = '';

		$oAccount = null;
		$oHelpdeskUser = null;
		$oHelpdeskUserFromAttachment = null;

		if (isset($aValues['HelpdeskUserID'], $aValues['HelpdeskTenantID']))
		{
			$oAccount = null;
			$oHelpdeskUser = $this->getHelpdeskAccountFromParam($oAccount);

			if ($oHelpdeskUser && $oHelpdeskUser->IdTenant === $aValues['HelpdeskTenantID'])
			{
				$oApiHelpdesk = $this->ApiHelpdesk();
				if ($oApiHelpdesk)
				{
					if ($oHelpdeskUser->IdHelpdeskUser === $aValues['HelpdeskUserID'])
					{
						$oHelpdeskUserFromAttachment = $oHelpdeskUser;
					}
					else if ($oHelpdeskUser->IsAgent)
					{
						$oHelpdeskUserFromAttachment = $oApiHelpdesk->getUserById($aValues['HelpdeskTenantID'], $aValues['HelpdeskUserID']);
					}
				}
			}
		}
		else if (isset($aValues['AccountID']))
		{
			$oAccount = $this->getAccountFromParam(true,
				!(isset($aValues['Iframed'], $aValues['Time']) && $aValues['Iframed'] && $aValues['Time'] > \Core\Base\Utils::iframedTimestamp())
			);
			
			if (!$oAccount || $aValues['AccountID'] !== $oAccount->IdAccount)
			{
				return false;
			}
		}

		if ($oHelpdeskUserFromAttachment && isset($aValues['FilestorageFile'], $aValues['StorageType'], $aValues['Path'], $aValues['Name']))
		{
			if ($bCache)
			{
				$this->verifyCacheByKey($sRawKey);
			}
			
			$bResult = false;
			$mResult = false;
			
			$sStorageType = $aValues['StorageType'];
			if (is_numeric($aValues['StorageType']))
			{
				$iStorageType = (int) $aValues['StorageType'];
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
				$sStorageType, $aValues['Path'], $aValues['Name']
			))
			{
				$mResult = $this->oApiFilestorage->getFile(
					$oHelpdeskUserFromAttachment,
					$sStorageType, $aValues['Path'], $aValues['Name']
				);
				if (is_resource($mResult))
				{
					if ($bCache)
					{
						$this->cacheByKey($sRawKey);
					}

					$bResult = true;
					$sFileName = $aValues['Name'];

					$sContentType = (empty($sFileName)) ? 'text/plain' : \MailSo\Base\Utils::MimeContentType($sFileName);
					$sFileName = $this->clearFileName($sFileName, $sContentType);

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
		else if (isset($aValues['TempFile'], $aValues['TempName'], $aValues['Name']) && ($oHelpdeskUserFromAttachment || $oAccount))
		{
			if ($bCache)
			{
				$this->verifyCacheByKey($sRawKey);
			}

			$bResult = false;
			$mResult = $this->ApiFileCache()->getFile($oHelpdeskUserFromAttachment ? $oHelpdeskUserFromAttachment : $oAccount, $aValues['TempName']);

			if (is_resource($mResult))
			{
				if ($bCache)
				{
					$this->cacheByKey($sRawKey);
				}

				$bResult = true;
				$sFileName = $aValues['Name'];
				$sContentType = (empty($sFileName)) ? 'text/plain' : \MailSo\Base\Utils::MimeContentType($sFileName);
				$sFileName = $this->clearFileName($sFileName, $sContentType);

				call_user_func_array($fCallback, array(
					$oAccount, $sContentType, $sFileName, $mResult, $oHelpdeskUser
				));
			}

			return $bResult;
		}
		else
		{
			$sFolder = isset($aValues['Folder']) ? $aValues['Folder'] : '';
			$iUid = (int) (isset($aValues['Uid']) ? $aValues['Uid'] : 0);
			$sMimeIndex = (string) (isset($aValues['MimeIndex']) ? $aValues['MimeIndex'] : '');
		}

		if ($bCache && 0 < strlen($sFolder) && 0 < $iUid)
		{
			$this->verifyCacheByKey($sRawKey);
		}

		$sContentTypeIn = (string) (isset($aValues['MimeType']) ? $aValues['MimeType'] : '');
		$sFileNameIn = (string) (isset($aValues['FileName']) ? $aValues['FileName'] : '');

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
			function($rResource, $sContentType, $sFileName, $sMimeIndex = '') use ($self, $oAccount, $fCallback, $sRawKey, $bCache, $sContentTypeIn, $sFileNameIn) {
				if (is_resource($rResource))
				{
					$sContentTypeOut = $sContentTypeIn;
					if (empty($sContentTypeOut))
					{
						$sContentTypeOut = $sContentType;
						if (empty($sContentTypeOut))
						{
							$sContentTypeOut = (empty($sFileName)) ? 'text/plain' : \MailSo\Base\Utils::MimeContentType($sFileName);
						}
					}

					$sFileNameOut = $sFileNameIn;
					if (empty($sFileNameOut) || '.' === $sFileNameOut{0})
					{
						$sFileNameOut = $sFileName;
					}

					$sFileNameOut = $self->clearFileName($sFileNameOut, $sContentType, $sMimeIndex);

					if ($bCache)
					{
						$self->cacheByKey($sRawKey);
					}

					call_user_func_array($fCallback, array(
						$oAccount, $sContentTypeOut, $sFileNameOut, $rResource
					));
				}
			}, $sFolder, $iUid, $sMimeIndex);
	}

	/**
	 * @return bool
	 */
	private function raw($bDownload = true, $bThumbnail = false)
	{
		$self = $this;
		return $this->rawCallback((string) $this->getParamValue('RawKey', ''), function ($oAccount, $sContentType, $sFileName, $rResource, $oHelpdeskUser = null) use ($self, $bDownload, $bThumbnail) {
			
			$self->RawOutputHeaders($bDownload, $sContentType, $sFileName);

			if (!$bDownload && 'text/html' === $sContentType)
			{
				$sHtml = stream_get_contents($rResource);
				if ($sHtml)
				{
					$sCharset = '';
					$aMacth = array();
					if (preg_match('/charset[\s]?=[\s]?([^\s"\']+)/i', $sHtml, $aMacth) && !empty($aMacth[1]))
					{
						$sCharset = $aMacth[1];
					}

					if ('' !== $sCharset && \MailSo\Base\Enumerations\Charset::UTF_8 !== $sCharset)
					{
						$sHtml = \MailSo\Base\Utils::ConvertEncoding($sHtml,
							\MailSo\Base\Utils::NormalizeCharset($sCharset, true), \MailSo\Base\Enumerations\Charset::UTF_8);
					}

					include_once PSEVEN_APP_ROOT_PATH.'libraries/other/CssToInlineStyles.php';

					$oCssToInlineStyles = new \TijsVerkoyen\CssToInlineStyles\CssToInlineStyles($sHtml);
					$oCssToInlineStyles->setEncoding('utf-8');
					$oCssToInlineStyles->setUseInlineStylesBlock(true);

					echo '<html><head></head><body>'.
						\MailSo\Base\HtmlUtils::ClearHtmlSimple($oCssToInlineStyles->convert(), true, true).
						'</body></html>';
				}
			}
			else
			{
				if ($bThumbnail && !$bDownload)
				{
					$self->thumbResource($oAccount ? $oAccount : $oHelpdeskUser, $rResource, $sFileName);
				}
				else
				{
					\MailSo\Base\Utils::FpassthruWithTimeLimitReset($rResource);
				}
			}
			
		}, !$bDownload);
	}

	/**
	 * @return bool
	 */
	public function RawIframe()
	{
		$sEncodedUrl = $this->getParamValue('RawKey', '');
		$sUrl = urldecode($sEncodedUrl);
		$sUrl = trim(trim($sUrl), '/\\?');

		$aParts = null;
		if (!empty($sUrl))
		{
			$aParts = explode('/', $sUrl);
		}

		if (is_array($aParts) && isset($aParts[0], $aParts[1], $aParts[2], $aParts[3]))
		{
			$aValues = \CApi::DecodeKeyValues($aParts[3]);
			
			if (isset($aValues['Iframed'], $aValues['Name'], $aValues['AccountID']) &&
				(!isset($aValues['MimeType']) || !isset($aValues['FileName']))
			)
			{
				$aValues['FileName'] = $aValues['Name'];
				$aValues['MimeType'] = \api_Utils::MimeContentType($aValues['FileName']);
			}

			if (isset($aValues['Iframed'], $aValues['MimeType'], $aValues['FileName']) && $aValues['Iframed'] &&
				\CApi::isIframedMimeTypeSupported($aValues['MimeType'], $aValues['FileName']))
			{
				$oAccount = $this->getAccountFromParam(false);
				if ($oAccount)
				{
					$sNewUrl = '';
					$sNewHash = '';
					$sResultUrl = '';
					
					$aSubParts = \CApi::DecodeKeyValues($aParts[3]);
					if (isset($aSubParts['Iframed']) && (int) $aParts[2] === (int) $oAccount->IdAccount &&
						0 < $oAccount->IdAccount)
					{
						$aSubParts['Time'] = \time();
						$sNewHash = \CApi::EncodeKeyValues($aSubParts);
					}

					if (!empty($sNewHash))
					{
						$aParts[3] = $sNewHash;
						$sNewUrl = rtrim(trim($this->oHttp->GetFullUrl()), '/').'/?/'.implode('/', $aParts);

						\CApi::Plugin()->RunHook('webmail.filter.iframed-attachments-url', array(&$sResultUrl, $sNewUrl, $aValues['MimeType'], $aValues['FileName']));

						if (empty($sResultUrl) && \CApi::GetConf('labs.allow-officeapps-viewer', true))
						{
							$sResultUrl = 'https://view.officeapps.live.com/op/view.aspx?src='.urlencode($sNewUrl);
						}
					}

					if (!empty($sResultUrl))
					{
						header('Content-Type: text/html', true);

						echo '<html style="height: 100%; width: 100%; margin: 0; padding: 0"><head></head><body'.
							' style="height: 100%; width: 100%; margin: 0; padding: 0">'.
							'<iframe style="height: 100%; width: 100%; margin: 0; padding: 0; border: 0" src="'.$sResultUrl.'"></iframe></body></html>';

						return true;
					}
				}
			}
		}
		
		return false;
	}

	/**
	 * @return bool
	 */
	public function RawView()
	{
		return $this->raw(false);
	}

	/**
	 * @return bool
	 */
	public function RawDownload()
	{
		return $this->raw(true);
	}

	/**
	 * @return bool
	 */
	public function RawThumbnail()
	{
		return $this->raw(false, true);
	}

	/**
	 * @param string $sFileName
	 * @param string $sContentType
	 * @param string $sMimeIndex = ''
	 *
	 * @return string
	 */
	public function clearFileName($sFileName, $sContentType, $sMimeIndex = '')
	{
		$sFileName = 0 === strlen($sFileName) ? preg_replace('/[^a-zA-Z0-9]/', '.', (empty($sMimeIndex) ? '' : $sMimeIndex.'.').$sContentType) : $sFileName;
		$sClearedFileName = preg_replace('/[\s]+/', ' ', preg_replace('/[\.]+/', '.', $sFileName));
		$sExt = \MailSo\Base\Utils::GetFileExtension($sClearedFileName);

		$iSize = 100;
		if ($iSize < strlen($sClearedFileName) - strlen($sExt))
		{
			$sClearedFileName = substr($sClearedFileName, 0, $iSize).(empty($sExt) ? '' : '.'.$sExt);
		}

		return \MailSo\Base\Utils::ClearFileName(\MailSo\Base\Utils::Utf8Clear($sClearedFileName));
	}

	/**
	 * @param string $sKey
	 *
	 * @return void
	 */
	public function cacheByKey($sKey)
	{
		if (!empty($sKey))
		{
			$iUtcTimeStamp = time();
			$iExpireTime = 3600 * 24 * 5;

			header('Cache-Control: private', true);
			header('Pragma: private', true);
			header('Etag: '.md5('Etag:'.md5($sKey)), true);
			header('Last-Modified: '.gmdate('D, d M Y H:i:s', $iUtcTimeStamp - $iExpireTime).' UTC', true);
			header('Expires: '.gmdate('D, j M Y H:i:s', $iUtcTimeStamp + $iExpireTime).' UTC', true);
		}
	}

	/**
	 * @param string $sKey
	 *
	 * @return void
	 */
	public function verifyCacheByKey($sKey)
	{
		if (!empty($sKey))
		{
			$sIfModifiedSince = $this->oHttp->GetHeader('If-Modified-Since', '');
			if (!empty($sIfModifiedSince))
			{
				$this->oHttp->StatusHeader(304);
				$this->cacheByKey($sKey);
				exit();
			}
		}
	}

	/**
	 * @param CAccount $oAccount
	 *
	 * @return array|null
	 */
	private function mobileSyncSettings($oAccount)
	{
		$mResult = null;
		$oApiDavManager = \CApi::Manager('dav');

		if ($oAccount && $oApiDavManager)
		{
			$oApiCapabilityManager = \CApi::Manager('capability');
			/* @var $oApiCapabilityManager \CApiCapabilityManager */

			$oApiCalendarManager = \CApi::Manager('calendar');

			$bEnableMobileSync = $oApiCapabilityManager->isMobileSyncSupported($oAccount);

			$mResult = array();

			$mResult['EnableDav'] = $bEnableMobileSync;

			$sDavLogin = $oApiDavManager->getLogin($oAccount);
			$sDavServer = $oApiDavManager->getServerUrl();

			$mResult['Dav'] = null;
			$mResult['ActiveSync'] = null;
			$mResult['DavError'] = '';

			$oException = $oApiDavManager->GetLastException();
			if (!$oException)
			{
				if ($bEnableMobileSync)
				{
					$mResult['Dav'] = array();
					$mResult['Dav']['Login'] = $sDavLogin;
					$mResult['Dav']['Server'] = $sDavServer;
					$mResult['Dav']['PrincipalUrl'] = '';

					$sPrincipalUrl = $oApiDavManager->getPrincipalUrl($oAccount);
					if ($sPrincipalUrl)
					{
						$mResult['Dav']['PrincipalUrl'] = $sPrincipalUrl;
					}

					$mResult['Dav']['Calendars'] = array();

					$aCalendars = $oApiCalendarManager ? $oApiCalendarManager->getCalendars($oAccount) : null;

//					if (isset($aCalendars['user']) && is_array($aCalendars['user']))
//					{
//						foreach($aCalendars['user'] as $aCalendar)
//						{
//							if (isset($aCalendar['name']) && isset($aCalendar['url']))
//							{
//								$mResult['Dav']['Calendars'][] = array(
//									'Name' => $aCalendar['name'],
//									'Url' => $sDavServer.$aCalendar['url']
//								);
//							}
//						}
//					}

					if (is_array($aCalendars) && 0 < count($aCalendars))
					{
						foreach($aCalendars as $aCalendar)
						{
							if (isset($aCalendar['Name']) && isset($aCalendar['Url']))
							{
								$mResult['Dav']['Calendars'][] = array(
									'Name' => $aCalendar['Name'],
									'Url' => $sDavServer.$aCalendar['Url']
								);
							}
						}
					}

					$mResult['Dav']['PersonalContactsUrl'] = $sDavServer.'/addressbooks/'.$sDavLogin.'/'.\afterlogic\DAV\Constants::ADDRESSBOOK_DEFAULT_NAME;
					$mResult['Dav']['CollectedAddressesUrl'] = $sDavServer.'/addressbooks/'.$sDavLogin.'/'.\afterlogic\DAV\Constants::ADDRESSBOOK_COLLECTED_NAME;
					$mResult['Dav']['SharedWithAllUrl'] = $sDavServer.'/addressbooks/'.$sDavLogin.'/'.\afterlogic\DAV\Constants::ADDRESSBOOK_SHARED_WITH_ALL_NAME;
					$mResult['Dav']['GlobalAddressBookUrl'] = $sDavServer.'/gab';
				}
			}
			else
			{
				$mResult['DavError'] = $oException->getMessage();
			}
		}

		return $mResult;
	}

	/**
	 * @param CAccount $oAccount
	 *
	 * @return array|null
	 */
	private function outlookSyncSettings($oAccount)
	{
		$mResult = null;
		if ($oAccount && $this->oApiCapability->isOutlookSyncSupported($oAccount))
		{
			/* @var $oApiDavManager \CApiDavManager */
			$oApiDavManager = \CApi::Manager('dav');

			$sLogin = $oApiDavManager->getLogin($oAccount);
			$sServerUrl = $oApiDavManager->getServerUrl();

			$mResult = array();
			$mResult['Login'] = '';
			$mResult['Server'] = '';
			$mResult['DavError'] = '';

			$oException = $oApiDavManager->GetLastException();
			if (!$oException)
			{
				$mResult['Login'] = $sLogin;
				$mResult['Server'] = $sServerUrl;
			}
			else
			{
				$mResult['DavError'] = $oException->getMessage();
			}
		}

		return $mResult;
	}
	
	public function AjaxSocialRegister()
	{
		$sTenantHash = trim($this->getParamValue('TenantHash', ''));
		if ($this->oApiCapability->isHelpdeskSupported())
		{
			$sNotificationEmail = trim($this->getParamValue('NotificationEmail', ''));
			if(isset($_COOKIE["p7social"]))
			{
				$aSocial = \CApi::DecodeKeyValues($_COOKIE["p7social"]);
			}
			else
			{
				$aSocial = array(
					'type' => '',
					'id' => '',
					'name' => '',
					'email' => ''
				);
			}
			$sSocialType = $aSocial['type'];
			$sSocialId = $aSocial['id'];
			$sSocialName = $aSocial['name'];
			$sEmail = $aSocial['email'];

			if (0 !== strlen($sEmail))
			{
				$sNotificationEmail = $sEmail;
			}

			if (0 === strlen($sNotificationEmail))
			{
				throw new \Core\Exceptions\ClientException(\Core\Notifications::InvalidInputParameter);
			}

			$mIdTenant = $this->oApiTenants->getTenantIdByHash($sTenantHash);
			if (!is_int($mIdTenant))
			{
				throw new \Core\Exceptions\ClientException(\Core\Notifications::InvalidInputParameter);
			}

			$bResult = false;
			try
			{
				$bResult = $this->oApiIntegrator->registerSocialAccount($mIdTenant, $sTenantHash, $sNotificationEmail, $sSocialId, $sSocialType, $sSocialName);
			}
			catch (\Exception $oException)
			{
				$iErrorCode = \Core\Notifications::UnknownError;
				if ($oException instanceof \CApiManagerException)
				{
					switch ($oException->getCode())
					{
						case \Errs::HelpdeskManager_UserAlreadyExists:
							$iErrorCode = \Core\Notifications::HelpdeskUserAlreadyExists;
							break;
						case \Errs::HelpdeskManager_UserCreateFailed:
							$iErrorCode = \Core\Notifications::CanNotCreateHelpdeskUser;
							break;
						case \Errs::Db_ExceptionError:
							$iErrorCode = \Core\Notifications::DataBaseError;
							break;
					}
				}

				throw new \Core\Exceptions\ClientException($iErrorCode);
			}

			if ($bResult)
			{
				$bResult = false;
				$oUser = \CApi::Manager('integrator')->getAhdSocialUser($sTenantHash, $sSocialId);
				if ($oUser)
				{
					\CApi::Manager('integrator')->setHelpdeskUserAsLoggedIn($oUser, false);
					$bResult = true;
				}
			}

			return $this->DefaultResponse(null, __FUNCTION__, $bResult);
		}

		return $this->FalseResponse(null, __FUNCTION__);
	}
	
	public function AjaxSocialAccountGet()
	{
		$mResult = false;
		$oTenant = null;
		$oAccount = $this->GetDefaultAccount();
		$sType = trim($this->getParamValue('Type', ''));
		
		if ($oAccount && $this->oApiTenants)
		{
			$oTenant = (0 < $oAccount->IdTenant) ? $this->oApiTenants->getTenantById($oAccount->IdTenant) :
				$this->oApiTenants->getDefaultGlobalTenant();
		}
		if ($oTenant)
		{
			$oApiSocial /* @var $oApiSocial \CApiSocialManager */ = \CApi::Manager('social');
			$mResult = $oApiSocial->getSocial($oAccount->IdAccount, $sType);
		}
		return $this->DefaultResponse(null, __FUNCTION__, $mResult);
	}	
	
	/**
	 * @return array
	 */
	public function UploadHelpdeskFile()
	{
		$oAccount = null;
		$oUser = $this->getHelpdeskAccountFromParam($oAccount);

		if (!$this->oApiCapability->isHelpdeskSupported() || !$this->oApiCapability->isFilesSupported())
		{
			throw new \Core\Exceptions\ClientException(\Core\Notifications::AccessDenied);
		}

		$aFileData = $this->getParamValue('FileData', null);

		$iSizeLimit = 0;

		$sError = '';
		$aResponse = array();

		if ($oUser)
		{
			if (is_array($aFileData))
			{
				if (0 < $iSizeLimit && $iSizeLimit < (int) $aFileData['size'])
				{
					$sError = 'size';
				}
				else
				{
					$sSavedName = 'upload-post-'.md5($aFileData['name'].$aFileData['tmp_name']);
					if ($this->ApiFileCache()->moveUploadedFile($oUser, $sSavedName, $aFileData['tmp_name']))
					{
						$sUploadName = $aFileData['name'];
						$iSize = $aFileData['size'];
						$sMimeType = \MailSo\Base\Utils::MimeContentType($sUploadName);

						$aResponse['HelpdeskFile'] = array(
							'Name' => $sUploadName,
							'TempName' => $sSavedName,
							'MimeType' => $sMimeType,
							'Size' =>  (int) $iSize,
							'Hash' => \CApi::EncodeKeyValues(array(
								'TempFile' => true,
								'HelpdeskTenantID' => $oUser->IdTenant,
								'HelpdeskUserID' => $oUser->IdHelpdeskUser,
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

		return $this->DefaultResponse($oAccount, __FUNCTION__, $aResponse);
	}
}
