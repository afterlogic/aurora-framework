<?php

/* -AFTERLOGIC LICENSE HEADER- */

/**
 * @package Mailsuite
 * @subpackage Storages
 */
class CApiMailsuiteDbStorage extends CApiMailsuiteStorage
{
	/**
	 * @var CDbStorage $oConnection
	 */
	protected $oConnection;

	/**
	 * @var CApiMailsuiteCommandCreator
	 */
	protected $oCommandCreator;

	/**
	 * @param CApiGlobalManager &$oManager
	 */
	public function __construct(CApiGlobalManager &$oManager)
	{
		parent::__construct('db', $oManager);

		$this->oConnection =& $oManager->GetConnection();
		$this->oCommandCreator =& $oManager->GetCommandCreator(
			$this, array(
				EDbType::MySQL => 'CApiMailsuiteCommandCreatorMySQL',
				EDbType::PostgreSQL => 'CApiMailsuiteCommandCreatorPostgreSQL'
			)
		);
	}

	/**
	 * @param int $iMailingListId
	 *
	 * @return CMailingList
	 */
	public function getMailingListById($iMailingListId)
	{
		$oMailingList = null;
		if ($this->oConnection->Execute($this->oCommandCreator->getMailingListById($iMailingListId)))
		{
			$oRow = $this->oConnection->GetNextRecord();

			if ($oRow)
			{
				/* @var $oApiDomainsManager CApiDomainsManager */
				$oApiDomainsManager = CApi::Manager('domains');

				$oDomain = null;
				$iDomainId = $oRow->id_domain;
				if (0 < $iDomainId)
				{
					$oDomain = $oApiDomainsManager->getDomainById($iDomainId);
				}
				else
				{
					$oDomain = $oApiDomainsManager->getDefaultDomain();
				}

				if ($oDomain)
				{
					$oMailingList = new CMailingList($oDomain);
					$oMailingList->InitByDbRow($oRow);
					$this->initMailingListMembers($oMailingList);
				}
			}

			$this->oConnection->FreeResult();
		}

		$this->throwDbExceptionIfExist();
		return $oMailingList;
	}

	/**
	 * @param CMailingList $oMailingList
	 *
	 * @return bool
	 */
	public function createMailingList(CMailingList &$oMailingList)
	{
		$bResult = false;
		if ($this->oConnection->Execute($this->oCommandCreator->createMailingList($oMailingList)))
		{
			$oMailingList->IdMailingList = $this->oConnection->GetLastInsertId('awm_accounts', 'id_acct');
			$bResult = $this->updateMailingListMembers($oMailingList);
		}

		$this->throwDbExceptionIfExist();
		return $bResult;
	}

	/**
	 * @param CMailingList $oMailingList
	 *
	 * @return bool
	 */
	public function updateMailingList(CMailingList $oMailingList)
	{
		$bResult = true;
		if ($this->oConnection->Execute($this->oCommandCreator->updateMailingList($oMailingList)))
		{
			$bResult = $this->updateMailingListMembers($oMailingList);
		}
		$this->throwDbExceptionIfExist();
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
		if ($this->oConnection->Execute($this->oCommandCreator->deleteMailingListById($iMailingListId)))
		{
			if ($this->oConnection->Execute(
				$this->oCommandCreator->clearMailingListMembers($iMailingListId)))
			{
				$bResult = true;
			}
		}

		$this->throwDbExceptionIfExist();
		return $bResult;
	}

	/**
	 * @param int $iMailingListId
	 *
	 * @return bool
	 */
	public function deleteMailingListGlobalSubContacts($iMailingListId)
	{
		$bResult = $this->oConnection->Execute($this->oCommandCreator->deleteMailingListGlobalSubContacts($iMailingListId));
		$this->throwDbExceptionIfExist();
		return $bResult;
	}

	/**
	 * @param int $iMailingListId
	 *
	 * @return bool
	 */
	public function deleteMailingListGlobalContacts($iMailingListId)
	{
		$bResult = $this->oConnection->Execute($this->oCommandCreator->deleteMailingListGlobalContacts($iMailingListId));
		$this->throwDbExceptionIfExist();
		return $bResult;
	}

	/**
	 * @param CMailingList $oMailingList
	 *
	 * @return bool
	 */
	public function deleteMailingList(CMailingList $oMailingList)
	{
		$bResult = false;
		if ($this->oConnection->Execute($this->oCommandCreator->deleteMailingListById($oMailingList->IdMailingList)))
		{
			if ($this->oConnection->Execute(
				$this->oCommandCreator->clearMailingListMembers($oMailingList->IdMailingList)))
				$bResult = true;
		}

		$this->throwDbExceptionIfExist();
		return $bResult;
	}

