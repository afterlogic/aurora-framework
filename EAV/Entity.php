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
 * @package Api
 */
class Entity
{
	/**
	 * @var int
	 */
	public $EntityId = 0;

	/**
	 * @var string
	 */
	public $UUID;

	/**
	 * @var string
	 */
	public $ModuleName;

	/**
	 *
	 * @var string
	 */
	public $ParentType = null;

	/**
	 *
	 * @var string
	 */
	public $ParentModuleName = null;

	/**
	 *
	 * @var string
	 */
	public $ParentUUID = null;

	/**
	 * @var array
	 */
	protected $aAttributes = [];

	/**
	 * @var array
	 *
	 * [
	 * 	'AttributeName'	=> 	// name of attribute
	 * 	[
	 * 		'string', 		// type of attribute
	 * 		'', 			// default value for attribute
	 * 		false,			// is overrided attribute
	 * 		false			// is restricted attribute
	 * 	],
	 * ]
	 *
	 */
	protected $aStaticMap = [];

	/**
	 * @var array
	 */
	protected $aMap = null;

	/**
	 * @var string[]
	 */
	protected static $aTypes = [
		'int',
		'string',
		'text',
		'bool',
		'datetime',
		'mediumblob',
		'double',
		'bigint',
		'nodb'
	];

	/**
	 * @var array
	 */
	public static $aSystemAttributes = [
		'entityid'		=> 'int',
		'uuid' 			=> 'string',
		'modulename'	=> 'string',
		'parentuuid'	=> 'string',
		'entitytype'	=> 'string'
	];

	/**
	 *
	 * @param string $sClassName
	 * @param string $sModuleName
	 * @return \Aurora\System\EAV\Entity
	 */
	public static function createInstance($sClassName, $sModuleName = '')
	{
		return class_exists($sClassName) ? (new $sClassName($sModuleName)) : new self($sModuleName);
	}

	/**
	 * @param string $sModuleName = ''
	 */
	public function __construct($sModuleName = '')
	{
		$this->ModuleName = $sModuleName;
		$this->UUID = self::generateUUID();

		$this->initDefaults();
	}

	/**
	 * Undocumented function
	 *
	 * @return void
	 */
	protected function initDefaults()
	{
		foreach ($this->getMap() as $sKey => $aMap)
		{
			$oAttribute = $this->initAttribute($sKey, $aMap[1]);
			if ($oAttribute)
			{
				$oAttribute->IsDefault = true;
				$this->setAttribute($oAttribute);
			}
		}
	}

	/**
	 *
     * @param string $sModuleName
	 * @return string
	 */
	public function setModule($sModuleName)
	{
		return $this->ModuleName = $sModuleName;
	}

	/**
	 *
	 * @return string
	 */
	public function getName()
	{
		return \get_class($this);
	}

	/**
	 *
	 * @return string
	 */
	public function getModule()
	{
		return $this->ModuleName;
	}

	public function isModuleDisabled($sModuleName)
	{
		$aDisabledModules = $this->getDisabledModules();

		return in_array($sModuleName, $aDisabledModules);
	}

	public function getDisabledModules()
	{
		$sDisabledModules = isset($this->{'@DisabledModules'}) ? \trim($this->{'@DisabledModules'}) : '';
		$aDisabledModules =  !empty($sDisabledModules) ? [$sDisabledModules] : [];
		if (substr_count($sDisabledModules, "|") > 0)
		{
			$aDisabledModules = explode("|", $sDisabledModules);
		}

		return $aDisabledModules;
	}

	public function clearDisabledModules()
	{
		$this->{'@DisabledModules'} = '';
		$this->saveAttribute('@DisabledModules');
	}

	public function disableModule($sModuleName)
	{
		$aDisabledModules = $this->getDisabledModules();
		if (!in_array($sModuleName, $aDisabledModules))
		{
			$aDisabledModules[] = $sModuleName;
			// clear array from empty values
			$aDisabledModules = array_filter($aDisabledModules, function ($var) {
				return !empty($var);
			});
			$this->{'@DisabledModules'} = implode("|", $aDisabledModules);
			$this->saveAttribute('@DisabledModules');
		}
	}

