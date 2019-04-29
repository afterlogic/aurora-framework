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
class Func
{
	/**
	 * @var string
	 */
	protected $sName;

	/**
	 * @var string
	 */
	protected $sIncParams;

	/**
	 * @var string
	 */
	protected $sResult;

	/**
	 * @var string
	 */
	protected $sText;

	/**
	 * @param string $sName
	 * @param string $sText
	 */
	public function __construct($sName, $sIncParams, $sResult, $sText)
	{
		$this->sName = $sName;
		$this->sIncParams = $sIncParams;
		$this->sResult = $sResult;
		$this->sText = $sText;
	}

	/**
	 * @param IDbHelper $oHelper
	 * @param bool $bAddDropFunction = false
	 * @return string
	 */
	public function ToString($oHelper, $bAddDropFunction = false)
	{
		$sResult = '';
		if ($bAddDropFunction)
		{
			$sResult .= 'DROP FUNCTION IF EXISTS '.$this->sName.';;'.Table::CRLF;
		}

		$sResult .= 'CREATE FUNCTION '.$this->sName.'('.$this->sIncParams.') RETURNS '.$this->sResult;
		$sResult .= Table::CRLF.$this->sText;

		return trim($sResult);
	}
}
