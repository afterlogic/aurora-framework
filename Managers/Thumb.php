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
        $oCache = new Cache('thumbs');
        $sMd5Hash = \md5('Raw/Thumb/' . $sHash . '/' . $sFileName);
        if ($oCache->has($sMd5Hash)) {
            $oCache->delete($sMd5Hash);
        }
    }

    public static function GetHash()
    {
        $sHash = (string) \Aurora\System\Router::getItemByIndex(1, '');
        if (empty($sHash)) {
            $sHash = \rand(1000, 9999);
        }

        return $sHash;
    }

    /**
     * @param string $sHash
     * @param string $sFileName
     *
     * @return string
     */
    public static function GetCacheFilename($sHash, $sFileName)
    {
        return \md5('Raw/Thumb/' . $sHash . '/' . $sFileName);
    }

    public static function GetResourceCache($iUserId, $sFileName)
    {
        $oCache = new Cache('thumbs');

        return $oCache->get(
            self::GetCacheFilename(self::GetHash(), $sFileName)
        );
    }

    public static function GetResource($iUserId, $rResource, $sFileName, $bShow = true)
    {
        $sThumb = null;

        try {
            $sCacheFilename = self::GetCacheFilename(self::GetHash(), $sFileName);
            $sCacheFilePathTmp = Cache::getPath() . $sCacheFilename;
            $rFile = \fopen($sCacheFilePathTmp, 'w+');
            \fwrite($rFile, \stream_get_contents($rResource));

            $oImageManager = new \Intervention\Image\ImageManager(['driver' => 'Gd']);
            $oThumb = $oImageManager->make($rFile)->orientate();

            $sThumb = (string) $oThumb->heighten(94)->widen(118)->stream();

            \unlink($sCacheFilePathTmp);

            $oCache = new Cache('thumbs');
            $oCache->set(
                $sCacheFilename,
                $sThumb
            );
        } catch (\Exception $oE) {
            \Aurora\System\Api::LogException($oE, \Aurora\System\Enums\LogLevel::Full);
        }

        if ($bShow) {
            echo $sThumb;
            exit();
        } else {
            return $sThumb;
        }
    }
}
