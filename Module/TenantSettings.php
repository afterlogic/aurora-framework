<?php
/**
 * This code is licensed under AGPLv3 license or Afterlogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

namespace Aurora\System\Module;

/**
 * @license https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @license https://afterlogic.com/products/common-licensing Afterlogic Software License
 * @copyright Copyright (c) 2019, Afterlogic Corp.
 *
 * @package Api
 */
class TenantSettings extends \Aurora\System\AbstractSettings
{
    protected $sTenantName;

    public function __construct($sModuleName, $sTenantName)
    {
        $this->sTenantName = $sTenantName;
        $sTenantsPath = $sPath = \Aurora\System\Api::GetModuleManager()->GetModulesSettingsPath() . 'tenants';

        if (!file_exists($sTenantsPath))
        {
            @\mkdir($sTenantsPath);
        }

        $sPath = $sTenantsPath . '/' . $sTenantName . '/' .  $sModuleName . '.config.json';
        parent::__construct($sPath);
    }

    /**
     * @var \Aurora\System\SettingsProperty
     */
    public function SetProperty($oProperty)
    {
        $this->aContainer[$oProperty->Name] = $oProperty;
    }

    /**
     * @return string
     */
    public function GetTenantName()
    {
        return $this->sTenantName;
    }
}
