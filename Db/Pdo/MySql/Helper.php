<?php
/**
 * This code is licensed under AGPLv3 license or Afterlogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

namespace Aurora\System\Db\Pdo\MySql;

/**
 * @license https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @license https://afterlogic.com/products/common-licensing Afterlogic Software License
 * @copyright Copyright (c) 2019, Afterlogic Corp.
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
			$sResult = addslashes($sValue);
		}
		else
		{
			$sResult = 0 === strlen($sValue) ? '\'\'' : '\''.addslashes($sValue).'\'';
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
		return 0 === strlen($sValue) ? $sValue : '`'.$sValue.'`';
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
		return false;
	}

	/**
	 * @return string
	 */
	public function CreateIndexRequest($iIndexType, $sTableName, $sIndexName, $aFields)
	{
		return '';
	}

	/**
	 * @return string
	 */
	public function DropIndexRequest($sIndexesName, $sTableName)
	{
		return sprintf('DROP INDEX %s ON %s', $sIndexesName, $sTableName);
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
				$sResult .= 'int(11) NOT NULL auto_increment';
				break;
			case Field::AUTO_INT_BIG:
				$sResult .= 'bigint(20) NOT NULL auto_increment';
				break;
			case Field::AUTO_INT_UNSIGNED:
				$sResult .= 'int(11) unsigned NOT NULL auto_increment';
				break;
			case Field::AUTO_INT_BIG_UNSIGNED:
				$sResult .= 'bigint(20) unsigned NOT NULL auto_increment';
				break;

			case Field::BIT:
				$sResult .= 'tinyint(1)';
				break;
			case Field::INT:
				$sResult .= 'int(11)';
				break;
			case Field::INT_UNSIGNED:
				$sResult .= 'int(11) unsigned';
				break;
			case Field::INT_SHORT:
				$sResult .= 'tinyint(4)';
				break;
			case Field::INT_SHORT_SMALL:
				$sResult .= 'tinyint(2)';
				break;
			case Field::INT_SMALL:
				$sResult .= 'smallint(6)';
				break;
			case Field::INT_BIG:
				$sResult .= 'bigint(20)';
				break;
			case Field::INT_UNSIGNED:
				$sResult .= 'int(11) UNSIGNED';
				break;
			case Field::INT_BIG_UNSIGNED:
				$sResult .= 'bigint UNSIGNED';
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
				$sResult .= 'longtext';
				break;
			case Field::TEXT_MEDIUM:
				$sResult .= 'mediumtext';
				break;
			case Field::BLOB:
				$sResult .= 'blob';
				break;
			case Field::BLOB_LONG:
				$sResult .= 'longblob';
				break;

			case Field::DATETIME:
				$sResult .= 'datetime';
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
		return '/*!40101 DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci */';
	}

	/**
	 * @return string
	 */
	public function GenerateLastIdSeq($sTableName, $sFiledName)
	{
		return null;
	}
}
