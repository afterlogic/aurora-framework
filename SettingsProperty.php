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
 */
class SettingsProperty
{
    /**
     * @var mixed
     */
    public $Value;

    /**
     * @var string
     */
    public $Type;

    /**
     * @var string
     */
    public $SpecType;

    /**
     * @var bool
     */
    public $Changed;

    /**
     * @var string
     */
    public $Description;

    /**
     * @var bool
     */
    public $IsDefault;

    /**
     *
     * @param mixed $mValue
     * @param string $sType
     * @param string $sSpecType
     * @param string $sDescription
     */
    public function __construct($mValue, $sType, $sSpecType = null, $sDescription = '')
    {
        $this->Value = $mValue;
        $this->Type = $sType;
        $this->SpecType = $sSpecType;
        $this->Description = $sDescription;
        $this->Changed = false;
        $this->IsDefault = true;
    }
}
