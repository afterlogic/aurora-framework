<?php

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
