<?php

use \Modules\SimpleChat\CAccount;

class SimpleChatModule extends AApiModule
{
	public $oCurrentAccount = null;
	
	public $oCurrentUser = null;
	
	public $oMainManager = null;
	
	public $oAccountsManager = null;
	
	public $oCoreDecorator = null;
	
	public $oAuthDecorator = null;
	
	public function init() 
	{
		$this->oMainManager = $this->GetManager('main', 'db');
		$this->oAccountsManager = $this->GetManager('accounts', 'db');
		
		$this->oCoreDecorator = \CApi::GetModuleDecorator('Core');
		$this->oAuthDecorator = \CApi::GetModuleDecorator('Auth');
				
//		$this->setObjectMap('CUser', array(
//				'HelpdeskSignature'					=> array('string', ''), //'helpdesk_signature'),
//				'HelpdeskSignatureEnable'			=> array('bool', true), //'helpdesk_signature_enable'),
//				'AllowHelpdeskNotifications'		=> array('bool', false), //'allow_helpdesk_notifications')
//			)
//		);
		
		$this->setObjectMap('CTenant', array(
				'AdminEmail'		=> array('string', ''),
				'AdminEmailAccount'	=> array('string', ''),
				'ClientIframeUrl'	=> array('string', ''),
				'AgentIframeUrl'	=> array('string', ''),
				'SiteName'			=> array('string', ''),
				'StyleAllow'		=> array('bool', false),
				'StyleImage'		=> array('string', ''),
				'FetcherType'		=> array('int', EHelpdeskFetcherType::NONE),
				'StyleText'			=> array('string', ''),
				'AllowFetcher'		=> array('bool', false),
				'FetcherTimer' => array('int', 0)
			
//			'HelpdeskFacebookAllow'		=> array('bool', false, false), //!!$oSettings->GetConf('Helpdesk/FacebookAllow')
//			'HelpdeskFacebookId'		=> array('string', '', false), //(string) $oSettings->GetConf('Helpdesk/FacebookId')
//			'HelpdeskFacebookSecret'	=> array('string', '', false), //(string) $oSettings->GetConf('Helpdesk/FacebookSecret')
//			'HelpdeskGoogleAllow'		=> array('bool', false, false), //!!$oSettings->GetConf('Helpdesk/GoogleAllow')
//			'HelpdeskGoogleId'			=> array('string', '', false), //(string) $oSettings->GetConf('Helpdesk/GoogleId')
//			'HelpdeskGoogleSecret'		=> array('string', '', false), //(string) $oSettings->GetConf('Helpdesk/GoogleSecret')
//			'HelpdeskTwitterAllow'		=> array('bool', false, false), //!!$oSettings->GetConf('Helpdesk/TwitterAllow')
//			'HelpdeskTwitterId'			=> array('string', '', false), //(string) $oSettings->GetConf('Helpdesk/TwitterId')
//			'HelpdeskTwitterSecret'		=> array('string', '', false), //(string) $oSettings->GetConf('Helpdesk/TwitterSecret')
			)
		);
		
//		$this->subscribeEvent('HelpDesk::Login', array($this, 'checkAuth'));
	}
	
	/**
	 * TODO it must set extended properties of tenant
	 * temp method
	 */
	public function setInheritedSettings()
	{
//		$oSettings =& CApi::GetSettings();
//		$oMap = $this->getStaticMap();
		
//		if (isset($oMap['HelpdeskFacebookAllow'][2]) && !$oMap['HelpdeskFacebookAllow'][2])
//		{
//			$this->HelpdeskFacebookAllow = !!$oSettings->GetConf('Helpdesk/FacebookAllow');
//		}
//		
//		if (isset($oMap['HelpdeskFacebookId'][2]) && !$oMap['HelpdeskFacebookId'][2])
//		{
//			$this->HelpdeskFacebookId = (string) $oSettings->GetConf('Helpdesk/FacebookId');
//		}
//		
//		if (isset($oMap['HelpdeskFacebookSecret'][2]) && !$oMap['HelpdeskFacebookSecret'][2])
//		{
//			$this->HelpdeskFacebookSecret = (string) $oSettings->GetConf('Helpdesk/FacebookSecret');
//		}
//		
//		if (isset($oMap['HelpdeskGoogleAllow'][2]) && !$oMap['HelpdeskGoogleAllow'][2])
//		{
//			$this->HelpdeskGoogleAllow = !!$oSettings->GetConf('Helpdesk/GoogleAllow');
//		}
//		
//		if (isset($oMap['HelpdeskGoogleId'][2]) && !$oMap['HelpdeskGoogleId'][2])
//		{
//			$this->HelpdeskGoogleId = (string) $oSettings->GetConf('Helpdesk/GoogleId');
//		}
//		
//		if (isset($oMap['HelpdeskGoogleSecret'][2]) && !$oMap['HelpdeskGoogleSecret'][2])
//		{
//			$this->HelpdeskGoogleSecret = (string) $oSettings->GetConf('Helpdesk/GoogleSecret');
//		}
//		
//		if (isset($oMap['HelpdeskTwitterAllow'][2]) && !$oMap['HelpdeskTwitterAllow'][2])
//		{
//			$this->HelpdeskTwitterAllow = !!$oSettings->GetConf('Helpdesk/TwitterAllow');
//		}
//		
//		if (isset($oMap['HelpdeskTwitterId'][2]) && !$oMap['HelpdeskTwitterId'][2])
//		{
//			$this->HelpdeskTwitterId = (string) $oSettings->GetConf('Helpdesk/TwitterId');
//		}
//		
//		if (isset($oMap['HelpdeskTwitterSecret'][2]) && !$oMap['HelpdeskTwitterSecret'][2])
//		{
//			$this->HelpdeskTwitterSecret = (string) $oSettings->GetConf('Helpdesk/TwitterSecret');
//		}
	}
	
