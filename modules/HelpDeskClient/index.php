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
		
		$oCoreModule = \CApi::GetModule('Core');
		if ($oCoreModule instanceof \AApiModule) {
			$sResult = file_get_contents($oCoreModule->GetPath().'/templates/Index.html');
		}
		
		if (is_string($sResult))
		{
			$sFrameOptions = \CApi::GetConf('labs.x-frame-options', '');
			if (0 < \strlen($sFrameOptions)) {
				@\header('X-Frame-Options: '.$sFrameOptions);
			}
			
//			$sHelpdeskHash = $this->oHttp->GetQuery('helpdesk', '');

			$sResult = strtr($sResult, array(
				'{{AppVersion}}' => PSEVEN_APP_VERSION,
				'{{IntegratorDir}}' =>  $oApiIntegrator->isRtl() ? 'rtl' : 'ltr',
				'{{IntegratorLinks}}' => $oApiIntegrator->buildHeadersLink('-helpdesk'),
				'{{IntegratorBody}}' => $oApiIntegrator->buildBody('-helpdesk')
			));
		}
		
		return $sResult;
	}
}

return new HelpDeskModuleClient('1.0');
