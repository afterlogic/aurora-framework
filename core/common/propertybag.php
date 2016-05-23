<?php


/* -AFTERLOGIC LICENSE HEADER- */

/**
 * @package Api
 */
abstract class api_APropertyBag
{
	/**
	 * @var bool
	 */
	public $__USE_TRIM_IN_STRINGS__;

	/**
	 * @var int
	 */
	public $iObjectId;

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
	 * @param string $sClassName
	 * @param string $sModuleName = ''
	 */
	public function __construct($sClassName, $sModuleName = '')
	{
		$this->__USE_TRIM_IN_STRINGS__ = false;
		
		$this->iObjectId = 0;
		$this->sClassName = $sClassName;
		$this->sModuleName = $sModuleName;

		$this->aContainer = array();
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
					$this->{$sKey} = api_Utils::DecodePassword($oRow->{$aTypes[1]});
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
	public function getPropertyType($sPropertyName)
	{
		$sResult = 'string';
		$aMap = $this->getMap();
		if (isset($aMap[$sPropertyName]) && isset($aMap[$sPropertyName][0]))
		{
			$sResult = $aMap[$sPropertyName][0];
		}
		
		return $sResult;
	}	

	/**
	 * @return array
	 */
	public function isStringProperty($sPropertyName)
	{
		$sType = $this->getPropertyType($sPropertyName);
		
		return ($sType === 'string' || $sType === 'text');
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
		
		if (isset($aMap[$sKey]))
		{
			$this->setType($mValue, $aMap[$sKey][0]);

			if ($this->__USE_TRIM_IN_STRINGS__ && 0 === strpos($aMap[$sKey][0], 'string'))
			{
				$mValue = trim($mValue);
			}

			$this->aContainer[$sKey] = $mValue;
		}
		else
		{
			throw new CApiBaseException(Errs::Container_UndefinedProperty, null, array('{{PropertyName}}' => $sKey));
		}
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
		$mReturn = null;
		if (array_key_exists($sKey, $this->aContainer))
		{
			$mReturn = $this->aContainer[$sKey];
		}
		else
		{
			throw new Exception('Undefined property '.$sKey);
		}

		return $mReturn;
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
		else if (in_array($sType, array('datetime', 'bool')))
		{
			settype($mValue, 'int');
		}
		else if (in_array($sType, array('password')))
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
	public function initBeforeChange()
	{
		return true;
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
		$aStaticMap = $this->getStaticMap();
		$aModules = \CApi::GetModuleManager()->GetModules();
		
		foreach ($aModules as $oModule)
		{
			$aStaticMap = array_merge($aStaticMap, $oModule->getObjectMap($this->sClassName));
		}
		
		return $aStaticMap;
	}

	/**
	 * @return array
	 */
	public function getStaticMap()
	{
		return is_array($this->aStaticMap) ? $this->aStaticMap : array();
	}	
}
