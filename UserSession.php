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
	public function __construct()
	{
	}

	public function Set($aData, $iTime = 0)
	{
		$aData['@time'] = $iTime;
		$sAuthToken = Api::EncodeKeyValues(
			$aData
		);
		
		return $sAuthToken;
	}
	
	public function Get($sAuthToken)
	{
		$mResult = false;
		
		if (strlen($sAuthToken) !== 0) 
		{
			$mResult = Api::DecodeKeyValues($sAuthToken);
			if (isset($mResult['@time']) && \time() > (int)$mResult['@time'] && (int)$mResult['@time'] > 0)
			{
				\Aurora\System\Api::Log('User session expired: ');
				\Aurora\System\Api::LogObject($mResult);
				$mResult = false;
			}
		}
		
		return $mResult;
	}
}
