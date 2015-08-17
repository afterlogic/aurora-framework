<?php

namespace saas\tool;

/**
 * @brief Simply password generator.
 */
class PasswordGenerator
{
	/**
	 * @brief Generates password string.
	 * 
	 * @param int $iLen Length of a generated password string
	 */
	static function generate($iLen)
	{
		$arr = array('a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l',
			'm', 'n', 'o', 'p', 'r', 's', 't', 'u', 'v', 'x', 'y', 'z',
			'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L',	'M', 'N', 'O', 'P', 'R', 'S',
			'T', 'U', 'V', 'X', 'Y', 'Z','1', '2', '3', '4', '5', '6', '7', '8', '9', '0'
		);

		$pass = '';
		for ($i = 0; $i < $iLen; ++$i)
		{
			$index = rand(0, count($arr) - 1);
			$pass .= $arr[$index];
		}

		return $pass;
	}
}
