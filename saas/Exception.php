<?php

namespace saas;

/**
 * Класс управления генерацией исключений.
 */
class Exception
{
	static private $throwExceptions = true;
	static private $lastException = null;

	static function enableExceptions($en = true)
	{
		self::$throwExceptions = $en;
	}

	static function throwException($exceptionClass)
	{
		if ($exceptionClass instanceof \Exception)
		{
			self::$lastException = $exceptionClass;
			if (self::$throwExceptions)
			{
				throw $exceptionClass;
			}
		}
	}
}
