<?php

/* -AFTERLOGIC LICENSE HEADER- */

/**
 * @property int $IdTenant
 * @property int $IdChannel
 * @property bool $IsDisabled
 * @property bool $IsEnableAdminPanelLogin
 * @property bool $IsDefault
 * @property string $Login
 * @property string $Email
 * @property string $PasswordHash
 * @property string $Description
 * @property int $QuotaInMB
 * @property int $AllocatedSpaceInMB
 * @property string $FilesUsageInBytes
 * @property int $FilesUsageInMB
 * @property int $FilesUsageDynamicQuotaInMB
 * @property int $UserCountLimit
 * @property int $DomainCountLimit
 * @property string $Capa
 * @property int $Expared
 * @property string $PayUrl
 * @property bool $IsTrial
 * @property bool $AllowChangeAdminEmail
 * @property bool $AllowChangeAdminPassword
 *
 * @property string $HelpdeskAdminEmailAccount
 * @property string $HelpdeskClientIframeUrl
 * @property string $HelpdeskAgentIframeUrl
 * @property string $HelpdeskSiteName
 * @property string $HelpdeskStyleAllow
 * @property string $HelpdeskStyleImage
 * @property int $HelpdeskFetcherType
 * @property bool $HelpdeskAllowFetcher
 * @property int $HelpdeskFetcherTimer
 * 
 * @property string $LoginStyleImage
 * @property string $AppStyleImage
 * 
 * @property bool $SipAllow
 * @property bool $SipAllowConfiguration
 * @property string $SipRealm
 * @property string $SipWebsocketProxyUrl
 * @property string $SipOutboundProxyUrl
 * @property string $SipCallerID
 * 
 * @property bool $TwilioAllow
 * @property bool $TwilioAllowConfiguration
 * @property string $TwilioAccountSID
 * @property string $TwilioAuthToken
 * @property string $TwilioAppSID
 *
 * @property array $Socials
 * 
 * @property string $CalendarNotificationEmailAccount
 * @property string $InviteNotificationEmailAccount
 *
 * @package Tenants
 * @subpackage Classes
 */
//class CTenant extends api_AContainer

