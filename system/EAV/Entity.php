<?php
/*
 * @copyright Copyright (c) 2017, Afterlogic Corp.
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

namespace Aurora\System\EAV;

class Entity
{
	/**
	 * @var int
	 */
	public $EntityId;

	/**
	 * @var int
	 */
	public $UUID;

	/**
	 * @var string
	 */
	protected $sModuleName;

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
	protected $aMap = null;
	
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
	 * @var array
	 */
	protected static $aReadOnlyAttributes = array(
		'entityid', 
		'uuid'
	);
	
	/**
	 * 
	 * @param string $sClassName
	 * @param string $sModuleName
	 * @return \Aurora\System\EAV\Entity
	 */
	public static function createInstance($sClassName, $sModuleName = '')
	{
		return class_exists($sClassName) ? (new $sClassName($sModuleName)) : new \Aurora\System\EAV\Entity($sModuleName);
	}

	/**
	 * @param string $sModuleName = ''
	 */
	public function __construct($sModuleName = '')
	{
		$this->EntityId = 0;
		$this->UUID = self::generateUUID();
		
		$this->sModuleName = $sModuleName;

		$this->aAttributes = array();
		
		$this->setStaticMap();
	}
	
	/**
	 * 
     * @param string $sModuleName
	 * @return string
	 */
	public function setModule($sModuleName)
	{
		return $this->sModuleName = $sModuleName;
	}

	/**
	 * 
	 * @return string
	 */
	public function getName()
	{
		return get_class($this);
	}
	
	/**
	 * 
	 * @return string
	 */
	public function getModule()
	{
		return $this->sModuleName;
	}
	
	public function isModuleDisabled($sModuleName)
	{
		$sDisabledModules = isset($this->{'@DisabledModules'}) ? $this->{'@DisabledModules'} : '';
		$aDisabledModules =  !empty(trim($sDisabledModules)) ? array($sDisabledModules) : array();
		if (substr_count($sDisabledModules, "|") > 0)
		{
			$aDisabledModules = explode("|", $sDisabledModules);
		}
		
		return in_array($sModuleName, $aDisabledModules);
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
		$aMapItem = $this->getMapItem($sPropertyName);
		if ($aMapItem !== null && is_array($aMapItem))
		{
			$bResult = ($aMapItem[0] === 'encrypted');
		}
		
		return $bResult;
	}		

	/**
	 * @param string $sName
	 * @return bool
	 */
	public function __isset($sName)
	{
		return ($this->getMapItem($sName) !== null) || isset($this->aAttributes[$sName]);
	}

	/**
	 * @param string $sAttribute
	 * @param mixed $mValue
	 * @return void
	 */
	public function __set($sAttribute, $mValue)
	{
		if (!($mValue instanceof \Aurora\System\EAV\Attribute))
		{
			if ($this->issetAttribute($sAttribute))
			{
				$oAttribute = $this->getAttribute($sAttribute);
				if ($oAttribute->Encrypted)
				{
					$oAttribute->Encrypted = false;
				}
				$oAttribute->Value = $mValue;
				$mValue = $oAttribute;
			}
			else
			{
				$mValue = \Aurora\System\EAV\Attribute::createInstance(
					$sAttribute, 
					$mValue, 
					$this->getType($sAttribute), 
					$this->isEncryptedAttribute($sAttribute), 
					$this->EntityId
				);
			}
		}
		if ($mValue->IsEncrypt)
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
		if ($oAttribute instanceof \Aurora\System\EAV\Attribute)
		{
			$oAttribute->setType($oAttribute->Type);
			if ($oAttribute->IsEncrypt)
			{
				$oAttribute->Decrypt();
			}
			$mValue = $oAttribute->Value;
		}
		else
		{
			
			$aMapItem = $this->getMapItem($sName);
			if (isset($aMapItem))
			{
				$oAttribute = \Aurora\System\EAV\Attribute::createInstance($sName, $aMapItem[1], $aMapItem[0]);
				if ($oAttribute->IsEncrypt)
				{
					$oAttribute->Decrypt();
				}	
				$this->setAttribute($oAttribute);
				$mValue = $oAttribute->Value;
			}
		}

		return $mValue;
	}
	
	public function populate($aProperties)
	{
		$aMap = $this->getMap();
		foreach ($aProperties as $sKey => $mValue)
		{
			if (isset($aMap[$sKey]))
			{
				$this->{$sKey} = $mValue;
			}
		}
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
			$this->aMap = array_merge(
				$this->getStaticMap(), 
				\Aurora\System\Api::GetModuleManager()->getExtendedObject($this->getName())
			);
		}
		return $this->aMap;
	}
	
	/**
	 * @return array
	 */
	protected function getMapItem($sName)
	{
		$aMap = $this->getMap();
		return isset($aMap[$sName]) ? $aMap[$sName] : null;
	}	
	
	/**
	 * @return bool
	 */
	public function issetAttribute($sAttributeName)
	{
		return isset($this->aAttributes[$sAttributeName]);
	}	

	public function setAttribute(\Aurora\System\EAV\Attribute $oAttribute)
	{
		if (!in_array(strtolower($oAttribute->Name), \Aurora\System\EAV\Entity::$aReadOnlyAttributes))
		{
			$oAttribute->EntityId = $this->EntityId;
			$this->aAttributes[$oAttribute->Name] = $oAttribute;
		}
	}
	
	/**
	 * @return \Aurora\System\EAV\Attribute
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
	public function setStaticMap()
	{
		foreach ($this->getMap() as $sKey => $aMap)
		{
			$this->{$sKey} = $aMap[1];
		}
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
		$aResult = array(
			'EntityId' => $this->EntityId,
			'UUID' => $this->UUID
		);
		foreach($this->aAttributes as $oAttribute)
		{
			$mValue = $oAttribute->Value;
			if ($this->isEncryptedAttribute($oAttribute->Name))
			{
				$mValue = \Aurora\System\Utils::DecryptValue($oAttribute->Value);
			}

			$aResult[$oAttribute->Name] = $mValue;
		}
		return $aResult;
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