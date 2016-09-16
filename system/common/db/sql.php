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
 * @package Api
 * @subpackage Db
 */
class CDbGeneralSql
{
	/**
	 * @var	int
	 */
	protected $iExecuteCount;

	/**
	 * @var	int
	 */
	public $ErrorCode;

	/**
	 * @var	string
	 */
	public $ErrorDesc;

	/**
	 * @return bool
	 */
	function IsConnected()
	{
		return false;
	}

	/**
	 * @param string $sLogDesc
	 * @param string $bIsSlaveExecute = false
	 * @return void
	 */
	protected function log($sLogDesc, $bIsSlaveExecute = false)
	{
		if (CApi::$bUseDbLog)
		{
			if ($bIsSlaveExecute)
			{
				CApi::Log('DB-Slave['.$this->iExecuteCount.'] > '.trim($sLogDesc));
			}
			else
			{
				CApi::Log('DB['.$this->iExecuteCount.'] > '.trim($sLogDesc));
			}
		}
	}

	/**
	 * @param string $sErrorDesc
	 * @return void
	 */
	protected function errorLog($sErrorDesc)
	{
		CApi::Log('DB ERROR < '.trim($sErrorDesc), ELogLevel::Error);
	}
}

/**
 * @package Api
 * @subpackage Db
 */
class CDbSql extends CDbGeneralSql
{
	/**
	 * @var	string
	 */
	protected $sHost;

	/**
	 * @var	string
	 */
	protected $sUser;

	/**
	 * @var	string
	 */
	protected $sPassword;

	/**
	 * @var	string
	 */
	protected $sDbName;

	/**
	 * @var	string
	 */
	protected $sDbTablePrefix;
}