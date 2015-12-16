<?php

/* -AFTERLOGIC LICENSE HEADER- */

/**
 * CApiMailsuiteManager class summary
 *
 * @package Mailsuite
 */
class CApiMailsuiteManager extends AApiManagerWithStorage
{
	/**
	 * Creates a new instance of the object.
	 *
	 * @param CApiGlobalManager &$oManager
	 */
	public function __construct(CApiGlobalManager &$oManager, $sForcedStorage = '')
	{
		parent::__construct('mailsuite', $oManager, $sForcedStorage);

		$this->inc('classes.mailing-list');
		$this->inc('classes.mail-aliases');
		$this->inc('classes.mail-forwards');
	}

	/**
	 * @param int $iMailingListId
	 *
	 * @return CMailingList
	 */
	public function getMailingListById($iMailingListId)
	{
		$oMailingList = null;
		try
		{
			$oMailingList = $this->oStorage->getMailingListById($iMailingListId);
		}
		catch (CApiBaseException $oException)
		{
			$this->setLastException($oException);
		}
		return $oMailingList;
	}

	/**
	 * @param CMailAliases &$oMailAliases
	 */
	public function initMailAliases(CMailAliases &$oMailAliases)
	{
		try
		{
			$this->oStorage->initMailAliases($oMailAliases);
		}
		catch (CApiBaseException $oException)
		{
			$this->setLastException($oException);
		}
	}

	/**
	 * @param CMailForwards &$oMailForwards
	 */
	public function initMailForwards(CMailForwards &$oMailForwards)
	{
		try
		{
			$this->oStorage->initMailForwards($oMailForwards);
		}
		catch (CApiBaseException $oException)
		{
			$this->setLastException($oException);
		}
	}

	/**
	 * @param CMailingList &$oMailingList
	 *
	 * @return bool
	 */
	public function createMailingList(CMailingList &$oMailingList)
	{
		$bResult = false;
		try
		{
			if ($oMailingList->validate())
			{
				if (!$this->mailingListExists($oMailingList))
				{
					if (!$this->oStorage->createMailingList($oMailingList))
					{
						throw new CApiManagerException(Errs::MailSuiteManager_MailingListCreateFailed);
					}

					if ($oMailingList)
					{
						/* @var $oApiContactsManager CApiContactsMainManager */
						$oApiContactsManager = CApi::Manager('contactsmain');

						/* @var $oApiTenantsManager CApiTenantsManager */
						$oApiTenantsManager = CApi::GetCoreManager('tenants');

						if ($oApiContactsManager && 'db' === CApi::GetManager()->GetStorageByType('contactsmain'))
						{
							$oContact = $oApiContactsManager->createContactObject();
							$oContact->BusinessEmail = $oMailingList->Email;
							$oContact->PrimaryEmail = EPrimaryEmailType::Business;
							$oContact->Type = EContactType::GlobalMailingList;
							$oContact->FullName = $oMailingList->Name;

							$oContact->IdTypeLink = $oMailingList->IdMailingList;
							$oContact->IdDomain = 0 < $oMailingList->IdDomain ? $oMailingList->IdDomain : 0;
							$oContact->IdTenant = $oApiTenantsManager ?
								$oApiTenantsManager->getTenantIdByDomainId($oContact->IdDomain) : 0;

							$oApiContactsManager->createContact($oContact);
						}
					}
				}
				else
				{
					throw new CApiManagerException(Errs::MailSuiteManager_MailingListAlreadyExists);
				}
			}

			$bResult = true;
		}
		catch (CApiBaseException $oException)
		{
			$bResult = false;
			$this->setLastException($oException);
		}

		return $bResult;
	}

	/**
	 * @param CMailingList &$oMailingList
	 *
	 * @return bool
	 */
	public function updateMailingList(CMailingList &$oMailingList)
	{
		$bResult = false;
		try
		{
			$bResult = $this->oStorage->updateMailingList($oMailingList);

			if ($bResult && $oMailingList && (
				(null !== $oMailingList->GetObsoleteValue('Name') && $oMailingList->GetObsoleteValue('Name') !== $oMailingList->Name)))
			{
				/* @var $oApiGContactsManager CApiGcontactsManager */
				$oApiGContactsManager = CApi::Manager('gcontacts');
				if ($oApiGContactsManager)
				{
					$oContact = $oApiGContactsManager->getContactByMailingListId($oMailingList->IdMailingList);
					if ($oContact)
					{
						$oContact->FullName = $oMailingList->Name;
						$oApiGContactsManager->updateContact($oContact);
					}
				}
			}

		}
		catch (CApiBaseException $oException)
		{
			$bResult = false;
			$this->setLastException($oException);
		}
		return $bResult;
	}

	/**
	 * @param int $iMailingListId
	 *
	 * @return bool
	 */
	public function deleteMailingListById($iMailingListId)
	{
		$bResult = false;
		try
		{
			if (!$this->oStorage->deleteMailingListById($iMailingListId))
			{
				throw new CApiManagerException(Errs::MailSuiteManager_MailingListDeleteFailed);
			}

			$this->deleteMailingListGlobalContacts($iMailingListId);

			$bResult = true;
		}
		catch (CApiBaseException $oException)
		{
			$bResult = false;
			$this->setLastException($oException);
		}

		return $bResult;
	}

