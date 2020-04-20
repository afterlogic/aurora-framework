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
			\Aurora\System\Api::GetModuleManager()->GetModulesSettingsPath() . $sModuleName . '.config.json'
		);
	}

	public function GetDefaultConfigFilePath()
	{
		return \Aurora\System\Api::GetModuleManager()->GetModulesRootPath() . $this->ModuleName . '/config.json';
	}

	public function InitDefaultConfiguration()
	{
		if (\file_exists($this->GetDefaultConfigFilePath()))
		{
			$sModulesSettingsPath = \Aurora\System\Api::GetModuleManager()->GetModulesSettingsPath();
			if (!\file_exists($sModulesSettingsPath))
			{
				\set_error_handler(function() {});
				\mkdir($sModulesSettingsPath, 0777);
				\restore_error_handler();
				if (!\file_exists($sModulesSettingsPath))
				{
					return;
				}
			}
			\copy($this->GetDefaultConfigFilePath(), $this->sPath);
		}
	}

	public function Load()
	{
		$bResult = false;
		if (!\file_exists($this->sPath))
		{
			$mData = $this->LoadDataFromFile($this->GetDefaultConfigFilePath());
		}
		else
		{
			$mData = $this->LoadDataFromFile($this->sPath);
		}

		if (!$mData)
		{
			$mData = $this->LoadDataFromFile($this->sPath.'.bak');
			if ($mData)
			{
				\copy($this->sPath.'.bak', $this->sPath);
			}
			else
			{
				$this->Save();
			}
		}

		if ($mData !== false)
		{
			$this->aContainer = $this->ParseData($mData);
			$this->bIsLoaded = true;
			$bResult = true;
		}

		return $bResult;
	}

	/**
	 * @param string $sTenantName
	 *
	 * @return \Aurora\System\Module\TenantSettings
	 */
	public function GetTenantSetttings($sTenantName)
	{
		if (!isset($this->aTenantSettings[$sTenantName]) && $this->IsTenantSettingsExists($sTenantName))
		{
			$this->aTenantSettings[$sTenantName] = new TenantSettings(
				$this->ModuleName,
				$sTenantName
			);
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
		return $this->GetTenantValue(\Aurora\System\Api::getTenantName(), $sName, $sDefaultValue);
   }

	/**
	 * @param string $sName
	 * @param mixed $sDefaultValue
	 *
 	 * @return mixed
	 */
	public function GetTenantValue($sTenantName, $sName, $sDefaultValue = null)
	{
		$mResult = $sDefaultValue;

		 $oTenantSettings = $this->GetTenantSetttings(
			$sTenantName
		 );

		 if (isset($oTenantSettings) && isset($oTenantSettings->{$sName}))
		 {
			$mResult = $oTenantSettings->GetValue($sName, $sDefaultValue);
		 }
		 else
		 {
			$mResult = parent::GetValue($sName, $sDefaultValue);
		 }


		 return $mResult;
	}

	/**
	 * @param string $sTenantName
	 * @param string $sName
	 * @param string $sValue
	 */
	public function SetTenantValue($sTenantName, $sName, $sValue = null)
    {
		if (isset($this->{$sName}))
		{
			$oTenantSettings = null;
			if (!isset($this->aTenantSettings[$sTenantName]))
			{
				$oTenantSettings = new TenantSettings(
					$this->ModuleName,
					$sTenantName
				);
			}
			else
			{
				$oTenantSettings = $this->aTenantSettings[$sTenantName];
			}

			if (isset($oTenantSettings))
			{
				if (!isset($oTenantSettings->{$sName}) && isset($this->aContainer[$sName]))
				{
                    $oTenantSettings->SetProperty($this->aContainer[$sName]);
				}
				$oTenantSettings->SetValue($sName, $sValue);
				$this->aTenantSettings[$sTenantName] = $oTenantSettings;
			}
			else
			{
				throw new \Aurora\System\Exceptions\SettingsException();
			}
		}
		else
		{
			throw new \Aurora\System\Exceptions\SettingsException();
		}
	}

	public function SaveTenantSettings($sTenantName)
	{
		$mResult = false;
		$oTenantSettings =  $this->GetTenantSetttings($sTenantName);
		if (isset($oTenantSettings))
		{
			$mResult = $oTenantSettings->Save();
		}

		return $mResult;
	}

	public function GetDefaultConfigValues()
	{
		$oDefaultSettings = new DefaultSettings($this->GetDefaultConfigFilePath());
		$oDefaultSettings->Load();
		return $oDefaultSettings->GetValues();
	}

	public function IsTenantSettingsExists($sTenantName)
	{
		return \file_exists(\Aurora\System\Api::GetModuleManager()->GetModulesSettingsPath() . 'tenants/' . $sTenantName . '/' .  $this->ModuleName . '.config.json');
	}
}
