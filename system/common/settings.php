<?php

/* -AFTERLOGIC LICENSE HEADER- */

/**
 * @package Api
 */
class api_Settings
{
	const JSON_FILE_NAME = '/settings/config.json';

	#<editor-fold defaultstate="collapsed" desc="protected">
	/**
	 * @var array
	 */
	protected $aMap;

	/**
	 * @var array
	 */
	protected $aObjectsMap;

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
	 * @return api_Settings
	 */
	public function __construct($sSettingsPath)
	{
		$this->aMap = array();
		$this->aLowerMap = array();
		$this->aObjectsMap = array();
		$this->aContainer = array();
		$this->sPath = $sSettingsPath;

		$this->initDefaultValues();
		
		if (!$this->Load($this->sPath.api_Settings::JSON_FILE_NAME))
		{
			if ($this->Load($this->sPath.api_Settings::JSON_FILE_NAME.'.bak'))
			{
				copy($this->sPath.api_Settings::JSON_FILE_NAME.'.bak', $this->sPath.api_Settings::JSON_FILE_NAME);
			}
			else
			{
				$this->Save();
			}
		}
	}

	/**
	 * @param string $sKey
	 *
	 * @return mixed
	 */
	public function GetConf($sKey)
	{
		$mResult = null;
		$sLowerKey = strtolower($sKey);
		if (array_key_exists($sLowerKey, $this->aLowerMap))
		{
			$mResult = $this->aContainer[$sKey];
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
					$mValue = $this->specValidate($sKey, $mValue);
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
			$this->aContainer = json_decode($sJsonData, true);
			$bResult = true;
		}
		
		return $bResult;
	}

	/**
	 * @return bool
	 */
	public function Save()
	{
		$aConvertedContainer = array();
		foreach ($this->aContainer as $sKey => $mValue)
		{
			$mValue = null;
			$sLowerKey = strtolower($sKey);
			if (isset($this->aLowerMap[$sLowerKey]))
			{
//				if (array_key_exists($sLowerKey, $this->aContainer))
//				{
					$mValue = $this->aContainer[$sKey];
//				}
//				else
//				{
//					$mValue = $this->aLowerMap[$sLowerKey][0];
//				}

				$aType = $this->aLowerMap[$sLowerKey];
				switch ($aType[1])
				{
					case 'string':
						$mValue = api_Utils::EncodeSpecialXmlChars((string) $mValue);
						break;
					case 'int':
						$mValue = (int) $mValue;
						break;
					case 'bool':
						$mValue = ((bool) $mValue) ? 'On' : 'Off';
						break;
					case 'spec':
						$mValue = $this->specBackConver($sKey, $mValue);
						break;
				}
			}			
			$aConvertedContainer[$sKey] = $mValue;
		}
		
		$sJsonData = json_encode($aConvertedContainer, JSON_PRETTY_PRINT);
		
		// save previous configuration
		$sJsonFile = $this->sPath.api_Settings::JSON_FILE_NAME;
		if (file_exists($sJsonFile))
		{
			copy($sJsonFile, $sJsonFile.'.bak');
		}
		return (bool) file_put_contents($sJsonFile, $sJsonData);
	}
	
	/**
	 * @param string $sKey
	 * @param string $sValue
	 *
	 * @return string
	 */
	protected function specBackConver($sKey, $sValue)
	{
		$mResult = $sValue;
		$sEnumName = $this->getEnumName($sKey);
		if (null !== $sEnumName)
		{
			$mResult = EnumConvert::ToXml($sValue, $sEnumName);
		}

		return $mResult;
	}	

	/**
	 * @staticvar array $aValues
	 * @param string $sKey
	 *
	 * @return string | null
	 */
	protected function getEnumName($sKey)
	{
		static $aValues = array(
			'dbtype'							=> 'EDbType',
			'defaulttimeformat'					=> 'ETimeFormat',
			'defaultdateformat'					=> 'EDateFormat',
			'logginglevel'						=> 'ELogLevel',
			'incomingmailprotocol'				=> 'EMailProtocol',
			'outgoingmailauth'					=> 'ESMTPAuthType',
			'outgoingsendingmethod'				=> 'ESendingMethod',
			'loginformtype'						=> 'ELoginFormType',
			'loginsignmetype'					=> 'ELoginSignMeType',
			'globaladdressbookvisibility'		=> 'EContactsGABVisibility',
			'weekstartson'						=> 'ECalendarWeekStartOn',
			'defaulttab'						=> 'ECalendarDefaultTab',
			'fetchertype'						=> 'EHelpdeskFetcherType',
		);

		$sLowerKey = strtolower($sKey);
		return isset($aValues[$sLowerKey]) ? $aValues[$sLowerKey] : null;
	}

	/**
	 * @param string $sKey
	 * @param string $sValue
	 *
	 * @return string
	 */
	protected function specValidate($sKey, $sValue)
	{
		$mResult = null;
		$sEnumName = $this->getEnumName($sKey);
		if (null !== $sEnumName)
		{
			$mResult = EnumConvert::validate($sValue, $sEnumName);
		}
		return $mResult;
	}

	/**
	 * @return void
	 */
	protected function initDefaultValues()
	{
		$this->aMap = array(
			'SiteName' => array('AfterLogic', 'string',
				'Default title that will be shown in browser\'s header (Default domain settings).'),

			'LicenseKey' => array('', 'string',
				'License key is supplied here.'),

			'AdminLogin' => array('mailadm', 'string'),
			'AdminPassword' => array('827ccb0eea8a706c4c34a16891f84e7b', 'string'),

			'DBType' => array(EDbType::MySQL, 'spec'),
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
			'DefaultTimeFormat' => array(ETimeFormat::F12, 'spec'),
			'DefaultDateFormat' => array(EDateFormat::MMDDYYYY, 'spec'),
			'AllowRegistration' => array(false, 'bool'),
			'RegistrationDomains' => array('', 'string'),
			'RegistrationQuestions' => array('', 'string'),
			'AllowPasswordReset' => array(false, 'bool'),
			'EnableLogging' => array(false, 'bool'),
			'EnableEventLogging' => array(false, 'bool'),
			'LoggingLevel' => array(ELogLevel::Full, 'spec'),
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
		foreach ($this->aMap as $sKey => $aField)
		{
			$this->aLowerMap[strtolower($sKey)] = $aField;
			$this->SetConf($sKey, $aField[0]);
		}
	
		$this->aObjectsMap = array(
			'Socials' => 'CTenantSocials'
		);
		foreach ($this->aObjectsMap as $sKey => $sClass)
		{
			$this->aLowerObjectsMap[strtolower($sKey)] = $sClass;
		}
	}
}

/**
 * @package Api
 */
class api_SettingsException extends Exception {}
