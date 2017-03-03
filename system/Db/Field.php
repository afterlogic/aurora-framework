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
 * @package Api
 * @subpackage Db
 */

namespace Aurora\System\Db;

/**
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