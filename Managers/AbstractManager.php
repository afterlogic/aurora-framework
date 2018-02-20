<?php
/*
 * @copyright Copyright (c) 2017, Afterlogic Corp.
 * @license AGPL-3.0 or Afterlogic Software License
 *
 * This code is licensed under AGPLv3 license or Afterlogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

/**
 * @package Api
 */

namespace Aurora\System\Managers;

/**
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

	public function __construct(\Aurora\System\Module\AbstractModule $oModule = null)
	{
		$this->oSettings =& \Aurora\System\Api::GetSettings();
		$this->oLastException = null;
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