	/**
	 * @param CMailingList $oMailingList
	 */
	protected function initMailingListMembers(CMailingList &$oMailingList)
	{
		if ($oMailingList && $this->oConnection->Execute(
			$this->oCommandCreator->getMailingListMembersById($oMailingList->IdMailingList)))
		{
			$oRow = null;
			$aMailingList = array();
			while (false !== ($oRow = $this->oConnection->GetNextRecord()))
			{
				$aMailingList[] = $oRow->list_to;
			}

			$oMailingList->Members = $aMailingList;
		}

		$this->throwDbExceptionIfExist();
	}

	/**
	 * @param CMailingList $oMailingList
	 *
	 * @return bool
	 */
	protected function updateMailingListMembers(CMailingList $oMailingList)
	{
		$result1 = $result2 = true;
		if ($oMailingList)
		{
			$result1 = $this->oConnection->Execute(
				$this->oCommandCreator->clearMailingListMembers($oMailingList->IdMailingList));

			if (0 < count($oMailingList->Members))
			{
				$result2 = $this->oConnection->Execute(
					$this->oCommandCreator->addMailingListMembers($oMailingList));
			}
		}
		$this->throwDbExceptionIfExist();
		return ($result1 && $result2) ? true : false;
	}

	/**
	 * @param CMailAliases $oMailAliases
	 */
	public function initMailAliases(CMailAliases &$oMailAliases)
	{
		if ($oMailAliases && $this->oConnection->Execute(
			$this->oCommandCreator->getMailAliasesById($oMailAliases->IdAccount)))
		{
			$oRow = null;
			$aMailAliases = array();
			while (false !== ($oRow = $this->oConnection->GetNextRecord()))
			{
				$aMailAliases[] = $oRow->alias_name . '@' . $oRow->alias_domain;
			}

			$oMailAliases->Aliases = $aMailAliases;
		}

		$this->throwDbExceptionIfExist();
	}

	/**
	 * @param CMailAliases $oMailAliases
	 *
	 * @return bool
	 */
	public function updateMailAliases(CMailAliases $oMailAliases)
	{
		$result1 = $result2 = true;
		if ($oMailAliases)
		{
			$result1 = $this->oConnection->Execute(
				$this->oCommandCreator->clearMailAliases($oMailAliases->IdAccount));

			if (0 < count($oMailAliases->Aliases))
			{
				$aAliases = array();
				foreach (array_unique($oMailAliases->Aliases) as $aAlias)
				{
					list($sAliasName, $sAliasDomain) = explode('@', $aAlias, 2);
					$add1 = $add2 = false;
					$result = $this->oConnection->Execute(
						$this->oCommandCreator->isAliasValidToCreateInAccounts($aAlias));
					if ($result)
					{
						$row = $this->oConnection->GetNextRecord();
						if (is_object($row))
						{
							if (0 === (int) $row->cnt)
							{
								$add1 = true;
							}
						}

						$this->oConnection->FreeResult();
					}
					$result = $this->oConnection->Execute(
						$this->oCommandCreator->isAliasValidToCreateInAliases($sAliasName, $sAliasDomain));
					if ($result)
					{
						$row = $this->oConnection->GetNextRecord();
						if (is_object($row))
						{
							if (0 === (int) $row->cnt)
							{
								$add2 = true;
							}
						}

						$this->oConnection->FreeResult();
					}
					if ($add1 && $add2)
					{
						$aAliases[] = $aAlias;
					}
				}
				$oMailAliases->Aliases = $aAliases;

				$result2 = $this->oConnection->Execute(
					$this->oCommandCreator->addMailAliases($oMailAliases));
			}
		}
		$this->throwDbExceptionIfExist();
		return ($result1 && $result2) ? true : false;
	}

	/**
	 * @param CMailForwards $oMailForwards
	 */
	public function initMailForwards(CMailForwards &$oMailForwards)
	{
		if ($oMailForwards && $this->oConnection->Execute(
			$this->oCommandCreator->getMailForwardsById($oMailForwards->IdAccount)))
		{
			$oRow = null;
			$aMailForwards = array();
			while (false !== ($oRow = $this->oConnection->GetNextRecord()))
			{
				$aMailForwards[] = $oRow->forward_to;
			}

			$oMailForwards->Forwards = $aMailForwards;
		}

		$this->throwDbExceptionIfExist();
	}

	/**
	 * @param CMailForwards $oMailForwards
	 *
	 * @return bool
	 */
	public function updateMailForwards(CMailForwards $oMailForwards)
	{
		$result1 = $result2 = true;
		if ($oMailForwards)
		{
			$result1 = $this->oConnection->Execute(
				$this->oCommandCreator->clearMailForwards($oMailForwards->IdAccount));

			if (0 < count($oMailForwards->Forwards))
			{
				$oMailForwards->Forwards = array_unique($oMailForwards->Forwards);
				$result2 = $this->oConnection->Execute(
					$this->oCommandCreator->addMailForwards($oMailForwards));
			}
		}
		$this->throwDbExceptionIfExist();
		return ($result1 && $result2) ? true : false;
	}
}
