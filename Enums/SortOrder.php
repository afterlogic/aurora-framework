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
class SortOrder extends \Aurora\System\Enums\AbstractEnumeration
{
    public const ASC = 0;
    public const DESC = 1;

    /**
     * @var array
     */
    protected $aConsts = array(
        'ASC' => self::ASC,
        'DESC' => self::DESC
    );
}
