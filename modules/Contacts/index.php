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
	public function DeleteContact($aParameters)
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
}

return new ContactsModule('1.0');
