<?php
/**
 * This code is licensed under AGPLv3 license or Afterlogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

namespace Aurora\System\Net;

/**
 * @license https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @license https://afterlogic.com/products/common-licensing Afterlogic Software License
 * @copyright Copyright (c) 2019, Afterlogic Corp.
 *
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
		$iConnectTimeOut = (null === $iConnectTimeOut) ? $oSettings->GetValue('SocketConnectTimeoutSeconds', 5) : $iConnectTimeOut;
		$iSocketTimeOut = (null === $iSocketTimeOut) ? $oSettings->GetValue('SocketGetTimeoutSeconds', 5) : $iSocketTimeOut;

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
			\Aurora\System\Api::Log('already connected['.$sHost.':'.$this->iPort.']: result = false', \Aurora\System\Enums\LogLevel::Error);

			$this->Disconnect();
			return false;
		}

		$sErrorStr = '';
		$iErrorNo = 0;

		\Aurora\System\Api::Log('start connect to '.$sHost.':'.$this->iPort);
		$this->rConnect = @fsockopen($sHost, $this->iPort, $iErrorNo, $sErrorStr, $this->iConnectTimeOut);

		if (!$this->IsConnected())
		{
			\Aurora\System\Api::Log('connection error['.$sHost.':'.$this->iPort.']: fsockopen = false ('.$iErrorNo.': '.$sErrorStr.')', \Aurora\System\Enums\LogLevel::Error);
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
				\Aurora\System\Api::Log('NET[Error] < Socket timeout reached during connection.', \Aurora\System\Enums\LogLevel::Error);
		    }
			else
			{
				\Aurora\System\Api::Log('NET[Error] < fgets = false', \Aurora\System\Enums\LogLevel::Error);
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
			\Aurora\System\Api::Log('NET[Error] < Could not send user request', \Aurora\System\Enums\LogLevel::Error);
			return false;
		}

		return true;
	}
}
