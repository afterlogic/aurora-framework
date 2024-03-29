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
class FileStorageType extends \Aurora\System\Enums\AbstractEnumeration
{
    public const Personal = 'personal';
    public const Corporate = 'corporate';
    public const Shared = 'shared';

    /**
     * @var array
     */
    protected $aConsts = array(
        'Personal' => self::Personal,
        'Corporate' => self::Corporate,
        'Shared' => self::Shared

    );
}
