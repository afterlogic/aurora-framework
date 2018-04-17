<?php
/*
 * This code is licensed under AGPLv3 license or Afterlogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

namespace Aurora\System\Db\Pdo\Postgres;

/**
 * @license https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @license https://afterlogic.com/products/common-licensing AfterLogic Software License
 * @copyright Copyright (c) 2018, Afterlogic Corp.
 *
 * @package Api
 * @subpackage Db
 */
class Helper implements \Aurora\System\Db\IHelper
{
	/**
	 * @param string $sValue
	 * @param bool $bWithOutQuote = false
	 * @param bool $bSearch = false
	 * @return string
	 */
	public function EscapeString($sValue, $bWithOutQuote = false, $bSearch = false)
	{
		$sResult = '';
		if ($bWithOutQuote)
		{
			$sResult = str_replace('\'', '\'\'', $sValue);
		}
		else
		{
			$sResult = 0 === strlen($sValue) ? '\'\'' : '\''.str_replace('\'', '\'\'', $sValue).'\'';
		}

		if ($bSearch)
		{
			$sResult = str_replace(array("%", "_"), array("\\%", "\\_"), $sResult);
		}

		return $sResult;
	}

	/**
	 * @param string $sValue
	 * @return string
	 */
	public function EscapeColumn($sValue)
	{
		return '"'.str_replace('"', '\\"', trim($sValue)).'"';
	}

	/**
	 * @param int $iTimeStamp
	 * @param bool $bAsInsert = false
	 * @return string
	 */
	public function TimeStampToDateFormat($iTimeStamp, $bAsInsert = false)
	{
		$sResult = (string) gmdate('Y-m-d H:i:s', $iTimeStamp);
		return ($bAsInsert) ? $this->UpdateDateFormat($sResult) : $sResult;
	}

	/**
	 * @param string $sFieldName
	 * @return string
	 */
	public function GetDateFormat($sFieldName)
	{
		return 'DATE_FORMAT('.$sFieldName.', "%Y-%m-%d %T")';
	}

	/**
	 * @param string $sFieldName
	 * @return string
	 */
	public function UpdateDateFormat($sFieldName)
	{
		return $this->EscapeString($sFieldName);
	}

	/**
	 * @return bool
	 */
	public function UseSingleIndexRequest()
	{
		return true;
	}

	/**
	 * @return string
	 */
	public function DropIndexRequest($sIndexesName, $sTableName)
	{
		return sprintf('DROP INDEX %s', $sIndexesName);
	}

	/**
	 * @return string
	 */
	public function CreateIndexRequest($iIndexType, $sTableName, $sIndexName, $aFields)
	{
		$sResult = '';
		if (Key::TYPE_INDEX === $iIndexType)
		{
			$aValues = array_map(array(&$this, 'EscapeColumn'), $aFields);
			$sResult = 'CREATE INDEX '.$this->EscapeColumn($sIndexName).
				' ON '.$sTableName.' ('.implode(', ', $aValues).')';
		}
		else if (Key::TYPE_UNIQUE_KEY === $iIndexType)
		{
			$aValues = array_map(array(&$this, 'EscapeColumn'), $aFields);
			$sResult = 'CREATE UNIQUE INDEX '.$this->EscapeColumn($sIndexName).
				' ON '.$sTableName.' ('.implode(', ', $aValues).')';
		}

		return $sResult;
	}

	/**
	 * @param string $sName
	 * @param int $iFieldType
	 * @return string
	 */
	public function FieldToString($sName, $iFieldType, $mDefault = null, $iCustomLen = null, $bNotNullWithOutDefault = false)
	{
		$sResult = $this->EscapeColumn($sName).' ';
		switch ($iFieldType)
		{
			case Field::AUTO_INT:
				$sResult .= 'serial NOT NULL';
				break;
			case Field::AUTO_INT_BIG:
				$sResult .= 'bigserial NOT NULL';
				break;
			case Field::AUTO_INT_UNSIGNED:
				$sResult .= 'serial NOT NULL';
				break;
			case Field::AUTO_INT_BIG_UNSIGNED:
				$sResult .= 'bigserial NOT NULL';
				break;

			case Field::BIT:
				$sResult .= 'smallint';
				break;
			case Field::INT:
				$sResult .= 'integer';
				break;
			case Field::INT_UNSIGNED:
				$sResult .= 'bigint';
				break;
			case Field::INT_SHORT:
				$sResult .= 'smallint';
				break;
			case Field::INT_SHORT_SMALL:
				$sResult .= 'smallint';
				break;
			case Field::INT_SMALL:
				$sResult .= 'integer';
				break;
			case Field::INT_BIG:
				$sResult .= 'bigint';
				break;
			case Field::INT_UNSIGNED:
				$sResult .= 'bigint';
				break;
			case Field::INT_BIG_UNSIGNED:
				$sResult .= 'bigint';
				break;

			case Field::CHAR:
				$sResult .= 'varchar(1)';
				break;
			case Field::VAR_CHAR:
				$sResult .= (null === $iCustomLen)
					? 'varchar(255)' : 'varchar('.((int) $iCustomLen).')';
				break;
			case Field::TEXT:
				$sResult .= 'text';
				break;
			case Field::TEXT_LONG:
				$sResult .= 'text';
				break;
			case Field::TEXT_MEDIUM:
				$sResult .= 'text';
				break;
			case Field::BLOB:
				$sResult .= 'bytea';
				break;
			case Field::BLOB_LONG:
				$sResult .= 'bytea';
				break;

			case Field::DATETIME:
				$sResult .= 'timestamp';
				break;
		}

		if (in_array($iFieldType, array(Field::AUTO_INT, Field::AUTO_INT_BIG,
			Field::AUTO_INT_UNSIGNED, Field::AUTO_INT_BIG_UNSIGNED,
			Field::TEXT, Field::TEXT_LONG, Field::BLOB, Field::BLOB_LONG)))
		{
			// no need default
		}
		else if (null !== $mDefault)
		{
			$sResult .= ' NOT NULL default ';
			if (is_string($mDefault))
			{
				$sResult .= $this->EscapeString($mDefault);
			}
			else if (is_numeric($mDefault))
			{
				$sResult .= (string) $mDefault;
			}
		}
		else
		{
			$sResult .= $bNotNullWithOutDefault ? ' NOT NULL' : ' default NULL';
		}

		return trim($sResult);
	}

	/**
	 * @return string
	 */
	public function CreateTableLastLine()
	{
		return '';
	}

	/**
	 * @return string
	 */
	public function GenerateLastIdSeq($sTableName, $sFiledName)
	{
		return \strtolower($sTableName.'_'.$sFiledName.'_seq');
	}
}
