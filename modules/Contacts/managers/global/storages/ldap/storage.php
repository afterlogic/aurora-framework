<?php

/* -AFTERLOGIC LICENSE HEADER- */

/**
 * Class CApiGcontactsLdapStorage is used for work with global contacts in ldap storage.
 * 
 * @package ContactsGlobal
 * @subpackage Storages
 */
class CApiContactsGlobalLdapStorage extends CApiContactsGlobalStorage
{
	/**
	 * @var string
	 */
	private $sContactObjectClass;

	/**
	 * @var string
	 */
	private $sContactObjectClassForSearch;

	/**
	 * @var string
	 */
	private $sUidFieldName;

	/**
	 * @var string
	 */
	private $sEmailFieldName;
	
	/**
	 * @var string
	 */
	private $sNameFieldName;

	/**
	 * Creates a new instance of the object.
	 * 
	 * @param CApiGlobalManager &$oManager Global manager object.
	 */
	public function __construct(AApiManager &$oManager)
	{
		parent::__construct('ldap', $oManager);
		\CApi::ManagerInc('contactsmain', 'classes.contact-list-item' );

		$this->sContactObjectClass = strtolower(CApi::GetConf('gcontacts.ldap.contact-object-class', 'user'));
		$this->sUidFieldName = strtolower(CApi::GetConf('gcontacts.ldap.uid-field-name', 'cn'));
		$this->sEmailFieldName = strtolower(CApi::GetConf('gcontacts.ldap.email-field-name', 'mail'));
		$this->sNameFieldName = strtolower(CApi::GetConf('gcontacts.ldap.name-field-name', 'cn'));
		$this->bSkipEmptyEmail = (bool) CApi::GetConf('gcontacts.ldap.skip-empty-email', true);
		
		$this->sContactObjectClassForSearch = '(objectClass='.$this->sContactObjectClass.')';
		if ($this->bSkipEmptyEmail && 0 < strlen($this->sEmailFieldName))
		{
			$this->sContactObjectClassForSearch = '(&'.$this->sContactObjectClassForSearch.'('.$this->sEmailFieldName.'=*@*))';
		}
	}

	/**
	 * Creates if it is necessary and returns ldap connector.
	 * 
	 * @staticvar CLdapConnector|null $oLdap Contains ldap connector if it was created previously.
	 * 
	 * @param CAccount $oAccount Account object.
	 * 
	 * @return CLdapConnector|bool
	 */
	private function _getLdapConnector($oAccount)
	{
		if (!$oAccount)
		{
			return false;
		}

		static $oLdap = null;
		if (null === $oLdap)
		{
			CApi::Inc('common.ldap');

			$oLdap = new CLdapConnector((string) CApi::GetConf('gcontacts.ldap.search-dn', ''));
			$oLdap = $oLdap->Connect(
				(string) CApi::GetConf('gcontacts.ldap.host', '127.0.0.1'),
				(int) CApi::GetConf('gcontacts.ldap.port', 389),
				(string) CApi::GetConf('gcontacts.ldap.bind-dn', ''),
				(string) CApi::GetConf('gcontacts.ldap.bind-password', ''),
				(string) CApi::GetConf('gcontacts.ldap.host-backup', ''),
				(int) CApi::GetConf('gcontacts.ldap.port-backup', 389)
			) ? $oLdap : false;
		}

		return $oLdap;
	}

	/**
	 * Returns search string for ldap request.
	 * 
	 * @param CLdapConnector $oLdap Ldap connector.
	 * @param string $sSearch Search string.
	 * 
	 * @return string
	 */
	private function _getSearchLdapRequest($oLdap, $sSearch)
	{
		$sName = 0 < strlen($this->sNameFieldName) ? '('.$this->sNameFieldName.'=*'.$oLdap->Escape($sSearch).'*)' : '';
		$sEmail = 0 < strlen($this->sEmailFieldName) ? '('.$this->sEmailFieldName.'=*'.$oLdap->Escape($sSearch).'*)' : '';
		return 0 < strlen($sName.$sEmail) ? '(|'.$sName.$sEmail.')' : '';
	}

