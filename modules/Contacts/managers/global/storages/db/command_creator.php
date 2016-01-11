<?php

/* -AFTERLOGIC LICENSE HEADER- */

/**
 * Class CApiContactsGlobalCommandCreator is used for creating query-strings which is used in database requests.
 * 
 * @package ContactsGlobal
 * @subpackage Storages
 */
class CApiContactsGlobalDbCommandCreator extends api_CommandCreator
{
	/**
	 * Returns common part of most queries.
	 * 
	 * @param CAccount $oAccount Account object.
	 * 
	 * @return array
	 */
	protected function _getCommonSubQuery($oAccount)
	{
		$sSubAdd = '1 = 0';
		if (EContactsGABVisibility::Off !== $oAccount->GlobalAddressBook)
		{
			if (0 <= $oAccount->IdDomain && $oAccount->Domain)
			{
				if (EContactsGABVisibility::DomainWide === $oAccount->GlobalAddressBook)
				{
					$sSubAdd = sprintf('id_domain = %d', $oAccount->IdDomain);
				}
				else if (EContactsGABVisibility::TenantWide === $oAccount->GlobalAddressBook)
				{
					$sSubAdd = sprintf('id_tenant = %d', $oAccount->Domain->IdTenant);
				}
				else if (EContactsGABVisibility::SystemWide === $oAccount->GlobalAddressBook)
				{
					$sSubAdd = '1 = 1';
				}
			}
		}

		return '('.$sSubAdd.
			sprintf(' AND (type = %d OR type = %d)', EContactType::GlobalAccounts, EContactType::GlobalMailingList).
		')';
	}

	/**
	 * Returns search part of most queries.
	 * 
	 * @param string $sSearch Search string.
	 *
	 * @return array
	 */
	protected function _getSearchSubQuery($sSearch)
	{
		$sSearchAdd = '';
		if (0 < strlen($sSearch))
		{
			$bPhone = api_Utils::IsPhoneSearch($sSearch);
			$sPhoneSearch = $bPhone ? api_Utils::ClearPhoneSearch($sSearch) : '';

			$sSearch = '\'%'.$this->escapeString($sSearch, true, true).'%\'';

			$sSearchAdd .= sprintf('fullname LIKE %s OR firstname LIKE %s OR surname LIKE %s OR nickname LIKE %s OR h_email LIKE %s OR b_email LIKE %s OR other_email LIKE %s',
				$sSearch, $sSearch, $sSearch, $sSearch, $sSearch, $sSearch, $sSearch);

			if (0 < strlen($sPhoneSearch))
			{
				$sPhoneSearch = '\'%'.$this->escapeString($sPhoneSearch, true, true).'%\'';
				
				$sSearchAdd .= sprintf(' OR '.
					'(b_phone <> \'\' AND REPLACE(REPLACE(REPLACE(REPLACE(b_phone, \'(\', \'\'), \')\', \'\'), \'+\', \'\'), \' \', \'\') LIKE %s) OR '.
					'(h_phone <> \'\' AND REPLACE(REPLACE(REPLACE(REPLACE(h_phone, \'(\', \'\'), \')\', \'\'), \'+\', \'\'), \' \', \'\') LIKE %s) OR '.
					'(h_mobile <> \'\' AND REPLACE(REPLACE(REPLACE(REPLACE(h_mobile, \'(\', \'\'), \')\', \'\'), \'+\', \'\'), \' \', \'\') LIKE %s)',
					 $sPhoneSearch, $sPhoneSearch, $sPhoneSearch);
			}
		}

		return $sSearchAdd;
	}

	/**
	 * Returns query-string with specified WHERE-part for found contact.
	 * 
	 * @param string $sWhere WHERE-part for query.
	 * @param bool $bIgnoreHideInGab = false. If **true** all global contacts will be checked including marked as "hide in global address book".
	 * 
	 * @return string
	 */
	protected function _getContactQueryByWhere($sWhere, $bIgnoreHideInGab = false)
	{
		$aMap = api_AContainer::DbReadKeys(CContact::getStaticMap());
		$aMap = array_map(array($this, 'escapeColumn'), $aMap);

		$sHideInGab = $bIgnoreHideInGab ? '' : ' AND hide_in_gab = 0';
		$sSql = 'SELECT %s FROM %sawm_addr_book WHERE deleted = 0'.$sHideInGab.' AND auto_create = 0 AND %s';

		return sprintf($sSql, implode(', ', $aMap), $this->prefix(), $sWhere);
	}

