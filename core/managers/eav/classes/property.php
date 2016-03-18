<?php

/* -AFTERLOGIC LICENSE HEADER- */

/**
 * @property int $Id
 * @property string $ObjectId
 * @property string $Name
 * @property mixed $Value
 * @property string $Type
 *
 * @package EAV
 * @subpackage Classes
 */
class CProperty
{
	public $Id;
	public $ObjectId;
	public $Name;
	public $Value;
	public $Type;
	
	public function __construct($sName, $sValue, $sType = 'string')
	{
		$this->Id = 0;
		$this->ObjectId = '';
		$this->Name	= $sName;
		$this->Value = $sValue;
		$this->Type = $sType;
	}

	/**
	 * @throws CApiValidationException
	 *
	 * @return bool
	 */
	public function validate()
	{
		return true;
	}
}