	/**
	 * Obtains count of all global contacts found by search string for specified account.
	 * 
	 * @param CAccount $oAccount Account object.
	 * @param string $sSearch = '' Search string.
	 * 
	 * @return int
	 */
	public function getContactItemsCount($oAccount, $sSearch)
	{
		$oLdap = $this->_getLdapConnector($oAccount);

		if ($oLdap)
		{
			$sFilter = $this->sContactObjectClassForSearch;
			if (0 < strlen($sSearch))
			{
				$sFilter = '(&'.$this->_getSearchLdapRequest($oLdap, $sSearch).$sFilter.')';
			}
			
			return $oLdap->Search($sFilter) ? $oLdap->ResultCount() : 0;
		}

		return 0;
	}

	/**
	 * Obtains all global contacts by search string for specified account.
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
		$oLdap = $this->_getLdapConnector($oAccount);
		
		$aContacts = false;

		if ($oLdap)
		{
			$sFilter = $this->sContactObjectClassForSearch;
			if (0 < strlen($sSearch))
			{
				$sFilter = '(&'.$this->_getSearchLdapRequest($oLdap, $sSearch).$sFilter.')';
			}

			if ($oLdap->Search($sFilter))
			{
				$aReturn = $oLdap->SortPaginate(
					EContactSortField::EMail === $iSortField ? $this->sEmailFieldName : $this->sNameFieldName,
					ESortOrder::ASC === $iSortOrder, $iOffset, $iRequestLimit);

				if ($aReturn && is_array($aReturn))
				{
					$aContacts = array();
					if (0 < count($aReturn))
					{
						foreach ($aReturn as $aItem)
						{
							$oContactItem = false;
							CApi::Plugin()->RunHook('gcontacts.contact-list-item-pre-filter', array(
								&$oContactItem, $aItem
							));

							if (false === $oContactItem && is_array($aItem))
							{
								$aItem = array_change_key_case($aItem, CASE_LOWER);
								if (isset($aItem[$this->sUidFieldName][0]))
								{
									$oContactItem = new CContactListItem();
									$oContactItem->Id = $aItem[$this->sUidFieldName][0];
									$oContactItem->IdStr = $oContactItem->Id;
									$oContactItem->Name = isset($aItem[$this->sNameFieldName][0]) ? $aItem[$this->sNameFieldName][0] : '';
									$oContactItem->Email = !empty($aItem[$this->sEmailFieldName][0]) ? $aItem[$this->sEmailFieldName][0] : '';
									$oContactItem->UseFriendlyName = true;
									$oContactItem->Global = true;
									$oContactItem->ReadOnly = true;
									$oContactItem->ItsMe = $oContactItem->Email === $oAccount->Email;
								}
							}

							CApi::Plugin()->RunHook('gcontacts.contact-list-item-post-filter', array(
								&$oContactItem, $aItem, $oAccount
							));

							if ($oContactItem)
							{
								$aContacts[] = $oContactItem;
							}

							unset($oContactItem);
						}
					}
				}
			}
		}
		
		return $aContacts;
	}

	/**
	 * Returns fields' map for contact object.
	 * 
	 * @return array
	 */
	private function _getContactMap()
	{
		$aMap = array(
			'displayName' => 'FullName',
			'cn' => 'FullName',

			'mail' => 'BusinessEmail',

			'title' => 'BusinessJobTitle',
			'company' => 'BusinessCompany',
			'department' => 'BusinessDepartment',
			'telephoneNumber' => 'BusinessPhone',
			'physicalDeliveryOfficeName' => 'BusinessOffice'
		);
		
		CApi::Plugin()->RunHook('gcontacts.contact-map-filter', array(&$aMap));

		return $aMap;
	}

