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
class UserRole extends AbstractEnumeration
{
    public const SuperAdmin = 0;
    public const TenantAdmin = 1;
    public const NormalUser = 2;
    public const Customer = 3;
    public const Anonymous = 4;

    /**
     * @var array
     */
    protected $aConsts = array(
        'SuperAdmin' => self::SuperAdmin,
        'TenantAdmin' => self::TenantAdmin,
        'NormalUser' => self::NormalUser,
        'Customer' => self::Customer,
        'Anonymous' => self::Anonymous,
    );
}