	protected function GetCurrentAccount()
	{
		$iUserId = \CApi::getLogginedUserId();
	
		if (!$this->oCurrentAccount && $iUserId)
		{
			$this->oCurrentAccount = $this->oAccountsManager->getAccountByUserId($iUserId);
		}
		
		return $this->oCurrentAccount;
	}
	
	protected function GetCurrentUser()
	{
		$iUserId = \CApi::getLogginedUserId();
	
		if (!$this->oCurrentUser && $iUserId)
		{
			$this->oCurrentUser = $this->oCoreDecorator->GetUser($iUserId);
		}
		
		return $this->oCurrentUser;
	}
	/**
	 * @param bool $bThrowAuthExceptionOnFalse Default value is **true**.
	 *
	 * @return \CHelpdeskUser|null
	 */
	protected function getHelpdeskAccountFromParam($bThrowAuthExceptionOnFalse = true)
	{
		$iUserId = \CApi::getLogginedUserId();
		$oUser = $this->oCoreDecorator ? $this->oCoreDecorator->GetUser($iUserId) : null;

		return $oUser;
		
		/*$oResult = null;
		$oAccount = null;

		if ('0' === (string) $this->getParamValue('IsExt', '1'))
		{
			$oAccount = $this->getDefaultAccountFromParam($bThrowAuthExceptionOnFalse);
			if ($oAccount && $this->oApiCapabilityManager->isHelpdeskSupported($oAccount))
			{
				$oResult = $this->getHelpdeskAccountFromMainAccount($oAccount);
			}
		}
		else
		{
//			$oApiTenants = \CApi::GetCoreManager('tenants');
//			$mTenantID = $oApiTenants->getTenantIdByHash($this->getParamValue('TenantHash', ''));
			$mTenantID = $this->oCoreDecorator->getTenantIdByHash($this->getParamValue('TenantHash', ''));

			if (is_int($mTenantID))
			{
				$oResult = \api_Utils::GetHelpdeskAccount($mTenantID);
			}
		}

		if (!$oResult && $bThrowAuthExceptionOnFalse)
		{
			throw new \Core\Exceptions\ClientException(\Core\Notifications::UnknownError);
		}

		return $oResult;*/
	}
	
