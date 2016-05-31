<?php

class ContactsModule extends AApiModule
{
	public $oApiContactsManager = null;
	
	public function init() 
	{
		$this->oApiContactsManager = $this->GetManager('main', 'db');
		
		$this->subscribeEvent('Mail::GetBodyStructureParts', array($this, 'onGetBodyStructureParts'));
		$this->subscribeEvent('Mail::ExtendMessageData', array($this, 'onExtendMessageData'));
		$this->subscribeEvent('CreateAccount', array($this, 'onCreateAccountEvent'));
	}
	
	private function populateSortParams( &$iSortField, &$iSortOrder)
	{
		$sSortField = (string) $this->getParamValue('SortField', 'Email');
		$iSortOrder = '1' === (string) $this->getParamValue('SortOrder', '0') ?
			\ESortOrder::ASC : \ESortOrder::DESC;

		switch (strtolower($sSortField))
		{
			case 'email':
				$iSortField = \EContactSortField::EMail;
				break;
			case 'name':
				$iSortField = \EContactSortField::Name;
				break;
			case 'frequency':
				$iSortField = \EContactSortField::Frequency;
				break;
		}
	}	
	
	/**
	 * @param \CContact $oContact
	 * @param bool $bItsMe = false
	 */
	private function populateContactObject(&$oContact, $bItsMe = false)
	{
		$iPrimaryEmail = $oContact->PrimaryEmail;
		switch (strtolower($this->getParamValue('PrimaryEmail', '')))
		{
			case 'home':
			case 'personal':
				$iPrimaryEmail = \EPrimaryEmailType::Home;
				break;
			case 'business':
				$iPrimaryEmail = \EPrimaryEmailType::Business;
				break;
			case 'other':
				$iPrimaryEmail = \EPrimaryEmailType::Other;
				break;
		}

		$oContact->PrimaryEmail = $iPrimaryEmail;

		$this->paramToObject('UseFriendlyName', $oContact, 'bool');

		$this->paramsStrToObjectHelper($oContact, 
				array(
					'Title', 
					'FullName', 
					'FirstName', 
					'LastName', 
					'NickName', 
					'Skype', 
					'Facebook',

					'HomeEmail', 
					'HomeStreet', 
					'HomeCity', 
					'HomeState', 
					'HomeZip',
					'HomeCountry', 
					'HomeFax', 
					'HomePhone', 
					'HomeMobile', 
					'HomeWeb',

					'BusinessCompany', 
					'BusinessJobTitle', 
					'BusinessDepartment',
					'BusinessOffice', 
					'BusinessStreet', 
					'BusinessCity', 
					'BusinessState',  
					'BusinessZip',
					'BusinessCountry', 
					'BusinessFax',
					'BusinessPhone', 
					'BusinessMobile',  
					'BusinessWeb',

					'OtherEmail', 
					'Notes', 
					'ETag'
		));

		if (!$bItsMe)
		{
			$this->paramToObject('BusinessEmail', $oContact);
		}

		$this->paramToObject('BirthdayDay', $oContact, 'int');
		$this->paramToObject('BirthdayMonth', $oContact, 'int');
		$this->paramToObject('BirthdayYear', $oContact, 'int');

		$aGroupsIds = $this->getParamValue('GroupsIds');
		$aGroupsIds = is_array($aGroupsIds) ? array_map('trim', $aGroupsIds) : array();
		$oContact->GroupsIds = array_unique($aGroupsIds);
	}	
	
	/**
	 * @param \CGroup $oGroup
	 */
	private function populateGroupObject(&$oGroup)
	{
		$this->paramToObject('IsOrganization', $oGroup, 'bool');

		$this->paramsStrToObjectHelper($oGroup, 
				array(
					'Name', 
					'Email', 
					'Country', 
					'City', 
					'Company', 
					'Fax', 
					'Phone',
					'State', 
					'Street', 
					'Web', 
					'Zip'			
				)
		);
	}	
	
	private function DownloadContacts($sSyncType)
	{
		$oAccount = $this->getDefaultAccountFromParam();
		if ($this->oApiCapabilityManager->isContactsSupported($oAccount))
		{
			$sOutput = $this->oApiContactsManager->export($oAccount->IdUser, $sSyncType);
			if (false !== $sOutput)
			{
				header('Pragma: public');
				header('Content-Type: text/csv');
				header('Content-Disposition: attachment; filename="export.' . $sSyncType . '";');
				header('Content-Transfer-Encoding: binary');

				return $sOutput;
			}
		}
		return false;
	}
	
