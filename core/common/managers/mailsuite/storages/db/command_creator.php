<?php

/* -AFTERLOGIC LICENSE HEADER- */

/**
 * @package Mailsuite
 * @subpackage Storages
 */
class CApiMailsuiteCommandCreator extends api_CommandCreator
{
	/**
	 * @param CMailingList $oMailingList
	 *
	 * @return string
	 */
	public function createMailingList(CMailingList $oMailingList)
	{
		$aResults = api_AContainer::DbInsertArrays($oMailingList, $this->oHelper);

		if ($aResults[0] && $aResults[1])
		{
			$sSql = 'INSERT INTO %sawm_accounts ( %s ) VALUES ( %s )';
			return sprintf($sSql, $this->prefix(),
				implode(', ', $aResults[0]),
				implode(', ', $aResults[1])
			);
		}

		return '';
	}
	
	/**
	 * @param CMailingList $oMailingList
	 *
	 * @return string
	 */
	public function updateMailingList(CMailingList $oMailingList)
	{
		$aResult = api_AContainer::DbUpdateArray($oMailingList, $this->oHelper);

		$sSql = 'UPDATE %sawm_accounts SET %s WHERE id_acct = %d';
		return sprintf($sSql, $this->prefix(), implode(', ', $aResult), $oMailingList->IdMailingList);
	}

	/**
	 * @param int $iMailingListId
	 *
	 * @return string
	 */
	public function getMailingListById($iMailingListId)
	{
		$aMap = api_AContainer::DbReadKeys(CAccount::getStaticMap());
		$aMap = array_map(array($this, 'escapeColumn'), $aMap);

		$sSql = 'SELECT %s FROM %sawm_accounts WHERE %s = %d';

		return sprintf($sSql, implode(', ', $aMap), $this->prefix(),
			$this->escapeColumn('id_acct'), $iMailingListId);
	}

	/**
	 * @param int $iMailingListId
	 *
	 * @return string
	 */
	public function deleteMailingListById($iMailingListId)
	{
		$sSql = 'DELETE FROM %sawm_accounts WHERE %s = %d';

		return sprintf($sSql, $this->prefix(), $this->escapeColumn('id_acct'), $iMailingListId);
	}

	/**
	 * @param int $iMailingListId
	 *
	 * @return string
	 */
	public function deleteMailingListGlobalSubContacts($iMailingListId)
	{
		// TODO Magic
		$sSql = 'DELETE aa1 '.
'FROM %sawm_addr_book AS aa1 '.
'INNER JOIN %sawm_addr_book AS aa2 ON aa1.type_id = aa2.id_addr '.
'WHERE aa1.type = 1 AND aa2.type = 3 AND aa2.type_id = %s';

		return sprintf($sSql, $this->prefix(), $this->prefix(), $this->escapeString($iMailingListId));
	}

	/**
	 * @param int $iMailingListId
	 *
	 * @return string
	 */
	public function deleteMailingListGlobalContacts($iMailingListId)
	{
		$sSql = 'DELETE FROM %sawm_addr_book WHERE %s = %d AND %s = %s';

		return sprintf($sSql, $this->prefix(),
			$this->escapeColumn('type'), 3, // TODO Magic
			$this->escapeColumn('type_id'),
			$this->escapeString($iMailingListId)
		);
	}

	/**
	 * @param int $iMailingListId
	 *
	 * @return string
	 */
	public function getMailingListMembersById($iMailingListId)
	{
		$sSql = 'SELECT %s FROM %sawm_mailinglists WHERE %s = %d';

		return sprintf($sSql, $this->escapeColumn('list_to'), $this->prefix(),
			$this->escapeColumn('id_acct'), $iMailingListId);
	}

	/**
	 * @param int $iAccountId
	 *
	 * @return string
	 */
	public function getMailAliasesById($iAccountId)
	{
		$sSql = 'SELECT %s, %s  FROM %sawm_mailaliases WHERE %s = %d';

		return sprintf($sSql, $this->escapeColumn('alias_name'),
				$this->escapeColumn('alias_domain'), $this->prefix(),
			$this->escapeColumn('id_acct'), $iAccountId);
	}

	/**
	 * @param int $iAccountId
	 *
	 * @return string
	 */
	public function getMailForwardsById($iAccountId)
	{
		$sSql = 'SELECT %s FROM %sawm_mailforwards WHERE %s = %d';

		return sprintf($sSql, $this->escapeColumn('forward_to'), $this->prefix(),
			$this->escapeColumn('id_acct'), $iAccountId);
	}

