<?php
/**
 * This code is licensed under AGPLv3 license or Afterlogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

namespace Aurora\System\Managers\Db;

/**
 * @license https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @license https://afterlogic.com/products/common-licensing Afterlogic Software License
 * @copyright Copyright (c) 2019, Afterlogic Corp.
 */
class Storage extends \Aurora\System\Managers\AbstractStorage
{
	/**
	 * @var CDbStorage $oConnection
	 */
	protected $oConnection;

	/**
	 * @var CApiDavCommandCreatorMySQL
	 */
	protected $oCommandCreator;

	/**
	 *
	 * @param \Aurora\System\Managers\Db $oManager
	 */
	public function __construct(\Aurora\System\Managers\Db &$oManager)
	{
		parent::__construct($oManager);

		$this->oConnection =& $oManager->GetConnection();
		$this->oCommandCreator = new CommandCreator\MySQL();
	}

	/**
	 * Executes queries from sql string.
	 *
	 * @param string $sSql - sql string.
	 *
	 * @return boolean
	 */
	public function executeSql($sSql)
	{
		$bResult = false;

		$sDbPrefix = $this->oCommandCreator->prefix();
		if (!empty($sSql) && $this->oConnection)
		{
			$sPrepSql = trim(str_replace('%PREFIX%', $sDbPrefix, $sSql));
			if (!empty($sPrepSql))
			{
				$bResult = $this->oConnection->Execute($sPrepSql);
			}
			$this->throwDbExceptionIfExist();
		}

		return $bResult;
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

	public function columnExists($sTable, $sColumn)
	{
		$bResult = false;

		$sDbPrefix = $this->oCommandCreator->prefix();
		if ($this->oConnection)
		{
			$sSql = $this->oCommandCreator->columnExists($sTable, $sColumn);

			if ($this->oConnection->Execute($sSql))
			{
				$oRow = $this->oConnection->GetNextRecord();
				if ($oRow !== false)
				{
					if ((int) $oRow->cnt > 0)
					{
						$bResult = true;
					}
				}
			}
		}
		return $bResult;
	}
}
