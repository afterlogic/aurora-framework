<?php
/*
 * @copyright Copyright (c) 2017, Afterlogic Corp.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 * 
 */

namespace Aurora\System\Managers\Capability;

/**
 * @package Capability
 */
class Manager extends \Aurora\System\AbstractManager
{
	/**
	 * @param &$oManager
	 */
	public function __construct(\Aurora\System\GlobalManager &$oManager)
	{
		parent::__construct('Сapability', $oManager);
	}

	/**
	 * @return bool
	 */
	public function isNotLite()
	{
		return !!\Aurora\System\Api::GetSystemManager('Licensing');
	}

	/**
	 * @return bool
	 */
	public function isCollaborationSupported()
	{
		return true;
//		return $this->isNotLite() && !!\Aurora\System\Api::GetSystemManager('collaboration');
	}

	/**
	 * @return bool
	 */
	public function isMailsuite()
	{
		return !!\Aurora\System\Api::GetConf('mailsuite', false) && !!\Aurora\System\Api::GetSystemManager('mailsuite');
	}

	/**
	 * @return bool
	 */
	public function isDavSupported()
	{
		return $this->isNotLite() && !!\Aurora\System\Api::GetModuleManager()->ModuleExists('Dav');
	}

	/**
	 * @return bool
	 */
	public function isTenantsSupported()
	{
		return $this->isNotLite() && !!\Aurora\System\Api::GetConf('tenant', false);
	}

	/**
	 * @param CAccount $oAccount = null
	 * 
	 * @return bool
	 */
	public function isCalendarSupported($oAccount = null)
	{
		return true; // TODO
	}

	/**
	 * @return bool
	 */
	public function isIosProfileSupported()
	{
		return $this->isNotLite();
	}

	/**
	 * @param CAccount $oAccount = null
	 * @return bool
	 */
	public function isCalendarSharingSupported($oAccount = null)
	{
		return true; // TODO
	}

	/**
	 * @param int $iUserId = null
	 * @return bool
	 */
	public function isCalendarAppointmentsSupported($iUserId = null)
	{
		return true; // TODO:
	}

	/**
	 * @param CAccount $oAccount = null
	 * @return bool
	 */
	public function isContactsSupported($oAccount = null)
	{
		return true; // TODO
	}

	/**
	 * @param CAccount $oAccount = null
	 * @return bool
	 */
	public function isPersonalContactsSupported($oAccount = null)
	{
		return true; // TODO
	}

	/**
	 * @param int $iUserId = null
	 * @param bool $bCheckShowSettings = true
	 * @return bool
	 */
	public function isGlobalContactsSupported($iUserId = null, $bCheckShowSettings = true)
	{
		return false; //TODO:
		
		$bResult = $this->isContactsSupported() && $this->isCollaborationSupported();
		if ($bResult && $bCheckShowSettings)
		{
			$oSettings = null;
			$oSettings =&\Aurora\System\Api::GetSettings();
			$bResult = $oSettings && !!$oSettings->GetConf('Contacts/ShowGlobalContactsInAddressBook');
		}

		if ($bResult && $iUserId)
		{
			$bIsGlobalContactsEnabled = true;
			$bResult = $this->isContactsSupported($iUserId) && $iUserId->User->getCapa(ECapa::GAB) && $bIsGlobalContactsEnabled;
		}		

		return $bResult;
	}

	/**
	 * @param CAccount $oAccount = null
	 * @return bool
	 */
	public function isSharedContactsSupported($oAccount = null)
	{
		$bResult = $this->isContactsSupported() && $this->isCollaborationSupported() &&
			\Aurora\System\Api::GetConf('labs.contacts-sharing', false);
		
		if ($bResult && $oAccount)
		{
			$bIsGlobalContactsEnabled = true;
			$bResult = $this->isContactsSupported($oAccount) && $oAccount->User->getCapa(ECapa::CONTACTS_SHARING) && $bIsGlobalContactsEnabled;
		}

		return $bResult;
	}

	/**
	 * @param CAccount $oAccount = null
	 * @return bool
	 */
	public function isGlobalSuggestContactsSupported($oAccount = null)
	{
		return $this->isGlobalContactsSupported($oAccount, false);
	}

	/**
	 * @param CAccount $oAccount = null
	 * @return bool
	 */
	public function isFilesSupported($oAccount = null)
	{
		return true; //TODO: sash
	}

	/**
	 * @param CAccount $oAccount = null
	 * @return bool
	 */
	public function isTwilioSupported($oAccount = null)
	{
		$bResult = $this->isCollaborationSupported() && !!\Aurora\System\Api::GetConf('labs.twilio', false);
		if ($bResult && $oAccount)
		{
			$oTenant = $this->_getCachedTenant($oAccount->IdTenant);
			if ($oTenant)
			{
				$bResult = $oTenant->isTwilioSupported();
			}
			
			if ($bResult)
			{
				$bResult = $oAccount->User->getCapa(ECapa::TWILIO);
			}
		}

		return $bResult;
	}

