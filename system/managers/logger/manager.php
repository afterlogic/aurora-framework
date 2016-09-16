<?php
/*
 * @copyright Copyright (c) 2016, Afterlogic Corp.
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
 * @package Logger
 */
class CApiLoggerManager extends AApiManager
{
	/**
	 * @var string
	 */
	protected $sLogFileName;

	/**
	 * @var string
	 */
	protected $sCurrentUserLogFileName;

	/**
	 * @var string
	 */
	protected $sLogFile;

	/**
	 * @var string
	 */
	protected $sCurrentUserLogFile;

	/**
	 * @param CApiGlobalManager &$oManager
	 */
	public function __construct(CApiGlobalManager &$oManager, $sForcedStorage = '')
	{
		parent::__construct('logger', $oManager);

		$sS = CApi::GetConf('log.custom-full-path', '');
		$sPrePath = empty($sS) ? CApi::DataPath().'/logs/' : rtrim(trim($sS), '\\/').'/';

		$this->sLogFileName = CApi::GetConf('log.log-file', 'log.txt');
		$this->sLogFile = $sPrePath.$this->sLogFileName;

		$this->sCurrentUserLogFileName = CApi::GetConf('log.event-file', 'event.txt');
		$this->sCurrentUserLogFile = $sPrePath.$this->sCurrentUserLogFileName;
	}

	/**
	 * @return string
	 */
	public function getLogName()
	{
		return $this->sLogFileName;
	}

	/**
	 * @return string
	 */
	public function getCurrentUserActivityLogName()
	{
		return $this->sCurrentUserLogFileName;
	}

	/**
	 * @return int|bool
	 */
	public function getCurrentLogSize()
	{
		return @filesize($this->sLogFile);
	}

	/**
	 * @return int|bool
	 */
	public function getCurrentUserActivityLogSize()
	{
		return @filesize($this->sCurrentUserLogFile);
	}

	/**
	 * @return bool
	 */
	public function deleteCurrentLog()
	{
		return $this->_deleteSomeFile($this->sLogFile);
	}

	/**
	 * @return bool
	 */
	public function deleteCurrentUserActivityLog()
	{
		return $this->_deleteSomeFile($this->sCurrentUserLogFile);
	}

	/**
	 * @param int &$iSize = 0
	 * @return bool|resource
	 */
	public function getCurrentLogStream(&$iSize = 0)
	{
		return $this->_getSomeFileStream($this->sLogFile, $iSize);
	}

	/**
	 * @param int &$iSize = 0
	 * 
	 * @return bool|resource
	 */
	public function getCurrentUserActivityLogStream(&$iSize = 0)
	{
		return $this->_getSomeFileStream($this->sCurrentUserLogFile, $iSize);
	}

	/**
	 * @param string $sFileFullPath
	 * @param int &$iSize
	 * 
	 * @return bool|resource
	 */
	protected function _getSomeFileStream($sFileFullPath, &$iSize)
	{
		$rResult = false;
		if (@file_exists($sFileFullPath))
		{
			$iSize = filesize($sFileFullPath);
			$rResult = fopen($sFileFullPath, 'rw+');
		}
		else
		{
			$iSize = false;
		}

		return $rResult;
	}

	/**
	 * @param string $sFileFullPath
	 * 
	 * @return bool
	 */
	protected function _deleteSomeFile($sFileFullPath)
	{
		return (@file_exists($sFileFullPath)) ? @unlink($sFileFullPath) : true;
	}
}
