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
	public $Encrypt;
	
	public function __construct($sName, $sValue, $sType = 'string', $bEncrypt = false)
	{
		$this->Id = 0;
		$this->ObjectId = '';
		$this->Name	= $sName;
		$this->Value = $sValue;
		$this->Type = $sType;
		$this->Encrypt = $bEncrypt;
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
