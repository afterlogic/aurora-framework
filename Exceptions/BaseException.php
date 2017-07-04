<?php
/*
 * @copyright Copyright (c) 2017, Afterlogic Corp.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 * 
 */

namespace Aurora\System\Exceptions;

/**
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
	 * @param array $aParams = array()
	 * @param array $aObjectParams = array()
	 */
	public function __construct($iCode, $oPrevious = null, $aParams = array(), $aObjectParams = array())
	{
		if (\Aurora\System\Exceptions\ErrorCodes::Validation_InvalidPort === $iCode)
		{
			\Aurora\System\Api::Log('Exception error: '.\Aurora\System\Exceptions\ErrorCodes::GetMessageByCode($iCode, $aParams), \Aurora\System\Enums\LogLevel::Error);
			$iCode = \Aurora\System\Exceptions\ErrorCodes::Validation_InvalidPort_OutInfo;
		}
		else if (\Aurora\System\Exceptions\ErrorCodes::Validation_InvalidEmail === $iCode)
		{
			\Aurora\System\Api::Log('Exception error: '.\Aurora\System\Exceptions\ErrorCodes::GetMessageByCode($iCode, $aParams), \Aurora\System\Enums\LogLevel::Error);
			$iCode = \Aurora\System\Exceptions\ErrorCodes::Validation_InvalidEmail_OutInfo;
		}
		else if (\Aurora\System\Exceptions\ErrorCodes::Validation_FieldIsEmpty === $iCode)
		{
			\Aurora\System\Api::Log('Exception error: '.\Aurora\System\Exceptions\ErrorCodes::GetMessageByCode($iCode, $aParams), \Aurora\System\Enums\LogLevel::Error);
			$iCode = \Aurora\System\Exceptions\ErrorCodes::Validation_FieldIsEmpty_OutInfo;
		}

		$this->aObjectParams = $aObjectParams;
		$this->oPrevious = $oPrevious ? $oPrevious : null;

		if ($this->oPrevious)
		{
			\Aurora\System\Api::Log('Previous Exception: '.$this->oPrevious->getMessage(), \Aurora\System\Enums\LogLevel::Error);
		}

		parent::__construct(\Aurora\System\Exceptions\ErrorCodes::GetMessageByCode($iCode, $aParams), $iCode);
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
		if ($this->oPrevious instanceof \MailSo\Imap\Exceptions\NegativeResponseException)
		{
			$oResponse = /* @var $oResponse \MailSo\Imap\Response */ $this->oPrevious->GetLastResponse();
			
			$sMessage = $oResponse instanceof \MailSo\Imap\Response ?
				$oResponse->Tag.' '.$oResponse->StatusOrIndex.' '.$oResponse->HumanReadable : '';
		}
		else if ($this->oPrevious instanceof \MailSo\Smtp\Exceptions\NegativeResponseException)
		{
			$sMessage = $this->oPrevious->getMessage();
//			$oSub = $this->oPrevious->getPrevious();
//			$oSub = $oSub instanceof \MailSo\Smtp\Exceptions\NegativeResponseException ? $oSub : null;
//
//			$sMessage = $oSub ? $oSub->getMessage() : $this->oPrevious->getMessage();
		}
		else if ($this->oPrevious instanceof \Exception)
		{
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

