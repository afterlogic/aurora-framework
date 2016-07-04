<?php

/* -AFTERLOGIC LICENSE HEADER- */

class CApiBasicSettings
{
	const JSON_FILE_NAME = 'config.json';

	#<editor-fold defaultstate="collapsed" desc="protected">
	/**
	 * @var array
	 */
	protected $aMap;

	/**
	 * @var array
	 */
	protected $aLowerMap;

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
	 * @return CApiSettings
	 */
	public function __construct($sSettingsPath, $aMap = array())
	{
		$this->aMap = $aMap;
		$this->aLowerMap = array();
		$this->aContainer = array();
		$this->sPath = $sSettingsPath;

		$this->init();
		
		if (!$this->Load($this->GetJsonPath()))
		{
			if ($this->Load($this->GetJsonPath().'.bak'))
			{
				copy($this->GetJsonPath().'.bak', $this->GetJsonPath());
			}
			else
			{
				$this->Save();
			}
		}
	}
	
	public function GetJsonPath()
	{
		return $this->sPath.self::JSON_FILE_NAME;
	}
	
	/**
	 * @param string $sKey
	 *
	 * @return mixed
	 */
	public function GetConf($sKey, $mDefault = null)
	{
		$mResult = null;
		$sLowerKey = strtolower($sKey);
		if (array_key_exists($sLowerKey, $this->aLowerMap))
		{
			$mResult = $this->aContainer[$sKey];
		}
		else 
		{
			$mResult = $mDefault;
		}

		return $mResult;
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
		$sLowerKey = strtolower($sKey);
		if (isset($this->aLowerMap[$sLowerKey]))
		{
			$aType = $this->aLowerMap[$sLowerKey];
			switch ($aType[1])
			{
				default:
					$mValue = null;
					break;
				case 'string':
					$mValue = (string) $mValue;
					break;
				case 'int':
					$mValue = (int) $mValue;
					break;
				case 'bool':
					$mValue = (bool) $mValue;
					break;
				case 'spec':
					$mValue = $this->specValidate($sKey, $mValue, isset($aType[2]) ? $aType[2] : null);
					break;
				case 'array':
					$mValue = $mValue;
					break;
			}

			if (null !== $mValue)
			{
				$bResult = true;
				$this->aContainer[$sKey] = $mValue;
			}
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
		if (file_exists($sJsonFile))
		{
			$sJsonData = file_get_contents($sJsonFile);
			$aJsonData = json_decode($sJsonData, true);
			foreach ($aJsonData as $sKey => $mValue)
			{
				$sLowerKey = strtolower($sKey);
				if (isset($this->aLowerMap[$sLowerKey]))
				{
					$aType = $this->aLowerMap[$sLowerKey];
					switch ($aType[1])
					{
						default:
							$mValue = null;
							break;
						case 'string':
							$mValue =(string) $mValue;
							break;
						case 'int':
							$mValue = (int) $mValue;
							break;
						case 'bool':
							$mValue = ('on' === strtolower($mValue) || '1' === (string) $mValue);
							break;
						case 'spec':
							$mValue = $this->specConver($mValue, isset($aType[2]) ? $aType[2] : null);
							break;
					}
				}
				if (null !== $mValue)
				{
					$this->aContainer[$sKey] = $mValue;
				}
			}
			
			$bResult = true;
		}
		
		return $bResult;
	}

	/**
	 * @return bool
	 */
	public function Save()
	{
		$bResult = true;
		$aConvertedContainer = array();
		foreach ($this->aContainer as $sKey => $mValue)
		{
			$mValue = null;
			$sLowerKey = strtolower($sKey);
			if (isset($this->aLowerMap[$sLowerKey]))
			{
				$mValue = $this->aContainer[$sKey];
				$aType = $this->aLowerMap[$sLowerKey];
				
				switch ($aType[1])
				{
					case 'string':
						$mValue = (string) $mValue;
						break;
					case 'int':
						$mValue = (int) $mValue;
						break;
					case 'bool':
						$mValue = ((bool) $mValue) ? 'On' : 'Off';
						break;
					case 'spec':
						$mValue = $this->specBackConver($mValue, isset($aTyp[2]) ? $aTyp[2] : null);
						break;
				}
			}			
			$aConvertedContainer[$sKey] = $mValue;
		}
		if (count($aConvertedContainer) > 0)
		{
			// backup previous configuration
			$sJsonFile = $this->GetJsonPath();
			if (file_exists($sJsonFile))
			{
				copy($sJsonFile, $sJsonFile.'.bak');
			}
			$bResult = (bool) file_put_contents($sJsonFile, json_encode($aConvertedContainer, JSON_PRETTY_PRINT));
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
			$mResult = EnumConvert::ToXml($sValue, $sEnumName);
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
			$mResult = EnumConvert::validate($sValue, $sEnumName);
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
			$mResult = EnumConvert::FromXml($sValue, $sEnumName);
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


/**
 * @package Api
 */
class CApiSettings extends CApiBasicSettings
{
	/**
	 * @return void
	 */
	public function __construct($sSettingsPath)
	{
		$aMap = array(
			'SiteName' => array('AfterLogic', 'string',
				'Default title that will be shown in browser\'s header (Default domain settings).'),

			'LicenseKey' => array('', 'string',
				'License key is supplied here.'),

			'AdminLogin' => array('mailadm', 'string'),
			'AdminPassword' => array('827ccb0eea8a706c4c34a16891f84e7b', 'string'),

			'DBType' => array(EDbType::MySQL, 'string'),
			'DBPrefix' => array('', 'string'),

			'DBHost' => array('127.0.0.1', 'string'),
			'DBName' => array('', 'string'),
			'DBLogin' => array('root', 'string'),
			'DBPassword' => array('', 'string'),

			'UseSlaveConnection' => array(false, 'bool'),
			'DBSlaveHost' => array('127.0.0.1', 'string'),
			'DBSlaveName' => array('', 'string'),
			'DBSlaveLogin' => array('root', 'string'),
			'DBSlavePassword' => array('', 'string'),

			'DefaultLanguage' => array('English', 'string'),
			'DefaultTimeZone' => array(0, 'int'), //TODO Magic
			'DefaultTimeFormat' => array(ETimeFormat::F12, 'spec', 'ETimeFormat'),
			'DefaultDateFormat' => array(EDateFormat::MMDDYYYY, 'spec', 'EDateFormat'),
			'AllowRegistration' => array(false, 'bool'),
			'RegistrationDomains' => array('', 'string'),
			'RegistrationQuestions' => array('', 'string'),
			'AllowPasswordReset' => array(false, 'bool'),
			'EnableLogging' => array(false, 'bool'),
			'EnableEventLogging' => array(false, 'bool'),
			'LoggingLevel' => array(ELogLevel::Full, 'spec', 'ELogLevel'),
			'EnableMobileSync' => array(false, 'bool'),

			'TenantGlobalCapa' => array('', 'string'),

			'LoginStyleImage' => array('', 'string'),
			'AppStyleImage' => array('', 'string'),
			'InvitationEmail' => array('', 'string'),
			
			'DefaultTab' => array('', 'string'),
			'RedirectToHttps' => array(false, 'bool'),

			'PasswordMinLength' => array(0, 'int'),
			'PasswordMustBeComplex' => array(false, 'bool'),
		);
		
		parent::__construct($sSettingsPath, $aMap);
	}
}

/**
 * @package Api
 */
class CApiSettingsException extends Exception {}
