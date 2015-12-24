<?php

class DavModule extends AApiModule
{
	public $oApiDavManager = null;
	
	public function init() 
	{
		parent::init();
		$this->oApiDavManager = $this->GetManager('main');
		$this->AddEntry('dav', 'EntryDav');
	}
	
	public function EntryDav()
	{
		set_error_handler(function ($errno, $errstr, $errfile, $errline ) 
		{
			throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
		});

		@set_time_limit(3000);

		$sBaseUri = '/';
		$oHttp = \MailSo\Base\Http::NewInstance();
		if (false !== \strpos($oHttp->GetUrl(), 'index.php/dav/'))
		{
			$aPath = \trim($oHttp->GetPath(), '/\\ ');
			$sBaseUri = (0 < \strlen($aPath) ? '/'.$aPath : '').'/index.php/dav/';
		}
		
		\Afterlogic\DAV\Server::getInstance($sBaseUri)->exec();
		return '';
	}	
	
	public function GetDavClient()
	{
		$mResult = false;
		$oAccount = $this->getParamValue('Account', null); 
		if ($oAccount && $oAccount instanceof CAccount)
		{
			$mResult = $this->oApiDavManager->GetDAVClient($oAccount);
		}
		return $mResult;
	}
	
	public function GetServerUrl()
	{
		$oAccount = $this->getParamValue('Account', null); 
		return $this->oApiDavManager->getServerUrl($oAccount);
	}
	
	public function GetServerHost()
	{
		$oAccount = $this->getParamValue('Account', null); 
		return $this->oApiDavManager->getServerHost($oAccount);
	}
	
	public function GetServerPort()
	{
		$oAccount = $this->getParamValue('Account', null); 
		return $this->oApiDavManager->getServerPort($oAccount);
	}
	
	public function GetPrincipalUrl()
	{
		$oAccount = $this->getParamValue('Account', null); 
		return $this->oApiDavManager->getPrincipalUrl($oAccount);
	}


	public function IsUseSsl()
	{
		$oAccount = $this->getParamValue('Account', null); 
		return $this->oApiDavManager->IsUseSsl($oAccount);
	}
	
	public function GetLogin()
	{
		$oAccount = $this->getParamValue('Account', null); 
		return $this->oApiDavManager->getLogin($oAccount);
	}
	
	public function IsMobileSyncEnabled()
	{
		return $this->oApiDavManager->isMobileSyncEnabled();
	}	
	
	public function SetMobileSyncEnable()
	{
		$bMobileSyncEnable = $this->getParamValue('MobileSyncEnable', false); 
		$oSettings =& CApi::GetSettings();
		$oSettings->SetConf('Common/EnableMobileSync', $bMobileSyncEnable);
		return (bool) $oSettings->SaveToXml();
	}	
	
	public function TestConnection()
	{
		$oAccount = $this->getParamValue('Account', null); 
		return $this->oApiDavManager->testConnection($oAccount);
	}	
	
	public function DeletePrincipal()
	{
		$oAccount = $this->getParamValue('Account', null); 
		return $this->oApiDavManager->deletePrincipal($oAccount);
	}	
	
	public function GetVCardObject()
	{
		$sData = $this->getParamValue('Data', ''); 
		return $this->oApiDavManager->getVCardObject($sData);
	}	
	
	public function GetPublicUser()
	{
		$sEmail = \Afterlogic\DAV\Constants::DAV_PUBLIC_PRINCIPAL;
		$aPrincipalsPath = \Afterlogic\DAV\Backend::Principal()->searchPrincipals(
				\Afterlogic\DAV\Constants::PRINCIPALS_PREFIX, 
				array(
					'{http://sabredav.org/ns}email-address' => $sEmail
				)
		);
		$aPrincipals = array_filter($aPrincipalsPath, function ($sPrincipalPath) use ($sEmail) {
			return ($sPrincipalPath === \Afterlogic\DAV\Constants::PRINCIPALS_PREFIX . '/' . $sEmail);
		});
		
		if (count($aPrincipals) === 0) {
			throw new \Exception("Unknown email address");
		}
		
		return \Afterlogic\DAV\Backend::Principal()->getPrincipalByPath($aPrincipals[0]);
				
	}
}

return new DavModule('1.0');
