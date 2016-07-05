<?php

/* -AFTERLOGIC LICENSE HEADER- */

/**
 * @package Filecache
 * @subpackage Storages
 */
class CApiFilecacheStorage extends AApiManagerStorage
{
	/**
	 * @var string
	 */
	protected $sDataPath;

	/**
	 * @param AApiManager &$oManager
	 */
	public function __construct($sStorageName, AApiManager &$oManager)
	{
		parent::__construct('filecache', $sStorageName, $oManager);

		$this->sDataPath = rtrim(trim(CApi::DataPath()), '\\/');
	}

	/**
	 * @param int $iUserId
	 * @param string $sKey
	 * @param string $sValue
	 * @param string $sFileSuffix Default value is empty string.
	 * @param string $sFolder Default value is empty string.
	 *
	 * @return bool
	 */
	public function put($iUserId, $sKey, $sValue, $sFileSuffix = '', $sFolder = '')
	{
		return false !== @file_put_contents(
			$this->generateFileName($iUserId, $sKey, true, $sFileSuffix, $sFolder), $sValue);
	}

	/**
	 * @param int $iUserId
	 * @param string $sKey
	 * @param resource $rSource
	 * @param string $sFileSuffix Default value is empty string.
	 * @param string $sFolder Default value is empty string.
	 *
	 * @return bool
	 */
	public function putFile($iUserId, $sKey, $rSource, $sFileSuffix = '', $sFolder = '')
	{
		$bResult = false;
		if ($rSource)
		{
			$rOpenOutput = @fopen($this->generateFileName($iUserId, $sKey, true, $sFileSuffix, $sFolder), 'w+b');
			if ($rOpenOutput)
			{
				$bResult = (false !== \MailSo\Base\Utils::MultipleStreamWriter($rSource, array($rOpenOutput)));
				@fclose($rOpenOutput);
			}
		}
		return $bResult;
	}

	/**
	 * @param int $iUserId
	 * @param string $sKey
	 * @param string $sSource
	 * @param string $sFileSuffix Default value is empty string.
	 * @param string $sFolder Default value is empty string.
	 *
	 * @return bool
	 */
	public function moveUploadedFile($iUserId, $sKey, $sSource, $sFileSuffix = '', $sFolder = '')
	{
		return @move_uploaded_file($sSource,
			$this->generateFileName($iUserId, $sKey, true, $sFileSuffix, $sFolder));
	}

	/**
	 * @param int $iUserId
	 * @param string $sKey
	 * @param string $sFileSuffix Default value is empty string.
	 * @param string $sFolder Default value is empty string.
	 *
	 * @return string|bool
	 */
	public function get($iUserId, $sKey, $sFileSuffix = '', $sFolder = '')
	{
		return @file_get_contents($this->generateFileName($iUserId, $sKey, false, $sFileSuffix, $sFolder));
	}

	/**
	 * @param int $iUserId
	 * @param string $sKey
	 * @param string $sFileSuffix Default value is empty string.
	 * @param string $sFolder Default value is empty string.
	 *
	 * @return resource|bool
	 */
	public function getFile($iUserId, $sKey, $sFileSuffix = '', $sFolder = '')
	{
		$mResult = false;
		$sFileName = $this->generateFileName($iUserId, $sKey, false, $sFileSuffix, $sFolder);
		if (@file_exists($sFileName))
		{
			$mResult = @fopen($sFileName, 'rb');
		}
		return $mResult;
	}

	/**
	 * @param int $iUserId
	 * @param string $sTempName
	 * @param string $sMode Default value is empty string.
	 *
	 * @return resource|bool
	 */
	public function getTempFile($iUserId, $sTempName, $sMode = '')
	{
		return @fopen($this->generateFileName($iUserId, $sTempName, true), $sMode);
	}

	/**
	 * @param int $iUserId
	 * @param string $sKey
	 * @param string $sFileSuffix Default value is empty string.
	 * @param string $sFolder Default value is empty string.
	 *
	 * @return bool
	 */
	public function clear($iUserId, $sKey, $sFileSuffix = '', $sFolder = '')
	{
		$mResult = false;
		$sFileName = $this->generateFileName($iUserId, $sKey, false, $sFileSuffix, $sFolder);
		if (@file_exists($sFileName))
		{
			$mResult = @unlink($sFileName);
		}
		return $mResult;
	}

	/**
	 * @param int $iUserId
	 * @param string $sKey
	 * @param string $sFileSuffix Default value is empty string.
	 * @param string $sFolder Default value is empty string.
	 *
	 * @return int|bool
	 */
	public function fileSize($iUserId, $sKey, $sFileSuffix = '', $sFolder = '')
	{
		return @filesize($this->generateFileName($iUserId, $sKey, false, $sFileSuffix));
	}

	/**
	 * @param int $iUserId
	 * @param string $sKey
	 * @param string $sFileSuffix Default value is empty string.
	 * @param string $sFolder Default value is empty string.
	 *
	 * @return bool
	 */
	public function isFileExists($iUserId, $sKey, $sFileSuffix = '', $sFolder = '')
	{
		return @file_exists($this->generateFileName($iUserId, $sKey, false, $sFileSuffix, $sFolder));
	}

	/**
	 * @param int $iUserId
	 * @param string $sKey
	 * @param bool $bMkDir Default value is **false**.
	 * @param string $sFileSuffix Default value is empty string.
	 * @param string $sFolder Default value is empty string.
	 *
	 * @throws \System\Exceptions\Exception
	 *
	 * @return string
	 */
	protected function generateFileName($iUserId, $sKey, $bMkDir = false, $sFileSuffix = '', $sFolder = '')
	{
		$sEmailMd5 = md5(strtolower($iUserId));

		$sKeyPath = md5($sKey);
		$sKeyPath = substr($sKeyPath, 0, 2).'/'.$sKeyPath;
		if (!empty($sFolder))
		{
			$sKeyPath = $sFolder . '/' . $sKeyPath;
		}
		$sFilePath = $this->sDataPath.'/temp/.cache/'.substr($sEmailMd5, 0, 2).'/'.$sEmailMd5.'/'.$sKeyPath.$sFileSuffix;
		if ($bMkDir && !@is_dir(dirname($sFilePath)))
		{
			if (!@mkdir(dirname($sFilePath), 0777, true))
			{
				throw new \System\Exceptions\Exception('Can\'t make storage directory "'.$sFilePath.'"');
			}
		}

		return $sFilePath;
	}

	/**
	 * @param int $iUserId
	 * @param string $sKey
	 * @param string $sFileSuffix Default value is empty string.
	 * @param string $sFolder Default value is empty string.
	 *
	 * @return string
	 */
	public function generateFullFilePath($iUserId, $sKey, $sFileSuffix = '', $sFolder = '')
	{
		return $this->generateFileName($iUserId, $sKey, true, $sFileSuffix, $sFolder);
	}

	/**
	 * @return bool
	 */
	public function gc()
	{
		return \MailSo\Base\Utils::RecTimeDirRemove($this->sDataPath.'/temp/.cache/', 60 * 60 * 6, time());
	}
}