	/**
	 * @return array
	 */
	public function GetGroups($iUserId = 0)
	{
//		$sAuthToken = $this->getParamValue('AuthToken');
//		$iUserId = \CApi::GetCoreManager('integrator')->getLogginedUserId($sAuthToken);
//		$oAccount = $this->getDefaultAccountFromParam();

		$aList = false;
		//TODO use real user settings
//		if ($this->oApiCapabilityManager->isPersonalContactsSupported($oAccount))
		
		if ($iUserId > 0)
		{
			$aList = $this->oApiContactsManager->getGroupItems($iUserId,
				\EContactSortField::Name, \ESortOrder::ASC, 0, 999);
		}
		else
		{
			throw new \Core\Exceptions\ClientException(\Core\Notifications::ContactsNotAllowed);
		}

		return $aList;
	}
	
	/**
	 * @return array
	 */
	public function GetGroup()
	{
		$oGroup = false;
		$oAccount = $this->getDefaultAccountFromParam();

		if ($this->oApiCapabilityManager->isPersonalContactsSupported($oAccount))
		{
			$sGroupId = (string) $this->getParamValue('GroupId', '');
			$oGroup = $this->oApiContactsManager->getGroupById($oAccount->IdUser, $sGroupId);
		}
		else
		{
			throw new \Core\Exceptions\ClientException(\Core\Notifications::ContactsNotAllowed);
		}

		return $oGroup;
	}
	
	/**
	 * @return array
	 */
	public function GetGroupEvents()
	{
		$aEvents = array();
		$oAccount = $this->getDefaultAccountFromParam();

		if ($this->oApiCapabilityManager->isPersonalContactsSupported($oAccount))
		{
			$sGroupId = (string) $this->getParamValue('GroupId', '');
			$aEvents = $this->oApiContactsManager->getGroupEvents($oAccount->IdUser, $sGroupId);
		}
		else
		{
			throw new \Core\Exceptions\ClientException(\Core\Notifications::ContactsNotAllowed);
		}

		return $aEvents;
	}	
	
	/**
	 * @return array
	 */
	public function GetContacts()
	{
		$sStorage = $this->getParamValue('Storage', 'personal');
		$sMethod = 'Get' . ucfirst($sStorage) . 'Contacts';
		if (method_exists($this, $sMethod))
		{
			return call_user_func(array($this, $sMethod));
		}
		
		return false;
	}	
	
	/**
	 * @return array
	 */
	public function GetContact()
	{
		$sStorage = $this->getParamValue('Storage', 'personal');
		$sMethod = 'Get' . ucfirst($sStorage) . 'Contact';
		if (method_exists($this, $sMethod))
		{
			return call_user_func(array($this, $sMethod));
		}
		
		return false;
	}	

	public function GetAllContacts()
	{
		$this->setParamValue('All', '1');
		return $this->GetPersonalContacts();
	}

	public function GetSharedContacts()
	{
		$this->setParamValue('SharedToAll', '1');
		return $this->GetPersonalContacts();
	}