class CTenant extends api_APropertyBag
{
	public function __construct($sModule)
	{
		parent::__construct(get_class($this), $sModule);

		$this->__USE_TRIM_IN_STRINGS__ = true;

		self::$aStaticMap = array(
			'IdTenant'					=> array('int', 0),
			'IdChannel'					=> array('int', 0),
			'IsDisabled'				=> array('bool', false),
			'IsDefault'					=> array('bool', false),
			'IsEnableAdminPanelLogin'	=> array('bool', false),
			'Login'						=> array('string', ''),
			'Email'						=> array('string', ''),
			'PasswordHash'				=> array('string', ''),
			'Description'				=> array('string', ''),
			'AllocatedSpaceInMB'		=> array('int', 0),
			'FilesUsageInMB'			=> array('int', 0),
			'FilesUsageDynamicQuotaInMB'=> array('int', 0),
			'FilesUsageInBytes'			=> array('string', '0'),
			'QuotaInMB'					=> array('int', 0),
			'UserCountLimit'			=> array('int', 0),
			'DomainCountLimit'			=> array('int', 0),
			'Capa'						=> array('string', '', false), //(string) $oSettings->GetConf('Common/TenantGlobalCapa')
			
			'AllowChangeAdminEmail'		=> array('bool', true),
			'AllowChangeAdminPassword'	=> array('bool', true),

			'Expared'					=> array('int', 0),
			'PayUrl'					=> array('string', ''),
			'IsTrial'					=> array('bool', false),

			'HelpdeskAdminEmailAccount'	=> array('string', ''),
			'HelpdeskClientIframeUrl'	=> array('string', ''),
			'HelpdeskAgentIframeUrl'	=> array('string', ''),
			'HelpdeskSiteName'			=> array('string', ''),
			'HelpdeskStyleAllow'		=> array('bool', false),
			'HelpdeskStyleImage'		=> array('string', ''),
			'HelpdeskStyleText'			=> array('string', ''),

			'LoginStyleImage'			=> array('string', ''),
			'AppStyleImage'				=> array('string', ''),

			'HelpdeskFacebookAllow'		=> array('bool', false, false), //!!$oSettings->GetConf('Helpdesk/FacebookAllow')
			'HelpdeskFacebookId'		=> array('string', '', false), //(string) $oSettings->GetConf('Helpdesk/FacebookId')
			'HelpdeskFacebookSecret'	=> array('string', '', false), //(string) $oSettings->GetConf('Helpdesk/FacebookSecret')
			'HelpdeskGoogleAllow'		=> array('bool', false, false), //!!$oSettings->GetConf('Helpdesk/GoogleAllow')
			'HelpdeskGoogleId'			=> array('string', '', false), //(string) $oSettings->GetConf('Helpdesk/GoogleId')
			'HelpdeskGoogleSecret'		=> array('string', '', false), //(string) $oSettings->GetConf('Helpdesk/GoogleSecret')
			'HelpdeskTwitterAllow'		=> array('bool', false, false), //!!$oSettings->GetConf('Helpdesk/TwitterAllow')
			'HelpdeskTwitterId'			=> array('string', '', false), //(string) $oSettings->GetConf('Helpdesk/TwitterId')
			'HelpdeskTwitterSecret'		=> array('string', '', false), //(string) $oSettings->GetConf('Helpdesk/TwitterSecret')
			'HelpdeskAllowFetcher'		=> array('bool', false),
			'HelpdeskFetcherType'		=> array('int', EHelpdeskFetcherType::NONE),
			'HelpdeskFetcherTimer'		=> array('int', 0),

			'SipAllow'					=> array('bool', false, false), //!!$oSettings->GetConf('Sip/AllowSip')
			'SipAllowConfiguration'		=> array('bool', false),
			'SipRealm'					=> array('string', '', false), //, (string) $oSettings->GetConf('Sip/Realm')
			'SipWebsocketProxyUrl'		=> array('string', '', false), //, (string) $oSettings->GetConf('Sip/WebsocketProxyUrl')
			'SipOutboundProxyUrl'		=> array('string', '', false), //, (string) $oSettings->GetConf('Sip/OutboundProxyUrl')
			'SipCallerID'				=> array('string', '', false), //, (string) $oSettings->GetConf('Sip/CallerID')
			
			'TwilioAllow'				=> array('bool', false, false), //, !!$oSettings->GetConf('Twilio/AllowTwilio')
			'TwilioAllowConfiguration'	=> array('bool', false),
			'TwilioPhoneNumber'			=> array('string', '', false), //, (string) $oSettings->GetConf('Twilio/PhoneNumber')
			'TwilioAccountSID'			=> array('string', '', false), //, (string) $oSettings->GetConf('Twilio/AccountSID')
			'TwilioAuthToken'			=> array('string', '', false), //(string) $oSettings->GetConf('Twilio/AuthToken')
			'TwilioAppSID'				=> array('string', '', false), //(string) $oSettings->GetConf('Twilio/AppSID')
			
//			'Socials'					=> array('array', array(), false), //$this->getDefaultSocials()
			'CalendarNotificationEmailAccount'	=> array('string', ''),
			'InviteNotificationEmailAccount'	=> array('string', '')
		);

		$this->SetDefaults();
		
		$this->setInheritedSettings();
		
	}
	
