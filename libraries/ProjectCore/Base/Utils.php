<?php

/* -AFTERLOGIC LICENSE HEADER- */

namespace ProjectCore\Base;

/**
 * @category ProjectCore
 * @package Base
 */
class Utils
{
	/**
	 * @param string $sValue
	 * @return array
	 */
	public static function ExplodeIntUids($sValue)
	{
		$aValue = explode(',', (string) $sValue);
		$aValue = array_map('trim', $aValue);
		$aValue = array_map('intval', $aValue);

		$aValue = array_filter($aValue, function ($iValue) {
			return 0 < $iValue;
		});

		return $aValue;
	}
	
	/**
	 * @return int
	 */
	public static function iframedTimestamp()
	{
		return time() - 60 * 2;
	}
}
