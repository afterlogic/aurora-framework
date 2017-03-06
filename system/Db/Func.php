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
class Func
{
	/**
	 * @var string
	 */
	protected $sName;

	/**
	 * @var string
	 */
	protected $sIncParams;

	/**
	 * @var string
	 */
	protected $sResult;

	/**
	 * @var string
	 */
	protected $sText;

	/**
	 * @param string $sName
	 * @param string $sText
	 */
	public function __construct($sName, $sIncParams, $sResult, $sText)
	{
		$this->sName = $sName;
		$this->sIncParams = $sIncParams;
		$this->sResult = $sResult;
		$this->sText = $sText;
	}

	/**
	 * @param IDbHelper $oHelper
	 * @param bool $bAddDropFunction = false
	 * @return string
	 */
	public function ToString($oHelper, $bAddDropFunction = false)
	{
		$sResult = '';
		if ($bAddDropFunction)
		{
			$sResult .= 'DROP FUNCTION IF EXISTS '.$this->sName.';;'.Table::CRLF;
		}

		$sResult .= 'CREATE FUNCTION '.$this->sName.'('.$this->sIncParams.') RETURNS '.$this->sResult;
		$sResult .= Table::CRLF.$this->sText;

		return trim($sResult);
	}
}