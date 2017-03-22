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

namespace Aurora\System\Net;

/**
 * @package Api
 * @subpackage Net
 */
abstract class AbstractProtocol
{
	/**
	 * @var resource
	 */
	protected $rConnect;

	/**
	 * @var string
	 */
	protected $sHost;

	/**
	 * @var int
	 */
	protected $iPort;

	/**
	 * @var bool
	 */
	protected $bUseSsl;

	/**
	 * @var int
	 */
	protected $iConnectTimeOut;

	/**
	 * @var int
	 */
	protected $iSocketTimeOut;

	/**
	 * @param string $sHost
	 * @param int $iPort
	 * @param bool $bUseSsl = false
	 * @param int $iConnectTimeOut = null
	 * @param int $iSocketTimeOut = null
	 */
	public function __construct($sHost, $iPort, $bUseSsl = false, $iConnectTimeOut = null, $iSocketTimeOut = null)
	{
		$oSettings =& \Aurora\System\Api::GetSettings();
		$iConnectTimeOut = (null === $iConnectTimeOut) ? $oSettings->GetConf('SocketConnectTimeoutSeconds', 5) : $iConnectTimeOut;
		$iSocketTimeOut = (null === $iSocketTimeOut) ? $oSettings->GetConf('SocketGetTimeoutSeconds', 5) : $iSocketTimeOut;

		$this->sHost = $sHost;
		$this->iPort = $iPort;
		$this->bUseSsl = $bUseSsl;
		$this->iConnectTimeOut = $iConnectTimeOut;
		$this->iSocketTimeOut = $iSocketTimeOut;
	}

	/**
	 * @return bool
	 */
	public function Connect()
	{
		$sHost = ($this->bUseSsl) ? 'ssl://'.$this->sHost : $this->sHost;

		if ($this->IsConnected())
		{
			\Aurora\System\Api::Log('already connected['.$sHost.':'.$this->iPort.']: result = false', ELogLevel::Error);

			$this->Disconnect();
			return false;
		}

		$sErrorStr = '';
		$iErrorNo = 0;

		\Aurora\System\Api::Log('start connect to '.$sHost.':'.$this->iPort);
		$this->rConnect = @fsockopen($sHost, $this->iPort, $iErrorNo, $sErrorStr, $this->iConnectTimeOut);

		if (!$this->IsConnected())
		{
			\Aurora\System\Api::Log('connection error['.$sHost.':'.$this->iPort.']: fsockopen = false ('.$iErrorNo.': '.$sErrorStr.')', ELogLevel::Error);
			return false;
		}
		else
		{
			\Aurora\System\Api::Log('connected');
		}

		if (\MailSo\Base\Utils::FunctionExistsAndEnabled('stream_set_timeout'))
		{
			@stream_set_timeout($this->rConnect, $this->iSocketTimeOut);
		}

		if (\MailSo\Base\Utils::FunctionExistsAndEnabled('@stream_set_blocking'))
		{
			@stream_set_blocking($this->rConnect, true);
		}

		return true;
	}

	/**
	 * @return bool
	 */
	public function Disconnect()
	{
		if ($this->IsConnected())
		{
			\Aurora\System\Api::Log('disconnect from '.$this->sHost.':'.$this->iPort);
			@fclose($this->rConnect);
		}
		$this->rConnect = null;
		return true;
	}

	/**
	 * @return resource
	 */
	public function GetConnectResource()
	{
		return $this->rConnect;
	}

	/**
	 * @return bool
	 */
	public function IsConnected()
	{
		return is_resource($this->rConnect);
	}

	/**
	 * @return string | bool
	 */
	public function ReadLine()
	{
		$sLine = @fgets($this->rConnect, 4096);
		\Aurora\System\Api::Log('NET < '.\Aurora\System\Utils::ShowCRLF($sLine));

		if (false === $sLine)
		{
		    $aSocketStatus = @socket_get_status($this->rConnect);
		    if (isset($aSocketStatus['timed_out']) && $aSocketStatus['timed_out'])
		    {
				\Aurora\System\Api::Log('NET[Error] < Socket timeout reached during connection.', ELogLevel::Error);
		    }
			else
			{
				\Aurora\System\Api::Log('NET[Error] < fgets = false', ELogLevel::Error);
			}
		}

		return $sLine;
	}

	/**
	 * @param string $sLine
	 * @return bool
	 */
	public function WriteLine($sLine, $aHideValues = array())
	{
		$sLine = $sLine."\r\n";
		$sLogLine = (0 < count($aHideValues))
			? str_replace($aHideValues, '*******', $sLine) : $sLine;

		\Aurora\System\Api::Log('NET > '.\Aurora\System\Utils::ShowCRLF($sLogLine));

		if (!@fputs($this->rConnect, $sLine))
		{
			\Aurora\System\Api::Log('NET[Error] < Could not send user request', ELogLevel::Error);
			return false;
		}

		return true;
	}
}