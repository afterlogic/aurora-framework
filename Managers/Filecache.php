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
 * @package Filecache
 */
class Filecache extends \Aurora\System\Managers\AbstractManagerWithStorage
{
    /**
     * @var Filecache\Storage
     */
    public $oStorage;

    public function __construct()
    {
        parent::__construct(\Aurora\System\Api::GetModule('Core'), new Filecache\Storage($this));
    }

    /**
     * @param string $sUserPublicId
     * @param string $sKey
     * @param string $sValue
     * @param string $sFileSuffix Default value is empty string.
     * @param string $sFolder Default value is empty string.
     *
     * @return bool
     */
    public function put($sUserPublicId, $sKey, $sValue, $sFileSuffix = '', $sFolder = 'System')
    {
        $bResult = false;
        try {
            $bResult = $this->oStorage->put($sUserPublicId, $sKey, $sValue, $sFileSuffix, $sFolder);
        } catch (\Aurora\System\Exceptions\BaseException $oException) {
            $this->setLastException($oException);
        }
        return $bResult;
    }

    /**
     * @param string $sUserPublicId
     * @param string $sKey
     * @param resource $rSource
     * @param string $sFileSuffix Default value is empty string.
     * @param string $sFolder Default value is empty string.
     *
     * @return bool
     */
    public function putFile($sUserPublicId, $sKey, $rSource, $sFileSuffix = '', $sFolder = 'System')
    {
        $bResult = false;
        try {
            $bResult = $this->oStorage->putFile($sUserPublicId, $sKey, $rSource, $sFileSuffix, $sFolder);
        } catch (\Aurora\System\Exceptions\BaseException $oException) {
            $this->setLastException($oException);
        }
        return $bResult;
    }

    /**
     * @param string $sUserPublicId
     * @param string $sKey
     * @param string $sSource
     * @param string $sFileSuffix Default value is empty string.
     * @param string $sFolder Default value is empty string.
     *
     * @return bool
     */
    public function moveUploadedFile($sUserPublicId, $sKey, $sSource, $sFileSuffix = '', $sFolder = 'System')
    {
        $bResult = false;
        try {
            $bResult = $this->oStorage->moveUploadedFile($sUserPublicId, $sKey, $sSource, $sFileSuffix, $sFolder);
        } catch (\Aurora\System\Exceptions\BaseException $oException) {
            $this->setLastException($oException);
        }
        return $bResult;
    }

    /**
     * @param string $sUserPublicId
     * @param string $sKey
     * @param string $sFileSuffix Default value is empty string.
     * @param string $sFolder Default value is empty string.
     *
     * @return string|bool
     */
    public function get($sUserPublicId, $sKey, $sFileSuffix = '', $sFolder = 'System')
    {
        $mResult = false;
        try {
            $mResult = $this->oStorage->get($sUserPublicId, $sKey, $sFileSuffix, $sFolder);
        } catch (\Aurora\System\Exceptions\BaseException $oException) {
            $this->setLastException($oException);
        }
        return $mResult;
    }

    /**
     * @param string $sUserPublicId
     * @param string $sKey
     * @param string $sFileSuffix Default value is empty string.
     * @param string $sFolder Default value is empty string.
     *
     * @return resource|bool
     */
    public function getFile($sUserPublicId, $sKey, $sFileSuffix = '', $sFolder = 'System')
    {
        $mResult = false;
        try {
            $mResult = $this->oStorage->getFile($sUserPublicId, $sKey, $sFileSuffix, $sFolder);
        } catch (\Aurora\System\Exceptions\BaseException $oException) {
            $this->setLastException($oException);
        }
        return $mResult;
    }

    /**
     * @param string $sUserPublicId
     * @param string $sTempName
     * @param string $sMode Default value is empty string.
     *
     * @return resource|bool
     */
    public function getTempFile($sUserPublicId, $sTempName, $sMode = 'System')
    {
        $mResult = false;
        try {
            $mResult = $this->oStorage->getTempFile($sUserPublicId, $sTempName, $sMode);
        } catch (\Aurora\System\Exceptions\BaseException $oException) {
            $this->setLastException($oException);
        }
        return $mResult;
    }

    /**
     * @param string $sUserPublicId
     * @param string $sKey
     * @param string $sFileSuffix Default value is empty string.
     * @param string $sFolder Default value is empty string.
     *
     * @return bool
     */
    public function clear($sUserPublicId, $sKey, $sFileSuffix = '', $sFolder = 'System')
    {
        $bResult = false;
        try {
            $bResult = $this->oStorage->clear($sUserPublicId, $sKey, $sFileSuffix, $sFolder);
        } catch (\Aurora\System\Exceptions\BaseException $oException) {
            $this->setLastException($oException);
        }
        return $bResult;
    }

    /**
     * @param string $sUserPublicId
     * @param string $sKey
     * @param string $sFileSuffix Default value is empty string.
     * @param string $sFolder Default value is empty string.
     *
     * @return int|bool
     */
    public function fileSize($sUserPublicId, $sKey, $sFileSuffix = '', $sFolder = 'System')
    {
        $mResult = false;
        try {
            $mResult = $this->oStorage->fileSize($sUserPublicId, $sKey, $sFileSuffix, $sFolder);
        } catch (\Aurora\System\Exceptions\BaseException $oException) {
            $this->setLastException($oException);
        }
        return $mResult;
    }

    /**
     * @param string $sUserPublicId
     * @param string $sKey
     * @param string $sFileSuffix Default value is empty string.
     * @param string $sFolder Default value is empty string.
     *
     * @return bool
     */
    public function isFileExists($sUserPublicId, $sKey, $sFileSuffix = '', $sFolder = 'System')
    {
        $bResult = false;
        try {
            $bResult = $this->oStorage->isFileExists($sUserPublicId, $sKey, $sFileSuffix, $sFolder);
        } catch (\Aurora\System\Exceptions\BaseException $oException) {
            $this->setLastException($oException);
        }
        return $bResult;
    }

    /**
     * @param string $sUserPublicId
     * @param string $sKey
     * @param string $sFileSuffix Default value is empty string.
     * @param string $sFolder Default value is empty string.
     *
     * @return bool|string
     */
    public function generateFullFilePath($sUserPublicId, $sKey, $sFileSuffix = '', $sFolder = 'System')
    {
        $mResult = false;
        try {
            $mResult = $this->oStorage->generateFullFilePath($sUserPublicId, $sKey, $sFileSuffix, $sFolder);
        } catch (\Aurora\System\Exceptions\BaseException $oException) {
            $this->setLastException($oException);
        }
        return $mResult;
    }

    /**
     * @return bool
     */
    public function gc()
    {
        $bResult = false;
        try {
            $bResult = $this->oStorage->gc();
        } catch (\Aurora\System\Exceptions\BaseException $oException) {
            $this->setLastException($oException);
        }
        return $bResult;
    }
}
