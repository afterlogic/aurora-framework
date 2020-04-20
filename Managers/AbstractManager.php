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
abstract class AbstractManager
{
	/**
	 * @var \Aurora\System\Exceptions\ManagerException
	 */
	protected $oLastException;

	/**
	 * @var \Aurora\System\Module\AbstractModule
	 */
	protected $oModule;

	/**
	 * @var \Aurora\System\Settings
	 */
	protected $oSettings;

	/**
	 *
	 * @param \Aurora\System\Module\AbstractModule $oModule
	 */
	public function __construct(\Aurora\System\Module\AbstractModule $oModule)
	{
		$this->oModule = $oModule;
	}

	/**
	 * @return \Aurora\System\Module\AbstractModule
	 */
	public function GetModule()
	{
		return $this->oModule;
	}

	/**
	 * @return &\Aurora\System\Settings
	 */
	public function &GetSettings()
	{
		return $this->oSettings;
	}

	public function &GetConnection()
	{
		return \Aurora\System\Api::GetConnection();
	}

	/**
	 * @param Exception $oException
	 * @param bool $bLog = true
	 */
	protected function setLastException(\Exception $oException, $bLog = true)
	{
		$this->oLastException = $oException;

		if ($bLog)
		{
			$sFile = str_replace(
				str_replace('\\', '/', strtolower(realpath(\Aurora\System\Api::WebMailPath()))), '~ ',
				str_replace('\\', '/', strtolower($oException->getFile())));

			\Aurora\System\Api::Log('Exception['.$oException->getCode().']: '.$oException->getMessage().
				AU_API_CRLF.$sFile.' ('.$oException->getLine().')'.
				AU_API_CRLF.'----------------------------------------------------------------------'.
				AU_API_CRLF.$oException->getTraceAsString(), \Aurora\System\Enums\LogLevel::Error);
		}
	}

	/**
	 * @return Exception
	 */
	public function GetLastException()
	{
		return $this->oLastException;
	}

	/**
	 * @return int
	 */
	public function getLastErrorCode()
	{
		$iResult = 0;
		if (null !== $this->oLastException)
		{
			$iResult = $this->oLastException->getCode();
		}
		return $iResult;
	}

	/**
	 * @return string
	 */
	public function GetLastErrorMessage()
	{
		$sResult = '';
		if (null !== $this->oLastException)
		{
			$sResult = $this->oLastException->getMessage();
		}
		return $sResult;
	}
}
