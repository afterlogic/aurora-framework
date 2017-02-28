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
 * @package Db
 * @subpackage Storages
 */
class CApiDbStorage extends \Aurora\System\AbstractManagerStorage
{
	/**
	 * @param \Aurora\System\GlobalManager &$oManager
	 */
	public function __construct($sStorageName, \Aurora\System\GlobalManager &$oManager)
	{
		parent::__construct('db', $sStorageName, $oManager);
	}

	/**
	 * @return bool
	 */
	public function testConnection()
	{
		return false;
	}

	/**
	 * @return bool
	 */
	public function createDatabase()
	{
		return false;
	}

	/**
	 * @param mixed $fVerboseCallback
	 *
	 * @return bool
	 */
	public function syncTables($fVerboseCallback)
	{
		return false;
	}

	/**
	 * @return bool
	 */
	public function isAUsersTableExists()
	{
		return false;
	}

	/**
	 * @return bool
	 */
	public function createTables()
	{
		return false;
	}

	/**
	 * @param bool $bAddDropTable Default value is **false**.
	 *
	 * @return string
	 */
	public function getSqlSchemaAsString($bAddDropTable = false)
	{
		return '';
	}

	/**
	 * @param bool $bAddDropTable Default value is **false**.
	 *
	 * @return array
	 */
	public function getSqlSchemaAsArray($bAddDropTable = false)
	{
		return array();
	}

	/**
	 * @param bool $bAddDropFunction Default value is **false**.
	 *
	 * @return array
	 */
	public function getSqlFunctionsAsArray($bAddDropFunction = false)
	{
		return array();
	}
}