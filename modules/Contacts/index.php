<?php

class ContactsModule extends AApiModule
{
	public $oApiContactsManager = null;
	public $oApiCapabilityManager = null;
	
	public function init() 
	{
		$this->oApiContactsManager = $this->GetManager('main', 'db');
		$this->oApiCapabilityManager = \CApi::GetCoreManager('capability');
	}
	
	private function populateSortParams($aParameters, &$iSortField, &$iSortOrder)
	{
		$sSortField = (string) $this->getParamValue($aParameters, 'SortField', 'Email');
		$iSortOrder = '1' === (string) $this->getParamValue($aParameters, 'SortOrder', '0') ?
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
	private function populateContactObject($aParameters, &$oContact, $bItsMe = false)
	{
		$iPrimaryEmail = $oContact->PrimaryEmail;
		switch (strtolower($this->getParamValue($aParameters, 'PrimaryEmail', '')))
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

		$this->paramToObject($aParameters, 'UseFriendlyName', $oContact, 'bool');

		$this->paramsStrToObjectHelper($aParameters, $oContact, 
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
			$this->paramToObject($aParameters, 'BusinessEmail', $oContact);
		}

		$this->paramToObject($aParameters, 'BirthdayDay', $oContact, 'int');
		$this->paramToObject($aParameters, 'BirthdayMonth', $oContact, 'int');
		$this->paramToObject($aParameters, 'BirthdayYear', $oContact, 'int');

		$aGroupsIds = $this->getParamValue($aParameters, 'GroupsIds');
		$aGroupsIds = is_array($aGroupsIds) ? array_map('trim', $aGroupsIds) : array();
		$oContact->GroupsIds = array_unique($aGroupsIds);
	}	
	
	/**
	 * @param \CGroup $oGroup
	 */
	private function populateGroupObject($aParameters, &$oGroup)
	{
		$this->paramToObject($aParameters, 'IsOrganization', $oGroup, 'bool');

		$this->paramsStrToObjectHelper($aParameters, $oGroup, 
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
	
	/**
	 * @return array
	 */
	public function GetGroups($aParameters)
	{
		$oAccount = $this->getDefaultAccountFromParam($aParameters);

		$aList = false;
		if ($this->oApiCapabilityManager->isPersonalContactsSupported($oAccount))
		{
			$aList = $this->oApiContactsManager->getGroupItems($oAccount->IdUser,
				\EContactSortField::Name, \ESortOrder::ASC, 0, 999);
		}
		else
		{
			throw new \Core\Exceptions\ClientException(\Core\Notifications::ContactsNotAllowed);
		}

		return $this->DefaultResponse($oAccount, __FUNCTION__, $aList);
	}
	
	/**
	 * @return array
	 */
	public function GetGroup($aParameters)
	{
		$oGroup = false;
		$oAccount = $this->getDefaultAccountFromParam($aParameters);

		if ($this->oApiCapabilityManager->isPersonalContactsSupported($oAccount))
		{
			$sGroupId = (string) $this->getParamValue('GroupId', '');
			$oGroup = $this->oApiContactsManager->getGroupById($oAccount->IdUser, $sGroupId);
		}
		else
		{
			throw new \Core\Exceptions\ClientException(\Core\Notifications::ContactsNotAllowed);
		}

		return $this->DefaultResponse($oAccount, __FUNCTION__, $oGroup);
	}

	public function GetContacts($aParameters)
	{
		$oAccount = $this->getDefaultAccountFromParam($aParameters);

		$iOffset = (int) $this->getParamValue($aParameters, 'Offset', 0);
		$iLimit = (int) $this->getParamValue($aParameters, 'Limit', 20);
		$sGroupId = (string) $this->getParamValue($aParameters, 'GroupId', '');
		$sSearch = (string) $this->getParamValue($aParameters, 'Search', '');
		$sFirstCharacter = (string) $this->getParamValue($aParameters, 'FirstCharacter', '');
		$bSharedToAll = '1' === (string) $this->getParamValue($aParameters, 'SharedToAll', '0');
		$bAll = '1' === (string) $this->getParamValue($aParameters, 'All', '0');

		$iSortField = \EContactSortField::Name;
		$iSortOrder = \ESortOrder::ASC;
		
		$iTenantId = $bSharedToAll ? $oAccount->IdTenant : null;
		
		$this->populateSortParams($aParameters, $iSortField, $iSortOrder);

		$bAllowContactsSharing = $this->oApiCapabilityManager->isSharedContactsSupported($oAccount);
		if ($bAll && !$bAllowContactsSharing &&
			!$this->oApiCapabilityManager->isGlobalContactsSupported($oAccount))
		{
			$bAll = false;
		}

		$iCount = 0;
		if ($this->oApiCapabilityManager->isPersonalContactsSupported($oAccount))
		{
			$iGroupId = ('' === $sGroupId) ? 0 : (int) $sGroupId;
			
			if ($bAllowContactsSharing && 0 < $iGroupId)
			{
				$iTenantId = $oAccount->IdTenant;
			}
			
			$iCount = $this->oApiContactsManager->getContactItemsCount(
				$oAccount->IdUser, $sSearch, $sFirstCharacter, $iGroupId, $iTenantId, $bAll);

			$aList = array();
			if (0 < $iCount)
			{
				$aContacts = $this->oApiContactsManager->getContactItems(
					$oAccount->IdUser, $iSortField, $iSortOrder, $iOffset,
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

		return $this->DefaultResponse($oAccount, __FUNCTION__, array(
			'ContactCount' => $iCount,
			'GroupId' => $sGroupId,
			'Search' => $sSearch,
			'FirstCharacter' => $sFirstCharacter,
			'All' => $bAll,
			'List' => $aList
		));		
	}
	
	/**
	 * @return array
	 */
	public function GetContactsByEmails($aParameters)
	{
		$aResult = array();
		$oAccount = $this->getDefaultAccountFromParam($aParameters);

		$sEmails = (string) $this->getParamValue($aParameters, 'Emails', '');
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

		return $this->DefaultResponse($oAccount, __FUNCTION__, $aResult);
	}	
	
	/**
	 * @return array
	 */
	public function GetGlobalContacts($aParameters)
	{
		$oAccount = $this->getDefaultAccountFromParam($aParameters);
		$oApiGlobalContacts = $this->GetManager($aParameters, 'global');

		$iOffset = (int) $this->getParamValue($aParameters, 'Offset', 0);
		$iLimit = (int) $this->getParamValue($aParameters, 'Limit', 20);
		$sSearch = (string) $this->getParamValue($aParameters, 'Search', '');

		$iSortField = \EContactSortField::EMail;
		$iSortOrder = \ESortOrder::ASC;

		$this->populateSortParams($aParameters, $iSortField, $iSortOrder);

		$iCount = 0;
		$aList = array();

		if ($this->oApiCapabilityManager->isGlobalContactsSupported($oAccount, true))
		{
			$iCount = $oApiGlobalContacts->getContactItemsCount($oAccount, $sSearch);

			if (0 < $iCount)
			{
				$aContacts = $oApiGlobalContacts->getContactItems(
					$oAccount, $iSortField, $iSortOrder, $iOffset,
					$iLimit, $sSearch);

				$aList = (is_array($aContacts)) ? $aContacts : array();
			}
		}
		else
		{
			throw new \Core\Exceptions\ClientException(\Core\Notifications::ContactsNotAllowed);
		}

		return $this->DefaultResponse($oAccount, __FUNCTION__, array(
			'ContactCount' => $iCount,
			'Search' => $sSearch,
			'List' => $aList
		));
	}	
	
	/**
	 * @return array
	 */
	public function GetContact($aParameters)
	{
		$oContact = false;
		$oAccount = $this->getDefaultAccountFromParam($aParameters);

		if ($this->oApiCapabilityManager->isPersonalContactsSupported($oAccount))
		{
			$sContactId = (string) $this->getParamValue($aParameters, 'ContactId', '');
			$bSharedToAll = '1' === (string) $this->getParamValue($aParameters, 'SharedToAll', '0');
			$iTenantId = $bSharedToAll ? $oAccount->IdTenant : null;

			$oContact = $this->oApiContactsManager->getContactById($oAccount->IdUser, $sContactId, false, $iTenantId);
		}
		else
		{
			throw new \Core\Exceptions\ClientException(\Core\Notifications::ContactsNotAllowed);
		}

		return $this->DefaultResponse($oAccount, __FUNCTION__, $oContact);
	}	
	
	/**
	 * @return array
	 */
	public function GetContactByEmail($aParameters)
	{
		$oContact = false;
		$oAccount = $this->getDefaultAccountFromParam($aParameters);
		
		$sEmail = (string) $this->getParamValue($aParameters, 'Email', '');

		if ($this->oApiCapabilityManager->isPersonalContactsSupported($oAccount))
		{
			$oContact = $this->oApiContactsManager->getContactByEmail($oAccount->IdUser, $sEmail);
		}

		if (!$oContact && $this->oApiCapabilityManager->isGlobalContactsSupported($oAccount, true))
		{
			$oApiGContacts = $this->GetManager('global');
			if ($oApiGContacts)
			{
				$oContact = $oApiGContacts->getContactByEmail($oAccount, $sEmail);
			}
		}

		return $this->DefaultResponse($oAccount, __FUNCTION__, $oContact);
	}	
	
	/**
	 * @return array
	 */
	public function GetSuggestions($aParameters)
	{
		$oAccount = $this->getDefaultAccountFromParam($aParameters);

		$sSearch = (string) $this->getParamValue($aParameters, 'Search', '');
		$bGlobalOnly = '1' === (string) $this->getParamValue($aParameters, 'GlobalOnly', '0');
		$bPhoneOnly = '1' === (string) $this->getParamValue($aParameters, 'PhoneOnly', '0');

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

		return $this->DefaultResponse($oAccount, __FUNCTION__, array(
			'Search' => $sSearch,
			'List' => $aList
		));
	}	
	
	public function DeleteSuggestion($aParameters)
	{
		$mResult = false;
		$oAccount = $this->getDefaultAccountFromParam($aParameters);

		if ($this->oApiCapabilityManager->isPersonalContactsSupported($oAccount))
		{
			$sContactId = (string) $this->getParamValue($aParameters, 'ContactId', '');
			$this->oApiContactsManager->resetContactFrequency($oAccount->IdUser, $sContactId);
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
	public function CreateContact($aParameters)
	{
		$oAccount = $this->getDefaultAccountFromParam($aParameters);
		
		if ($this->oApiCapabilityManager->isPersonalContactsSupported($oAccount))
		{
			$oContact = new \CContact();
			$oContact->IdUser = $oAccount->IdUser;
			$oContact->IdTenant = $oAccount->IdTenant;
			$oContact->IdDomain = $oAccount->IdDomain;
			$oContact->SharedToAll = '1' === $this->getParamValue($aParameters, 'SharedToAll', '0');

			$this->populateContactObject($aParameters, $oContact);

			$this->oApiContactsManager->createContact($oContact);
			return $this->DefaultResponse($oAccount, __FUNCTION__, $oContact ? array(
				'IdContact' => $oContact->IdContact
			) : false);
		}
		else
		{
			throw new \Core\Exceptions\ClientException(\Core\Notifications::ContactsNotAllowed);
		}

		return $this->FalseResponse($oAccount, __FUNCTION__);
	}	
	
	/**
	 * @return array
	 */
	public function UpdateContact($aParameters)
	{
		$oAccount = $this->getDefaultAccountFromParam($aParameters);

		$bGlobal = '1' === $this->getParamValue($aParameters, 'Global', '0');
		$sContactId = $this->getParamValue($aParameters, 'ContactId', '');

		$bSharedToAll = '1' === $this->getParamValue($aParameters, 'SharedToAll', '0');
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
				$this->populateContactObject($aParameters, $oContact, $oContact->ItsMe);

				if ($oApiContacts->updateContact($oContact))
				{
					return $this->TrueResponse($oAccount, __FUNCTION__);
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

		return $this->FalseResponse($oAccount, __FUNCTION__);
	}
	
	/**
	 * @return array
	 */
	public function DeleteContacts($aParameters)
	{
		$oAccount = $this->getDefaultAccountFromParam($aParameters);

		if ($this->oApiCapabilityManager->isPersonalContactsSupported($oAccount))
		{
			$aContactsId = explode(',', $this->getParamValue($aParameters, 'ContactsId', ''));
			$aContactsId = array_map('trim', $aContactsId);
			
			$bSharedToAll = '1' === (string) $this->getParamValue($aParameters, 'SharedToAll', '0');
			$iTenantId = $bSharedToAll ? $oAccount->IdTenant : null;

			return $this->DefaultResponse($oAccount, __FUNCTION__,
				$this->oApiContactsManager->deleteContacts($oAccount->IdUser, $aContactsId, $iTenantId));
		}
		else
		{
			throw new \Core\Exceptions\ClientException(\Core\Notifications::ContactsNotAllowed);
		}

		return $this->FalseResponse($oAccount, __FUNCTION__);
	}	
	
	/**
	 * @return array
	 */
	public function CreateGroup($aParameters)
	{
		$oAccount = $this->getDefaultAccountFromParam($aParameters);

		if ($this->oApiCapabilityManager->isPersonalContactsSupported($oAccount))
		{
			$oGroup = new \CGroup();
			$oGroup->IdUser = $oAccount->IdUser;

			$this->populateGroupObject($aParameters, $oGroup);

			$this->oApiContactsManager->createGroup($oGroup);
			return $this->DefaultResponse($oAccount, __FUNCTION__, $oGroup ? array(
				'IdGroup' => $oGroup->IdGroup
			) : false);
		}
		else
		{
			throw new \Core\Exceptions\ClientException(\Core\Notifications::ContactsNotAllowed);
		}

		return $this->FalseResponse($oAccount, __FUNCTION__);
	}	
	
	/**
	 * @return array
	 */
	public function UpdateGroup($aParameters)
	{
		$oAccount = $this->getDefaultAccountFromParam($aParameters);

		$sGroupId = $this->getParamValue($aParameters, 'GroupId', '');

		if ($this->oApiCapabilityManager->isPersonalContactsSupported($oAccount))
		{
			$oGroup = $this->oApiContactsManager->getGroupById($oAccount->IdUser, $sGroupId);
			if ($oGroup)
			{
				$this->populateGroupObject($aParameters, $oGroup);

				if ($this->oApiContactsManager->updateGroup($oGroup))
				{
					return $this->TrueResponse($oAccount, __FUNCTION__);
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
			throw new \Core\Exceptions\ClientException(
				\Core\Notifications::ContactsNotAllowed);
		}

		return $this->FalseResponse($oAccount, __FUNCTION__);
	}	
	
	/**
	 * @return array
	 */
	public function DeleteGroup($aParameters)
	{
		$oAccount = $this->getDefaultAccountFromParam($aParameters);

		if ($this->oApiCapabilityManager->isPersonalContactsSupported($oAccount))
		{
			$sGroupId = $this->getParamValue($aParameters, 'GroupId', '');

			return $this->DefaultResponse($oAccount, __FUNCTION__,
				$this->oApiContactsManager->deleteGroup($oAccount->IdUser, $sGroupId));
		}
		else
		{
			throw new \Core\Exceptions\ClientException(\Core\Notifications::ContactsNotAllowed);
		}

		return $this->FalseResponse($oAccount, __FUNCTION__);
	}
	
	/**
	 * @return array
	 */
	public function AddContactsToGroup($aParameters)
	{
		$oAccount = $this->getAccountFromParam($aParameters);

		if ($this->oApiCapabilityManager->isPersonalContactsSupported($oAccount))
		{
			$sGroupId = (string) $this->getParamValue($aParameters, 'GroupId', '');

			$aContactsId = $this->getParamValue($aParameters, 'ContactsId', null);
			if (!is_array($aContactsId))
			{
				return $this->DefaultResponse($oAccount, __FUNCTION__, false);
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

				return $this->DefaultResponse($oAccount, __FUNCTION__, $bRes1 && $bRes2);
			}
		}
		else
		{
			throw new \Core\Exceptions\ClientException(\Core\Notifications::ContactsNotAllowed);
		}

		return $this->DefaultResponse($oAccount, __FUNCTION__, false);
	}
	
	/**
	 * @return array
	 */
	public function RemoveContactsFromGroup($aParameters)
	{
		$oAccount = $this->getAccountFromParam($aParameters);

		if ($this->oApiCapabilityManager->isPersonalContactsSupported($oAccount) ||
			$this->oApiCapabilityManager->isGlobalContactsSupported($oAccount, true))
		{
			$sGroupId = (string) $this->getParamValue($aParameters, 'GroupId', '');

			$aContactsId = explode(',', $this->getParamValue($aParameters, 'ContactsId', ''));
			$aContactsId = array_map('trim', $aContactsId);

			$oGroup = $this->oApiContactsManager->getGroupById($oAccount->IdUser, $sGroupId);
			if ($oGroup)
			{
				return $this->DefaultResponse($oAccount, __FUNCTION__,
					$this->oApiContactsManager->removeContactsFromGroup($oGroup, $aContactsId));
			}

			return $this->DefaultResponse($oAccount, __FUNCTION__, false);
		}
		else
		{
			throw new \Core\Exceptions\ClientException(\Core\Notifications::ContactsNotAllowed);
		}

		return $this->DefaultResponse($oAccount, __FUNCTION__, false);
	}	
}

return new ContactsModule('1.0');
