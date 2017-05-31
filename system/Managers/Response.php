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
 * @package Api
 */
class Response
{
	protected static $sMethod = null;

	public static $objectNames = array(
			'CApiMailMessageCollection' => 'MessageCollection',
			'CApiMailMessage' => 'Message',
			'CApiMailFolderCollection' => 'FolderCollection',
			'CApiMailFolder' => 'Folder'
	);

	public static function GetMethod()
	{
		return  self::$sMethod;
	}
	
	public static function SetMethod($sMethod)
	{
		self::$sMethod = $sMethod;
	}

	/**
	 * @param string $sObjectName
	 *
	 * @return string
	 */
	public static function GetObjectName($sObjectName)
	{
		return !empty(self::$objectNames[$sObjectName]) ? self::$objectNames[$sObjectName] : $sObjectName;
	}
	
	/**
	 * @param object $oData
	 *
	 * @return array | false
	 */
	public static function objectWrapper($oData, $aParameters = array())
	{
		$mResult = false;
		if (\is_object($oData))
		{
			$aNames = \explode('\\', \get_class($oData));
			$sObjectName = end($aNames);
			$mResult = array(
				'@Object' => self::GetObjectName($sObjectName)
			);			

			if ($oData instanceof \MailSo\Base\Collection)
			{
				$mResult['@Object'] = 'Collection/'.$mResult['@Object'];
				$mResult['@Count'] = $oData->Count();
				$mResult['@Collection'] = self::GetResponseObject($oData->CloneAsArray(), $aParameters);
			}
			else
			{
				$mResult['@Object'] = 'Object/'.$mResult['@Object'];
			}
		}

		return $mResult;
	}
	
	/**
	 * @param mixed $mResponse
	 *
	 * @return mixed
	 */
	public static function GetResponseObject($mResponse, $aParameters = array())
	{
		$mResult = null;

		if (\is_object($mResponse))
		{
			if (\method_exists($mResponse, 'toResponseArray'))	
			{
				$mResult = \array_merge(self::objectWrapper($mResponse, $aParameters), $mResponse->toResponseArray($aParameters));
			}
			else
			{
				$mResult = \array_merge(self::objectWrapper($mResponse, $aParameters), self::CollectionToResponseArray($mResponse, $aParameters));
			}
		}
		else if (\is_array($mResponse))
		{
			foreach ($mResponse as $iKey => $oItem)
			{
				$mResponse[$iKey] = self::GetResponseObject($oItem, $aParameters);
			}

			$mResult = $mResponse;
		}
		else
		{
			$mResult = $mResponse;
		}

		unset($mResponse);
		return $mResult;
	}	
	
	/**
	 * @param bool $bDownload
	 * @param string $sContentType
	 * @param string $sFileName
	 *
	 * @return bool
	 */
	public static function OutputHeaders($bDownload, $sContentType, $sFileName)
	{
	
		if ($bDownload)
		{
			\header('Content-Type: '.$sContentType, true);
		}
		else
		{
			$aParts = \explode('/', $sContentType, 2);
			if (\in_array(\strtolower($aParts[0]), array('image', 'video', 'audio')) ||
				\in_array(\strtolower($sContentType), array('application/pdf', 'application/x-pdf', 'text/html')))
			{
				\header('Content-Type: '.$sContentType, true);
			}
			else
			{
				\header('Content-Type: text/plain; charset=', true);
			}
		}

		\header('Content-Disposition: '.($bDownload ? 'attachment' : 'inline' ).'; '.
			\trim(\MailSo\Base\Utils::EncodeHeaderUtf8AttributeValue('filename', $sFileName)), true);
		
		\header('Accept-Ranges: none', true);
	}
	
	public static function GetThumbResource($oAccount, $rResource, $sFileName, $bShow = true)
	{
		$sMd5Hash = \md5(\rand(1000, 9999));

		$oApiFileCache = new Filecache\Manager();
		$oApiFileCache->putFile($oAccount, 'Raw/Thumbnail/'.$sMd5Hash, $rResource, '_'.$sFileName);
		if ($oApiFileCache->isFileExists($oAccount, 'Raw/Thumbnail/'.$sMd5Hash, '_'.$sFileName))
		{
			try
			{
				$oThumb = new \PHPThumb\GD(
					$oApiFileCache->generateFullFilePath($oAccount, 'Raw/Thumbnail/'.$sMd5Hash, '_'.$sFileName)
				);

				if ($bShow)
				{
					$oThumb->adaptiveResize(120, 100)->show();
				}
				else 
				{
					return $oThumb->adaptiveResize(120, 100)->getImageAsString();
				}
			}
			catch (\Exception $oE) {}
		}

		$oApiFileCache->clear($oAccount, 'Raw/Thumbnail/'.$sMd5Hash, '_'.$sFileName);
	}	
	
	/**
	 * @param string $sKey
	 *
	 * @return void
	 */
	public static function cacheByKey($sKey)
	{
		if (!empty($sKey))
		{
			$iUtcTimeStamp = time();
			$iExpireTime = 3600 * 24 * 5;

			\header('Cache-Control: private', true);
			\header('Pragma: private', true);
			\header('Etag: '.\md5('Etag:'.\md5($sKey)), true);
			\header('Last-Modified: '.\gmdate('D, d M Y H:i:s', $iUtcTimeStamp - $iExpireTime).' UTC', true);
			\header('Expires: '.\gmdate('D, j M Y H:i:s', $iUtcTimeStamp + $iExpireTime).' UTC', true);
		}
	}

	/**
	 * @param string $sKey
	 *
	 * @return void
	 */
	public static function verifyCacheByKey($sKey)
	{
		if (!empty($sKey))
		{
			$oHttp = \MailSo\Base\Http::NewInstance();
			$sIfModifiedSince = $oHttp->GetHeader('If-Modified-Since', '');
			if (!empty($sIfModifiedSince))
			{
				$oHttp->StatusHeader(304);
				self::cacheByKey($sKey);
				exit();
			}
		}
	}	
	
	public static function CollectionToResponseArray($oCollection, $aParameters = array())
	{
		$aResult = array();
		if ($oCollection instanceof \MailSo\Base\Collection)
		{
			$aNames = \explode('\\', \get_class($oCollection));
			$sObjectName = end($aNames);

			$aResult = array(
				'@Object' => 'Collection/'. self::GetObjectName($sObjectName),
				'@Count' => $oCollection->Count(),
				'@Collection' => self::GetResponseObject($oCollection->CloneAsArray(), $aParameters)
			);
		}
		
		return $aResult;
	}
	
}

