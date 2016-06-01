<?php

class IosModule extends AApiModule
{
	public function init() {
		parent::init();
		
		$this->AddEntries(array(
				'ios' => 'EntryIos',
				'profile' => 'EntryProfile'
			)
		);
	}
	
	public function EntryIos()
	{
		$sResult = file_get_contents($this->GetPath().'/templates/Ios.html');

		$oApiIntegrator = \CApi::GetCoreManager('integrator');
		$iUserId = $oApiIntegrator->getLogginedUserId();
		if (0 < $iUserId)
		{
			$oAccount = $oApiIntegrator->getLogginedDefaultAccount();
			$aPaths = \System\Service::GetPaths();
			$bError = isset($aPaths[1]) && 'error' === strtolower($aPaths[1]); // TODO

			@setcookie('skip_ios', '1', time() + 3600 * 3600, '/', null, null, true);

			$sResult = strtr($sResult, array(
				'{{IOS/HELLO}}' => \CApi::ClientI18N('IOS/HELLO', $oAccount),
				'{{IOS/DESC_P1}}' => \CApi::ClientI18N('IOS/DESC_P1', $oAccount),
				'{{IOS/DESC_P2}}' => \CApi::ClientI18N('IOS/DESC_P2', $oAccount),
				'{{IOS/DESC_P3}}' => \CApi::ClientI18N('IOS/DESC_P3', $oAccount),
				'{{IOS/DESC_P4}}' => \CApi::ClientI18N('IOS/DESC_P4', $oAccount),
				'{{IOS/DESC_P5}}' => \CApi::ClientI18N('IOS/DESC_P5', $oAccount),
				'{{IOS/DESC_P6}}' => \CApi::ClientI18N('IOS/DESC_P6', $oAccount),
				'{{IOS/DESC_P7}}' => \CApi::ClientI18N('IOS/DESC_P7', $oAccount),
				'{{IOS/DESC_BUTTON_YES}}' => \CApi::ClientI18N('IOS/DESC_BUTTON_YES', $oAccount),
				'{{IOS/DESC_BUTTON_SKIP}}' => \CApi::ClientI18N('IOS/DESC_BUTTON_SKIP', $oAccount),
				'{{IOS/DESC_BUTTON_OPEN}}' => \CApi::ClientI18N('IOS/DESC_BUTTON_OPEN', $oAccount),
				'{{AppVersion}}' => PSEVEN_APP_VERSION,
				'{{IntegratorLinks}}' => $oApiIntegrator->buildHeadersLink()
			));
		}
		else
		{
			\CApi::Location('./');
		}
		
		return $sResult;
	}
	
	public function EntryProfile()
	{
		/* @var $oApiIosManager \CApiIosManager */
		$oApiIosManager = \CApi::GetCoreManager('ios');

		$oApiIntegrator = \CApi::GetCoreManager('integrator');
		$oAccount = $oApiIntegrator->getLogginedDefaultAccount();

		$mResultProfile = $oApiIosManager && $oAccount ? $oApiIosManager->generateXMLProfile($oAccount) : false;

		if (!$mResultProfile)
		{
			\CApi::Location('./?IOS/Error');
		}
		else
		{
			header('Content-type: application/x-apple-aspen-config; chatset=utf-8');
			header('Content-Disposition: attachment; filename="afterlogic.mobileconfig"');
			echo $mResultProfile;
		}		
	}
	
}