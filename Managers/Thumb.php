<?php
/**
 * This code is licensed under AGPLv3 license or Afterlogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

namespace Aurora\System\Managers;

/**
 * @license https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @license https://afterlogic.com/products/common-licensing Afterlogic Software License
 * @copyright Copyright (c) 2019, Afterlogic Corp.
 *
 * @package Api
 */
class Thumb
{
	public static function RemoveFromCache($iUserId, $sHash, $sFileName)
	{
		$oCache = new Cache('thumbs', \Aurora\System\Api::getUserUUIDById($iUserId));
		$sMd5Hash = \md5('Raw/Thumb/'.$sHash.'/'.$sFileName);
		if ($oCache->has($sMd5Hash))
		{
			$oCache->delete($sMd5Hash);
		}
	}

	public static function GetOrientation($rResource)
	{
		$iOrientation = 0;
		if (\function_exists('exif_read_data'))
		{
			if ($exif_data = @\exif_read_data($rResource, 'IFD0'))
			{
				$iOrientation = @$exif_data['Orientation'];
			}
		}

		return $iOrientation;
	}

	public static function OrientateImage($image, $iOrientation)
	{
		switch ($iOrientation)
		{
			case 2:
				$image->flip();
				break;

			case 3:
				$image->rotate(180);
				break;

			case 4:
				$image->rotate(180)->flip();
				break;

			case 5:
				$image->rotate(270)->flip();
				break;

			case 6:
				$image->rotate(270);
				break;

			case 7:
				$image->rotate(90)->flip();
				break;

			case 8:
				$image->rotate(90);
				break;
		}
	}

	public static function GetHash()
	{
		$sHash = (string) \Aurora\System\Router::getItemByIndex(1, '');
		if (empty($sHash))
		{
			$sHash = \rand(1000, 9999);
		}

		return $sHash;
	}

	public static function GetCacheFilename($sHash, $sFileName)
	{
		return \md5('Raw/Thumb/'.$sHash.'/'.$sFileName);
	}

	public static function GetResourceCache($iUserId, $sFileName)
	{
		$oCache = new Cache('thumbs', \Aurora\System\Api::getUserUUIDById($iUserId));

		return $oCache->get(
			self::GetCacheFilename(self::GetHash(), $sFileName)
		);
	}

	public static function GetResource($iUserId, $rResource, $sFileName, $bShow = true)
	{
		$sThumb = null;

		try
		{
			$sCacheFilename = self::GetCacheFilename(self::GetHash(), $sFileName);
			$sCacheFilePathTmp = Cache::getPath() . $sCacheFilename . '-' . $sFileName;
			$rFile = \fopen($sCacheFilePathTmp, 'w+');
			\fwrite($rFile, \stream_get_contents($rResource));

			$iOrientation = self::GetOrientation($rFile);
			$oImageManager = new \Intervention\Image\ImageManager(['driver' => 'Gd']);
			$oThumb = $oImageManager->make($rFile);
			self::OrientateImage($oThumb, $iOrientation);

			$sThumb = $oThumb->heighten(100)->widen(100)->response();

			\unlink($sCacheFilePathTmp);

			$oCache = new Cache('thumbs', \Aurora\System\Api::getUserUUIDById($iUserId));
			$oCache->set(
				$sCacheFilename,
				$sThumb
			);
		}
		catch (\Exception $oE) {}

		if ($bShow)
		{
			echo $sThumb; exit();
		}
		else
		{
			return $sThumb;
		}
	}
}