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
abstract class AbstractSettings
{
    public const JSON_FILE_NAME = 'config.json';

    /**
     * @var array
     */
    protected $aContainer;

    /**
     * @var string
     */
    protected $sPath;

    /**
     * @var bool
     */
    protected $bIsLoaded;

    /**
     * @param string $sSettingsPath
     *
     * @return AbstractSettings
     */
    public function __construct($sSettingsPath)
    {
        $this->aContainer = [];
        $this->sPath = $sSettingsPath;
        $this->bIsLoaded = false;
    }

    /**
     *
     * @param string $sName
     *
     * @return bool
     */
    public function __isset($sName)
    {
        if (!$this->bIsLoaded) {
            $this->Load();
        }

        return isset($this->aContainer[$sName]);
    }

    /**
     *
     * @param string $sName
     * @param mixed $mValue
     */
    public function __set($sName, $mValue)
    {
        $this->SetValue($sName, $mValue);
    }

    /**
     *
     * @param string $sName
     *
     * @return mixed
     */
    public function __get($sName)
    {
        return $this->GetValue($sName);
    }

    /**
     * @return array
     */
    public function GetValues()
    {
        return $this->aContainer;
    }

    /**
     *
     * @return string
     */
    public function GetPath()
    {
        return $this->sPath;
    }

    /**
     * @param array $aValues
     */
    public function SetValues($aValues)
    {
        $this->aContainer = $aValues;
    }

    /**
     * @param string $sKey
     *
     * @return mixed
     */
    public function GetValue($sKey, $mDefault = null)
    {
        if (!$this->bIsLoaded) {
            $this->Load();
        }

        return (isset($this->aContainer[$sKey])) ? $this->aContainer[$sKey]->Value : $mDefault;
    }

    /**
     * @param string $sKey
     *
     * @return SettingsProperty
     */
    public function GetSettingsProperty($sKey)
    {
        if (!$this->bIsLoaded) {
            $this->Load();
        }

        return (isset($this->aContainer[$sKey])) ? $this->aContainer[$sKey] : null;
    }


    /**
     * @deprecated
     *
     * @param string $sKey
     *
     * @return mixed
     */
    public function GetConf($sKey, $mDefault = null)
    {
        return $this->GetValue($sKey, $mDefault);
    }

    /**
     * @param string $sKey
     * @param mixed $mValue = null
     *
     * @return bool
     */
    public function SetValue($sKey, $mValue)
    {
        $bResult = false;

        $sType = (isset($this->aContainer[$sKey])) ? $this->aContainer[$sKey]->Type : \gettype($mValue);
        if (!isset($this->aContainer[$sKey])) {
            $this->aContainer[$sKey] = new SettingsProperty($mValue, $sType);
        }

        switch ($sType) {
            case 'string':
                $mValue = (string) $mValue;
                break;
            case 'int':
            case 'integer':
                $mValue = (int) $mValue;
                break;
            case 'bool':
            case 'boolean':
                $mValue = (bool) $mValue;
                break;
            case 'spec':
                $mValue = $this->specValidate($mValue, $this->aContainer[$sKey]->SpecType);
                break;
            case 'array':
                if (!Utils::IsAssocArray($mValue)) {
                    // rebuild array indexes
                    $mValue = array_values($mValue);
                }
                break;
            default:
                $mValue = null;
                break;
        }
        $this->aContainer[$sKey]->Value = $mValue;
        $this->aContainer[$sKey]->Changed = true;

        return $bResult;
    }

    /**
     * @deprecated
     *
     * @param string $sKey
     * @param mixed $mValue = null
     *
     * @return bool
     */
    public function SetConf($sKey, $mValue)
    {
        return $this->SetValue($sKey, $mValue);
    }

    public function IsExists()
    {
        return \file_exists($this->sPath);
    }

    public function BackupConfigFile()
    {
        $sJsonFile = $this->sPath;
        if (\file_exists($sJsonFile)) {
            \copy($sJsonFile, $sJsonFile . '.bak');
        }
    }

    public function LoadDataFromBackup()
    {
        return $this->LoadDataFromFile($this->sPath . '.bak');
    }

    public function CheckConfigFile()
    {
        $bResult = true;

        // backup previous configuration
        $sJsonFile = $this->sPath;
        if (!\file_exists(\dirname($sJsonFile))) {
            \set_error_handler(function () {});
            \mkdir(\dirname($sJsonFile), 0777);
            \restore_error_handler();
            $bResult = \file_exists(\dirname($sJsonFile));
        }

        return $bResult;
    }

    public function SaveDataToConfigFile($aData)
    {
        $sJsonFile = $this->sPath;
        return (bool) \file_put_contents(
            $sJsonFile,
            \json_encode(
                $aData,
                JSON_PRETTY_PRINT | JSON_OBJECT_AS_ARRAY
            )
        );
    }