	public function GetPersonalContacts()
	{
		$sAuthToken = $this->getParamValue('AuthToken');
		$iUserId = \CApi::GetCoreManager('integrator')->getLogginedUserId($sAuthToken);
		
		$oUser = \CApi::ExecuteMethod('Core::GetUser', array(
			'AuthToken' => $sAuthToken,
			'UserId' => $iUserId
		));
		
//		$oAccount = $this->getDefaultAccountFromParam();

		$iOffset = (int) $this->getParamValue('Offset', 0);
		$iLimit = (int) $this->getParamValue('Limit', 20);
		$sGroupId = (string) $this->getParamValue('GroupId', '');
		$sSearch = (string) $this->getParamValue('Search', '');
		$sFirstCharacter = (string) $this->getParamValue('FirstCharacter', '');
		$bSharedToAll = '1' === (string) $this->getParamValue('SharedToAll', '0');
		$bAll = '1' === (string) $this->getParamValue('All', '0');

		$iSortField = \EContactSortField::Name;
		$iSortOrder = \ESortOrder::ASC;
		
		$iTenantId = $bSharedToAll ? $oUser->IdTenant : null;
		
		$this->populateSortParams($iSortField, $iSortOrder);
		
		//TODO use real user settings
//		$bAllowContactsSharing = $this->oApiCapabilityManager->isSharedContactsSupported($oAccount);
		$bAllowContactsSharing = true;
		if ($bAll && !$bAllowContactsSharing &&
			!$this->oApiCapabilityManager->isGlobalContactsSupported($oAccount))
		{
			$bAll = false;
		}

		$iCount = 0;
		$aList = array();
		
		//TODO use real user settings
//		if ($this->oApiCapabilityManager->isPersonalContactsSupported($oAccount))
		if (true)
		{
			$iGroupId = ('' === $sGroupId) ? 0 : (int) $sGroupId;
			
			if ($bAllowContactsSharing && 0 < $iGroupId)
			{
				$iTenantId = $oUser->IdTenant;
			}
			
			$iCount = $this->oApiContactsManager->getContactItemsCount(
				$iUserId, $sSearch, $sFirstCharacter, $iGroupId, $iTenantId, $bAll);

			if (0 < $iCount)
			{
				$aContacts = $this->oApiContactsManager->getContactItems(
					$iUserId, $iSortField, $iSortOrder, $iOffset,
					$iLimit, $sSearch, $sFirstCharacter, $sGroupId, $iTenantId, $bAll);

				if (is_array($aContacts))
				{
					$aList = $aContacts;
				}
			}
		}
		else
		{
			throw new \Core\Exceptions\ClientException(\Core\Notifications::ContactsNotAllowed);
		}

		return array(
			'ContactCount' => $iCount,
			'GroupId' => $sGroupId,
			'Search' => $sSearch,
			'FirstCharacter' => $sFirstCharacter,
			'All' => $bAll,
			'List' => \CApiResponseManager::GetResponseObject($aList)
		);		
	}
	
	/**
	 * @return array
	 */
	public function GetContactsByEmails()
	{
		$aResult = array();
		$oAccount = $this->getDefaultAccountFromParam();

		$sEmails = (string) $this->getParamValue('Emails', '');
		$aEmails = explode(',', $sEmails);

		if (0 < count($aEmails))
		{
			$oApiContacts = $this->oApiContactsManager;
			$oApiGlobalContacts = $this->GetManager('global');
			
			$bPab = $oApiContacts && $this->oApiCapabilityManager->isPersonalContactsSupported($oAccount);
			$bGab = $oApiGlobalContacts && $this->oApiCapabilityManager->isGlobalContactsSupported($oAccount, true);

			foreach ($aEmails as $sEmail)
			{
				$oContact = false;
				$sEmail = trim($sEmail);
				
				if ($bPab)
				{
					$oContact = $oApiContacts->getContactByEmail($oAccount->IdUser, $sEmail);
				}

				if (!$oContact && $bGab)
				{
					$oContact = $oApiGlobalContacts->getContactByEmail($oAccount, $sEmail);
				}

				if ($oContact)
				{
					$aResult[$sEmail] = $oContact;
				}
			}
		}

		return $aResult;
	}	
	
	/**
	 * @return array
	 */
	public function GetGlobalContacts()
	{
		$oAccount = $this->getDefaultAccountFromParam();
		$oApiGlobalContacts = $this->GetManager('global');

		$iOffset = (int) $this->getParamValue('Offset', 0);
		$iLimit = (int) $this->getParamValue('Limit', 20);
		$sSearch = (string) $this->getParamValue('Search', '');

		$iSortField = \EContactSortField::EMail;
		$iSortOrder = \ESortOrder::ASC;

		$this->populateSortParams($iSortField, $iSortOrder);

		$iCount = 0;
		$aList = array();

		if ($this->oApiCapabilityManager->isGlobalContactsSupported($oAccount, true))
		{
			$iCount = $oApiGlobalContacts->getContactItemsCount($oAccount, $sSearch);

			if (0 < $iCount)
			{
				$aContacts = $oApiGlobalContacts->getContactItems(
					$oAccount, $iSortField, $iSortOrder, $iOffset,
					$iLimit, $sSearch
				);

				$aList = (is_array($aContacts)) ? $aContacts : array();
			}
		}
		else
		{
			throw new \Core\Exceptions\ClientException(\Core\Notifications::ContactsNotAllowed);
		}

		return array(
			'ContactCount' => $iCount,
			'Search' => $sSearch,
			'List' => $aList
		);
	}	
	
	
	
