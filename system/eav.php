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
		$this->sUUID = '';
		$this->sClassName = $sClassName;
		$this->sModuleName = $sModuleName;

		$this->aAttributes = array();
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
		foreach ($this->getMap()as $sMapKey => $aMap)
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
	 * @return bool
	 */
	public function isAttribute($sPropertyName)
	{
		$aMap = $this->getMap();
		return isset($aMap[$sPropertyName]) || isset($this->aAttributes[$sPropertyName]);
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
	 * @param string $sPropertyName
	 * @return bool
	 */
	public function __isset($sPropertyName)
	{
		return $this->isAttribute($sPropertyName);
	}

	/**
	 * @param string $sKey
	 * @param mixed $mValue
	 * @return void
	 */
	public function __set($sKey, $mValue)
	{
		$aMap = $this->getMap();
		
		$sType = 'string';
		if (isset($aMap[$sKey]) && isset($aMap[$sKey][0]))
		{
			$sType = $aMap[$sKey][0];
		}
		$this->setType($mValue, $sType);

		if ($this->__USE_TRIM_IN_STRINGS__ && 0 === strpos($sType, 'string'))
		{
			$mValue = trim($mValue);
		}

		$this->addAttribute(\CAttribute::createInstance(
			$sKey, 
			$mValue, 
			$sType, 
			$this->isEncryptedAttribute($sKey), 
			$this->iId
		), true);
	}

	/**
	 * @param string $sKey
	 *
	 * @throws Exception
	 *
	 * @return mixed
	 */
	public function __get($sKey)
	{
		$mValue = null;
		$oAttribute = $this->getAttribute($sKey);
		if ($oAttribute instanceof \CAttribute)
		{
			$aMap = $this->getMap();
			$sType = 'string';
			if (isset($aMap[$sKey]))
			{
				$sType = $aMap[$sKey][0];
			}
			$mValue = $oAttribute->Value;
			$this->setType($mValue, $sType);
		}

		return $mValue;
	}

	/**
	 * @return string
	 */
	public function getType($sAttributeName)
	{
		$mType = 'string';
		
		$aMap = $this->getMap();
		if (isset($aMap[$sAttributeName]))
		{
			$mType = $aMap[$sAttributeName][0];
			if ($mType === 'encrypted')
			{
				$mType = 'string';
			}
		}
		
		return $mType;
	}
	
	/**
	 * @param mixed $mValue
	 * @param string $sType
	 */
	protected function setType(&$mValue, $sType)
	{
		$sType = strtolower($sType);
		if (in_array($sType, array('string', 'int', 'array')))
		{
			settype($mValue, $sType);
		}
		else if (in_array($sType, array('bool')))
		{
			settype($mValue, 'int');
		}
		else if (in_array($sType, array('encoded', 'datetime')))
		{
			settype($mValue, 'string');
		}
		else if (0 === strpos($sType, 'string('))
		{
			settype($mValue, 'string');
			if (0 < strlen($mValue))
			{
				$iSize = substr($sType, 7, -1);
				if (is_numeric($iSize) && (int) $iSize < strlen($mValue))
				{
					$mValue = api_Utils::Utf8Truncate($mValue, (int) $iSize);
				}
			}
		}
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
	
	public function addAttribute(\CAttribute $oAttribute, $bOverwrite = false)
	{
		if (!isset($this->aAttributes[$oAttribute->Name]) || $bOverwrite)
		{
			$this->aAttributes[$oAttribute->Name] = $oAttribute;
		}
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
			$aResult[$oAttribute->Name] = $oAttribute->Value;
		}
		return array_merge(
			array(
				'iObjectId' => $this->iId,
				'sUUID' => $this->sUUID
			), 
			$aResult
		);
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
	 * @var bool $Encrypt
	 */
	public $Encrypt;
	
	/**
	 * @param string $sName
	 * @param mixed $sValue
	 * @param string $sType
	 * @param bool $bEncrypt
	 * @param int $iEntityId
	 */
	public function __construct($sName, $sValue = null, $sType = 'string', $bEncrypt = false, $iEntityId = 0)
	{
		$this->Id = 0;
		$this->EntityId = $iEntityId;
		$this->Name	= $sName;
		$this->Value = $sValue;
		$this->Type = $sType;
		$this->Encrypt = $bEncrypt;
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
	public static function createInstance($sName, $sValue = null, $sType = 'string', $bEncrypt = false, $iEntityId = 0)
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
	
}

