<?php
/**
 * This code is licensed under AGPLv3 license or Afterlogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

namespace Aurora\System\EAV;

/**
 * @license https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @license https://afterlogic.com/products/common-licensing Afterlogic Software License
 * @copyright Copyright (c) 2019, Afterlogic Corp.
 *
 * @package EAV
 * @subpackage Classes
 */
class Attribute
{
	/**
	 * @var int $Id
	 */
	public $Id;

	/**
	 * @var int $EntityId
	 */
	public $EntityId;

	/**
	 * @var string $Name
	 */
	public $Name;

	/**
	 * @var mixed $Value
	 */
	public $Value;

	/**
	 * @var string $Type
	 */
	public $Type;

	/**
	 * @var bool $IsEncrypt
	 */
	public $IsEncrypt;

	/**
	 * @var bool $Encrypted
	 */
	public $Encrypted;

	/**
	 * @var bool $ReadOnly
	 */
	public $ReadOnly;

	/**
	 *
	 * @var bool $Override
	 */
	public $Override;

	/**
	 *
	 * @var bool $CanInherit
	 */
	public $CanInherit;

	/**
	 *
	 * @var bool $Inherited
	 */
	public $Inherited;

	/**
	 * @param string $sName
	 * @param mixed $mValue
	 * @param string $sType
	 * @param bool $bIsEncrypt
	 * @param int $iEntityId
	 * @param bool $bReadOnly
	 */
	public function __construct($sName, $mValue = null, $sType = 'string', $bIsEncrypt = false, $iEntityId = 0, $bReadOnly = false, $bExtended = false)
	{
		$this->Id = 0;
		$this->EntityId = $iEntityId;
		$this->Name	= $sName;
		$this->Value = $mValue;
		$this->IsEncrypt = $bIsEncrypt;
		$this->Encrypted = false;
		$this->ReadOnly = $bReadOnly;
		$this->Override = false;
		$this->Inherited = false;
		$this->CanInherit = false;
		$this->IsDefault = false;
		$this->bExtended = $bExtended;

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
	public static function createInstance($sName, $sValue = null, $sType = null, $bEncrypt = false, $iEntityId = 0, $bReadOnly = false, $bExtended = false)
	{
		return new self($sName, $sValue, $sType, $bEncrypt, $iEntityId, $bReadOnly, $bExtended);
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
		if (!is_null($this->Value))
		{
			switch ($this->Type)
			{
				case "mediumblob":
				case "string" :
					$bResult = true;
					break;
				case "text" :
					$bResult = true;
					break;
				case "datetime" :
					$bResult = !empty($this->Value);
					break;
			}
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
		if (in_array($sType, ['string', 'int', 'array', 'double', 'bool']))
		{
			settype($this->Value, $sType);
		}
		else if ($sType === 'bigint')
		{
			settype($this->Value, 'int');
		}
		else if (in_array($sType, ['encoded', 'datetime', 'mediumblob']) && !is_null($this->Value))
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

	public function save($oEntity)
	{
		return \Aurora\System\Managers\Eav::getInstance()->setAttribute($oEntity, $this);
	}
}
