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
class Key
{
	const TYPE_KEY = 0;
	const TYPE_UNIQUE_KEY = 1;
	const TYPE_PRIMARY_KEY = 2;
	const TYPE_INDEX = 3;
	const TYPE_FULLTEXT = 4;

	/**
	 * @var int
	 */
	protected $iType;

	/**
	 * @var array
	 */
	protected $aFields;

	/**
	 * @param string $sName
	 * @param int $iType
	 * @param array $aFields
	 */
	public function __construct($iType, array $aFields)
	{
		$this->iType = $iType;
		$this->aFields = $aFields;
	}

	/**
	 * @return int
	 */
	public function GetType()
	{
		return $this->iType;
	}

	/**
	 * @param string $sTableName
	 * @return string
	 */
	public function GetName($sTableName)
	{
		$aList = $this->aFields;
		sort($aList);
		return strtoupper($sTableName.'_'.implode('_', $aList).'_INDEX');
	}

	/**
	 * @return array
	 */
	public function GetIndexesFields()
	{
		return $this->aFields;
	}

	/**
	 * @return string
	 */
	public function ToString($oHelper, $sTableName)
	{
		$sResult = '';
		if (0 < count($this->aFields))
		{
			switch ($this->iType)
			{
				case Key::TYPE_PRIMARY_KEY:
					$sResult .= 'PRIMARY KEY';
					break;
				case Key::TYPE_UNIQUE_KEY:
					$sResult .= 'UNIQUE '.$oHelper->EscapeColumn($this->GetName($sTableName));
					break;
				case Key::TYPE_INDEX:
					$sResult .= 'INDEX '.$oHelper->EscapeColumn($this->GetName($sTableName));
					break;
//				case Key::TYPE_FULLTEXT:
//					$sResult .= 'FULLTEXT '.$oHelper->EscapeColumn($this->GetName($sTableName));
//					break;
			}

			$aValues = array_map(array(&$oHelper, 'EscapeColumn'), $this->aFields);
			$sResult .= ' ('.implode(', ', $aValues).')';
		}

		return trim($sResult);
	}

	/**
	 * @return string
	 */
	public function ToSingleIndexRequest($oHelper, $sTableName)
	{
		return $oHelper->CreateIndexRequest($this->iType, $sTableName, $this->GetName($sTableName), $this->aFields);
	}
}
