<?php
/**
 * This code is licensed under AGPLv3 license or Afterlogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

namespace Aurora\System\Module;

use Aurora\System\Api;

/**
 * @license https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @license https://afterlogic.com/products/common-licensing Afterlogic Software License
 * @copyright Copyright (c) 2019, Afterlogic Corp.
 *
 * @package Api
 */
class Settings extends \Aurora\System\AbstractSettings
{
    /**
     * @var \Aurora\System\Module\TenantSettings[]
     */
    protected $aTenantSettings = [];

    /**
     * @var string
     */
    public $ModuleName;

    /**
     * @param string $sModuleName
     */
    public function __construct($sModuleName)
    {
        $this->ModuleName = $sModuleName;

        parent::__construct(
            Api::GetModuleManager()->GetModulesSettingsPath() . $sModuleName . '.config.json'
        );

        $this->initDefaults();
    }

    protected function initDefaults()
    {
        $this->aContainer = [];
    }

    /**
     * @param string $sTenantName
     *
     * @return \Aurora\System\Module\TenantSettings
     */
    public function GetTenantSetttings($sTenantName)
    {
        if (!isset($this->aTenantSettings[$sTenantName])) {
            $oTenantSettings = new TenantSettings(
                $this->ModuleName,
                $sTenantName
            );
            $oTenantSettings->Load();
            $this->aTenantSettings[$sTenantName] = $oTenantSettings;
        }

        return isset($this->aTenantSettings[$sTenantName]) ? $this->aTenantSettings[$sTenantName] : null;
    }

    /**
     * @param string $sName
     * @param mixed $sDefaultValue
     *
     * @return mixed
     */
    public function GetValue($sName, $sDefaultValue = null)
    {
        return $this->GetTenantValue(Api::getTenantName(), $sName, $sDefaultValue);
    }

    /**
     * @param string $sName
     * @param mixed $sDefaultValue
     *
     * @return mixed
     */
    public function GetTenantValue($sTenantName, $sName, $sDefaultValue = null)
    {
        $mResult = parent::GetValue($sName, $sDefaultValue);

        if ($this->IsTenantSettingsExists($sTenantName)) {
            $oTenantSettings = $this->GetTenantSetttings($sTenantName);
            if ($oTenantSettings !== null && isset($oTenantSettings->{$sName})) {
                $mResult = $oTenantSettings->GetValue($sName, $sDefaultValue);
            }
        }

        return $mResult;
    }

    public function SaveTenantSettings($sTenantName, $aValues = [])
    {
        $mResult = false;
        $oTenantSettings =  $this->GetTenantSetttings($sTenantName);
        foreach ($aValues as $key => $value) {
            if (!isset($oTenantSettings->{$key}) && isset($this->aContainer[$key])) {
                $oTenantSettings->SetProperty($key, $this->aContainer[$key]);
            }
            $oTenantSettings->SetValue($key, $value);
        }
        if ($oTenantSettings !== null) {
            $mResult = $oTenantSettings->Save();
        }

        return $mResult;
    }

    public function IsTenantSettingsExists($sTenantName)
    {
        return \file_exists(Api::GetModuleManager()->GetModulesSettingsPath() . 'tenants/' . $sTenantName . '/' . $this->ModuleName . '.config.json');
    }
}