	/**
	 * @return array
	 */
	public function GetGlobalContact()
	{
		$oContact = false;
		$oAccount = $this->getDefaultAccountFromParam();
		$sContactId = (string) $this->getParamValue('ContactId', '');
		
		if ($this->oApiCapabilityManager->isGlobalContactsSupported($oAccount)) {

			$oApiGlobalContacts = $this->GetManager('global');
			$oContact = $oApiGlobalContacts->getContactById($oAccount, $sContactId);

		} else {

			throw new \Core\Exceptions\ClientException(\Core\Notifications::ContactsNotAllowed);
		}

		return $oContact;
	}	
	
	public function GetPersonalContact()
	{
		$oContact = false;
		$oAccount = $this->getDefaultAccountFromParam();
		$sContactId = (string) $this->getParamValue('ContactId', '');

		if ($this->oApiCapabilityManager->isPersonalContactsSupported($oAccount)) {

			$bSharedToAll = '1' === (string) $this->getParamValue('SharedToAll', '0');
			$iTenantId = $bSharedToAll ? $oAccount->IdTenant : null;

			$oContact = $this->oApiContactsManager->getContactById($oAccount->IdUser, $sContactId, false, $iTenantId);
		} else {

			throw new \Core\Exceptions\ClientException(\Core\Notifications::ContactsNotAllowed);
		}
		
		return $oContact;
	}
	
	public function GetSharedContact()
	{
		$this->setParamValue('SharedToAll', '1');
		return $this->GetPersonalContact();
	}
	
	
	public function DownloadContactsAsCSV()
	{
		return $this->DownloadContacts('csv');
	}
	
	public function DownloadContactsAsVCF()
	{
		return $this->DownloadContacts('vcf');
	}

	/**
	 * @return array
	 */
	public function GetContactByEmail()
	{
		$oContact = false;
		$oAccount = $this->getDefaultAccountFromParam();
		
		$sEmail = (string) $this->getParamValue('Email', '');

		if ($this->oApiCapabilityManager->isPersonalContactsSupported($oAccount)) {
			
			$oContact = $this->oApiContactsManager->getContactByEmail($oAccount->IdUser, $sEmail);
		}

		if (!$oContact && $this->oApiCapabilityManager->isGlobalContactsSupported($oAccount, true)) {
			
			$oApiGContacts = $this->GetManager('global');
			if ($oApiGContacts) {
				
				$oContact = $oApiGContacts->getContactByEmail($oAccount, $sEmail);
			}
		}

		return $oContact;
	}	
	
	/**
	 * @return array
	 */
	public function GetSuggestions()
	{
		$oAccount = $this->getDefaultAccountFromParam();

		$sSearch = (string) $this->getParamValue('Search', '');
		$bGlobalOnly = '1' === (string) $this->getParamValue('GlobalOnly', '0');
		$bPhoneOnly = '1' === (string) $this->getParamValue('PhoneOnly', '0');

		$aList = array();
		
		$iSharedTenantId = null;
		if ($this->oApiCapabilityManager->isSharedContactsSupported($oAccount) && !$bPhoneOnly)
		{
			$iSharedTenantId = $oAccount->IdTenant;
		}

		if ($this->oApiCapabilityManager->isContactsSupported($oAccount))
		{
			$aContacts = 	$this->oApiContactsManager->getSuggestItems($oAccount, $sSearch,
					\CApi::GetConf('webmail.suggest-contacts-limit', 20), $bGlobalOnly, $bPhoneOnly, $iSharedTenantId);

			if (is_array($aContacts))
			{
				$aList = $aContacts;
			}
		}

		$aCounts = array(0, 0);
		
		\CApi::Plugin()->RunHook('webmail.change-suggest-list', array($oAccount, $sSearch, &$aList, &$aCounts));

		return array(
			'Search' => $sSearch,
			'List' => $aList
		);
	}	
	
