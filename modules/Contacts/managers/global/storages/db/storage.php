<?php

/* -AFTERLOGIC LICENSE HEADER- */

/**
 * Class CApiGcontactsDbStorage is used for work with global contacts in database storage.
 * 
 * @package ContactsGlobal
 * @subpackage Storages
 */
class CApiContactsGlobalDbStorage extends CApiContactsGlobalStorage
{
	/**
	 * @var CApiContactsMainManager
	 */
	protected $oApiContactsManager;

	/**
	 * @var CDbStorage
	 */
	protected $oConnection;

	/**
	 * @var CApiGcontactsCommandCreator
	 */
	protected $oCommandCreator;

	/**
	 * Creates a new instance of the object.
	 * 
	 * @param AApiManager &$oManager Global manager object.
	 */
	public function __construct(AApiManager &$oManager)
	{
		parent::__construct('db', $oManager);

//		$this->oApiContactsManager = /* @var CApiContactsContactsManager */ CApi::Manager('contactsmain');
		
		$this->oConnection =& $oManager->GetGlobalManager()->GetConnection();
		$this->oCommandCreator =& $oManager->GetCommandCreator(
			$this, array(
				EDbType::MySQL => 'CApiContactsGlobalCommandCreatorMySQL',
				EDbType::PostgreSQL => 'CApiContactsGlobalCommandCreatorPostgreSQL'
			)
		);
	}

	/**
	 * Obtains count of all global contacts found by search-string for specified account.
	 * 
	 * @param CAccount $oAccount Account object.
	 * @param string $sSearch = '' Search-string.
	 * 
	 * @return int
	 */
	public function getContactItemsCount($oAccount, $sSearch)
	{
		$iResult = 0;
		if ($this->oConnection->Execute(
			$this->oCommandCreator->getContactItemsCountQuery($oAccount, $sSearch)))
		{
			$oRow = $this->oConnection->GetNextRecord();
			if ($oRow)
			{
				$iResult = (int) $oRow->cnt;
			}

			$this->oConnection->FreeResult();
		}
		return $iResult;
	}

	/**
	 * Obtains all global contacts by search-string for specified account.
	 * 
	 * @param CAccount $oAccount Account object.
	 * @param int $iSortField Sort field for sorting contact list.
	 * @param int $iSortOrder Sort order for sorting contact list.
	 * @param int $iOffset Offset value for obtaining a partial list.
	 * @param int $iRequestLimit Limit value for obtaining a partial list.
	 * @param string $sSearch Search string.
	 * 
	 * @return bool|array
	 */
	public function getContactItems($oAccount,
		$iSortField, $iSortOrder, $iOffset, $iRequestLimit, $sSearch)
	{
		$mContactItems = false;
		if ($this->oConnection->Execute($this->oCommandCreator->getContactItemsQuery(
			$oAccount, $iSortField, $iSortOrder, $iOffset, $iRequestLimit, $sSearch)))
		{
			$mContactItems = array();

			while (false !== ($oRow = $this->oConnection->GetNextRecord()))
			{
				$oContactItem = /** @type CContactListItem */ $this->oApiContactsManager->createContactListItemObject();
				$oContactItem->InitByDbRowWithType('global', $oRow, $oAccount->IdUser);
				$mContactItems[] = $oContactItem;
				unset($oContactItem);
			}
		}
		return $mContactItems;
	}

	/**
	 * Populates contact object by data obtained from database.
	 * 
	 * @param CAccount $oAccount Account object.
	 * @param object $oRow Row object that includes contact data.
	 * 
	 * @return CContact|null
	 */
	private function _populateContactObject($oAccount, $oRow)
	{
		$oContact = null;

		if ($oRow)
		{
			$oContact = new CContact();
			$oContact->InitByDbRow($oRow);

			if ((EContactType::Global_ === $oContact->Type || EContactType::GlobalAccounts === $oContact->Type) &&
				(string) $oAccount->IdUser === (string) $oContact->IdTypeLink)
			{
				$oContact->ReadOnly = false;
				$oContact->ItsMe = true;
			}
		}

		return $oContact;
	}

	/**
	 * Obtains contact by identifier for specified account.
	 * 
	 * @param CAccount $oAccount Account object.
	 * @param mixed $mContactId Global contact identifier.
	 * @param bool $bIgnoreHideInGab = false. If **true** all global contacts will be checked including marked as "hide in global address book".
	 * 
	 * @return CContact|bool
	 */
	public function getContactById($oAccount, $mContactId, $bIgnoreHideInGab = false)
	{
		$oContact = false;
		if ($this->oConnection->Execute($this->oCommandCreator->getContactByIdQuery(
			$oAccount, $mContactId, $bIgnoreHideInGab)))
		{
			$oContact = $this->_populateContactObject($oAccount, $this->oConnection->GetNextRecord());
			$this->oConnection->FreeResult();
		}

		$this->throwDbExceptionIfExist();
		return $oContact;
	}

