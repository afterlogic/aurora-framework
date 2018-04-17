<?php
/*
 * This code is licensed under AGPLv3 license or Afterlogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

namespace Aurora\System\Db;

/**
 * @license https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @license https://afterlogic.com/products/common-licensing AfterLogic Software License
 * @copyright Copyright (c) 2018, Afterlogic Corp.
 *
 * @package Api
 * @subpackage Db
 */
class MySql extends Sql
{
	/*
	 * @var	resource
	 */
	protected $_rConectionHandle;

	/**
	 * @var	resource
	 */
	protected $_rResultId;

	/**
	 * @var bool
	 */
	protected $bUseExplain;

	/**
	 * @var bool
	 */
	protected $bUseExplainExtended;

	/**
	 * @param string $sHost
	 * @param string $sUser
	 * @param string $sPassword
	 * @param string $sDbName
	 * @param string $sDbTablePrefix = ''
	 */
	public function __construct($sHost, $sUser, $sPassword, $sDbName, $sDbTablePrefix = '')
	{
		$this->sHost = trim($sHost);
		$this->sUser = trim($sUser);
		$this->sPassword = trim($sPassword);
		$this->sDbName = trim($sDbName);
		$this->sDbTablePrefix = trim($sDbTablePrefix);

		$this->_rConectionHandle = null;
		$this->_rResultId = null;

		$this->iExecuteCount = 0;
		$oSettings =& \Aurora\System\Api::GetSettings();
		$this->bUseExplain = $oSettings->GetConf('DBUseExplain', false);
		$this->bUseExplainExtended = $oSettings->GetConf('DBUseExplainExtended', false);
	}

	/**
	 * @return bool
	 */
	function IsConnected()
	{
		return is_resource($this->_rConectionHandle);
	}

	/**
	 * @param string $sHost
	 * @param string $sUser
	 * @param string $sPassword
	 * @param string $sDbName
	 */
	public function ReInitIfNotConnected($sHost, $sUser, $sPassword, $sDbName)
	{
		if (!$this->IsConnected())
		{
			$this->sHost = trim($sHost);
			$this->sUser = trim($sUser);
			$this->sPassword = trim($sPassword);
			$this->sDbName = trim($sDbName);
		}
	}

	/**
	 * @param bool $bWithSelect = true
	 * @return bool
	 */
	public function Connect($bWithSelect = true, $bNewLink = false)
	{
		if (!function_exists('mysqli_connect'))
		{
			throw new \Aurora\System\Exceptions\DbException('Can\'t load MySQLi extension.', 0);
		}

		if (strlen($this->sHost) == 0 || strlen($this->sUser) == 0 || strlen($this->sDbName) == 0)
		{
			throw new \Aurora\System\Exceptions\DbException('Not enough details required to establish connection.', 0);
		}

		@ini_set('mysql.connect_timeout', 5);

		if (\Aurora\System\Api::$bUseDbLog)
		{
			\Aurora\System\Api::Log('DB(mysql) : start connect to '.$this->sUser.'@'.$this->sHost);
		}
		
		$this->_rConectionHandle = @mysqli_connect($this->sHost, $this->sUser, $this->sPassword, (bool) $bNewLink);
		if ($this->_rConectionHandle)
		{
			if (\Aurora\System\Api::$bUseDbLog)
			{
				\Aurora\System\Api::Log('DB : connected to '.$this->sUser.'@'.$this->sHost);
			}
			
			@register_shutdown_function(array(&$this, 'Disconnect'));
			return ($bWithSelect) ? $this->Select() : true;
		}
		else
		{
			\Aurora\System\Api::Log('DB : connect to '.$this->sUser.'@'.$this->sHost.' failed', \Aurora\System\Enums\LogLevel::Error);
			$this->_setSqlError();
			return false;
		}
	}

	/**
	 * @return bool
	 */
	public function ConnectNoSelect()
	{
		return $this->Connect(false);
	}

	/**
	 * @return bool
	 */
	public function Select()
	{
		if (0 < strlen($this->sDbName))
		{
			$rDbSelect = @mysqli_select_db($this->_rConectionHandle, $this->sDbName);
			if(!$rDbSelect)
			{
				$this->_setSqlError();
				if ($this->_rConectionHandle)
				{
					@mysqli_close($this->_rConectionHandle);
				}
				$this->_rConectionHandle = null;
				return false;
			}

			if ($this->_rConectionHandle)
			{
				$bSet = false;
				if (function_exists('mysqli_set_charset'))
				{
					$bSet = true;
					mysqli_set_charset($this->_rConectionHandle, 'utf8');
				}

				if (!$bSet)
				{
					mysqli_query($this->_rConectionHandle, 'SET NAMES utf8');
				}
			}

			return true;
		}

		return false;
	}

	/**
	 * @return bool
	 */
	public function Disconnect()
	{
		$result = true;
		if ($this->_rConectionHandle)
		{
			if (is_resource($this->_rResultId))
			{
				mysqli_free_result($this->_rResultId);
			}
			$this->_resultId = null;

			if (\Aurora\System\Api::$bUseDbLog)
			{
				\Aurora\System\Api::Log('DB : disconnect from '.$this->sUser.'@'.$this->sHost);
			}

			$result = @mysqli_close($this->_rConectionHandle);
			$this->_rConectionHandle = null;
			return $result;
		}
		else
		{
			return false;
		}
	}