	public function DeleteSuggestion()
	{
		$mResult = false;
		$oAccount = $this->getDefaultAccountFromParam();

		if ($this->oApiCapabilityManager->isPersonalContactsSupported($oAccount))
		{
			$sContactId = (string) $this->getParamValue('ContactId', '');
			$this->oApiContactsManager->resetContactFrequency($oAccount->IdUser, $sContactId);
		}
		else
		{
			throw new \Core\Exceptions\ClientException(\Core\Notifications::ContactsNotAllowed);
		}

		return $mResult;
	}	
	
	public function UpdateSuggestTable()
	{
		$oAccount = $this->getDefaultAccountFromParam();
		$aEmails = $this->getParamValue('Emails', array());
		$this->oApiContactsManager->updateSuggestTable($oAccount->IdUser, $aEmails);
	}
	
	/**
	 * @return array
	 */
	public function CreateContact()
	{
		$oAccount = $this->getDefaultAccountFromParam();
		
		if ($this->oApiCapabilityManager->isPersonalContactsSupported($oAccount))
		{
			$oContact = \CContact::createInstance();
			$oContact->IdUser = $oAccount->IdUser;
			$oContact->IdTenant = $oAccount->IdTenant;
			$oContact->IdDomain = $oAccount->IdDomain;
			$oContact->SharedToAll = '1' === $this->getParamValue('SharedToAll', '0');

			$this->populateContactObject($oContact);

			$this->oApiContactsManager->createContact($oContact);
			return $oContact ? array(
				'IdContact' => $oContact->IdContact
			) : false;
		}
		else
		{
			throw new \Core\Exceptions\ClientException(\Core\Notifications::ContactsNotAllowed);
		}

		return false;
	}	
	
	/**
	 * @return array
	 */
	public function UpdateContact()
	{
		$oAccount = $this->getDefaultAccountFromParam();

		$bGlobal = '1' === $this->getParamValue('Global', '0');
		$sContactId = $this->getParamValue('ContactId', '');

		$bSharedToAll = '1' === $this->getParamValue('SharedToAll', '0');
		$iTenantId = $bSharedToAll ? $oAccount->IdTenant : null;

		if ($bGlobal && $this->oApiCapabilityManager->isGlobalContactsSupported($oAccount, true))
		{
			$oApiContacts = $this->GetManager('global');
		}
		else if (!$bGlobal && $this->oApiCapabilityManager->isPersonalContactsSupported($oAccount))
		{
			$oApiContacts = $this->oApiContactsManager;
		}

		if ($oApiContacts)
		{
			$oContact = $oApiContacts->getContactById($bGlobal ? $oAccount : $oAccount->IdUser, $sContactId, false, $iTenantId);
			if ($oContact)
			{
				$this->populateContactObject($oContact, $oContact->ItsMe);

				if ($oApiContacts->updateContact($oContact))
				{
					return true;
				}
				else
				{
					switch ($oApiContacts->getLastErrorCode())
					{
						case \Errs::Sabre_PreconditionFailed:
							throw new \Core\Exceptions\ClientException(
								\Core\Notifications::ContactDataHasBeenModifiedByAnotherApplication);
					}
				}
			}
		}
		else
		{
			throw new \Core\Exceptions\ClientException(\Core\Notifications::ContactsNotAllowed);
		}

		return false;
	}
	
	/**
	 * @return array
	 */
	public function DeleteContacts()
	{
		$oAccount = $this->getDefaultAccountFromParam();

		if ($this->oApiCapabilityManager->isPersonalContactsSupported($oAccount))
		{
			$aContactsId = explode(',', $this->getParamValue('ContactsId', ''));
			$aContactsId = array_map('trim', $aContactsId);
			
			$bSharedToAll = '1' === (string) $this->getParamValue('SharedToAll', '0');
			$iTenantId = $bSharedToAll ? $oAccount->IdTenant : null;

			return $this->oApiContactsManager->deleteContacts($oAccount->IdUser, $aContactsId, $iTenantId);
		}
		else
		{
			throw new \Core\Exceptions\ClientException(\Core\Notifications::ContactsNotAllowed);
		}

		return false;
	}	
	