	/**
	 * Returns query-string for obtaining count of all global contacts found by search-string for specified account.
	 * 
	 * @param CAccount $oAccount Account object.
	 * @param string $sSearch = '' Search-string.
	 * 
	 * @return string
	 */
	public function getContactItemsCountQuery($oAccount, $sSearch = '')
	{
		$sSubAdd = $this->_getCommonSubQuery($oAccount);
		$sSearchAdd = $this->_getSearchSubQuery($sSearch);
		
		$sSql = 'SELECT count(id_addr) as cnt FROM %sawm_addr_book WHERE hide_in_gab = 0 AND %s%s';

		return sprintf($sSql, $this->prefix(), $sSubAdd,
			0 < strlen($sSearchAdd) ? ' AND ('.$sSearchAdd.')' : '');
	}

	/**
	 * Returns query-string for obtaining contact by identifier for specified account.
	 * 
	 * @param CAccount $oAccount Account object.
	 * @param mixed $mContactId Global contact identifier.
	 * @param bool $bIgnoreHideInGab = false. If **true** all global contacts will be checked including marked as "hide in global address book".
	 * 
	 * @return string
	 */
	public function getContactByIdQuery($oAccount, $mContactId, $bIgnoreHideInGab = false)
	{
		$sSubAdd = $this->_getCommonSubQuery($oAccount);

		return $this->_getContactQueryByWhere(sprintf('%s AND %s = %s',
			$sSubAdd, $this->escapeColumn('id_addr'), $this->escapeString($mContactId)), $bIgnoreHideInGab);
	}

	/**
	 * Returns query-string for obtaining contact by type identifier for specified account.
	 * 
	 * @param CAccount $oAccount Account object.
	 * @param mixed $mGlobalContactTypeId Global contact type identifier.
	 * @param bool $bIgnoreHideInGab = false. If **true** all global contacts will be checked including marked as "hide in global address book".
	 * 
	 * @return string
	 */
	public function getContactByTypeIdQuery($oAccount, $mContactTypeId, $bIgnoreHideInGab = false)
	{
		$sSubAdd = $this->_getCommonSubQuery($oAccount);

		return $this->_getContactQueryByWhere(sprintf('%s AND %s = %s',
			$sSubAdd, $this->escapeColumn('type_id'), $this->escapeString($mContactTypeId)), $bIgnoreHideInGab);
	}

	/**
	 * Returns query-string for obtaining contact by mailing list identifier.
	 * 
	 * @param mixed $iMailingListID Mailing list identifier.
	 * 
	 * @return string
	 */
	public function getContactByMailingListIdQuery($iMailingListID)
	{
		return $this->_getContactQueryByWhere(sprintf('%s = %d AND %s = %s',
			$this->escapeColumn('type'), EContactType::GlobalMailingList,
			$this->escapeColumn('type_id'), $this->escapeString($iMailingListID)
		), true);
	}

	/**
	 * Returns query-string for obtaining contact by email for specified account.
	 * 
	 * @param CAccount $oAccount Account object.
	 * @param string $sEmail Contact email.
	 * 
	 * @return string
	 */
	public function getContactByEmailQuery($oAccount, $sEmail)
	{
		$sSubAdd = $this->_getCommonSubQuery($oAccount);

		$sSearch = '\'%'.$this->escapeString($sEmail, true, true).'%\'';
		
		$sSearchAdd = sprintf(' AND (h_email LIKE %s OR b_email LIKE %s OR other_email LIKE %s)',
			$sSearch, $sSearch, $sSearch);

		return $this->_getContactQueryByWhere($sSubAdd.$sSearchAdd);
	}

	/**
	 * Returns query-string for updating contact data by contact object.
	 * 
	 * @param CContact $oContact Contact object.
	 * 
	 * @return string
	 */
	public function updateContactQuery($oContact)
	{
		$sSql = 'UPDATE %sawm_addr_book SET %s WHERE id_addr = %d AND (%s = %d OR %s = %d)';
		return sprintf($sSql, $this->prefix(),
			implode(', ', api_AContainer::DbUpdateArray($oContact, $this->oHelper)), $oContact->IdContact,
			$this->oHelper->EscapeColumn('type'), EContactType::GlobalAccounts,
			$this->oHelper->EscapeColumn('type'), EContactType::GlobalMailingList
		);
	}

	/**
	 * Returns query-string for looking for contacts in the address book belonging to the user or the mailing list.
	 * 
	 * @return string
	 */
	public function getMissingGlobalContactsDataQuery()
	{
		$sSql = 'SELECT acc.def_acct, acc.mailing_list, acc.id_acct, acc.id_user, acc.email, acc.friendly_nm, acc.id_domain, acc.id_tenant, acc.hide_in_gab
FROM %sawm_accounts AS acc INNER JOIN %sawm_domains AS dom ON dom.id_domain = acc.id_domain
WHERE acc.hide_in_gab = 0 AND
(
	(acc.def_acct = 1 AND acc.mailing_list = 0 AND acc.id_user NOT IN (SELECT addr1.type_id FROM %sawm_addr_book AS addr1 WHERE addr1.type = %d))
	OR
	(acc.mailing_list = 1 AND acc.id_acct NOT IN (SELECT addr2.type_id FROM %sawm_addr_book AS addr2 WHERE addr2.type = %d))
)
';
		return sprintf($sSql, $this->prefix(), $this->prefix(), $this->prefix(),
			EContactType::GlobalAccounts, $this->prefix(), EContactType::GlobalMailingList);
	}

