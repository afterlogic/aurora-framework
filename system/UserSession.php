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

/**
 * @package Api
 */

namespace Aurora\System;

class UserSession
{
	/**
     * @var \MailSo\Cache\CacheClient
     */
    protected $Session = null;
	
	/**
     * @var \MailSo\Cache\CacheClient
     */
	protected $Path = '';
	
	public function __construct()
	{
		$this->Path = \Aurora\System\Api::DataPath().'/sessions';
		$oSession = \MailSo\Cache\CacheClient::NewInstance();
		$oSessionDriver = \MailSo\Cache\Drivers\File::NewInstance($this->Path);
		$oSessionDriver->bRootDir = true;
		$oSession->SetDriver($oSessionDriver);
		$oSession->SetCacheIndex(\Aurora\System\Api::Version());

		$this->Session = $oSession;
	}
	
	protected function getList()
	{
		$aResult = array();
		$aItems = scandir($this->Path);
		foreach ($aItems as $sItemName)
		{
			if ($sItemName === '.' or $sItemName === '..')
			{
				continue;
			}
			
			$sItemPath = $this->Path . DIRECTORY_SEPARATOR . $sItemName;
			$aItem = \Aurora\System\Api::DecodeKeyValues(file_get_contents($sItemPath));
			if (is_array($aItem) && isset($aItem['token']))
			{
				$aResult[$sItemPath] = $aItem;
			}
		}
		
		return $aResult;
	}

	public function Set($aData)
	{
		if (!file_exists($this->Path))
		{
			@mkdir($this->Path, 0777);
		}
		
		$sAccountHashTable = \Aurora\System\Api::EncodeKeyValues($aData);
		$sAuthToken = \md5(\microtime(true).\rand(10000, 99999));
		return $this->Session->Set('AUTHTOKEN:'.$sAuthToken, $sAccountHashTable) ? $sAuthToken : '';
	}
	
	public function Get($sAuthToken)
	{
		$mResult = false;
		
		if (strlen($sAuthToken) !== 0) {
			
			$sKey = $this->Session->get('AUTHTOKEN:'.$sAuthToken);
		}
		if (!empty($sKey) && is_string($sKey)) {
			
			$mResult = \Aurora\System\Api::DecodeKeyValues($sKey);
		}
		
		return $mResult;
	}
	
	public function Delete($sAuthToken)
	{
		$this->Session->Delete('AUTHTOKEN:'.$sAuthToken);
	}
	
	public function DeleteById($iId)
	{
		$aList = $this->getList();
		foreach ($aList as $sKey => $aItem)
		{
			if (isset($aItem['id']) && (int)$aItem['id'] === $iId)
			{
				@unlink($sKey);
			}
		}
	}
}