<?php
/*
 * @copyright Copyright (c) 2016, Afterlogic Corp.
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

/**
 * @package Api
 */
abstract class AApiTwoFactorAuthPlugin extends AApiPlugin
{
	/**
	 * @var \CApiTwofactorauthManager
	 */
	protected $oApiTwofactorauth;
	
	/**
	 * @param string $sVersion
	 * @param CApiPluginManager $oPluginManager
	 */
	public function __construct($sVersion, CApiPluginManager $oPluginManager)
	{
		parent::__construct($sVersion, $oPluginManager);
		$this->oApiTwofactorauth = null;

        $this->addHook('api-integrator-set-account-as-logged-in', 'setAccountIsLoggedIn');
	}
	
	protected function getTwofactorauthManager()
	{
		if (null === $this->oApiTwofactorauth)
		{
			$this->oApiTwofactorauth = \CApi::Manager('twofactorauth');
		}
		
		return $this->oApiTwofactorauth;
	}
	
    /**
     * Create new secret.
     * 16 characters, randomly chosen from the allowed base32 characters.
     *
     * @param CAccount $oAccount
     * @param int $iDataType
     * @param $sDataValue
     * @param bool $bAllowUpdate
     * @return string
     */
    public function createDataValue($oAccount = null, $iDataType = null, $sDataValue, $bAllowUpdate = true)
    {
		return '';
    }

    /**
     * Remove secret
     *
     * @param CAccount $oAccount
     * @return bool
     */
    public function removeDataValue($oAccount = null)
    {
        return false;
    }
	
    /**
     * Calculate the code, with given secret and point in time
     *
     * @param CAccount $oAccount
     * @return string
     */
    public function getCode($oAccount)
    {
		return '';
	}	
	
    /**
     * Get QR-Code URL for image, from google charts
     *
     * @param string $sName
     * @param string $sDataValue
     * @return string
     */
    public function getQRCode($sName, $sDataValue)
    {
		return '';
	}	
	
    /**
     * Check if the code is correct.
     *
     * @param string $sDataValue
     * @param string $sCode
     * @return bool
     */
	public function verifyCode($sDataValue, $sCode)
	{
        return false;
	}

    /**
     * @param ref $bResult
     */
    public function setAccountIsLoggedIn(&$bResult)
    {
        $bResult = false;
    }
}
