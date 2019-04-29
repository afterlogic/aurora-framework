<?php
/**
 * This code is licensed under AGPLv3 license or Afterlogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

namespace Aurora\System\Db;

/**
 * @license https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @license https://afterlogic.com/products/common-licensing Afterlogic Software License
 * @copyright Copyright (c) 2019, Afterlogic Corp.
 *
 * @static
 * @package Api
 * @subpackage Db
 */
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
				if (\Aurora\System\Enums\DbType::PostgreSQL === $iDbType)
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
	 * @param int $iDbType = \Aurora\System\Enums\DbType::MySQL
	 * @return IDbHelper
	 */
	public static function CommandCreatorHelperFabric($iDbType = \Aurora\System\Enums\DbType::MySQL)
	{
		$oHelper = null;
		if (\Aurora\System\Enums\DbType::PostgreSQL === $iDbType)
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
	public static function &CreateConnector(\Aurora\System\AbstractSettings $oSettings)
	{
		$aResult = array();
		if (!is_object(self::$oDbConnector))
		{
			Creator::$oDbConnector = Creator::ConnectorFabric(array(
				'Type' => $oSettings->DBType,
				'DBHost' => $oSettings->DBHost,
				'DBLogin' => $oSettings->DBLogin,
				'DBPassword' => $oSettings->DBPassword,
				'DBName' => $oSettings->DBName,
				'DBTablePrefix' => $oSettings->DBPrefix
			));

			if ($oSettings->UseSlaveConnection)
			{
				Creator::$oSlaveDbConnector = Creator::ConnectorFabric(array(
					'Type' => $oSettings->DBType,
					'DBHost' => $oSettings->DBSlaveHost,
					'DBLogin' => $oSettings->DBSlaveLogin,
					'DBPassword' => $oSettings->DBSlavePassword,
					'DBName' => $oSettings->DBSlaveName,
					'DBTablePrefix' => $oSettings->DBPrefix
				));
			}
		}

		$aResult = array(&Creator::$oDbConnector, &Creator::$oSlaveDbConnector);
		return $aResult;
	}

	/**
	 * @param \Aurora\System\AbstractSettings $oSettings
	 * @return &IDbHelper
	 */
	public static function &CreateCommandCreatorHelper(\Aurora\System\AbstractSettings $oSettings)
	{
		if (is_object(Creator::$oCommandCreatorHelper))
		{
			return Creator::$oCommandCreatorHelper;
		}

		Creator::$oCommandCreatorHelper = Creator::CommandCreatorHelperFabric(
			$oSettings->DBType);

		return Creator::$oCommandCreatorHelper;
	}
}