	/**
	 * temp method
	 */
	public function setInheritedSettings()
	{
		$oSettings =& CApi::GetSettings();
		$oMap = $this->getStaticMap();
		
		if (isset($oMap['Capa'][2]) && !$oMap['Capa'][2])
		{
			$this->Capa = (string) $oSettings->GetConf('Common/TenantGlobalCapa');
		}
		
		if (isset($oMap['HelpdeskFacebookAllow'][2]) && !$oMap['HelpdeskFacebookAllow'][2])
		{
			$this->HelpdeskFacebookAllow = !!$oSettings->GetConf('Helpdesk/FacebookAllow');
		}
		
		if (isset($oMap['HelpdeskFacebookId'][2]) && !$oMap['HelpdeskFacebookId'][2])
		{
			$this->HelpdeskFacebookId = (string) $oSettings->GetConf('Helpdesk/FacebookId');
		}
		
		if (isset($oMap['HelpdeskFacebookSecret'][2]) && !$oMap['HelpdeskFacebookSecret'][2])
		{
			$this->HelpdeskFacebookSecret = (string) $oSettings->GetConf('Helpdesk/FacebookSecret');
		}
		
		if (isset($oMap['HelpdeskGoogleAllow'][2]) && !$oMap['HelpdeskGoogleAllow'][2])
		{
			$this->HelpdeskGoogleAllow = !!$oSettings->GetConf('Helpdesk/GoogleAllow');
		}
		
		if (isset($oMap['HelpdeskGoogleId'][2]) && !$oMap['HelpdeskGoogleId'][2])
		{
			$this->HelpdeskGoogleId = (string) $oSettings->GetConf('Helpdesk/GoogleId');
		}
		
		if (isset($oMap['HelpdeskGoogleSecret'][2]) && !$oMap['HelpdeskGoogleSecret'][2])
		{
			$this->HelpdeskGoogleSecret = (string) $oSettings->GetConf('Helpdesk/GoogleSecret');
		}
		
		if (isset($oMap['HelpdeskTwitterAllow'][2]) && !$oMap['HelpdeskTwitterAllow'][2])
		{
			$this->HelpdeskTwitterAllow = !!$oSettings->GetConf('Helpdesk/TwitterAllow');
		}
		
		if (isset($oMap['HelpdeskTwitterId'][2]) && !$oMap['HelpdeskTwitterId'][2])
		{
			$this->HelpdeskTwitterId = (string) $oSettings->GetConf('Helpdesk/TwitterId');
		}
		
		if (isset($oMap['HelpdeskTwitterSecret'][2]) && !$oMap['HelpdeskTwitterSecret'][2])
		{
			$this->HelpdeskTwitterSecret = (string) $oSettings->GetConf('Helpdesk/TwitterSecret');
		}
		
		if (isset($oMap['SipAllow'][2]) && !$oMap['SipAllow'][2])
		{
			$this->SipAllow = !!$oSettings->GetConf('Sip/AllowSip');
		}
		
		if (isset($oMap['SipRealm'][2]) && !$oMap['SipRealm'][2])
		{
			$this->SipRealm = (string) $oSettings->GetConf('Sip/Realm');
		}
		
		if (isset($oMap['SipWebsocketProxyUrl'][2]) && !$oMap['SipWebsocketProxyUrl'][2])
		{
			$this->SipWebsocketProxyUrl = (string) $oSettings->GetConf('Sip/WebsocketProxyUrl');
		}
		
		if (isset($oMap['SipOutboundProxyUrl'][2]) && !$oMap['SipOutboundProxyUrl'][2])
		{
			$this->SipOutboundProxyUrl = (string) $oSettings->GetConf('Sip/OutboundProxyUrl');
		}
		
		if (isset($oMap['SipCallerID'][2]) && !$oMap['SipCallerID'][2])
		{
			$this->SipCallerID = (string) $oSettings->GetConf('Sip/CallerID');
		}
		
		if (isset($oMap['TwilioAllow'][2]) && !$oMap['TwilioAllow'][2])
		{
			$this->TwilioAllow = !!$oSettings->GetConf('Twilio/AllowTwilio');
		}
		
		if (isset($oMap['TwilioPhoneNumber'][2]) && !$oMap['TwilioPhoneNumber'][2])
		{
			$this->TwilioPhoneNumber = (string) $oSettings->GetConf('Twilio/PhoneNumber');
		}
		
		if (isset($oMap['TwilioAccountSID'][2]) && !$oMap['TwilioAccountSID'][2])
		{
			$this->TwilioAccountSID = (string) $oSettings->GetConf('Twilio/AccountSID');
		}
		
		if (isset($oMap['TwilioAuthToken'][2]) && !$oMap['TwilioAuthToken'][2])
		{
			$this->TwilioAuthToken = (string) $oSettings->GetConf('Twilio/AuthToken');
		}
		
		if (isset($oMap['TwilioAppSID'][2]) && !$oMap['TwilioAppSID'][2])
		{
			$this->TwilioAppSID = (string) $oSettings->GetConf('Twilio/AppSID');
		}
		
//		if (isset($oMap['Socials'][2]) && !$oMap['Socials'][2])
//		{
//			$this->Socials = $this->getDefaultSocials();
//		}
	}
	
