<?php

/* -AFTERLOGIC LICENSE HEADER- */

/**
 * @package Api
 * @subpackage Db
 */
class CDbGeneralSql
{
	/**
	 * @var	int
	 */
	protected $iExecuteCount;

	/**
	 * @var	int
	 */
	public $ErrorCode;

	/**
	 * @var	string
	 */
	public $ErrorDesc;

	/**
	 * @return bool
	 */
	function IsConnected()
	{
		return false;
	}

	/**
	 * @param string $sLogDesc
	 * @param string $bIsSlaveExecute = false
	 * @return void
	 */
	protected function log($sLogDesc, $bIsSlaveExecute = false)
	{
		if (CApi::$bUseDbLog)
		{
			if ($bIsSlaveExecute)
			{
				CApi::Log('DB-Slave['.$this->iExecuteCount.'] > '.trim($sLogDesc));
			}
			else
			{
				CApi::Log('DB['.$this->iExecuteCount.'] > '.trim($sLogDesc));
			}
		}
	}

	/**
	 * @param string $sErrorDesc
	 * @return void
	 */
	protected function errorLog($sErrorDesc)
	{
		CApi::Log('DB ERROR < '.trim($sErrorDesc), ELogLevel::Error);
	}
}

/**
 * @package Api
 * @subpackage Db
 */
class CDbSql extends CDbGeneralSql
{
	/**
	 * @var	string
	 */
	protected $sHost;

	/**
	 * @var	string
	 */
	protected $sUser;

	/**
	 * @var	string
	 */
	protected $sPassword;

	/**
	 * @var	string
	 */
	protected $sDbName;

	/**
	 * @var	string
	 */
	protected $sDbTablePrefix;
}