<?php
/**
 * This code is licensed under AGPLv3 license or Afterlogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

namespace Aurora\System\Enums;

/**
 * @license https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @license https://afterlogic.com/products/common-licensing Afterlogic Software License
 * @copyright Copyright (c) 2019, Afterlogic Corp.
 */
abstract class AbstractEnumeration
{
    /**
     * @var array
     */
    protected $aConsts = array();

    /**
     *
     * @return array
     */
    public function getMap()
    {
        return $this->aConsts;
    }

    /**
     * @return bool
     */
    public static function validateValue($value)
    {
        /* @phpstan-ignore-next-line */
        return in_array($value, array_values((new static())->getMap()));
    }
}