	/**
	 * @param CAccount $oAccount = null
	 * @return bool
	 */
	public function isSipSupported($oAccount = null)
	{
		$bResult = $this->isCollaborationSupported() && !!\Aurora\System\Api::GetConf('labs.voice', false);
		if ($bResult && $oAccount)
		{
			$oTenant = $this->_getCachedTenant($oAccount->IdTenant);
			if ($oTenant)
			{
				$bResult = $oTenant->isSipSupported();
			}

			if ($bResult)
			{
				$bResult = $oAccount->User->getCapa(ECapa::SIP);
			}
		}

		return $bResult;
	}

	/**
	 * @param CAccount $oAccount = null
	 * @return bool
	 */
	public function isHelpdeskSupported($oAccount = null)
	{
		$bResult = $this->isCollaborationSupported() && !!\Aurora\System\Api::GetConf('helpdesk', false);
		if ($bResult && $oAccount)
		{
			$oTenant = $this->_getCachedTenant($oAccount->IdTenant);
			if ($oTenant)
			{
				$bResult = $oTenant->isHelpdeskSupported();
			}
		}

		return $bResult;
	}

	/**
	 * @param CAccount $oAccount = null
	 * @return bool
	 */
	public function isMobileSyncSupported($oAccount = null)
	{
		$bResult = $this->isNotLite() && $this->isDavSupported() &&
			($this->isContactsSupported() || $this->isGlobalContactsSupported() ||
			$this->isCalendarSupported() || $this->isHelpdeskSupported());

		if ($bResult)
		{
			$bResult = \Aurora\System\Api::GetSettingsConf('Common/EnableMobileSync');
		}
			
		if ($bResult && $oAccount)
		{
			$bResult = $oAccount->User->getCapa(ECapa::MOBILE_SYNC) &&
				($this->isContactsSupported($oAccount) || $this->isGlobalContactsSupported($oAccount) ||
				$this->isCalendarSupported($oAccount) || $this->isHelpdeskSupported($oAccount));
		}

		return $bResult;
	}

	/**
	 * @param CAccount $oAccount = null
	 * @return bool
	 */
	public function isOutlookSyncSupported($oAccount = null)
	{
		$bResult = $this->isNotLite() && $this->isDavSupported() && $this->isCollaborationSupported();
//		if ($bResult && $oAccount)
//		{
//			$bResult = $oAccount->User->GetCapa(ECapa::OUTLOOK_SYNC);
//		}
// TODO

		return $bResult;
	}

	/**
	 * @staticvar $sCache
	 * @return string
	 */
	public function getSystemCapaAsString()
	{
		static $sCache = null;
		if (null === $sCache)
		{
			$aCapa[] = ECapa::WEBMAIL;

			if ($this->isPersonalContactsSupported())
			{
				$aCapa[] = ECapa::PAB;
			}

			if ($this->isGlobalContactsSupported())
			{
				$aCapa[] = ECapa::GAB;
			}

			if ($this->isCalendarSupported())
			{
				$aCapa[] = ECapa::CALENDAR;
			}

			if ($this->isCalendarAppointmentsSupported())
			{
				$aCapa[] = ECapa::MEETINGS;
			}

			if ($this->isCalendarSharingSupported())
			{
				$aCapa[] = ECapa::CAL_SHARING;
			}

			if ($this->isMobileSyncSupported())
			{
				$aCapa[] = ECapa::MOBILE_SYNC;
			}

			if ($this->isOutlookSyncSupported())
			{
				$aCapa[] = ECapa::OUTLOOK_SYNC;
			}

			if ($this->isFilesSupported())
			{
				$aCapa[] = ECapa::FILES;
			}

			if ($this->isHelpdeskSupported())
			{
				$aCapa[] = ECapa::HELPDESK;
			}

			if ($this->isSipSupported())
			{
				$aCapa[] = ECapa::SIP;
			}
			
			if ($this->isTwilioSupported())
			{
				$aCapa[] = ECapa::TWILIO;
			}

			$sCache = trim(strtoupper(implode(' ', $aCapa)));
		}

		return $sCache;
	}

	/**
	 * @return bool
	 */
	public function hasSslSupport()
	{
		return \Aurora\System\Utils::hasSslSupport();
	}

	/**
	 * @return bool
	 */
	public function hasGdSupport()
	{
		return \Aurora\System\Utils::HasGdSupport();
	}

	/**
	 * @param int $iIdTenant
	 * @return CTenant
	 */
	private function _getCachedTenant($iIdTenant)
	{
		static $aCache = array();
		$oTenant = null;

		if (isset($aCache[$iIdTenant]))
		{
			$oTenant = $aCache[$iIdTenant];
		}
		else
		{
			$oApiTenants = /* @var $oApiTenants CApiTenantsManager */\Aurora\System\Api::GetSystemManager('tenants');
			if ($oApiTenants)
			{
				$oTenant = (0 < $iIdTenant) ? $oApiTenants->getTenantById($iIdTenant) : $oApiTenants->getDefaultGlobalTenant();
			}
		}

		if ($oTenant && !isset($aCache[$iIdTenant]))
		{
			$aCache[$iIdTenant] = $oTenant;
		}

		return $oTenant;
	}
}
