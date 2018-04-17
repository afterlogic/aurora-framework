<?php
/*
 * This code is licensed under AGPLv3 license or Afterlogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

namespace Aurora\System\Enums;

/**
 * @license https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @license https://afterlogic.com/products/common-licensing AfterLogic Software License
 * @copyright Copyright (c) 2018, Afterlogic Corp.
 *
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
