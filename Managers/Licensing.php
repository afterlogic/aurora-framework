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

namespace Aurora\System\Managers;

/**
 * @package Licensing
 */
class Licensing extends \Aurora\System\Managers\AbstractManager
{
	/**
	 * @param \Aurora\System\Managers\GlobalManager &$oManager
	 */
	public function __construct()
	{
		parent::__construct();

		include_once \Aurora\System\Api::RootPath() . 'Managers/Licensing/classes/inc.php';
		include_once \Aurora\System\Api::RootPath() . 'Managers/Licensing/classes/enc.php';
	}

	/**
	 * @return string
	 */
	public function GetLicenseKey()
	{
		return $this->oSettings->GetConf('LicenseKey');
	}

	/**
	 * @param string $sKey
	 * @return bool
	 */
	public function UpdateLicenseKey($sKey)
	{
		$this->oSettings->SetConf('LicenseKey', $sKey);
		return $this->oSettings->Save();
	}

	/**
	 * @return int
	 */
	public function GetCurrentNumberOfUsers()
	{
		static $iCache = null;
		if (null === $iCache)
		{
			/* @var $oApiUsersManager CApiUsersManager */
//			$oApiUsersManager =\Aurora\System\Api::GetSystemManager('users');
			$iCache = $oApiUsersManager->getTotalUsersCount();
		}
		return $iCache;
	}

	/**
	 * @return int
	 */
	public function GetLicenseType()
	{
		$aInfo = $this->getInfo()->ObjValues();
		return isset($aInfo[1]) ? (int) $aInfo[1] : null;
	}

	/**
	 * @return bool
	 */
	public function IsValidKey()
	{
		$aInfo = $this->getInfo();
		return $aInfo->IsValid();
	}

	/**
	 * @return int
	 */
	public function GetVersion()
	{
		$aInfo = $this->getInfo();
		$oValues = $aInfo->ObjValues();
		return $oValues[5];
	}

	/**
	 * @param bool $bCheckOnCreate = false
	 * @return bool
	 */
	public function IsValidLimit($bCheckOnCreate = false)
	{
		$aInfo = $this->getInfo();
		$iCurrentNumberOfUsers = $this->GetCurrentNumberOfUsers();
		$iCurrentNumberOfUsers += $bCheckOnCreate ? 1 : 0;
		return $aInfo->IsValidLimit($iCurrentNumberOfUsers);
	}

	/**
	 * @param int $iExpiredSeconds
	 * @return bool
	 */
	public function IsAboutToExpire(&$iExpiredSeconds)
	{
		return $this->getInfo()->IsAboutToExpire($iExpiredSeconds);
	}

	/**
	 * @return int
	 */
	public function GetUserNumberLimit()
	{
		$aInfo = $this->getInfo()->ObjValues();
		return isset($aInfo[2]) ? $aInfo[2] : null;
	}

	/**
	 * @return int
	 */
	public function GetUserNumberLimitAsString()
	{
		$aInfo = $this->getInfo()->ObjValues();
		$sResult = empty($aInfo[0]) ? 'Empty' : 'Invalid';
		if (isset($aInfo[1], $aInfo[2], $aInfo[5]))
		{
			switch ($aInfo[1])
			{
				case 0:
					$sResult = 'Unlim';
					break;
				case 1:
					$sResult = $aInfo[2].' users, Permanent';
					break;
				case 2:
					$sResult = $aInfo[2].' domains';
					break;
				case 10:
					$sResult = 'Trial';
					if (isset($aInfo[4]))
					{
						$sResult .= ', expires in '.ceil($aInfo[4] / 60 / 60 / 24).' day(s).';
					}
					break;
				case 11:
					$sResult = 'Trial expired.
This license is outdated, please contact AfterLogic to upgrade your license key.';
					break;
				case 3:
					$sResult =  $aInfo[2].' users, Annual';
					if (isset($aInfo[4]))
					{
						$sResult .= ', expires in '.ceil($aInfo[4] / 60 / 60 / 24).' day(s).';
					}
					break;
				case 13:
					$sResult = $aInfo[2].' users, Annual, Expired.
This license is outdated, please contact AfterLogic to upgrade your license key.';
					break;
				case 14:
					$sResult = 'This license is outdated, please contact AfterLogic to upgrade your license key.';
					break;
			}
		}

		return $sResult;
	}

	/**
	 * @return int
	 */
	public function GetT()
	{
		return $this->getInfo()->Generate();
	}

	/**
	 * @return bool
	 */
	public function IsAU()
	{
		return $this->getInfo()->IsAU();
	}

	/**
	 * @return ALInfo
	 */
	protected function getInfo()
	{
		$oK = new ALInfo($this->oSettings->GetConf('LicenseKey'), defined('AL_AU') && AL_AU);
		return $oK;
	}

}