	/**
	 * @param CMailingList $oMailingList
	 *
	 * @return string
	 */
	public function addMailingListMembers(CMailingList $oMailingList)
	{
		$aListSql = array();
		foreach ($oMailingList->Members as $sEmail)
		{
			$aListSql[] = '('.$oMailingList->IdMailingList.', '.$this->escapeString($oMailingList->Email).', '.$this->escapeString($sEmail).')';
		}

		if (0 < count($aListSql))
		{
			$sSql = 'INSERT INTO %sawm_mailinglists (id_acct, list_name, list_to) VALUES ';
			return sprintf($sSql, $this->prefix()).implode(', ', $aListSql);
		}

		return '';
	}

	/**
	 * @param string $sEmail
	 *
	 * @return string
	 */
	public function isAliasValidToCreateInAccounts($sEmail)
	{
		$sSql = 'SELECT COUNT(id_acct) AS cnt FROM %sawm_accounts WHERE email = %s AND def_acct = 1 AND deleted = 0';
		return sprintf($sSql, $this->prefix(), $this->escapeString($sEmail));
	}

	/**
	 * @param string $sAliasName
	 * @param string $sAliasDomain
	 *
	 * @return string
	 */
	public function isAliasValidToCreateInAliases($sAliasName, $sAliasDomain)
	{
		$sSql = 'SELECT COUNT(id) AS cnt FROM %sawm_mailaliases WHERE alias_name = %s AND alias_domain = %s';
		return sprintf($sSql, $this->prefix(), $this->escapeString($sAliasName), $this->escapeString($sAliasDomain));
	}

	/**
	 * @param CMailAliases $oMailAliases
	 *
	 * @return string
	 */
	public function addMailAliases(CMailAliases $oMailAliases)
	{
		$aListSql = array();
		foreach ($oMailAliases->Aliases as $sEmail)
		{
			list($alias_name, $alias_domain) = explode('@', $sEmail, 2);
			$aListSql[] = '('.$oMailAliases->IdAccount.', '.$this->escapeString($alias_name).', '.$this->escapeString($alias_domain).', '.$this->escapeString($oMailAliases->Email).')';
		}

		if (0 < count($aListSql))
		{
			$sSql = 'INSERT INTO %sawm_mailaliases (id_acct, alias_name, alias_domain, alias_to) VALUES ';
			return sprintf($sSql, $this->prefix()).implode(', ', $aListSql);
		}

		return '';
	}

	/**
	 * @param CMailForwards $oMailForwards
	 *
	 * @return string
	 */
	public function addMailForwards(CMailForwards $oMailForwards)
	{
		$aListSql = array();
		foreach ($oMailForwards->Forwards as $sEmail)
		{
			list($forward_name, $forward_domain) = explode('@', $oMailForwards->Email, 2);
			$aListSql[] = '('.$oMailForwards->IdAccount.', '.$this->escapeString($forward_name).', '.$this->escapeString($forward_domain).', '.$this->escapeString($sEmail).')';
		}

		if (0 < count($aListSql))
		{
			$sSql = 'INSERT INTO %sawm_mailforwards (id_acct, forward_name, forward_domain, forward_to) VALUES ';
			return sprintf($sSql, $this->prefix()).implode(', ', $aListSql);
		}

		return '';
	}

	/**
	 * @param int $iMailingListId
	 *
	 * @return string
	 */
	public function clearMailingListMembers($iMailingListId)
	{
		$sSql = 'DELETE FROM %sawm_mailinglists WHERE %s = %d';

		return sprintf($sSql, $this->prefix(), $this->escapeColumn('id_acct'), $iMailingListId);
	}

	/**
	 * @param int $iAccountId
	 *
	 * @return string
	 */
	public function clearMailAliases($iAccountId)
	{
		$sSql = 'DELETE FROM %sawm_mailaliases WHERE %s = %d';

		return sprintf($sSql, $this->prefix(), $this->escapeColumn('id_acct'), $iAccountId);
	}

	/**
	 * @param int $iAccountId
	 *
	 * @return string
	 */
	public function clearMailForwards($iAccountId)
	{
		$sSql = 'DELETE FROM %sawm_mailforwards WHERE %s = %d';

		return sprintf($sSql, $this->prefix(), $this->escapeColumn('id_acct'), $iAccountId);
	}
}

/**
 * @package Mailsuite
 * @subpackage Storages
 */
class CApiMailsuiteCommandCreatorMySQL extends CApiMailsuiteCommandCreator
{
}

/**
 * @package Mailsuite
 * @subpackage Storages
 */
class CApiMailsuiteCommandCreatorPostgreSQL extends CApiMailsuiteCommandCreator
{
}
