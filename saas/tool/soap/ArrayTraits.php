<?php

namespace saas\tool\soap;

defined('APP_ROOTPATH') || define('APP_ROOTPATH', realpath(__DIR__.'/../../../'));

require_once APP_ROOTPATH.'/saas/api/Field.php';

/**
 * Утилиты для конверсии массиво PHP <-> SOAP.
 * 
 * @author saydex
 */
class ArrayTraits
{
	static function toPHPArray($soapArray)
	{
		$res = array();
		foreach ($soapArray as $field)
		{
			$res[$field->name] = $field->value;
		}

		return $res;
	}

	static function toSOAPArray($phpArray)
	{
		$res = array();
		foreach ($phpArray as $key => $value)
		{
			$field = new \saas\api\Field();
			$field->name = $key;
			$field->value = $value;
			$res[] = $field;
		}

		return $res;
	}
}
