<?php
/**
 * This code is licensed under AGPLv3 license or Afterlogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

namespace Aurora\System\Exceptions;

/**
 * @license https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @license https://afterlogic.com/products/common-licensing Afterlogic Software License
 * @copyright Copyright (c) 2022, Afterlogic Corp.
 *
 * @package Api
 */
class BaseException extends Exception
{
    /**
     * @var array
     */
    protected $aObjectParams;

    /**
     * @var Exception
     */
    protected $oPrevious;

    /**
     * @param int $iCode
     * @param Exception $oPrevious = null
     * @param array $aObjectParams = array()
     */
    public function __construct($iCode, $oPrevious = null, $aObjectParams = array())
    {
        $this->aObjectParams = $aObjectParams;
        $this->oPrevious = $oPrevious ? $oPrevious : null;

        if ($this->oPrevious) {
            \Aurora\System\Api::Log('Previous Exception: ' . $this->oPrevious->getMessage(), \Aurora\System\Enums\LogLevel::Error);
        }

        parent::__construct('BaseException', $iCode);
    }

    /**
     * @return array
     */
    public function GetObjectParams()
    {
        return $this->aObjectParams;
    }

    /**
     * @return string
     */
    public function GetPreviousMessage()
    {
        $sMessage = '';
        if ($this->oPrevious instanceof \MailSo\Imap\Exceptions\NegativeResponseException) {
            $oResponse = /* @var $oResponse \MailSo\Imap\Response */ $this->oPrevious->GetLastResponse();

            $sMessage = $oResponse instanceof \MailSo\Imap\Response ?
                $oResponse->Tag . ' ' . $oResponse->StatusOrIndex . ' ' . $oResponse->HumanReadable : '';
        } elseif ($this->oPrevious instanceof \MailSo\Smtp\Exceptions\NegativeResponseException) {
            $sMessage = $this->oPrevious->getMessage();
        } elseif ($this->oPrevious instanceof \Exception) {
            $sMessage = $this->oPrevious->getMessage();
        }

        return $sMessage;
    }

    /**
     * @return string
     */
    public function GetPreviousException()
    {
        return $this->oPrevious;
    }
}
