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
class Table
{
	const CRLF = "\r\n";
	const TAB = "\t";

	/**
	 * @var string
	 */
	protected $sName;

	/**
	 * @var string
	 */
	protected $sPrefix;

	/**
	 * @var array
	 */
	protected $aFields;

	/**
	 * @var array
	 */
	protected $aKeys;

	/**
	 * @param string $sName
	 * @param string $sPrefix
	 * @param array $aFields
	 * @param array $aKeys = array()
	 */
	public function __construct($sName, $sPrefix, array $aFields, $aKeys = array())
	{
		$this->sName = $sName;
		$this->sPrefix = $sPrefix;
		$this->aFields = $aFields;
		$this->aKeys = $aKeys;
	}

	/**
	 * @return string
	 */
	public function Name($nWithPrefix = true)
	{
		return (($nWithPrefix) ? $this->sPrefix : '').$this->sName;
	}

	/**
	 * @return array
	 */
	public function &GetFields()
	{
		return $this->aFields;
	}

	/**
	 * @return array
	 */
	public function GetFieldNames()
	{
		$aField = array();
		foreach ($this->aFields as /* @var $oField Field */ $oField)
		{
			$aField[] = $oField->Name();
		}
		return $aField;
	}

	/**
	 * @return array
	 */
	public function GetIndexesFieldsNames()
	{
		$aKeyLines = array();
		foreach ($this->aKeys as /* @var $oKey Key */ $oKey)
		{
			if (Key::TYPE_PRIMARY_KEY !== $oKey->GetType())
			{
				$aKeyFields = $oKey->GetIndexesFields();
				if (is_array($aKeyFields) && 0 < count($aKeyFields))
				{
					$aKeyLines[] = $aKeyFields;
				}
			}
		}
		return $aKeyLines;
	}

	/**
	 * @param string $sName
	 * @return Field
	 */
	public function GetFieldByName($sName)
	{
		$oResultField = false;
		foreach ($this->aFields as /* @var $oField Field */ $oField)
		{
			if ($sName === $oField->Name())
			{
				$oResultField = $oField;
				break;
			}
		}
		return $oResultField;
	}

	/**
	 * @param IDbHelper $oHelper
	 * @param bool $bAddDropTable = false
	 * @return string
	 */
	public function ToString($oHelper, $bAddDropTable = false)
	{
		$sResult = '';
		if ($bAddDropTable)
		{
			$sResult .= 'DROP TABLE IF EXISTS '.$oHelper->EscapeColumn($this->Name()).';'.Table::CRLF;
		}

		$sResult .= 'CREATE TABLE '.$oHelper->EscapeColumn($this->Name())
			.' ('.Table::CRLF.Table::TAB;

		$aFieldLines = array();
		foreach ($this->aFields as /* @var $oField Field */ $oField)
		{
			$aFieldLines[] = $oField->ToString($oHelper);
		}

		$sResult .= implode(','.Table::CRLF.Table::TAB, $aFieldLines);
		unset($aFieldLines);

		$aAdditionalRequests = array();
		
		$aKeyLines = array();
		foreach ($this->aKeys as /* @var $oKey Key */ $oKey)
		{
			if (Key::TYPE_PRIMARY_KEY === $oKey->GetType() || !$oHelper->UseSingleIndexRequest())
			{
				$sLine = $oKey->ToString($oHelper, $this->Name());
				if (!empty($sLine))
				{
					$aKeyLines[] = $sLine;
				}
			}
			else
			{
				$aAdd = $oKey->ToSingleIndexRequest($oHelper, $this->Name());
				if (!empty($aAdd))
				{
					$aAdditionalRequests[] = $aAdd;
				}
			}
		}

		if (0 < count($aKeyLines))
		{
			$sResult .= ','.Table::CRLF.Table::TAB.
				implode(','.Table::CRLF.Table::TAB, $aKeyLines);
		}
		
		unset($aKeyLines);

		return trim($sResult.Table::CRLF.') '.$oHelper->CreateTableLastLine()).
			(0 < count($aAdditionalRequests) ? ";\n\n".implode(";\n\n", $aAdditionalRequests) : '');
	}

	/**
	 * @param IDbHelper $oHelper
	 * @param array $aFieldsToAdd
	 * @return string
	 */
	public function GetAlterAddFields($oHelper, $aFieldsToAdd)
	{
		if (0 < count($aFieldsToAdd))
		{
			$aLines = array();
			foreach ($this->aFields as /* @var $oField Field */ $oField)
			{
				if (in_array($oField->Name(), $aFieldsToAdd))
				{
					$aLines[] = 'ADD '.$oField->ToString($oHelper);
				}
			}

			return sprintf('ALTER TABLE %s %s', $oHelper->EscapeColumn($this->Name()), implode(', ', $aLines));
		}

		return false;
	}

	/**
	 * @param IDbHelper $oHelper
	 * @param array $aFieldsToDelete
	 * @return string
	 */
	public function GetAlterDeleteFields($oHelper, $aFieldsToDelete)
	{
		if (0 < count($aFieldsToDelete))
		{
			$aLines = array();
			foreach ($aFieldsToDelete as $sFieldName)
			{
				$aLines[] = 'DROP '.$oHelper->EscapeColumn($sFieldName);
			}

			return sprintf('ALTER TABLE %s %s', $oHelper->EscapeColumn($this->Name()), implode(', ', $aLines));
		}

		return false;
	}

	/**
	 * @param IDbHelper $oHelper
	 * @param array $aIndexesToCreate
	 * @return string
	 */
	public function GetAlterCreateIndexes($oHelper, $aIndexesToCreate)
	{
		if (0 < count($aIndexesToCreate))
		{
			$sName = strtoupper('awm_'.$this->Name().'_'.implode('_', $aIndexesToCreate).'_index');
			$aIndexesToCreate = array_map(array($oHelper, 'EscapeColumn'), $aIndexesToCreate);

			return sprintf('CREATE INDEX %s ON %s (%s)', $sName,
				$oHelper->EscapeColumn($this->Name()), implode(', ', $aIndexesToCreate));
		}

		return false;
	}

	/**
	 * @param IDbHelper $oHelper
	 * @param string $sIndexesName
	 * @return string
	 */
	public function GetAlterDeleteIndexes($oHelper, $sIndexesName)
	{
		if (!empty($sIndexesName))
		{
			return $oHelper->DropIndexRequest($sIndexesName, $this->Name());
		}

		return false;
	}
}