<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Aurora\System\Utils;

class Validate
{
	/**
	 * @param string $sFuncName
	 * @return string
	 */
	public static function GetError($sFuncName)
	{
		switch ($sFuncName)
		{
			case 'Port':
				return 'Required valid port.';
			case 'IsEmpty':
				return 'Required fields cannot be empty.';
		}

		return 'Error';
	}

	/**
	 * @param mixed $mValue
	 * @return bool
	 */
	public static function IsValidLogin($mValue)
	{
		return preg_match('/^[a-zA-Z0-9@\-_\.]+$/', $mValue);
	}

	/**
	 * @param mixed $mValue
	 * @return bool
	 */
	public static function IsEmpty($mValue)
	{
		return !is_string($mValue) || 0 === strlen($mValue);
	}

	/**
	 * @param mixed $mValue
	 * @return bool
	 */
	public static function Port($mValue)
	{
		$bResult = false;
		if (0 < strlen((string) $mValue) && is_numeric($mValue))
		{
			$iPort = (int) $mValue;
			if (0 < $iPort && $iPort < 65355)
			{
				$bResult = true;
			}
		}
		return $bResult;
	}
}
