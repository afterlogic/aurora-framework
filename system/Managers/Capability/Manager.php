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
class Manager extends \Aurora\System\Managers\AbstractManager
{
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
	 * @param CAccount $oAccount = null
	 * 
	 * @return bool
	 */
	public function isCalendarSupported($oAccount = null)
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
			$bResult = $this->isContactsSupported($iUserId) && $iUserId->User->getCapa(\Aurora\System\Enums\Capa::GAB) && $bIsGlobalContactsEnabled;
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
	public function isMobileSyncSupported($oAccount = null)
	{
		$bResult = $this->isNotLite() &&
			($this->isContactsSupported() || $this->isGlobalContactsSupported() ||
			$this->isCalendarSupported() || $this->isHelpdeskSupported());

		if ($bResult)
		{
			$oMobileSyncModule = \Aurora\System\Api::GetModule('MobileSync');
			$bResult = $oMobileSyncModule && !$oMobileSyncModule->getConfig('Disabled');
		}
			
		if ($bResult && $oAccount)
		{
			$bResult = $oAccount->User->getCapa(\Aurora\System\Enums\Capa::MOBILE_SYNC) &&
				($this->isContactsSupported($oAccount) || $this->isGlobalContactsSupported($oAccount) ||
				$this->isCalendarSupported($oAccount));
		}

		return $bResult;
	}

	/**
	 * @param CAccount $oAccount = null
	 * @return bool
	 */
	public function isOutlookSyncSupported($oAccount = null)
	{
		$bResult = $this->isNotLite() && $this->isCollaborationSupported();
//		if ($bResult && $oAccount)
//		{
//			$bResult = $oAccount->User->GetCapa(\Aurora\System\Enums\Capa::OUTLOOK_SYNC);
//		}
// TODO

		return $bResult;
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