	/**
	 * @return array
	 */
	public function UpdateShared()
	{
		$oAccount = $this->getDefaultAccountFromParam();
		
		$aContactsId = explode(',', $this->getParamValue('ContactsId', ''));
		$aContactsId = array_map('trim', $aContactsId);
		
		$bSharedToAll = '1' === $this->getParamValue('SharedToAll', '0');
		$iTenantId = $bSharedToAll ? $oAccount->IdTenant : null;

		if ($this->oApiCapabilityManager->isPersonalContactsSupported($oAccount))
		{
			$oApiContacts = $this->oApiContactsManager;
		}

		if ($oApiContacts && $this->oApiCapabilityManager->isSharedContactsSupported($oAccount))
		{
			foreach ($aContactsId as $sContactId)
			{
				$oContact = $oApiContacts->getContactById($oAccount->IdUser, $sContactId, false, $iTenantId);
				if ($oContact)
				{
					if ($oContact->SharedToAll)
					{
						$oApiContacts->updateContactUserId($oContact, $oAccount->IdUser);
					}

					$oContact->SharedToAll = !$oContact->SharedToAll;
					$oContact->IdUser = $oAccount->IdUser;
					$oContact->IdDomain = $oAccount->IdDomain;
					$oContact->IdTenant = $oAccount->IdTenant;

					if (!$oApiContacts->updateContact($oContact))
					{
						switch ($oApiContacts->getLastErrorCode())
						{
							case \Errs::Sabre_PreconditionFailed:
								throw new \Core\Exceptions\ClientException(
									\Core\Notifications::ContactDataHasBeenModifiedByAnotherApplication);
						}
					}
				}
			}
			
			return true;
		}
		else
		{
			throw new \Core\Exceptions\ClientException(\Core\Notifications::ContactsNotAllowed);
		}

		return false;
	}	
	
	/**
	 * @return array
	 */
	public function AddContactsFromFile()
	{
		$oAccount = $this->getDefaultAccountFromParam();

		$mResult = false;

		if (!$this->oApiCapabilityManager->isPersonalContactsSupported($oAccount))
		{
			throw new \Core\Exceptions\ClientException(\Core\Notifications::ContactsNotAllowed);
		}

		$sTempFile = (string) $this->getParamValue('File', '');
		if (empty($sTempFile))
		{
			throw new \Core\Exceptions\ClientException(\Core\Notifications::InvalidInputParameter);
		}

		$oApiFileCache = /* @var $oApiFileCache \CApiFilecacheManager */ \CApi::GetCoreManager('filecache');
		$sData = $oApiFileCache->get($oAccount, $sTempFile);
		if (!empty($sData))
		{
			$oContact = \CContact::createInstance();
			$oContact->InitFromVCardStr($oAccount->IdUser, $sData);

			if ($this->oApiContactsManager->createContact($oContact))
			{
				$mResult = array(
					'Uid' => $oContact->IdContact
				);
			}
		}

		return $mResult;
	}	
	
	/**
	 * @return array
	 */
	public function CreateGroup()
	{
		$oAccount = $this->getDefaultAccountFromParam();

		if ($this->oApiCapabilityManager->isPersonalContactsSupported($oAccount))
		{
			$oGroup = new \CGroup();
			$oGroup->IdUser = $oAccount->IdUser;

			$this->populateGroupObject($oGroup);

			$this->oApiContactsManager->createGroup($oGroup);
			return $oGroup ? array(
				'IdGroup' => $oGroup->IdGroup
			) : false;
		}
		else
		{
			throw new \Core\Exceptions\ClientException(\Core\Notifications::ContactsNotAllowed);
		}

		return false;
	}	
	
	/**
	 * @return array
	 */
	public function UpdateGroup()
	{
		$oAccount = $this->getDefaultAccountFromParam();

		$sGroupId = $this->getParamValue('GroupId', '');

		if ($this->oApiCapabilityManager->isPersonalContactsSupported($oAccount))
		{
			$oGroup = $this->oApiContactsManager->getGroupById($oAccount->IdUser, $sGroupId);
			if ($oGroup)
			{
				$this->populateGroupObject($oGroup);

				if ($this->oApiContactsManager->updateGroup($oGroup))
				{
					return true;
				}
				else
				{
					switch ($this->oApiContactsManager->getLastErrorCode())
					{
						case \Errs::Sabre_PreconditionFailed:
							throw new \Core\Exceptions\ClientException(
								\Core\Notifications::ContactDataHasBeenModifiedByAnotherApplication);
					}
				}
			}
		}
		else
		{
			throw new \Core\Exceptions\ClientException(
				\Core\Notifications::ContactsNotAllowed);
		}

		return false;
	}	
	
