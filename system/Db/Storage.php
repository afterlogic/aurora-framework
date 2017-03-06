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
 * @static
 * @package Api
 * @subpackage Db
 */

namespace Aurora\System\Db;

/**
 * @package Api
 * @subpackage Db
 */
class Storage
{
	/**
	 * @var string
	 */
	protected $sPrefix;

	/**
	 * @var Sql
	 */
	protected $oConnector;

	/**
	 * @var Sql
	 */
	protected $oSlaveConnector;

	/**
	 * @var CApiDbException
	 */
	protected $oLastException;

	/**
	 * @var CApiSettings
	 */
	protected $oSettings;

	/**
	 * @param CApiSettings $oSettings
	 */
	public function __construct(\Aurora\System\Settings &$oSettings)
	{
		$aConnections =& Creator::CreateConnector($oSettings);

		$this->oSettings = $oSettings;
		$this->sPrefix = $this->oSettings->GetConf('DBPrefix');
		$this->oConnector = null;
		$this->oSlaveConnector = null;
		$this->oLastException = null;

		if (is_array($aConnections) && 2 === count($aConnections))
		{
			$this->oConnector =& $aConnections[0];
			if (null !== $aConnections[1])
			{
				$this->oSlaveConnector =& $aConnections[1];
			}
		}
	}

	/**
	 * @return &Sql
	 */
	public function &GetConnector()
	{
		return $this->oConnector;
	}

	/**
	 * @return &Sql
	 */
	public function &GetSlaveConnector()
	{
		return $this->oSlaveConnector;
	}

	/**
	 * @return bool
	 */
	public function IsConnected()
	{
		try
		{
			return $this->oConnector->IsConnected();
		}
		catch (CApiDbException $oException)
		{
			$this->SetException($oException);
		}

		return false;
	}

	/**
	 * @return bool
	 */
	public function Connect()
	{
		try
		{
			if ($this->oConnector->IsConnected())
			{
				return true;
			}

			$this->oConnector->ReInitIfNotConnected(
				$this->oSettings->GetConf('DBHost'),
				$this->oSettings->GetConf('DBLogin'),
				$this->oSettings->GetConf('DBPassword'),
				$this->oSettings->GetConf('DBName')
			);

			return $this->oConnector->Connect();
		}
		catch (CApiDbException $oException)
		{
			$this->SetException($oException);
		}

		return false;
	}

	/**
	 * @return bool
	 */
	public function ConnectSlave()
	{
		try
		{
			if ($this->oSlaveConnector->IsConnected())
			{
				return true;
			}

			$this->oSlaveConnector->ReInitIfNotConnected(
				$this->oSettings->GetConf('DBHost'),
				$this->oSettings->GetConf('DBLogin'),
				$this->oSettings->GetConf('DBPassword'),
				$this->oSettings->GetConf('DBName')
			);

			return $this->oSlaveConnector->Connect(true, true);
		}
		catch (CApiDbException $oException)
		{
			$this->SetException($oException);
		}

		return false;
	}

	/**
	 * @return bool
	 */
	public function ConnectNoSelect()
	{
		try
		{
			if ($this->oConnector->IsConnected())
			{
				return true;
			}
			return $this->oConnector->ConnectNoSelect();
		}
		catch (CApiDbException $oException)
		{
			$this->SetException($oException);
		}

		return false;
	}

	/**
	 * @return bool
	 */
	public function Disconnect()
	{
		try
		{
			$this->oConnector->Disconnect();
			if ($this->oSlaveConnector)
			{
				$this->oSlaveConnector->Disconnect();
			}
			return true;
		}
		catch (CApiDbException $oException)
		{
			$this->SetException($oException);
		}

		return false;
	}

	/**
	 * @return bool
	 */
	public function Select()
	{
		try
		{
			return $this->oConnector->Select();
		}
		catch (CApiDbException $oException)
		{
			$this->SetException($oException);
		}

		return false;
	}

	/**
	 * @return bool
	 */
	public function Execute($sSql)
	{
		$bResult = false;
		try
		{
			if (!empty($sSql))
			{
				if ($this->oSlaveConnector && $this->isSlaveSql($sSql))
				{
					if ($this->ConnectSlave())
					{
						$bResult = $this->oSlaveConnector->Execute($sSql, true);
					}
				}
				else
				{
					if ($this->Connect())
					{
						$bResult = $this->oConnector->Execute($sSql);
					}
				}
			}
		}
		catch (CApiDbException $oException)
		{
			$this->SetException($oException);
		}

		return $bResult;
	}

