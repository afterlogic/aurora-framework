<?php
/*
 * @copyright Copyright (c) 2017, Afterlogic Corp.
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
 * @static
 * @package Api
 * @subpackage Db
 */

namespace Aurora\System\Db;

class Creator
{
	/**
	 * @var DbMySql;
	 */
	static $oDbConnector;

	/**
	 * @var DbMySql;
	 */
	static $oSlaveDbConnector;

	/**
	 * @var object;
	 */
	static $oCommandCreatorHelper;

	private function __construct() {}

	/**
	 * @return void
	 */
	public static function ClearStatic()
	{
		self::$oDbConnector = null;
		self::$oSlaveDbConnector = null;
	}

	/**
	 * @param array $aData
	 * @return Sql
	 */
	public static function ConnectorFabric($aData)
	{
		$oConnector = null;
		if (isset($aData['Type']))
		{
			$iDbType = $aData['Type'];

			if (isset($aData['DBHost'], $aData['DBLogin'], $aData['DBPassword'], $aData['DBName'], $aData['DBTablePrefix']))
			{
				if (\EDbType::PostgreSQL === $iDbType)
				{
					$oConnector = new Pdo\Postgres($aData['DBHost'], $aData['DBLogin'], $aData['DBPassword'], $aData['DBName'], $aData['DBTablePrefix']);
				}
				else
				{
					$oConnector = new Pdo\MySql($aData['DBHost'], $aData['DBLogin'], $aData['DBPassword'], $aData['DBName'], $aData['DBTablePrefix']);
				}
			}
		}

		return $oConnector;
	}

	/**
	 * @param int $iDbType = EDbType::MySQL
	 * @return IDbHelper
	 */
	public static function CommandCreatorHelperFabric($iDbType = EDbType::MySQL)
	{
		$oHelper = null;
		if (\EDbType::PostgreSQL === $iDbType)
		{
			$oHelper = new Pdo\Postgres\Helper();
		}
		else
		{
			$oHelper = new Pdo\MySql\Helper();
		}

		return $oHelper;
	}

	/**
	 * @param \Aurora\System\Settingss $oSettings
	 * @return &MySql
	 */
	public static function &CreateConnector(\Aurora\System\Settings $oSettings)
	{
		$aResult = array();
		if (!is_object(self::$oDbConnector))
		{
			Creator::$oDbConnector = Creator::ConnectorFabric(array(
				'Type' => $oSettings->GetConf('DBType'),
				'DBHost' => $oSettings->GetConf('DBHost'),
				'DBLogin' => $oSettings->GetConf('DBLogin'),
				'DBPassword' => $oSettings->GetConf('DBPassword'),
				'DBName' => $oSettings->GetConf('DBName'),
				'DBTablePrefix' => $oSettings->GetConf('DBPrefix')
			));

			if ($oSettings->GetConf('UseSlaveConnection'))
			{
				Creator::$oSlaveDbConnector = Creator::ConnectorFabric(array(
					'Type' => $oSettings->GetConf('DBType'),
					'DBHost' => $oSettings->GetConf('DBSlaveHost'),
					'DBLogin' => $oSettings->GetConf('DBSlaveLogin'),
					'DBPassword' => $oSettings->GetConf('DBSlavePassword'),
					'DBName' => $oSettings->GetConf('DBSlaveName'),
					'DBTablePrefix' => $oSettings->GetConf('DBPrefix')
				));
			}
		}

		$aResult = array(&Creator::$oDbConnector, &Creator::$oSlaveDbConnector);
		return $aResult;
	}

	/**
	 * @param \Aurora\System\Settings $oSettings
	 * @return &IDbHelper
	 */
	public static function &CreateCommandCreatorHelper(\Aurora\System\Settings $oSettings)
	{
		if (is_object(Creator::$oCommandCreatorHelper))
		{
			return Creator::$oCommandCreatorHelper;
		}

		Creator::$oCommandCreatorHelper = Creator::CommandCreatorHelperFabric(
			$oSettings->GetConf('DBType'));

		return Creator::$oCommandCreatorHelper;
	}
}