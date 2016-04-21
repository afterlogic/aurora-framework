<?php

class MailModule extends AApiModule
{
	public $oApiAccountsManager = null;
	
	public function init() 
	{
		$this->oApiAccountsManager = $this->GetManager('accounts', 'db');
		
		$this->setObjectMap('CUser', array(
				'MailsPerPage'	=> array('int', '20'),
				'UseThreads'						=> array('bool', true), //'use_threads'),
				'SaveRepliedMessagesToCurrentFolder' => array('bool', false), //'save_replied_messages_to_current_folder'),
			)
		);
	}
	
	/**
	 * 
	 * @return boolean
	 */
	public function CreateAccount($iUserId = 0, $sEmail = '', $sPassword = '')
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
			$oAccount->IncomingMailPassword = $sPassword;

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
	public function UpdateAccount($iAccountId = 0, $sEmail = '', $sPassword = '')
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
}

return new MailModule('1.0');
