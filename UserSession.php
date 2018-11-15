<?php
/*
 * This code is licensed under AGPLv3 license or Afterlogic Software License
 * if commercial version of the product was purchased.
 * For full statements of the licenses see LICENSE-AFTERLOGIC and LICENSE-AGPL3 files.
 */

namespace Aurora\System;

/**
 * @license https://www.gnu.org/licenses/agpl-3.0.html AGPL-3.0
 * @license https://afterlogic.com/products/common-licensing AfterLogic Software License
 * @copyright Copyright (c) 2018, Afterlogic Corp.
 *
 * @package Api
 */
class UserSession
{
	const TOKEN_VERSION = '2.2';

	public function Set($aData, $iTime = 0)
	{
		$aData['@time'] = $iTime;
		$aData['@ver'] = self::TOKEN_VERSION;
		return Api::EncodeKeyValues(
			$aData
		);
	}

	public function UpdateTimestamp($sAuthToken, $iTime = 0)
	{
		$aData = $this->Get($sAuthToken);
		return $this->Set($aData, $iTime);
	}
	
	public function Get($sAuthToken)
	{
		$mResult = false;
		
		if (strlen($sAuthToken) !== 0) 
		{
			$mResult = Api::DecodeKeyValues($sAuthToken);

			if ($mResult !== false)
			{
				$oUser = \Aurora\Modules\Core\Managers\Integrator::getInstance()->getAuthenticatedUserByIdHelper($mResult['id']);

				if ($oUser && (int) $oUser->TokensValidFromTimestamp > (int) $mResult['@time'])
				{
					$mResult = false;
				}
				else if ((isset($mResult['@ver']) && $mResult['@ver'] !== self::TOKEN_VERSION) || !isset($mResult['@ver']))
				{
					$mResult = false;
				}
				else if ((isset($mResult['sign-me']) && !((bool) $mResult['sign-me'])) || (!isset($mResult['sign-me'])))
				{
					$iTime = 0;
					if (isset($mResult['@time']))
					{
						$iTime = (int) $mResult['@time'];
					}
					$iExpireUserSessionsBefore = Api::GetSettings()->GetConf("ExpireUserSessionsBefore", 0);
					if ($iExpireUserSessionsBefore > $iTime && $iTime > 0)
					{
						\Aurora\System\Api::Log('User session expired: ');
						\Aurora\System\Api::LogObject($mResult);
						$mResult = false;
					}
				}
	
			}
		}
		
		return $mResult;
	}
}