	/**
	 * Returns query-string for adding contacts to the address book.
	 * 
	 * @return string
	 */
	public function insertMissingGlobalContactsQuery($aData)
	{
		$sNow = $this->oHelper->TimeStampToDateFormat(time(), true);

		$sSql = sprintf('INSERT INTO %sawm_addr_book
(id_user, id_domain, id_tenant, b_email, view_email, fullname, primary_email, date_created, date_modified, type_id, type) VALUES ', $this->prefix());

		$aValues = array();
		foreach ($aData as $aItem)
		{
			$sEmail = $this->escapeString($aItem['email']);
			$sFullName = $this->escapeString($aItem['friendly_nm']);

			$aValues[] = sprintf(
				'(%d, %d, %d, %s, %s, %s, %d, %s, %s, %s, %d)',
				0,
				$aItem['id_domain'],
				$aItem['id_tenant'],
				$sEmail,
				$sEmail,
				$sFullName,
				EPrimaryEmailType::Business,
				$sNow,
				$sNow,
				$this->escapeString(1 === (int) $aItem['mailing_list'] ? $aItem['id_acct'] : $aItem['id_user']),
				1 === (int) $aItem['mailing_list'] ? EContactType::GlobalMailingList : EContactType::GlobalAccounts
			);
		}
		
		return $sSql.implode(', ', $aValues);
	}
}

/**
 * Class CApiContactsGlobalCommandCreatorMySQL is used for creating query-strings which is used in MySQL database requests.
 * 
 * @package ContactsGlobal
 * @subpackage Storages
 */
class CApiContactsGlobalDbCommandCreatorMySQL extends CApiContactsGlobalDbCommandCreator
{
	/**
	 * Returns query-string for obtaining all global contacts by search string for specified account.
	 * 
	 * @param CAccount $oAccount Account object.
	 * @param int $iSortField Sort field for sorting contact list.
	 * @param int $iSortOrder Sort order for sorting contact list.
	 * @param int $iOffset Offset value for obtaining a partial list.
	 * @param int $iRequestLimit Limit value for obtaining a partial list.
	 * @param string $sSearch Search string.
	 * 
	 * @return string
	 */
	public function getContactItemsQuery($oAccount,
		$iSortField, $iSortOrder, $iOffset, $iRequestLimit, $sSearch)
	{
		$sSubAdd = $this->_getCommonSubQuery($oAccount);
		$sSearchAdd = $this->_getSearchSubQuery($sSearch);

		$sSql = 'SELECT id_addr, str_id, view_email, primary_email, h_email, b_email, other_email,
fullname, use_frequency, firstname, surname, use_friendly_nm, type, type_id, b_phone, h_phone, h_mobile, date_modified
FROM %sawm_addr_book
WHERE %s AND deleted = 0 AND hide_in_gab = 0 AND auto_create = 0%s
ORDER BY %s
LIMIT %d OFFSET %d';

		$sField = EContactSortField::GetContactDbField($iSortField);
		$sOrder = (ESortOrder::ASC === $iSortOrder) ? 'ASC' : 'DESC';
		$sOrderBy = $sField.' '.$sOrder;

		if ('use_frequency' === $sField)
		{
			$aAdd = 'shared_to_all ';
			$aAdd .= (ESortOrder::ASC === $iSortOrder) ? 'DESC' : 'ASC';
			$sOrderBy = $aAdd.', '.$sOrderBy;
		}
		else if ('fullname' === $sField)
		{
			$aAdd = 'view_email ';
			$aAdd .= (ESortOrder::ASC !== $iSortOrder) ? 'DESC' : 'ASC';
			$sOrderBy = $sOrderBy.', '.$aAdd;
		}

		return sprintf($sSql, $this->prefix(), $sSubAdd,
			0 < strlen($sSearchAdd) ? ' AND ('.$sSearchAdd.')' : '', $sOrderBy,
			$iRequestLimit,
			$iOffset
		);
	}
}

/**
 * Class CApiGcontactsCommandCreatorPostgreSQL is used for creating query-strings which is used in PostgreSQL database requests.
 * 
 * @package ContactsGlobal
 * @subpackage Storages
 */
class CApiContactsGlobalDbCommandCreatorPostgreSQL extends CApiContactsGlobalDbCommandCreatorMySQL
{
	
}
