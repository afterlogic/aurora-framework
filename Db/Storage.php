<?php
/**
 * This code is licensed under AGPLv3 license or Afterlogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

namespace Aurora\System\Db;

/**
 * @license https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @license https://afterlogic.com/products/common-licensing Afterlogic Software License
 * @copyright Copyright (c) 2019, Afterlogic Corp.
 *
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
	 * @var \Aurora\System\Exceptions\DbException
	 */
	protected $oLastException;

	/**
	 * @var \Aurora\System\Settings
	 */
	protected $oSettings;

	/**
	 * @param \Aurora\System\Settings $oSettings
	 */
	public function __construct(\Aurora\System\AbstractSettings &$oSettings)
	{
		$aConnections =& Creator::CreateConnector($oSettings);

		$this->oSettings = $oSettings;
		$this->sPrefix = $this->oSettings->DBPrefix;
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
		return $this->oConnector->IsConnected();
	}

	/**
	 * @return bool
	 */
	public function Connect()
	{
		if (!isset($this->oConnector))
		{
			return false;
		}

		if ($this->oConnector->IsConnected())
		{
			return true;
		}

		$this->oConnector->ReInitIfNotConnected(
			$this->oSettings->DBHost,
			$this->oSettings->DBLogin,
			$this->oSettings->DBPassword,
			$this->oSettings->DBName
		);

		return $this->oConnector->Connect();
	}

	/**
	 * @return bool
	 */
	public function ConnectSlave()
	{
		if ($this->oSlaveConnector->IsConnected())
		{
			return true;
		}

		$this->oSlaveConnector->ReInitIfNotConnected(
			$this->oSettings->DBHost,
			$this->oSettings->DBLogin,
			$this->oSettings->DBPassword,
			$this->oSettings->DBName
		);

		return $this->oSlaveConnector->Connect(true, true);
	}

	/**
	 * @return bool
	 */
	public function ConnectNoSelect()
	{
		if ($this->oConnector->IsConnected())
		{
			return true;
		}
		return $this->oConnector->ConnectNoSelect();
	}

	/**
	 * @return bool
	 */
	public function Disconnect()
	{
		$this->oConnector->Disconnect();
		if ($this->oSlaveConnector)
		{
			$this->oSlaveConnector->Disconnect();
		}
		return true;
	}

	/**
	 * @return bool
	 */
	public function Select()
	{
		return $this->oConnector->Select();
	}

	/**
	 * @return bool
	 */
	public function Execute($sSql)
	{
		$bResult = false;
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

		return $bResult;
	}

	/**
	 * @param bool $bAutoFree = true
	 * @return bool
	 */
	public function GetNextArrayRecord($bAutoFree = true)
	{
		if ($this->oSlaveConnector)
		{
			return $this->oSlaveConnector->GetNextArrayRecord($bAutoFree);
		}
		return $this->oConnector->GetNextArrayRecord($bAutoFree);
	}

	/**
	 * @param bool $bAutoFree = true
	 * @return bool
	 */
	public function GetNextRecord($bAutoFree = true)
	{
		if ($this->oSlaveConnector)
		{
			return $this->oSlaveConnector->GetNextRecord($bAutoFree);
		}

		return $this->oConnector->GetNextRecord($bAutoFree);
	}

	/**
	 * @return bool
	 */
	public function FreeResult()
	{
		if ($this->oSlaveConnector)
		{
			return $this->oSlaveConnector->FreeResult();
		}

		if ($this->oConnector)
		{
			return $this->oConnector->FreeResult();
		}
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
		return $this->oConnector->GetLastInsertId($sTableName, $sFieldName);
	}

	/**
	 * @return int
	 */
	public function ResultCount()
	{
		if ($this->oSlaveConnector)
		{
			return $this->oSlaveConnector->ResultCount();
		}
		return $this->oConnector->ResultCount();
	}

	/**
	 * @return array
	 */
	public function GetTableNames()
	{
		$aResult = false;
		if ($this->Connect())
		{
			$aResult = $this->oConnector->GetTableNames();
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
			$aResult = $this->oConnector->GetTableFields($sTableName);
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
			$aResult = $this->oConnector->GetTableIndexes($sTableName);
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
	 * @return \Aurora\System\Exceptions\DbException
	 */
	public function GetException()
	{
		return $this->oLastException;
	}

	/**
	 * @param \Aurora\System\Exceptions\DbException $oException
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
