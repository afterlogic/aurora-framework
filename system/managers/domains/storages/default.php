<?php
/*
 * @copyright Copyright (c) 2016, Afterlogic Corp.
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

/**
 * @internal
 * 
 * @package Domains
 * @subpackage Storages
 */
class CApiDomainsStorage extends AApiManagerStorage
{
	/**
	 * @param CApiGlobalManager &$oManager
	 */
	public function __construct($sStorageName, AApiManager &$oManager)
	{
		parent::__construct('domains', $sStorageName, $oManager);
	}

	/**
	 * @param string $sDomainId
	 *
	 * @return CDomain
	 */
	public function getDomainById($sDomainId)
	{
		return null;
	}

	/**
	 * @param string $sDomainName
	 *
	 * @return CDomain
	 */
	public function getDomainByName($sDomainName)
	{
		return null;
	}

	/**
	 * @param string $sDomainUrl
	 *
	 * @return CDomain
	 */
	public function getDomainByUrl($sDomainUrl)
	{
		return null;
	}

	/**
	 * @param CDomain &$oDomain
	 *
	 * @return bool
	 */
	public function createDomain(CDomain &$oDomain)
	{
		return false;
	}

	/**
	 * @param CDomain $oDomain
	 *
	 * @return bool
	 */
	public function updateDomain(CDomain $oDomain)
	{
		return false;
	}
	
	/**
	 * @param int $iVisibility
	 * @param int $iTenantId
	 *
	 * @return bool
	 */
	public function setGlobalAddressBookVisibilityByTenantId($iVisibility, $iTenantId)
	{
		return false;
	}

	/**
	 * @param array $aDomainsIds
	 *
	 * @return bool
	 */
	public function areDomainsEmpty($aDomainsIds)
	{
		return true;
	}

	/**
	 * @param array $aDomainsIds
	 * @param bool $bEnable
	 *
	 * @return bool
	 */
	public function enableOrDisableDomains($aDomainsIds, $bEnable)
	{
		return false;
	}

	/**
	 * @param int $iTenantId
	 * @param bool $bEnable
	 *
	 * @return bool
	 */
	public function enableOrDisableDomainsByTenantId($iTenantId, $bEnable)
	{
		return false;
	}

	/**
	 * @param array $aDomainsIds
	 *
	 * @return bool
	 */
	public function deleteDomains($aDomainsIds)
	{
		return false;
	}

	/**
	 * @param int $iDomainId
	 * @return bool
	 */
	public function deleteDomain($iDomainId)
	{
		return false;
	}

	/**
	 * @param int $iPage
	 * @param int $iDomainsPerPage
	 * @param string $sOrderBy Default value is **'name'**
	 * @param bool $bOrderType Default value is **true**
	 * @param string $sSearchDesc Default value is empty string
	 * @param int $iTenantId Default value is **0**
	 *
	 * @return array|false [IdDomain => [IsInternal, Name]]
	 */
	public function getDomainsList($iPage, $iDomainsPerPage, $sOrderBy = 'name', $bOrderType = true, $sSearchDesc = '', $iTenantId = 0)
	{
		return false;
	}

	/**
	 * @param int $iTenantId
	 *
	 * @return array
	 */
	public function getDomainIdsByTenantId($iTenantId)
	{
		return false;
	}
	
	/**
	 * @param int $iTenantId
	 *
	 * @return \CDomain
	 */
	public function getDefaultDomainByTenantId($iTenantId)
	{
		return null;
	}
	
	/**
	 * @param string $sDomainName
	 *
	 * @return bool
	 */
	public function domainExists($sDomainName)
	{
		return false;
	}

	/**
	 * @param string $sSearchDesc Default value is empty string.
	 * @param int $iTenantId Default value is **0**.
	 *
	 * @return int|false
	 */
	public function getDomainCount($sSearchDesc = '', $iTenantId = 0)
	{
		return 0;
	}
}
