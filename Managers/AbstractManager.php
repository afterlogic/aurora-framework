<?php
/*
 * @copyright Copyright (c) 2017, Afterlogic Corp.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 * 
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
		$this->oSettings =& \Aurora\System\Api::$oManager->GetSettings();
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
		return \Aurora\System\Api::$oManager->GetConnection();
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
