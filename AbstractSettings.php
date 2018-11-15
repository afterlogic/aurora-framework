<?php
/*
 * This code is licensed under AGPLv3 license or Afterlogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

namespace Aurora\System;

/**
 * @license https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @license https://afterlogic.com/products/common-licensing AfterLogic Software License
 * @copyright Copyright (c) 2018, Afterlogic Corp.
 */
abstract class AbstractSettings
{
	const JSON_FILE_NAME = 'config.json';

	#<editor-fold defaultstate="collapsed" desc="protected">
	/**
	 * @var array
	 */
	protected $aContainer;

	/**
	 * @var string
	 */
	protected $sPath;
	#</editor-fold>	
	
	/**
	 * @param string $sSettingsPath
	 *
	 * @return AbstractSettings
	 */
	public function __construct($sSettingsPath)
	{
		$this->aContainer = array();
		$this->sPath = $sSettingsPath;

		if (!$this->Load($this->sPath))
		{
			if ($this->Load($this->sPath.'.bak'))
			{
				copy($this->sPath.'.bak', $this->sPath);
			}
			else
			{
				$this->Save();
			}
		}
	}
	
	/**
	 * 
	 * @param type $sName
	 * @param type $mValue
	 */
	public function __set($sName, $mValue) 
	{
		$this->SetConf($sName, $mValue);
	}

	/**
	 * 
	 * @param type $sName
	 */
	public function __get($sName) 
	{
		$this->GetConf($sName);
	}

	/**
	 * @return array
	 */
	public function GetConfigValues()
	{
		return $this->aContainer;
	}
	
	/**
	 * 
	 * @return string
	 */
	public function GetConfigPath()
	{
		return $this->sPath;
	}

	/**
	 * @param array $aValues
	 */
	public function SetConfigValues($aValues)
	{
		$this->aContainer = $aValues;
	}

	/**
	 * @param string $sKey
	 *
	 * @return mixed
	 */
	public function GetConf($sKey, $mDefault = null)
	{
		return (isset($this->aContainer[$sKey])) ? $this->aContainer[$sKey]->Value : $mDefault;
	}
	
	/**
	 * @param string $sKey
	 * @param mixed $mValue = null
	 *
	 * @return bool
	 */
	public function SetConf($sKey, $mValue)
	{
		$bResult = false;

		$sType = (isset($this->aContainer[$sKey])) ? $this->aContainer[$sKey]->Type : gettype($mValue);
		if (!isset($this->aContainer[$sKey]))
		{
			$this->aContainer[$sKey] = new SettingsProperty($sKey, $mValue, $sType);
		}
		
		switch ($sType)
		{
			default:
				$this->aContainer[$sKey]->Value = null;
				break;
			case 'string':
				$this->aContainer[$sKey]->Value = (string) $mValue;
				break;
			case 'int':
			case 'integer':
				$this->aContainer[$sKey]->Value = (int) $mValue;
				break;
			case 'bool':
			case 'boolean':
				$this->aContainer[$sKey]->Value = (bool) $mValue;
				break;
			case 'spec':
				$this->aContainer[$sKey]->Value = $this->specValidate($mValue, $this->aContainer[$sKey]->SpecType);
				break;
			case 'array':
				$this->aContainer[$sKey]->Value = $mValue;
				break;
		}

		return $bResult;
	}
	
	protected function Populate($aData)
	{
		$bResult = false;
		
		if (is_array($aData))
		{
			foreach ($aData as $sKey => $mValue)
			{
				$sSpecType = null;
				if (is_array($mValue))
				{
					$sType = isset($mValue[1]) ? $mValue[1] : (isset($mValue[0]) ? gettype($mValue[0]) : "string");
					$sSpecType = isset($mValue[2]) ? $mValue[2] : null;
					$mValue = isset($mValue[0]) ? $mValue[0] : "";
				}
				else
				{
					$sType = gettype($mValue);
				}

				switch ($sType)
				{
					case 'string':
						$mValue =(string) $mValue;
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
				if (null !== $mValue)
				{
					$this->aContainer[$sKey] = new SettingsProperty($sKey, $mValue, $sType, $sSpecType);
				}
			}
			$bResult = true;
		}	
		
		return $bResult;
	}

	/**
	 * @param string $sJsonFile
	 *
	 * @return bool
	 */
	public function Load($sJsonFile)
	{
		$bResult = false;
		
		if (\file_exists($sJsonFile))
		{
			$sJsonData = \file_get_contents($sJsonFile);
			$aData = \json_decode($sJsonData, true);
			$bResult = $this->Populate($aData);
		}
		
		return $bResult;
	}

	/**
	 * @return bool
	 */
	public function Save()
	{
		$bResult = false;
		$aConvertedContainer = array();
		foreach ($this->aContainer as $sKey => $mValue)
		{
			if ($mValue->Type === 'spec')
			{
				$mValue->Value = $this->specBackConver($mValue->Value, $mValue->SpecType);
			}
			$aValue = array(
				$mValue->Value, 
				$mValue->Type
			);
			if ($mValue->Type === 'spec')
			{
				$aValue[] = $mValue->SpecType;
			}
			$aConvertedContainer[$sKey] = $aValue;
		}
		if (count($aConvertedContainer) > 0)
		{
			// backup previous configuration
			$sJsonFile = $this->sPath;
			if (file_exists($sJsonFile))
			{
				copy($sJsonFile, $sJsonFile.'.bak');
			}
			if (!file_exists(dirname($sJsonFile)))
			{
				set_error_handler(function() {});					
				mkdir(dirname($sJsonFile), 0777);
				restore_error_handler();
				if (!file_exists(dirname($sJsonFile)))
				{
					return false;
				}
			}
			if ((bool) file_put_contents(
				$sJsonFile, 
				json_encode(
					$aConvertedContainer, 
					JSON_PRETTY_PRINT | JSON_OBJECT_AS_ARRAY
				)
			))
			{
				$bResult = true;
			}
			else
			{
				throw new \Aurora\System\Exceptions\SettingsException('Can\'t write settings to the configuration file: ' . $sJsonFile);
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
		if (null !== $sEnumName)
		{
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
		if (null !== $sEnumName)
		{
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
		if (null !== $sEnumName)
		{
			$mResult = Enums\EnumConvert::FromXml($sValue, $sEnumName);
		}

		return $this->specValidate($mResult, $sEnumName);
	}		

	/**
	 * @return void
	 */
	protected function init()
	{
		foreach ($this->aMap as $sKey => $aField)
		{
			$this->aLowerMap[strtolower($sKey)] = $aField;
			$this->SetConf($sKey, $aField[0]);
		}
	}
}
