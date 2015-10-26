<?php

class CoreModule extends AApiModule
{
	/**
	 * @return array
	 */
	public function DoServerInitializations()
	{
		$oAccount = $this->getAccountFromParam();

		$bResult = false;

		$oApiIntegrator = \CApi::GetCoreManager('integrator');

		if ($oAccount && $oApiIntegrator)
		{
			$oApiIntegrator->resetCookies();
		}

		if ($this->oApiCapabilityManager->isGlobalContactsSupported($oAccount, true))
		{
			$bResult = \CApi::GetModuleManager()->Execute('Contacts', 'SynchronizeExternalContacts', array('Account' => $oAccount));
		}

		$oCacher = \CApi::Cacher();

		$bDoGC = false;
		$bDoHepdeskClear = false;
		if ($oCacher && $oCacher->IsInited())
		{
			$iTime = $oCacher->GetTimer('Cache/ClearFileCache');
			if (0 === $iTime || $iTime + 60 * 60 * 24 < time())
			{
				if ($oCacher->SetTimer('Cache/ClearFileCache'))
				{
					$bDoGC = true;
				}
			}

			if (\CApi::GetModuleManager()->ModuleExists('Helpdesk'))
			{
				$iTime = $oCacher->GetTimer('Cache/ClearHelpdeskUsers');
				if (0 === $iTime || $iTime + 60 * 60 * 24 < time())
				{
					if ($oCacher->SetTimer('Cache/ClearHelpdeskUsers'))
					{
						$bDoHepdeskClear = true;
					}
				}
			}
		}

		if ($bDoGC)
		{
			\CApi::Log('GC: FileCache / Start');
			$oApiFileCache = \Capi::GetCoreManager('filecache');
			$oApiFileCache->gc();
			$oCacher->gc();
			\CApi::Log('GC: FileCache / End');
		}

		if ($bDoHepdeskClear && \CApi::GetModuleManager()->ModuleExists('Helpdesk'))
		{
			\CApi::GetModuleManager()->ExecuteMethod('Helpdesk', 'ClearUnregistredUsers');
			\CApi::GetModuleManager()->ExecuteMethod('Helpdesk', 'ClearAllOnline');
		}

		return $this->DefaultResponse($oAccount, __FUNCTION__, $bResult);
	}
	
	/**
	 * @return array
	 */
	public function Noop()
	{
		return $this->TrueResponse(null, __FUNCTION__);
	}

	/**
	 * @return array
	 */
	public function Ping()
	{
		return $this->DefaultResponse(null, __FUNCTION__, 'Pong');
	}	
	
	/**
	 * @return array
	 */
	public function GetAppData()
	{
		$oApiIntegratorManager = \CApi::GetCoreManager('integrator');
		$sAuthToken = (string) $this->getParamValue('AuthToken', '');
		return $this->DefaultResponse(null, __FUNCTION__, 
				$oApiIntegratorManager ? $oApiIntegratorManager->appData(false, null, '', '', '', $sAuthToken) : false);
	}	
}

return new CoreModule('1.0');