	/**
	 * @return array
	 */
	public function DeleteGroup()
	{
		$oAccount = $this->getDefaultAccountFromParam();

		if ($this->oApiCapabilityManager->isPersonalContactsSupported($oAccount))
		{
			$sGroupId = $this->getParamValue('GroupId', '');

			return $this->oApiContactsManager->deleteGroup($oAccount->IdUser, $sGroupId);
		}
		else
		{
			throw new \Core\Exceptions\ClientException(\Core\Notifications::ContactsNotAllowed);
		}

		return false;
	}
	
	/**
	 * @return array
	 */
	public function AddContactsToGroup()
	{
		$oAccount = $this->getDefaultAccountFromParam();

		if ($this->oApiCapabilityManager->isPersonalContactsSupported($oAccount))
		{
			$sGroupId = (string) $this->getParamValue('GroupId', '');

			$aContactsId = $this->getParamValue('ContactsId', null);
			if (!is_array($aContactsId))
			{
				return false;
			}

			$oGroup = $this->oApiContactsManager->getGroupById($oAccount->IdUser, $sGroupId);
			if ($oGroup)
			{
				$aLocalContacts = array();
				$aGlobalContacts = array();
				
				foreach ($aContactsId as $aItem)
				{
					if (is_array($aItem) && 2 === count($aItem))
					{
						if ('1' === $aItem[1])
						{
							$aGlobalContacts[] = $aItem[0];
						}
						else
						{
							$aLocalContacts[] = $aItem[0];
						}
					}
				}

				$bRes1 = true;
				if (0 < count($aGlobalContacts))
				{
					$bRes1 = false;
					if (!$this->oApiCapabilityManager->isGlobalContactsSupported($oAccount, true))
					{
						throw new \Core\Exceptions\ClientException(\Core\Notifications::ContactsNotAllowed);
					}

					$bRes1 = $this->oApiContactsManager->addGlobalContactsToGroup($oAccount, $oGroup, $aGlobalContacts);
				}

				$bRes2 = true;
				if (0 < count($aLocalContacts))
				{
					$bRes2 = $this->oApiContactsManager->addContactsToGroup($oGroup, $aLocalContacts);
				}

				return $bRes1 && $bRes2;
			}
		}
		else
		{
			throw new \Core\Exceptions\ClientException(\Core\Notifications::ContactsNotAllowed);
		}

		return false;
	}
	
	/**
	 * @return array
	 */
	public function RemoveContactsFromGroup()
	{
		$oAccount = $this->getDefaultAccountFromParam();

		if ($this->oApiCapabilityManager->isPersonalContactsSupported($oAccount) ||
			$this->oApiCapabilityManager->isGlobalContactsSupported($oAccount, true))
		{
			$sGroupId = (string) $this->getParamValue('GroupId', '');

			$aContactsId = explode(',', $this->getParamValue('ContactsId', ''));
			$aContactsId = array_map('trim', $aContactsId);

			$oGroup = $this->oApiContactsManager->getGroupById($oAccount->IdUser, $sGroupId);
			if ($oGroup)
			{
				return $this->oApiContactsManager->removeContactsFromGroup($oGroup, $aContactsId);
			}

			return false;
		}
		else
		{
			throw new \Core\Exceptions\ClientException(\Core\Notifications::ContactsNotAllowed);
		}

		return false;
	}	
	
	public function SynchronizeExternalContacts()
	{
		$oAccount = $this->getParamValue('Account', null);
		if ($oAccount)
		{
			return $this->oApiContactsManager->SynchronizeExternalContacts($oAccount);
		}
		
	}
	
	public function onGetBodyStructureParts($aParts, &$aResultParts)
	{
		foreach ($aParts as $oPart)
		{
			if ($oPart instanceof \MailSo\Imap\BodyStructure && 
					($oPart->ContentType() === 'text/vcard' || $oPart->ContentType() === 'text/x-vcard'))
			{
				$aResultParts[] = $oPart;
			}
		}
	}
	
