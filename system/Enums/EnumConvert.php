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

namespace Aurora\System\Enums;

/**
 * @package Api
 * @subpackage Enum
 */
class EnumConvert
{
	/**
	 * @staticvar array $aClasses
	 * @param string $sClassName
	 * @return array
	 */
	protected static function GetInst($sClassName)
	{
		static $aClasses = array();

		if (!isset($aClasses[$sClassName]) && class_exists($sClassName))
		{
			$aClasses[$sClassName] = new $sClassName;
		}

		return (isset($aClasses[$sClassName])) ? $aClasses[$sClassName]->getMap() : array();
	}

	/**
	 * @param mixed $mValue
	 * @param string $sClassName
	 * @return int
	 */
	static function validate($mValue, $sClassName)
	{
		$aConsts = self::GetInst($sClassName);

		$sResult = null;
		foreach ($aConsts as $mEnumValue)
		{
			if ($mValue === $mEnumValue)
			{
				$sResult = $mValue;
				break;
			}
		}
		return $sResult;
	}

	/**
	 * @param mixed $sXmlValue
	 * @param string $sClassName
	 * @return int
	 */
	public static function FromXml($sXmlValue, $sClassName)
	{
		$aConsts = self::GetInst($sClassName);

		$niResult = null;
		if (isset($aConsts[$sXmlValue]))
		{
			$niResult = $aConsts[$sXmlValue];
		}

		return self::validate($niResult, $sClassName);
	}

	/**
	 * @param mixed $sXmlValue
	 * @param string $sClassName
	 * @return int
	 */
	public static function FromPost($sXmlValue, $sClassName)
	{
		return self::FromXml($sXmlValue, $sClassName);
	}

	/**
	 * @param mixed $mValue
	 * @param string $sClassName
	 * @return string
	 */
	public static function ToXml($mValue, $sClassName)
	{
		$aConsts = self::GetInst($sClassName);

		$sResult = '';
		foreach ($aConsts as $sKey => $mEnumValue)
		{
			if ($mValue === $mEnumValue)
			{
				$sResult = $sKey;
				break;
			}
		}
		return $sResult;
	}

	/**
	 * @param mixed $mValue
	 * @param string $sClassName
	 * @return string
	 */
	public static function ToPost($mValue, $sClassName)
	{
		return self::ToXml($mValue, $sClassName);
	}
}