	public function disableModules($aModules)
	{
		$aDisabledModules = $this->getDisabledModules();
		foreach ($aModules as $sModuleName)
		{
			if (!in_array($sModuleName, $aDisabledModules))
			{
				$aDisabledModules[] = $sModuleName;
				// clear array from empty values
				$aDisabledModules = array_filter($aDisabledModules, function ($var) {
					return !empty($var);
				});
				$this->{'@DisabledModules'} = implode("|", $aDisabledModules);
			}
		}
		$this->saveAttribute('@DisabledModules');
	}

	public function enableModule($sModuleName)
	{
		$aDisabledModules = $this->getDisabledModules();

		if (($iKey = array_search($sModuleName, $aDisabledModules)) !== false)
		{
			unset($aDisabledModules[$iKey]);
			$this->{'@DisabledModules'} = implode("|", $aDisabledModules);
			$this->saveAttribute('@DisabledModules');
		}
	}

	public function enableModules($aModules)
	{
		$aDisabledModules = $this->getDisabledModules();

		foreach ($aModules as $sModuleName)
		{
			if (($iKey = array_search($sModuleName, $aDisabledModules)) !== false)
			{
				unset($aDisabledModules[$iKey]);
				$this->{'@DisabledModules'} = implode("|", $aDisabledModules);
			}
		}
		$this->saveAttribute('@DisabledModules');
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
			[
				'string',
				'text',
				'datetime',
				'mediumblob'
			]
		);
	}

	public function isSystemAttribute($sAttribute)
	{
		return in_array(strtolower($sAttribute), array_keys(self::$aSystemAttributes));
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
	 * @param string $sPropertyName
	 * @return bool
	 */
	public function isExtendedAttribute($sPropertyName)
	{
		$bResult = false;
		$aMapItem = $this->getMapItem($sPropertyName);
		if ($aMapItem !== null && is_array($aMapItem))
		{
			$bResult = (isset($aMapItem['@Extended']) && $aMapItem['@Extended'] === true) ;
		}

		return $bResult;
	}

	/**
	 * @param string $sPropertyName
	 * @return bool
	 */
	public function canInheridAttribute($sPropertyName)
	{
		$bResult = false;
		$aMapItem = $this->getMapItem($sPropertyName);
		if ($aMapItem !== null && is_array($aMapItem))
		{
			$bResult = (isset($aMapItem[3]) && $aMapItem[3] === true) ;
		}

		return $bResult;
	}

	/**
	 * @param string $sAttribute
	 * @param mixed $mValue
	 * @return void
	 */
	public function setAttributeValue($sAttribute, $mValue)
	{
		$oAttribute = $this->initAttribute($sAttribute, $mValue);
		$oAttribute->IsDefault = false;
		$this->setAttribute($oAttribute);
	}

	/**
	 * @param string $sName
	 *
	 * @throws Exception
	 *
	 * @return mixed
	 */
	public function getAttributeValue($sName)
	{
		$mValue = null;
		$oAttribute = $this->getAttribute($sName);
		if ($oAttribute instanceof Attribute)
		{
			$oAttribute->setType($oAttribute->Type);
			if ($oAttribute->IsEncrypt)
			{
				$oAttribute->Decrypt();
			}
			$mValue = $oAttribute->Value;

			if ($this->isDefaultValue($sName, $mValue) && isset($this->ParentType))
			{
				if (is_subclass_of($this->ParentType, \Aurora\System\EAV\Entity::class))
				{
					if (isset($this->ParentUUID))
					{
						$oEntity = \Aurora\System\Managers\Eav::getInstance()->getEntity($this->ParentUUID);
						if (isset($oEntity) && $oEntity->canInheridAttribute($sName))
						{
							$mValue = $oEntity->{$sName};
							$oAttribute->Inherited = true;
						}
					}
				}
				else if(is_subclass_of($this->ParentType, \Aurora\System\AbstractSettings::class))
				{
					if($this->ParentType === \Aurora\System\Settings::class)
					{
						$mValue = \Aurora\System\Api::GetSettings()->GetValue($sName);
						$oAttribute->Inherited = true;
					}
					if($this->ParentType === \Aurora\System\Module\Settings::class)
					{
						if ($this->isExtendedAttribute($sName))
						{
							list($sModuleName, $sName) = \explode('::', $sName);
						}
						else
						{
							$sModuleName = $this->ModuleName;
						}
						$oModule = \Aurora\System\Api::GetModule($sModuleName);
						if ($oModule instanceof \Aurora\System\Module\AbstractModule)
						{
							$mValue = $oModule->getConfig($sName, $mValue);
							$oAttribute->Inherited = true;
						}
					}
				}
			}
		}
		else
		{
			$aMapItem = $this->getMapItem($sName);
			if (isset($aMapItem))
			{
				$oAttribute = Attribute::createInstance($sName, $aMapItem[1], $aMapItem[0]);
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
		$this->setAttributeValue($sAttribute, $mValue);
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
		return $this->getAttributeValue($sName);
	}

	/**
	 *
	 * @param type $aProperties
	 */
	public function populate($aProperties)
	{
		$aMap = $this->getMap();
		foreach ($aProperties as $sKey => $mValue)
		{
			if (isset($aMap[$sKey]))
			{
				$this->setAttributeValue($sKey, $mValue);
			}
		}
	}

	public function resetToDefaults()
	{
		foreach ($this->aAttributes as $oAttrinbute)
		{
			$this->setAttributeValue($oAttrinbute->Name, $this->getDefaultValue($oAttrinbute->Name));
		}
	}

	public function resetToDefault($sAttribute)
	{
		$mResult = \Aurora\System\Managers\Eav::getInstance()->deleteAttribute(
			$this->getType($sAttribute),
			$this->EntityId,
			$sAttribute
		);

		if ($mResult)
		{
			$this->{$sAttribute} = $this->getDefaultValue($sAttribute);
		}
	}

	/**
	 * @return string
	 */
	public function getType($sAttribute)
	{
		$mType = 'string';

		if ($this->isSystemAttribute($sAttribute))
		{
			if (isset(self::$aSystemAttributes[\strtolower($sAttribute)]))
			{
				$mType = self::$aSystemAttributes[\strtolower($sAttribute)];
			}
		}
		else
		{
			$aMap = $this->getMap();
			if (isset($aMap[$sAttribute]))
			{
				$mType = $aMap[$sAttribute][0];
				if ($mType === 'encrypted')
				{
					$mType = 'string';
				}
			}
		}

		return $mType;
	}

	/**
	 *
	 * @param type $sAttribute
	 * @param type $mValue
	 * @return type
	 */
	public function isDefaultValue($sAttribute, $mValue)
	{
		$oAttribute = $this->getAttribute($sAttribute);
		return ($oAttribute && $oAttribute->IsDefault);
	}

	/**
	 *
	 * @param type $sAttribute
	 * @return type
	 */
	public function getDefaultValue($sAttribute)
	{
		$mResult = null;
		$aMap = $this->getMap();
		if (isset($aMap[$sAttribute]))
		{
			$mResult = $aMap[$sAttribute][1];
		}

		return $mResult;
	}

	/**
	 *
	 * @param type $sAttribute
	 * @return type
	 */
	public function isOverridedAttribute($sAttribute)
	{
		$bOverride = false;
		$oAttribute = $this->getAttribute($sAttribute);
		if ($oAttribute instanceof Attribute)
		{
			$bOverride = $oAttribute->Override;
		}
		$aMap = $this->getMap();
		return ((isset($aMap[$sAttribute]) && isset($aMap[$sAttribute][2]) && $aMap[$sAttribute][2] === true) || $bOverride);
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
		$aExtendedObject = \Aurora\System\ObjectExtender::getInstance()->getObject($this->getName());
		$this->aMap = array_merge(
			$aStaticMap,
			$aExtendedObject
		);

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
	 * @param string $sAttribute
	 * @param mixed $mValue
	 * @return Attribute
	 */
	public function initAttribute($sAttribute, $mValue)
	{
		if (!($mValue instanceof Attribute))
		{
			if ($this->issetAttribute($sAttribute))
			{
				$oAttribute = $this->getAttribute($sAttribute);
				if ($oAttribute->Encrypted)
				{
					$oAttribute->Encrypted = false;
				}
				$oAttribute->Value = $mValue;
				$oAttribute->setType($oAttribute->Type);
				$mValue = $oAttribute;
			}
			else
			{
				$mValue = Attribute::createInstance(
					$sAttribute,
					$mValue,
					$this->getType($sAttribute),
					$this->isEncryptedAttribute($sAttribute),
					$this->EntityId,
					false,
					$this->isExtendedAttribute($sAttribute)
				);
			}
		}
		if ($mValue->IsEncrypt)
		{
			$mValue->Encrypt();
		}
		$mValue->Inherited = false;

		return $mValue;
	}

	/**
	 * @return bool
	 */
	public function issetAttribute($sAttributeName)
	{
		return isset($this->aAttributes[$sAttributeName]);
	}

	/**
	 *
	 * @param \Aurora\System\EAV\Attribute $oAttribute
	 */
	private function setAttribute(Attribute $oAttribute)
	{
		if (!$this->isSystemAttribute($oAttribute->Name))
		{
			$oAttribute->EntityId = $this->EntityId;
			$this->aAttributes[$oAttribute->Name] = $oAttribute;
		}
	}

	/**
	 *
	 * @param array $aAttributes
	 */
	public function setOverridedAttributes($aAttributes)
	{
		foreach($aAttributes as $sAttribute)
		{
			$oAttribute = $this->getAttribute($sAttribute);
			if ($oAttribute instanceof Attribute)
			{
				$oAttribute->Override = true;
			}
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
	 * @param bool
	 * @return array
	 */
	public function getAttributes($bOnlyOverrided = false)
	{
		$aAttributes = array();
		if ($bOnlyOverrided)
		{
			$aAttributes = $this->getOverridedAttributes();
		}
		else
		{
			$aAttributes = $this->aAttributes;
		}

		return $aAttributes;
	}

	public function getAttributesType()
	{
		$this->getMap();
	}

	/**
	 * @param bool
	 * @return array
	 */
	public function getOverridedAttributes()
	{
		$self = $this;
		return array_filter($this->aAttributes, function ($oAttribute) use ($self) {
				return $self->isOverridedAttribute($oAttribute->Name);
			}
		);
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
			$oAttribute = $this->initAttribute($sKey, $aMap[1]);
			if ($oAttribute)
			{
				$this->setAttribute($oAttribute);
			}
		}
	}

	/**
	 * @return array
	 */
	public function getStaticMap()
	{
		return is_array($this->aStaticMap) ? $this->aStaticMap : [];
	}

	/**
	 * @return array
	 */
	public function toArray()
	{
		$aResult = [
			'EntityId' => $this->EntityId,
			'UUID' => $this->UUID,
			'ParentUUID' => $this->ParentUUID,
			'ModuleName' => $this->ModuleName
		];

		foreach($this->aAttributes as $oAttribute)
		{
			if (!$this->isRestrictedAttribute($oAttribute->Name))
			{
				if ($oAttribute->Encrypted && !empty($this->{$oAttribute->Name}))
				{
					// Dummy encrypted attribute could be passed to client side by toResponseArray method.
					$aResult[$oAttribute->Name] = '*****';
				}
				else
				{
					$aResult[$oAttribute->Name] = $this->{$oAttribute->Name};
				}
			}
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

	public static function extend($sModuleName, $aMap)
	{
		\Aurora\System\ObjectExtender::getInstance()->extend($sModuleName, static::class, $aMap);
	}

	public function save()
	{
		return \Aurora\System\Managers\Eav::getInstance()->saveEntity($this);
	}

	public function saveAttribute($sName)
	{
		$oAttribute = $this->getAttribute($sName);
		if ($oAttribute instanceof Attribute)
		{
			return $oAttribute->save($this);
		}
	}

	public function saveAttributes($aAttributes)
	{
		$bResult = false;

		$aAttributeOjects = [];
		foreach ($aAttributes as $sName)
		{
			$aAttributeOjects[$sName] = $this->getAttribute($sName);
		}

		if (count($aAttributeOjects) > 0)
		{
			$bResult = \Aurora\System\Managers\Eav::getInstance()->setAttributes($this, $aAttributeOjects);
		}

		return $bResult;
	}

	public function isNodbAttribute($sAttributeName)
	{
		return $this->getType($sAttributeName) === 'nodb';
	}

	public function isRestrictedAttribute($sAttributeName)
	{
		$aMap = $this->getMap();
		return ((isset($aMap[$sAttributeName]) && isset($aMap[$sAttributeName][3]) && $aMap[$sAttributeName][3] === true));
	}
}
