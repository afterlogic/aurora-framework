<?php
/*
 * This code is licensed under AGPLv3 license or Afterlogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

namespace Aurora\System\Managers;

/**
 * @license https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @license https://afterlogic.com/products/common-licensing AfterLogic Software License
 * @copyright Copyright (c) 2018, Afterlogic Corp.
 *
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
				\Aurora\System\Api::GetModuleManager()->broadcastEvent(
					'System', 
					'toResponseArray' . \Aurora\System\Module\AbstractModule::$Delimiter . 'before', 
					$aArgs
				);

				$mResult = \array_merge(self::objectWrapper($mResponse, $aParameters), $mResponse->toResponseArray($aParameters));

				\Aurora\System\Api::GetModuleManager()->broadcastEvent(
					'System', 
					'toResponseArray' . \Aurora\System\Module\AbstractModule::$Delimiter . 'after', 
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
	
	public static function RemoveThumbFromCache($iUserId, $sHash, $sFileName)
	{
		$oCache = new Cache('thumbs', \Aurora\System\Api::getUserUUIDById($iUserId));
		$sMd5Hash = \md5('Raw/Thumb/'.$sHash.'/'.$sFileName);
		if ($oCache->has($sMd5Hash))
		{
			$oCache->delete($sMd5Hash);
		}
	}
	
	public static function getImageAngle($rResource)
	{
		$iRotateAngle = 0;
		if (\function_exists('exif_read_data')) 
		{ 
			if ($exif_data = @\exif_read_data($rResource, 'IFD0')) 
			{ 
				switch (@$exif_data['Orientation']) 
				{ 
					case 1: 
						$iRotateAngle = 0; 
						break; 
					case 3: 
						$iRotateAngle = 180; 
						break; 
					case 6: 
						$iRotateAngle = 270; 
						break; 
					case 8: 
						$iRotateAngle = 90; 
						break; 
				}
			}
		}

		return $iRotateAngle;
	}

	public static function GetThumbResource($iUserId, $rResource, $sFileName, $bShow = true)
	{
		$sHash = (string) \Aurora\System\Application::GetPathItemByIndex(1, '');
		if (empty($sHash))
		{
			$sHash = \rand(1000, 9999);
		}
		$oCache = new Cache('thumbs', \Aurora\System\Api::getUserUUIDById($iUserId));

		$sCacheFileName = \md5('Raw/Thumb/'.$sHash.'/'.$sFileName);

		$sThumb = $oCache->get($sCacheFileName);

		if ($sThumb === null)
		{
			$iRotateAngle = self::getImageAngle($rResource);
			try
			{
				$oImageManager = new \Intervention\Image\ImageManager(['driver' => 'Gd']);
				$oThumb = $oImageManager->make($rResource);
				if ($iRotateAngle > 0)
				{
					$oThumb = $oThumb->rotate($iRotateAngle);
				}
				$sThumb = $oThumb->resize(120, 100)->response();

				$oCache->set($sCacheFileName, $sThumb);
			}
			catch (\Exception $oE) {}
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

