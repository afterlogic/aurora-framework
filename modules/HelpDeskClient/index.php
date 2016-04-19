<?php

class HelpDeskModuleClient extends AApiModule
{
	public $oApiHelpDeskManager = null;
	
	public $oCoreDecorator = null;
	
	public function init() 
	{
		$this->oApiHelpDeskManager = $this->GetManager('main', 'db');

		$this->AddEntry('helpdesk', 'EntryHelpDesk');
		
		$this->oCoreDecorator = \CApi::GetModuleDecorator('Core');
		$this->oHelpDeskDecorator = \CApi::GetModuleDecorator('HelpDesk');
				
		$this->setObjectMap('CUser', array(
				'IsAgent'	=> array('bool', true)
			)
		);
	}
	
	public function EntryHelpDesk()
	{
//		$oLogginedAccount = $this->GetDefaultAccount();
		$oLogginedAccount = $this->oHelpDeskDecorator->getHelpdeskAccountFromParam();

		$oApiIntegrator = \CApi::GetCoreManager('integrator');
		
//		$oApiTenants = \CApi::GetCoreManager('tenants');
//		$mHelpdeskIdTenant = $oApiTenants->getTenantIdByHash($this->oHttp->GetQuery('helpdesk'));
		$mHelpdeskIdTenant = $this->oCoreDecorator->GetTenantIdByHash($this->oHttp->GetQuery('helpdesk'));
		
		if (!is_int($mHelpdeskIdTenant))
		{
			\CApi::Location('./');
			return '';
		}

		$bDoId = false;
		$sThread = $this->oHttp->GetQuery('thread');
		$sThreadAction = $this->oHttp->GetQuery('action');
		if (0 < strlen($sThread))
		{
			$iThreadID = $this->oApiHelpDeskManager->getThreadIdByHash($mHelpdeskIdTenant, $sThread);
			if (0 < $iThreadID)
			{
				$oApiIntegrator->setThreadIdFromRequest($iThreadID, $sThreadAction);
				$bDoId = true;
			}
		}

		$sActivateHash = $this->oHttp->GetQuery('activate');
		if (0 < strlen($sActivateHash) && !$this->oHttp->HasQuery('forgot'))
		{
			$bRemove = true;
			$oUser = $this->oApiHelpDeskManager->getUserByActivateHash($mHelpdeskIdTenant, $sActivateHash);
			/* @var $oUser \CHelpdeskUser */
			if ($oUser)
			{
				if (!$oUser->Activated)
				{
					$oUser->Activated = true;
					$oUser->regenerateActivateHash();

					if ($this->oApiHelpDeskManager->updateUser($oUser))
					{
						$bRemove = false;
						$oApiIntegrator->setUserAsActivated($oUser);
					}
				}
			}

			if ($bRemove)
			{
				$oApiIntegrator->removeUserAsActivated();
			}
		}

	
		//TODO oApiCapabilityManager
//		if ($oLogginedAccount && $this->oApiCapabilityManager->isHelpdeskSupported($oLogginedAccount) && $oLogginedAccount->IdTenant === $mHelpdeskIdTenant)
		if ($oLogginedAccount && $oLogginedAccount->IdTenant === $mHelpdeskIdTenant)
		{
			if (!$bDoId)
			{
				$oApiIntegrator->setThreadIdFromRequest(0);
			}

			$oApiIntegrator->skipMobileCheck();
			\CApi::Location('./');
			return '';
		}
		
//		var_dump($this->GetPath().'/templates/Login.html');
		
		$oCoreModule = \CApi::GetModule('Core');
		if ($oCoreModule instanceof \AApiModule) {
			$sResult = file_get_contents($oCoreModule->GetPath().'/templates/Index.html');
		}
		
//		$sResult = file_get_contents($this->GetPath().'/templates/Login.html');
		if (is_string($sResult)) {
//			$sFrameOptions = \CApi::GetConf('labs.x-frame-options', '');
//			if (0 < \strlen($sFrameOptions)) {
//				@\header('X-Frame-Options: '.$sFrameOptions);
//			}
			
			list($sLanguage, $sTheme, $sSiteName) = $oApiIntegrator->getThemeAndLanguage();
			
			$sHelpdeskHash = $this->oHttp->GetQuery('helpdesk', '');

//			$sAuthToken = isset($_COOKIE[self::AUTH_TOKEN_KEY]) ? $_COOKIE[self::AUTH_TOKEN_KEY] : '';
			$sResult = strtr($sResult, array(
				'{{AppVersion}}' => PSEVEN_APP_VERSION,
//				'{{IntegratorDir}}' => $oApiIntegrator->isRtl($sAuthToken) ? 'rtl' : 'ltr',
				'{{IntegratorDir}}' => 'ltr',
				'{{IntegratorLinks}}' => $oApiIntegrator->buildHeadersLink($sAuthToken, $sHelpdeskHash),
//				'{{IntegratorLinks}}' => '',
//				'{{IntegratorBody}}' => $oApiIntegrator->buildBody($sAuthToken)
				'{{IntegratorBody}}' => '<div class="auroraMain">
					<div id="auroraContent">
						<div class="screens"></div>
						<div class="popups"></div>
					</div>
					<div id="pSevenHidden"></div>'.
				'<div>'.
				$oApiIntegrator->compileTemplates().
				$oApiIntegrator->compileLanguage($sLanguage).
				$oApiIntegrator->compileAppData($sHelpdeskHash, '', '', $sAccessToken).
				'<script src="./static/js/app-helpdesk.js?'.CApi::VersionJs().'"></script>'.
					(CApi::Plugin()->HasJsFiles() ? '<script src="?/Plugins/js/'.CApi::Plugin()->Hash().'/"></script>' : '').
				'</div></div>'."\r\n".'<!-- '.CApi::Version().' -->'
			));
		}
		
		return $sResult;
	}
}

return new HelpDeskModuleClient('1.0');
