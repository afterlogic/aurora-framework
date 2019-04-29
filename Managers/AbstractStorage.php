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
abstract class AbstractStorage
{
	/**
	 * @var \Aurora\System\Managers\AbstractManager
	 */
	protected $oManager;

	/**
	 * @var \Aurora\System\Settings
	 */
	protected $oSettings;

	/**
	 * @var \Aurora\System\Exceptions\BaseException
	 */
	protected $oLastException;

	public function __construct(AbstractManager &$oManager)
	{
		$this->oManager = $oManager;
		$this->oSettings =& \Aurora\System\Api::GetSettings();
		$this->oLastException = null;
	}

	/**
	 * @return &\Aurora\System\Settings
	 */
	public function &GetSettings()
	{
		return $this->oSettings;
	}

	/**
	 * @return \Aurora\System\Exceptions\BaseException
	 */
	public function GetStorageException()
	{
		return $this->oLastException;
	}

	/**
	 * @param \Aurora\System\Exceptions\BaseException $oException
	 */
	public function SetStorageException($oException)
	{
		$this->oLastException = $oException;
	}

	/**
	 * @todo move to db storage
	 */
	protected function throwDbExceptionIfExist()
	{
		// connection in db storage
		if ($this->oConnection)
		{
			$oException = $this->oConnection->GetException();
			if ($oException instanceof \Aurora\System\Exceptions\DbException)
			{
				throw new \Aurora\System\Exceptions\BaseException(\Aurora\System\Exceptions\Errs::Db_ExceptionError, $oException);
			}
		}
	}
}
