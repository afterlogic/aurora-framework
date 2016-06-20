<?php

/* -AFTERLOGIC LICENSE HEADER- */

/**
 * @property int $Id
 * @property string $EntityId
 * @property string $Name
 * @property mixed $Value
 * @property string $Type
 *
 * @package EAV
 * @subpackage Classes
 */
class CAttribute
{
	public $Id;
	public $EntityId;
	public $Name;
	public $Value;
	public $Type;
	public $Encrypt;
	
	public function __construct($sName, $sValue, $sType = 'string', $bEncrypt = false)
	{
		$this->Id = 0;
		$this->EntityId = '';
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
	
	public function needToEscape()
	{
		$bResult = false;
		switch ($this->Type)
		{
			case "string" :
				$bResult = true;
				break;
			case "text" :
				$bResult = true;
				break;
			case "datetime" :
				$bResult = true;
				break;
		}	
		
		return $bResult;
	}
	
	public function getValueFormat()
	{
		$bResult = '%s';
		switch ($this->Type)
		{
			case "int" :
				$bResult = '%d';
				break;
			case "bool" :
				$bResult = '%d';
				break;
		}	
		
		return $bResult;	
	}
	
}