	/**
	 * Populates contact object after searching by ldap request.
	 * 
	 * @param CLdapConnector $oLdap Ldap connector object.
	 * @param CAccount $oAccount Account object.
	 * 
	 * @return CContact|false
	 */
	private function _populateResultContact($oLdap, $oAccount)
	{
		$aItem = $oLdap->ResultItem();

		$oContact = false;
		CApi::Plugin()->RunHook('gcontacts.contact-item-pre-filter', array(
			&$oContact, $aItem
		));

		if (false === $oContact && is_array($aItem))
		{
			$aMap = $this->_getContactMap();

			$aMap = array_change_key_case($aMap, CASE_LOWER);
			$aItem = array_change_key_case($aItem, CASE_LOWER);

			if (isset($aItem[$this->sUidFieldName][0]))
			{
				$oContact = new CContact();
				$oContact->IdUser = $oAccount->IdUser;
				$oContact->UseFriendlyName = true;
				$oContact->ReadOnly = true;
				$oContact->Global = true;
				$oContact->IdContact = $aItem[$this->sUidFieldName][0];
				$oContact->IdContactStr = $oContact->IdContact;
				$oContact->PrimaryEmail = EPrimaryEmailType::Business;

				foreach ($aMap as $sKey => $sField)
				{
					if (isset($aItem[$sKey]) && isset($oContact->{$sField}) && 0 === strlen($oContact->{$sField}))
					{
						$oContact->{$sField} = isset($aItem[$sKey][0]) ? $aItem[$sKey][0] : '';
					}
				}

				$oContact->ViewEmail = 0 < strlen($oContact->BusinessEmail) ? $oContact->BusinessEmail : $oContact->HomeEmail;
				$oContact->ItsMe = $oContact->ViewEmail === $oAccount->Email;
			}
		}

		CApi::Plugin()->RunHook('gcontacts.contact-item-post-filter', array(
			&$oContact, $aItem, $oAccount
		));

		return $oContact ? $oContact : false;
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
		$oLdap = $this->_getLdapConnector($oAccount);

		$oResultContact = false;
		if ($oLdap && $oLdap->Search('(&'.$this->sContactObjectClassForSearch.'('.$this->sUidFieldName.'='.$oLdap->Escape($mContactId).'))'))
		{
			$oResultContact = $this->_populateResultContact($oLdap, $oAccount);
		}

		return $oResultContact;
	}

	/**
	 * Obtains contact by email for specified account.
	 * 
	 * @param CAccount $oAccount Account object.
	 * @param string $sContactEmail Contact email.
	 * 
	 * @return CContact|bool
	 */
	public function getContactByEmail($oAccount, $sContactEmail)
	{
		$oLdap = $this->_getLdapConnector($oAccount);

		$oResultContact = false;
		if ($oLdap && $oLdap->Search('(&'.$this->sContactObjectClassForSearch.'('.$this->sEmailFieldName.'='.$oLdap->Escape($sContactEmail).'))'))
		{
			$oResultContact = $this->_populateResultContact($oLdap, $oAccount);
		}

		return $oResultContact;
	}

	/**
	 * @ignore
	 * 
	 * @param CAccount $oAccount
	 * @param mixed $mGlobalContactTypeId
	 * @param bool $bIgnoreHideInGab = false
	 * 
	 * @return CContact|bool
	 */
	public function getContactByTypeId($oAccount, $mGlobalContactTypeId, $bIgnoreHideInGab = false)
	{
		return false;
	}
	
	/**
	 * @ignore
	 * 
	 * @param CContact $oContact
	 * 
	 * @return bool
	 */
	public function updateContact($oContact)
	{
		return false;
	}
	
	/**
	 * @ignore
	 * 
	 * @return bool
	 */
	public function syncMissingGlobalContacts()
	{
		return false;
	}
}
