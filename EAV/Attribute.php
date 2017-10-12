<?php
/*
 * @copyright Copyright (c) 2017, Afterlogic Corp.
 * @license AGPL-3.0 or Afterlogic Software License
 *
 * This code is licensed under AGPLv3 license or Afterlogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

/**
 * @package Api
 */

namespace Aurora\System\EAV;

/**
 * @package EAV
 * @subpackage Classes
 */
class Attribute
{
	/*
	 * @var int $Id
	 */
	public $Id;

	/*
	 * @var int $EntityId
	 */
	public $EntityId;

	/*
	 * @var string $Name
	 */
	public $Name;

	/*
	 * @var mixed $Value
	 */
	public $Value;

	/*
	 * @var string $Type
	 */
	public $Type;

	/*
	 * @var bool $IsEncrypt
	 */
	public $IsEncrypt;
	
	/*
	 * @var bool $Encrypted
	 */
	public $Encrypted;	
	
	/*
	 * @var bool $ReadOnly
	 */
	public $ReadOnly;	

	/**
	 * @param string $sName
	 * @param mixed $mValue
	 * @param string $sType
	 * @param bool $bIsEncrypt
	 * @param int $iEntityId
	 * @param bool $bReadOnly
	 */
	public function __construct($sName, $mValue = null, $sType = 'string', $bIsEncrypt = false, $iEntityId = 0, $bReadOnly = false)
	{
		$this->Id = 0;
		$this->EntityId = $iEntityId;
		$this->Name	= $sName;
		$this->Value = $mValue;
		$this->IsEncrypt = $bIsEncrypt;
		$this->Encrypted = false;
		$this->ReadOnly = $bReadOnly;

		$this->setType($sType);
	}
	
	/**
	 * @param string $sName
	 * @param mixed $sValue
	 * @param string $sType
	 * @param bool $bEncrypt
	 * @param int $iEntityId
	 * @param bool $bReadOnly
	 * 
	 * @return \Aurora\System\EAV\Attribute
	 */
	public static function createInstance($sName, $sValue = null, $sType = null, $bEncrypt = false, $iEntityId = 0, $bReadOnly = false)
	{
		return new self($sName, $sValue, $sType, $bEncrypt, $iEntityId, $bReadOnly);
	}

	/**
	 * @throws \Aurora\System\Exceptions\ValidationException
	 *
	 * @return bool
	 */
	public function validate()
	{
		return true;
	}
	
	/**
	 * @return bool
	 */
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
	
	
	/**
	 * @param string $sType
	 */
	public function setType($sType)
	{
		if ($sType === null)
		{
			$sType = gettype($this->Value);
		}
		$this->Type = $sType;
		
		$sType = strtolower($sType);
		if (in_array($sType, array('string', 'int', 'array')))
		{
			settype($this->Value, $sType);
		}
		else if (in_array($sType, array('bool')))
		{
			$this->Value = (bool) $this->Value;
		}
		else if (in_array($sType, array('encoded', 'datetime')))
		{
			settype($this->Value, 'string');
		}
		else if (0 === strpos($sType, 'string('))
		{
			settype($this->Value, 'string');
			if (0 < strlen($this->Value))
			{
				$iSize = substr($sType, 7, -1);
				if (is_numeric($iSize) && (int) $iSize < strlen($this->Value))
				{
					$this->Value = \Aurora\System\Utils::Utf8Truncate($this->Value, (int) $iSize);
				}
			}
		}
	}	
	
	/**
	 * @return bool
	 */
	public function getValueFormat()
	{
		$sResult = '%s';
		switch ($this->Type)
		{
			case "int" :
				$sResult = '%d';
				break;
			case "bool" :
				$sResult = '%d';
				break;
		}	
		
		return $sResult;	
	}
	
	public function Encrypt()
	{
		if (!empty($this->Value) && !$this->Encrypted)
		{
			$this->Value = \Aurora\System\Utils::EncryptValue($this->Value);
			$this->Encrypted = true;
		}
	}
	
	public function Decrypt()
	{
		if ($this->Encrypted)
		{
			$this->Value = \Aurora\System\Utils::DecryptValue($this->Value);
			$this->Encrypted = false;
		}
	}	
}

