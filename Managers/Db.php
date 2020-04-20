<?php
/**
 * This code is licensed under AGPLv3 license or Afterlogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

namespace Aurora\System\Managers;

/**
 * @license https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @license https://afterlogic.com/products/common-licensing Afterlogic Software License
 * @copyright Copyright (c) 2019, Afterlogic Corp.
 *
 * @package Db
 */
class Db extends AbstractManagerWithStorage
{
	private static $_instance = null;

	public static function createInstance()
	{
		return new self();
	}

	public static function getInstance()
	{
		if(is_null(self::$_instance))
		{
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	public function __construct()
	{
		parent::__construct(\Aurora\System\Api::GetModule('Core'), new Db\Storage($this));
	}

	public function executeSql($sSql)
	{
		return $this->oStorage->executeSql($sSql);
	}

	public function executeSqlFile($sFilePath)
	{
		return $this->oStorage->executeSqlFile($sFilePath);
	}

	public function columnExists($sTable, $sColumn)
	{
		return $this->oStorage->columnExists($sTable, $sColumn);
	}

}
