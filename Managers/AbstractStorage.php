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

namespace Aurora\System\Managers;

/**
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
	
	/**
	 * Executes queries from sql file.
	 * 
	 * @param string $sFilePath Path to sql file.
	 * 
	 * @return boolean
	 */
	public function executeSqlFile($sFilePath)
	{
		$bResult = false;
		
		$sDbPrefix = $this->oCommandCreator->prefix();
		
		$mFileContent = file_exists($sFilePath) ? file_get_contents($sFilePath) : false;

		if ($mFileContent && $this->oConnection)
		{
			$aSqlStrings = explode(';', $mFileContent);
			foreach ($aSqlStrings as $sSql)
			{
				$sPrepSql = trim(str_replace('%PREFIX%', $sDbPrefix, $sSql));
				if (!empty($sPrepSql))
				{
					$bResult = $this->oConnection->Execute($sPrepSql);
				}
				$this->throwDbExceptionIfExist();
			}
		}

		return $bResult;
	}
}