	/**
	 * @param string $sQuery
	 * @param string $bIsSlaveExecute = false
	 * @return bool
	 */
	public function Execute($sQuery, $bIsSlaveExecute = false)
	{
		$sExplainLog = '';
		$sQuery = trim($sQuery);
		if (($this->bUseExplain || $this->bUseExplainExtended) && 0 === strpos($sQuery, 'SELECT'))
		{
			$sExplainQuery = 'EXPLAIN ';
			$sExplainQuery .= ($this->bUseExplainExtended) ? 'extended '.$sQuery : $sQuery;

			$rExplainResult = @mysqli_query($this->_rConectionHandle, $sExplainQuery);
			while (false != ($mResult = mysqli_fetch_assoc($rExplainResult)))
			{
				$sExplainLog .= AU_API_CRLF.print_r($mResult, true);
			}

			if ($this->bUseExplainExtended)
			{
				$rExplainResult = @mysqli_query($this->_rConectionHandle, 'SHOW warnings');
				while (false != ($mResult = mysqli_fetch_assoc($rExplainResult)))
				{
					$sExplainLog .= AU_API_CRLF.print_r($mResult, true);
				}
			}
		}

		$this->iExecuteCount++;
		$this->log($sQuery, $bIsSlaveExecute);
		if (!empty($sExplainLog))
		{
			$this->log('EXPLAIN:'.AU_API_CRLF.trim($sExplainLog), $bIsSlaveExecute);
		}

		$this->_rResultId = @mysqli_query($this->_rConectionHandle, $sQuery);
		if ($this->_rResultId === false)
		{
			$this->_setSqlError();
		}

		return ($this->_rResultId !== false);
	}

	/**
	 * @param bool $bAutoFree = true
	 * @return &object
	 */
	public function &GetNextRecord($bAutoFree = true)
	{
		if ($this->_rResultId)
		{
			$mResult = @mysqli_fetch_object($this->_rResultId);
			if (!$mResult && $bAutoFree)
			{
				$this->FreeResult();
			}
			return $mResult;
		}
		else
		{
			$nNull = false;
			$this->_setSqlError();
			return $nNull;
		}
	}

	/**
	 * @param bool $bAutoFree = true
	 * @return &array
	 */
	public function &GetNextArrayRecord($bAutoFree = true)
	{
		if ($this->_rResultId)
		{
			$mResult = mysqli_fetch_assoc($this->_rResultId);
			if (!$mResult && $bAutoFree)
			{
				$this->FreeResult();
			}
			return $mResult;
		}
		else
		{
			$nNull = false;
			$this->_setSqlError();
			return $nNull;
		}
	}

	/**
	 * @param string $sTableName = null
	 * @param string $sFieldName = null
	 * @return int
	 */
	public function GetLastInsertId($sTableName = null, $sFieldName = null)
	{
		return (int) @mysqli_insert_id($this->_rConectionHandle);
	}

	/**
	 * @return array
	 */
	public function GetTableNames()
	{
		if (!$this->Execute('SHOW TABLES'))
		{
			return false;
		}

		$aResult = array();
		while (false !== ($aValue = $this->GetNextArrayRecord()))
		{
			foreach ($aValue as $sValue)
			{
				$aResult[] = $sValue;
				break;
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
		if (!$this->Execute('SHOW COLUMNS FROM `'.$sTableName.'`'))
		{
			return false;
		}

		$aResult = array();
		while (false !== ($oValue = $this->GetNextRecord()))
		{
			if ($oValue && isset($oValue->Field) && 0 < strlen($oValue->Field))
			{
				$aResult[] = $oValue->Field;
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
		if (!$this->Execute('SHOW INDEX FROM `'.$sTableName.'`'))
		{
			return false;
		}

		$aResult = array();
		while (false !== ($oValue = $this->GetNextRecord()))
		{
			if ($oValue && isset($oValue->Key_name, $oValue->Column_name))
			{
				if (!isset($aResult[$oValue->Key_name]))
				{
					$aResult[$oValue->Key_name] = array();
				}
				$aResult[$oValue->Key_name][] = $oValue->Column_name;
			}
		}

		return $aResult;
	}

	/**
	 * @return bool
	 */
	public function FreeResult()
	{
		if ($this->_rResultId)
		{
			if (!@mysqli_free_result($this->_rResultId))
			{
				$this->_setSqlError();
				return false;
			}
			else
			{
				$this->_rResultId = null;
			}
		}
		return true;
	}

	/**
	 * @return int
	 */
	public function ResultCount()
	{
		return @mysqli_num_rows($this->_rResultId);
	}

	/**
	 * @return void
	 */
	private function _setSqlError()
	{
		if ($this->IsConnected())
		{
			$this->ErrorDesc = @mysqli_error($this->_rConectionHandle);
			$this->ErrorCode = @mysqli_errno($this->_rConectionHandle);
		}
		else
		{
			$this->ErrorDesc = @mysqli_error();
			$this->ErrorCode = @mysqli_errno();
		}

		if (0 < strlen($this->ErrorDesc))
		{
			$this->errorLog($this->ErrorDesc);
			throw new \Aurora\System\Exceptions\DbException($this->ErrorDesc, $this->ErrorCode);
		}
	}
}