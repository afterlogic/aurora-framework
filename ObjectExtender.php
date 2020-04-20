<?php
/**
 * This code is licensed under AGPLv3 license or Afterlogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

namespace Aurora\System;

/**
 * @license https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @license https://afterlogic.com/products/common-licensing Afterlogic Software License
 * @copyright Copyright (c) 2019, Afterlogic Corp.
 *
 * @package Api
 */
class ObjectExtender
{
	/**
     * @var array
     */
    protected $_aObjects = [];

    /**
     *
     */
    protected static $self = null;

    /**
	 *
	 * @return \self
	 */
	public static function createInstance()
	{
		return new self();
	}

	/**
	 *
	 * @return ObjectExtender
	 */
	public static function getInstance()
	{
		if (is_null(self::$self))
		{
			self::$self = new self();
		}

		return self::$self;
	}

	/**
	 *
	 * @param string $sModule
	 * @param string $sType
	 * @param array $aMap
	 */
	public function extend($sModule, $sType, $aMap)
	{
		foreach ($aMap as $sKey => $aValue)
		{
			$aValue['@Extended'] = true;
			$this->_aObjects[$sType][$sModule . Module\AbstractModule::$Delimiter . $sKey] = $aValue;
		}
    }

	/**
	 *
	 * @param string $sType
	 * @return array
	 */
	public function getObject($sType)
	{
		return isset($this->_aObjects[$sType]) ? $this->_aObjects[$sType] : [];
	}

	/**
	 *
	 * @param string $sType
	 * @return boolean
	 */
	public function issetObject($sType)
	{
		return isset($this->_aObjects[$sType]);
	}
}