	/**
	 * @param bool $bAutoFree = true
	 * @return bool
	 */
	public function GetNextArrayRecord($bAutoFree = true)
	{
		try
		{
			if ($this->oSlaveConnector)
			{
				return $this->oSlaveConnector->GetNextArrayRecord($bAutoFree);
			}
			return $this->oConnector->GetNextArrayRecord($bAutoFree);
		}
		catch (CApiDbException $oException)
		{
			$this->SetException($oException);
		}
		return false;
	}

	/**
	 * @param bool $bAutoFree = true
	 * @return bool
	 */
	public function GetNextRecord($bAutoFree = true)
	{
		try
		{
			if ($this->oSlaveConnector)
			{
				return $this->oSlaveConnector->GetNextRecord($bAutoFree);
			}
			
			return $this->oConnector->GetNextRecord($bAutoFree);
		}
		catch (CApiDbException $oException)
		{
			$this->SetException($oException);
		}
		return false;
	}

	/**
	 * @return bool
	 */
	public function FreeResult()
	{
		try
		{
			if ($this->oSlaveConnector)
			{
				return $this->oSlaveConnector->FreeResult();
			}
			
			return $this->oConnector->FreeResult();
		}
		catch (CApiDbException $oException)
		{
			$this->SetException($oException);
		}
		return false;
	}

	/**
	 * @return array|bool [object]
	 */
	public function GetResultAsObjects()
	{
		$aResult = array();
		while (false !== ($oRow = $this->GetNextRecord()))
		{
			$aResult[] = $oRow;
		}
		return $aResult;
	}

	/**
	 * @return array|bool [array]
	 */
	public function GetResultAsAssocArrays()
	{
		$aResult = array();
		while (false !== ($aRow = $this->GetNextArrayRecord()))
		{
			$aResult[] = $aRow;
		}
		return $aResult;
	}

	/**
	 * @param string $sTableName = null
	 * @param string $sFieldName = null
	 * @return int
	 */
	public function GetLastInsertId($sTableName = null, $sFieldName = null)
	{
		try
		{
			return $this->oConnector->GetLastInsertId($sTableName, $sFieldName);
		}
		catch (CApiDbException $oException)
		{
			$this->SetException($oException);
		}
		
		return false;
	}

	/**
	 * @return int
	 */
	public function ResultCount()
	{
		try
		{
			if ($this->oSlaveConnector)
			{
				return $this->oSlaveConnector->ResultCount();
			}
			return $this->oConnector->ResultCount();
		}
		catch (CApiDbException $oException)
		{
			$this->SetException($oException);
		}
		return false;
	}

	/**
	 * @return array
	 */
	public function GetTableNames()
	{
		$aResult = false;
		if ($this->Connect())
		{
			try
			{
				$aResult = $this->oConnector->GetTableNames();
			}
			catch (CApiDbException $oException)
			{
				$this->SetException($oException);
			}
		}
		return $aResult;
	}

	/**
	 * @param string $sTableName
	 * @return array
	 */
	public function GetTableFields($sTableName)
	{
		$aResult = false;
		if ($this->Connect())
		{
			try
			{
				$aResult = $this->oConnector->GetTableFields($sTableName);
			}
			catch (CApiDbException $oException)
			{
				$this->SetException($oException);
			}
		}
		return $aResult;
	}

	/**
	 * @param string $sTableName
	 * @return array
	 */
	public function GetTableIndexes($sTableName)
	{
		$aResult = false;
		if ($this->Connect())
		{
			try
			{
				$aResult = $this->oConnector->GetTableIndexes($sTableName);
			}
			catch (CApiDbException $oException)
			{
				$this->SetException($oException);
			}
		}
		return $aResult;
	}

	/**
	 * @return string
	 */
	public function prefix()
	{
		return $this->sPrefix;
	}

	/**
	 * @return string
	 */
	public function GetError()
	{
		return '#'.$this->oConnector->ErrorCode.': '.$this->oConnector->ErrorDesc;
	}

	/**
	 * @return CApiDbException
	 */
	public function GetException()
	{
		return $this->oLastException;
	}

	/**
	 * @param CApiDbException $oException
	 */
	public function SetException($oException)
	{
		$this->oLastException = $oException;
	}

	/**
	 * @param string $sSql
	 * @return bool
	 */
	protected function isSlaveSql($sSql)
	{
		return in_array(strtoupper(substr(trim($sSql), 0, 6)), array('SELECT'));
	}
}