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

namespace Aurora\System\Net\Protocols;

/**
 * @package Api
 * @subpackage Net
 */
class Pop3 extends \Aurora\System\Net\AbstractProtocol
{
	/**
	 * @return bool
	 */
	public function Connect()
	{
		$bResult = false;
		if (parent::Connect())
		{
			$bResult = $this->CheckResponse($this->GetNextLine());
		}
		return $bResult;
	}

	/**
	 * @param string $sLogin
	 * @param string $sPassword
	 * @param string $sLoginAuthKey = ''
	 * @param string $sProxyAuthUser = ''
	 * @return bool
	 */
	public function Login($sLogin, $sPassword, $sLoginAuthKey = '', $sProxyAuthUser = '')
	{
		return $this->SendCommand('USER '.$sLogin) && $this->SendCommand('PASS '.$sPassword, array($sPassword));
	}

	/**
	 * @param string $sLogin
	 * @param string $sPassword
	 * @return bool
	 */
	public function ConnectAndLogin($sLogin, $sPassword)
	{
		return $this->Connect() && $this->Login($sLogin, $sPassword);
	}

	/**
	 * @return bool
	 */
	public function Disconnect()
	{
		return parent::Disconnect();
	}

	/**
	 * @return bool
	 */
	public function Logout()
	{
		return $this->SendCommand('QUIT');
	}

	/**
	 * @return bool
	 */
	public function LogoutAndDisconnect()
	{
		return $this->Logout() && $this->Disconnect();
	}

	/**
	 * @return bool
	 */
	public function GetNamespace()
	{
		return '';
	}

	/**
	 * @param string $sCmd
	 * @return bool
	 */
	public function SendLine($sCmd)
	{
		return $this->WriteLine($sCmd);
	}

	/**
	 * @param string $sCmd
	 * @param array $aHideValues = array()
	 * @return bool
	 */
	public function SendCommand($sCmd, $aHideValues = array())
	{
		if ($this->WriteLine($sCmd, $aHideValues))
		{
			return $this->CheckResponse($this->GetNextLine());
		}

		return false;
	}

	/**
	 * @return string
	 */
	public function GetNextLine()
	{
		return $this->ReadLine();
	}

	/**
	 * @param string $sResponse
	 * @return bool
	 */
	public function CheckResponse($sResponse)
	{
		return ('+OK' === substr($sResponse, 0, 3));
	}
}
