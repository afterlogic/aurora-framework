<?php

namespace saas\api;

/**
 * Представление SOAP поле при передаче или приёме через XML.
 * 
 * @author saydex
 *
 */
class Field
{
	public $name;
	public $value;

	function __construct($name = null, $value = null)
	{
		$this->name = $name;
		$this->value = $value;
	}
}
