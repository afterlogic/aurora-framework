<?php

class DavModule extends AApiModule
{
	public $oApiDavManager = null;
	
	public function init() 
	{
		parent::init();
		$this->oApiDavManager = $this->GetManager('main');
		$this->AddEntry('dav', 'EntryDav');
		
		$this->subscribeEvent('Calendar::GetCalendars::after', array($this, 'onAfterGetCalendars'));
	}
	
	public function EntryDav()
	{
		set_error_handler(function ($errno, $errstr, $errfile, $errline ) {
			
			throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
		});

		@set_time_limit(3000);

		$sBaseUri = '/';
		$oHttp = \MailSo\Base\Http::NewInstance();
		if (false !== \strpos($oHttp->GetUrl(), 'index.php/dav/')) {
			
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
		return $this->oApiDavManager->getServerUrl(
			$this->getParamValue('Account', null)
		);
	}
	
	public function GetServerHost()
	{
		return $this->oApiDavManager->getServerHost(
			$this->getParamValue('Account', null)
		);
	}
	
	public function GetServerPort()
	{
		return $this->oApiDavManager->getServerPort(
			$this->getParamValue('Account', null)
		);
	}
	
	public function GetPrincipalUrl()
	{
		return $this->oApiDavManager->getPrincipalUrl(
			$this->getParamValue('Account', null)
		);
	}


	public function IsUseSsl()
	{
		return $this->oApiDavManager->IsUseSsl(
			$this->getParamValue('Account', null)
		);
	}
	
	public function GetLogin()
	{
		return $this->oApiDavManager->getLogin(
			$this->getParamValue('Account', null)
		);
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
		return $this->oApiDavManager->testConnection(
			$this->getParamValue('Account', null)
		);
	}	
	
	public function DeletePrincipal()
	{
		return $this->oApiDavManager->deletePrincipal(
			$this->getParamValue('Account', null)
		);
	}	
	
	public function GetVCardObject()
	{
		return $this->oApiDavManager->getVCardObject(
			$this->getParamValue('Data', '')
		);
	}	
	
	public function GetPublicUser()
	{
		return \Afterlogic\DAV\Constants::DAV_PUBLIC_PRINCIPAL;
	}
	
	public function onAfterGetCalendars(&$aParameters)
	{
		if (isset($aParameters['@Result']) && $aParameters['@Result'] !== false) {
			
			$aParameters['@Result']['ServerUrl'] = $this->GetServerUrl();
		}
	}
	
	public function Login($Login, $Password)
	{
		$mResult = false;
		$this->broadcastEvent('Login', array(
			'login' => $Login,
			'password' => $Password,
			'result' => &$mResult)
		);		
		
		return ($mResult !== false && isset($mResult['id'])) ? $mResult['id'] : false;
	}
}

return new DavModule('1.0');
