<?php
/**
 * This code is licensed under AGPLv3 license or Afterlogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

namespace Aurora\System\Managers\Filecache;

/**
 * @license https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @license https://afterlogic.com/products/common-licensing Afterlogic Software License
 * @copyright Copyright (c) 2019, Afterlogic Corp.
 *
 * @package Filecache
 * @subpackage Storages
 */
class Storage extends \Aurora\System\Managers\AbstractStorage
{
    /**
     * @var string
     */
    protected $sDataPath;

    /**
     * @var string
     */
    protected $sPath;

    /**
     * @param \Aurora\System\Managers\AbstractManager &$oManager
     */
    public function __construct(\Aurora\System\Managers\AbstractManager &$oManager)
    {
        parent::__construct($oManager);

        $this->sDataPath = rtrim(trim(\Aurora\System\Api::DataPath()), '\\/');
        $this->sPath = '/temp/.cache/';
    }

    /**
     * @param string $sPath
     */
    public function setPath($sPath)
    {
        $this->sPath = $sPath;
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
    public function put($sUserPublicId, $sKey, $sValue, $sFileSuffix = '', $sFolder = '')
    {
        return false !== @file_put_contents(
            $this->generateFileName($sUserPublicId, $sKey, true, $sFileSuffix, $sFolder),
            $sValue
        );
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
    public function putFile($sUserPublicId, $sKey, $rSource, $sFileSuffix = '', $sFolder = '')
    {
        $bResult = false;
        if ($rSource) {
            $rOpenOutput = @fopen($this->generateFileName($sUserPublicId, $sKey, true, $sFileSuffix, $sFolder), 'w+b');
            if ($rOpenOutput) {
                $bResult = (false !== \MailSo\Base\Utils::MultipleStreamWriter($rSource, array($rOpenOutput)));
                @fclose($rOpenOutput);
            }
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
    public function moveUploadedFile($sUserPublicId, $sKey, $sSource, $sFileSuffix = '', $sFolder = '')
    {
        return @move_uploaded_file(
            $sSource,
            $this->generateFileName($sUserPublicId, $sKey, true, $sFileSuffix, $sFolder)
        );
    }

    /**
     * @param string $sUserPublicId
     * @param string $sKey
     * @param string $sFileSuffix Default value is empty string.
     * @param string $sFolder Default value is empty string.
     *
     * @return string|bool
     */
    public function get($sUserPublicId, $sKey, $sFileSuffix = '', $sFolder = '')
    {
        return @file_get_contents($this->generateFileName($sUserPublicId, $sKey, false, $sFileSuffix, $sFolder));
    }

    /**
     * @param string $sUserPublicId
     * @param string $sKey
     * @param string $sFileSuffix Default value is empty string.
     * @param string $sFolder Default value is empty string.
     *
     * @return resource|bool
     */
    public function getFile($sUserPublicId, $sKey, $sFileSuffix = '', $sFolder = '')
    {
        $mResult = false;
        $sFileName = $this->generateFileName($sUserPublicId, $sKey, false, $sFileSuffix, $sFolder);
        if (@file_exists($sFileName)) {
            $mResult = @fopen($sFileName, 'rb');
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
    public function getTempFile($sUserPublicId, $sTempName, $sMode = '')
    {
        return @fopen($this->generateFileName($sUserPublicId, $sTempName, true), $sMode);
    }

    /**
     * @param string $sUserPublicId
     * @param string $sKey
     * @param string $sFileSuffix Default value is empty string.
     * @param string $sFolder Default value is empty string.
     *
     * @return bool
     */
    public function clear($sUserPublicId, $sKey, $sFileSuffix = '', $sFolder = '')
    {
        $bResult = false;
        $sFileName = $this->generateFileName($sUserPublicId, $sKey, false, $sFileSuffix, $sFolder);
        if (@file_exists($sFileName)) {
            $bResult = @unlink($sFileName);
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
    public function fileSize($sUserPublicId, $sKey, $sFileSuffix = '', $sFolder = '')
    {
        return @filesize($this->generateFileName($sUserPublicId, $sKey, false, $sFileSuffix, $sFolder));
    }

    /**
     * @param string $sUserPublicId
     * @param string $sKey
     * @param string $sFileSuffix Default value is empty string.
     * @param string $sFolder Default value is empty string.
     *
     * @return bool
     */
    public function isFileExists($sUserPublicId, $sKey, $sFileSuffix = '', $sFolder = '')
    {
        return @file_exists($this->generateFileName($sUserPublicId, $sKey, false, $sFileSuffix, $sFolder));
    }

    /**
     * @param string $sUserPublicId
     * @param string $sKey
     * @param bool $bMkDir Default value is **false**.
     * @param string $sFileSuffix Default value is empty string.
     * @param string $sFolder Default value is empty string.
     *
     * @throws \Aurora\System\Exceptions\Exception
     *
     * @return string
     */
    protected function generateFileName($sUserPublicId, $sKey, $bMkDir = false, $sFileSuffix = '', $sFolder = '')
    {
        $sEmailMd5 = md5(strtolower($sUserPublicId));

        $sKeyPath = md5($sKey);
        $sKeyPath = substr($sKeyPath, 0, 2) . '/' . $sKeyPath;
        if (!empty($sFolder)) {
            $sFolder = $sFolder . '/';
        }
        $sFilePath = $this->sDataPath . $this->sPath . $sFolder . substr($sEmailMd5, 0, 2) . '/' . $sEmailMd5 . '/' . $sKeyPath . $sFileSuffix;
        if ($bMkDir && !@is_dir(dirname($sFilePath))) {
            if (!@mkdir(dirname($sFilePath), 0777, true)) {
                throw new \Aurora\System\Exceptions\Exception('Can\'t make storage directory "' . $sFilePath . '"');
            }
        }

        return $sFilePath;
    }

    /**
     * @param string $sUserPublicId
     * @param string $sKey
     * @param string $sFileSuffix Default value is empty string.
     * @param string $sFolder Default value is empty string.
     *
     * @return string
     */
    public function generateFullFilePath($sUserPublicId, $sKey, $sFileSuffix = '', $sFolder = '')
    {
        return $this->generateFileName($sUserPublicId, $sKey, true, $sFileSuffix, $sFolder);
    }

    /**
     * @return bool
     */
    public function gc()
    {
        return \MailSo\Base\Utils::RecTimeDirRemove(
            $this->sDataPath . $this->sPath,
            60 * 60 * 6,
            time()
        );
    }
}
