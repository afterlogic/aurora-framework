<?php
/*
 * @copyright Copyright (c) 2016, Afterlogic Corp.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 * 
 */

/**
 * @package Api
 */
class AEntity
{
	/**
	 * @var bool
	 */
	public $__USE_TRIM_IN_STRINGS__;

	/**
	 * @var int
	 */
	public $iId;

	/**
	 * @var int
	 */
	public $sUUID;

	/**
	 * @var string
	 */
	public $sModuleName;

	/**
	 * @var string
	 */
	public $sClassName;

	/**
	 * @var array
	 */
	protected $aAttributes;
	
	/**
	 * @var array
	 */
	protected $aStaticMap;
	
	/**
	 * @var array
	 */
	protected $aMap;
	
	/**
	 * @var array
	 */
	protected static $aTypes = array(
		'int', 
		'string', 
		'text', 
		'bool', 
		'datetime'		
	);
	
	/**
	 * @param string $sClassName
	 * @param string $sModuleName = ''
	 */
	public function __construct($sClassName, $sModuleName = '')
	{
		$this->__USE_TRIM_IN_STRINGS__ = false;
		
		$this->iId = 0;
		$this->sUUID = self::generateUUID();
		$this->sClassName = $sClassName;
		$this->sModuleName = $sModuleName;

		$this->aAttributes = array();
	}
	
	/**
	 * 
	 * @return string
	 */
	public function getName()
	{
		return $this->sClassName;
	}
	
	/**
	 * 
	 * @return string
	 */
	public function getModule()
	{
		return $this->sModuleName;
	}
	
