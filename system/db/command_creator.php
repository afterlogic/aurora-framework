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

namespace Aurora\System\Db;

/**
 * @package Api
 * @subpackage Db
 */
abstract class AbstractCommandCreator
{
	/**
	 * @var IDbHelper
	 */
	protected $oHelper;

	/**
	 * @var string
	 */
	protected $sPrefix;

	/**
	 * @param IHelper $oHelper
	 * @param string $sPrefix
	 */
	public function __construct($oHelper, $sPrefix)
	{
		$this->oHelper = $oHelper;
		$this->sPrefix = (string) $sPrefix;
	}

	public function prefix()
	{
		return $this->sPrefix;
	}

	/**
	 * @param string $sValue
	 * @param bool $bWithOutQuote = false
	 * @param bool $bSearch = false
	 * @return string
	 */
	protected function escapeString($sValue, $bWithOutQuote = false, $bSearch = false)
	{
		return $this->oHelper->EscapeString($sValue, $bWithOutQuote, $bSearch);
	}

	/**
	 * @param array $aValue
	 * @return array
	 */
	protected function escapeArray($aValue)
	{
		return array_map(array(&$this->oHelper, 'EscapeString'), $aValue);
	}

	/**
	 * @param string $str
	 * @return string
	 */
	protected function escapeColumn($str)
	{
		return $this->oHelper->EscapeColumn($str);
	}

	/**
	 * @param string $sFieldName
	 * @return string
	 */
	protected function GetDateFormat($sFieldName)
	{
		return $this->oHelper->GetDateFormat($sFieldName);
	}

	/**
	 * @param string $sFieldName
	 * @return string
	 */
	protected function UpdateDateFormat($sFieldName)
	{
		return $this->oHelper->UpdateDateFormat($sFieldName);
	}
}
