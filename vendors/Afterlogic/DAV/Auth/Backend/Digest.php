<?php

/* -AFTERLOGIC LICENSE HEADER- */

namespace afterlogic\DAV\Auth\Backend;

class Digest extends \Sabre\DAV\Auth\Backend\AbstractDigest
{
    /**
     * Creates the backend object.
     *
     * @return void
     */
    public function __construct()
	{
    }

    public function setCurrentUser($user)
	{
		$this->currentUser = $user;
	}
	
	public function getDigestHash($sRealm, $sUserName)
	{
		if (class_exists('CApi') && \CApi::IsValid())
		{
			/* @var $oApiCapabilityManager \CApiCapabilityManager */
			$oApiCapabilityManager = \CApi::GetCoreManager('capability');

			if ($oApiCapabilityManager)
			{
				$oAccount = \afterlogic\DAV\Utils::GetAccountByLogin($sUserName);
				if ($oAccount && $oAccount->IsDisabled)
				{
					return null;
				}

				$bIsOutlookSyncClient = \afterlogic\DAV\Utils::ValidateClient('outlooksync');

				$bIsMobileSync = false;
				$bIsOutlookSync = false;
				$bIsDemo = false;

				if ($oAccount)
				{
					$bIsMobileSync = $oApiCapabilityManager->isMobileSyncSupported($oAccount);
					$bIsOutlookSync = $oApiCapabilityManager->isOutlookSyncSupported($oAccount);
					
					\CApi::Plugin()->RunHook('plugin-is-demo-account', array(&$oAccount, &$bIsDemo));
				}
				
				if (($oAccount && (($bIsMobileSync && !$bIsOutlookSyncClient) || ($bIsOutlookSync && $bIsOutlookSyncClient))) ||
					$bIsDemo || ($oApiCalendarManager && $sUserName === \CApi::ExecuteMethod('Dav::GetPublicUser')))
				{
					\afterlogic\DAV\Utils::CheckPrincipals($sUserName);
					
					return md5($sUserName.':'.$sRealm.':'.($bIsDemo ? 'demo' : $oAccount->IncomingMailPassword));
				}
			}
		}

		return null;
	}
}