    public function ParseData($aData)
    {
        $aContainer = [];

        if (\is_array($aData)) {
            foreach ($aData as $sKey => $mValue) {
                if (isset($aData[$sKey])) {
                    $sSpecType = null;
                    $sDescription = '';
                    if (\is_array($mValue)) {
                        $sType = isset($mValue[1]) ? $mValue[1] : (isset($mValue[0]) ? \gettype($mValue[0]) : "string");
                        $sSpecType = isset($mValue[2]) ? $mValue[2] : null;
                        $sDescription = isset($mValue[3]) ? $mValue[3] : '';
                        $mValue = isset($mValue[0]) ? $mValue[0] : '';
                        if (isset($aData[$sKey . '_Description'])) {
                            $sDescription = isset($aData[$sKey . '_Description'][0]) ? $aData[$sKey . '_Description'][0] : '';
                            unset($aData[$sKey . '_Description']);
                        }
                    } else {
                        $sType = \gettype($mValue);
                    }

                    switch ($sType) {
                        case 'string':
                            $mValue = (string) $mValue;
                            break;
                        case 'int':
                        case 'integer':
                            $sType = 'int';
                            $mValue = (int) $mValue;
                            break;
                        case 'bool':
                        case 'boolean':
                            $sType = 'bool';
                            $mValue = (bool) $mValue;
                            break;
                        case 'spec':
                            $mValue = $this->specConver($mValue, $sSpecType);
                            break;
                        case 'array':
                            break;
                        default:
                            $mValue = null;
                            break;
                    }
                    if (null !== $mValue) {
                        $aContainer[$sKey] = new SettingsProperty($mValue, $sType, $sSpecType, $sDescription);
                    }
                }
            }
        }

        return $aContainer;
    }

    /**
     * @param string $sJsonFile
     *
     * @return bool
     */
    public function LoadDataFromFile($sJsonFile)
    {
        $mResult = false;

        if (\file_exists($sJsonFile)) {
            $sJsonData = \file_get_contents($sJsonFile);
            $mResult = \json_decode($sJsonData, true);
        }

        return $mResult;
    }

    /**
     * @param bool $bForceLoad
     *
     * @return bool
     */
    public function Load($bForceLoad = false)
    {
        $bResult = false;
        if ($this->bIsLoaded && !$bForceLoad) {
            $bResult = true;
        } else {
            $mData = false;

            if (\file_exists($this->sPath)) {
                $mData = $this->LoadDataFromFile($this->sPath);
            }

            if (!$mData) {
                $mData = $this->LoadDataFromBackup();
            }

            if ($mData) {
                $aParsedData = $this->ParseData($mData);
                foreach ($aParsedData as $key => $val) {
                    $val->IsDefault = false;
                    if (isset($this->aContainer[$key])) {
                        $val->Description = $this->aContainer[$key]->Description;
                    }
                    $this->aContainer[$key] = $val;
                }
                $bResult = true;
            } else {
                $bResult = $this->Save();
            }

            $this->bIsLoaded = true;
        }

        return $bResult;
    }

    /**
     * @return array
     */
    public function GetData()
    {
        $aResult = [];
        foreach ($this->aContainer as $sKey => $mValue) {
            $aValue = [];
            $value = $mValue->Value;
            if ($mValue->Type === 'spec') {
                $value = $this->specBackConver($mValue->Value, $mValue->SpecType);
                $aValue[] = $mValue->SpecType;
            } else {
                $aValue[] = null;
            }
            \array_unshift(
                $aValue,
                $value,
                $mValue->Type
            );
            $aValue[] = $mValue->Description;

            $aResult[$sKey] = $aValue;
        }

        return $aResult;
    }

    /**
     * @param bool $bBackupConfigFile
     *
     * @return bool
     */
    public function Save($bBackupConfigFile = true)
    {
        $bResult = false;
        $aData = $this->GetData();
        if (count($aData) > 0) {
            if ($this->CheckConfigFile()) {
                if ($bBackupConfigFile) {
                    $this->BackupConfigFile();
                }
                if ($this->SaveDataToConfigFile($aData)) {
                    $bResult = true;
                } else {
                    throw new \Aurora\System\Exceptions\SettingsException('Can\'t write settings to the configuration file');
                }
            }
        }

        return $bResult;
    }

    /**
     * @param string $sValue
     * @param string $sEnumName
     *
     * @return string
     */
    protected function specBackConver($sValue, $sEnumName)
    {
        $mResult = $sValue;
        if (null !== $sEnumName) {
            $mResult = Enums\EnumConvert::ToXml($sValue, $sEnumName);
        }

        return $mResult;
    }

    /**
     * @param string $sValue
     * @param string $sEnumName
     *
     * @return string
     */
    protected function specValidate($sValue, $sEnumName)
    {
        $mResult = null;
        if (null !== $sEnumName) {
            $mResult = Enums\EnumConvert::validate($sValue, $sEnumName);
        }
        return $mResult;
    }

    /**
     * @param string $sValue
     * @param string $sEnumName
     *
     * @return string
     */
    protected function specConver($sValue, $sEnumName)
    {
        $mResult = null;
        if (null !== $sEnumName) {
            $mResult = Enums\EnumConvert::FromXml($sValue, $sEnumName);
        }

        return $this->specValidate($mResult, $sEnumName);
    }

    /**
     * @return void
     */
    protected function init() {}
}
