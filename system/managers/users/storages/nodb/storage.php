<?php
/*
 * @copyright Copyright (c) 2016, Afterlogic Corp.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 * 
 */

/**
 * @internal
 * 
 * @package Users
 * @subpackage Storages
 */
class CApiUsersNodbStorage extends CApiUsersStorage
{
	const SESS_ACCOUNT_STORAGE = 'sess-acct-storage';
	const SESS_CAL_USERS_STORAGE = 'sess-cal-user-storage';

	/**
	 * @param CApiGlobalManager &$oManager
	 */
	public function __construct(CApiGlobalManager &$oManager)
	{
		parent::__construct('nodb', $oManager);

		CSession::$sSessionName = API_SESSION_WEBMAIL_NAME;
	}

	/**
	 * Retrieves information on WebMail Pro account. Account ID is used for look up.
	 * 
	 * @param int $iAccountId Account identifier.
	 * 
	 * @return CAccount
	 */
	public function getAccountById($iAccountId)
	{
		$oAccount = CSession::get(CApiUsersNodbStorage::SESS_ACCOUNT_STORAGE, null);
		return ($oAccount && $iAccountId === $oAccount->IdAccount) ? clone $oAccount : null;
	}

	/**
	 * Creates WebMail account.
	 * 
	 * @param CAccount &$oAccount Object instance with prepopulated account properties.
	 * 
	 * @return bool
	 */
	public function createAccount(CAccount &$oAccount)
	{
		$oAccount->IdAccount = 1;
		$oAccount->IdUser = 1;
		$oAccount->User->IdUser = 1;

		$oAccount->PreviousMailPassword = '';
		$oAccount->FlushObsolete('PreviousMailPassword');

		CSession::Set(CApiUsersNodbStorage::SESS_ACCOUNT_STORAGE, $oAccount);

		return true;
	}

	/**
	 * Retrieves list of accounts for given WebMail Pro user. 
	 * 
	 * @param int $iUserId User identifier. 
	 * 
	 * @return array | false
	 */
	public function getAccountIdList($iUserId)
	{
		$oAccount = CSession::get(CApiUsersNodbStorage::SESS_ACCOUNT_STORAGE, null);
		return ($oAccount && $iUserId === $oAccount->IdUser) ? array($iUserId) : false;
	}

	/**
	 * Checks if particular account exists. 
	 * 
	 * @param CAccount $oAccount Object instance with prepopulated account properties. 
	 * 
	 * @return bool
	 */
	public function accountExists(CAccount $oAccount)
	{
		return false;
	}

	/**
	 * Saves changes made to the account.
	 * 
	 * @param CAccount &$oAccount Account object containing data to be saved.
	 * 
	 * @return bool
	 */
	public function updateAccount(CAccount $oAccount)
	{
		$oAccount->PreviousMailPassword = '';
		$oAccount->FlushObsolete('PreviousMailPassword');

		CSession::Set(CApiUsersNodbStorage::SESS_ACCOUNT_STORAGE, $oAccount);

		return true;
	}

	/**
	 * Creates calendar user in storage.
	 * 
	 * @param CCalUser &$oCalUser CCalUser object.
	 * 
	 * @return bool
	 */
	public function createCalUser(CCalUser &$oCalUser)
	{
		$oCalUser->IdCalUser = 1;
		$oCalUser->IdUser = 1;

		CSession::Set(CApiUsersNodbStorage::SESS_CAL_USERS_STORAGE, $oCalUser);

		return true;
	}

	/**
	 * Obtains CCalUser object that contains calendar settings for specified user. User identifier is used for look up.
	 * 
	 * @param int $iUserId User identifier.
	 * 
	 * @return CCalUser
	 */
	public function getCalUser($iUserId)
	{
		$oCalUser = CSession::get(CApiUsersNodbStorage::SESS_ACCOUNT_STORAGE, null);
		return ($oCalUser && $iUserId === $oCalUser->IdUser) ? clone $oCalUser : null;
	}

	/**
	 * Updates calendar user settings.
	 * 
	 * @param CCalUser $oCalUser CCalUser object.
	 * 
	 * @return bool
	 */
	public function updateCalUser(CCalUser $oCalUser)
	{
		CSession::Set(CApiUsersNodbStorage::SESS_CAL_USERS_STORAGE, $oCalUser);

		return true;
	}
}