	/**
	 * @param CMailingList &$oMailingList
	 *
	 * @return bool
	 */
	public function deleteMailingList(CMailingList &$oMailingList)
	{
		$bResult = false;
		try
		{
			if ($oMailingList->validate())
			{
				if ($this->mailingListExists($oMailingList))
				{
					if (!$this->oStorage->deleteMailingList($oMailingList))
					{
						throw new CApiManagerException(Errs::MailSuiteManager_MailingListDeleteFailed);
					}

					$this->deleteMailingListGlobalSubContacts($oMailingList->IdMailingList);
					$this->deleteMailingListGlobalContacts($oMailingList->IdMailingList);
				}
			}

			$bResult = true;
		}
		catch (CApiBaseException $oException)
		{
			$bResult = false;
			$this->setLastException($oException);
		}

		return $bResult;
	}

	/**
	 * @param int $iMailingListId
	 *
	 * @return bool
	 */
	public function deleteMailingListGlobalSubContacts($iMailingListId)
	{
		$bResult = false;
		try
		{
			$bResult = $this->oStorage->deleteMailingListGlobalSubContacts($iMailingListId);
		}
		catch (CApiBaseException $oException)
		{
			$bResult = false;
			$this->setLastException($oException);
		}

		return $bResult;
	}

	/**
	 * @param int $iMailingListId
	 *
	 * @return bool
	 */
	public function deleteMailingListGlobalContacts($iMailingListId)
	{
		$bResult = false;
		try
		{
			$bResult = $this->oStorage->deleteMailingListGlobalContacts($iMailingListId);
		}
		catch (CApiBaseException $oException)
		{
			$bResult = false;
			$this->setLastException($oException);
		}

		return $bResult;
	}

	/**
	 * @param CMailAliases &$oMailAliases
	 *
	 * @return bool
	 */
	public function updateMailAliases(CMailAliases &$oMailAliases)
	{
		$bResult = false;
		try
		{
			$bResult = $this->oStorage->updateMailAliases($oMailAliases);
		}
		catch (CApiBaseException $oException)
		{
			$bResult = false;
			$this->setLastException($oException);
		}
		return $bResult;
	}

	/**
	 * @param CMailForwards &$oMailForwards
	 *
	 * @return bool
	 */
	public function updateMailForwards(CMailForwards &$oMailForwards)
	{
		$bResult = false;
		try
		{
			$bResult = $this->oStorage->updateMailForwards($oMailForwards);
		}
		catch (CApiBaseException $oException)
		{
			$bResult = false;
			$this->setLastException($oException);
		}
		return $bResult;
	}

	/**
	 * @param CAccount $oAccount
	 *
	 * @return bool
	 */
	public function deleteMailAliases(CAccount $oAccount)
	{
		if (!$this->oStorage->updateMailAliases(new CMailAliases($oAccount)))
		{
			$this->lastErrorCode = $this->oMailsuiteApi->getLastErrorCode();
			$this->lastErrorMessage = $this->oMailsuiteApi->GetLastErrorMessage();
			return false;
		}

		return true;
	}

	/**
	 * @param CAccount $oAccount
	 *
	 * @return bool
	 */
	public function deleteMailForwards(CAccount $oAccount)
	{
		if (!$this->oStorage->updateMailForwards(new CMailForwards($oAccount)))
		{
			$this->lastErrorCode = $this->oMailsuiteApi->getLastErrorCode();
			$this->lastErrorMessage = $this->oMailsuiteApi->GetLastErrorMessage();
			return false;
		}

		return true;
	}

	/**
	 * @param CAccount $oAccount
	 *
	 * @return bool
	 */
	public function deleteMailDir(CAccount $oAccount)
	{
		if ($oAccount && $oAccount->IsInternal)
		{
			$sScript = '/opt/afterlogic/scripts/webshell-maildirdel.sh';
			if (file_exists($sScript))
			{
				$sCmd = $sScript.' '.$oAccount->Domain->Name.' '.api_Utils::GetAccountNameFromEmail($oAccount->IncomingMailLogin);

				CApi::Log('deleteMailDir / exec: '.$sCmd);
				$sReturn = trim(shell_exec($sCmd));
				if (!empty($sReturn))
				{
					CApi::Log('deleteMailDir / exec result: '.$sReturn);
				}
			}
			else
			{
				CApi::Log('deleteMailDir: '.$sScript.' does not exist', ELogLevel::Error);
			}
		}
	}

	/**
	 * @param CMailingList $oMailingList
	 *
	 * @return bool
	 */
	public function mailingListExists(CMailingList $oMailingList)
	{
		/* @var $oApiUsersManager CApiUsersManager */
		$oApiUsersManager = CApi::GetCoreManager('users');
		return $oApiUsersManager->accountExists($oMailingList->generateAccount());
	}
}