	/**
	 * Populates contact object which is mailing list by data obtained from database.
	 * 
	 * @param object $oRow Row object that includes contact data.
	 * 
	 * @return CContact|null
	 */
	private function _populateContactObjectForMailingList($oRow)
	{
		$oContact = null;

		if ($oRow)
		{
			$oContact = new CContact();
			$oContact->InitByDbRow($oRow);
		}

		return $oContact;
	}

	/**
	 * Obtains contact by mailing list identifier.
	 * 
	 * @param mixed $iMailingListID Mailing list identifier.
	 * 
	 * @return CContact|bool
	 */
	public function getContactByMailingListId($iMailingListID)
	{
		$oContact = false;
		if ($this->oConnection->Execute($this->oCommandCreator->getContactByMailingListIdQuery($iMailingListID)))
		{
			$oContact = $this->_populateContactObjectForMailingList($this->oConnection->GetNextRecord());
			$this->oConnection->FreeResult();
		}

		$this->throwDbExceptionIfExist();
		return $oContact;
	}

	/**
	 * Obtains contact by type identifier for specified account.
	 * 
	 * @param CAccount $oAccount Account object.
	 * @param mixed $mGlobalContactTypeId Global contact type identifier.
	 * @param bool $bIgnoreHideInGab = false. If **true** all global contacts will be checked including marked as "hide in global address book".
	 * 
	 * @return CContact|bool
	 */
	public function getContactByTypeId($oAccount, $mContactTypeId, $bIgnoreHideInGab = false)
	{
		$oContact = false;
		if ($this->oConnection->Execute($this->oCommandCreator->getContactByTypeIdQuery(
			$oAccount, $mContactTypeId, $bIgnoreHideInGab)))
		{
			$oContact = $this->_populateContactObject($oAccount, $this->oConnection->GetNextRecord());
			$this->oConnection->FreeResult();
		}

		$this->throwDbExceptionIfExist();
		return $oContact;
	}
	
	/**
	 * Obtains contact by email for specified account.
	 * 
	 * @param CAccount $oAccount Account object.
	 * @param string $sEmail Contact email.
	 * 
	 * @return CContact|bool
	 */
	public function getContactByEmail($oAccount, $sEmail)
	{
		$oContact = false;
		if ($this->oConnection->Execute($this->oCommandCreator->getContactByEmailQuery($oAccount, $sEmail)))
		{
			$oContact = $this->_populateContactObject($oAccount, $this->oConnection->GetNextRecord());
			$this->oConnection->FreeResult();
		}

		$this->throwDbExceptionIfExist();
		return $oContact;
	}

	/**
	 * Updates contact data by contact object.
	 * 
	 * @param CContact $oContact Contact object.
	 * 
	 * @return bool
	 */
	public function updateContact($oContact)
	{
		$bResult = $this->oConnection->Execute($this->oCommandCreator->updateContactQuery($oContact));
		$this->throwDbExceptionIfExist();
		return $bResult;
	}

	/**
	 * Looks for contacts in the address book belonging to the user or the mailing list, 
	 * and if not found, adds them to the address book.
	 * 
	 * @return bool
	 */
	public function syncMissingGlobalContacts()
	{
		$bResult = false;
		if ($this->oConnection->Execute($this->oCommandCreator->getMissingGlobalContactsDataQuery()))
		{
			$aData = array();
			
			while (false !== ($oRow = $this->oConnection->GetNextRecord()))
			{
				if (isset($oRow->id_acct, $oRow->hide_in_gab) && !$oRow->hide_in_gab)
				{
					$aData[] = array(
						'id_acct' => $oRow->id_acct,
						'mailing_list' => $oRow->mailing_list,
						'id_user' => $oRow->id_user,
						'id_domain' => $oRow->id_domain,
						'id_tenant' => $oRow->id_tenant,
						'email' => $oRow->email,
						'friendly_nm' => $oRow->friendly_nm
					);
				}
			}

			$bResult = true;
			if (0 < count($aData))
			{
				$bResult = $this->oConnection->Execute($this->oCommandCreator->insertMissingGlobalContactsQuery($aData));
			}
		}

		$this->throwDbExceptionIfExist();
		return $bResult;
	}
}
