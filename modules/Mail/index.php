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
				'MailsPerPage'	=> array('int', '20'),
				'UseThreads'						=> array('bool', true), //'use_threads'),
				'SaveRepliedMessagesToCurrentFolder' => array('bool', false), //'save_replied_messages_to_current_folder'),
			)
		);
		
		$this->subscribeEvent('Login', array($this, 'checkAuth'));
	}
	
	/**
	 * 
	 * @return boolean
	 */
	public function CreateAccount($iUserId = 0, $sEmail = '', $sPassword = '', $sServer = '')
	{
		$oEventResult = null;
		$this->broadcastEvent('CreateAccount', array(
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
}

return new MailModule('1.0');
