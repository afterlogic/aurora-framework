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
 * @package Api
 * @subpackage Db
 */
class Field
{
	const AUTO_INT = 10;
	const AUTO_INT_BIG = 11;
	const AUTO_INT_UNSIGNED = 12;
	const AUTO_INT_BIG_UNSIGNED = 13;

	const BIT = 20;
	const INT = 21;
	const INT_SHORT = 22;
	const INT_SMALL = 23;
	const INT_BIG = 24;
	const INT_UNSIGNED = 25;
	const INT_SHORT_SMALL = 26;
	const INT_BIG_UNSIGNED = 27;

	const CHAR = 31;
	const VAR_CHAR = 32;
	const TEXT = 33;
	const TEXT_MEDIUM = 37;
	const TEXT_LONG = 34;
	const BLOB = 35;
	const BLOB_LONG = 36;

	const DATETIME = 40;

	/**
	 * @var string
	 */
	protected $sName;

	/**
	 * @var int
	 */
	protected $iType;

	/**
	 * @var mixed
	 */
	protected $mDefault;

	/**
	 * @var int
	 */
	protected $iCustomLen;

	/**
	 * @var bool
	 */
	protected $bNotNullWithOutDefault;

	/**
	 * @param string $sName
	 * @param int $iType
	 * @param mixed $mDefault = null
	 * @param int $iCustomLen = null
	 * @param bool $bNotNullWithOutDefault = false
	 */
	public function __construct($sName, $iType, $mDefault = null, $iCustomLen = null, $bNotNullWithOutDefault = false)
	{
		$this->sName = $sName;
		$this->iType = $iType;
		$this->mDefault = $mDefault;
		$this->iCustomLen = $iCustomLen;
		$this->bNotNullWithOutDefault = $bNotNullWithOutDefault;
	}

	/**
	 * @return string
	 */
	public function Name()
	{
		return $this->sName;
	}

	/**
	 * @param string $sTableName
	 * @param IDbHelper $oHelper
	 * @return string
	 */
	public function ToAlterString($sTableName, $oHelper)
	{
		return sprintf('ALTER TABLE %s ADD %s',
			$oHelper->EscapeColumn($sTableName), $this->ToString($oHelper));
	}

	/**
	 * @return string
	 */
	public function ToString($oHelper)
	{
		return $oHelper->FieldToString($this->sName, $this->iType, $this->mDefault, $this->iCustomLen, $this->bNotNullWithOutDefault);
	}
}
