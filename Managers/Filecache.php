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
	 *
	 * @param string $sForcedStorage
	 */
	public function __construct()
	{
		parent::__construct(\Aurora\System\Api::GetModule('Core'), new Filecache\Storage($this));
	}

	/**
	 * @param \Aurora\Modules\StandardAuth\Classes\Account|CHelpdeskUser $oAccount
	 * @param string $sKey
	 * @param string $sValue
	 * @param string $sFileSuffix Default value is empty string.
	 * @param string $sFolder Default value is empty string.
	 *
	 * @return bool
	 */
	public function put($oAccount, $sKey, $sValue, $sFileSuffix = '', $sFolder = 'System')
	{
		$bResult = false;
		try
		{
			$bResult = $this->oStorage->put($oAccount, $sKey, $sValue, $sFileSuffix, $sFolder);
		}
		catch (\Aurora\System\Exceptions\BaseException $oException)
		{
			$this->setLastException($oException);
		}
		return $bResult;
	}

	/**
	 * @param \Aurora\Modules\StandardAuth\Classes\Account|CHelpdeskUser $oAccount
	 * @param string $sKey
	 * @param resource $rSource
	 * @param string $sFileSuffix Default value is empty string.
	 * @param string $sFolder Default value is empty string.
	 *
	 * @return bool
	 */
	public function putFile($oAccount, $sKey, $rSource, $sFileSuffix = '', $sFolder = 'System')
	{
		$bResult = false;
		try
		{
			$bResult = $this->oStorage->putFile($oAccount, $sKey, $rSource, $sFileSuffix, $sFolder);
		}
		catch (\Aurora\System\Exceptions\BaseException $oException)
		{
			$this->setLastException($oException);
		}
		return $bResult;
	}

	/**
	 * @param string $sUUID
	 * @param string $sKey
	 * @param string $sSource
	 * @param string $sFileSuffix Default value is empty string.
	 * @param string $sFolder Default value is empty string.
	 *
	 * @return bool
	 */
	public function moveUploadedFile($sUUID, $sKey, $sSource, $sFileSuffix = '', $sFolder = 'System')
	{
		$bResult = false;
		try
		{
			$bResult = $this->oStorage->moveUploadedFile($sUUID, $sKey, $sSource, $sFileSuffix, $sFolder);
		}
		catch (\Aurora\System\Exceptions\BaseException $oException)
		{
			$this->setLastException($oException);
		}
		return $bResult;
	}

	/**
	 * @param \Aurora\Modules\StandardAuth\Classes\Account|CHelpdeskUser $oAccount
	 * @param string $sKey
	 * @param string $sFileSuffix Default value is empty string.
	 * @param string $sFolder Default value is empty string.
	 *
	 * @return string|bool
	 */
	public function get($oAccount, $sKey, $sFileSuffix = '', $sFolder = 'System')
	{
		$mResult = false;
		try
		{
			$mResult = $this->oStorage->get($oAccount, $sKey, $sFileSuffix, $sFolder);
		}
		catch (\Aurora\System\Exceptions\BaseException $oException)
		{
			$this->setLastException($oException);
		}
		return $mResult;
	}

	/**
	 * @param \Aurora\Modules\StandardAuth\Classes\Account|CHelpdeskUser $oAccount
	 * @param string $sKey
	 * @param string $sFileSuffix Default value is empty string.
	 * @param string $sFolder Default value is empty string.
	 *
	 * @return resource|bool
	 */
	public function getFile($oAccount, $sKey, $sFileSuffix = '', $sFolder = 'System')
	{
		$mResult = false;
		try
		{
			$mResult = $this->oStorage->getFile($oAccount, $sKey, $sFileSuffix, $sFolder);
		}
		catch (\Aurora\System\Exceptions\BaseException $oException)
		{
			$this->setLastException($oException);
		}
		return $mResult;
	}

	/**
	 * @param \Aurora\Modules\StandardAuth\Classes\Account|CHelpdeskUser $oAccount
	 * @param string $sTempName
	 * @param string $sMode Default value is empty string.
	 *
	 * @return resource|bool
	 */
	public function getTempFile($oAccount, $sTempName, $sMode = 'System')
	{
		$mResult = false;
		try
		{
			$mResult = $this->oStorage->getTempFile($oAccount, $sTempName, $sMode);
		}
		catch (\Aurora\System\Exceptions\BaseException $oException)
		{
			$this->setLastException($oException);
		}
		return $mResult;
	}

	/**
	 * @param string $sUUID
	 * @param string $sKey
	 * @param string $sFileSuffix Default value is empty string.
	 * @param string $sFolder Default value is empty string.
	 *
	 * @return bool
	 */
	public function clear($sUUID, $sKey, $sFileSuffix = '', $sFolder = 'System')
	{
		$bResult = false;
		try
		{
			$bResult = $this->oStorage->clear($sUUID, $sKey, $sFileSuffix, $sFolder);
		}
		catch (\Aurora\System\Exceptions\BaseException $oException)
		{
			$this->setLastException($oException);
		}
		return $bResult;
	}

	/**
	 * @param \Aurora\Modules\StandardAuth\Classes\Account|CHelpdeskUser $oAccount
	 * @param string $sKey
	 * @param string $sFileSuffix Default value is empty string.
	 * @param string $sFolder Default value is empty string.
	 *
	 * @return int|bool
	 */
	public function fileSize($oAccount, $sKey, $sFileSuffix = '', $sFolder = 'System')
	{
		$mResult = false;
		try
		{
			$mResult = $this->oStorage->fileSize($oAccount, $sKey, $sFileSuffix, $sFolder);
		}
		catch (\Aurora\System\Exceptions\BaseException $oException)
		{
			$this->setLastException($oException);
		}
		return $mResult;
	}

	/**
	 * @param \Aurora\Modules\StandardAuth\Classes\Account|CHelpdeskUser $oAccount
	 * @param string $sKey
	 * @param string $sFileSuffix Default value is empty string.
	 * @param string $sFolder Default value is empty string.
	 *
	 * @return bool
	 */
	public function isFileExists($oAccount, $sKey, $sFileSuffix = '', $sFolder = 'System')
	{
		$bResult = false;
		try
		{
			$bResult = $this->oStorage->isFileExists($oAccount, $sKey, $sFileSuffix, $sFolder);
		}
		catch (\Aurora\System\Exceptions\BaseException $oException)
		{
			$this->setLastException($oException);
		}
		return $bResult;
	}

	/**
	 * @param string $sUUID
	 * @param string $sKey
	 * @param string $sFileSuffix Default value is empty string.
	 * @param string $sFolder Default value is empty string.
	 *
	 * @return bool|string
	 */
	public function generateFullFilePath($sUUID, $sKey, $sFileSuffix = '', $sFolder = 'System')
	{
		$mResult = false;
		try
		{
			$mResult = $this->oStorage->generateFullFilePath($sUUID, $sKey, $sFileSuffix, $sFolder);
		}
		catch (\Aurora\System\Exceptions\BaseException $oException)
		{
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
		try
		{
			$bResult = $this->oStorage->gc();
		}
		catch (\Aurora\System\Exceptions\BaseException $oException)
		{
			$this->setLastException($oException);
		}
		return $bResult;
	}
}