	public static function createInstance($sModule = 'Core')
	{
		return new CTenant($sModule);
	}
	
	/**
	 * @return bool
	 */
	public function isFilesSupported()
	{
		if (!CApi::GetConf('capa', false))
		{
			return true;
		}

		return '' === $this->Capa || false !== strpos($this->Capa, ETenantCapa::FILES);
	}

	/**
	 * @return bool
	 */
	public function isHelpdeskSupported()
	{
		if (!CApi::GetConf('capa', false))
		{
			return true;
		}

		return '' === $this->Capa || false !== strpos($this->Capa, ETenantCapa::HELPDESK);
	}

	/**
	 * @return bool
	 */
	public function isSipSupported()
	{
		if (!CApi::GetConf('capa', false))
		{
			return true;
		}

		return '' === $this->Capa || false !== strpos($this->Capa, ETenantCapa::SIP);
	}

	/**
	 * @return bool
	 */
	public function isTwilioSupported()
	{
		if (!CApi::GetConf('capa', false))
		{
			return true;
		}

		return '' === $this->Capa || false !== strpos($this->Capa, ETenantCapa::TWILIO);
	}
	
	
	/**
	 * @param string $sPassword
	 *
	 * @return string
	 */
	public static function hashPassword($sPassword)
	{
		return empty($sPassword) ? '' : md5('Awm'.md5($sPassword.'Awm'));
	}

	/**
	 * @param string $sPassword
	 *
	 * @return bool
	 */
	public function validatePassword($sPassword)
	{
		return self::hashPassword($sPassword) === $this->PasswordHash;
	}

	/**
	 * @param string $sPassword
	 */
	public function setPassword($sPassword)
	{
		$this->PasswordHash = self::hashPassword($sPassword);
	}

	public function getUserCount()
	{
		$oUsersApi = CApi::GetCoreManager('users');
		return $oUsersApi->getUsersCountForTenant($this->iObjectId);
	}

	public function getDomainCount()
	{
		$oDomainsApi = CApi::GetCoreManager('domains');
		return $oDomainsApi->getDomainCount('', $this->iObjectId);
	}

	/**
	 * @return bool
	 *
	 * @throws CApiValidationException(Errs::Validation_InvalidTenantName) 1109
	 * @throws CApiValidationException(Errs::Validation_FieldIsEmpty) 1102
	 * @throws CApiValidationException(Errs::Validation_InvalidEmail) 1107
	 *
	 * @return true
	 */
	public function validate()
	{
		if (!$this->IsDefault)
		{
			switch (true)
			{
				case !api_Validate::IsValidTenantLogin($this->Login):
					throw new CApiValidationException(Errs::Validation_InvalidTenantName);
				case api_Validate::IsEmpty($this->Login):
					throw new CApiValidationException(Errs::Validation_FieldIsEmpty, null, array(
						'{{ClassName}}' => 'CTenant', '{{ClassField}}' => 'Login'));
				case !api_Validate::IsEmpty($this->Email) && !preg_match('/^[^@]+@[^@]+$/', $this->Email):
					throw new CApiValidationException(Errs::Validation_InvalidEmail, null, array(
						'{{ClassName}}' => 'CTenant', '{{ClassField}}' => 'Email'));
			}
		}

		return true;
	}
	
