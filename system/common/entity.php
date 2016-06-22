<?php


/* -AFTERLOGIC LICENSE HEADER- */

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
	protected $aContainer;
	
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
		$this->sClassName = $sClassName;
		$this->sModuleName = $sModuleName;

		$this->aContainer = array();
	}
	
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
	 * @param stdClass $oRow
	 */
	public function InitByDbRow($oRow)
	{
		$aMap = $this->getMap();
		foreach ($aMap as $sKey => $aTypes)
		{
			if (isset($aTypes[1]) && property_exists($oRow, $aTypes[1]))
			{
				if ('password' === $aTypes[0])
				{
					$this->{$sKey} = api_Utils::DecryptValue($oRow->{$aTypes[1]});
				}
				else if ('datetime' === $aTypes[0])
				{
					$iDateTime = 0;
					$aDateTime = api_Utils::DateParse($oRow->{$aTypes[1]});
					if (is_array($aDateTime))
					{
						$iDateTime = gmmktime($aDateTime['hour'], $aDateTime['minute'], $aDateTime['second'],
							$aDateTime['month'], $aDateTime['day'], $aDateTime['year']);

						if (false === $iDateTime || $iDateTime <= 0)
						{
							$iDateTime = 0;
						}
					}

					$this->{$sKey} = $iDateTime;
				}
				else if ('serialize' === $aTypes[0])
				{
					$this->{$sKey} = ('' === $oRow->{$aTypes[1]} || !is_string($oRow->{$aTypes[1]})) ?
						'' : unserialize($oRow->{$aTypes[1]});
				}
				else
				{
					$this->{$sKey} = $oRow->{$aTypes[1]};
				}

				$this->FlushObsolete($sKey);
			}
		}
	}		

	/**
	 * @param string $sPropertyName
	 * @return bool
	 */
	public function IsProperty($sPropertyName)
	{
		$aMap = $this->getMap();
		return isset($aMap[$sPropertyName]);
	}

	/**
	 * @return array
	 */
	public function isStringAttribute($sPropertyName)
	{
		return in_array(
				$this->getAttributeType($sPropertyName), 
				array(
					'string', 
					'text', 
					'datetime'
			)
		);
	}		
	
	/**
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
		return $this->IsProperty($sPropertyName);
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
		if (isset($aMap[$sKey]))
		{
			$sType = $aMap[$sKey][0];
		}
		$this->setType($mValue, $sType);

		if ($this->__USE_TRIM_IN_STRINGS__ && 0 === strpos($sType, 'string'))
		{
			$mValue = trim($mValue);
		}

		$this->aContainer[$sKey] = $mValue;
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
		if (array_key_exists($sKey, $this->aContainer))
		{
			$mValue = $this->aContainer[$sKey];
			
			$aMap = $this->getMap();
			$sType = 'string';
			if (isset($aMap[$sKey]))
			{
				$sType = $aMap[$sKey][0];
			}
			$this->setType($mValue, $sType);
		}
		else
		{
			throw new Exception('Undefined property '.$sKey);
		}

		return $mValue;
	}

	public function getType($sAttributeName)
	{
		
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
			$aModules = \CApi::GetModules();

			foreach ($aModules as $oModule)
			{
				$aStaticMap = array_merge($aStaticMap, $oModule->getObjectMap($this->sClassName));
			}
			$this->aMap = $aStaticMap;
		}
		
		return $this->aMap;
	}
	
	/**
	 * @return array
	 */
	public function getAttributeInfo($sAttributeName)
	{
		$mResult = false;
		$aMap = $this->getMap();
		if (isset($aMap[$sAttributeName]))
		{
			$mResult = $aMap[$sAttributeName];
		}
		
		return $mResult;
	}	
	
	/**
	 * @return array
	 */
	public function getAttributeType($sAttributeName)
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
	
	public function toArray()
	{
		return array_merge(array('iObjectId' => $this->iId), $this->aContainer);
	}
	
	public function getAttributes()
	{
		return $this->aContainer;
	}
}
