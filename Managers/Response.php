<?php
/*
 * @copyright Copyright (c) 2017, Afterlogic Corp.
 * @license AGPL-3.0 or Afterlogic Software License
 *
 * This code is licensed under AGPLv3 license or Afterlogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

namespace Aurora\System\Managers;

/**
 * @package Api
 */
class Response
{
	protected static $sMethod = null;

	public static $objectNames = array(
			'Aurora\Modules\Mail\Classes\MessageCollection' => 'MessageCollection',
			'Aurora\Modules\Mail\Classes\Message' => 'Message',
			'Aurora\Modules\Mail\Classes\FolderCollection' => 'FolderCollection',
			'Aurora\Modules\Mail\Classes\Folder' => 'Folder'
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
//			$aNames = \explode('\\', \get_class($oData));
//			$sObjectName = end($aNames);
			$sObjectName = \get_class($oData);
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
				$aArgs = [$mResponse, $aParameters];
				$this->broadcastEvent(
					'System', 
					'toResponseArray' . AbstractModule::$Delimiter . 'before', 
					$aArgs
				);

				$mResult = \array_merge(self::objectWrapper($mResponse, $aParameters), $mResponse->toResponseArray($aParameters));

				$this->broadcastEvent(
					'System', 
					'toResponseArray' . AbstractModule::$Delimiter . 'after', 
					$aArgs,
					$mResult
				);			
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
		$sHash = (string) \Aurora\System\Application::GetPathItemByIndex(1, '');
		if (empty($sHash))
		{
			$sHash = \rand(1000, 9999);
		}
		$sMd5Hash = \md5($sHash);

		$oApiFileCache = new Filecache();
		
		$sThumb = null;
		if (!$oApiFileCache->isFileExists($oAccount, 'Raw/Thumb/'.$sMd5Hash, '_'.$sFileName, 'System'))
		{
			$oApiFileCache->putFile($oAccount, 'Raw/ThumbOrig/'.$sMd5Hash, $rResource, '_'.$sFileName, 'System');
			if ($oApiFileCache->isFileExists($oAccount, 'Raw/ThumbOrig/'.$sMd5Hash, '_'.$sFileName, 'System'))
			{
				try
				{
					$oThumb = new \PHPThumb\GD(
						$oApiFileCache->generateFullFilePath($oAccount, 'Raw/ThumbOrig/'.$sMd5Hash, '_'.$sFileName, 'System')
					);

					$sThumb = $oThumb->adaptiveResize(120, 100)->getImageAsString();
					$oApiFileCache->put($oAccount, 'Raw/Thumb/'.$sMd5Hash, $sThumb, '_'.$sFileName, 'System');
				}
				catch (\Exception $oE) {}
			}
			$oApiFileCache->clear($oAccount, 'Raw/ThumbOrig/'.$sMd5Hash, '_'.$sFileName, 'System');
		}
		if (!isset($sThumb))
		{
			$sThumb = $oApiFileCache->get($oAccount, 'Raw/Thumb/'.$sMd5Hash, '_'.$sFileName, 'System');
		}
		if ($bShow)
		{
			echo $sThumb; exit();
		}
		else 
		{
			return $sThumb;
		}
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
//			$aNames = \explode('\\', \get_class($oCollection));
//			$sObjectName = end($aNames);
			$sObjectName = \get_class($oCollection);

			$aResult = array(
				'@Object' => 'Collection/'. self::GetObjectName($sObjectName),
				'@Count' => $oCollection->Count(),
				'@Collection' => self::GetResponseObject($oCollection->CloneAsArray(), $aParameters)
			);
		}
		
		return $aResult;
	}
	
}