	/**
	 * @return string
	 */
	public function getHelpdeskStyleText()
	{
		return '' !== $this->HelpdeskStyleText ? base64_decode($this->HelpdeskStyleText) : '';
	}

	/**
	 * @param string $sStyle
	 */
	public function setHelpdeskStyleText($sStyle)
	{
		$sStyle = trim($sStyle);
		$this->HelpdeskStyleText = ('' !== $sStyle) ? base64_encode($sStyle) : '';
	}

	/**
	 * @return array
	 */
	public function getDefaultSocials()
	{
		$aResult = array();
		$oSettings =& CApi::GetSettings();
		$aSocials = $oSettings->GetConf('Socials');
		if (isset($aSocials) && is_array($aSocials))
		{
			$oPlugin = \CApi::Plugin()->GetPluginByName('external-services');
			if ($oPlugin)
			{
				$aConnectors = $oPlugin->GetEnabledConnectors();
				foreach ($aSocials as $sKey => $aSocial)
				{
					if (in_array(strtolower($sKey), $aConnectors))
					{
						$oTenantSocial = CTenantSocials::initFromSettings($aSocial);
						if ($oTenantSocial !== null)
						{
							$aResult[strtolower($sKey)] = $oTenantSocial;
						}
					}
				}
			}
		}
		
		return $aResult;
	}
	
	/**
	 * @return array
	 */
	public function getSocialByName($sName)
	{
		return isset($this->Socials[strtolower($sName)]) ? $this->Socials[strtolower($sName)] : null;
	}

	/**
	 * @return array
	 */
	public function getSocials()
	{
		$aSocials = array();
		if ($this->iObjectId > 0 && count($this->Socials) === 0)
		{
			foreach ($this->getDefaultSocials() as $sKey => $oTenantSocial)
			{
				$sSocialApiKey = $oTenantSocial->SocialApiKey !== null ? '' : null;
				$oTenantSocial = new CTenantSocials();
				$oTenantSocial->IdTenant = $this->iObjectId;
				$oTenantSocial->SocialName = ucfirst($sKey);
				$oTenantSocial->SocialApiKey = $sSocialApiKey;
				$aSocials[strtolower($sKey)] = $oTenantSocial;
			}
		}
		else 
		{
			$aSocials = $this->Socials;
			foreach ($this->getDefaultSocials() as $sKey => $oTenantSocial)
			{
				if (!isset($aSocials[strtolower($sKey)]))
				{
					$sSocialApiKey = $oTenantSocial->SocialApiKey !== null ? '' : null;
					$oTenantSocial = new CTenantSocials();
					$oTenantSocial->IdTenant = $this->iObjectId;
					$oTenantSocial->SocialName = ucfirst($sKey);
					$oTenantSocial->SocialApiKey = $sSocialApiKey;
					$aSocials[strtolower($sKey)] = $oTenantSocial;
				}
			}			
		}
		$this->Socials = $aSocials;
		return $this->Socials;
	}
	
	/**
	 * @return array
	 */
	public function getSocialsForSettings()
	{
		$aSettingsSocials = array();
		foreach ($this->Socials as $sKey => $oSocial)
		{
			if (is_array($oSocial))
			{
				$aSettingsSocials[ucfirst($sKey)] = $oSocial;
			}
			else if ($oSocial instanceof CTenantSocials)
			{
				$aSettingsSocials[ucfirst($sKey)] = $oSocial->initForSettings();
			}
		}
		return $aSettingsSocials;
	}
	
	/**
	 * @param array $aSocials
	 */
	public function setSocials($aSocials)
	{
/*
		if ($this->IdTenant === 0)
		{
			$this->Socials = $this->getSocialsForSettings();
		}
		else
		{
 */
			$this->Socials = $aSocials;
/*
		}
 */
	}
}