	public function UploadContacts()
	{
		$oAccount = $this->getDefaultAccountFromParam();

		if (!$this->oApiCapabilityManager->isPersonalContactsSupported($oAccount))
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

		if (is_array($aFileData)) {
			
			$sFileType = strtolower(\api_Utils::GetFileExtension($aFileData['name']));
			$bIsCsvVcfExtension  = $sFileType === 'csv' || $sFileType === 'vcf';

			if ($bIsCsvVcfExtension) {
				
				$oApiFileCacheManager = \CApi::GetCoreManager('filecache');
				$sSavedName = 'import-post-' . md5($aFileData['name'] . $aFileData['tmp_name']);
				if ($oApiFileCacheManager->moveUploadedFile($oAccount, $sSavedName, $aFileData['tmp_name'])) {
						$iParsedCount = 0;

						$iImportedCount = $this->oApiContactsManager->import(
							$oAccount->IdUser,
							$sFileType,
							$oApiFileCacheManager->generateFullFilePath($oAccount, $sSavedName),
							$iParsedCount,
							$iGroupId = $aAdditionalData['GroupId'],
							$bIsShared = $aAdditionalData['IsShared']
						);

					if (false !== $iImportedCount && -1 !== $iImportedCount) {
						
						$aResponse['ImportedCount'] = $iImportedCount;
						$aResponse['ParsedCount'] = $iParsedCount;
					} else {
						
						$sError = 'unknown';
					}

					$oApiFileCacheManager->clear($oAccount, $sSavedName);
				} else {
					
					$sError = 'unknown';
				}
			} else {
				
				throw new \Core\Exceptions\ClientException(\Core\Notifications::IncorrectFileExtension);
			}
		}
		else {
			
			$sError = 'unknown';
		}

		if (0 < strlen($sError)) {
			
			$aResponse['Error'] = $sError;
		}

		return $aResponse;
		
	}
	
	public function onExtendMessageData($oAccount, &$oMessage, $aData)
	{
		$oApiCapa = /* @var CApiCapabilityManager */ $this->oApiCapabilityManager;
		$oApiFileCache = /* @var CApiFilecacheManager */ CApi::GetCoreManager('filecache');

		foreach ($aData as $aDataItem) {
			
			if ($aDataItem['Part'] instanceof \MailSo\Imap\BodyStructure && 
					($aDataItem['Part']->ContentType() === 'text/vcard' || 
					$aDataItem['Part']->ContentType() === 'text/x-vcard')) {
				$sData = $aDataItem['Data'];
				if (!empty($sData) && $oApiCapa->isContactsSupported($oAccount)) {
					
					$oContact = \CContact::createInstance();
					$oContact->InitFromVCardStr($oAccount->IdUser, $sData);
					$oContact->initBeforeChange();

					$oContact->IdContact = 0;

					$bContactExists = false;
					if (0 < strlen($oContact->ViewEmail)) {
						
						$oLocalContact = $this->oApiContactsManager->getContactByEmail($oAccount->IdUser, $oContact->ViewEmail);
						if ($oLocalContact) {
							
							$oContact->IdContact = $oLocalContact->IdContact;
							$bContactExists = true;
						}
					}

					$sTemptFile = md5($sData).'.vcf';
					if ($oApiFileCache && $oApiFileCache->put($oAccount, $sTemptFile, $sData)) {
						
						$oVcard = CApiMailVcard::createInstance();

						$oVcard->Uid = $oContact->IdContact;
						$oVcard->File = $sTemptFile;
						$oVcard->Exists = !!$bContactExists;
						$oVcard->Name = $oContact->FullName;
						$oVcard->Email = $oContact->ViewEmail;

						$oMessage->addExtend('VCARD', $oVcard);
					} else {
						
						CApi::Log('Can\'t save temp file "'.$sTemptFile.'"', ELogLevel::Error);
					}					
				}
			}
		}
	}	
	
	public function onCreateAccountEvent($oAccount)
	{
		if ($oAccount instanceof \CAccount) {
			
			$oContact = $this->oApiContactsManager->createContactObject();
			$oContact->BusinessEmail = $oAccount->Email;
			$oContact->PrimaryEmail = EPrimaryEmailType::Business;
			$oContact->FullName = $oAccount->FriendlyName;
			$oContact->Type = EContactType::GlobalAccounts;

			$oContact->IdTypeLink = $oAccount->IdUser;
			$oContact->IdDomain = 0 < $oAccount->IdDomain ? $oAccount->IdDomain : 0;
			$oContact->IdTenant = $oAccount->Domain ? $oAccount->Domain->IdTenant : 0;

			$this->oApiContactsManager->createContact($oContact);
		}
	}
	
}