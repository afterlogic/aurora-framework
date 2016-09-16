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
 */
class CGet
{
	/**
	 * @var bool
	 */
	public static $bIsMagicQuotesOn = false;

	private function __construct() {}

	/**
	 * @param string $sKey
	 * @return bool
	 */
	public static function Has($sKey)
	{
		return (isset($_GET[$sKey]));
	}

	/**
	 * @param string $sKey
	 * @param mixed $nmDefault = null
	 * @return mixed
	 */
	public static function get($sKey, $nmDefault = null)
	{
		return (isset($_GET[$sKey])) ? self::_stripSlashesValue($_GET[$sKey]) : $nmDefault;
	}

	/**
	 * @param string $sKey
	 * @param mixed $mValue
	 */
	public static function Set($sKey, $mValue)
	{
		$_GET[$sKey] = $mValue;
	}

	/**
	 * @param mixed $mValue
	 * @return mixed
	 */
	private static function _stripSlashesValue($mValue)
	{
		if (!self::$bIsMagicQuotesOn)
		{
			return $mValue;
		}

		$sType = gettype($mValue);
		if ($sType === 'string')
		{
			return stripslashes($mValue);
		}
		else if ($sType === 'array')
		{
			$aReturnValue = array();
			$mValueKeys = array_keys($mValue);
			foreach($mValueKeys as $sKey)
			{
				$aReturnValue[$sKey] = self::_stripSlashesValue($mValue[$sKey]);
			}
			return $aReturnValue;
		}
		else
		{
			return $mValue;
		}
	}
}

CGet::$bIsMagicQuotesOn = (bool) ini_get('magic_quotes_gpc');