    /**
     * Returns a pseudo-random v4 UUID
     *
     * This function is based on a comment by Andrew Moore on php.net
     *
     * @see http://www.php.net/manual/en/function.uniqid.php#94959
     * @return string
     */
    static public function generateUUID() 
	{
        return sprintf(

            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',

            // 32 bits for "time_low"
            mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),

            // 16 bits for "time_mid"
            mt_rand( 0, 0xffff ),

            // 16 bits for "time_hi_and_version",
            // four most significant bits holds version number 4
            mt_rand( 0, 0x0fff ) | 0x4000,

            // 16 bits, 8 bits for "clk_seq_hi_res",
            // 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
            mt_rand( 0, 0x3fff ) | 0x8000,

            // 48 bits for "node"
            mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
        );
    }

    /**
     * Checks if a string is a valid UUID.
     *
     * @param string $uuid
     * @return bool
     */
    static public function validateUUID($uuid) 
	{
        return preg_match(
            '/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/i',
            $uuid
        ) == true;
    }	
	
	/**
	 * @return array
	 */
	public static function getTypes()
	{
		return self::$aTypes;
	}

	/**
	 * @return void
	 */
	public function SetDefaults()
	{
		$aDefaultValues = array();
		foreach ($this->getMap() as $sMapKey => $aMap)
		{
			$aDefaultValues[$sMapKey] = $aMap[1];
		}
		
		$this->setValues($aDefaultValues);
	}

	/**
	 * @param array $aValues
	 * @return void
	 */
	public function setValues($aValues)
	{
		foreach ($aValues as $sKey => $mValue)
		{
			$this->{$sKey} = $mValue;
		}
	}	

	/**
	 * @param string $sPropertyName
	 * @return array
	 */
	public function isStringAttribute($sPropertyName)
	{
		return in_array(
				$this->getType($sPropertyName), 
				array(
					'string', 
					'text', 
					'datetime'
			)
		);
	}		
	
	/**
	 * @param string $sPropertyName
	 * @return array
	 */
	public function isEncryptedAttribute($sPropertyName)
	{
		$bResult = false;
		$aMap = $this->getMap();
		if (isset($aMap[$sPropertyName]))
		{
			$bResult = ($aMap[$sPropertyName][0] === 'encrypted');
		}
		
		return $bResult;
	}		

	/**
	 * @param string $sName
	 * @return bool
	 */
	public function __isset($sName)
	{
		$aMap = $this->getMap();
		return isset($aMap[$sName]) || isset($this->aAttributes[$sName]);
	}

	/**
	 * @param string $sAttribute
	 * @param mixed $mValue
	 * @return void
	 */
	public function __set($sAttribute, $mValue)
	{
		if (!($mValue instanceof \CAttribute))
		{
			$mValue = \CAttribute::createInstance(
				$sAttribute, 
				$mValue, 
				$this->getType($sAttribute), 
				$this->isEncryptedAttribute($sAttribute), 
				$this->iId
			);
		}
		if ($mValue->Encrypted)
		{
			$mValue->Encrypt();
		}
		$this->setAttribute($mValue);
	}

	/**
	 * @param string $sName
	 *
	 * @throws Exception
	 *
	 * @return mixed
	 */
	public function __get($sName)
	{
		$mValue = null;
		$oAttribute = $this->getAttribute($sName);
		if ($oAttribute instanceof \CAttribute)
		{
			$aMap = $this->getMap();
			$sType = 'string';
			if (isset($aMap[$sName]))
			{
				$sType = $aMap[$sName][0];
			}
			$oAttribute->setType($sType);
			if ($oAttribute->Encrypted)
			{
				$oAttribute->Decrypt();
			}
			$mValue = $oAttribute->Value;
		}

		return $mValue;
	}

	/**
	 * @return string
	 */
	public function getType($sAttribute)
	{
		$mType = 'string';
		
		$aMap = $this->getMap();
		if (isset($aMap[$sAttribute]))
		{
			$mType = $aMap[$sAttribute][0];
			if ($mType === 'encrypted')
			{
				$mType = 'string';
			}
		}
		
		return $mType;
	}
	
	/**
	 * @return bool
	 */
	public function validate()
	{
		return true;
	}

	/**
	 * @return array
	 */
	public function getMap()
	{
		if (!isset($this->aMap))
		{
			$aStaticMap = $this->getStaticMap();

			foreach (\CApi::GetModules() as $oModule)
			{
				$aStaticMap = array_merge($aStaticMap, $oModule->getObjectMap($this->sClassName));
			}
			$this->aMap = $aStaticMap;
		}
		
		return $this->aMap;
	}
	
	public function setAttribute(\CAttribute $oAttribute)
	{
		$this->aAttributes[$oAttribute->Name] = $oAttribute;
	}
	
	/**
	 * @return array
	 */
	public function getAttribute($sAttributeName)
	{
		return isset($this->aAttributes[$sAttributeName]) ? $this->aAttributes[$sAttributeName] : false;
	}	
	
	/**
	 * @return array
	 */
	public function getAttributes()
	{
		return $this->aAttributes;
	}	
	
	/**
	 * @return array
	 */
	public function getAttributesKeys()
	{
		return array_keys($this->aAttributes);
	}		

	/**
	 * @return int
	 */
	public function countAttributes()
	{
		return count($this->aAttributes);
	}	

	/**
	 * @param array 
	 */
	public function setStaticMap($aStaticMap)
	{
		$this->aStaticMap = $aStaticMap;
		$this->SetDefaults();
	}

	/**
	 * @return array
	 */
	public function getStaticMap()
	{
		return is_array($this->aStaticMap) ? $this->aStaticMap : array();
	}	
	
	/**
	 * @return array
	 */	
	public function toArray()
	{
		$aResult = array();
		foreach($this->aAttributes as $oAttribute)
		{
			$mValue = $oAttribute->Value;
			if ($this->isEncryptedAttribute($oAttribute->Name))
			{
				$mValue = \api_Utils::DecryptValue($oAttribute->Value);
			}

			$aResult[$oAttribute->Name] = $mValue;
		}
		return array_merge(
			array(
				'iObjectId' => $this->iId,
				'sUUID' => $this->sUUID
			), 
			$aResult
		);
	}
	
	/**
	 * alias to toArray
	 * 
	 * @return array
	 */	
	public function toResponseArray()
	{
		return $this->toArray();
	}
}

/**
 * @package EAV
 * @subpackage Classes
 */
class CAttribute
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
	 * @var bool $Encrypted
	 */
	public $Encrypted;
	
	/**
	 * @param string $sName
	 * @param mixed $mValue
	 * @param string $sType
	 * @param bool $bEncrypted
	 * @param int $iEntityId
	 */
	public function __construct($sName, $mValue = null, $sType = 'string', $bEncrypted = false, $iEntityId = 0)
	{
		$this->Id = 0;
		$this->EntityId = $iEntityId;
		$this->Name	= $sName;
		$this->Encrypted = $bEncrypted;
		
		if ($sType === null)
		{
			$sType = gettype($mValue);
		}
		else
		{
			$this->setType($sType);
		}
		$this->Type = $sType;
		$this->Value = $mValue;
		
	}
	
	/**
	 * @param string $sName
	 * @param mixed $sValue
	 * @param string $sType
	 * @param bool $bEncrypt
	 * @param int $iEntityId
	 * 
	 * @return \CAttribute
	 */
	public static function createInstance($sName, $sValue = null, $sType = null, $bEncrypt = false, $iEntityId = 0)
	{
		return new self($sName, $sValue, $sType, $bEncrypt, $iEntityId);
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
					$this->Value = api_Utils::Utf8Truncate($this->Value, (int) $iSize);
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
		$this->Value = \api_Utils::EncryptValue($this->Value);
	}
	
	public function Dencrypt()
	{
		$this->Value = \api_Utils::DecryptValue($this->Value);
	}	
}

