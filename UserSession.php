<?php
/*
 * @copyright Copyright (c) 2017, Afterlogic Corp.
 * @license AGPL-3.0 or Afterlogic Software License
 *
 * This code is licensed under AGPLv3 license or Afterlogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

/**
 * @package Api
 */

namespace Aurora\System;

class UserSession
{
	/**
     * @var string
     */
	protected $Path = '';
	
	public function __construct()
	{
		$this->Path = Api::DataPath().'/sessions/';
	}

	public function Set($aData, $iTime = 0)
	{
		if (!\file_exists($this->Path))
		{
			@\mkdir($this->Path, 0777);
		}
		
		$aData['@time'] = $iTime;
		$sAuthToken = \md5(\microtime(true).\rand(10000, 99999));
		$aData['auth-token'] = $sAuthToken;
		$sAccountHashTable = Api::EncodeKeyValues(
			$aData
		);
		$sPath = $this->generateFileName($sAuthToken, $aData['id']);
		return (false !== \file_put_contents($sPath, $sAccountHashTable)) ? $sAuthToken : '';
	}
	
	public function Get($sAuthToken)
	{
		$mResult = false;
		
		$sKey = '';
		if (strlen($sAuthToken) !== 0) 
		{
			$sPath = $this->generateFileName($sAuthToken);
			$aFiles = \glob($sPath . '*');
			if (\is_array($aFiles) && \count($aFiles) > 0)
			{
				$sKey = \file_get_contents($aFiles[0]);
			}

			$sKey = \is_string($sKey) ? $sKey : '';
		}
		if (!empty($sKey) && \is_string($sKey)) 
		{
			$mResult = Api::DecodeKeyValues($sKey);
			if (isset($mResult['@time']) && \time() > (int)$mResult['@time'] && (int)$mResult['@time'] > 0)
			{
				\Aurora\System\Api::Log('User session expired: ');
				\Aurora\System\Api::LogObject($mResult);
				$this->Delete($sAuthToken);
				$mResult = false;
			}
		}
		
		return $mResult;
	}
	
	public function GetById($iId)
	{
		$mResult = false;
		
		$aFiles = \glob($this->Path . '*.' . $iId);
		if (\is_array($aFiles) && \count($aFiles) > 0)
		{
			$sKey = $aFiles[0];
			$aItem = Api::DecodeKeyValues(\file_get_contents($sKey));
			if (\is_array($aItem) && isset($aItem['token']))
			{
				if (isset($aItem['id']) && isset($aItem['auth-token']) && (int)$aItem['id'] === $iId)
				{
					if (\basename($sKey) === \basename($this->generateFileName($aItem['auth-token'], $iId)))
					{
						$mResult = $aItem;
					}
					else
					{
						@\unlink($sKey);
					}
				}
			}
		}		
		
		return $mResult;
	}
	
	public function Delete($sAuthToken)
	{
		$sPath = $this->generateFileName($sAuthToken);
		$aFiles = \glob($sPath . '*');
		if (\is_array($aFiles) && \count($aFiles) > 0)
		{
			\unlink($aFiles[0]);
		}
	}
	
	public function DeleteById($iId)
	{
		$aFiles = \glob($this->Path.'*.' . $iId);
		if (\is_array($aFiles) && \count($aFiles) > 0)
		{
			@\unlink($aFiles[0]);
		}
	}
	
	public function GC($iTimeToClearInHours = 0)
	{
		if (0 < $iTimeToClearInHours)
		{
			\MailSo\Base\Utils::RecTimeDirRemove($this->Path, 60 * 60 * $iTimeToClearInHours, \time());
			return true;
		}
		
		return false;
	}
	
	/**
	 * @param string $sKey
	 * @param int UserId
	 *
	 * @return string
	 */
	private function generateFileName($sKey, $iUserId = 0)
	{
		$sFilePath = '';
		if (3 < \strlen($sKey))
		{
			$sKeyPath = \sha1('AUTHTOKEN:' . $sKey . Api::$sSalt);
			if ($iUserId !== 0)
			{
				$sKeyPath = $sKeyPath . '.' . $iUserId;
			}

			$sFilePath = $this->Path.$sKeyPath;
		}

		return $sFilePath;
	}	
}