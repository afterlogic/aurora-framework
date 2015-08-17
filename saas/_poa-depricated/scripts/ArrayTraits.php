<?php

require_once dirname(__FILE__).'/Field.php';

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
			$field = new Field();
			$field->name = $key;
			$field->value = $value;
			$res[] = $field;
		}

		return $res;
	}
}