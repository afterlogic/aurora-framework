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
abstract class AbstractCommandCreator
{
	/**
	 * @var IDbHelper
	 */
	protected $oHelper;

	/**
	 * @var string
	 */
	protected $sPrefix;

	/**
	 * @param IHelper $oHelper
	 * @param string $sPrefix
	 */
	public function __construct($oHelper = '', $sPrefix = '')
	{
		$oSettings =& \Aurora\System\Api::GetSettings();

		$oCommandCreatorHelper =& $this->GetHelper();

		if ($oSettings)
		{
			$this->oHelper = $oCommandCreatorHelper;
			$this->sPrefix = (string) $oSettings->DBPrefix;
		}
	}

	/**
	 * @return CDbStorage
	 */
	public function &GetHelper()
	{
		if (null === $this->oHelper)
		{
			$oSettings =& \Aurora\System\Api::GetSettings();
			if ($oSettings)
			{
				$this->oHelper = \Aurora\System\Db\Creator::CreateCommandCreatorHelper($oSettings);
			}
			else
			{
				$this->oHelper = false;
			}
		}
		return $this->oHelper;
	}

	public function prefix()
	{
		return $this->sPrefix;
	}

	/**
	 * @param string $sValue
	 * @param bool $bWithOutQuote = false
	 * @param bool $bSearch = false
	 * @return string
	 */
	protected function escapeString($sValue, $bWithOutQuote = false, $bSearch = false)
	{
		return $this->oHelper->EscapeString($sValue, $bWithOutQuote, $bSearch);
	}

	/**
	 * @param array $aValue
	 * @return array
	 */
	protected function escapeArray($aValue)
	{
		return array_map(array(&$this->oHelper, 'EscapeString'), $aValue);
	}

	/**
	 * @param string $str
	 * @return string
	 */
	protected function escapeColumn($str)
	{
		return $this->oHelper->EscapeColumn($str);
	}

	/**
	 * @param string $sFieldName
	 * @return string
	 */
	protected function GetDateFormat($sFieldName)
	{
		return $this->oHelper->GetDateFormat($sFieldName);
	}

	/**
	 * @param string $sFieldName
	 * @return string
	 */
	protected function UpdateDateFormat($sFieldName)
	{
		return $this->oHelper->UpdateDateFormat($sFieldName);
	}
}