	/**
	 * @param \CAccount $oAccount
	 * 
	 * @return \CHelpdeskUser|null
	 */
	protected function getHelpdeskAccountFromMainAccount(&$oAccount)
	{
		$oResult = null;
		$oApiUsers = CApi::GetCoreManager('users');
		if ($oAccount && $oAccount->IsDefaultAccount && $this->oApiCapabilityManager->isHelpdeskSupported($oAccount))
		{
			if (0 < $oAccount->User->IdHelpdeskUser)
			{
				$oHelpdeskUser = $this->oMainManager->getUserById($oAccount->IdTenant, $oAccount->User->IdHelpdeskUser);
				$oResult = $oHelpdeskUser instanceof \CHelpdeskUser ? $oHelpdeskUser : null;
			}

			if (!($oResult instanceof \CHelpdeskUser))
			{
				$oHelpdeskUser = $this->oMainManager->getUserByEmail($oAccount->IdTenant, $oAccount->Email);
				$oResult = $oHelpdeskUser instanceof \CHelpdeskUser ? $oHelpdeskUser : null;
				
				if ($oResult instanceof \CHelpdeskUser)
				{
					$oAccount->User->IdHelpdeskUser = $oHelpdeskUser->IdHelpdeskUser;
					$oApiUsers->updateAccount($oAccount);
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

				if ($this->oMainManager->createUser($oHelpdeskUser))
				{
					$oAccount->User->IdHelpdeskUser = $oHelpdeskUser->IdHelpdeskUser;
					$oApiUsers->updateAccount($oAccount);

					$oResult = $oHelpdeskUser;
				}
			}
		}

		return $oResult;
	}	
	
	public function Login($sLogin = '', $sPassword = '', $bSignMe = 0)
	{
		setcookie('aft-cache-ctrl', '', time() - 3600);
		$sTenantHash = \CApi::getTenantHash();
		if ($this->oApiCapabilityManager->isHelpdeskSupported())
		{
			$sEmail = trim($sLogin);
			$sPassword = trim($sPassword);
			$bSignMe = '1' === (string) $bSignMe;

			if (0 === strlen($sEmail) || 0 === strlen($sPassword))
			{
				throw new \Core\Exceptions\ClientException(\Core\Notifications::InvalidInputParameter);
			}
			
			$mIdTenant = $this->oCoreDecorator ? $this->oCoreDecorator->getTenantIdByHash($sTenantHash) : null;

			if (!is_int($mIdTenant))
			{
				throw new \Core\Exceptions\ClientException(\Core\Notifications::InvalidInputParameter);
			}

			try
			{
//				$oApiIntegrator = \CApi::GetCoreManager('integrator');
//				$oHelpdeskUser = $oApiIntegrator->loginToHelpdeskAccount($mIdTenant, $sEmail, $sPassword);
//				if ($oHelpdeskUser && !$oHelpdeskUser->Blocked)
//				{
//					$oApiIntegrator->setHelpdeskUserAsLoggedIn($oHelpdeskUser, $bSignMe);
//					return true;
//				}
				
				$mResult = null;
				
				$this->broadcastEvent('Login', array(
					'login' => $sLogin,
					'password' => $sPassword,
					'result' => &$mResult
				));
				
				if (is_array($mResult))
				{
					$aAccountHashTable = $mResult;

		//			$iTime = $bSignMe ? time() + 60 * 60 * 24 * 30 : 0;
					$sAccountHashTable = \CApi::EncodeKeyValues($aAccountHashTable);

					$sAuthToken = \md5(\microtime(true).\rand(10000, 99999));

					$sAuthToken = \CApi::Cacher()->Set('AUTHTOKEN:'.$sAuthToken, $sAccountHashTable) ? $sAuthToken : '';

					return array(
						'AuthToken' => $sAuthToken
					);
				}
			}
			catch (\Exception $oException)
			{
				$iErrorCode = \Core\Notifications::UnknownError;
				if ($oException instanceof \CApiManagerException)
				{
					switch ($oException->getCode())
					{
						case \Errs::HelpdeskManager_AccountSystemAuthentication:
							$iErrorCode = \Core\Notifications::HelpdeskSystemUserExists;
							break;
						case \Errs::HelpdeskManager_AccountAuthentication:
							$iErrorCode = \Core\Notifications::AuthError;
							break;
						case \Errs::HelpdeskManager_UnactivatedUser:
							$iErrorCode = \Core\Notifications::HelpdeskUnactivatedUser;
							break;
						case \Errs::Db_ExceptionError:
							$iErrorCode = \Core\Notifications::DataBaseError;
							break;
					}
				}

				throw new \Core\Exceptions\ClientException($iErrorCode);
			}
		}

		return false;
	}

	public function Logout()
	{
		setcookie('aft-cache-ctrl', '', time() - 3600);
		if ($this->oApiCapabilityManager->isHelpdeskSupported())
		{
			$oApiIntegrator = \CApi::GetCoreManager('integrator');
			$oApiIntegrator->logoutHelpdeskUser();
		}

		return true;
	}	
	
	public function Register($sLogin = '', $sPassword = '', $sName = '', $bIsExt = false)
	{
		$sTenantHash = \CApi::getTenantHash();
//		if ($this->oApiCapabilityManager->isHelpdeskSupported())
//		{
			$sLogin = trim($sLogin);
			$sName = trim($sName);
			$sPassword = trim($sPassword);

			if (0 === strlen($sLogin) || 0 === strlen($sPassword))
			{
				throw new \Core\Exceptions\ClientException(\Core\Notifications::InvalidInputParameter);
			}

			$mIdTenant = $this->oCoreDecorator ? $this->oCoreDecorator->getTenantIdByHash($sTenantHash) : null;
			if (!is_int($mIdTenant))
			{
				throw new \Core\Exceptions\ClientException(\Core\Notifications::InvalidInputParameter);
			}
			
			$bResult = false;
			try
			{
				$oEventResult = null;
				$iUserId = \CApi::getLogginedUserId();
				
				$this->broadcastEvent('CreateAccount', array(
					'IdTenant' => $mIdTenant,
					'IdUser' => $iUserId,
					'login' => $sLogin,
					'password' => $sPassword,
					'result' => &$oEventResult
				));
				
				if ($oEventResult instanceOf \CUser)
				{
					//Create account for auth
					$oAuthAccount = \CAccount::createInstance('HelpDesk');
					$oAuthAccount->IdUser = $oEventResult->iObjectId;
					$oAuthAccount->Login = $sLogin;
					$oAuthAccount->Password = $sPassword;
					
					if ($this->oAuthDecorator->SaveAccount($oAuthAccount))
					{
						//Create propertybag account
						$oAccount = \Modules\HelpDesk\CAccount::createInstance();
						$oAccount->IdUser = $oEventResult->iObjectId;
						$oAccount->NotificationEmail = $sLogin ? $sLogin : '';

						$bResult = $this->oAccountsManager->createAccount($oAccount);
					}
					else
					{
						$this->oAuthDecorator->DeleteAccount($oAuthAccount);
					}

					return $bResult;
				}
				else
				{
					throw new \Core\Exceptions\ClientException(\Core\Notifications::NonUserPassed);
				}
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

			return $bResult;
//		}

//		return false;
	}	
	
	/**
	 * @return array
	 */
	public function IsAgent(\CUser $oUser)
	{
		return $this->oMainManager->isAgent($oUser);
	}	
	
	public function Forgot($sEmail = '', $bIsExt = false)
	{
		$sTenantHash = \CApi::getTenantHash();
		if ($this->oApiCapabilityManager->isHelpdeskSupported())
		{
			$sEmail = trim($sEmail);

			if (0 === strlen($sEmail))
			{
				throw new \Core\Exceptions\ClientException(\Core\Notifications::InvalidInputParameter);
			}

			$mIdTenant = $this->oCoreDecorator ? $this->oCoreDecorator->getTenantIdByHash($sTenantHash) : null;

			if (!is_int($mIdTenant))
			{
				throw new \Core\Exceptions\ClientException(\Core\Notifications::InvalidInputParameter);
			}

			$oAccount = $this->oAccountsManager->getAccountByEmail($mIdTenant, $sEmail);
			
			if (!($oAccount instanceof \Modules\HelpDesk\CAccount))
			{
				throw new \Core\Exceptions\ClientException(\Core\Notifications::HelpdeskUnknownUser);
			}

//			return $this->oMainManager->forgotUser($oAccount);
			
			$oFromAccount = null;
			
			$aData = $this->oMainManager->getHelpdeskMainSettings($mIdTenant);

			if (!empty($aData['AdminEmailAccount']))
			{
				$oApiUsers = $this->_getApiUsers();
				if ($oApiUsers)
				{
					$oFromAccount = $oApiUsers->getAccountByEmail($aData['AdminEmailAccount']);
				}
			}

			$sSiteName = isset($aData['SiteName']) ? $aData['SiteName'] : '';

			if ($oFromAccount)
			{
				$oApiMail = $this->oMainManager->_getApiMail();
				if ($oApiMail)
				{
					$sEmail = $oAccount->getNotificationEmail();
					if (!empty($sEmail))
					{
						$oFromEmail = \MailSo\Mime\Email::NewInstance($oFromAccount->Email, $sSiteName);
						$oToEmail = \MailSo\Mime\Email::NewInstance($sEmail, $oAccount->Name);

						$oUserMessage = $this->oMainManager->_buildUserMailMail(PSEVEN_APP_ROOT_PATH.'templates/helpdesk/user.forgot.html',
							$oFromEmail->ToString(), $oToEmail->ToString(),
							'Forgot', '', '', $oAccount, $sSiteName);

						$oApiMail->sendMessage($oFromAccount, $oUserMessage);
					}
				}
			}
		}
		
		return false;
	}	
	
	public function ForgotChangePassword()
	{
		$sTenantHash = \CApi::getTenantHash();
		if ($this->oApiCapabilityManager->isHelpdeskSupported())
		{
			$sActivateHash = \trim($this->getParamValue('ActivateHash', ''));
			$sNewPassword = \trim($this->getParamValue('NewPassword', ''));

			if (0 === strlen($sNewPassword) || 0 === strlen($sActivateHash))
			{
				throw new \Core\Exceptions\ClientException(\Core\Notifications::InvalidInputParameter);
			}

			$oApiTenants = \CApi::GetCoreManager('tenants');
			$mIdTenant = $oApiTenants->getTenantIdByHash($sTenantHash);
			if (!is_int($mIdTenant))
			{
				throw new \Core\Exceptions\ClientException(\Core\Notifications::InvalidInputParameter);
			}

			$oUser = $this->oMainManager->getUserByActivateHash($mIdTenant, $sActivateHash);
			if (!($oUser instanceof \CHelpdeskUser))
			{
				throw new \Core\Exceptions\ClientException(\Core\Notifications::HelpdeskUnknownUser);
			}

			$oUser->Activated = true;
			$oUser->setPassword($sNewPassword);
			$oUser->regenerateActivateHash();

			return $this->oMainManager->updateUser($oUser);
		}

		return false;
	}	
	
	public function CreatePost($iThreadId = 0, $sIsInternal = '0', $sSubject = '', $sText = '', $sCc = '', $sBcc = '', $mAttachments = null, $iIsExt = 0)
	{
		$oUser = $this->GetCurrentUser();
		
		/* @var $oAccount CAccount */

//		$iThreadId = (int) $this->getParamValue('ThreadId', 0);
//		$sSubject = trim((string) $this->getParamValue('Subject', ''));
//		$sText = trim((string) $this->getParamValue('Text', ''));
//		$sCc = trim((string) $this->getParamValue('Cc', ''));
//		$sBcc = trim((string) $this->getParamValue('Bcc', ''));
//		$bIsInternal = '1' === (string) $this->getParamValue('IsInternal', '0');
//		$mAttachments = $this->getParamValue('Attachments', null);
		
		$bIsInternal = '1' === $sIsInternal;
		
		if (0 === strlen($sText) || (0 === $iThreadId && 0 === strlen($sSubject)))
		{
			throw new \Core\Exceptions\ClientException(\Core\Notifications::InvalidInputParameter);
		}

		$mResult = false;
		$bIsNew = false;

		$oThread = null;
		if (0 === $iThreadId)
		{
			$bIsNew = true;
			
			$oThread = new \CHelpdeskThread();
			$oThread->IdTenant = $oUser->IdTenant;
			$oThread->IdOwner = $oUser->iObjectId;
			$oThread->Type = \EHelpdeskThreadType::Pending;
			$oThread->Subject = $sSubject;

			if (!$this->oMainManager->createThread($oUser, $oThread))
			{
				$oThread = null;
			}
		}
		else
		{
			$oThread = $this->oMainManager->getThreadById($oUser, $iThreadId);
		}

		if ($oThread && 0 < $oThread->IdHelpdeskThread)
		{
			$oPost = new \CHelpdeskPost();
			$oPost->IdTenant = $oUser->IdTenant;
			$oPost->IdOwner = $oUser->iObjectId;
			$oPost->IdHelpdeskThread = $oThread->IdHelpdeskThread;
			$oPost->Type = $bIsInternal ? \EHelpdeskPostType::Internal : \EHelpdeskPostType::Normal;
			$oPost->SystemType = \EHelpdeskPostSystemType::None;
			$oPost->Text = $sText;

			$aResultAttachment = array();
			if (is_array($mAttachments) && 0 < count($mAttachments))
			{
				foreach ($mAttachments as $sTempName => $sHash)
				{
					$aDecodeData = \CApi::DecodeKeyValues($sHash);
					if (!isset($aDecodeData['HelpdeskUserID']))
					{
						continue;
					}

					$rData = $this->ApiFileCache()->getFile($oUser, $sTempName);
					if ($rData)
					{
						$iFileSize = $this->ApiFileCache()->fileSize($oUser, $sTempName);

						$sThreadID = (string) $oThread->IdHelpdeskThread;
						$sThreadID = str_pad($sThreadID, 2, '0', STR_PAD_LEFT);
						$sThreadIDSubFolder = substr($sThreadID, 0, 2);

						$sThreadFolderName = API_HELPDESK_PUBLIC_NAME.'/'.$sThreadIDSubFolder.'/'.$sThreadID;

						$this->oApiFilestorage->createFolder($oUser, \EFileStorageTypeStr::Corporate, '',
							$sThreadFolderName);

						$sUploadName = isset($aDecodeData['Name']) ? $aDecodeData['Name'] : $sTempName;

						$this->oApiFilestorage->createFile($oUser,
							\EFileStorageTypeStr::Corporate, $sThreadFolderName, $sUploadName, $rData, false);

						$oAttachment = new \CHelpdeskAttachment();
						$oAttachment->IdHelpdeskThread = $oThread->IdHelpdeskThread;
						$oAttachment->IdHelpdeskPost = $oPost->IdHelpdeskPost;
						$oAttachment->IdOwner = $oUser->iObjectId;
						$oAttachment->IdTenant = $oUser->IdTenant;

						$oAttachment->FileName = $sUploadName;
						$oAttachment->SizeInBytes = $iFileSize;
						$oAttachment->encodeHash($oUser, $sThreadFolderName);
						
						$aResultAttachment[] = $oAttachment;
					}
				}

				if (is_array($aResultAttachment) && 0 < count($aResultAttachment))
				{
					$oPost->Attachments = $aResultAttachment;
				}
			}

			$mResult = $this->oMainManager->createPost($oUser, $oThread, $oPost, $bIsNew, true, $sCc, $sBcc);

			if ($mResult)
			{
				$mResult = array(
					'ThreadId' => $oThread->IdHelpdeskThread,
					'ThreadIsNew' => $bIsNew
				);
			}
		}

		return $mResult;
	}	
	
	/**
	 * @return array
	 */
	public function DeletePost($iPostId = 0, $iThreadId = 0, $bIsExt = 0)
	{
		$oUser = $this->GetCurrentUser();

		if (!$oUser)
		{
			throw new \Core\Exceptions\ClientException(\Core\Notifications::AccessDenied);
		}

		$iThreadId = (int) $iThreadId;
		$iPostId = (int) $iPostId;
		
		if (0 >= $iThreadId || 0 >= $iPostId)
		{
			throw new \Core\Exceptions\ClientException(\Core\Notifications::InvalidInputParameter);
		}

		$oThread = $this->oMainManager->getThreadById($oUser, $iThreadId);
		if (!$oThread)
		{
			throw new \Core\Exceptions\ClientException(\Core\Notifications::InvalidInputParameter);
		}

		if (!$this->oMainManager->verifyPostIdsBelongToUser($oUser, array($iPostId)))
		{
			throw new \Core\Exceptions\ClientException(\Core\Notifications::AccessDenied);
		}

		return $this->oMainManager->deletePosts($oUser, $oThread, array($iPostId));
	}	
	
	/**
	 * @return array
	 */
	public function GetThreadByIdOrHash()
	{
		$oAccount = null;
		$oThread = false;
		$oUser = $this->getHelpdeskAccountFromParam($oAccount);

		$bIsAgent = $this->IsAgent($oUser);

		$sThreadId = (int) $this->getParamValue('ThreadId', 0);
		$sThreadHash = (string) $this->getParamValue('ThreadHash', '');
		if (empty($sThreadHash) && $sThreadId === 0)
		{
			throw new \Core\Exceptions\ClientException(\Core\Notifications::InvalidInputParameter);
		}

		$mHelpdeskThreadId = $sThreadId ? $sThreadId : $this->oMainManager->getThreadIdByHash($oUser->IdTenant, $sThreadHash);
		if (!is_int($mHelpdeskThreadId) || 1 > $mHelpdeskThreadId)
		{
			throw new \Core\Exceptions\ClientException(\Core\Notifications::InvalidInputParameter);
		}

		$oThread = $this->oMainManager->getThreadById($oUser, $mHelpdeskThreadId);
		if (!$oThread)
		{
			throw new \Core\Exceptions\ClientException(\Core\Notifications::InvalidInputParameter);
		}

		$aUserInfo = $this->oMainManager->userInformation($oUser, array($oThread->IdOwner));
		if (is_array($aUserInfo) && 0 < count($aUserInfo))
		{
			if (isset($aUserInfo[$oThread->IdOwner]) && is_array($aUserInfo[$oThread->IdOwner]))
			{
				$sEmail = isset($aUserInfo[$oThread->IdOwner][0]) ? $aUserInfo[$oThread->IdOwner][0] : '';
				$sName = isset($aUserInfo[$oThread->IdOwner][1]) ? $aUserInfo[$oThread->IdOwner][1] : '';

				if (empty($sEmail) && !empty($aUserInfo[$oThread->IdOwner][3]))
				{
					$sEmail = $aUserInfo[$oThread->IdOwner][3];
				}

				if (!$bIsAgent && 0 < strlen($sName))
				{
					$sEmail = '';
				}

				$oThread->Owner = array($sEmail, $sName);
			}
		}

		return $oThread;
	}	
	
	/**
	 * @return array
	 */
	public function GetPosts($iThreadId = 0, $iStartFromId = 0, $iLimit = 10, $iIsExt = 1)
	{
		$oUser = $this->getHelpdeskAccountFromParam();
		

		if (1 > $iThreadId || 0 > $iStartFromId || 1 > $iLimit)
		{
			throw new \Core\Exceptions\ClientException(\Core\Notifications::InvalidInputParameter);
		}
		
		$oThread = $this->oMainManager->getThreadById($oUser, $iThreadId);
		if (!$oThread)
		{
			throw new \Core\Exceptions\ClientException(\Core\Notifications::InvalidInputParameter);
		}

		$aPostList = $this->oMainManager->getPosts($oUser, $oThread, $iStartFromId, $iLimit);
		$iExtPostsCount = $iIsExt ? $this->oMainManager->getExtPostsCount($oUser, $oThread) : 0;

		$aOwnerDataList = array();
		if (is_array($aPostList) && 0 < count($aPostList))
		{
			foreach ($aPostList as &$oItem)
			{
				if ($oItem && !isset($aOwnerDataList[$oItem->IdOwner]))
				{
//					$aIdList[$oItem->IdOwner] = (int) $oItem->IdOwner;
					$oOwnerUser = $this->oCoreDecorator->GetUser($oItem->IdOwner);
					$oOwnerAccount = $this->oAccountsManager->getAccountByUserId($oItem->IdOwner);
					
					if ($oOwnerUser)
					{
						$aOwnerDataList[$oItem->IdOwner] = array(
							'Email' => '',  //actualy, it's a User Login stored in Auth account
							'Name' => $oOwnerUser->Name,
							'NotificationEmail' => $oOwnerAccount->NotificationEmail
						);
					}
				}
			}
		}
		
		if (!isset($aOwnerDataList[$oThread->IdOwner]))
		{
			$oOwnerUser = $this->oCoreDecorator->GetUser($oThread->IdOwner);
			$oOwnerAccount = $this->oAccountsManager->getAccountByUserId($oThread->IdOwner);

			if ($oOwnerUser)
			{
				$aOwnerDataList[$oThread->IdOwner] = array(
					'Email' => '', //actualy, it's a User Login stored in Auth account
					'Name' => $oOwnerUser->Name,
					'NotificationEmail' => $oOwnerAccount->NotificationEmail
				);
			}
		}

		if (0 < count($aOwnerDataList))
		{
//			$aIdList = array_values($aIdList);
//			$aUserInfo = $this->oMainManager->userInformation($oUser, $aIdList);
//			$aUserInfo => id_helpdesk_user, email, name, is_agent, notification_email

//			if (is_array($aUserInfo) && 0 < count($aUserInfo))
//			{
				$bIsAgent = $this->IsAgent($oUser);
				
				foreach ($aPostList as &$oItem)
				{
					if ($oItem && isset($aOwnerDataList[$oItem->IdOwner]) && is_array($aOwnerDataList[$oItem->IdOwner]))
					{
						$oItem->Owner = array(
							isset($aOwnerDataList[$oItem->IdOwner]['Email']) ? $aOwnerDataList[$oItem->IdOwner]['Email'] : '',
							isset($aOwnerDataList[$oItem->IdOwner]['Name']) ? $aOwnerDataList[$oItem->IdOwner]['Name'] : ''
						);

						if (empty($oItem->Owner[0]))
						{
							$oItem->Owner[0] = isset($aOwnerDataList[$oItem->IdOwner]['notification_email']) ? $aOwnerDataList[$oItem->IdOwner]['notification_email'] : '';
						}

						if (!$bIsAgent && 0 < strlen($oItem->Owner[1]))
						{
							$oItem->Owner[0] = '';
						}

						$oItem->IsThreadOwner = $oThread->IdOwner === $oItem->IdOwner;
					}

					if ($oItem)
					{
						$oItem->ItsMe = $oUser->iObjectId === $oItem->IdOwner;
					}
				}

				if (isset($aOwnerDataList[$oThread->IdOwner]) && is_array($aOwnerDataList[$oThread->IdOwner]))
				{
					$sEmail = isset($aOwnerDataList[$oThread->IdOwner]['Email']) ? $aOwnerDataList[$oThread->IdOwner]['Email'] : '';
					$sName = isset($aOwnerDataList[$oThread->IdOwner]['Name']) ? $aOwnerDataList[$oThread->IdOwner]['Name'] : '';

					if (!$bIsAgent && 0 < strlen($sName))
					{
						$sEmail = '';
					}

					$oThread->Owner = array($sEmail, $sName);
				}
//			}
		}

		if ($oThread->HasAttachments)
		{
			$aAttachments = $this->oMainManager->getAttachments($oUser, $oThread);
			if (is_array($aAttachments))
			{
				foreach ($aPostList as &$oItem)
				{
					if (isset($aAttachments[$oItem->IdHelpdeskPost]) && is_array($aAttachments[$oItem->IdHelpdeskPost]) &&
						0 < count($aAttachments[$oItem->IdHelpdeskPost]))
					{
						$oItem->Attachments = $aAttachments[$oItem->IdHelpdeskPost];

						foreach ($oItem->Attachments as $oAttachment)
						{
							if ($oAttachment && '.asc' === \strtolower(\substr(\trim($oAttachment->FileName), -4)))
							{
								$oAttachment->populateContent($oUser, $this->oApiHelpdesk, $this->oApiFilestorage);
							}
						}
					}
				}
			}
		}

		return array(
			'ThreadId' => $oThread->IdHelpdeskThread,
			'StartFromId' => $iStartFromId,
			'Limit' => $iLimit,
			'ItemsCount' => $iExtPostsCount ? $iExtPostsCount : ($oThread->PostCount > count($aPostList) ? $oThread->PostCount : count($aPostList)),
			'List' => $aPostList
		);
	}
	
	/**
	 * @return array
	 */
	public function DeleteThread($sThreadId = 0, $bIsExt = 0)
	{
		$oUser = $this->GetCurrentUser();

		if (!$oUser)
		{
			throw new \Core\Exceptions\ClientException(\Core\Notifications::AccessDenied);
		}

		$iThreadId = (int) $sThreadId;

		if (0 < $iThreadId && !$this->IsAgent($oUser) && !$this->oMainManager->verifyThreadIdsBelongToUser($oUser, array($iThreadId)))
		{
			throw new \Core\Exceptions\ClientException(\Core\Notifications::AccessDenied);
		}

		$bResult = false;
		if (0 < $iThreadId)
		{
			$bResult = $this->oMainManager->archiveThreads($oUser, array($iThreadId));
		}

		return $bResult;
	}	
	
	/**
	 * @return array
	 */
	public function ChangeThreadState($iThreadId = 0, $iThreadType = \EHelpdeskThreadType::None, $IsExt = 0)
	{
		$oUser = $this->getHelpdeskAccountFromParam();

//		$iThreadId = (int) $this->getParamValue('ThreadId', 0);
//		$iThreadType = (int) $this->getParamValue('Type', \EHelpdeskThreadType::None);

		if (1 > $iThreadId || !in_array($iThreadType, array(
			\EHelpdeskThreadType::Pending,
			\EHelpdeskThreadType::Waiting,
			\EHelpdeskThreadType::Answered,
			\EHelpdeskThreadType::Resolved,
			\EHelpdeskThreadType::Deferred
		)))
		{
			throw new \Core\Exceptions\ClientException(\Core\Notifications::InvalidInputParameter);
		}

		if (!$oUser || ($iThreadType !== \EHelpdeskThreadType::Resolved && !$this->IsAgent($oUser)))
		{
			throw new \Core\Exceptions\ClientException(\Core\Notifications::AccessDenied);
		}

		$bResult = false;
		$oThread = $this->oMainManager->getThreadById($oUser, $iThreadId);
		if ($oThread)
		{
			$oThread->Type = $iThreadType;
			$bResult = $this->oMainManager->updateThread($oUser, $oThread);
		}
		
		return $bResult;
	}	
	

	public function PingThread($iThreadId = 0)
	{
		$oUser = $this->getHelpdeskAccountFromParam();

//		$iThreadId = (int) $this->getParamValue('ThreadId', 0);

		if (0 === $iThreadId)
		{
			throw new \Core\Exceptions\ClientException(\Core\Notifications::InvalidInputParameter);
		}

		$this->oMainManager->setOnline($oUser, $iThreadId);

		return $this->oMainManager->getOnline($oUser, $iThreadId);
	}
	
	public function SetThreadSeen($iThreadId = 0)
	{
		$oUser = $this->getHelpdeskAccountFromParam();

//		$iThreadId = (int) $this->getParamValue('ThreadId', 0);

		if (0 === $iThreadId)
		{
			throw new \Core\Exceptions\ClientException(\Core\Notifications::InvalidInputParameter);
		}

		$oThread = $this->oMainManager->getThreadById($oUser, $iThreadId);
		if (!$oThread)
		{
			throw new \Core\Exceptions\ClientException(\Core\Notifications::AccessDenied);
		}

		return $this->oMainManager->setThreadSeen($oUser, $oThread);
	}	
	
	/**
	 * @return array
	 */
	public function GetThreads($iOffset = 0, $iLimit = 10, $iFilter = \EHelpdeskThreadFilterType::All, $sSearch = '')
	{
		$oUser = $this->getHelpdeskAccountFromParam();
		
		if (0 > $iOffset || 1 > $iLimit)
		{
			throw new \Core\Exceptions\ClientException(\Core\Notifications::InvalidInputParameter);
		}

		$aThreadsList = array();
		$iCount = $this->oMainManager->getThreadsCount($oUser, $iFilter, $sSearch);
		if ($iCount)
		{
			$aThreadsList = $this->oMainManager->getThreads($oUser, $iOffset, $iLimit, $iFilter, $sSearch);
		}

		$aOwnerDataList = array();
		if (is_array($aThreadsList) && 0 < count($aThreadsList))
		{
			foreach ($aThreadsList as &$oItem)
			{
//				$aOwnerList[$oItem->IdOwner] = (int) $oItem->IdOwner;
				$oOwnerUser = $this->oCoreDecorator->GetUser($oItem->IdOwner);
				$oOwnerAccount = $this->oAccountsManager->getAccountByUserId($oItem->IdOwner);
				if ($oOwnerUser)
				{
					$aOwnerDataList[$oOwnerUser->iObjectId] = array(
						'Email' => '', //actualy, it's a User Login stored in Auth account
						'Name' => $oOwnerUser->Name,
						'NotificationEmail' => $oOwnerAccount->NotificationEmail
					);
				}
			}
		}

		if (0 < count($aOwnerDataList))
		{
//			$aOwnerList = array_values($aOwnerList);
			
//			$aUserInfo = $this->oMainManager->userInformation($oUser, $aOwnerList);
//			id_helpdesk_user, email, name, is_agent, notification_email
			
			if (is_array($aOwnerDataList) && 0 < count($aOwnerDataList))
			{
				$bIsAgent = $this->IsAgent($oUser);
				
				foreach ($aThreadsList as &$oItem)
				{
					if ($oItem && isset($aOwnerDataList[$oItem->IdOwner]))
					{
						$sEmail = isset($aOwnerDataList[$oItem->IdOwner]['Email']) ? $aOwnerDataList[$oItem->IdOwner]['Email'] : '';
						$sName = isset($aOwnerDataList[$oItem->IdOwner]['Name']) ? $aOwnerDataList[$oItem->IdOwner]['Name'] : '';

						if (empty($sEmail) && !empty($aOwnerDataList[$oItem->IdOwner]['NotificationEmail']))
						{
							$sEmail = $aOwnerDataList[$oItem->IdOwner]['NotificationEmail'];
						}

						if (!$bIsAgent && 0 < strlen($sName))
						{
							$sEmail = '';
						}
						
						$oItem->Owner = array($sEmail, $sName);
					}
				}
			}
		}

		return array(
			'Search' => $sSearch,
			'Filter' => $iFilter,
			'List' => $aThreadsList,
			'Offset' => $iOffset,
			'Limit' => $iLimit,
			'ItemsCount' =>  $iCount
		);
	}	
	
	public function GetThreadsPendingCount()
	{
		$oAccount = null;
		$oUser = $this->getHelpdeskAccountFromParam($oAccount);

		if (!($oUser instanceof \CHelpdeskUser))
		{
			throw new \Core\Exceptions\ClientException(\Core\Notifications::HelpdeskUnknownUser);
		}


		return $this->oMainManager->getThreadsPendingCount($oUser->IdTenant);
	}	
	
	/**
	 * @return array
	 */
	public function UpdateUserPassword()
	{
		$oAccount = null;
		$oUser = $this->getHelpdeskAccountFromParam($oAccount);

		$sCurrentPassword = (string) $this->getParamValue('CurrentPassword', '');
		$sNewPassword = (string) $this->getParamValue('NewPassword', '');

		$bResult = false;
		if ($oUser && $oUser->validatePassword($sCurrentPassword) && 0 < strlen($sNewPassword))
		{
			$oUser->setPassword($sNewPassword);
			if (!$this->oMainManager->updateUser($oUser))
			{
				throw new \Core\Exceptions\ClientException(\Core\Notifications::CanNotChangePassword);
			}
		}

		return $bResult;
	}	
	
	/**
	 * @return array
	 */
	public function UpdateSettings()
	{
		setcookie('aft-cache-ctrl', '', time() - 3600);
		$oAccount = null;
		$oUser = $this->getHelpdeskAccountFromParam($oAccount);

		$sName = (string) $this->getParamValue('Name', $oUser->Name);
		$sLanguage = (string) $this->getParamValue('Language', $oUser->Language);
		//$sLanguage = $this->validateLang($sLanguage);

		$sDateFormat = (string) $this->getParamValue('DateFormat', $oUser->DateFormat);
		$iTimeFormat = (int) $this->getParamValue('TimeFormat', $oUser->TimeFormat);

		$oUser->Name = trim($sName);
		$oUser->Language = trim($sLanguage);
		$oUser->DateFormat = $sDateFormat;
		$oUser->TimeFormat = $iTimeFormat;
		
		return $this->oMainManager->updateUser($oUser);
	}	
	
	/**
	 * @return array
	 */
	public function UpdateUserSettings()
	{
		/*$oAccount = $this->getAccountFromParam();
		$oHelpdeskUser = $this->GetHelpdeskAccountFromMainAccount($oAccount);
		
		$oAccount->User->AllowHelpdeskNotifications =  (bool) $this->getParamValue('AllowHelpdeskNotifications', $oAccount->User->AllowHelpdeskNotifications);
		$oHelpdeskUser->Signature = trim((string) $this->getParamValue('Signature', $oHelpdeskUser->Signature));
		$oHelpdeskUser->SignatureEnable = (bool) $this->getParamValue('SignatureEnable', $oHelpdeskUser->SignatureEnable);

		$bResult = $this->oApiUsers->UpdateAccount($oAccount);
		if ($bResult)
		{
			$bResult = $this->ApiHelpdesk()->updateUser($oHelpdeskUser);
		}
		else
		{
			$this->ApiHelpdesk()->updateUser($oHelpdeskUser);
		}

		return $this->DefaultResponse(__FUNCTION__, $bResult);*/

		$oAccount = $this->getAccountFromParam();

		$oAccount->User->AllowHelpdeskNotifications =  (bool) $this->getParamValue('AllowHelpdeskNotifications', $oAccount->User->AllowHelpdeskNotifications);
		$oAccount->User->HelpdeskSignature = trim((string) $this->getParamValue('HelpdeskSignature', $oAccount->User->HelpdeskSignature));
		$oAccount->User->HelpdeskSignatureEnable = (bool) $this->getParamValue('HelpdeskSignatureEnable', $oAccount->User->HelpdeskSignatureEnable);

		$oApiUsers = \CApi::GetCoreManager('users');
		return $oApiUsers->UpdateAccount($oAccount);
	}	
	
//	public function checkAuth($sLogin, $sPassword, &$mResult)
//	{
//		$oAccount = $this->oAccountsManager->getAccountByCredentials($sLogin, $sPassword);
//
//		if ($oAccount)
//		{
//			$mResult = array(
//				'token' => 'auth',
//				'sign-me' => true,
//				'id' => $oAccount->IdUser,
//				'email' => $oAccount->Login
//			);
//		}
//	}
	
	public function CheckNonAuthorizedMethodAllowed($sMethodName = '', $sAuthToken = '')
	{
		return !!in_array($sMethodName, array('Login', 'Register', 'Forgot'));
	}
}

return new HelpDeskModule('1.0');
